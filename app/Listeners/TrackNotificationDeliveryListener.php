<?php

namespace App\Listeners;

use App\Enums\NotificationDeliveryStatus;
use App\Models\NotificationDelivery;
use App\Notifications\EtmsEventNotification;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\Events\NotificationFailed;

class TrackNotificationDeliveryListener
{
    public function handleMessageSent(MessageSent $event): void
    {
        $headers = $event->message->getHeaders();
        if (! $headers->has('X-ETMS-Delivery-Key')) {
            return;
        }

        $deliveryKey = trim((string) $headers->get('X-ETMS-Delivery-Key')?->getBodyAsString());
        if ($deliveryKey === '') {
            return;
        }

        $messageId = null;
        if ($headers->has('Message-ID')) {
            $messageId = trim((string) $headers->get('Message-ID')?->getBodyAsString(), "<> \t\n\r");
        }

        NotificationDelivery::query()
            ->where('delivery_key', $deliveryKey)
            ->update([
                'status' => NotificationDeliveryStatus::Sent->value,
                'mailer' => (string) ($event->data['mailer'] ?? config('mail.default')),
                'provider_message_id' => $messageId ?: null,
                'sent_at' => now(),
                'error_message' => null,
            ]);
    }

    public function handleNotificationFailed(NotificationFailed $event): void
    {
        if ($event->channel !== 'mail') {
            return;
        }

        $deliveryKey = $event->notification instanceof EtmsEventNotification
            ? $event->notification->deliveryKey
            : null;

        if ($deliveryKey === null) {
            return;
        }

        $exception = $event->data['exception'] ?? null;
        $errorMessage = $exception instanceof \Throwable
            ? $exception->getMessage()
            : 'Notification mail dispatch failed.';

        NotificationDelivery::query()
            ->where('delivery_key', $deliveryKey)
            ->update([
                'status' => NotificationDeliveryStatus::Failed->value,
                'error_message' => mb_substr($errorMessage, 0, 2000),
                'failed_at' => now(),
            ]);
    }
}
