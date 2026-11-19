<?php

namespace App\Filament\Resources\CertificateResource\Pages;

use App\Enums\CertificateStatus;
use App\Filament\Resources\CertificateResource;
use App\Models\Certificate;
use App\Models\User;
use App\Services\Certificate\CertificateService;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;

class ViewCertificate extends ViewRecord
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdf')
                ->label('Download PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->url(fn (): string => route('certificates.download', $this->getRecord())),
            Action::make('revokeCertificate')
                ->label('Revoke Certificate')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->getRecord()->status === CertificateStatus::Issued)
                ->schema([
                    Textarea::make('revocation_reason')
                        ->label('Mandatory Revocation Reason')
                        ->required()
                        ->maxLength(500),
                ])
                ->action(function (array $data, CertificateService $certificateService): void {
                    /** @var Certificate $record */
                    $record = $this->getRecord();
                    /** @var User $admin */
                    $admin = Auth::user();

                    try {
                        $certificateService->revokeCertificate($record, $admin, (string) $data['revocation_reason']);
                        Notification::make()->title('Certificate revoked and logged')->warning()->send();
                        $this->refreshFormData(['status', 'revoked_at', 'revocation_reason']);
                    } catch (DomainException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
