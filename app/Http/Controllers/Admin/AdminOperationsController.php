<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CertificateStatus;
use App\Enums\ModuleStatus;
use App\Enums\SessionStatus;
use App\Enums\UserStatus;
use App\Enums\RoleSlug;
use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\User;
use App\Models\Module;
use App\Models\TrainingSession;
use App\Services\Certificate\CertificateService;
use App\Services\Notification\EtmsNotificationService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use App\Services\Report\ReportsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Role;
use App\Models\InstructorProfile;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class AdminOperationsController extends Controller
{
    public function certificateSettings(CertificateService $service): Response
    {
        $this->authorize('viewAny', Certificate::class);

        return Inertia::render('Admin/Certificates/Settings', [
            'settings' => $service->globalSettings(),
        ]);
    }

    public function updateCertificateSettings(Request $request, CertificateService $service): RedirectResponse
    {
        $this->authorize('viewAny', Certificate::class);
        $data = $request->validate([
            'issuing_organisation' => ['required', 'string', 'max:200'],
            'signatory_name' => ['required', 'string', 'max:200'],
            'signatory_title' => ['required', 'string', 'max:200'],
            'logo' => ['nullable', 'image', 'max:5120'],
            'signature' => ['nullable', 'image', 'max:5120'],
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('certificates/logo', 'public');
        }
        $signaturePath = null;
        if ($request->hasFile('signature')) {
            $signaturePath = $request->file('signature')->store('certificates/signatures', 'public');
        }

        $service->updateGlobalSettings(
            admin: $request->user(),
            issuingOrganisation: $data['issuing_organisation'],
            signatoryName: $data['signatory_name'],
            signatoryTitle: $data['signatory_title'],
            logoPath: $logoPath,
            signaturePath: $signaturePath,
        );

        return back()->with('success', 'Certificate signatory settings updated.');
    }
    public function storeUser(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'status' => ['required', 'in:'.implode(',', array_column(UserStatus::cases(), 'value'))],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('roles', 'slug')],
            'profile_phone' => ['nullable', 'string', 'max:30'],
            'profile_job_title' => ['nullable', 'string', 'max:120'],
            'profile_billing_contact_name' => ['nullable', 'string', 'max:150'],
            'profile_billing_email' => ['nullable', 'email', 'max:255'],
            'profile_billing_phone' => ['nullable', 'string', 'max:30'],
            'profile_billing_address_line_1' => ['nullable', 'string', 'max:255'],
            'profile_billing_address_line_2' => ['nullable', 'string', 'max:255'],
            'profile_billing_city' => ['nullable', 'string', 'max:100'],
            'profile_billing_state' => ['nullable', 'string', 'max:100'],
            'profile_billing_postcode' => ['nullable', 'string', 'max:20'],
            'profile_billing_country' => ['nullable', 'string', 'size:2'],
            'instructor_professional_title' => ['nullable', 'string', 'max:150'],
            'instructor_qualifications' => ['nullable', 'string', 'max:255'],
            'instructor_short_bio' => ['nullable', 'string', 'max:500'],
            'instructor_biography' => ['nullable', 'string'],
            'instructor_specialisations' => ['nullable', 'array', 'max:30'],
            'instructor_specialisations.*' => ['string', 'max:100'],
            'instructor_is_public' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'status' => $data['status'],
            'email_verified_at' => now(),
        ]);
        if ($request->hasFile('photo')) {
            $user->photo_storage_disk = 'public';
            $user->photo_storage_key = $request->file('photo')->store('users/avatars', 'public');
            $user->save();
        }
        $user->syncRolesBySlugs($data['roles'], $request->user()->id);
        $this->syncCustomerProfile($user, $data);
        $this->syncInstructorProfile($user, $data);

        AuditLog::record(
            event: 'user.created_by_admin',
            auditableType: 'users',
            auditableId: $user->id,
            newValues: ['email' => $user->email, 'roles' => $data['roles']],
            actor: $request->user(),
        );

        return redirect()->route('admin-vue.users.index')->with('success', 'User created.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'status' => ['required', 'in:'.implode(',', array_column(UserStatus::cases(), 'value'))],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('roles', 'slug')],
            'profile_phone' => ['nullable', 'string', 'max:30'],
            'profile_job_title' => ['nullable', 'string', 'max:120'],
            'profile_billing_contact_name' => ['nullable', 'string', 'max:150'],
            'profile_billing_email' => ['nullable', 'email', 'max:255'],
            'profile_billing_phone' => ['nullable', 'string', 'max:30'],
            'profile_billing_address_line_1' => ['nullable', 'string', 'max:255'],
            'profile_billing_address_line_2' => ['nullable', 'string', 'max:255'],
            'profile_billing_city' => ['nullable', 'string', 'max:100'],
            'profile_billing_state' => ['nullable', 'string', 'max:100'],
            'profile_billing_postcode' => ['nullable', 'string', 'max:20'],
            'profile_billing_country' => ['nullable', 'string', 'size:2'],
            'instructor_professional_title' => ['nullable', 'string', 'max:150'],
            'instructor_qualifications' => ['nullable', 'string', 'max:255'],
            'instructor_short_bio' => ['nullable', 'string', 'max:500'],
            'instructor_biography' => ['nullable', 'string'],
            'instructor_specialisations' => ['nullable', 'array', 'max:30'],
            'instructor_specialisations.*' => ['string', 'max:100'],
            'instructor_is_public' => ['nullable', 'boolean'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $oldRoles = $user->roles()->pluck('slug')->sort()->values()->all();
        $oldValues = ['name' => $user->name, 'email' => $user->email, 'status' => $user->status->value, 'roles' => $oldRoles];
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'status' => $data['status'],
        ]);
        if (filled($data['password'] ?? null)) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();
        if ($request->hasFile('photo')) {
            $oldKey = $user->photo_storage_key;
            $user->photo_storage_disk = 'public';
            $user->photo_storage_key = $request->file('photo')->store('users/avatars', 'public');
            $user->save();
            if ($oldKey && $oldKey !== $user->photo_storage_key) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldKey);
            }
        }
        $user->syncRolesBySlugs($data['roles'], $request->user()->id);
        $this->syncCustomerProfile($user, $data);
        $this->syncInstructorProfile($user, $data);

        AuditLog::record(
            event: 'user.updated_by_admin',
            auditableType: 'users',
            auditableId: $user->id,
            oldValues: $oldValues,
            newValues: ['name' => $user->name, 'email' => $user->email, 'status' => $user->status->value, 'roles' => collect($data['roles'])->sort()->values()->all()],
            actor: $request->user(),
        );

        return redirect()->route('admin-vue.users.index')->with('success', 'User updated.');
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);
        $user->delete();
        AuditLog::record(
            event: 'user.deleted_by_admin',
            auditableType: 'users',
            auditableId: $user->id,
            oldValues: ['email' => $user->email],
            actor: $request->user(),
        );
        return redirect()->route('admin-vue.users.index')->with('success', 'User deleted.');
    }

    private function syncInstructorProfile(User $user, array $data): void
    {
        if (! $user->isInstructor()) {
            $user->instructorProfile()->delete();
            return;
        }

        $profile = $user->instructorProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $data['name'],
                'professional_title' => $data['instructor_professional_title'] ?? null,
                'qualifications' => $data['instructor_qualifications'] ?? null,
                'short_bio' => $data['instructor_short_bio'] ?? null,
                'biography' => $data['instructor_biography'] ?? null,
                'specialisations' => $data['instructor_specialisations'] ?? [],
                'is_public' => (bool) ($data['instructor_is_public'] ?? false),
                'published_at' => ($data['instructor_is_public'] ?? false) ? now() : null,
                'updated_by' => request()->user()?->id,
            ],
        );
    }

    private function syncCustomerProfile(User $user, array $data): void
    {
        $hasProfileInput = collect([
            'profile_phone', 'profile_job_title',
            'profile_billing_contact_name', 'profile_billing_email', 'profile_billing_phone',
            'profile_billing_address_line_1', 'profile_billing_address_line_2', 'profile_billing_city',
            'profile_billing_state', 'profile_billing_postcode',
        ])->contains(fn (string $key): bool => filled($data[$key] ?? null));

        if (! $user->isCustomer() && ! $hasProfileInput && ! $user->customerProfile()->exists()) {
            return;
        }

        $user->customerProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'phone' => $data['profile_phone'] ?? null,
                'job_title' => $data['profile_job_title'] ?? null,
                'billing_contact_name' => $data['profile_billing_contact_name'] ?? null,
                'billing_email' => $data['profile_billing_email'] ?? null,
                'billing_phone' => $data['profile_billing_phone'] ?? null,
                'billing_address_line_1' => $data['profile_billing_address_line_1'] ?? null,
                'billing_address_line_2' => $data['profile_billing_address_line_2'] ?? null,
                'billing_city' => $data['profile_billing_city'] ?? null,
                'billing_state' => $data['profile_billing_state'] ?? null,
                'billing_postcode' => $data['profile_billing_postcode'] ?? null,
                'billing_country' => strtoupper((string) ($data['profile_billing_country'] ?? 'MY')),
            ],
        );
    }
    public function issueCertificate(Request $request, CertificateService $service): RedirectResponse
    {
        $data = $request->validate([
            'trainee_id' => ['required', 'integer', 'exists:users,id'],
            'record_type' => ['required', 'in:session,module'],
            'record_id' => ['required', 'integer'],
            'completion_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $service->issueCertificate(
                admin: $request->user(),
                trainee: User::query()->findOrFail($data['trainee_id']),
                recordType: $data['record_type'],
                recordId: (int) $data['record_id'],
                completionDate: $data['completion_date'],
                notes: $data['notes'] ?? null,
            );
        } catch (DomainException $exception) {
            return back()->withErrors(['certificate' => $exception->getMessage()]);
        }

        return back()->with('success', 'Certificate issuance started.');
    }

    public function changeUserStatus(Request $request, User $user, EtmsNotificationService $notifications): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, 'Administrators cannot change their own account status.');

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_column(UserStatus::cases(), 'value'))],
            'status_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $oldStatus = $user->status->value;
        $user->forceFill([
            'status' => $data['status'],
            'status_reason' => $data['status_reason'],
            'status_changed_at' => now(),
            'status_changed_by' => $request->user()->id,
        ])->save();

        \App\Models\AuditLog::record(
            event: 'user.status_changed',
            auditableType: 'users',
            auditableId: $user->id,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $data['status']],
            reason: $data['status_reason'],
            actor: $request->user(),
        );

        $notifications->notifyUser(
            user: $user,
            eventType: 'user.status_changed',
            title: 'Account Status Updated',
            message: "Your Ecohydrotech ETMS account status has been changed from {$oldStatus} to {$data['status']}.",
            actionUrl: route('portal.dashboard', [], false),
            actionLabel: 'View Account',
            details: ['Previous Status' => ucfirst($oldStatus), 'New Status' => ucfirst($data['status']), 'Reason' => $data['status_reason']],
        );

        return back()->with('success', 'User account status updated.');
    }

    public function revokeCertificate(Request $request, Certificate $certificate, CertificateService $service): RedirectResponse
    {
        $data = $request->validate(['revocation_reason' => ['required', 'string', 'max:500']]);

        try {
            $service->revokeCertificate($certificate, $request->user(), $data['revocation_reason']);
        } catch (DomainException $exception) {
            return back()->withErrors(['certificate' => $exception->getMessage()]);
        }

        return back()->with('success', 'Certificate revoked.');
    }

    public function users(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $users = User::query()->with(['roles:id,name,slug', 'customerProfile', 'instructorProfile'])->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))->when($status !== '' && UserStatus::tryFrom($status), fn ($q) => $q->where('status', $status))->latest()->paginate(15)->withQueryString()->through(static fn (User $user): array => [
            'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'roles' => $user->roles->pluck('slug')->values()->all(),
            'roleLabels' => $user->roles->pluck('name')->values()->all(),
            'status' => $user->status instanceof UserStatus ? $user->status->label() : (string) $user->status,
            'statusValue' => $user->status instanceof UserStatus ? $user->status->value : (string) $user->status,
            'verified' => $user->email_verified_at !== null,
            'photoUrl' => $user->photoUrl(),
            'profile_phone' => $user->customerProfile?->phone,
            'profile_job_title' => $user->customerProfile?->job_title,
            'profile_billing_contact_name' => $user->customerProfile?->billing_contact_name,
            'profile_billing_email' => $user->customerProfile?->billing_email,
            'profile_billing_phone' => $user->customerProfile?->billing_phone,
            'profile_billing_address_line_1' => $user->customerProfile?->billing_address_line_1,
            'profile_billing_address_line_2' => $user->customerProfile?->billing_address_line_2,
            'profile_billing_city' => $user->customerProfile?->billing_city,
            'profile_billing_state' => $user->customerProfile?->billing_state,
            'profile_billing_postcode' => $user->customerProfile?->billing_postcode,
            'profile_billing_country' => $user->customerProfile?->billing_country ?: 'MY',
            'instructor_professional_title' => $user->instructorProfile?->professional_title,
            'instructor_qualifications' => $user->instructorProfile?->qualifications,
            'instructor_short_bio' => $user->instructorProfile?->short_bio,
            'instructor_biography' => $user->instructorProfile?->biography,
            'instructor_specialisations' => $user->instructorProfile?->specialisations ?? [],
            'instructor_is_public' => $user->instructorProfile?->is_public ?? false,
        ]);
        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => ['q' => $search, 'status' => $status],
            'statuses' => collect(UserStatus::cases())->map(fn (UserStatus $s): array => ['value' => $s->value, 'label' => $s->label()])->values()->all(),
            'roles' => Role::query()->orderBy('name')->get(['id', 'slug', 'name'])->map(static fn (Role $role): array => [
                'value' => $role->slug,
                'label' => $role->name,
            ])->values()->all(),
        ]);
    }

    public function certificates(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $certificates = Certificate::query()->with(['user:id,name,email', 'trainingSession:id,session_code,title'])->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('certificate_number', 'like', "%{$search}%")->orWhere('trainee_name_snapshot', 'like', "%{$search}%")->orWhere('training_title_snapshot', 'like', "%{$search}%")))->when($status !== '' && CertificateStatus::tryFrom($status), fn ($q) => $q->where('status', $status))->latest('issued_at')->paginate(15)->withQueryString()->through(static fn (Certificate $certificate): array => [
            'id' => $certificate->id, 'number' => $certificate->certificate_number, 'trainee' => $certificate->trainee_name_snapshot, 'title' => $certificate->training_title_snapshot, 'recordType' => $certificate->record_type, 'completionDate' => $certificate->completion_date?->toDateString(), 'issuedAt' => $certificate->issued_at?->toIso8601String(), 'status' => $certificate->status instanceof CertificateStatus ? $certificate->status->label() : (string) $certificate->status,
        ]);
        return Inertia::render('Admin/Certificates/Index', [
            'certificates' => $certificates,
            'filters' => ['q' => $search, 'status' => $status],
            'statuses' => collect(CertificateStatus::cases())->map(fn (CertificateStatus $s): array => ['value' => $s->value, 'label' => $s->label()])->values()->all(),
            'trainees' => User::query()->where('status', UserStatus::Active->value)->orderBy('name')->get(['id', 'name', 'email']),
            'sessions' => TrainingSession::query()->where('status', SessionStatus::Completed->value)->orderByDesc('starts_at')->get(['id', 'session_code', 'title']),
            'modules' => Module::query()->where('status', '!=', ModuleStatus::Archived->value)->orderBy('title')->get(['id', 'code', 'title']),
        ]);
    }

    public function reports(Request $request, ReportsService $reports): Response
    {
        $from = is_string($request->query('from')) ? $request->query('from') : null;
        $to = is_string($request->query('to')) ? $request->query('to') : null;
        return Inertia::render('Admin/Reports/Index', ['report' => $reports->generate($from, $to), 'filters' => ['from' => $from ?? '', 'to' => $to ?? ''], 'exportUrls' => ['bookings' => route('admin.reports.export', ['dataset' => 'bookings']), 'revenue' => route('admin.reports.export', ['dataset' => 'revenue']), 'refunds' => route('admin.reports.export', ['dataset' => 'refunds'])]]);
    }
}
