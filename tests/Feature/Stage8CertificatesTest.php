<?php

namespace Tests\Feature;

use App\Enums\CertificateStatus;
use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Enums\EntitlementStatus;
use App\Enums\RoleSlug;
use App\Models\Certificate;
use App\Models\Attendance;
use App\Models\Module;
use App\Models\ModuleEntitlement;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Certificate\CertificateService;
use Database\Seeders\CatalogueSeeder;
use Database\Seeders\CertificateSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\RoleAndPermissionSeeder;
use Database\Seeders\SessionAndBookingSeeder;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class Stage8CertificatesTest extends TestCase
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

    public function test_administrator_can_issue_pdf_certificate_for_session_and_module_with_configurable_global_signatory(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $session1 = TrainingSession::query()->where('session_code', 'SES-2026-003')->firstOrFail();
        $module101 = Module::query()->where('code', 'EHT-HYD-101')->firstOrFail();

        $certificateService = app(CertificateService::class);
        $seededCertificate = Certificate::query()
            ->where('user_id', $trainee->id)
            ->where('training_session_id', $session1->id)
            ->firstOrFail();
        $certificateService->revokeCertificate($seededCertificate, $admin, 'Reissued during certificate lifecycle test.');
        $session1->update([
            'status' => \App\Enums\SessionStatus::Completed,
            'completed_at' => now()->subDay(),
            'completed_by' => $admin->id,
        ]);
        Attendance::query()
            ->where('training_session_id', $session1->id)
            ->where('user_id', $trainee->id)
            ->update([
                'attendance_status' => AttendanceStatus::Present,
                'review_status' => AttendanceReviewStatus::Verified,
                'recorded_by' => $admin->id,
                'recorded_at' => now()->subDay(),
                'submitted_by' => $admin->id,
                'submitted_at' => now()->subDay(),
                'verified_by' => $admin->id,
                'verified_at' => now()->subDay(),
            ]);
        ModuleEntitlement::query()->updateOrCreate(
            [
                'user_id' => $trainee->id,
                'module_id' => $module101->id,
            ],
            [
                'grant_source' => 'admin_grant',
                'status' => EntitlementStatus::Active,
                'granted_at' => now(),
                'granted_by' => $admin->id,
                'grant_reason' => 'Certificate lifecycle test entitlement.',
            ],
        );

        // Configure custom global signatory details in system_settings
        $certificateService->updateGlobalSettings(
            admin: $admin,
            issuingOrganisation: 'Ecohydrotech Academy Malaysia',
            signatoryName: 'Prof. Ir. Dr. Zainal Abidin',
            signatoryTitle: 'Chief Academic & Technical Director',
        );

        // 1. Issue certificate for a Physical Training Session
        $sessionCert = $certificateService->issueCertificate(
            admin: $admin,
            trainee: $trainee,
            recordType: 'session',
            recordId: $session1->id,
            completionDate: '2026-11-15',
            notes: 'Completed all practical MSMA design workshop exercises.',
        );

        $this->assertSame(CertificateStatus::Issued, $sessionCert->status);
        $this->assertSame('session', $sessionCert->record_type);
        $this->assertSame($session1->id, $sessionCert->training_session_id);
        $this->assertSame('Ecohydrotech Academy Malaysia', $sessionCert->issuing_organisation_snapshot);
        $this->assertSame('Prof. Ir. Dr. Zainal Abidin', $sessionCert->signatory_name_snapshot);
        $this->assertSame('Chief Academic & Technical Director', $sessionCert->signatory_title_snapshot);
        $this->assertSame('Ahmad Faiz', $sessionCert->trainee_name_snapshot);
        $this->assertNotEmpty($sessionCert->pdf_storage_key);

        // Verify authentic PDF binary stored securely on private certificates server disk
        $this->assertTrue(Storage::disk('certificates')->exists($sessionCert->pdf_storage_key));
        $pdfContents = Storage::disk('certificates')->get($sessionCert->pdf_storage_key);
        $this->assertStringStartsWith('%PDF-', (string) $pdfContents);

        // 2. Issue certificate for an Independent Training Module
        $moduleCert = $certificateService->issueCertificate(
            admin: $admin,
            trainee: $trainee,
            recordType: 'module',
            recordId: $module101->id,
            completionDate: '2026-11-18',
        );

        $this->assertSame(CertificateStatus::Issued, $moduleCert->status);
        $this->assertSame('module', $moduleCert->record_type);
        $this->assertNull($moduleCert->training_session_id);
        $this->assertSame($module101->id, $moduleCert->module_id);
        $this->assertTrue(Storage::disk('certificates')->exists($moduleCert->pdf_storage_key));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'certificate.issued',
            'auditable_type' => Certificate::class,
            'auditable_id' => $moduleCert->id,
            'actor_id' => $admin->id,
        ]);
    }

    public function test_duplicate_certificates_for_same_trainee_and_training_record_are_prevented_by_service_and_mysql(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $session3 = TrainingSession::query()->where('session_code', 'SES-2026-003')->firstOrFail();

        // Seed already created 1 active certificate for trainee + SES-2026-003
        $certificateService = app(CertificateService::class);

        $threwDomainException = false;
        try {
            $certificateService->issueCertificate(
                admin: $admin,
                trainee: $trainee,
                recordType: 'session',
                recordId: $session3->id,
                completionDate: now()->toDateString(),
            );
        } catch (DomainException) {
            $threwDomainException = true;
        }
        $this->assertTrue($threwDomainException, 'Service must block duplicate active certificates.');

        // Also confirm MySQL 8.4 unique generated index uq_certificates_active_user_record rejects raw duplicate insert
        $this->expectException(QueryException::class);

        DB::table('certificates')->insert([
            'certificate_number' => 'EHT-2026-DUP999',
            'verification_token' => bin2hex(random_bytes(32)),
            'user_id' => $trainee->id,
            'record_type' => 'session',
            'training_session_id' => $session3->id,
            'module_id' => $session3->module_id,
            'status' => 'issued',
            'trainee_name_snapshot' => $trainee->name,
            'training_code_snapshot' => $session3->session_code,
            'training_title_snapshot' => $session3->title,
            'completion_date' => now()->toDateString(),
            'issued_at' => now(),
            'issuing_organisation_snapshot' => 'Ecohydrotech Solution',
            'signatory_name_snapshot' => 'Director',
            'signatory_title_snapshot' => 'Director',
            'pdf_storage_disk' => 'certificates',
            'issued_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_trainee_dashboard_and_certificate_portal_allow_viewing_and_downloading_issued_certificates(): void
    {
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $otherTrainee = User::factory()->create(['email_verified_at' => now()]);
        $otherTrainee->assignRole(RoleSlug::Customer);

        $certificate = Certificate::query()->where('user_id', $trainee->id)->firstOrFail();

        // Dashboard exposes issued certificate with ref, training title, and download URL
        $this->actingAs($trainee)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Dashboard')
                ->has('certificates', 1)
                ->where('certificates.0.certificateNumber', $certificate->certificate_number)
                ->where('certificates.0.title', $certificate->training_title_snapshot)
                ->where('certificates.0.downloadUrl', route('certificates.download', $certificate)),
            );

        // /portal/certificates listing & detail preview
        $this->actingAs($trainee)
            ->get(route('portal.certificates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Certificates/Index')
                ->has('issuedCertificates', 1)
                ->where('issuedCertificates.0.certificateNumber', $certificate->certificate_number)
                ->where('issuedCertificates.0.isIssued', true),
            );

        $this->actingAs($trainee)
            ->get(route('portal.certificates.show', $certificate))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Certificates/Show')
                ->where('certificate.certificateNumber', $certificate->certificate_number)
                ->where('certificate.isIssued', true)
                ->where('certificate.signatoryName', $certificate->signatory_name_snapshot)
                ->where('certificate.downloadUrl', route('certificates.download', $certificate)),
            );

        // Download PDF streams cleanly and writes audit log
        $this->actingAs($trainee)
            ->get(route('certificates.download', $certificate))
            ->assertOk()
            ->assertDownload("{$certificate->certificate_number}.pdf");

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'certificate.downloaded',
            'auditable_type' => Certificate::class,
            'auditable_id' => $certificate->id,
            'actor_id' => $trainee->id,
        ]);

        // Signed temporary download URL works while valid, rejects when expired
        $validSigned = $certificate->temporaryDownloadUrl(15);
        $this->actingAs($trainee)->get($validSigned)->assertOk();

        $expiredSigned = URL::temporarySignedRoute(
            'certificates.download',
            now()->subMinutes(5),
            ['certificate' => $certificate->certificate_number],
        );
        $this->actingAs($trainee)->get($expiredSigned)->assertForbidden();

        // Another trainee cannot view or download someone else's certificate
        $this->actingAs($otherTrainee)
            ->get(route('portal.certificates.show', $certificate))
            ->assertForbidden();

        $this->actingAs($otherTrainee)
            ->get(route('certificates.download', $certificate))
            ->assertForbidden();
    }

    public function test_revoked_certificate_blocks_trainee_download_requires_reason_and_maintains_history(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $certificate = Certificate::query()->where('user_id', $trainee->id)->firstOrFail();

        $certificateService = app(CertificateService::class);

        // Empty revocation reason must be rejected
        $threwEmptyReason = false;
        try {
            $certificateService->revokeCertificate($certificate, $admin, '   ');
        } catch (DomainException) {
            $threwEmptyReason = true;
        }
        $this->assertTrue($threwEmptyReason, 'Revocation requires a non-empty reason.');

        // Revoke with mandatory reason
        $revoked = $certificateService->revokeCertificate(
            certificate: $certificate,
            admin: $admin,
            reason: 'Issued in error prior to final project submission verification.',
        );

        $this->assertSame(CertificateStatus::Revoked, $revoked->status);
        $this->assertSame('Issued in error prior to final project submission verification.', $revoked->revocation_reason);

        // Revoked certificate CANNOT be downloaded by trainee (403 Forbidden)
        $this->actingAs($trainee)
            ->get(route('certificates.download', $revoked))
            ->assertForbidden();

        // Detail page clearly displays revoked status without Download link
        $this->actingAs($trainee)
            ->get(route('portal.certificates.show', $revoked))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Certificates/Show')
                ->where('certificate.isIssued', false)
                ->where('certificate.isRevoked', true)
                ->where('certificate.revocationReason', 'Issued in error prior to final project submission verification.')
                ->where('certificate.downloadUrl', null),
            )
            ->assertDontSee('Download Official PDF');

        // Issuance & revocation history maintained in audit_logs
        $history = $revoked->historyLogs();
        $this->assertTrue($history->contains('event', 'certificate.issued'));
        $this->assertTrue($history->contains('event', 'certificate.revoked'));
    }

    public function test_vue_admin_certificate_workspace_and_mysql_revocation_check_constraint(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->firstOrFail();
        $customer = User::query()->where('email', 'trainee@ecohydrotech.com.my')->firstOrFail();
        $certificate = Certificate::query()->where('user_id', $customer->id)->firstOrFail();

        $this->actingAs($customer)->get(route('admin-vue.certificates.index'))->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin-vue.certificates.index'))
            ->assertOk()
            ->assertSee($certificate->certificate_number);

        // Admin CAN download stored PDF from Filament/controller for inspection
        $this->actingAs($admin)
            ->get(route('certificates.download', $certificate))
            ->assertOk();

        // MySQL 8.4 CHECK constraint chk_certificates_revoked must block setting status='revoked' without revocation_reason
        $this->expectException(QueryException::class);

        DB::table('certificates')
            ->where('id', $certificate->id)
            ->update([
                'status' => 'revoked',
                'revoked_by' => null,
                'revoked_at' => null,
                'revocation_reason' => null,
            ]);
    }
}
