<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminTrainingSessionController;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\Admin\AdminRecordingController;
use App\Http\Controllers\Admin\AdminFinanceController;
use App\Http\Controllers\Admin\AdminOperationsController;
use App\Http\Controllers\Admin\AdminContentController;
use App\Http\Controllers\Admin\AdminCommerceGovernanceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Instructor\InstructorDashboardController;
use App\Http\Controllers\Instructor\InstructorProfileController;
use App\Http\Controllers\Instructor\InstructorSessionController;
use App\Http\Controllers\MaterialDownloadController;
use App\Http\Controllers\SessionMaterialDownloadController;
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
use App\Http\Controllers\Portal\SessionDocumentCheckoutController;
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
        Route::get('/materials/{material}/download', MaterialDownloadController::class)
            ->middleware('throttle:downloads')
            ->name('materials.download');
        Route::get('/session-materials/{material}/download', SessionMaterialDownloadController::class)
            ->middleware('throttle:downloads')
            ->name('session-materials.download');
        Route::get('/certificates/{certificate:certificate_number}/download', [CertificateController::class, 'download'])
            ->middleware('throttle:downloads')
            ->name('certificates.download');

        Route::middleware('role:customer')->group(function (): void {
            Route::get('/checkout/session/{session:slug}', [SessionCheckoutController::class, 'show'])->name('checkout.session.show');
            Route::post('/checkout/session/{session:slug}', [SessionCheckoutController::class, 'store'])->name('checkout.session.store');
            Route::get('/checkout/module/{module:slug}', [ModuleCheckoutController::class, 'show'])->name('checkout.module.show');
            Route::post('/checkout/module/{module:slug}', [ModuleCheckoutController::class, 'store'])->name('checkout.module.store');
            Route::get('/checkout/session-document/{session:slug}', [SessionDocumentCheckoutController::class, 'show'])->name('checkout.session-document.show');
            Route::post('/checkout/session-document/{session:slug}', [SessionDocumentCheckoutController::class, 'store'])->name('checkout.session-document.store');
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
        Route::post('/sessions/{session:session_code}/materials', [InstructorSessionController::class, 'storeMaterial'])->name('sessions.materials.store');
        Route::get('/profile', [InstructorProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [InstructorProfileController::class, 'update'])->name('profile.update');
        Route::get('/notifications', [NotificationCentreController::class, 'instructorIndex'])->name('notifications.index');
        Route::post('/notifications/read-all', [NotificationCentreController::class, 'markAllAsRead'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [NotificationCentreController::class, 'markAsRead'])->name('notifications.read');
    });

Route::middleware(['auth', 'active.account', 'verified', 'role:administrator'])
    ->get('/admin-vue', AdminDashboardController::class)
    ->name('admin-vue.dashboard');

Route::middleware(['auth', 'active.account', 'verified', 'role:administrator'])
    ->get('/admin-vue/sessions', [AdminTrainingSessionController::class, 'index'])
    ->name('admin-vue.sessions.index');

Route::middleware(['auth', 'active.account', 'verified', 'role:administrator'])
    ->group(function (): void {
        Route::get('/admin-vue/sessions/create', [AdminTrainingSessionController::class, 'create'])->name('admin-vue.sessions.create');
        Route::post('/admin-vue/sessions', [AdminTrainingSessionController::class, 'store'])->name('admin-vue.sessions.store');
        Route::get('/admin-vue/sessions/{trainingSession}/edit', [AdminTrainingSessionController::class, 'edit'])->name('admin-vue.sessions.edit');
        Route::put('/admin-vue/sessions/{trainingSession}', [AdminTrainingSessionController::class, 'update'])->name('admin-vue.sessions.update');
        Route::delete('/admin-vue/sessions/{trainingSession}', [AdminTrainingSessionController::class, 'destroy'])->name('admin-vue.sessions.destroy');
        Route::post('/admin-vue/sessions/{trainingSession}/materials', [AdminTrainingSessionController::class, 'storeMaterial'])->name('admin-vue.sessions.materials.store');
        Route::post('/admin-vue/sessions/{trainingSession}/materials/{material}', [AdminTrainingSessionController::class, 'updateMaterial'])->name('admin-vue.sessions.materials.update');
        Route::delete('/admin-vue/sessions/{trainingSession}/materials/{material}', [AdminTrainingSessionController::class, 'destroyMaterial'])->name('admin-vue.sessions.materials.destroy');
    });

Route::middleware(['auth', 'active.account', 'verified', 'role:administrator'])
    ->get('/admin-vue/sessions/{trainingSession}', [AdminTrainingSessionController::class, 'show'])
    ->name('admin-vue.sessions.show');

Route::middleware(['auth', 'active.account', 'verified', 'role:administrator'])
    ->prefix('admin-vue/sessions/{trainingSession}')
    ->name('admin-vue.sessions.')
    ->group(function (): void {
        Route::post('/publish', [AdminTrainingSessionController::class, 'publish'])->name('publish');
        Route::post('/close-registration', [AdminTrainingSessionController::class, 'closeRegistration'])->name('close-registration');
        Route::post('/complete', [AdminTrainingSessionController::class, 'markCompleted'])->name('complete');
        Route::post('/cancel', [AdminTrainingSessionController::class, 'cancel'])->name('cancel');
        Route::post('/notify', [AdminTrainingSessionController::class, 'notifyParticipants'])->name('notify');
        Route::post('/reconcile-seats', [AdminTrainingSessionController::class, 'reconcileSeats'])->name('reconcile-seats');
    });

Route::middleware(['auth', 'active.account', 'verified', 'role:administrator'])
    ->group(function (): void {
        Route::post('/admin-vue/recordings', [AdminRecordingController::class, 'store'])->name('admin-vue.recordings.store');
    });

Route::middleware(['auth', 'active.account', 'verified', 'role:administrator'])
    ->prefix('admin-vue/recordings/{recording}')
    ->name('admin-vue.recordings.')
    ->group(function (): void {
        Route::post('/approve', [AdminRecordingController::class, 'approve'])->name('approve');
        Route::post('/unpublish', [AdminRecordingController::class, 'unpublish'])->name('unpublish');
        Route::post('/reject', [AdminRecordingController::class, 'reject'])->name('reject');
        Route::post('/transcode', [AdminRecordingController::class, 'transcode'])->name('transcode');
    });

Route::middleware(['auth', 'active.account', 'verified', 'role:administrator'])
    ->prefix('admin-vue/attendance/{attendance}')
    ->name('admin-vue.attendance.')
    ->group(function (): void {
        Route::post('/verify', [AdminAttendanceController::class, 'verify'])->name('verify');
        Route::post('/request-correction', [AdminAttendanceController::class, 'requestCorrection'])->name('request-correction');
    });

Route::middleware(['auth', 'active.account', 'verified', 'role:administrator'])
    ->get('/admin-vue/bookings/{booking}', [AdminBookingController::class, 'show'])
    ->name('admin-vue.bookings.show');

Route::middleware(['auth', 'active.account', 'verified', 'role:administrator'])
    ->post('/admin-vue/bookings/{booking}/cancel', [AdminBookingController::class, 'cancel'])
    ->name('admin-vue.bookings.cancel');

Route::middleware(['auth', 'active.account', 'verified', 'role:administrator'])
    ->get('/admin-vue/payments', [AdminFinanceController::class, 'payments'])
    ->name('admin-vue.payments.index');

Route::middleware(['auth', 'active.account', 'verified', 'role:administrator'])
    ->prefix('admin-vue/payments/{payment}')
    ->name('admin-vue.payments.')
    ->group(function (): void {
        Route::post('/verify', [AdminFinanceController::class, 'verifyPayment'])->name('verify');
        Route::post('/refund', [AdminFinanceController::class, 'recordRefund'])->name('refund');
    });

Route::middleware(['auth', 'active.account', 'verified', 'role:administrator'])
    ->get('/admin-vue/refunds', [AdminFinanceController::class, 'refunds'])
    ->name('admin-vue.refunds.index');

Route::middleware(['auth', 'active.account', 'verified', 'role:administrator'])
    ->prefix('admin-vue/refunds/{refund}')
    ->name('admin-vue.refunds.')
    ->group(function (): void {
        Route::post('/complete', [AdminFinanceController::class, 'completeRefund'])->name('complete');
        Route::post('/reject', [AdminFinanceController::class, 'rejectRefund'])->name('reject');
    });

Route::middleware(['auth', 'active.account', 'verified', 'role:administrator'])
    ->group(function (): void {
        Route::get('/admin-vue/users', [AdminOperationsController::class, 'users'])->name('admin-vue.users.index');
        Route::post('/admin-vue/users', [AdminOperationsController::class, 'storeUser'])->name('admin-vue.users.store');
        Route::put('/admin-vue/users/{user}', [AdminOperationsController::class, 'updateUser'])->name('admin-vue.users.update');
        Route::delete('/admin-vue/users/{user}', [AdminOperationsController::class, 'destroyUser'])->name('admin-vue.users.destroy');
        Route::post('/admin-vue/users/{user}/status', [AdminOperationsController::class, 'changeUserStatus'])->name('admin-vue.users.status');
        Route::get('/admin-vue/certificates', [AdminOperationsController::class, 'certificates'])->name('admin-vue.certificates.index');
        Route::get('/admin-vue/certificate-settings', [AdminOperationsController::class, 'certificateSettings'])->name('admin-vue.certificate-settings.edit');
        Route::post('/admin-vue/certificate-settings', [AdminOperationsController::class, 'updateCertificateSettings'])->name('admin-vue.certificate-settings.update');
        Route::post('/admin-vue/certificates/issue', [AdminOperationsController::class, 'issueCertificate'])->name('admin-vue.certificates.issue');
        Route::post('/admin-vue/certificates/{certificate}/revoke', [AdminOperationsController::class, 'revokeCertificate'])->name('admin-vue.certificates.revoke');
        Route::get('/admin-vue/reports', [AdminOperationsController::class, 'reports'])->name('admin-vue.reports.index');
    });

Route::middleware(['auth', 'active.account', 'verified', 'role:administrator'])
    ->group(function (): void {
        Route::get('/admin-vue/orders', [AdminCommerceGovernanceController::class, 'orders'])->name('admin-vue.orders.index');
        Route::get('/admin-vue/orders/{order:order_number}', [AdminCommerceGovernanceController::class, 'showOrder'])->name('admin-vue.orders.show');

        Route::get('/admin-vue/announcements', [AdminContentController::class, 'announcements'])->name('admin-vue.announcements.index');
        Route::post('/admin-vue/announcements', [AdminContentController::class, 'storeAnnouncement'])->name('admin-vue.announcements.store');
        Route::post('/admin-vue/announcements/{announcement}', [AdminContentController::class, 'updateAnnouncement'])->name('admin-vue.announcements.update');
        Route::post('/admin-vue/announcements/{announcement}/send', [AdminContentController::class, 'sendAnnouncement'])->name('admin-vue.announcements.send');
        Route::delete('/admin-vue/announcements/{announcement}', [AdminContentController::class, 'destroyAnnouncement'])->name('admin-vue.announcements.destroy');

        Route::get('/admin-vue/instructor-profiles', [AdminContentController::class, 'instructorProfiles'])->name('admin-vue.instructor-profiles.index');
        Route::post('/admin-vue/instructor-profiles', [AdminContentController::class, 'storeInstructorProfile'])->name('admin-vue.instructor-profiles.store');
        Route::post('/admin-vue/instructor-profiles/{profile}', [AdminContentController::class, 'updateInstructorProfile'])->name('admin-vue.instructor-profiles.update');
        Route::post('/admin-vue/instructor-profiles/{profile}/toggle', [AdminContentController::class, 'toggleInstructorProfile'])->name('admin-vue.instructor-profiles.toggle');
        Route::delete('/admin-vue/instructor-profiles/{profile}', [AdminContentController::class, 'destroyInstructorProfile'])->name('admin-vue.instructor-profiles.destroy');
    });
