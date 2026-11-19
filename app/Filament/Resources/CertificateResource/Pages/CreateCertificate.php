<?php

namespace App\Filament\Resources\CertificateResource\Pages;

use App\Filament\Resources\CertificateResource;
use App\Models\User;
use App\Services\Certificate\CertificateService;
use DomainException;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateCertificate extends CreateRecord
{
    protected static string $resource = CertificateResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        /** @var User $admin */
        $admin = Auth::user();
        /** @var User $trainee */
        $trainee = User::query()->findOrFail((int) $data['user_id']);

        $recordType = (string) $data['record_type'];
        $recordId = $recordType === 'session'
            ? (int) $data['training_session_id']
            : (int) $data['module_id'];

        try {
            return app(CertificateService::class)->issueCertificate(
                admin: $admin,
                trainee: $trainee,
                recordType: $recordType,
                recordId: $recordId,
                completionDate: (string) $data['completion_date'],
                notes: $data['notes'] ?? null,
            );
        } catch (DomainException $e) {
            Notification::make()
                ->title('Cannot Issue Certificate')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();

            throw $e;
        }
    }
}
