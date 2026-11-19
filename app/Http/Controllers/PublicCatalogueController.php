<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Module;
use App\Models\TrainingSession;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicCatalogueController extends Controller
{
    public function home(): View
    {
        $categories = Category::query()
            ->active()
            ->ordered()
            ->withCount('publishedModules')
            ->get();

        $featuredModules = Module::query()
            ->published()
            ->with('category')
            ->withCount('publishedMaterials')
            ->orderByDesc('published_at')
            ->limit(6)
            ->get();

        $upcomingSessions = TrainingSession::query()
            ->upcomingPublic()
            ->with(['module.category', 'instructors.instructorProfile'])
            ->limit(4)
            ->get();

        return view('public.home', [
            'categories' => $categories,
            'featuredModules' => $featuredModules,
            'upcomingSessions' => $upcomingSessions,
        ]);
    }


    public function index(Request $request): View
    {
        return view('public.catalogue');
    }

    public function show(Module $module): View
    {
        $module->loadMissing([
            'category',
            'publishedMaterials',
            'upcomingSessions.instructors.instructorProfile',
        ]);

        abort_unless(
            $module->isPublished() && $module->category?->is_active,
            404,
        );

        return view('public.module-show', [
            'module' => $module,
        ]);
    }
}
