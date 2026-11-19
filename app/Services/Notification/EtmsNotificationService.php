<?php

namespace App\Services\Notification;

use App\Enums\AnnouncementStatus;
use App\Enums\BookingStatus;
use App\Enums\EntitlementStatus;
use App\Enums\NotificationDeliveryStatus;
use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\ModuleEntitlement;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Notifications\EtmsEventNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class EtmsNotificationService
{
    /**
     * Dispatch an ETMS notification across database and queued mail channels with delivery telemetry.
     *
     * @param  array<string, string>  $details
     * @param  array<string, mixed>  $metadata
     */
    public function notifyUser(
        User $user,
        string $eventType,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        array $details = [],
        ?string $reference = null,
        bool $sendEmail = true,
        bool $sendInApp = true,
        array $metadata = [],
    ): ?NotificationDelivery {
        $delivery = null;
        $deliveryKey = null;

        if ($sendEmail && ! empty($user->email) && Schema::hasTable('notification_deliveries')) {
            $deliveryKey = (string) Str::uuid();
            $delivery = NotificationDelivery::query()->create([
                'delivery_key' => $deliveryKey,
                'user_id' => $user->id,
                'recipient_email' => $user->email,
                'event_type' => $eventType,
                'channel' => 'mail',
                'mailer' => (string) config('mail.default'),
                'subject' => "[ETMS] {$title}",
                'status' => NotificationDeliveryStatus::Queued,
                'metadata' => $metadata,
                'queued_at' => now(),
            ]);
        }

        try {
            $user->notify(new EtmsEventNotification(
                eventType: $eventType,
                title: $title,
                message: $message,
                actionUrl: $actionUrl,
                actionLabel: $actionLabel,
                details: $details,
                reference: $reference,
                sendEmail: $sendEmail,
                sendInApp: $sendInApp,
                deliveryKey: $deliveryKey,
                metadata: $metadata,
            ));
        } catch (Throwable $e) {
            if ($delivery !== null) {
                $delivery->update([
                    'status' => NotificationDeliveryStatus::Failed,
                    'error_message' => mb_substr($e->getMessage(), 0, 2000),
                    'failed_at' => now(),
                ]);
            }
        }

        return $delivery?->fresh();
    }

    /**
     * Resolve announcement target recipients and dispatch notifications via queue.
     */
    public function dispatchAnnouncement(Announcement $announcement, User $actor): int
    {
        $recipients = $this->resolveAnnouncementUsers($announcement);

        $announcement->update(['status' => AnnouncementStatus::Sending]);

        $count = 0;

        foreach ($recipients as $user) {
            $recipientRow = AnnouncementRecipient::query()->firstOrCreate(
                [
                    'announcement_id' => $announcement->id,
                    'user_id' => $user->id,
                ],
                [
                    'delivery_status' => 'pending',
                ],
            );

            $actionUrl = $user->isInstructor() && ! $user->isCustomer()
                ? ($announcement->trainingSession
                    ? route('instructor.sessions.show', $announcement->trainingSession)
                    : route('instructor.notifications.index'))
                : ($announcement->trainingSession
                    ? route('sessions.show', $announcement->trainingSession)
                    : route('portal.notifications.index'));

            $details = [];
            if ($announcement->trainingSession) {
                $details['Session'] = "{$announcement->trainingSession->session_code} — {$announcement->trainingSession->title}";
                $details['Schedule'] = $announcement->trainingSession->formattedSchedule();
            } elseif ($announcement->module) {
                $details['Module'] = "{$announcement->module->code} — {$announcement->module->title}";
            }

            $delivery = $this->notifyUser(
                user: $user,
                eventType: 'session.announcement',
                title: $announcement->title,
                message: $announcement->body,
                actionUrl: $actionUrl,
                actionLabel: 'View Details',
                details: $details,
                reference: 'ANN-'.$announcement->id,
                sendEmail: $announcement->send_email,
                sendInApp: $announcement->send_in_app,
                metadata: ['announcement_id' => $announcement->id],
            );

            $failed = $delivery !== null && $delivery->status === NotificationDeliveryStatus::Failed;

            $recipientRow->update([
                'delivery_status' => $failed ? 'failed' : 'sent',
                'sent_at' => $failed ? null : now(),
                'error_message' => $delivery?->error_message,
            ]);

            $count++;
        }

        $announcement->update([
            'status' => AnnouncementStatus::Sent,
            'sent_at' => now(),
        ]);

        AuditLog::record(
            event: 'announcement.dispatched',
            auditableType: Announcement::class,
            auditableId: $announcement->id,
            oldValues: null,
            newValues: [
                'audience_type' => $announcement->audience_type,
                'recipients_count' => $count,
            ],
            reason: 'Administrator dispatched platform announcement.',
            actor: $actor,
        );

        return $count;
    }

    /**
     * @return Collection<int, User>
     */
    protected function resolveAnnouncementUsers(Announcement $announcement): Collection
    {
        return match ($announcement->audience_type) {
            'session_participants' => $announcement->training_session_id
                ? User::query()
                    ->where('status', 'active')
                    ->where(function ($q) use ($announcement): void {
                        $q->whereIn('id', Booking::query()
                            ->where('training_session_id', $announcement->training_session_id)
                            ->where('status', BookingStatus::Confirmed->value)
                            ->pluck('user_id'))
                            ->orWhereHas('assignedSessions', fn ($sq) => $sq->where('training_sessions.id', $announcement->training_session_id));
                    })
                    ->get()
                : collect(),
            'module_entitled_users' => $announcement->module_id
                ? User::query()
                    ->where('status', 'active')
                    ->whereIn('id', ModuleEntitlement::query()
                        ->where('module_id', $announcement->module_id)
                        ->where('status', EntitlementStatus::Active->value)
                        ->pluck('user_id'))
                    ->get()
                : collect(),
            'role' => $announcement->target_role
                ? User::query()
                    ->where('status', 'active')
                    ->whereHas('roles', fn ($q) => $q->where('slug', $announcement->target_role))
                    ->get()
                : collect(),
            default => User::query()
                ->where('status', 'active')
                ->whereHas('roles', fn ($q) => $q->where('slug', 'customer'))
                ->get(),
        };
    }
}
