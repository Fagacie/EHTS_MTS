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
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Role;
use App\Models\InstructorProfile;
use App\Models\AuditLog;
use App\Models\SystemSetting;
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
            'signatureDataUri' => $service->resolveSignatureDataUri(),
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
            'signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,svg,webp', 'max:5120'],
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

        return back()->with('status', 'Certificate signatory settings updated.');
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

        return redirect()->route('admin-vue.users.index')->with('status', 'User created.');
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

        return redirect()->route('admin-vue.users.index')->with('status', 'User updated.');
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
        return redirect()->route('admin-vue.users.index')->with('status', 'User deleted.');
    }

    private function syncInstructorProfile(User $user, array $data): void
    {
        if (! $user->isInstructor()) {
            $user->instructorProfile()->delete();
            return;
        }

        $isPublic = array_key_exists('instructor_is_public', $data)
            ? (bool) $data['instructor_is_public']
            : true;

        $existingProfile = $user->instructorProfile;

        $user->instructorProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => $data['name'],
                'professional_title' => $data['instructor_professional_title'] ?? null,
                'qualifications' => $data['instructor_qualifications'] ?? null,
                'short_bio' => $data['instructor_short_bio'] ?? null,
                'biography' => $data['instructor_biography'] ?? null,
                'specialisations' => $data['instructor_specialisations'] ?? [],
                'is_public' => $isPublic,
                'published_at' => $isPublic ? ($existingProfile?->published_at ?? now()) : null,
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

        return back()->with('status', 'Certificate issued and published to trainee portal.');
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

        return back()->with('status', 'User account status updated.');
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
        $role = (string) $request->query('role', '');

        $users = User::query()
            ->with(['roles:id,name,slug', 'customerProfile', 'instructorProfile'])
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($status !== '' && UserStatus::tryFrom($status), fn ($q) => $q->where('status', $status))
            ->when($role !== '' && RoleSlug::tryFrom($role), fn ($q) => $q->whereHas('roles', fn ($rq) => $rq->where('slug', $role)))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(static fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
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
            'filters' => ['q' => $search, 'status' => $status, 'role' => $role],
            'statuses' => collect(UserStatus::cases())->map(fn (UserStatus $s): array => ['value' => $s->value, 'label' => $s->label()])->values()->all(),
            'roles' => Role::query()->orderBy('name')->get(['id', 'slug', 'name'])->map(static fn (Role $r): array => [
                'value' => $r->slug,
                'label' => $r->name,
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

        return Inertia::render('Admin/Reports/Index', [
            'report' => $reports->generate($from, $to),
            'filters' => [
                'from' => $from ?? '',
                'to' => $to ?? '',
            ],
            'pdfUrl' => route('admin-vue.reports.pdf'),
            'exportUrls' => [
                'sessions' => route('admin-vue.reports.export', ['dataset' => 'sessions']),
                'bookings' => route('admin-vue.reports.export', ['dataset' => 'bookings']),
                'attendance' => route('admin-vue.reports.export', ['dataset' => 'attendance']),
                'revenue' => route('admin-vue.reports.export', ['dataset' => 'revenue']),
                'refunds' => route('admin-vue.reports.export', ['dataset' => 'refunds']),
                'popular_modules' => route('admin-vue.reports.export', ['dataset' => 'popular_modules']),
                'certificates' => route('admin-vue.reports.export', ['dataset' => 'certificates']),
                'instructors' => route('admin-vue.reports.export', ['dataset' => 'instructors']),
            ],
        ]);
    }

    public function downloadReportPdf(Request $request, ReportsService $reports): HttpResponse
    {
        $from = is_string($request->query('from')) ? $request->query('from') : null;
        $to = is_string($request->query('to')) ? $request->query('to') : null;
        $section = is_string($request->query('section')) ? $request->query('section') : 'all';
        $allowedSections = ['all', 'sessions', 'bookings', 'attendance', 'finance', 'modules', 'certificates'];
        if (! in_array($section, $allowedSections, true)) {
            $section = 'all';
        }

        $sectionTitles = [
            'all' => 'COMPREHENSIVE SYSTEM REPORT',
            'sessions' => 'SESSIONS & PARTICIPANTS REPORT',
            'bookings' => 'BOOKINGS & REGISTRATIONS REPORT',
            'attendance' => 'ATTENDANCE VERIFICATION REPORT',
            'finance' => 'REVENUE, PAYMENTS & REFUNDS REPORT',
            'modules' => 'MODULE SALES & PERFORMANCE REPORT',
            'certificates' => 'CERTIFICATES & INSTRUCTORS REPORT',
        ];

        $tz = config('etms.display_timezone', 'Asia/Kuala_Lumpur');
        $report = $reports->generate($from, $to);

        $fromLabel = ! empty($report['filters']['from'])
            ? Carbon::parse($report['filters']['from'], $tz)->format('d M Y')
            : null;
        $toLabel = ! empty($report['filters']['to'])
            ? Carbon::parse($report['filters']['to'], $tz)->format('d M Y')
            : null;

        $periodLabel = match (true) {
            $fromLabel !== null && $toLabel !== null => "{$fromLabel} – {$toLabel}",
            $fromLabel !== null => "From {$fromLabel}",
            $toLabel !== null => "Up to {$toLabel}",
            default => 'All Time',
        };

        $logoDataUri = null;
        $jpegLogoPath = public_path('images/ecohydrotech-logo-pdf.jpg');
        if (is_file($jpegLogoPath)) {
            $logoDataUri = 'data:image/jpeg;base64,'.base64_encode((string) file_get_contents($jpegLogoPath));
        }

        $pdf = Pdf::loadView('pdf.report', [
            'report' => $report,
            'section' => $section,
            'reportTitle' => $sectionTitles[$section],
            'periodLabel' => $periodLabel,
            'generatedAtLabel' => now($tz)->format('d M Y, h:i A').' MYT',
            'preparedBy' => $request->user()?->name ?? 'Administrator',
            'logoDataUri' => $logoDataUri,
        ])->setPaper('a4', 'portrait');

        $stamp = now($tz)->format('Ymd-His');
        $slug = $section === 'all' ? 'Full-Report' : ucfirst($section).'-Report';
        $filename = "Ecohydrotech-{$slug}-{$stamp}.pdf";

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function exportReport(Request $request, string $dataset, ReportsService $reports): StreamedResponse
    {
        $from = is_string($request->query('from')) ? $request->query('from') : null;
        $to = is_string($request->query('to')) ? $request->query('to') : null;

        $csv = $reports->buildCsvDataset($dataset, $from, $to);

        return response()->streamDownload(function () use ($csv): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $csv['headers']);
            foreach ($csv['rows'] as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $csv['filename'], [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function systemSettings(): Response
    {
        return Inertia::render('Admin/Settings/Index', [
            'settings' => [
                'organisation_name' => (string) SystemSetting::getValue('system.organisation_name', config('etms.brand_name', 'Ecohydrotech Solutions Sdn. Bhd.')),
                'short_name' => (string) SystemSetting::getValue('system.short_name', config('etms.short_name', 'ETMS')),
                'support_email' => (string) SystemSetting::getValue('system.support_email', config('etms.support_email', 'no-reply@ecohydrotech.com.my')),
                'support_phone' => (string) SystemSetting::getValue('system.support_phone', ''),
                'company_address' => (string) SystemSetting::getValue('system.company_address', 'Malaysia'),
                'display_timezone' => (string) SystemSetting::getValue('system.display_timezone', config('etms.display_timezone', 'Asia/Kuala_Lumpur')),
                'currency' => (string) SystemSetting::getValue('system.currency', 'MYR'),
                'booking_hold_minutes' => (int) SystemSetting::getValue('bookings.hold_minutes', 15),
                'auto_close_hours' => (int) SystemSetting::getValue('bookings.auto_close_hours', 0),
                'default_email_notifications' => (bool) SystemSetting::getValue('notifications.default_email', true),
                'default_in_app_notifications' => (bool) SystemSetting::getValue('notifications.default_in_app', true),
                'booking_confirmation_email' => (bool) SystemSetting::getValue('notifications.booking_confirmation_email', true),
                'certificate_issued_email' => (bool) SystemSetting::getValue('notifications.certificate_issued_email', true),
            ],
            'environment' => [
                'appEnv' => (string) config('app.env'),
                'laravelVersion' => app()->version(),
                'phpVersion' => PHP_VERSION,
                'paymentGateway' => ucfirst((string) config('payments.default', env('PAYMENT_GATEWAY', 'billplz'))),
                'videoTokenTtlMinutes' => (int) round(((int) config('etms.video.token_ttl_seconds', 3600)) / 60),
                'backupRetentionDays' => (int) config('etms.backup.retention_days', 14),
            ],
        ]);
    }

    public function updateSystemSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'organisation_name' => ['required', 'string', 'max:150'],
            'short_name' => ['required', 'string', 'max:30'],
            'support_email' => ['required', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:40'],
            'company_address' => ['nullable', 'string', 'max:500'],
            'display_timezone' => ['required', 'in:Asia/Kuala_Lumpur,Asia/Singapore,UTC'],
            'currency' => ['required', 'in:MYR,USD,SGD,NGN'],
            'booking_hold_minutes' => ['required', 'integer', 'min:5', 'max:60'],
            'auto_close_hours' => ['required', 'integer', 'min:0', 'max:168'],
            'default_email_notifications' => ['required', 'boolean'],
            'default_in_app_notifications' => ['required', 'boolean'],
            'booking_confirmation_email' => ['required', 'boolean'],
            'certificate_issued_email' => ['required', 'boolean'],
        ]);

        $actorId = $request->user()?->id;

        SystemSetting::setValue('system.organisation_name', $data['organisation_name'], 'general', 'string', 'Primary organisation name displayed across ETMS.', true, $actorId);
        SystemSetting::setValue('system.short_name', $data['short_name'], 'general', 'string', 'Short platform identifier.', true, $actorId);
        SystemSetting::setValue('system.support_email', $data['support_email'], 'general', 'string', 'Primary support contact email.', true, $actorId);
        SystemSetting::setValue('system.support_phone', $data['support_phone'] ?? '', 'general', 'string', 'Support contact phone number.', true, $actorId);
        SystemSetting::setValue('system.company_address', $data['company_address'] ?? '', 'general', 'string', 'Organisation mailing address.', false, $actorId);
        SystemSetting::setValue('system.display_timezone', $data['display_timezone'], 'commerce', 'string', 'Default display timezone for schedules and reports.', true, $actorId);
        SystemSetting::setValue('system.currency', $data['currency'], 'commerce', 'string', 'Default billing currency.', true, $actorId);
        SystemSetting::setValue('bookings.hold_minutes', (int) $data['booking_hold_minutes'], 'commerce', 'integer', 'Seat reservation hold duration in minutes.', false, $actorId);
        SystemSetting::setValue('bookings.auto_close_hours', (int) $data['auto_close_hours'], 'commerce', 'integer', 'Hours before session start to automatically close registration.', false, $actorId);
        SystemSetting::setValue('notifications.default_email', (bool) $data['default_email_notifications'], 'notifications', 'boolean', 'Enable email channel by default for new announcements.', false, $actorId);
        SystemSetting::setValue('notifications.default_in_app', (bool) $data['default_in_app_notifications'], 'notifications', 'boolean', 'Enable in-app notifications by default for new announcements.', false, $actorId);
        SystemSetting::setValue('notifications.booking_confirmation_email', (bool) $data['booking_confirmation_email'], 'notifications', 'boolean', 'Send email confirmation upon verified booking payment.', false, $actorId);
        SystemSetting::setValue('notifications.certificate_issued_email', (bool) $data['certificate_issued_email'], 'notifications', 'boolean', 'Send email alert when a certificate is issued.', false, $actorId);

        AuditLog::record(
            event: 'system_settings.updated',
            auditableType: SystemSetting::class,
            auditableId: 0,
            newValues: $data,
            actor: $request->user(),
        );

        return back()->with('status', 'System settings updated.');
    }
}
