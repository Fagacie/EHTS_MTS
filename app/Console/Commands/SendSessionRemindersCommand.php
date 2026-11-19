<?php

namespace App\Console\Commands;

use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Models\NotificationDelivery;
use App\Models\TrainingSession;
use App\Services\Notification\EtmsNotificationService;
use Illuminate\Console\Command;

class SendSessionRemindersCommand extends Command
{
    protected $signature = 'etms:send-session-reminders {--hours=24 : Send reminders for sessions starting within this many hours}';

    protected $description = 'Send automated session reminder notifications to confirmed participants and assigned instructors.';

    public function handle(EtmsNotificationService $notifier): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $now = now();
        $windowEnd = $now->copy()->addHours($hours);
        $tz = config('etms.display_timezone', 'Asia/Kuala_Lumpur');

        $sessions = TrainingSession::query()
            ->whereIn('status', [
                SessionStatus::Published->value,
                SessionStatus::Full->value,
                SessionStatus::RegistrationClosed->value,
            ])
            ->where('starts_at', '>', $now)
            ->where('starts_at', '<=', $windowEnd)
            ->with([
                'bookings' => fn ($q) => $q->where('status', BookingStatus::Confirmed->value)->with('user'),
                'instructors',
            ])
            ->get();

        $dispatched = 0;

        foreach ($sessions as $session) {
            $scheduleMyt = $session->starts_at?->timezone($tz)->format('d M Y, h:i A').' MYT';
            $venueText = trim("{$session->venue_name}, {$session->venue_city}", ', ');

            foreach ($session->bookings as $booking) {
                $user = $booking->user;
                if (! $user) {
                    continue;
                }

                $alreadySent = NotificationDelivery::query()
                    ->where('event_type', 'session.reminder')
                    ->where('user_id', $user->id)
                    ->where('metadata->session_id', $session->id)
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                $notifier->notifyUser(
                    user: $user,
                    eventType: 'session.reminder',
                    title: "Upcoming Session Reminder: {$session->title}",
                    message: "Reminder: your confirmed training session {$session->title} ({$session->session_code}) starts on {$scheduleMyt}.",
                    actionUrl: route('portal.bookings.show', $booking, false),
                    actionLabel: 'View Booking & Venue',
                    details: [
                        'Booking Reference' => $booking->booking_reference,
                        'Session Code' => $session->session_code,
                        'Start (MYT)' => $scheduleMyt,
                        'Venue' => $venueText ?: 'See booking details',
                    ],
                    reference: $booking->booking_reference,
                    metadata: [
                        'session_id' => $session->id,
                        'role' => 'trainee',
                    ],
                );

                $dispatched++;
            }

            foreach ($session->instructors as $instructor) {
                $alreadySent = NotificationDelivery::query()
                    ->where('event_type', 'session.reminder')
                    ->where('user_id', $instructor->id)
                    ->where('metadata->session_id', $session->id)
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                $notifier->notifyUser(
                    user: $instructor,
                    eventType: 'session.reminder',
                    title: "Instructor Session Reminder: {$session->title}",
                    message: "Reminder: you are assigned to facilitate {$session->title} ({$session->session_code}) starting {$scheduleMyt}.",
                    actionUrl: route('instructor.sessions.show', $session, false),
                    actionLabel: 'Open Instructor Session',
                    details: [
                        'Session Code' => $session->session_code,
                        'Start (MYT)' => $scheduleMyt,
                        'Venue' => $venueText ?: 'See session details',
                        'Confirmed Seats' => (string) $session->confirmed_seats_count,
                    ],
                    reference: $session->session_code,
                    metadata: [
                        'session_id' => $session->id,
                        'role' => 'instructor',
                    ],
                );

                $dispatched++;
            }
        }

        $this->info("Dispatched {$dispatched} session reminder(s).");

        return self::SUCCESS;
    }
}
