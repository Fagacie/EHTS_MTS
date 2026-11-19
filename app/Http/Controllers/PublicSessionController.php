<?php

namespace App\Http\Controllers;

use App\Enums\SessionStatus;
use App\Models\Category;
use App\Models\TrainingSession;
use App\Services\Booking\SessionBookingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicSessionController extends Controller
{
    public function index(Request $request): View
    {
        $categorySlug = trim((string) $request->query('category', ''));
        $search = trim((string) $request->query('q', ''));

        $sessionsQuery = TrainingSession::query()
            ->upcomingPublic()
            ->with(['module.category', 'instructors.instructorProfile']);

        if ($categorySlug !== '') {
            $sessionsQuery->whereHas('module.category', fn ($q) => $q->where('slug', $categorySlug));
        }

        if ($search !== '') {
            $sessionsQuery->where(function ($q) use ($search): void {
                $q->where('session_code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('venue_name', 'like', "%{$search}%")
                    ->orWhere('venue_city', 'like', "%{$search}%")
                    ->orWhereHas('module', fn ($mq) => $mq->where('code', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%"));
            });
        }

        return view('public.sessions.index', [
            'sessions' => $sessionsQuery->paginate(9)->withQueryString(),
            'categories' => Category::query()->active()->ordered()->get(),
            'selectedCategory' => $categorySlug,
            'search' => $search,
        ]);
    }

    public function show(TrainingSession $session, SessionBookingService $bookingService): View
    {
        $session = $bookingService->reconcileSessionCounters($session->id);
        $session->load(['module.category', 'instructors.instructorProfile']);

        $isPublicStatus = in_array($session->status, [
            SessionStatus::Published,
            SessionStatus::Full,
            SessionStatus::RegistrationClosed,
        ], true);

        abort_unless(
            $isPublicStatus && $session->module && $session->module->isPublished() && $session->module->category?->is_active,
            404,
        );

        $existingBooking = null;
        if (auth()->check()) {
            $existingBooking = $session->bookings()
                ->where('user_id', auth()->id())
                ->whereIn('status', ['pending_payment', 'confirmed'])
                ->first();
        }

        return view('public.sessions.show', [
            'session' => $session,
            'existingBooking' => $existingBooking,
        ]);
    }
}
