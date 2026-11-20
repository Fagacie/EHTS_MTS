<?php

use App\Http\Controllers\Admin\ReportExportController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Instructor\InstructorDashboardController;
use App\Http\Controllers\Instructor\InstructorProfileController;
use App\Http\Controllers\Instructor\InstructorSessionController;
use App\Http\Controllers\MaterialDownloadController;
use App\Http\Controllers\NotificationCentreController;
use App\Http\Controllers\Portal\BookingController;
use App\Http\Controllers\Portal\CertificateController;
use App\Http\Controllers\Portal\LearningPortalController;
use App\Http\Controllers\Portal\ModuleCheckoutController;
use App\Http\Controllers\Portal\OrderController;
use App\Http\Controllers\Portal\PortalDashboardController;
use App\Http\Controllers\Portal\PortalSearchController;
use App\Http\Controllers\Portal\ProfileController;
use App\Http\Controllers\Portal\SecurityController;
use App\Http\Controllers\Portal\SessionCheckoutController;
use App\Http\Controllers\PublicCatalogueController;
use App\Http\Controllers\PublicInstructorController;
use App\Http\Controllers\PublicSessionController;
use App\Http\Controllers\Webhooks\BillplzWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PublicCatalogueController::class, 'home'])->name('home');

Route::get('/training', [PublicCatalogueController::class, 'index'])->name('catalogue.index');
Route::get('/training/{module:slug}', [PublicCatalogueController::class, 'show'])->name('catalogue.modules.show');

Route::get('/sessions', [PublicSessionController::class, 'index'])->name('sessions.index');
Route::get('/sessions/{session:slug}', [PublicSessionController::class, 'show'])->name('sessions.show');

Route::get('/instructors', [PublicInstructorController::class, 'index'])->name('instructors.index');
Route::get('/instructors/{instructor}', [PublicInstructorController::class, 'show'])->name('instructors.show');

Route::post('/webhooks/billplz', BillplzWebhookController::class)
    ->middleware('throttle:webhooks')
    ->name('webhooks.billplz');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:auth-register')
        ->name('register.store');

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/forgot-password', [PasswordResetController::class, 'createLink'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'storeLink'])
        ->middleware('throttle:password-reset')
        ->name('password.email');

    Route::get('/reset-password/{token}', [PasswordResetController::class, 'createReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'storeReset'])
        ->middleware('throttle:password-reset')
        ->name('password.update');
});

Route::middleware(['auth', 'active.account'])->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::middleware('verified')->group(function (): void {
        Route::get('/two-factor-setup', [TwoFactorChallengeController::class, 'showSetup'])->name('two-factor.setup');
        Route::post('/two-factor-setup', [TwoFactorChallengeController::class, 'confirmSetup'])->name('two-factor.setup.confirm');
        Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'showChallenge'])->name('two-factor.challenge');
        Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'verifyChallenge'])
            ->middleware('throttle:6,1')
            ->name('two-factor.challenge.verify');

        Route::get('/materials/{material}/download', MaterialDownloadController::class)
            ->middleware('throttle:downloads')
            ->name('materials.download');
        Route::get('/certificates/{certificate:certificate_number}/download', [CertificateController::class, 'download'])
            ->middleware('throttle:downloads')
            ->name('certificates.download');

        Route::middleware('role:customer')->group(function (): void {
            Route::get('/checkout/session/{session:slug}', [SessionCheckoutController::class, 'show'])->name('checkout.session.show');
            Route::post('/checkout/session/{session:slug}', [SessionCheckoutController::class, 'store'])->name('checkout.session.store');
            Route::get('/checkout/module/{module:slug}', [ModuleCheckoutController::class, 'show'])->name('checkout.module.show');
            Route::post('/checkout/module/{module:slug}', [ModuleCheckoutController::class, 'store'])->name('checkout.module.store');
            Route::get('/checkout/return', [OrderController::class, 'returnPage'])->name('checkout.return');
        });
    });
});

Route::prefix('portal')
    ->name('portal.')
    ->middleware(['auth', 'active.account', 'verified', 'role:customer'])
    ->group(function (): void {
        Route::get('/', PortalDashboardController::class)->name('dashboard');
        Route::get('/search', PortalSearchController::class)->name('search');
        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/{booking:booking_reference}', [BookingController::class, 'show'])->name('bookings.show');
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order:order_number}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order:order_number}/pay', [OrderController::class, 'pay'])->name('orders.pay');
        Route::get('/modules', [LearningPortalController::class, 'modules'])->name('modules.index');
        Route::get('/modules/{module:slug}', [LearningPortalController::class, 'showModule'])->name('modules.show');
        Route::get('/modules/{module:slug}/videos/{video}/watch', [LearningPortalController::class, 'watchModuleVideo'])->name('modules.videos.watch');
        Route::get('/modules/{module:slug}/videos/{video}/stream/{asset}', [LearningPortalController::class, 'streamModuleVideoAsset'])
            ->where('asset', '[A-Za-z0-9._-]+')
            ->middleware('throttle:video-streams')
            ->name('modules.videos.stream');
        Route::get('/recordings', [LearningPortalController::class, 'recordings'])->name('recordings.index');
        Route::get('/recordings/{recording}/watch', [LearningPortalController::class, 'watchRecording'])->name('recordings.watch');
        Route::get('/recordings/{recording}/stream/{asset}', [LearningPortalController::class, 'streamRecordingAsset'])
            ->where('asset', '[A-Za-z0-9._-]+')
            ->middleware('throttle:video-streams')
            ->name('recordings.stream');
        Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
        Route::get('/certificates/{certificate:certificate_number}', [CertificateController::class, 'show'])->name('certificates.show');
        Route::get('/notifications', [NotificationCentreController::class, 'portalIndex'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationCentreController::class, 'markAllAsRead'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [NotificationCentreController::class, 'markAsRead'])->name('notifications.read');
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::get('/security', [SecurityController::class, 'edit'])->name('security.edit');
        Route::put('/security/password', [SecurityController::class, 'updatePassword'])->name('security.password.update');
    });

Route::prefix('instructor')
    ->name('instructor.')
    ->middleware(['auth', 'active.account', 'verified', 'role:instructor'])
    ->group(function (): void {
        Route::get('/', InstructorDashboardController::class)->name('dashboard');
        Route::get('/sessions', [InstructorSessionController::class, 'index'])->name('sessions.index');
        Route::get('/sessions/{session:session_code}', [InstructorSessionController::class, 'show'])->name('sessions.show');
        Route::get('/sessions/{session:session_code}/attendance', [InstructorSessionController::class, 'attendance'])->name('sessions.attendance');
        Route::put('/sessions/{session:session_code}/attendance', [InstructorSessionController::class, 'updateAttendance'])->name('sessions.attendance.update');
        Route::post('/sessions/{session:session_code}/attendance/submit', [InstructorSessionController::class, 'submitAttendance'])->name('sessions.attendance.submit');
        Route::get('/sessions/{session:session_code}/completion', [InstructorSessionController::class, 'completion'])->name('sessions.completion');
        Route::post('/sessions/{session:session_code}/completion', [InstructorSessionController::class, 'storeCompletion'])->name('sessions.completion.store');
        Route::get('/sessions/{session:session_code}/recordings', [InstructorSessionController::class, 'recordings'])->name('sessions.recordings');
        Route::post('/sessions/{session:session_code}/recordings', [InstructorSessionController::class, 'storeRecording'])->name('sessions.recordings.store');
        Route::get('/profile', [InstructorProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [InstructorProfileController::class, 'update'])->name('profile.update');
        Route::get('/notifications', [NotificationCentreController::class, 'instructorIndex'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationCentreController::class, 'markAllAsRead'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [NotificationCentreController::class, 'markAsRead'])->name('notifications.read');
    });

Route::middleware(['auth', 'active.account', 'verified', 'role:administrator', 'admin.mfa'])
    ->get('/admin/reports/export/{dataset}', ReportExportController::class)
    ->name('admin.reports.export');
