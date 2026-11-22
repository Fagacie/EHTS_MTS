<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EncodingStatus;
use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Enums\ModuleStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessVideoToHlsJob;
use App\Models\AuditLog;
use App\Models\Module;
use App\Models\ModuleMaterial;
use App\Models\ModuleVideo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminModuleController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Module::class);

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');

        $modules = Module::query()
            ->withCount('materials')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query
                ->where('code', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%")))
            ->when(ModuleStatus::tryFrom($status), fn ($query) => $query->where('status', $status))
            ->latest('created_at')
            ->paginate(15)
            ->withQueryString()
            ->through(static fn (Module $module): array => [
                'id' => $module->id,
                'code' => $module->code,
                'title' => $module->title,
                'slug' => $module->slug,
                'summary' => $module->summary,
                'materials' => (int) $module->materials_count,
                'independent' => (bool) $module->is_available_independently,
                'price' => $module->independent_price !== null ? number_format((float) $module->independent_price, 2) : null,
                'currency' => $module->currency ?: config('etms.currency'),
                'status' => $module->status?->value,
                'statusLabel' => $module->status?->label(),
                'editUrl' => route('admin-vue.modules.edit', $module),
            ]);

        return Inertia::render('Admin/Modules/Index', [
            'modules' => $modules,
            'filters' => ['q' => $search, 'status' => $status],
            'statuses' => collect(ModuleStatus::cases())->map(fn (ModuleStatus $item): array => [
                'value' => $item->value,
                'label' => $item->label(),
            ])->values()->all(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Module::class);

        return Inertia::render('Admin/Modules/Form', [
            'mode' => 'create',
            'statuses' => collect(ModuleStatus::cases())->map(fn (ModuleStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values()->all(),
            'materialTypes' => collect(MaterialType::cases())->map(fn (MaterialType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->values()->all(),
            'materialStatuses' => collect(MaterialStatus::cases())->map(fn (MaterialStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values()->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Module::class);

        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'code' => ['nullable', 'string', 'max:50', 'unique:modules,code'],
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', 'unique:modules,slug'],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['required', 'string'],
            'learning_objectives' => ['nullable', 'array'],
            'learning_objectives.*' => ['nullable', 'string', 'max:500'],
            'is_available_independently' => ['nullable', 'boolean'],
            'independent_price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:'.implode(',', array_column(ModuleStatus::cases(), 'value'))],
            'cover_image' => ['nullable', 'image', 'max:5120'],
        ]);

        $code = ! empty($data['code'])
            ? strtoupper(trim($data['code']))
            : 'MOD-'.now()->format('Y').'-'.strtoupper(Str::random(6));

        $slug = ! empty($data['slug'])
            ? Str::slug($data['slug'])
            : Str::slug($data['title']).'-'.strtolower(Str::random(6));

        $coverPath = null;
        if ($request->hasFile('cover_image')) {
            $coverPath = $request->file('cover_image')->store('modules/covers', 'public');
        }

        $status = ModuleStatus::from($data['status']);
        $objectives = array_values(array_filter(array_map(
            static fn (mixed $item): string => trim((string) $item),
            $data['learning_objectives'] ?? [],
        )));

        $module = Module::query()->create([
            'category_id' => $data['category_id'] ?? null,
            'code' => $code,
            'title' => $data['title'],
            'slug' => $slug,
            'summary' => $data['summary'] ?? null,
            'description' => $data['description'],
            'learning_objectives' => $objectives,
            'cover_image_disk' => $coverPath ? 'public' : null,
            'cover_image_path' => $coverPath,
            'is_available_independently' => (bool) ($data['is_available_independently'] ?? true),
            'independent_price' => $data['independent_price'],
            'currency' => config('etms.currency'),
            'status' => $status,
            'published_at' => $status === ModuleStatus::Published ? now() : null,
            'archived_at' => $status === ModuleStatus::Archived ? now() : null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        AuditLog::record(
            event: 'module.created',
            auditableType: 'modules',
            auditableId: $module->id,
            newValues: ['code' => $module->code, 'title' => $module->title, 'status' => $status->value],
            actor: $request->user(),
        );

        return redirect()->route('admin-vue.modules.edit', $module)->with('success', 'Standalone module created. You can now upload its learning materials.');
    }

    public function edit(Module $module): Response
    {
        $this->authorize('update', $module);
        $module->load(['materials', 'videos']);

        return Inertia::render('Admin/Modules/Form', [
            'mode' => 'edit',
            'module' => [
                'id' => $module->id,
                'code' => $module->code,
                'title' => $module->title,
                'slug' => $module->slug,
                'summary' => $module->summary,
                'description' => $module->description,
                'learning_objectives' => $module->learning_objectives ?? [],
                'is_available_independently' => $module->is_available_independently,
                'independent_price' => $module->independent_price,
                'status' => $module->status->value,
                'cover_image_url' => $module->coverImageUrl(),
                'materials' => $module->materials->map(static fn (ModuleMaterial $material): array => [
                    'id' => $material->id,
                    'title' => $material->title,
                    'description' => $material->description,
                    'material_type' => $material->material_type?->value ?? 'pdf',
                    'typeLabel' => $material->material_type?->label() ?? 'PDF',
                    'status' => $material->status?->value ?? 'published',
                    'statusLabel' => $material->status?->label() ?? 'Published',
                    'original_filename' => $material->original_filename,
                    'fileSizeLabel' => $material->formattedFileSize(),
                    'is_downloadable' => (bool) $material->is_downloadable,
                    'sort_order' => (int) $material->sort_order,
                    'downloadUrl' => $material->temporaryDownloadUrl(15),
                ])->values()->all(),
                'videos' => $module->videos->map(static fn (ModuleVideo $video): array => [
                    'id' => $video->id,
                    'title' => $video->title,
                    'description' => $video->description,
                    'original_filename' => $video->original_filename,
                    'durationLabel' => $video->formattedDuration(),
                    'encodingStatus' => $video->encoding_status?->value ?? 'pending',
                    'encodingLabel' => $video->encoding_status?->label() ?? 'Pending',
                    'status' => $video->status?->value ?? 'published',
                    'statusLabel' => $video->status?->label() ?? 'Published',
                    'sort_order' => (int) $video->sort_order,
                ])->values()->all(),
            ],
            'statuses' => collect(ModuleStatus::cases())->map(fn (ModuleStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values()->all(),
            'materialTypes' => collect(MaterialType::cases())->map(fn (MaterialType $type): array => [
                'value' => $type->value,
                'label' => $type->label(),
            ])->values()->all(),
            'materialStatuses' => collect(MaterialStatus::cases())->map(fn (MaterialStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ])->values()->all(),
        ]);
    }

    public function update(Request $request, Module $module): RedirectResponse
    {
        $this->authorize('update', $module);

        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'code' => ['required', 'string', 'max:50', Rule::unique('modules', 'code')->ignore($module->id)],
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', Rule::unique('modules', 'slug')->ignore($module->id)],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['required', 'string'],
            'learning_objectives' => ['nullable', 'array'],
            'learning_objectives.*' => ['nullable', 'string', 'max:500'],
            'is_available_independently' => ['nullable', 'boolean'],
            'independent_price' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:'.implode(',', array_column(ModuleStatus::cases(), 'value'))],
            'cover_image' => ['nullable', 'image', 'max:5120'],
        ]);

        $oldStatus = $module->status->value;
        $status = ModuleStatus::from($data['status']);
        $oldCover = $module->cover_image_path;

        if ($request->hasFile('cover_image')) {
            $newCover = $request->file('cover_image')->store('modules/covers', 'public');
            $module->cover_image_disk = 'public';
            $module->cover_image_path = $newCover;
            if ($oldCover && $oldCover !== $newCover) {
                Storage::disk('public')->delete($oldCover);
            }
        }

        $objectives = array_values(array_filter(array_map(
            static fn (mixed $item): string => trim((string) $item),
            $data['learning_objectives'] ?? [],
        )));

        $module->fill([
            'code' => strtoupper(trim($data['code'])),
            'title' => $data['title'],
            'slug' => ! empty($data['slug']) ? Str::slug($data['slug']) : $module->slug,
            'summary' => $data['summary'] ?? null,
            'description' => $data['description'],
            'learning_objectives' => $objectives,
            'is_available_independently' => (bool) ($data['is_available_independently'] ?? true),
            'independent_price' => $data['independent_price'],
            'status' => $status,
            'published_at' => $status === ModuleStatus::Published ? ($module->published_at ?? now()) : $module->published_at,
            'archived_at' => $status === ModuleStatus::Archived ? ($module->archived_at ?? now()) : $module->archived_at,
            'updated_by' => $request->user()->id,
        ]);
        $module->save();

        AuditLog::record(
            event: $status === ModuleStatus::Published && $oldStatus !== ModuleStatus::Published->value ? 'module.published' : 'module.updated',
            auditableType: 'modules',
            auditableId: $module->id,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $status->value],
            actor: $request->user(),
        );

        return redirect()->route('admin-vue.modules.edit', $module)->with('success', 'Standalone module updated.');
    }

    public function destroy(Request $request, Module $module): RedirectResponse
    {
        $this->authorize('delete', $module);

        if ($module->entitlements()->exists() || $module->certificates()->exists()) {
            abort(422, 'This module cannot be deleted because customer entitlements or certificates exist.');
        }

        $module->delete();
        AuditLog::record(
            event: 'module.deleted',
            auditableType: 'modules',
            auditableId: $module->id,
            oldValues: ['code' => $module->code, 'title' => $module->title],
            actor: $request->user(),
        );

        return redirect()->route('admin-vue.modules.index')->with('success', 'Standalone module deleted.');
    }

    public function changeStatus(Request $request, Module $module): RedirectResponse
    {
        $this->authorize('update', $module);
        $data = $request->validate(['status' => ['required', 'in:published,unpublished,archived']]);
        $newStatus = ModuleStatus::from($data['status']);
        $oldStatus = $module->status->value;
        $module->update([
            'status' => $newStatus,
            'published_at' => $newStatus === ModuleStatus::Published ? ($module->published_at ?? now()) : $module->published_at,
            'archived_at' => $newStatus === ModuleStatus::Archived ? now() : $module->archived_at,
            'updated_by' => $request->user()->id,
        ]);
        AuditLog::record(
            event: 'module.'.($newStatus === ModuleStatus::Published ? 'published' : ($newStatus === ModuleStatus::Archived ? 'archived' : 'unpublished')),
            auditableType: 'modules',
            auditableId: $module->id,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $newStatus->value],
            actor: $request->user(),
        );

        return back()->with('success', 'Module status updated.');
    }

    public function storeMaterial(Request $request, Module $module): RedirectResponse
    {
        $this->authorize('update', $module);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'material_type' => ['required', 'in:'.implode(',', array_column(MaterialType::cases(), 'value'))],
            'status' => ['required', 'in:'.implode(',', array_column(MaterialStatus::cases(), 'value'))],
            'is_downloadable' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,csv,txt,zip,png,jpg,jpeg', 'max:102400'],
        ]);

        $file = $request->file('file');
        $path = $file->store('modules/'.$module->id, 'materials');
        $nextOrder = isset($data['sort_order']) ? (int) $data['sort_order'] : (((int) $module->materials()->max('sort_order')) + 1);

        $material = ModuleMaterial::query()->create([
            'module_id' => $module->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'material_type' => $data['material_type'],
            'storage_disk' => 'materials',
            'storage_bucket' => 'etms-server-storage',
            'storage_key' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType() ?: 'application/octet-stream',
            'file_size_bytes' => max(1, (int) $file->getSize()),
            'checksum_sha256' => hash_file('sha256', $file->getRealPath()) ?: null,
            'is_downloadable' => (bool) $data['is_downloadable'],
            'sort_order' => $nextOrder,
            'status' => $data['status'],
            'published_at' => $data['status'] === MaterialStatus::Published->value ? now() : null,
            'uploaded_by' => $request->user()->id,
        ]);

        AuditLog::record(
            event: 'module_material.created',
            auditableType: ModuleMaterial::class,
            auditableId: $material->id,
            newValues: $material->only(['module_id', 'title', 'status', 'material_type']),
            actor: $request->user(),
        );

        return back()->with('success', 'Learning material uploaded to module.');
    }

    public function updateMaterial(Request $request, Module $module, ModuleMaterial $material): RedirectResponse
    {
        abort_unless($material->module_id === $module->id, 404);
        $this->authorize('update', $module);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'material_type' => ['required', 'in:'.implode(',', array_column(MaterialType::cases(), 'value'))],
            'status' => ['required', 'in:'.implode(',', array_column(MaterialStatus::cases(), 'value'))],
            'is_downloadable' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,csv,txt,zip,png,jpg,jpeg', 'max:102400'],
        ]);

        if ($request->hasFile('file')) {
            $oldKey = $material->storage_key;
            $file = $request->file('file');
            $material->storage_key = $file->store('modules/'.$module->id, 'materials');
            $material->original_filename = $file->getClientOriginalName();
            $material->mime_type = $file->getClientMimeType() ?: 'application/octet-stream';
            $material->file_size_bytes = max(1, (int) $file->getSize());
            $material->checksum_sha256 = hash_file('sha256', $file->getRealPath()) ?: null;
            if ($oldKey) {
                Storage::disk('materials')->delete($oldKey);
            }
        }

        $material->fill([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'material_type' => $data['material_type'],
            'status' => $data['status'],
            'is_downloadable' => (bool) $data['is_downloadable'],
            'sort_order' => isset($data['sort_order']) ? (int) $data['sort_order'] : $material->sort_order,
            'published_at' => $data['status'] === MaterialStatus::Published->value ? ($material->published_at ?? now()) : $material->published_at,
        ]);
        $material->save();

        AuditLog::record(
            event: 'module_material.updated',
            auditableType: ModuleMaterial::class,
            auditableId: $material->id,
            newValues: $material->only(['title', 'status', 'material_type']),
            actor: $request->user(),
        );

        return back()->with('success', 'Module learning material updated.');
    }

    public function destroyMaterial(Request $request, Module $module, ModuleMaterial $material): RedirectResponse
    {
        abort_unless($material->module_id === $module->id, 404);
        $this->authorize('update', $module);

        $key = $material->storage_key;
        $material->delete();
        if ($key) {
            Storage::disk('materials')->delete($key);
        }

        AuditLog::record(
            event: 'module_material.deleted',
            auditableType: ModuleMaterial::class,
            auditableId: $material->id,
            oldValues: ['title' => $material->title],
            actor: $request->user(),
        );

        return back()->with('success', 'Module learning material deleted.');
    }

    public function storeVideo(Request $request, Module $module): RedirectResponse
    {
        $this->authorize('update', $module);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', 'in:draft,published,archived'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'file' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-matroska,video/webm,application/octet-stream', 'max:2097152'],
        ]);

        $file = $request->file('file');
        $sourceKey = $file->store('sources/module-'.$module->id, 'videos');
        $nextOrder = isset($data['sort_order']) ? (int) $data['sort_order'] : (((int) $module->videos()->max('sort_order')) + 1);

        $video = ModuleVideo::query()->create([
            'module_id' => $module->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'provider' => 'local_hls',
            'provider_library_id' => 'local',
            'provider_video_id' => 'mod-vid-'.strtolower(Str::random(10)),
            'storage_disk' => 'videos',
            'source_storage_key' => $sourceKey,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getClientMimeType() ?: 'video/mp4',
            'file_size_bytes' => max(1, (int) $file->getSize()),
            'encoding_status' => EncodingStatus::Pending,
            'sort_order' => $nextOrder,
            'status' => $data['status'],
            'published_at' => $data['status'] === MaterialStatus::Published->value ? now() : null,
            'uploaded_by' => $request->user()->id,
        ]);

        ProcessVideoToHlsJob::dispatch(ModuleVideo::class, $video->id);

        AuditLog::record(
            event: 'module_video.created',
            auditableType: ModuleVideo::class,
            auditableId: $video->id,
            newValues: ['module_id' => $module->id, 'title' => $video->title, 'status' => $video->status?->value],
            actor: $request->user(),
        );

        return back()->with('success', 'Video lesson uploaded to standalone module.');
    }

    public function destroyVideo(Request $request, Module $module, ModuleVideo $video): RedirectResponse
    {
        abort_unless($video->module_id === $module->id, 404);
        $this->authorize('update', $module);

        $sourceKey = $video->source_storage_key;
        $manifestKey = $video->hls_manifest_key;
        $title = $video->title;
        $video->delete();

        if ($sourceKey) {
            Storage::disk('videos')->delete($sourceKey);
        }
        if ($manifestKey) {
            Storage::disk('videos')->delete($manifestKey);
        }

        AuditLog::record(
            event: 'module_video.deleted',
            auditableType: ModuleVideo::class,
            auditableId: $video->id,
            oldValues: ['title' => $title],
            actor: $request->user(),
        );

        return back()->with('success', 'Module video lesson deleted.');
    }
}
