<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SessionStatus;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Attendance;
use App\Models\Booking;
use App\Models\Module;
use App\Models\SessionRecording;
use App\Models\TrainingSession;
use App\Services\Booking\SessionBookingService;
use App\Services\Notification\EtmsNotificationService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use App\Enums\RecordingAccessPolicy;
use App\Models\User;
use App\Models\SessionInstructor;
use App\Models\SessionMaterial;
use App\Models\Category;
use App\Enums\RoleSlug;
use App\Enums\MaterialType;
use App\Enums\SessionMaterialStatus;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Http\RedirectResponse;

class AdminTrainingSessionController extends Controller
{
    public function show(Request $request, TrainingSession $trainingSession): Response
    {
        $this->authorize('view', $trainingSession);

        $trainingSession->load([
            'module:id,code,title',
            'instructors:id,name',
            'mainDocumentMaterial',
            'completer:id,name',
        ]);

        $participantSearch = trim((string) $request->query('q', ''));
        $participantStatus = (string) $request->query('booking_status', '');

        $participants = $trainingSession->bookings()
            ->with([
                'user:id,name,email',
                'order:id,order_number,status,total_amount,paid_at',
                'order.payments:id,order_id,status,amount,paid_at',
                'attendance:id,booking_id,attendance_status,review_status,submitted_at,verified_at,verification_notes',
            ])
            ->when($participantSearch !== '', fn ($query) => $query->where(function ($query) use ($participantSearch): void {
                $query->where('booking_reference', 'like', "%{$participantSearch}%")
                    ->orWhereHas('user', fn ($user) => $user
                        ->where('name', 'like', "%{$participantSearch}%")
                        ->orWhere('email', 'like', "%{$participantSearch}%"));
            }))
            ->when($participantStatus !== '' && BookingStatus::tryFrom($participantStatus), fn ($query) => $query->where('status', $participantStatus))
            ->latest('created_at')
            ->paginate(15, ['*'], 'participants')
            ->withQueryString()
            ->through(static fn (Booking $booking): array => [
                'id' => $booking->id,
                'reference' => $booking->booking_reference,
                'participant' => [
                    'name' => $booking->user?->name ?? 'Unknown',
                    'email' => $booking->user?->email,
                ],
                'bookingStatus' => $booking->status instanceof BookingStatus ? $booking->status->label() : (string) $booking->status,
                'bookingStatusValue' => $booking->status instanceof BookingStatus ? $booking->status->value : (string) $booking->status,
                'paymentStatus' => $booking->order?->payments?->first()?->status instanceof PaymentStatus
                    ? $booking->order->payments->first()->status->label()
                    : (string) ($booking->order?->payments?->first()?->status ?? 'Not available'),
                'attendanceStatus' => $booking->attendance?->attendance_status?->label(),
                'attendanceReviewStatus' => $booking->attendance?->review_status?->label(),
                'registeredAt' => $booking->created_at?->toIso8601String(),
                'detailUrl' => route('admin-vue.bookings.show', $booking),
            ]);

        $attendance = $trainingSession->attendances()
            ->with(['user:id,name,email', 'submitter:id,name', 'verifier:id,name'])
            ->latest('submitted_at')
            ->get()
            ->map(static fn (Attendance $record): array => [
                'id' => $record->id,
                'bookingId' => $record->booking_id,
                'participant' => [
                    'name' => $record->user?->name ?? 'Unknown',
                    'email' => $record->user?->email,
                ],
                'attendanceStatus' => $record->attendance_status?->label(),
                'reviewStatus' => $record->review_status?->label(),
                'submittedAt' => $record->submitted_at?->toIso8601String(),
                'verifiedAt' => $record->verified_at?->toIso8601String(),
                'submittedBy' => $record->submitter?->name,
                'verifiedBy' => $record->verifier?->name,
                'notes' => $record->verification_notes,
            ]);

        $materials = $trainingSession->materials()
            ->get()
            ->map(static fn (SessionMaterial $material): array => [
                'id' => $material->id,
                'title' => $material->title,
                'fileName' => $material->original_filename,
                'description' => $material->description,
                'type' => $material->material_type?->label(),
                'status' => $material->status?->value,
                'isMainDocument' => $material->is_main_document,
                'isDownloadable' => $material->is_downloadable,
            ])
            ->values()
            ->all();

        $recordings = $trainingSession->recordings()
            ->with(['submitter:id,name', 'reviewer:id,name'])
            ->get()
            ->map(static fn (SessionRecording $recording): array => [
                'id' => $recording->id,
                'title' => $recording->title,
                'description' => $recording->description,
                'status' => $recording->status?->value,
                'encodingStatus' => $recording->encoding_status?->value,
                'submittedBy' => $recording->submitter?->name,
                'reviewedBy' => $recording->reviewer?->name,
                'submittedAt' => $recording->submitted_at?->toIso8601String(),
                'reviewNotes' => $recording->review_notes,
            ])
            ->values()
            ->all();

        $eligibleCount = $trainingSession->attendances()
            ->where('attendance_status', 'present')
            ->where('review_status', 'verified')
            ->count();
        $certificateCount = $trainingSession->certificates()->count();
        $eligibleUserIds = $trainingSession->attendances()
            ->where('attendance_status', 'present')
            ->where('review_status', 'verified')
            ->pluck('user_id')
            ->unique()
            ->values();

        return Inertia::render('Admin/TrainingSessions/Show', [
            'session' => [
                'id' => $trainingSession->id,
                'title' => $trainingSession->title,
                'description' => $trainingSession->description,
                'status' => $trainingSession->status instanceof SessionStatus ? $trainingSession->status->label() : (string) $trainingSession->status,
                'statusValue' => $trainingSession->status instanceof SessionStatus ? $trainingSession->status->value : (string) $trainingSession->status,
                'sessionCode' => $trainingSession->session_code,
                'startsAt' => $trainingSession->starts_at?->toIso8601String(),
                'endsAt' => $trainingSession->ends_at?->toIso8601String(),
                'registrationOpensAt' => $trainingSession->registration_opens_at?->toIso8601String(),
                'registrationClosesAt' => $trainingSession->registration_closes_at?->toIso8601String(),
                'venueName' => $trainingSession->venue_name,
                'venueAddress' => $trainingSession->venue_address,
                'venueCity' => $trainingSession->venue_city,
                'venueState' => $trainingSession->venue_state,
                'capacity' => $trainingSession->capacity,
                'confirmedCount' => (int) $trainingSession->confirmed_seats_count,
                'reservedCount' => (int) $trainingSession->reserved_seats_count,
                'price' => number_format((float) $trainingSession->price, 2, '.', ''),
                'module' => $trainingSession->module ? ['code' => $trainingSession->module->code, 'title' => $trainingSession->module->title] : null,
                'instructors' => $trainingSession->instructors->map(fn (User $instructor): array => [
                    'name' => $instructor->name,
                    'role' => $instructor->pivot?->role_label,
                ])->values()->all(),
                'isAvailableIndependently' => $trainingSession->is_available_independently,
                'independentPrice' => $trainingSession->independent_price === null ? null : number_format((float) $trainingSession->independent_price, 2, '.', ''),
                'mainDocument' => $trainingSession->mainDocumentMaterial ? [
                    'id' => $trainingSession->mainDocumentMaterial->id,
                    'title' => $trainingSession->mainDocumentMaterial->title,
                    'fileName' => $trainingSession->mainDocumentMaterial->original_filename,
                    'status' => $trainingSession->mainDocumentMaterial->status?->value,
                ] : null,
                'editUrl' => route('admin-vue.sessions.edit', $trainingSession),
            ],
            'participants' => $participants,
            'participantFilters' => ['q' => $participantSearch, 'booking_status' => $participantStatus],
            'participantStatuses' => collect(BookingStatus::cases())->map(fn (BookingStatus $status): array => ['value' => $status->value, 'label' => $status->label()])->values()->all(),
            'materials' => $materials,
            'recordings' => $recordings,
            'attendance' => $attendance,
            'completion' => [
                'submitted' => filled($trainingSession->completion_submitted_at),
                'submittedAt' => $trainingSession->completion_submitted_at?->toIso8601String(),
                'submittedBy' => $trainingSession->completer?->name,
                'notes' => $trainingSession->completion_notes,
                'status' => $trainingSession->status instanceof SessionStatus ? $trainingSession->status->value : (string) $trainingSession->status,
                'eligibleParticipantCount' => $eligibleCount,
                'eligibleUserIds' => $eligibleUserIds,
                'issuedCertificateCount' => $certificateCount,
            ],
            'materialTypes' => collect(MaterialType::cases())->map(fn (MaterialType $type): array => ['value' => $type->value, 'label' => $type->label()])->values()->all(),
            'materialStatuses' => collect(SessionMaterialStatus::cases())->map(fn (SessionMaterialStatus $status): array => ['value' => $status->value, 'label' => ucfirst(str_replace('_', ' ', $status->value))])->values()->all(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', TrainingSession::class);
        return Inertia::render('Admin/TrainingSessions/Form', [
            'mode' => 'create',
            'statuses' => collect(SessionStatus::cases())->map(fn (SessionStatus $status): array => ['value' => $status->value, 'label' => $status->label()])->values()->all(),
            'recordingPolicies' => collect(RecordingAccessPolicy::cases())->map(fn (RecordingAccessPolicy $policy): array => ['value' => $policy->value, 'label' => $policy->label()])->values()->all(),
            'instructors' => $this->instructorOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', TrainingSession::class);
        $data = $request->validate($this->sessionRules(false));
        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt = Carbon::parse($data['ends_at']);
        $status = SessionStatus::from($data['status']);
        $sessionCode = 'SES-'.now()->format('Y').'-'.strtoupper(Str::random(6));
        $compatibilityModule = $this->createCompatibilityModule(
            title: $data['title'],
            description: $data['description'] ?? null,
            code: $sessionCode,
            actorId: $request->user()->id,
            coverPath: $request->hasFile('cover_image')
                ? $request->file('cover_image')->store('modules/covers', 'public')
                : null,
        );
        $session = TrainingSession::query()->create([
            'module_id' => $compatibilityModule->id,
            'session_code' => $sessionCode,
            'title' => $data['title'],
            'slug' => Str::slug($data['title']).'-'.strtolower(Str::random(6)),
            'description' => $data['description'] ?? null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'duration_minutes' => $startsAt->diffInMinutes($endsAt),
            'timezone' => config('etms.display_timezone', 'Asia/Kuala_Lumpur'),
            'registration_opens_at' => filled($data['registration_opens_at'] ?? null) ? Carbon::parse($data['registration_opens_at']) : null,
            'registration_closes_at' => Carbon::parse($data['registration_closes_at']),
            'venue_name' => $data['venue_name'],
            'venue_address' => $data['venue_address'],
            'venue_city' => $data['venue_city'],
            'venue_state' => $data['venue_state'],
            'capacity' => $data['capacity'],
            'reserved_seats_count' => 0,
            'confirmed_seats_count' => 0,
            'price' => $data['price'],
            'currency' => 'MYR',
            'recording_access_policy' => $data['recording_access_policy'] ?? RecordingAccessPolicy::AttendeesOnly->value,
            'is_available_independently' => (bool) ($data['is_available_independently'] ?? false),
            'independent_price' => ($data['is_available_independently'] ?? false) ? ($data['independent_price'] ?? null) : null,
            'status' => $status,
            'published_at' => $status === SessionStatus::Published ? now() : null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);
        $this->syncInstructors($session, $data['instructors'] ?? [], $request->user()->id);
        AuditLog::record(
            event: 'session.created',
            auditableType: TrainingSession::class,
            auditableId: $session->id,
            newValues: ['session_code' => $session->session_code, 'title' => $session->title, 'status' => $status->value],
            actor: $request->user(),
        );
        return redirect()->route('admin-vue.sessions.index')->with('status', 'Training session created.');
    }

    public function edit(TrainingSession $trainingSession): Response
    {
        $this->authorize('update', $trainingSession);
        $trainingSession->load(['module:id,cover_image_disk,cover_image_path', 'instructors:id,name', 'materials:id,training_session_id,title,description,material_type,status,original_filename,is_downloadable,is_main_document']);
        return Inertia::render('Admin/TrainingSessions/Form', [
            'mode' => 'edit',
            'session' => [
                'id' => $trainingSession->id,
                'title' => $trainingSession->title,
                'description' => $trainingSession->description,
                'starts_at' => $trainingSession->starts_at?->timezone(config('etms.display_timezone'))->format('Y-m-d\\TH:i'),
                'ends_at' => $trainingSession->ends_at?->timezone(config('etms.display_timezone'))->format('Y-m-d\\TH:i'),
                'registration_opens_at' => $trainingSession->registration_opens_at?->timezone(config('etms.display_timezone'))->format('Y-m-d\\TH:i'),
                'registration_closes_at' => $trainingSession->registration_closes_at?->timezone(config('etms.display_timezone'))->format('Y-m-d\\TH:i'),
                'venue_name' => $trainingSession->venue_name,
                'venue_address' => $trainingSession->venue_address,
                'venue_city' => $trainingSession->venue_city,
                'venue_state' => $trainingSession->venue_state,
                'capacity' => $trainingSession->capacity,
                'price' => $trainingSession->price,
                'recording_access_policy' => $trainingSession->recording_access_policy->value,
                'is_available_independently' => $trainingSession->is_available_independently,
                'independent_price' => $trainingSession->independent_price,
                'main_document_material_id' => $trainingSession->main_document_material_id,
                'cover_image_url' => $trainingSession->module?->coverImageUrl(),
                'status' => $trainingSession->status->value,
                'instructors' => $trainingSession->sessionInstructors()->with('instructor:id,name')->get()->map(fn (SessionInstructor $assignment): array => [
                    'instructor_id' => (string) $assignment->instructor_id,
                    'role_label' => $assignment->role_label,
                ])->values()->all(),
                'materials' => $trainingSession->materials->map(fn (SessionMaterial $material): array => [
                    'id' => $material->id,
                    'title' => $material->title,
                    'description' => $material->description,
                    'material_type' => $material->material_type->value,
                    'status' => $material->status->value,
                    'original_filename' => $material->original_filename,
                    'is_downloadable' => $material->is_downloadable,
                    'is_main_document' => $material->is_main_document,
                ])->values()->all(),
            ],
            'statuses' => collect(SessionStatus::cases())->map(fn (SessionStatus $status): array => ['value' => $status->value, 'label' => $status->label()])->values()->all(),
            'recordingPolicies' => collect(RecordingAccessPolicy::cases())->map(fn (RecordingAccessPolicy $policy): array => ['value' => $policy->value, 'label' => $policy->label()])->values()->all(),
            'instructors' => $this->instructorOptions(),
            'materialTypes' => collect(MaterialType::cases())->map(fn (MaterialType $type): array => ['value' => $type->value, 'label' => $type->label()])->values()->all(),
            'materialStatuses' => collect(SessionMaterialStatus::cases())->map(fn (SessionMaterialStatus $status): array => ['value' => $status->value, 'label' => ucfirst(str_replace('_', ' ', $status->value))])->values()->all(),
        ]);
    }

    public function update(Request $request, TrainingSession $trainingSession): RedirectResponse
    {
        $this->authorize('update', $trainingSession);
        $data = $request->validate($this->sessionRules(true));
        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt = Carbon::parse($data['ends_at']);
        $oldStatus = $trainingSession->status->value;
        $status = SessionStatus::from($data['status']);
        $compatibilityModule = $trainingSession->module_id
            ? $trainingSession->module()->first()
            : null;
        if (! $compatibilityModule) {
            $compatibilityModule = $this->createCompatibilityModule(
                title: $data['title'],
                description: $data['description'] ?? null,
                code: $trainingSession->session_code,
                actorId: $request->user()->id,
                coverPath: $request->hasFile('cover_image')
                    ? $request->file('cover_image')->store('modules/covers', 'public')
                    : null,
            );
        } else {
            $oldCover = $compatibilityModule->cover_image_path;
            $newCover = $request->hasFile('cover_image')
                ? $request->file('cover_image')->store('modules/covers', 'public')
                : null;
            $compatibilityModule->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? $compatibilityModule->description,
                'updated_by' => $request->user()->id,
                ...($newCover ? [
                    'cover_image_disk' => 'public',
                    'cover_image_path' => $newCover,
                ] : []),
            ]);
            if ($oldCover && $newCover && $oldCover !== $newCover) {
                Storage::disk('public')->delete($oldCover);
            }
        }
        $trainingSession->fill([
            'module_id' => $compatibilityModule->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'duration_minutes' => $startsAt->diffInMinutes($endsAt),
            'registration_opens_at' => filled($data['registration_opens_at'] ?? null) ? Carbon::parse($data['registration_opens_at']) : null,
            'registration_closes_at' => Carbon::parse($data['registration_closes_at']),
            'venue_name' => $data['venue_name'],
            'venue_address' => $data['venue_address'],
            'venue_city' => $data['venue_city'],
            'venue_state' => $data['venue_state'],
            'capacity' => $data['capacity'],
            'price' => $data['price'],
            'recording_access_policy' => $data['recording_access_policy'] ?? RecordingAccessPolicy::AttendeesOnly->value,
            'is_available_independently' => (bool) ($data['is_available_independently'] ?? false),
            'independent_price' => ($data['is_available_independently'] ?? false) ? ($data['independent_price'] ?? null) : null,
            'main_document_material_id' => ($data['is_available_independently'] ?? false) ? ($data['main_document_material_id'] ?? null) : null,
            'status' => $status,
            'published_at' => $status === SessionStatus::Published ? ($trainingSession->published_at ?? now()) : $trainingSession->published_at,
            'updated_by' => $request->user()->id,
        ]);
        $trainingSession->save();
        $this->syncInstructors($trainingSession, $data['instructors'] ?? [], $request->user()->id);
        AuditLog::record(
            event: 'session.updated',
            auditableType: TrainingSession::class,
            auditableId: $trainingSession->id,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $status->value],
            actor: $request->user(),
        );
        return redirect()->route('admin-vue.sessions.index')->with('status', 'Training session updated.');
    }

    public function destroy(Request $request, TrainingSession $trainingSession): RedirectResponse
    {
        if ($trainingSession->bookings()->exists()) {
            abort(422, 'This session cannot be deleted because it has bookings or historical commercial records.');
        }
        if ($trainingSession->certificates()->exists()) {
            abort(422, 'This session cannot be deleted because certificates have been issued.');
        }
        if ($trainingSession->attendances()->exists()) {
            abort(422, 'This session cannot be deleted because attendance records exist.');
        }
        if ($trainingSession->recordings()->exists()) {
            abort(422, 'This session cannot be deleted because session recordings exist.');
        }
        if ($trainingSession->materials()->exists()) {
            abort(422, 'This session cannot be deleted because learning or session material records exist.');
        }
        if ($trainingSession->sessionDocumentEntitlements()->exists()) {
            abort(422, 'This session cannot be deleted because document entitlements exist.');
        }

        $this->authorize('delete', $trainingSession);
        $code = $trainingSession->session_code;
        $title = $trainingSession->title;
        $compatibilityModule = $trainingSession->module;

        DB::transaction(function () use ($trainingSession, $compatibilityModule): void {
            $trainingSession->sessionInstructors()->delete();
            $trainingSession->delete();

            if (
                $compatibilityModule
                && $compatibilityModule->category?->slug === 'training-sessions'
                && $compatibilityModule->trainingSessions()->count() === 0
                && ! $compatibilityModule->entitlements()->exists()
                && ! $compatibilityModule->certificates()->exists()
                && ! $compatibilityModule->materials()->exists()
                && ! $compatibilityModule->videos()->exists()
            ) {
                $category = $compatibilityModule->category;
                $compatibilityModule->forceDelete();
                if ($category && $category->slug === 'training-sessions' && ! $category->modules()->exists()) {
                    $category->delete();
                }
            }
        });

        AuditLog::record(
            event: 'session.deleted',
            auditableType: TrainingSession::class,
            auditableId: $trainingSession->id,
            oldValues: ['session_code' => $code, 'title' => $trainingSession->title],
            actor: $request->user(),
        );
        return redirect()->route('admin-vue.sessions.index')->with('status', 'Training session deleted.');
    }

    public function reconcileSeats(Request $request, TrainingSession $trainingSession, SessionBookingService $service): RedirectResponse
    {
        $this->authorize('update', $trainingSession);
        $service->reconcileSessionCounters($trainingSession->id, 'Manual administrator seat reconciliation.');
        AuditLog::record(
            event: 'session.seats_reconciled',
            auditableType: TrainingSession::class,
            auditableId: $trainingSession->id,
            newValues: [
                'reserved_seats_count' => $trainingSession->fresh()->reserved_seats_count,
                'confirmed_seats_count' => $trainingSession->fresh()->confirmed_seats_count,
            ],
            actor: $request->user(),
        );
        return back()->with('status', 'Seat counters reconciled from active bookings.');
    }

    public function storeMaterial(Request $request, TrainingSession $trainingSession): RedirectResponse
    {
        $this->authorize('update', $trainingSession);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'material_type' => ['required', 'in:'.implode(',', array_column(MaterialType::cases(), 'value'))],
            'status' => ['required', 'in:'.implode(',', array_column(SessionMaterialStatus::cases(), 'value'))],
            'is_downloadable' => ['required', 'boolean'],
            'is_main_document' => ['required', 'boolean'],
            'is_available_independently' => ['nullable', 'boolean'],
            'independent_price' => ['nullable', 'numeric', 'min:0'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,csv,txt,zip,png,jpg,jpeg', 'max:102400'],
        ]);
        $independentEnabled = (bool) ($data['is_available_independently'] ?? $trainingSession->is_available_independently);
        $trainingSession->update([
            'is_available_independently' => $independentEnabled,
            'independent_price' => $independentEnabled ? ($data['independent_price'] ?? $trainingSession->independent_price) : null,
        ]);
        $file = $request->file('file');
        $key = $file->store('sessions/'.$trainingSession->id, 'session-materials');
        $material = SessionMaterial::query()->create([
            'training_session_id' => $trainingSession->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'material_type' => $data['material_type'],
            'storage_disk' => 'session-materials',
            'storage_key' => $key,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType(),
            'file_size_bytes' => $file->getSize(),
            'checksum_sha256' => hash_file('sha256', $file->getRealPath()) ?: null,
            'is_downloadable' => (bool) $data['is_downloadable'],
            'is_main_document' => (bool) $data['is_main_document'],
            'status' => $data['status'],
            'uploaded_by' => $request->user()->id,
            'published_at' => $data['status'] === SessionMaterialStatus::Published->value ? now() : null,
        ]);
        AuditLog::record(event: 'session.material_uploaded', auditableType: SessionMaterial::class, auditableId: $material->id, newValues: ['title' => $material->title], actor: $request->user());
        return back()->with('status', 'Session material uploaded.');
    }

    public function destroyMaterial(Request $request, TrainingSession $trainingSession, SessionMaterial $material): RedirectResponse
    {
        abort_unless($material->training_session_id === $trainingSession->id, 404);
        $this->authorize('update', $trainingSession);
        $key = $material->storage_key;
        $material->delete();
        if ($key) {
            \Illuminate\Support\Facades\Storage::disk('session-materials')->delete($key);
        }
        AuditLog::record(event: 'session.material_deleted', auditableType: SessionMaterial::class, auditableId: $material->id, oldValues: ['title' => $material->title], actor: $request->user());
        return back()->with('status', 'Session material deleted.');
    }

    public function updateMaterial(Request $request, TrainingSession $trainingSession, SessionMaterial $material): RedirectResponse
    {
        abort_unless($material->training_session_id === $trainingSession->id, 404);
        $this->authorize('update', $trainingSession);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'material_type' => ['required', 'in:'.implode(',', array_column(MaterialType::cases(), 'value'))],
            'status' => ['required', 'in:'.implode(',', array_column(SessionMaterialStatus::cases(), 'value'))],
            'is_downloadable' => ['required', 'boolean'],
            'is_main_document' => ['required', 'boolean'],
            'is_available_independently' => ['nullable', 'boolean'],
            'independent_price' => ['nullable', 'numeric', 'min:0'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,csv,txt,zip,png,jpg,jpeg', 'max:102400'],
        ]);
        if ($request->hasFile('file')) {
            $oldKey = $material->storage_key;
            $file = $request->file('file');
            $material->storage_key = $file->store('sessions/'.$trainingSession->id, 'session-materials');
            $material->original_filename = $file->getClientOriginalName();
            $material->mime_type = $file->getClientMimeType();
            $material->file_size_bytes = $file->getSize();
            $material->checksum_sha256 = hash_file('sha256', $file->getRealPath()) ?: null;
            if ($oldKey) {
                \Illuminate\Support\Facades\Storage::disk('session-materials')->delete($oldKey);
            }
        }
        $material->fill([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'material_type' => $data['material_type'],
            'status' => $data['status'],
            'is_downloadable' => (bool) $data['is_downloadable'],
            'is_main_document' => (bool) $data['is_main_document'],
            'published_at' => $data['status'] === SessionMaterialStatus::Published->value ? ($material->published_at ?? now()) : $material->published_at,
        ]);
        $material->save();
        if (array_key_exists('is_available_independently', $data)) {
            $independentEnabled=(bool)$data['is_available_independently'];
            $trainingSession->update([
                'is_available_independently'=>$independentEnabled,
                'independent_price'=>$independentEnabled?($data['independent_price']??$trainingSession->independent_price):null,
            ]);
        }
        AuditLog::record(event: 'session.material_updated', auditableType: SessionMaterial::class, auditableId: $material->id, newValues: ['title' => $material->title, 'status' => $material->status->value], actor: $request->user());
        return back()->with('status', 'Session material updated.');
    }

    private function sessionRules(bool $editing): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['required', 'date', 'before:starts_at'],
            'venue_name' => ['required', 'string', 'max:200'],
            'venue_address' => ['required', 'string', 'max:255'],
            'venue_city' => ['required', 'string', 'max:100'],
            'venue_state' => ['required', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'recording_access_policy' => ['nullable', 'in:'.implode(',', array_column(RecordingAccessPolicy::cases(), 'value'))],
            'is_available_independently' => ['required', 'boolean'],
            'independent_price' => ['nullable', 'numeric', 'min:0'],
            'main_document_material_id' => ['nullable', 'integer', 'exists:session_materials,id'],
            'status' => ['required', 'in:'.implode(',', array_column(SessionStatus::cases(), 'value'))],
            'instructors' => ['nullable', 'array'],
            'instructors.*.instructor_id' => ['required', 'integer', 'exists:users,id'],
            'instructors.*.role_label' => ['required', 'string', 'max:80'],
            'cover_image' => ['nullable', 'image', 'max:5120'],
        ];
    }

    private function createCompatibilityModule(string $title, ?string $description, string $code, int $actorId, ?string $coverPath = null): Module
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'training-sessions'],
            [
                'name' => 'Training Sessions',
                'description' => 'Compatibility catalogue category for physical training sessions.',
                'sort_order' => 0,
                'is_active' => true,
            ],
        );

        return Module::query()->create([
            'category_id' => $category->id,
            'code' => $code,
            'title' => $title,
            'slug' => Str::slug($title).'-'.strtolower(Str::random(6)),
            'summary' => $description ? Str::limit(strip_tags($description), 500) : null,
            'description' => $description ?: $title,
            'learning_objectives' => null,
            'cover_image_disk' => $coverPath ? 'public' : null,
            'cover_image_path' => $coverPath,
            'is_available_independently' => false,
            'independent_price' => null,
            'currency' => 'MYR',
            'status' => 'published',
            'published_at' => now(),
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);
    }

    private function instructorOptions(): array
    {
        return User::query()->whereHas('roles', fn ($query) => $query->where('slug', RoleSlug::Instructor->value))
            ->orderBy('name')->get(['id', 'name'])
            ->map(fn (User $user): array => ['value' => (string) $user->id, 'label' => $user->name])
            ->values()->all();
    }

    private function syncInstructors(TrainingSession $session, array $assignments, int $actorId): void
    {
        $session->sessionInstructors()->delete();
        foreach ($assignments as $assignment) {
            SessionInstructor::query()->create([
                'training_session_id' => $session->id,
                'instructor_id' => $assignment['instructor_id'],
                'role_label' => $assignment['role_label'],
                'assigned_by' => $actorId,
                'assigned_at' => now(),
            ]);
        }
    }
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $sessions = TrainingSession::query()
            ->with(['module:id,code,title', 'instructors:id,name'])
            ->withCount([
                'bookings as confirmed_bookings_count' => fn ($query) => $query->where('status', 'confirmed'),
            ])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('session_code', 'like', "%{$search}%");
            }))
            ->when($status !== '' && SessionStatus::tryFrom($status), fn ($query) => $query->where('status', $status))
            ->orderBy('starts_at')
            ->paginate(15)
            ->withQueryString()
            ->through(static fn (TrainingSession $session): array => [
                'id' => $session->id,
                'title' => $session->title,
                'sessionCode' => $session->session_code,
                'startsAt' => $session->starts_at?->toIso8601String(),
                'venue' => implode(', ', array_filter([$session->venue_name, $session->venue_city])),
                'module' => $session->module ? [
                    'code' => $session->module->code,
                    'title' => $session->module->title,
                ] : null,
                'instructors' => $session->instructors->pluck('name')->values()->all(),
                'confirmedBookings' => (int) $session->confirmed_bookings_count,
                'reservedSeats' => (int) $session->reserved_seats_count,
                'capacity' => (int) $session->capacity,
                'price' => number_format((float) $session->price, 2, '.', ''),
                'status' => $session->status instanceof SessionStatus
                    ? $session->status->label()
                    : (string) $session->status,
                'statusValue' => $session->status instanceof SessionStatus
                    ? $session->status->value
                    : (string) $session->status,
                'editUrl' => route('admin-vue.sessions.edit', $session),
                'detailUrl' => route('admin-vue.sessions.show', $session),
            ]);

        return Inertia::render('Admin/TrainingSessions/Index', [
            'sessions' => $sessions,
            'filters' => [
                'q' => $search,
                'status' => $status,
            ],
            'statuses' => collect(SessionStatus::cases())
                ->map(static fn (SessionStatus $sessionStatus): array => [
                    'value' => $sessionStatus->value,
                    'label' => $sessionStatus->label(),
                ])
                ->values()
                ->all(),
        ]);
    }

    public function publish(TrainingSession $trainingSession): \Illuminate\Http\RedirectResponse
    {
        abort_unless(in_array($trainingSession->status, [SessionStatus::Draft, SessionStatus::RegistrationClosed], true), 422, 'This session cannot be published from its current status.');

        $oldStatus = $trainingSession->status->value;
        $trainingSession->update([
            'status' => SessionStatus::Published->value,
            'published_at' => $trainingSession->published_at ?? now(),
            'updated_by' => Auth::id(),
        ]);
        AuditLog::record(
            event: 'session.published',
            auditableType: TrainingSession::class,
            auditableId: $trainingSession->id,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => SessionStatus::Published->value],
        );

        return back()->with('status', 'Training session published.');
    }

    public function closeRegistration(TrainingSession $trainingSession): \Illuminate\Http\RedirectResponse
    {
        abort_unless(in_array($trainingSession->status, [SessionStatus::Published, SessionStatus::Full], true), 422, 'Registration cannot be closed from the current status.');

        $oldStatus = $trainingSession->status->value;
        $trainingSession->update([
            'status' => SessionStatus::RegistrationClosed->value,
            'updated_by' => Auth::id(),
        ]);
        AuditLog::record(
            event: 'session.registration_closed',
            auditableType: TrainingSession::class,
            auditableId: $trainingSession->id,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => SessionStatus::RegistrationClosed->value],
        );

        return back()->with('status', 'Session registration closed.');
    }

    public function markCompleted(TrainingSession $trainingSession): \Illuminate\Http\RedirectResponse
    {
        abort_unless(in_array($trainingSession->status, [SessionStatus::Published, SessionStatus::Full, SessionStatus::RegistrationClosed, SessionStatus::InProgress], true), 422, 'This session cannot be completed from its current status.');
        abort_unless(filled($trainingSession->completion_submitted_at) && filled($trainingSession->completion_notes), 422, 'The instructor completion report is required before approval.');

        $oldStatus = $trainingSession->status->value;
        $trainingSession->update([
            'status' => SessionStatus::Completed->value,
            'completed_at' => $trainingSession->completed_at ?? now(),
            'completed_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);
        AuditLog::record(
            event: 'session.completed',
            auditableType: TrainingSession::class,
            auditableId: $trainingSession->id,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => SessionStatus::Completed->value],
        );

        return back()->with('status', 'Training session marked completed.');
    }

    public function cancel(Request $request, TrainingSession $trainingSession, SessionBookingService $service): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate(['cancellation_reason' => ['required', 'string', 'max:500']]);

        try {
            $service->cancelSessionByAdmin($trainingSession, $request->user(), $data['cancellation_reason']);
        } catch (DomainException $exception) {
            return back()->withErrors(['session' => $exception->getMessage()]);
        }

        return back()->with('status', 'Training session cancelled and bookings released.');
    }

    public function notifyParticipants(Request $request, TrainingSession $trainingSession, EtmsNotificationService $notifier): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate(['update_message' => ['required', 'string', 'max:800']]);
        $summary = trim($data['update_message']);
        $sent = 0;

        foreach ($trainingSession->bookings()->where('status', 'confirmed')->with('user')->get() as $booking) {
            if ($booking->user) {
                $notifier->notifyUser(
                    user: $booking->user,
                    eventType: 'session.updated',
                    title: "Session Update: {$trainingSession->title}",
                    message: $summary,
                    actionUrl: route('portal.bookings.show', $booking, false),
                    actionLabel: 'View Session Booking',
                    details: ['Session' => "{$trainingSession->session_code} — {$trainingSession->title}"],
                    reference: $trainingSession->session_code,
                );
                $sent++;
            }
        }

        foreach ($trainingSession->instructors()->get() as $instructor) {
            $notifier->notifyUser(
                user: $instructor,
                eventType: 'session.updated',
                title: "Assigned Session Update: {$trainingSession->title}",
                message: $summary,
                actionUrl: route('instructor.sessions.show', $trainingSession, false),
                actionLabel: 'Open Instructor Session',
                details: ['Session' => "{$trainingSession->session_code} — {$trainingSession->title}"],
                reference: $trainingSession->session_code,
            );
            $sent++;
        }

        AuditLog::record(
            event: 'session.update_notified',
            auditableType: TrainingSession::class,
            auditableId: $trainingSession->id,
            oldValues: null,
            newValues: ['recipients_notified' => $sent],
            reason: $summary,
            actor: $request->user(),
        );

        return back()->with('status', "Session update queued for {$sent} recipient(s).");
    }
}
