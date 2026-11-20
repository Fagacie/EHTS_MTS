<?php

namespace App\Support;

use App\Models\InstructorProfile;
use App\Models\Module;
use App\Models\TrainingSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class PublicCataloguePresenter
{
    /**
     * Format a session into a clean, non-sensitive public list DTO.
     *
     * @return array<string, mixed>
     */
    public static function sessionListItem(TrainingSession $session, ?string $displayTimezone = null): array
    {
        $displayTimezone ??= (string) config('etms.display_timezone', 'Asia/Kuala_Lumpur');
        $sessionTz = $session->timezone ?: $displayTimezone;
        $start = $session->starts_at?->copy()->timezone($sessionTz);
        $end = $session->ends_at?->copy()->timezone($sessionTz);
        $isMultiDay = $start && $end && ! $start->isSameDay($end);

        $instructorNames = $session->instructors
            ->map(fn ($instructor): ?string => $instructor->instructorProfile?->display_name ?? $instructor->name)
            ->filter()
            ->implode(', ');

        $venueLocation = collect([$session->venue_city, $session->venue_state])
            ->filter()
            ->implode(', ');

        $dateRangeLabel = match (true) {
            ! $start => 'TBA',
            ! $isMultiDay || ! $end => $start->format('j M Y'),
            $start->isSameMonth($end) => "{$start->format('j')}\u{2013}{$end->format('j M Y')}",
            $start->isSameYear($end) => "{$start->format('j M')}\u{2013}{$end->format('j M Y')}",
            default => "{$start->format('j M Y')}\u{2013}{$end->format('j M Y')}",
        };

        $tzAbbr = $sessionTz === 'Asia/Kuala_Lumpur' ? 'MYT' : ($start?->format('T') ?: 'MYT');
        $timeLabel = $start ? "{$start->format('g:i A')} {$tzAbbr}" : 'Schedule TBA';
        $isOpen = $session->isOpenForBooking();

        return [
            'id' => $session->id,
            'sessionCode' => $session->session_code,
            'title' => $session->title,
            'slug' => $session->slug,
            'startDayLabel' => $start ? $start->format('j M') : 'TBA',
            'startYearTimeLabel' => $start ? "{$start->format('Y')} · {$start->format('g:i A')}" : 'Schedule TBA',
            'endsDateLabel' => $isMultiDay && $end ? $end->format('j M Y') : null,
            'dateRangeLabel' => $dateRangeLabel,
            'timeLabel' => $timeLabel,
            'isThisMonth' => $start !== null && $start->isSameMonth(now($sessionTz)),
            'durationLabel' => $session->formattedDuration(),
            'venueName' => $session->venue_name,
            'venueLocation' => $venueLocation !== '' ? $venueLocation : null,
            'capacity' => (int) $session->capacity,
            'remainingSeats' => $session->remainingSeats(),
            'isOpenForBooking' => $isOpen,
            'statusLabel' => $session->status->label(),
            'statusValue' => $session->status->value,
            'priceLabel' => $session->formattedPrice(),
            'moduleCode' => $session->module?->code,
            'moduleTitle' => $session->module?->title,
            'moduleSlug' => $session->module?->slug,
            'moduleCategory' => $session->module?->category?->name,
            'instructorNames' => $instructorNames !== '' ? $instructorNames : null,
            'detailUrl' => route('sessions.show', $session),
            'bookUrl' => $isOpen ? route('checkout.session.show', $session) : null,
        ];
    }

    /**
     * Format a module into a clean public card DTO.
     *
     * @return array<string, mixed>
     */
    public static function moduleCardItem(Module $module): array
    {
        $summary = $module->summary ?: Str::limit(strip_tags((string) $module->description), 160);
        $price = $module->formattedIndependentPrice();

        return [
            'id' => $module->id,
            'code' => $module->code,
            'title' => $module->title,
            'slug' => $module->slug,
            'summary' => $summary !== '' ? $summary : null,
            'categoryName' => $module->category?->name ?? 'Training Programme',
            'categorySlug' => $module->category?->slug ?? 'general',
            'coverImageUrl' => $module->coverImageUrl(),
            'isAvailableIndependently' => (bool) $module->is_available_independently && $price !== null,
            'independentPriceLabel' => $price,
            'publishedMaterialsCount' => (int) ($module->published_materials_count ?? 0),
            'publishedVideosCount' => (int) ($module->published_videos_count ?? 0),
            'upcomingSessionsCount' => (int) ($module->upcoming_sessions_count ?? 0),
            'detailUrl' => route('catalogue.modules.show', $module->slug),
        ];
    }

    /**
     * Format a public instructor profile into a safe card DTO without exposing contact emails or phone numbers.
     *
     * @return array<string, mixed>
     */
    public static function instructorCardItem(InstructorProfile $profile, ?int $upcomingSessionsCount = null): array
    {
        $item = [
            'id' => $profile->id,
            'displayName' => $profile->resolvedDisplayName(),
            'professionalTitle' => $profile->professional_title,
            'qualifications' => $profile->qualifications,
            'shortBio' => $profile->short_bio,
            'specialisations' => array_values(array_slice($profile->specialisations ?? [], 0, 4)),
            'photoUrl' => $profile->photoUrl(),
            'profileUrl' => route('instructors.show', $profile),
        ];

        if ($upcomingSessionsCount !== null) {
            $item['upcomingSessionsCount'] = $upcomingSessionsCount;
        }

        return $item;
    }

    /**
     * Build a structured pagination metadata payload from a Laravel paginator.
     *
     * @param  LengthAwarePaginator<int, mixed>  $paginator
     * @return array<string, mixed>
     */
    public static function paginationMeta(LengthAwarePaginator $paginator): array
    {
        $rawLinks = $paginator->linkCollection()->toArray();
        $links = collect($rawLinks)
            ->reject(function (array $link): bool {
                $label = trim(html_entity_decode(strip_tags((string) ($link['label'] ?? ''))));

                return Str::contains($label, ['Previous', 'Next', '«', '»']);
            })
            ->map(fn (array $link): array => [
                'url' => $link['url'] ?? null,
                'label' => trim(html_entity_decode(strip_tags((string) ($link['label'] ?? '')))),
                'active' => (bool) ($link['active'] ?? false),
            ])
            ->values()
            ->all();

        return [
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'perPage' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'prevPageUrl' => $paginator->previousPageUrl(),
            'nextPageUrl' => $paginator->nextPageUrl(),
            'links' => $links,
        ];
    }
}
