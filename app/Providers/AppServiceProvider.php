<?php

namespace App\Providers;

use App\Listeners\TrackNotificationDeliveryListener;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\InstructorProfile;
use App\Models\Module;
use App\Models\ModuleEntitlement;
use App\Models\ModuleMaterial;
use App\Models\ModuleVideo;
use App\Models\NotificationDelivery;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\SessionRecording;
use App\Models\TrainingSession;
use App\Models\User;
use App\Policies\BookingPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CertificatePolicy;
use App\Policies\InstructorProfilePolicy;
use App\Policies\ModuleEntitlementPolicy;
use App\Policies\ModuleMaterialPolicy;
use App\Policies\ModulePolicy;
use App\Policies\ModuleVideoPolicy;
use App\Policies\OrderPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\RefundPolicy;
use App\Policies\SessionRecordingPolicy;
use App\Policies\TrainingSessionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (app()->isProduction() || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        $this->configureRateLimiting();

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Module::class, ModulePolicy::class);
        Gate::policy(ModuleMaterial::class, ModuleMaterialPolicy::class);
        Gate::policy(ModuleVideo::class, ModuleVideoPolicy::class);
        Gate::policy(ModuleEntitlement::class, ModuleEntitlementPolicy::class);
        Gate::policy(Certificate::class, CertificatePolicy::class);
        Gate::policy(InstructorProfile::class, InstructorProfilePolicy::class);
        Gate::policy(TrainingSession::class, TrainingSessionPolicy::class);
        Gate::policy(SessionRecording::class, SessionRecordingPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::policy(Refund::class, RefundPolicy::class);

        Gate::define('access-portal', fn (User $user) => $user->isActive() && $user->isCustomer());
        Gate::define('access-instructor', fn (User $user) => $user->isActive() && $user->isInstructor());
        Gate::define('access-admin', fn (User $user) => $user->isActive() && $user->isAdministrator());

        Event::listen(MessageSent::class, [TrackNotificationDeliveryListener::class, 'handleMessageSent']);
        Event::listen(NotificationFailed::class, [TrackNotificationDeliveryListener::class, 'handleNotificationFailed']);

        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            $deliveryKey = null;
            if (Schema::hasTable('notification_deliveries') && ! empty($notifiable->email)) {
                $deliveryKey = (string) Str::uuid();
                NotificationDelivery::query()->create([
                    'delivery_key' => $deliveryKey,
                    'user_id' => $notifiable->id ?? null,
                    'recipient_email' => $notifiable->email,
                    'event_type' => 'user.email_verification',
                    'channel' => 'mail',
                    'mailer' => (string) config('mail.default'),
                    'subject' => '[ETMS] Verify Your Email Address',
                    'status' => 'queued',
                    'queued_at' => now(),
                ]);
            }

            $mail = (new MailMessage)
                ->subject('[ETMS] Verify Your Email Address')
                ->view('emails.etms-notification', [
                    'subject' => '[ETMS] Verify Your Email Address',
                    'badge' => 'ACCOUNT VERIFICATION',
                    'heading' => 'Verify Your Email Address',
                    'greeting' => 'Dear '.($notifiable->name ?? 'Customer').',',
                    'introLines' => [
                        'Please verify your email address to activate portal access, physical session bookings, and module purchases.',
                    ],
                    'details' => [
                        'Account Email' => (string) ($notifiable->email ?? ''),
                    ],
                    'actionLabel' => 'Verify Email Address',
                    'actionUrl' => $url,
                    'outroLines' => ['If you did not create an Ecohydrotech account, no action is required.'],
                ]);

            if ($deliveryKey !== null) {
                $mail->withSymfonyMessage(static function (Email $email) use ($deliveryKey): void {
                    $email->getHeaders()->addTextHeader('X-ETMS-Delivery-Key', $deliveryKey);
                });
            }

            return $mail;
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $resetUrl = route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);

            $deliveryKey = null;
            if (Schema::hasTable('notification_deliveries') && ! empty($notifiable->email)) {
                $deliveryKey = (string) Str::uuid();
                NotificationDelivery::query()->create([
                    'delivery_key' => $deliveryKey,
                    'user_id' => $notifiable->id ?? null,
                    'recipient_email' => $notifiable->email,
                    'event_type' => 'user.password_reset_link',
                    'channel' => 'mail',
                    'mailer' => (string) config('mail.default'),
                    'subject' => '[ETMS] Reset Your Account Password',
                    'status' => 'queued',
                    'queued_at' => now(),
                ]);
            }

            $mail = (new MailMessage)
                ->subject('[ETMS] Reset Your Account Password')
                ->view('emails.etms-notification', [
                    'subject' => '[ETMS] Reset Your Account Password',
                    'badge' => 'ACCOUNT SECURITY',
                    'heading' => 'Reset Your Account Password',
                    'greeting' => 'Dear '.($notifiable->name ?? 'User').',',
                    'introLines' => [
                        'We received a request to reset your Ecohydrotech ETMS account password.',
                    ],
                    'details' => [
                        'Account Email' => (string) ($notifiable->email ?? ''),
                        'Link Expiry' => '60 minutes',
                    ],
                    'actionLabel' => 'Reset Password',
                    'actionUrl' => $resetUrl,
                    'outroLines' => ['If you did not request a password reset, you can safely ignore this message.'],
                ]);

            if ($deliveryKey !== null) {
                $mail->withSymfonyMessage(static function (Email $email) use ($deliveryKey): void {
                    $email->getHeaders()->addTextHeader('X-ETMS-Delivery-Key', $deliveryKey);
                });
            }

            return $mail;
        });
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('auth-register', function (Request $request): Limit {
            return Limit::perMinute(10)->by((string) $request->ip());
        });

        RateLimiter::for('password-reset', function (Request $request): Limit {
            $email = strtolower((string) $request->input('email', ''));

            return Limit::perMinute(5)->by("{$email}|{$request->ip()}");
        });

        RateLimiter::for('downloads', function (Request $request): Limit {
            return Limit::perMinute(60)->by((string) ($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('video-streams', function (Request $request): Limit {
            return Limit::perMinute(240)->by((string) ($request->user()?->id ?? $request->ip()));
        });

        RateLimiter::for('webhooks', function (Request $request): Limit {
            return Limit::perMinute(120)->by((string) $request->ip());
        });
    }
}
