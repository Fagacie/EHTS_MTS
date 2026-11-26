<?php

namespace App\Policies;

use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Enums\BookingStatus;
use App\Enums\EncodingStatus;
use App\Enums\RecordingAccessPolicy;
use App\Enums\RecordingStatus;
use App\Models\Attendance;
use App\Models\Booking;
use App\Models\SessionRecording;
use App\Models\User;

class SessionRecordingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator() && $user->hasPermission('recordings.publish');
    }

    public function view(User $user, SessionRecording $recording): bool
    {
        if ($user->isInstructor() && $recording->trainingSession?->instructors()->where('users.id', $user->id)->exists()) {
            return true;
        }

        return $user->isAdministrator() && $user->hasPermission('recordings.publish');
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator() && $user->hasPermission('recordings.publish');
    }

    public function update(User $user, SessionRecording $recording): bool
    {
        return $user->isAdministrator() && $user->hasPermission('recordings.publish');
    }

    public function delete(User $user, SessionRecording $recording): bool
    {
        return $user->isAdministrator() && $user->hasPermission('recordings.publish');
    }

    /**
     * Authorize playback of a session recording (`D-017`, Section 6.3).
     * Requires `recording_access_policy = 'attendees_only'`, confirmed booking, and verified `present` attendance.
     */
    public function watch(User $user, SessionRecording $recording): bool
    {
        if (! $user->isActive()) {
            return false;
        }

        if ($user->isAdministrator() && $user->hasPermission('recordings.publish')) {
            return true;
        }

        $session = $recording->trainingSession;
        if (! $session) {
            return false;
        }

        if ($user->isInstructor() && $session->instructors()->where('users.id', $user->id)->exists()) {
            return true;
        }

        if (
            $recording->status !== RecordingStatus::Published
            || $recording->encoding_status !== EncodingStatus::Ready
            || $session->recording_access_policy !== RecordingAccessPolicy::AttendeesOnly
        ) {
            return false;
        }

        return Booking::query()
            ->where('user_id', $user->id)
            ->where('training_session_id', $session->id)
            ->where('status', BookingStatus::Confirmed->value)
            ->exists();
    }
}
