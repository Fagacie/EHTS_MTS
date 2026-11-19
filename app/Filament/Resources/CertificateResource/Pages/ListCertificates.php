<?php

namespace App\Filament\Resources\CertificateResource\Pages;

use App\Filament\Resources\CertificateResource;
use App\Models\User;
use App\Services\Certificate\CertificateService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListCertificates extends ListRecords
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('configureSignatory')
                ->label('Configure Global Signatory')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('gray')
                ->schema(function (CertificateService $certificateService): array {
                    $current = $certificateService->globalSettings();

                    return [
                        TextInput::make('issuing_organisation')
                            ->label('Organisation Name')
                            ->default($current['issuing_organisation'])
                            ->required()
                            ->maxLength(150),
                        TextInput::make('signatory_name')
                            ->label('Authorised Signatory Name')
                            ->default($current['signatory_name'])
                            ->required()
                            ->maxLength(150),
                        TextInput::make('signatory_title')
                            ->label('Authorised Signatory Designation')
                            ->default($current['signatory_title'])
                            ->required()
                            ->maxLength(150),
                    ];
                })
                ->action(function (array $data, CertificateService $certificateService): void {
                    /** @var User $admin */
                    $admin = Auth::user();
                    $certificateService->updateGlobalSettings(
                        admin: $admin,
                        issuingOrganisation: (string) $data['issuing_organisation'],
                        signatoryName: (string) $data['signatory_name'],
                        signatoryTitle: (string) $data['signatory_title'],
                    );

                    Notification::make()
                        ->title('Global certificate signatory settings updated')
                        ->success()
                        ->send();
                }),
            CreateAction::make()->label('Issue Certificate'),
        ];
    }
}
