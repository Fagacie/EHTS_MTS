<?php

namespace App\Support;

use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Enums\BookingStatus;
use App\Enums\EncodingStatus;
use App\Enums\RecordingStatus;
use App\Models\Attendance;
use App\Models\Booking;
use App\Models\InstructorProfile;
use App\Models\SessionRecording;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class InstructorPresenter
{
    public static function displayTimezone(): string
    {
        return (string) config('etms.display_timezone', 'Asia/Kuala_Lumpur');
    }

    public static function formatDateTime(?\DateTimeInterface $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return $date->setTimezone(new \DateTimeZone(self::displayTimezone()))->format('d M Y, h:i A');
    }

    public static function formatDate(?\DateTimeInterface $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return $date->setTimezone(new \DateTimeZone(self::displayTimezone()))->format('d M Y');
    }

    /**
     * @return array<string, mixed>
     */
    public static function sessionCard(TrainingSession $session, ?User $instructor = null): array
    {
        $roleLabel = 'Instructor';
        if ($session->relationLoaded('instructors') && $instructor !== null) {
            $assignedInstructor = $session->instructors->firstWhere('id', $instructor->id);
            $roleLabel = $assignedInstructor?->pivot->role_label ?? 'Instructor';
        } elseif (isset($session->pivot->role_label)) {
            $roleLabel = (string) $session->pivot->role_label;
        }

        $confirmedCount = (int) ($session->confirmed_participants_count ?? $session->bookings()
            ->where('status', BookingStatus::Confirmed->value)
            ->count());

        $recordingsCount = (int) ($session->recordings_count ?? $session->recordings()->count());

        return [
            'id' => $session->id,
            'sessionCode' => $session->session_code,
            'slug' => $session->slug,
            'title' => $session->title,
            'statusValue' => $session->status->value,
            'statusLabel' => $session->status->label(),
            'moduleCode' => $session->module?->code,
            'moduleTitle' => $session->module?->title,
            'categoryName' => $session->module?->category?->name,
            'scheduleLabel' => $session->formattedSchedule(),
            'durationLabel' => $session->formattedDuration(),
            'venueName' => $session->venue_name,
            'venueCity' => $session->venue_city,
            'venueState' => $session->venue_state,
            'venueAddress' => $session->venue_address,
            'roleLabel' => $roleLabel,
            'confirmedParticipantsCount' => $confirmedCount,
            'capacity' => (int) $session->capacity,
            'recordingsCount' => $recordingsCount,
            'recordingAccessPolicy' => $session->recording_access_policy->value,
            'recordingAccessPolicyLabel' => $session->recording_access_policy->label(),
            'completionNotes' => $session->completion_notes,
            'completedAtLabel' => self::formatDateTime($session->completed_at),
            'showUrl' => route('instructor.sessions.show', $session),
            'attendanceUrl' => route('instructor.sessions.attendance', $session),
            'completionUrl' => route('instructor.sessions.completion', $session),
            'recordingsUrl' => route('instructor.sessions.recordings', $session),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function rosterParticipant(Booking $booking): array
    {
        $attendance = $booking->attendance;

        return [
            'bookingId' => $booking->id,
            'bookingReference' => $booking->booking_reference,
            'participantName' => $booking->user?->name ?? 'Participant',
            'organisation' => 'Trainee',
            'attendanceStatusValue' => $attendance?->attendance_status?->value ?? AttendanceStatus::Pending->value,
            'attendanceStatusLabel' => $attendance?->attendance_status?->label() ?? AttendanceStatus::Pending->label(),
            'reviewStatusValue' => $attendance?->review_status?->value ?? AttendanceReviewStatus::Draft->value,
            'reviewStatusLabel' => $attendance?->review_status?->label() ?? AttendanceReviewStatus::Draft->label(),
            'remarks' => $attendance?->remarks,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function attendanceRecord(Attendance $attendance): array
    {
        return [
            'id' => $attendance->id,
            'bookingId' => $attendance->booking_id,
            'bookingReference' => $attendance->booking?->booking_reference ?? '—',
            'participantName' => $attendance->user?->name ?? 'Participant',
            'organisation' => 'Trainee',
            'attendanceStatus' => $attendance->attendance_status->value,
            'attendanceStatusLabel' => $attendance->attendance_status->label(),
            'remarks' => $attendance->remarks ?? '',
            'reviewStatus' => $attendance->review_status->value,
            'reviewStatusLabel' => $attendance->review_status->label(),
            'recordedAtLabel' => self::formatDateTime($attendance->recorded_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function recordingItem(SessionRecording $recording): array
    {
        $durationLabel = null;
        if ($recording->duration_seconds !== null && $recording->duration_seconds > 0) {
            $mins = (int) round($recording->duration_seconds / 60);
            $durationLabel = "{$mins} min";
        }

        return [
            'id' => $recording->id,
            'title' => $recording->title,
            'description' => $recording->description,
            'providerVideoId' => $recording->provider_video_id,
            'storageDisk' => $recording->storage_disk ?: 'recordings',
            'originalFilename' => $recording->original_filename,
            'durationSeconds' => $recording->duration_seconds,
            'durationLabel' => $durationLabel,
            'encodingStatus' => $recording->encoding_status->value,
            'encodingStatusLabel' => $recording->encoding_status->label(),
            'status' => $recording->status->value,
            'statusLabel' => $recording->status->label(),
            'submittedAtLabel' => self::formatDateTime($recording->submitted_at),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function profileData(User $user, InstructorProfile $profile): array
    {
        $specialisations = is_array($profile->specialisations) ? $profile->specialisations : [];

        return [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'photoUrl' => $user->photoUrl(),
            ],
            'profile' => [
                'displayName' => $profile->display_name ?: $user->name,
                'professionalTitle' => $profile->professional_title ?? '',
                'qualifications' => $profile->qualifications ?? '',
                'shortBio' => $profile->short_bio ?? '',
                'specialisations' => $specialisations,
                'specialisationsText' => implode(', ', $specialisations),
                'biography' => $profile->biography ?? '',
                'isPublic' => (bool) $profile->is_public,
                'photoUrl' => $profile->photoUrl(),
                'publicProfileUrl' => $profile->is_public ? route('instructors.show', $profile) : null,
            ],
            'updateUrl' => route('instructor.profile.update'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function notificationItem(DatabaseNotification $notification): array
    {
        $data = is_array($notification->data) ? $notification->data : [];
        $actionUrl = is_string($data['action_url'] ?? null) ? $data['action_url'] : null;

        $rawDetails = $data['details'] ?? null;
        $details = [];
        $detailText = null;

        if (is_array($rawDetails)) {
            foreach ($rawDetails as $label => $value) {
                $details[] = [
                    'label' => (string) $label,
                    'value' => is_scalar($value) ? (string) $value : (string) json_encode($value),
                ];
            }
        } elseif (is_string($rawDetails) && trim($rawDetails) !== '') {
            $detailText = trim($rawDetails);
        }

        return [
            'id' => (string) $notification->id,
            'title' => (string) ($data['title'] ?? 'Notification'),
            'message' => (string) ($data['message'] ?? ''),
            'reference' => isset($data['reference']) ? (string) $data['reference'] : null,
            'details' => $details,
            'detailText' => $detailText,
            'isUnread' => $notification->read_at === null,
            'createdAtLabel' => self::formatDateTime($notification->created_at),
            'actionUrl' => $actionUrl,
            'actionLabel' => (string) ($data['action_label'] ?? 'View Details'),
            'actionIsInertia' => PortalPresenter::isInertiaPath($actionUrl),
            'markReadUrl' => route('instructor.notifications.read', $notification),
        ];
    }
}
