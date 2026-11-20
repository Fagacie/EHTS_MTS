<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\InstructorProfile;
use App\Models\Module;
use App\Models\ModuleMaterial;
use App\Models\ModuleVideo;
use App\Models\SystemSetting;
use App\Models\TrainingSession;
use App\Support\PublicCataloguePresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicCatalogueController extends Controller
{
    public function home(): Response
    {
        $displayTimezone = (string) config('etms.display_timezone', 'Asia/Kuala_Lumpur');

        $categories = Category::query()
            ->active()
            ->ordered()
            ->withCount('publishedModules')
            ->get();

        $featuredModules = Module::query()
            ->published()
            ->with('category')
            ->withCount(['publishedMaterials', 'publishedVideos', 'upcomingSessions'])
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        $upcomingSessions = TrainingSession::query()
            ->upcomingPublic()
            ->with(['module.category', 'instructors.instructorProfile'])
            ->limit(8)
            ->get();

        $publicInstructors = InstructorProfile::query()
            ->public()
            ->with('user')
            ->orderBy('display_name')
            ->limit(4)
            ->get();

        return Inertia::render('Public/Home', [
            'categories' => $categories->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'publishedModulesCount' => (int) $category->published_modules_count,
                'catalogueUrl' => route('catalogue.index', ['category' => $category->slug]),
            ])->values()->all(),
            'upcomingSessions' => $upcomingSessions->map(
                fn (TrainingSession $session): array => PublicCataloguePresenter::sessionListItem($session, $displayTimezone),
            )->values()->all(),
            'featuredModules' => $featuredModules->map(
                fn (Module $module): array => PublicCataloguePresenter::moduleCardItem($module),
            )->values()->all(),
            'instructors' => $publicInstructors->map(
                fn (InstructorProfile $profile): array => PublicCataloguePresenter::instructorCardItem($profile),
            )->values()->all(),
            'stats' => [
                'publishedModulesCount' => Module::query()->published()->count(),
                'independentModulesCount' => Module::query()->published()->where('is_available_independently', true)->count(),
                'upcomingSessionsCount' => TrainingSession::query()->upcomingPublic()->count(),
                'activeCategoriesCount' => $categories->count(),
                'publicInstructorsCount' => InstructorProfile::query()->public()->count(),
                'reservationHoldMinutes' => (int) SystemSetting::getValue('booking.reservation_hold_minutes', 15),
            ],
            'assets' => [
                'heroPhotoUrl' => asset('images/home/hero-field-hydrology.jpg'),
                'heroPhotoWebpUrl' => file_exists(public_path('images/home/hero-field-hydrology.webp'))
                    ? asset('images/home/hero-field-hydrology.webp')
                    : null,
                'classroomPhotoUrl' => asset('images/home/classroom-session.jpg'),
                'classroomPhotoWebpUrl' => file_exists(public_path('images/home/classroom-session.webp'))
                    ? asset('images/home/classroom-session.webp')
                    : null,
                'logoSvgUrl' => asset('images/ecohydrotech-logo.svg'),
            ],
        ]);
    }

    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('q', ''));
        $categorySlug = trim((string) $request->query('category', ''));
        $availability = (string) $request->query('availability', 'all');
        $sort = (string) $request->query('sort', 'newest');

        if (! in_array($availability, ['all', 'independent', 'session_only'], true)) {
            $availability = 'all';
        }

        if (! in_array($sort, ['newest', 'title_asc', 'price_asc'], true)) {
            $sort = 'newest';
        }

        $categories = Category::query()
            ->active()
            ->ordered()
            ->withCount('publishedModules')
            ->get();

        $query = Module::query()
            ->published()
            ->with('category')
            ->withCount(['publishedMaterials', 'publishedVideos', 'upcomingSessions']);

        if ($search !== '') {
            $term = "%{$search}%";
            $query->where(function (Builder $q) use ($term): void {
                $q->where('code', 'like', $term)
                    ->orWhere('title', 'like', $term)
                    ->orWhere('summary', 'like', $term)
                    ->orWhere('description', 'like', $term);
            });
        }

        if ($categorySlug !== '') {
            $query->whereHas('category', fn (Builder $q) => $q->where('slug', $categorySlug)->where('is_active', true));
        }

        if ($availability === 'independent') {
            $query->where('is_available_independently', true);
        } elseif ($availability === 'session_only') {
            $query->where('is_available_independently', false);
        }

        match ($sort) {
            'title_asc' => $query->orderBy('title'),
            'price_asc' => $query->orderByRaw('CASE WHEN independent_price IS NULL THEN 1 ELSE 0 END, independent_price ASC'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };

        $paginator = $query->paginate(9)->withQueryString();

        return Inertia::render('Public/Catalogue/Index', [
            'modules' => collect($paginator->items())
                ->map(fn (Module $module): array => PublicCataloguePresenter::moduleCardItem($module))
                ->values()
                ->all(),
            'pagination' => PublicCataloguePresenter::paginationMeta($paginator),
            'categories' => $categories->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'publishedModulesCount' => (int) $category->published_modules_count,
                'catalogueUrl' => route('catalogue.index', ['category' => $category->slug]),
            ])->values()->all(),
            'filters' => [
                'q' => $search,
                'category' => $categorySlug,
                'availability' => $availability,
                'sort' => $sort,
            ],
            'totalPublishedCount' => (int) $categories->sum('published_modules_count'),
        ]);
    }

    public function show(Request $request, Module $module): Response
    {
        $displayTimezone = (string) config('etms.display_timezone', 'Asia/Kuala_Lumpur');

        $module->loadMissing([
            'category',
            'publishedMaterials',
            'publishedVideos',
            'upcomingSessions.instructors.instructorProfile',
        ]);

        abort_unless(
            $module->isPublished() && $module->category?->is_active,
            404,
        );

        $user = $request->user();
        $price = $module->formattedIndependentPrice();
        $isAvailableIndependently = (bool) $module->is_available_independently && $price !== null;
        $hasActiveEntitlement = $user !== null && $user->hasActiveModuleEntitlement($module->id);
        $canPurchaseAsCustomer = $user !== null && $user->isCustomer();

        return Inertia::render('Public/Catalogue/Show', [
            'module' => [
                'id' => $module->id,
                'code' => $module->code,
                'title' => $module->title,
                'slug' => $module->slug,
                'summary' => $module->summary,
                'description' => $module->description,
                'learningObjectives' => array_values(array_filter(is_array($module->learning_objectives) ? $module->learning_objectives : [])),
                'categoryName' => $module->category->name,
                'categorySlug' => $module->category->slug,
                'categoryUrl' => route('catalogue.index', ['category' => $module->category->slug]),
                'coverImageUrl' => $module->coverImageUrl(),
                'isAvailableIndependently' => $isAvailableIndependently,
                'independentPriceLabel' => $price,
                'checkoutUrl' => $isAvailableIndependently ? route('checkout.module.show', $module) : null,
                'portalModuleUrl' => route('portal.modules.show', $module),
                'hasActiveEntitlement' => $hasActiveEntitlement,
                'canPurchaseAsCustomer' => $canPurchaseAsCustomer,
            ],
            'materials' => $module->publishedMaterials->map(function (ModuleMaterial $material) use ($user): array {
                $canDownload = $user !== null && $user->can('download', $material);

                return [
                    'id' => $material->id,
                    'title' => $material->title,
                    'description' => $material->description,
                    'typeLabel' => $material->material_type->label(),
                    'fileSizeLabel' => $material->formattedFileSize(),
                    'canDownload' => $canDownload,
                    'downloadUrl' => $canDownload ? route('materials.download', $material) : null,
                ];
            })->values()->all(),
            'videos' => $module->publishedVideos->map(fn (ModuleVideo $video): array => [
                'id' => $video->id,
                'title' => $video->title,
                'description' => $video->description,
                'durationLabel' => $video->formattedDuration(),
                'isReady' => $video->isPlayable(),
            ])->values()->all(),
            'upcomingSessions' => $module->upcomingSessions->map(function (TrainingSession $session) use ($module, $displayTimezone): array {
                $session->setRelation('module', $module);

                return PublicCataloguePresenter::sessionListItem($session, $displayTimezone);
            })->values()->all(),
        ]);
    }
}
