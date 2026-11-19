<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\SystemSetting;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Certificate\CertificateService;
use Illuminate\Database\Seeder;

class CertificateSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@ecohydrotech.com.my')->first();
        $trainee = User::query()->where('email', 'trainee@ecohydrotech.com.my')->first();

        if (! $admin) {
            return;
        }

        // Seed default global certificate signatory & organisation settings (D-027)
        SystemSetting::setValue(
            key: 'certificates.issuing_organisation',
            value: CertificateService::DEFAULT_ORGANISATION,
            group: 'certificates',
            type: 'string',
            description: 'Organisation name printed on issued training certificates (D-027).',
            isPublic: true,
            updatedBy: $admin->id,
        );

        SystemSetting::setValue(
            key: 'certificates.signatory_name',
            value: CertificateService::DEFAULT_SIGNATORY_NAME,
            group: 'certificates',
            type: 'string',
            description: 'Authorised signatory full name printed on issued certificates (D-027).',
            isPublic: false,
            updatedBy: $admin->id,
        );

        SystemSetting::setValue(
            key: 'certificates.signatory_title',
            value: CertificateService::DEFAULT_SIGNATORY_TITLE,
            group: 'certificates',
            type: 'string',
            description: 'Authorised signatory designation printed on issued certificates (D-027).',
            isPublic: false,
            updatedBy: $admin->id,
        );

        SystemSetting::setValue(
            key: 'certificates.logo_path',
            value: CertificateService::DEFAULT_LOGO_PATH,
            group: 'certificates',
            type: 'string',
            description: 'Logo asset path rendered on official PDF certificates.',
            isPublic: false,
            updatedBy: $admin->id,
        );

        if (! $trainee) {
            return;
        }

        $session3 = TrainingSession::query()->where('session_code', 'SES-2026-003')->first();
        if ($session3) {
            $alreadyExists = Certificate::query()
                ->where('user_id', $trainee->id)
                ->where('record_type', 'session')
                ->where('training_session_id', $session3->id)
                ->exists();

            if (! $alreadyExists) {
                app(CertificateService::class)->issueCertificate(
                    admin: $admin,
                    trainee: $trainee,
                    recordType: 'session',
                    recordId: $session3->id,
                    completionDate: now()->subDay()->toDateString(),
                    notes: 'Seeded demo completion certificate.',
                );
            }
        }
    }
}
