<?php

namespace App\Notifications;

use App\Enums\NotificationDeliveryStatus;
use App\Models\NotificationDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Symfony\Component\Mime\Email;
use Throwable;

class EtmsEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    /**
     * @param  array<string, string>  $details
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public readonly string $eventType,
        public readonly string $title,
        public readonly string $message,
        public readonly ?string $actionUrl = null,
        public readonly ?string $actionLabel = null,
        public readonly array $details = [],
        public readonly ?string $reference = null,
        public readonly bool $sendEmail = true,
        public readonly bool $sendInApp = true,
        public readonly ?string $deliveryKey = null,
        public readonly array $metadata = [],
    ) {
        $this->onQueue('notifications');
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($this->sendInApp) {
            $channels[] = 'database';
        }

        if ($this->sendEmail && ! empty($notifiable->email)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $recipientName = $notifiable->name ?? 'Valued Participant';

        $mail = (new MailMessage)
            ->subject("[ETMS] {$this->title}")
            ->view('emails.etms-notification', [
                'subject' => "[ETMS] {$this->title}",
                'badge' => str_replace('.', ' · ', strtoupper($this->eventType)),
                'heading' => $this->title,
                'greeting' => "Dear {$recipientName},",
                'introLines' => [$this->message],
                'details' => $this->details,
                'actionLabel' => $this->actionLabel,
                'actionUrl' => $this->actionUrl,
                'outroLines' => ['If you did not expect this update or need assistance, please reply to our support team.'],
            ]);

        if ($this->deliveryKey !== null) {
            $deliveryKey = $this->deliveryKey;
            $mail->withSymfonyMessage(static function (Email $email) use ($deliveryKey): void {
                $email->getHeaders()->addTextHeader('X-ETMS-Delivery-Key', $deliveryKey);
            });
        }

        return $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event_type' => $this->eventType,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'action_label' => $this->actionLabel,
            'reference' => $this->reference,
            'details' => $this->details,
            'metadata' => $this->metadata,
        ];
    }

    public function failed(Throwable $exception): void
    {
        if ($this->deliveryKey === null) {
            return;
        }

        NotificationDelivery::query()
            ->where('delivery_key', $this->deliveryKey)
            ->update([
                'status' => NotificationDeliveryStatus::Failed->value,
                'error_message' => mb_substr($exception->getMessage(), 0, 2000),
                'failed_at' => now(),
            ]);
    }
}
