<?php

namespace App\Filament\Resources;

use App\Enums\EntitlementStatus;
use App\Enums\RoleSlug;
use App\Filament\Resources\ModuleEntitlementResource\Pages\CreateModuleEntitlement;
use App\Filament\Resources\ModuleEntitlementResource\Pages\ListModuleEntitlements;
use App\Models\AuditLog;
use App\Models\Module;
use App\Models\ModuleEntitlement;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ModuleEntitlementResource extends Resource
{
    protected static ?string $model = ModuleEntitlement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|UnitEnum|null $navigationGroup = 'Commerce & Finance';

    protected static ?string $navigationLabel = 'Module Entitlements';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Manual Administrator Module Entitlement Grant (ADM-16, FR-07)')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Customer / Trainee')
                            ->options(fn (): array => User::query()
                                ->whereHas('roles', fn ($q) => $q->where('slug', RoleSlug::Customer->value))
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (User $u) => [$u->id => "{$u->name} ({$u->email})"])
                                ->all())
                            ->searchable()
                            ->required(),
                        Select::make('module_id')
                            ->label('Training Module')
                            ->options(fn (): array => Module::query()->orderBy('code')->get()->mapWithKeys(fn (Module $m) => [$m->id => "{$m->code} — {$m->title}"])->all())
                            ->searchable()
                            ->required(),
                        Textarea::make('grant_reason')
                            ->label('Mandatory Reason for Manual Entitlement Grant')
                            ->required()
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('granted_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->description(fn (ModuleEntitlement $record): string => $record->user?->email ?? '')
                    ->searchable(),
                TextColumn::make('module.code')
                    ->label('Module')
                    ->description(fn (ModuleEntitlement $record): string => $record->module?->title ?? '')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('grant_source')
                    ->label('Source')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'admin_grant' ? 'Admin Grant' : 'Purchased')
                    ->color(fn (string $state): string => $state === 'admin_grant' ? 'info' : 'primary'),
                TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->fontFamily('mono')
                    ->placeholder('Manual Grant'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (EntitlementStatus|string $state): string => $state instanceof EntitlementStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn (EntitlementStatus|string $state): string => ($state instanceof EntitlementStatus ? $state : EntitlementStatus::tryFrom((string) $state))?->badgeColor() ?? 'gray'),
                TextColumn::make('granted_at')
                    ->label('Granted (MYT)')
                    ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                    ->dateTime('d M Y, h:i A'),
            ])
            ->filters([
                SelectFilter::make('module_id')
                    ->label('Module')
                    ->relationship('module', 'title'),
                SelectFilter::make('status')
                    ->options(collect(EntitlementStatus::cases())->mapWithKeys(fn (EntitlementStatus $s) => [$s->value => $s->label()])),
            ])
            ->recordActions([
                Action::make('revokeEntitlement')
                    ->label('Revoke Access')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (ModuleEntitlement $record): bool => $record->status === EntitlementStatus::Active)
                    ->schema([
                        Textarea::make('revocation_reason')
                            ->label('Mandatory Revocation Reason')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (ModuleEntitlement $record, array $data): void {
                        $record->update([
                            'status' => EntitlementStatus::Revoked,
                            'revoked_at' => now(),
                            'revoked_by' => Auth::id(),
                            'revocation_reason' => (string) $data['revocation_reason'],
                        ]);

                        AuditLog::record(
                            event: 'module_entitlement.revoked',
                            auditableType: ModuleEntitlement::class,
                            auditableId: $record->id,
                            oldValues: ['status' => EntitlementStatus::Active->value],
                            newValues: ['status' => EntitlementStatus::Revoked->value],
                            reason: (string) $data['revocation_reason'],
                        );

                        Notification::make()->title('Module entitlement revoked')->warning()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModuleEntitlements::route('/'),
            'create' => CreateModuleEntitlement::route('/create'),
        ];
    }
}
