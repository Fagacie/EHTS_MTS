<?php

namespace App\Http\Controllers;

use App\Models\InstructorProfile;
use App\Models\TrainingSession;
use App\Support\PublicCataloguePresenter;
use Inertia\Inertia;
use Inertia\Response;

class PublicInstructorController extends Controller
{
    public function index(): Response
    {
        $instructors = InstructorProfile::query()
            ->public()
            ->with([
                'user' => fn ($query) => $query->withCount([
                    'assignedSessions as upcoming_sessions_count' => fn ($sq) => $sq->upcomingPublic(),
                ]),
            ])
            ->orderBy('display_name')
            ->get();

        return Inertia::render('Public/Instructors/Index', [
            'instructors' => $instructors->map(
                fn (InstructorProfile $profile): array => PublicCataloguePresenter::instructorCardItem(
                    $profile,
                    (int) ($profile->user?->upcoming_sessions_count ?? 0),
                ),
            )->values()->all(),
        ]);
    }

    public function show(InstructorProfile $instructor): Response
    {
        $displayTimezone = (string) config('etms.display_timezone', 'Asia/Kuala_Lumpur');

        $instructor->loadMissing('user');

        abort_unless(
            $instructor->is_public && $instructor->user && $instructor->user->isActive(),
            404,
        );

        $upcomingSessions = $instructor->user
            ->assignedSessions()
            ->upcomingPublic()
            ->with(['module.category', 'instructors.instructorProfile'])
            ->get();

        return Inertia::render('Public/Instructors/Show', [
            'instructor' => [
                'id' => $instructor->id,
                'displayName' => $instructor->resolvedDisplayName(),
                'professionalTitle' => $instructor->professional_title,
                'qualifications' => $instructor->qualifications,
                'shortBio' => $instructor->short_bio,
                'biography' => $instructor->biography,
                'specialisations' => array_values(array_filter($instructor->specialisations ?? [])),
                'photoUrl' => $instructor->photoUrl(),
            ],
            'upcomingSessions' => $upcomingSessions->map(
                fn (TrainingSession $session): array => PublicCataloguePresenter::sessionListItem($session, $displayTimezone),
            )->values()->all(),
        ]);
    }
}
