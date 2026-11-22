<?php

namespace App\Http\Controllers;

use App\Enums\RecordingAccessPolicy;
use App\Enums\SessionStatus;
use App\Models\Category;
use App\Models\TrainingSession;
use App\Services\Booking\SessionBookingService;
use App\Support\PublicCataloguePresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicSessionController extends Controller
{
    public function index(Request $request): Response
    {
        $displayTimezone = (string) config('etms.display_timezone', 'Asia/Kuala_Lumpur');
        $categorySlug = trim((string) $request->query('category', ''));
        $search = trim((string) $request->query('q', ''));
        $availability = (string) $request->query('availability', 'all');

        if (! in_array($availability, ['all', 'this_month', 'open_seats'], true)) {
            $availability = 'all';
        }

        $categories = Category::query()
            ->active()
            ->ordered()
            ->withCount('publishedModules')
            ->get();

        $sessionsQuery = TrainingSession::query()
            ->upcomingPublic()
            ->with(['module.category', 'instructors.instructorProfile']);

        if ($categorySlug !== '') {
            $sessionsQuery->whereHas('module.category', fn ($q) => $q->where('slug', $categorySlug)->where('is_active', true));
        }

        if ($search !== '') {
            $sessionsQuery->where(function ($q) use ($search): void {
                $q->where('session_code', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('venue_name', 'like', "%{$search}%")
                    ->orWhere('venue_city', 'like', "%{$search}%")
                    ->orWhere('venue_state', 'like', "%{$search}%");
            });
        }

        if ($availability === 'this_month') {
            $monthStartUtc = now($displayTimezone)->startOfMonth()->utc();
            $monthEndUtc = now($displayTimezone)->endOfMonth()->utc();
            $sessionsQuery->whereBetween('starts_at', [$monthStartUtc, $monthEndUtc]);
        } elseif ($availability === 'open_seats') {
            $nowUtc = now();
            $sessionsQuery
                ->where('status', SessionStatus::Published->value)
                ->whereRaw('(reserved_seats_count + confirmed_seats_count) < capacity')
                ->where(fn ($q) => $q->whereNull('registration_opens_at')->orWhere('registration_opens_at', '<=', $nowUtc))
                ->where(fn ($q) => $q->whereNull('registration_closes_at')->orWhere('registration_closes_at', '>', $nowUtc));
        }

        $paginator = $sessionsQuery->paginate(9)->withQueryString();

        return Inertia::render('Public/Sessions/Index', [
            'sessions' => collect($paginator->items())
                ->map(fn (TrainingSession $session): array => PublicCataloguePresenter::sessionListItem($session, $displayTimezone))
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
            ],
            'totalUpcomingCount' => TrainingSession::query()->upcomingPublic()->count(),
        ]);
    }

    public function show(Request $request, TrainingSession $session, SessionBookingService $bookingService): Response
    {
        $displayTimezone = (string) config('etms.display_timezone', 'Asia/Kuala_Lumpur');

        $session = $bookingService->reconcileSessionCounters($session->id);
        $session->load(['module.category', 'instructors.instructorProfile']);

        $isPublicStatus = in_array($session->status, [
            SessionStatus::Published,
            SessionStatus::Full,
            SessionStatus::RegistrationClosed,
        ], true);

        abort_unless($isPublicStatus, 404);

        $existingBooking = null;
        if ($request->user()) {
            $bookingRecord = $session->bookings()
                ->where('user_id', $request->user()->id)
                ->whereIn('status', ['pending_payment', 'confirmed'])
                ->first();

            if ($bookingRecord) {
                $existingBooking = [
                    'bookingReference' => $bookingRecord->booking_reference,
                    'statusLabel' => $bookingRecord->status->label(),
                    'statusValue' => $bookingRecord->status->value,
                    'detailUrl' => route('portal.bookings.show', $bookingRecord),
                ];
            }
        }

        $sessionTz = $session->timezone ?: $displayTimezone;
        $start = $session->starts_at?->copy()->timezone($sessionTz);
        $end = $session->ends_at?->copy()->timezone($sessionTz);
        $isMultiDay = $start && $end && ! $start->isSameDay($end);
        $tzAbbr = $sessionTz === 'Asia/Kuala_Lumpur' ? 'MYT' : ($start?->format('T') ?: 'MYT');

        $dateRangeLabel = match (true) {
            ! $start => 'TBA',
            ! $isMultiDay || ! $end => $start->format('j M Y'),
            $start->isSameMonth($end) => "{$start->format('j')}\u{2013}{$end->format('j M Y')}",
            $start->isSameYear($end) => "{$start->format('j M')}\u{2013}{$end->format('j M Y')}",
            default => "{$start->format('j M Y')}\u{2013}{$end->format('j M Y')}",
        };

        $timeLabel = $start ? "{$start->format('g:i A')} {$tzAbbr}" : 'Schedule TBA';
        $remainingSeats = $session->remainingSeats();
        $isOpenForBooking = $session->isOpenForBooking();
        $venueLocation = collect([$session->venue_city, $session->venue_state])->filter()->implode(', ');
        $venueAddressLine = collect([
            $session->venue_address,
            $session->venue_city,
            $session->venue_state,
            'Malaysia',
        ])->filter()->implode(', ');

        $recordingPolicyLabel = $session->recording_access_policy === RecordingAccessPolicy::AttendeesOnly
            ? 'Post-session recordings included for verified present attendees'
            : 'In-person live instruction only';

        return Inertia::render('Public/Sessions/Show', [
            'session' => [
                'id' => $session->id,
                'sessionCode' => $session->session_code,
                'title' => $session->title,
                'slug' => $session->slug,
                'description' => $session->description,
                'coverImageUrl' => $session->coverImageUrl(),
                'dateRangeLabel' => $dateRangeLabel,
                'timeLabel' => $timeLabel,
                'scheduleLabel' => $session->formattedSchedule(),
                'durationLabel' => $session->formattedDuration(),
                'registrationClosesLabel' => $session->registration_closes_at
                    ? $session->registration_closes_at->copy()->timezone($sessionTz)->format('j M Y, g:i A')." {$tzAbbr}"
                    : null,
                'venueName' => $session->venue_name,
                'venueAddressLine' => $venueAddressLine !== '' ? $venueAddressLine : null,
                'venueLocation' => $venueLocation !== '' ? $venueLocation : null,
                'capacity' => (int) $session->capacity,
                'remainingSeats' => $remainingSeats,
                'seatsOpenLabel' => "{$remainingSeats} / {$session->capacity} seats open",
                'isOpenForBooking' => $isOpenForBooking,
                'isFull' => $remainingSeats === 0 || $session->status === SessionStatus::Full,
                'statusLabel' => $session->status->label(),
                'statusValue' => $session->status->value,
                'priceLabel' => $session->formattedPrice(),
                'recordingPolicyLabel' => $recordingPolicyLabel,
                'bookUrl' => $isOpenForBooking ? route('checkout.session.show', $session) : null,
                'module' => null,
                'instructors' => $session->instructors->map(function ($instructorUser): array {
                    $profile = $instructorUser->instructorProfile;
                    $canLinkProfile = $profile && $profile->is_public && $instructorUser->isActive();

                    return [
                        'id' => $instructorUser->id,
                        'displayName' => $profile?->display_name ?: $instructorUser->name,
                        'roleLabel' => $instructorUser->pivot?->role_label ?: 'Instructor',
                        'professionalTitle' => $profile?->professional_title,
                        'qualifications' => $profile?->qualifications,
                        'photoUrl' => $profile?->photoUrl(),
                        'profileUrl' => $canLinkProfile ? route('instructors.show', $profile) : null,
                    ];
                })->values()->all(),
            ],
            'existingBooking' => $existingBooking,
            'canBookAsCustomer' => $request->user()?->isCustomer() ?? false,
        ]);
    }
}
