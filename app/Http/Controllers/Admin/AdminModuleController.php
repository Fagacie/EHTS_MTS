<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ModuleStatus;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Module;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminModuleController extends Controller
{
    public function create(): Response
    {
        $this->authorize('create', Module::class);
        return Inertia::render('Admin/Modules/Form', [
            'mode' => 'create',
            'categories' => Category::query()->active()->ordered()->get(['id', 'name'])->map(fn (Category $category): array => [
                'value' => (string) $category->id,
                'label' => $category->name,
            ])->values()->all(),
            'statuses' => collect(ModuleStatus::cases())->map(fn (ModuleStatus $status): array => ['value' => $status->value, 'label' => $status->label()])->values()->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Module::class);
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'code' => ['required', 'string', 'max:50', 'unique:modules,code'],
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:220', 'unique:modules,slug'],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['required', 'string'],
            'learning_objectives' => ['nullable', 'array'],
            'learning_objectives.*' => ['string', 'max:500'],
            'is_available_independently' => ['required', 'boolean'],
            'independent_price' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:'.implode(',', array_column(ModuleStatus::cases(), 'value'))],
            'cover_image' => ['nullable', 'image', 'max:5120'],
        ]);

        $coverPath = null;
        if ($request->hasFile('cover_image')) {
            $coverPath = $request->file('cover_image')->store('modules/covers', 'public');
        }
        $status = ModuleStatus::from($data['status']);
        $module = Module::query()->create([
            'category_id' => $data['category_id'],
            'code' => $data['code'],
            'title' => $data['title'],
            'slug' => $data['slug'],
            'summary' => $data['summary'] ?? null,
            'description' => $data['description'],
            'learning_objectives' => $data['learning_objectives'] ?? [],
            'cover_image_disk' => $coverPath ? 'public' : null,
            'cover_image_path' => $coverPath,
            'is_available_independently' => (bool) $data['is_available_independently'],
            'independent_price' => $data['is_available_independently'] ? ($data['independent_price'] ?? null) : null,
            'currency' => 'MYR',
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
        return redirect()->route('admin-vue.modules.index')->with('success', 'Module created.');
    }

    public function edit(Module $module): Response
    {
        $this->authorize('update', $module);
        return Inertia::render('Admin/Modules/Form', [
            'mode' => 'edit',
            'module' => [
                'id' => $module->id,
                'category_id' => (string) $module->category_id,
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
            ],
            'categories' => Category::query()->active()->ordered()->get(['id', 'name'])->map(fn (Category $category): array => [
                'value' => (string) $category->id,
                'label' => $category->name,
            ])->values()->all(),
            'statuses' => collect(ModuleStatus::cases())->map(fn (ModuleStatus $status): array => ['value' => $status->value, 'label' => $status->label()])->values()->all(),
        ]);
    }

    public function update(Request $request, Module $module): RedirectResponse
    {
        $this->authorize('update', $module);
        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'code' => ['required', 'string', 'max:50', Rule::unique('modules', 'code')->ignore($module->id)],
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:220', Rule::unique('modules', 'slug')->ignore($module->id)],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['required', 'string'],
            'learning_objectives' => ['nullable', 'array'],
            'learning_objectives.*' => ['string', 'max:500'],
            'is_available_independently' => ['required', 'boolean'],
            'independent_price' => ['nullable', 'numeric', 'min:0'],
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
            if ($oldCover && $module->cover_image_disk !== 'public') {
                Storage::disk('public')->delete($oldCover);
            }
        }
        $module->fill([
            'category_id' => $data['category_id'],
            'code' => $data['code'],
            'title' => $data['title'],
            'slug' => $data['slug'],
            'summary' => $data['summary'] ?? null,
            'description' => $data['description'],
            'learning_objectives' => $data['learning_objectives'] ?? [],
            'is_available_independently' => (bool) $data['is_available_independently'],
            'independent_price' => $data['is_available_independently'] ? ($data['independent_price'] ?? null) : null,
            'status' => $status,
            'published_at' => $status === ModuleStatus::Published ? ($module->published_at ?? now()) : $module->published_at,
            'archived_at' => $status === ModuleStatus::Archived ? ($module->archived_at ?? now()) : $module->archived_at,
            'updated_by' => $request->user()->id,
        ]);
        $module->save();
        if ($oldCover && $request->hasFile('cover_image') && $oldCover !== $module->cover_image_path) {
            Storage::disk('public')->delete($oldCover);
        }
        AuditLog::record(
            event: $status === ModuleStatus::Published && $oldStatus !== ModuleStatus::Published->value ? 'module.published' : 'module.updated',
            auditableType: 'modules',
            auditableId: $module->id,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $status->value],
            actor: $request->user(),
        );
        return redirect()->route('admin-vue.modules.index')->with('success', 'Module updated.');
    }

    public function destroy(Request $request, Module $module): RedirectResponse
    {
        $this->authorize('delete', $module);
        $module->delete();
        AuditLog::record(
            event: 'module.deleted',
            auditableType: 'modules',
            auditableId: $module->id,
            oldValues: ['code' => $module->code, 'title' => $module->title],
            actor: $request->user(),
        );
        return redirect()->route('admin-vue.modules.index')->with('success', 'Module deleted.');
    }
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $modules = Module::query()->with('category:id,name')->withCount('materials')
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query->where('code', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%")))
            ->when(ModuleStatus::tryFrom($status), fn ($query) => $query->where('status', $status))
            ->latest('created_at')->paginate(15)->withQueryString()
            ->through(static fn (Module $module): array => [
                'id' => $module->id, 'code' => $module->code, 'title' => $module->title,
                'category' => $module->category?->name, 'materials' => $module->materials_count,
                'independent' => $module->is_available_independently,
                'price' => $module->independent_price !== null ? number_format((float) $module->independent_price, 2) : null,
                'status' => $module->status?->value, 'statusLabel' => $module->status?->label(),
                'editUrl' => route('admin-vue.modules.edit', $module),
            ]);

        return Inertia::render('Admin/Modules/Index', [
            'modules' => $modules,
            'filters' => ['q' => $search, 'status' => $status],
            'statuses' => collect(ModuleStatus::cases())->map(fn (ModuleStatus $item): array => ['value' => $item->value, 'label' => $item->label()])->values()->all(),
        ]);
    }

    public function changeStatus(Request $request, Module $module): RedirectResponse
    {
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
}
