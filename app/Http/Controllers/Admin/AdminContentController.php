<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AnnouncementStatus;
use App\Enums\EncodingStatus;
use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Enums\RoleSlug;
use App\Enums\SessionMaterialStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessVideoToHlsJob;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\InstructorProfile;
use App\Models\Module;
use App\Models\ModuleMaterial;
use App\Models\ModuleVideo;
use App\Models\SessionMaterial;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Notification\EtmsNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserStatus;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminContentController extends Controller
{
    public function categories(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $active = (string) $request->query('active', '');
        $categories = Category::query()->withCount('modules')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"))
            ->when($active === '1', fn ($q) => $q->where('is_active', true))
            ->when($active === '0', fn ($q) => $q->where('is_active', false))
            ->orderBy('sort_order')->orderBy('name')
            ->paginate(15)->withQueryString()
            ->through(fn (Category $category): array => [
                'id' => $category->id, 'name' => $category->name, 'slug' => $category->slug,
                'description' => $category->description, 'sortOrder' => $category->sort_order,
                'isActive' => $category->is_active, 'modules' => $category->modules_count,
            ]);

        return Inertia::render('Admin/Content/Categories/Index', [
            'categories' => $categories, 'filters' => ['q' => $search, 'active' => $active],
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash', 'unique:categories,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);
        $data['slug'] = $data['slug'] ?: Str::slug($data['name']);
        $category = Category::create($data);
        AuditLog::record(event: 'category.created', auditableType: Category::class, auditableId: $category->id, newValues: $category->only(['name', 'slug', 'is_active', 'sort_order']), actor: $request->user());
        return back()->with('success', 'Category created.');
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', 'unique:categories,slug,'.$category->id],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);
        $old = $category->only(['name', 'slug', 'description', 'sort_order', 'is_active']);
        $category->update($data);
        AuditLog::record(event: 'category.updated', auditableType: Category::class, auditableId: $category->id, oldValues: $old, newValues: $data, actor: $request->user());
        return back()->with('success', 'Category updated.');
    }

    public function toggleCategory(Request $request, Category $category): RedirectResponse
    {
        $old = $category->is_active;
        $category->update(['is_active' => ! $old]);
        AuditLog::record(event: $category->is_active ? 'category.published' : 'category.archived', auditableType: Category::class, auditableId: $category->id, oldValues: ['is_active' => $old], newValues: ['is_active' => $category->is_active], actor: $request->user());
        return back()->with('success', $category->is_active ? 'Category published.' : 'Category archived.');
    }

    public function destroyCategory(Category $category): RedirectResponse
    {
        abort_if($category->modules()->exists(), 422, 'Categories with modules cannot be deleted.');
        $category->delete();
        return back()->with('success', 'Category deleted.');
    }

    public function moduleMaterials(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $moduleId = (string) $request->query('module_id', '');
        $status = (string) $request->query('status', '');
        $type = (string) $request->query('type', '');
        $materials = ModuleMaterial::query()->with('module:id,code,title')->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhere('original_filename', 'like', "%{$search}%"))
            ->when(Module::query()->whereKey((int) $moduleId)->exists(), fn ($q) => $q->where('module_id', $moduleId))
            ->when(MaterialStatus::tryFrom($status), fn ($q) => $q->where('status', $status))
            ->when(MaterialType::tryFrom($type), fn ($q) => $q->where('material_type', $type))
            ->latest()->paginate(15)->withQueryString()
            ->through(fn (ModuleMaterial $material): array => [
                'id' => $material->id, 'moduleId' => $material->module_id,
                'module' => $material->module?->code.' — '.$material->module?->title,
                'title' => $material->title, 'description' => $material->description,
                'materialType' => $material->material_type?->value, 'status' => $material->status?->value,
                'statusLabel' => $material->status?->label(), 'filename' => $material->original_filename,
                'size' => $material->formattedFileSize(), 'downloadable' => $material->is_downloadable,
                'sortOrder' => $material->sort_order,
            ]);

        return Inertia::render('Admin/Content/ModuleMaterials/Index', [
            'materials' => $materials, 'filters' => ['q' => $search, 'module_id' => $moduleId, 'status' => $status, 'type' => $type],
            'modules' => Module::query()->orderBy('code')->get(['id', 'code', 'title'])->map(fn (Module $m): array => ['id' => $m->id, 'label' => $m->code.' — '.$m->title]),
            'types' => collect(MaterialType::cases())->map(fn (MaterialType $s): array => ['value' => $s->value, 'label' => $s->label()])->values(),
            'statuses' => collect(MaterialStatus::cases())->map(fn (MaterialStatus $s): array => ['value' => $s->value, 'label' => $s->label()])->values(),
        ]);
    }

    public function storeModuleMaterial(Request $request): RedirectResponse
    {
        $data = $this->validateModuleMaterial($request, true);
        $file = $request->file('file');
        $path = $file->store('modules', 'materials');
        $data['storage_disk'] = 'materials'; $data['storage_bucket'] = 'etms-server-storage';
        $data['storage_key'] = $path; $data['original_filename'] = $file->getClientOriginalName();
        $data['mime_type'] = $file->getMimeType() ?: 'application/octet-stream'; $data['file_size_bytes'] = $file->getSize() ?: 1;
        $data['checksum_sha256'] = hash('sha256', $file->get());
        $data['uploaded_by'] = $request->user()->id;
        $material = ModuleMaterial::create(ModuleMaterial::enrichStorageMetadata($data, $request->user()->id));
        AuditLog::record(event: 'module_material.created', auditableType: ModuleMaterial::class, auditableId: $material->id, newValues: $material->only(['module_id', 'title', 'status', 'material_type']), actor: $request->user());
        return back()->with('success', 'Module material uploaded.');
    }

    public function updateModuleMaterial(Request $request, ModuleMaterial $material): RedirectResponse
    {
        $data = $this->validateModuleMaterial($request, false);
        $old = $material->only(['module_id', 'title', 'description', 'material_type', 'status', 'is_downloadable', 'sort_order']);
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('modules', 'materials');
            $data['storage_key'] = $path; $data['original_filename'] = $file->getClientOriginalName();
            $data['mime_type'] = $file->getMimeType() ?: 'application/octet-stream'; $data['file_size_bytes'] = $file->getSize() ?: 1;
            $data['checksum_sha256'] = hash('sha256', $file->get());
        }
        $material->update(ModuleMaterial::enrichStorageMetadata($data, $request->user()->id));
        AuditLog::record(event: 'module_material.updated', auditableType: ModuleMaterial::class, auditableId: $material->id, oldValues: $old, newValues: $data, actor: $request->user());
        return back()->with('success', 'Module material updated.');
    }

    private function validateModuleMaterial(Request $request, bool $fileRequired): array
    {
        $rules = [
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'title' => ['required', 'string', 'max:200'],
            'material_type' => ['required', 'in:'.implode(',', array_column(MaterialType::cases(), 'value'))],
            'status' => ['required', 'in:'.implode(',', array_column(MaterialStatus::cases(), 'value'))],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_downloadable' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
            'file' => [($fileRequired ? 'required' : 'nullable'), 'file', 'max:104857600'],
        ];
        $data = $request->validate($rules);
        unset($data['file']);
        return $data;
    }

    public function destroyModuleMaterial(ModuleMaterial $material): RedirectResponse
    {
        $material->delete();
        return back()->with('success', 'Module material deleted.');
    }

    public function sessionMaterials(Request $request): Response
    {
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('q', ''));
        $materials = SessionMaterial::query()->with(['trainingSession:id,session_code,title', 'uploader:id,name'])
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhereHas('trainingSession', fn ($s) => $s->where('session_code', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%")))
            ->when(SessionMaterialStatus::tryFrom($status), fn ($q) => $q->where('status', $status))
            ->latest()->paginate(15)->withQueryString()
            ->through(fn (SessionMaterial $material): array => [
                'id' => $material->id, 'session' => $material->trainingSession?->session_code,
                'sessionTitle' => $material->trainingSession?->title, 'title' => $material->title,
                'uploader' => $material->uploader?->name, 'status' => $material->status?->value,
                'statusLabel' => ucfirst(str_replace('_', ' ', $material->status?->value ?? '')),
                'filename' => $material->original_filename, 'description' => $material->description,
            ]);
        return Inertia::render('Admin/Content/SessionMaterials/Index', [
            'materials' => $materials, 'filters' => ['q' => $search, 'status' => $status],
            'statuses' => collect(SessionMaterialStatus::cases())->map(fn (SessionMaterialStatus $s): array => ['value' => $s->value, 'label' => ucfirst(str_replace('_', ' ', $s->value))])->values(),
        ]);
    }

    public function reviewSessionMaterial(Request $request, SessionMaterial $material): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:published,rejected,archived'],
            'review_notes' => ['nullable', 'string', 'max:500'],
        ]);
        $old = $material->status->value;
        $material->update([
            'status' => $data['status'], 'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(), 'review_notes' => $data['review_notes'] ?? null,
            'published_at' => $data['status'] === 'published' ? now() : $material->published_at,
        ]);
        AuditLog::record(event: 'session_material.reviewed', auditableType: SessionMaterial::class, auditableId: $material->id, oldValues: ['status' => $old], newValues: ['status' => $data['status']], reason: $data['review_notes'] ?? null, actor: $request->user());
        return back()->with('success', 'Session material review updated.');
    }

    public function moduleVideos(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $videos = ModuleVideo::query()->with('module:id,code,title')
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%")->orWhereHas('module', fn ($m) => $m->where('code', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%")))
            ->when(MaterialStatus::tryFrom($status), fn ($q) => $q->where('status', $status))
            ->orderBy('sort_order')->paginate(15)->withQueryString()
            ->through(fn (ModuleVideo $video): array => [
                'id' => $video->id, 'moduleId' => $video->module_id,
                'module' => $video->module?->code.' — '.$video->module?->title, 'title' => $video->title,
                'description' => $video->description, 'sortOrder' => $video->sort_order,
                'encodingStatus' => $video->encoding_status?->value, 'encodingLabel' => $video->encoding_status?->label(),
                'status' => $video->status?->value, 'statusLabel' => $video->status?->label(),
                'hasSource' => filled($video->source_storage_key), 'duration' => $video->formattedDuration(),
            ]);
        return Inertia::render('Admin/Content/ModuleVideos/Index', [
            'videos' => $videos, 'filters' => ['q' => $search, 'status' => $status],
            'modules' => Module::query()->orderBy('code')->get(['id', 'code', 'title'])->map(fn (Module $m): array => ['id' => $m->id, 'label' => $m->code.' — '.$m->title]),
            'statuses' => collect(MaterialStatus::cases())->map(fn (MaterialStatus $s): array => ['value' => $s->value, 'label' => $s->label()])->values(),
        ]);
    }

    public function storeModuleVideo(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,published,archived'],
            'encoding_status' => ['required', 'in:pending,processing,ready,failed'],
            'file' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-matroska,video/webm,application/octet-stream', 'max:2097152'],
        ]);
        $file = $request->file('file');
        $data['provider'] = 'local_hls'; $data['provider_library_id'] = 'local';
        $data['provider_video_id'] = 'pending'; $data['storage_disk'] = 'videos';
        $data['uploaded_by'] = $request->user()->id;
        if ($file) {
            $data['source_storage_key'] = $file->store('sources', 'videos');
            $data['original_filename'] = $file->getClientOriginalName();
            $data['mime_type'] = $file->getMimeType();
            $data['file_size_bytes'] = $file->getSize();
        }
        unset($data['file']);
        $video = ModuleVideo::create($data);
        if (! empty($video->source_storage_key)) {
            ProcessVideoToHlsJob::dispatch(ModuleVideo::class, $video->id);
        }
        AuditLog::record(event: 'module_video.created', auditableType: ModuleVideo::class, auditableId: $video->id, newValues: $video->only(['module_id', 'title', 'status']), actor: $request->user());
        return back()->with('success', 'Module video created.');
    }

    public function updateModuleVideo(Request $request, ModuleVideo $video): RedirectResponse
    {
        $data = $request->validate([
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:draft,published,archived'],
            'encoding_status' => ['required', 'in:pending,processing,ready,failed'],
            'file' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-matroska,video/webm,application/octet-stream', 'max:2097152'],
        ]);
        unset($data['file']);
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $data['source_storage_key'] = $file->store('sources', 'videos');
            $data['original_filename'] = $file->getClientOriginalName();
            $data['mime_type'] = $file->getMimeType(); $data['file_size_bytes'] = $file->getSize();
        }
        $video->update($data);
        AuditLog::record(event: 'module_video.updated', auditableType: ModuleVideo::class, auditableId: $video->id, newValues: $data, actor: $request->user());
        return back()->with('success', 'Module video updated.');
    }

    public function transcodeModuleVideo(ModuleVideo $video): RedirectResponse
    {
        abort_unless(filled($video->source_storage_key), 422, 'This video has no source file.');
        ProcessVideoToHlsJob::dispatch(ModuleVideo::class, $video->id);
        return back()->with('success', 'FFmpeg HLS transcoding job queued.');
    }

    public function destroyModuleVideo(ModuleVideo $video): RedirectResponse
    {
        $video->delete();
        return back()->with('success', 'Module video deleted.');
    }

    public function announcements(Request $request): Response
    {
        $status = (string) $request->query('status', '');
        $search = trim((string) $request->query('q', ''));
        $announcements = Announcement::query()->withCount('recipients')
            ->when($search !== '', fn ($q) => $q->where('title', 'like', "%{$search}%"))
            ->when(AnnouncementStatus::tryFrom($status), fn ($q) => $q->where('status', $status))
            ->latest()->paginate(15)->withQueryString()
            ->through(fn (Announcement $a): array => [
                'id' => $a->id, 'title' => $a->title, 'body' => $a->body,
                'audienceType' => $a->audience_type, 'audienceLabel' => $a->audienceLabel(),
                'trainingSessionId' => $a->training_session_id, 'moduleId' => $a->module_id,
                'targetRole' => $a->target_role, 'sendEmail' => $a->send_email,
                'sendInApp' => $a->send_in_app, 'status' => $a->status?->value,
                'statusLabel' => $a->status?->label(), 'recipients' => $a->recipients_count,
                'sentAt' => $a->sent_at?->toIso8601String(),
            ]);
        return Inertia::render('Admin/Content/Announcements/Index', [
            'announcements' => $announcements, 'filters' => ['q' => $search, 'status' => $status],
            'statuses' => collect(AnnouncementStatus::cases())->map(fn (AnnouncementStatus $s): array => ['value' => $s->value, 'label' => $s->label()])->values(),
            'roles' => collect(RoleSlug::cases())->map(fn (RoleSlug $r): array => ['value' => $r->value, 'label' => $r->label()])->values(),
            'sessions' => TrainingSession::query()->orderByDesc('starts_at')->get(['id', 'session_code', 'title'])->map(fn (TrainingSession $s): array => ['id' => $s->id, 'label' => $s->session_code.' — '.$s->title]),
            'modules' => Module::query()->orderBy('title')->get(['id', 'code', 'title'])->map(fn (Module $m): array => ['id' => $m->id, 'label' => $m->code.' — '.$m->title]),
        ]);
    }

    public function storeAnnouncement(Request $request): RedirectResponse
    {
        $data = $this->validateAnnouncement($request);
        $data['created_by'] = $request->user()->id;
        Announcement::create($data);
        return back()->with('success', 'Announcement draft created.');
    }

    public function updateAnnouncement(Request $request, Announcement $announcement): RedirectResponse
    {
        abort_if($announcement->status === AnnouncementStatus::Sent, 422, 'Sent announcements cannot be edited.');
        $announcement->update($this->validateAnnouncement($request));
        return back()->with('success', 'Announcement updated.');
    }

    private function validateAnnouncement(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:10000'],
            'audience_type' => ['required', 'in:all_customers,session_participants,module_entitled_users,role,selected_users'],
            'training_session_id' => ['nullable', 'required_if:audience_type,session_participants', 'integer', 'exists:training_sessions,id'],
            'module_id' => ['nullable', 'required_if:audience_type,module_entitled_users', 'integer', 'exists:modules,id'],
            'target_role' => ['nullable', 'required_if:audience_type,role', 'in:administrator,instructor,customer'],
            'send_email' => ['required', 'boolean'],
            'send_in_app' => ['required', 'boolean'],
        ]);
        return $data;
    }

    public function sendAnnouncement(Request $request, Announcement $announcement, EtmsNotificationService $notifier): RedirectResponse
    {
        abort_unless(in_array($announcement->status, [AnnouncementStatus::Draft, AnnouncementStatus::Scheduled, AnnouncementStatus::Failed], true), 422, 'Only unsent announcements can be dispatched.');
        $count = $notifier->dispatchAnnouncement($announcement, $request->user());
        return back()->with('success', "Announcement dispatched to {$count} recipient(s).");
    }

    public function destroyAnnouncement(Announcement $announcement): RedirectResponse
    {
        abort_unless($announcement->status === AnnouncementStatus::Draft, 422, 'Only draft announcements can be deleted.');
        $announcement->delete();
        return back()->with('success', 'Announcement deleted.');
    }

    public function instructorProfiles(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $public = (string) $request->query('public', '');
        $profiles = InstructorProfile::query()->with('user:id,name,email')
            ->when($search !== '', fn ($q) => $q->where('display_name', 'like', "%{$search}%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->when($public === '1', fn ($q) => $q->where('is_public', true))
            ->when($public === '0', fn ($q) => $q->where('is_public', false))
            ->orderBy('display_name')->paginate(15)->withQueryString()
            ->through(fn (InstructorProfile $profile): array => [
                'id' => $profile->id, 'userId' => $profile->user_id,
                'name' => $profile->display_name ?: $profile->user?->name,
                'email' => $profile->user?->email, 'professionalTitle' => $profile->professional_title,
                'qualifications' => $profile->qualifications, 'shortBio' => $profile->short_bio,
                'biography' => $profile->biography, 'specialisations' => $profile->specialisations ?? [],
                'isPublic' => $profile->is_public, 'photoUrl' => $profile->photoUrl(),
            ]);
        $instructors = User::query()->whereHas('roles', fn ($q) => $q->where('slug', RoleSlug::Instructor->value))
            ->orderBy('name')->get(['id', 'name', 'email'])
            ->map(fn (User $u): array => ['id' => $u->id, 'label' => $u->name.' ('.$u->email.')']);
        return Inertia::render('Admin/Content/InstructorProfiles/Index', [
            'profiles' => $profiles, 'filters' => ['q' => $search, 'public' => $public], 'instructors' => $instructors,
        ]);
    }

    public function storeInstructorProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'professional_title' => ['nullable', 'string', 'max:150'],
            'qualifications' => ['nullable', 'string', 'max:255'],
            'short_bio' => ['nullable', 'string', 'max:500'],
            'biography' => ['nullable', 'string'],
            'specialisations' => ['nullable', 'array', 'max:30'],
            'specialisations.*' => ['string', 'max:100'],
            'is_public' => ['required', 'boolean'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
            'status' => UserStatus::Active->value,
        ]);
        $user->assignRole(RoleSlug::Instructor, $request->user()->id);

        $profileData = [
            'display_name' => $data['name'],
            'professional_title' => $data['professional_title'] ?? null,
            'qualifications' => $data['qualifications'] ?? null,
            'short_bio' => $data['short_bio'] ?? null,
            'biography' => $data['biography'] ?? null,
            'specialisations' => $data['specialisations'] ?? [],
            'is_public' => (bool) $data['is_public'],
            'updated_by' => $request->user()->id,
            'published_at' => $data['is_public'] ? now() : null,
        ];
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $profileData['photo_storage_disk'] = 'public';
            $profileData['photo_storage_key'] = $photo->store('instructors/photos', 'public');
        }
        $profile = InstructorProfile::query()->create([
            'user_id' => $user->id,
            ...$profileData,
        ]);
        AuditLog::record(event: 'instructor_profile.created', auditableType: InstructorProfile::class, auditableId: $profile->id, newValues: $profile->only(['user_id', 'display_name', 'is_public']), actor: $request->user());
        return back()->with('success', 'Instructor profile created.');
    }

    public function updateInstructorProfile(Request $request, InstructorProfile $profile): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$profile->user_id],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'professional_title' => ['nullable', 'string', 'max:150'],
            'qualifications' => ['nullable', 'string', 'max:255'],
            'short_bio' => ['nullable', 'string', 'max:500'],
            'biography' => ['nullable', 'string'],
            'specialisations' => ['nullable', 'array', 'max:30'],
            'specialisations.*' => ['string', 'max:100'],
            'is_public' => ['required', 'boolean'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);
        $old = $profile->only(['display_name', 'professional_title', 'qualifications', 'is_public']);
        $profile->user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            ...($data['password'] ? ['password' => Hash::make($data['password'])] : []),
        ]);
        $profileData = [
            'display_name' => $data['name'],
            'professional_title' => $data['professional_title'] ?? null,
            'qualifications' => $data['qualifications'] ?? null,
            'short_bio' => $data['short_bio'] ?? null,
            'biography' => $data['biography'] ?? null,
            'specialisations' => $data['specialisations'] ?? [],
            'is_public' => (bool) $data['is_public'],
            'updated_by' => $request->user()->id,
            'published_at' => $data['is_public'] ? ($profile->published_at ?? now()) : $profile->published_at,
        ];
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $profileData['photo_storage_disk'] = 'public';
            $profileData['photo_storage_key'] = $photo->store('instructors/photos', 'public');
        }
        $profile->update($profileData);
        AuditLog::record(event: 'instructor_profile.updated', auditableType: InstructorProfile::class, auditableId: $profile->id, oldValues: $old, newValues: $data, actor: $request->user());
        return back()->with('success', 'Instructor profile updated.');
    }

    private function validateInstructorProfile(Request $request, bool $requiredUser): array
    {
        $data = $request->validate([
            'user_id' => [($requiredUser ? 'required' : 'nullable'), 'integer', 'exists:users,id', 'unique:instructor_profiles,user_id'.($requiredUser ? '' : ','.$request->route('profile')->id)],
            'display_name' => ['required', 'string', 'max:150'],
            'professional_title' => ['nullable', 'string', 'max:150'],
            'qualifications' => ['nullable', 'string', 'max:255'],
            'short_bio' => ['nullable', 'string', 'max:500'],
            'biography' => ['nullable', 'string'],
            'specialisations' => ['nullable', 'array', 'max:30'],
            'specialisations.*' => ['string', 'max:100'],
            'is_public' => ['required', 'boolean'],
            'photo' => ['nullable', 'image', 'max:5120'],
        ]);
        unset($data['photo']);
        return $data;
    }

    private function createOrUpdateInstructorProfile(array $data, Request $request, ?InstructorProfile $profile = null): InstructorProfile
    {
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $data['photo_storage_disk'] = 'public';
            $data['photo_storage_key'] = $photo->store('instructors/photos', 'public');
        }
        $data['updated_by'] = $request->user()->id;
        if (! $profile) {
            $profile = InstructorProfile::create($data);
        } else {
            $profile->update($data);
        }
        if ($profile->is_public) {
            $profile->update(['published_at' => $profile->published_at ?? now()]);
        }
        return $profile;
    }

    public function toggleInstructorProfile(Request $request, InstructorProfile $profile): RedirectResponse
    {
        $old = $profile->is_public;
        $profile->update(['is_public' => ! $old, 'published_at' => ! $old ? now() : $profile->published_at, 'updated_by' => $request->user()->id]);
        AuditLog::record(event: $profile->is_public ? 'instructor_profile.published' : 'instructor_profile.hidden', auditableType: InstructorProfile::class, auditableId: $profile->id, oldValues: ['is_public' => $old], newValues: ['is_public' => $profile->is_public], actor: $request->user());
        return back()->with('success', $profile->is_public ? 'Instructor profile published.' : 'Instructor profile hidden.');
    }

    public function destroyInstructorProfile(InstructorProfile $profile): RedirectResponse
    {
        $profile->delete();
        return back()->with('success', 'Instructor profile deleted.');
    }
}
