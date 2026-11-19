<?php

namespace App\Filament\Resources;

use App\Enums\CertificateStatus;
use App\Enums\ModuleStatus;
use App\Enums\RoleSlug;
use App\Enums\SessionStatus;
use App\Filament\Resources\CertificateResource\Pages\CreateCertificate;
use App\Filament\Resources\CertificateResource\Pages\ListCertificates;
use App\Filament\Resources\CertificateResource\Pages\ViewCertificate;
use App\Models\Booking;
use App\Models\Certificate;
use App\Models\Module;
use App\Models\ModuleEntitlement;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Certificate\CertificateService;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class CertificateResource extends Resource
{
    protected static ?string $model = Certificate::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|UnitEnum|null $navigationGroup = 'Physical Sessions & Delivery';

    protected static ?string $navigationLabel = 'Certificates';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'certificate_number';

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        $settings = app(CertificateService::class)->globalSettings();

        return $schema
            ->components([
                Section::make('Select Trainee & Completed Training Record')
                    ->description('Issue an official PDF certificate for a trainee who completed a physical session or training module. Active duplicate certificates for the same trainee and training record are automatically blocked.')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Trainee')
                            ->options(fn (): array => User::query()
                                ->where('status', 'active')
                                ->whereHas('roles', fn ($q) => $q->where('slug', RoleSlug::Customer->value))
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (User $u) => [$u->id => "{$u->name} ({$u->email})"])
                                ->all())
                            ->searchable()
                            ->live()
                            ->required(),

                        Select::make('record_type')
                            ->label('Training Record Type')
                            ->options([
                                'session' => 'Physical Training Session',
                                'module' => 'Training Module',
                            ])
                            ->default('session')
                            ->live()
                            ->required(),

                        Select::make('training_session_id')
                            ->label('Completed Physical Training Session')
                            ->options(function (Get $get): array {
                                $userId = $get('user_id');

                                return TrainingSession::query()
                                    ->where('status', '!=', SessionStatus::Cancelled->value)
                                    ->orderByDesc('starts_at')
                                    ->get()
                                    ->mapWithKeys(function (TrainingSession $session) use ($userId): array {
                                        $suffix = '';
                                        if ($userId) {
                                            $hasConfirmed = Booking::query()
                                                ->where('user_id', (int) $userId)
                                                ->where('training_session_id', $session->id)
                                                ->where('status', 'confirmed')
                                                ->exists();
                                            $suffix = $hasConfirmed ? ' [Confirmed Booking]' : '';
                                        }

                                        return [$session->id => "{$session->session_code} — {$session->title}{$suffix}"];
                                    })
                                    ->all();
                            })
                            ->searchable()
                            ->visible(fn (Get $get): bool => $get('record_type') === 'session')
                            ->required(fn (Get $get): bool => $get('record_type') === 'session'),

                        Select::make('module_id')
                            ->label('Completed Training Module')
                            ->options(function (Get $get): array {
                                $userId = $get('user_id');

                                return Module::query()
                                    ->where('status', '!=', ModuleStatus::Archived->value)
                                    ->orderBy('code')
                                    ->get()
                                    ->mapWithKeys(function (Module $module) use ($userId): array {
                                        $suffix = '';
                                        if ($userId) {
                                            $hasAccess = ModuleEntitlement::query()
                                                ->where('user_id', (int) $userId)
                                                ->where('module_id', $module->id)
                                                ->where('status', 'active')
                                                ->exists();
                                            $suffix = $hasAccess ? ' [Active Entitlement]' : '';
                                        }

                                        return [$module->id => "{$module->code} — {$module->title}{$suffix}"];
                                    })
                                    ->all();
                            })
                            ->searchable()
                            ->visible(fn (Get $get): bool => $get('record_type') === 'module')
                            ->required(fn (Get $get): bool => $get('record_type') === 'module'),

                        DatePicker::make('completion_date')
                            ->label('Completion Date')
                            ->default(now()->toDateString())
                            ->required(),

                        Textarea::make('notes')
                            ->label('Issuance Notes (Optional)')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),

                Section::make('Global Organisation & Signatory Snapshot Preview')
                    ->description("Will be issued under: {$settings['issuing_organisation']} · Authorised Signatory: {$settings['signatory_name']} ({$settings['signatory_title']}). Update anytime via Configure Signatory on the Certificates list.")
                    ->schema([]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Certificate Details & Official Snapshots')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('certificate_number')
                            ->label('Certificate Reference')
                            ->fontFamily('mono')
                            ->weight('bold'),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (CertificateStatus|string $state): string => $state instanceof CertificateStatus ? $state->label() : ucfirst((string) $state))
                            ->color(fn (CertificateStatus|string $state): string => ($state instanceof CertificateStatus ? $state : CertificateStatus::tryFrom((string) $state))?->badgeColor() ?? 'gray'),
                        TextEntry::make('record_type')
                            ->label('Record Type')
                            ->formatStateUsing(fn (string $state): string => $state === 'session' ? 'Physical Training Session' : 'Training Module'),
                        TextEntry::make('trainee_name_snapshot')
                            ->label('Trainee Full Name'),
                        TextEntry::make('training_code_snapshot')
                            ->label('Training Code')
                            ->fontFamily('mono'),
                        TextEntry::make('training_title_snapshot')
                            ->label('Training / Module Title'),
                        TextEntry::make('completion_date')
                            ->label('Completion Date')
                            ->date('d M Y'),
                        TextEntry::make('issued_at')
                            ->label('Issue Date (MYT)')
                            ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                            ->dateTime('d M Y, h:i A'),
                        TextEntry::make('issuer.name')
                            ->label('Issued By'),
                    ]),

                Section::make('Organisation, Signatory & Secure PDF Storage')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('issuing_organisation_snapshot')
                            ->label('Organisation'),
                        TextEntry::make('signatory_name_snapshot')
                            ->label('Signatory Name'),
                        TextEntry::make('signatory_title_snapshot')
                            ->label('Signatory Designation'),
                        TextEntry::make('pdf_storage_disk')
                            ->label('Storage Disk')
                            ->fontFamily('mono'),
                        TextEntry::make('pdf_storage_key')
                            ->label('Private R2 Key')
                            ->fontFamily('mono'),
                        TextEntry::make('pdf_file_size_bytes')
                            ->label('PDF Size')
                            ->formatStateUsing(fn (Certificate $record): string => $record->formattedFileSize()),
                    ]),

                Section::make('Revocation Record')
                    ->columns(3)
                    ->visible(fn (Certificate $record): bool => $record->isRevoked())
                    ->schema([
                        TextEntry::make('revoked_at')
                            ->label('Revoked At (MYT)')
                            ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                            ->dateTime('d M Y, h:i A'),
                        TextEntry::make('revoker.name')
                            ->label('Revoked By'),
                        TextEntry::make('revocation_reason')
                            ->label('Mandatory Revocation Reason')
                            ->columnSpanFull(),
                    ]),

                Section::make('Certificate Issuance & Revocation History')
                    ->schema([
                        ViewEntry::make('audit_history')
                            ->view('filament.certificates.history'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('issued_at', 'desc')
            ->columns([
                TextColumn::make('certificate_number')
                    ->label('Certificate Ref')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('trainee_name_snapshot')
                    ->label('Trainee')
                    ->description(fn (Certificate $record): string => $record->user?->email ?? '')
                    ->searchable(['trainee_name_snapshot']),
                TextColumn::make('record_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'session' ? 'Session' : 'Module')
                    ->color(fn (string $state): string => $state === 'session' ? 'info' : 'primary'),
                TextColumn::make('training_title_snapshot')
                    ->label('Training / Module')
                    ->description(fn (Certificate $record): string => $record->training_code_snapshot ?? '')
                    ->limit(40)
                    ->searchable(),
                TextColumn::make('completion_date')
                    ->label('Completed')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('issued_at')
                    ->label('Issued (MYT)')
                    ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (CertificateStatus|string $state): string => $state instanceof CertificateStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn (CertificateStatus|string $state): string => ($state instanceof CertificateStatus ? $state : CertificateStatus::tryFrom((string) $state))?->badgeColor() ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(CertificateStatus::cases())->mapWithKeys(fn (CertificateStatus $s) => [$s->value => $s->label()])),
                SelectFilter::make('record_type')
                    ->label('Training Record Type')
                    ->options([
                        'session' => 'Physical Training Session',
                        'module' => 'Training Module',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('downloadPdf')
                    ->label('Download PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->url(fn (Certificate $record): string => route('certificates.download', $record)),
                Action::make('revokeCertificate')
                    ->label('Revoke')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Certificate $record): bool => $record->status === CertificateStatus::Issued)
                    ->schema([
                        Textarea::make('revocation_reason')
                            ->label('Mandatory Revocation Reason')
                            ->helperText('Revoked certificates become inactive immediately and cannot be downloaded by trainees.')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (Certificate $record, array $data, CertificateService $certificateService): void {
                        /** @var User $admin */
                        $admin = Auth::user();
                        try {
                            $certificateService->revokeCertificate($record, $admin, (string) $data['revocation_reason']);
                            Notification::make()->title('Certificate revoked and logged')->warning()->send();
                        } catch (DomainException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCertificates::route('/'),
            'create' => CreateCertificate::route('/create'),
            'view' => ViewCertificate::route('/{record}'),
        ];
    }
}
