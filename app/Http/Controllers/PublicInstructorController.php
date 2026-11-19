<?php

namespace App\Http\Controllers;

use App\Models\InstructorProfile;
use Illuminate\View\View;

class PublicInstructorController extends Controller
{
    public function index(): View
    {
        $instructors = InstructorProfile::query()
            ->public()
            ->with('user')
            ->orderBy('display_name')
            ->get();

        return view('public.instructors.index', [
            'instructors' => $instructors,
        ]);
    }

    public function show(InstructorProfile $instructor): View
    {
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

        return view('public.instructors.show', [
            'instructor' => $instructor,
            'upcomingSessions' => $upcomingSessions,
        ]);
    }
}
