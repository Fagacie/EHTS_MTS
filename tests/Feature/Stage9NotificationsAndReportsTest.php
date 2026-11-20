<?php

namespace Tests\Feature;

use App\Console\Commands\SendSessionRemindersCommand;
use App\Enums\AnnouncementStatus;
use App\Enums\BookingStatus;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\RefundMethod;
use App\Enums\SessionStatus;
use App\Models\Announcement;
use App\Models\Booking;
use App\Models\Module;
use App\Models\NotificationDelivery;
use App\Models\Order;
use App\Models\Payment;
use App\Models\TrainingSession;
use App\Models\User;
use App\Notifications\EtmsEventNotification;
use App\Services\Booking\SessionBookingService;
use App\Services\Certificate\CertificateService;
use App\Services\Notification\EtmsNotificationService;
use App\Services\Payment\PaymentOrchestrationService;
use App\Services\Payment\RefundService;
use App\Services\Report\ReportsService;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\CertificateSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SessionAndBookingSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class Stage9NotificationsAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleAndPermissionSeeder::class,
            DemoUserSeeder::class,
            CatalogueSeeder::class,
            SessionAndBookingSeeder::class,
            CertificateSeeder::class,
        ]);
    }

    public function test_branded_queued_notification_delivery_tracking_and_failure_logging(): void
    {
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $notifier = app(EtmsNotificationService::class);

        $delivery = $notifier->notifyUser(
            user: $trainee,
            eventType: 'booking.confirmed',
            title: 'River Basin Field Assessment Seat Confirmed',
            message: 'Your seat allocation is ready for check-in.',
            actionUrl: route('portal.bookings.index', [], false),
            actionLabel: 'Open My Bookings',
            details: ['Session Code' => 'SES-2026-002', 'Amount' => 'MYR 1,450.00'],
            reference: 'BKG-TEST-9001',
        );

        $this->assertNotNull($delivery);
        $delivery->refresh();

        $this->assertSame(NotificationDeliveryStatus::Sent, $delivery->status);
        $this->assertNotNull($delivery->sent_at);
        $this->assertSame('[ETMS] River Basin Field Assessment Seat Confirmed', $delivery->subject);

        // Verify queued contract & queue name
        $queuedNotification = new EtmsEventNotification(
            eventType: 'booking.confirmed',
            title: 'Queue Test',
            message: 'Checking queue assignment',
        );
        $this->assertInstanceOf(ShouldQueue::class, $queuedNotification);
        $this->assertSame('notifications', $queuedNotification->queue);

        // Verify branded HTML rendering directly via toMail()->render()
        $mailableHtml = $queuedNotification->toMail($trainee)->render();
        $this->assertStringContainsString('Ecohydrotech', $mailableHtml);
        $this->assertStringContainsString('Queue Test', $mailableHtml);

        // Verify NotificationFailed listener records failure telemetry
        $failedDelivery = NotificationDelivery::query()->create([
            'delivery_key' => (string) Str::uuid(),
            'user_id' => $trainee->id,
            'recipient_email' => $trainee->email,
            'event_type' => 'payment.failed',
            'channel' => 'mail',
            'mailer' => 'resend',
            'subject' => '[ETMS] Payment Failure Test',
            'status' => NotificationDeliveryStatus::Queued,
            'queued_at' => now(),
        ]);

        $failingNotification = new EtmsEventNotification(
            eventType: 'payment.failed',
            title: 'Payment Failure Test',
            message: 'Simulated SMTP/Resend transport failure',
            deliveryKey: $failedDelivery->delivery_key,
        );

        event(new NotificationFailed(
            notifiable: $trainee,
            notification: $failingNotification,
            channel: 'mail',
            data: ['exception' => new TransportException('Resend upstream timeout 503')],
        ));

        $failedDelivery->refresh();
        $this->assertSame(NotificationDeliveryStatus::Failed, $failedDelivery->status);
        $this->assertNotNull($failedDelivery->failed_at);
        $this->assertStringContainsString('Resend upstream timeout 503', (string) $failedDelivery->error_message);
    }

    public function test_lifecycle_events_trigger_in_app_and_email_notifications_across_modules(): void
    {
        Storage::fake('certificates');

        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $instructor = User::query()->where('email', 'instructor@ecohydrotech.com.my')->firstOrFail();
        $session = TrainingSession::query()->where('session_code', 'SES-2026-002')->firstOrFail();
        $module = Module::query()->where('code', 'EHT-FLD-201')->firstOrFail();

        // 1. Account password update triggers notification
        $this->actingAs($trainee)
            ->put(route('portal.security.password.update'), [
                'current_password' => 'Password123!',
                'password' => 'NewPassword!234',
                'password_confirmation' => 'NewPassword!234',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notification_deliveries', [
            'user_id' => $trainee->id,
            'event_type' => 'user.password_updated',
            'status' => NotificationDeliveryStatus::Sent->value,
        ]);

        // 2. Password reset link & password reset confirmation (guest route)
        $this->post(route('logout'));
        Mail::fake();
        $token = Password::broker()->createToken($trainee);
        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $trainee->email,
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseHas('notification_deliveries', [
            'user_id' => $trainee->id,
            'event_type' => 'user.password_reset',
        ]);

        // 3. Certificate issuance & revocation notifications
        $certService = app(CertificateService::class);
        $cert = $certService->issueCertificate(
            admin: $admin,
            trainee: $trainee,
            recordType: 'module',
            recordId: $module->id,
            completionDate: '2026-10-15',
            notes: 'Completed independent study module.',
        );
        $this->assertDatabaseHas('notification_deliveries', [
            'user_id' => $trainee->id,
            'event_type' => 'certificate.issued',
        ]);

        $certService->revokeCertificate($cert, $admin, 'Verification test revocation.');
        $this->assertDatabaseHas('notification_deliveries', [
            'user_id' => $trainee->id,
            'event_type' => 'certificate.revoked',
        ]);

        // 4. Expired booking hold notification & payment failed/succeeded + refund completed notifications
        $otherCustomer = User::query()->where('email', 'staff.trainee@ecohydrotech.com.my')->firstOrFail();
        $bookingService = app(SessionBookingService::class);

        $expiringBooking = $bookingService->reserveSeat($otherCustomer, $session);
        Booking::query()->whereKey($expiringBooking->id)->update([
            'reservation_expires_at' => now()->subMinute(),
        ]);
        $bookingService->reconcileSessionCounters($session->id, 'Test hold expiration sweep');

        $this->assertDatabaseHas('notification_deliveries', [
            'user_id' => $otherCustomer->id,
            'event_type' => 'booking.expired',
        ]);

        // Confirmed payment & module access notifications
        $orchestrator = app(PaymentOrchestrationService::class);
        $moduleOrder = $orchestrator->createModulePurchaseOrder($otherCustomer, $module);

        $failedAttempt = Payment::query()->create([
            'payment_reference' => 'PAY-STG9-FAIL',
            'order_id' => $moduleOrder->id,
            'user_id' => $otherCustomer->id,
            'gateway' => 'billplz',
            'amount' => $moduleOrder->total_amount,
            'currency' => 'MYR',
            'status' => 'pending',
            'gateway_bill_id' => 'bp_stg9_fail',
        ]);

        $orchestrator->reconcileVerifiedGatewayState(
            paymentId: $failedAttempt->id,
            paid: false,
            state: 'failed',
            paidAt: null,
            transactionId: 'txn_stg9_fail',
            transactionStatus: 'failed',
            rawPayload: ['id' => 'bp_stg9_fail', 'paid' => 'false'],
            actor: $otherCustomer,
        );

        $this->assertDatabaseHas('notification_deliveries', [
            'user_id' => $otherCustomer->id,
            'event_type' => 'payment.failed',
        ]);

        $payment = Payment::query()->create([
            'payment_reference' => 'PAY-STG9-MOD01',
            'order_id' => $moduleOrder->id,
            'user_id' => $otherCustomer->id,
            'gateway' => 'billplz',
            'amount' => $moduleOrder->total_amount,
            'currency' => 'MYR',
            'status' => 'pending',
            'gateway_bill_id' => 'bp_stg9_mod01',
        ]);

        $orchestrator->reconcileVerifiedGatewayState(
            paymentId: $payment->id,
            paid: true,
            state: 'paid',
            paidAt: now(),
            transactionId: 'txn_stg9_01',
            transactionStatus: 'completed',
            rawPayload: ['id' => 'bp_stg9_mod01', 'paid' => 'true'],
            actor: $otherCustomer,
        );

        $this->assertDatabaseHas('notification_deliveries', [
            'user_id' => $otherCustomer->id,
            'event_type' => 'payment.succeeded',
        ]);
        $this->assertDatabaseHas('notification_deliveries', [
            'user_id' => $otherCustomer->id,
            'event_type' => 'module.access_granted',
        ]);

        // Refund completed notification
        app(RefundService::class)->recordAndCompleteAdminRefund(
            payment: $payment->fresh(),
            admin: $admin,
            amount: (float) $payment->amount,
            method: RefundMethod::ManualBankTransfer,
            reason: 'Stage 9 refund notification check.',
            disbursementReference: 'MBB-999001',
        );

        $this->assertDatabaseHas('notification_deliveries', [
            'user_id' => $otherCustomer->id,
            'event_type' => 'refund.completed',
        ]);

        // 5. Session reminder command runs once and deduplicates subsequent runs
        $reminderSession = TrainingSession::query()->create([
            'module_id' => $module->id,
            'session_code' => 'SES-2026-REMIND',
            'title' => 'Reminder Test Field Briefing',
            'slug' => 'ses-2026-remind',
            'description' => 'Upcoming tomorrow',
            'starts_at' => now()->addHours(10),
            'ends_at' => now()->addHours(16),
            'duration_minutes' => 360,
            'timezone' => 'Asia/Kuala_Lumpur',
            'venue_name' => 'Putrajaya Wetland Lab',
            'venue_address' => 'Precinct 13',
            'venue_city' => 'Putrajaya',
            'capacity' => 20,
            'confirmed_seats_count' => 0,
            'price' => 950.00,
            'currency' => 'MYR',
            'registration_opens_at' => now()->subDays(5),
            'registration_closes_at' => now()->addHours(5),
            'status' => SessionStatus::Published,
            'published_at' => now()->subDays(5),
            'created_by' => $admin->id,
        ]);

        $reminderSession->instructors()->sync([$instructor->id]);

        $remBooking = $bookingService->reserveSeat($trainee, $reminderSession);
        $bookingService->confirmBooking($remBooking, $admin, 'Stage 9 reminder test confirmation');

        $this->assertDatabaseHas('notification_deliveries', [
            'user_id' => $trainee->id,
            'event_type' => 'booking.confirmed',
        ]);

        Artisan::call(SendSessionRemindersCommand::class, ['--hours' => 24]);

        $this->assertSame(
            2,
            NotificationDelivery::query()->where('event_type', 'session.reminder')->count()
        );

        // Second run must be idempotent (zero duplicates)
        Artisan::call(SendSessionRemindersCommand::class, ['--hours' => 24]);
        $this->assertSame(
            2,
            NotificationDelivery::query()->where('event_type', 'session.reminder')->count()
        );
    }

    public function test_trainee_and_instructor_notification_centres_and_announcements(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $instructor = User::query()->where('email', 'instructor@ecohydrotech.com.my')->firstOrFail();
        $corporate = User::query()->where('email', 'staff.trainee@ecohydrotech.com.my')->firstOrFail();

        $announcement = Announcement::query()->create([
            'title' => 'Monsoon Field Safety Advisory',
            'body' => 'Please bring PPE boots and safety vests to all practical hydrology fieldworks.',
            'audience_type' => 'all_customers',
            'send_email' => true,
            'send_in_app' => true,
            'status' => AnnouncementStatus::Draft,
            'created_by' => $admin->id,
        ]);

        $dispatchedCount = app(EtmsNotificationService::class)->dispatchAnnouncement($announcement, $admin);
        $this->assertGreaterThanOrEqual(2, $dispatchedCount);
        $announcement->refresh();
        $this->assertSame(AnnouncementStatus::Sent, $announcement->status);

        // Trainee notification centre displays unread items & marks single / all read
        $traineeNotification = $trainee->unreadNotifications()->latest()->firstOrFail();

        $this->actingAs($trainee)
            ->get(route('portal.notifications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Notifications/Index')
                ->where('filter', 'all')
                ->where('unreadCount', fn (int $count): bool => $count >= 1)
                ->where('notifications', fn ($items): bool => collect($items)->contains(
                    fn (array $item): bool => ($item['title'] ?? null) === 'Monsoon Field Safety Advisory',
                )),
            );

        // Corporate cannot mark trainee's notification read
        $this->actingAs($corporate)
            ->post(route('portal.notifications.read', $traineeNotification->id))
            ->assertNotFound();

        $this->actingAs($trainee)
            ->post(route('portal.notifications.read', $traineeNotification->id))
            ->assertRedirect();

        $this->assertNotNull($traineeNotification->fresh()->read_at);

        // Mark remaining notifications read
        app(EtmsNotificationService::class)->notifyUser(
            user: $trainee,
            eventType: 'booking.confirmed',
            title: 'Second Notice For Mark All Test',
            message: 'Body text',
        );
        $this->actingAs($trainee)
            ->post(route('portal.notifications.read-all'))
            ->assertRedirect();
        $this->assertSame(0, $trainee->unreadNotifications()->count());

        // Instructor notification centre works identically
        app(EtmsNotificationService::class)->notifyUser(
            user: $instructor,
            eventType: 'session.updated',
            title: 'Instructor Lab Prep Notice',
            message: 'Updated equipment checklist uploaded.',
            actionUrl: route('instructor.sessions.index', [], false),
            actionLabel: 'View Sessions',
        );

        $instrNotification = $instructor->unreadNotifications()->latest()->firstOrFail();

        $this->actingAs($instructor)
            ->get(route('instructor.notifications.index'))
            ->assertOk()
            ->assertSee('Instructor Lab Prep Notice');

        $this->actingAs($instructor)
            ->post(route('instructor.notifications.read', ['notification' => $instrNotification->id, 'open' => 1]))
            ->assertRedirect(route('instructor.sessions.index', [], false));

        $this->assertNotNull($instrNotification->fresh()->read_at);
    }

    public function test_administrative_reports_csv_exports_and_filament_dashboard_widgets(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();

        $reportsService = app(ReportsService::class);
        $report = $reportsService->generate();

        $this->assertGreaterThanOrEqual(2, $report['customers']['total']);
        $this->assertGreaterThanOrEqual(1, $report['catalogue']['published_modules']);
        $this->assertGreaterThanOrEqual(1, $report['bookings']['confirmed']);
        $this->assertGreaterThan(0.0, $report['revenue']['gross_myr']);
        $this->assertGreaterThanOrEqual(1, $report['certificates']['issued']);
        $this->assertNotEmpty($report['popular_modules']);
        $this->assertNotEmpty($report['instructor_stats']);

        // Future date filter isolates empty activity range cleanly
        $futureFiltered = $reportsService->generate('2035-01-01', '2035-01-31');
        $this->assertSame(0, $futureFiltered['bookings']['total']);
        $this->assertSame(0.0, $futureFiltered['revenue']['gross_myr']);

        // Non-admin customers forbidden from admin reports, exports, and notification pages
        $this->actingAs($trainee)->get('/admin/reports')->assertForbidden();
        $this->actingAs($trainee)->get(route('admin.reports.export', 'revenue'))->assertForbidden();

        // Admin views upgraded Dashboard, Reports page, Announcements, and Deliveries
        $adminSession = ['auth.two_factor_verified' => true];

        $this->actingAs($admin)
            ->withSession($adminSession)
            ->get('/admin')
            ->assertOk()
            ->assertSee('Net Revenue (MYR)')
            ->assertSee('Monthly Revenue Trend (MYR)')
            ->assertSee('Recent Operational Activity');

        $this->actingAs($admin)
            ->withSession($adminSession)
            ->get('/admin/reports')
            ->assertOk()
            ->assertSee('ETMS Administrative Reports')
            ->assertSee('Most Popular Training Modules')
            ->assertSee('Instructor & Session Statistics')
            ->assertSee('Revenue & Payment Summary (MYR)');

        $this->actingAs($admin)
            ->withSession($adminSession)
            ->get('/admin/announcements')
            ->assertOk()
            ->assertSee('Announcements');

        $this->actingAs($admin)
            ->withSession($adminSession)
            ->get('/admin/notification-deliveries')
            ->assertOk()
            ->assertSee('Notification Deliveries');

        // Test all 6 CSV export endpoints
        foreach (['bookings', 'revenue', 'refunds', 'popular_modules', 'instructors', 'certificates'] as $dataset) {
            $response = $this->actingAs($admin)
                ->withSession($adminSession)
                ->get(route('admin.reports.export', $dataset));

            $response->assertOk();
            $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        }
    }
}
