<?php

namespace App\Filament\Resources;

use App\Enums\CustomerType;
use App\Enums\UserStatus;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Services\Notification\EtmsNotificationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|UnitEnum|null $navigationGroup = 'Users & Access';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account Identity')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(150),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                            ->minLength(8)
                            ->helperText('Leave blank while editing to retain the existing password.'),
                        Select::make('status')
                            ->options(collect(UserStatus::cases())->mapWithKeys(fn (UserStatus $s) => [$s->value => $s->label()]))
                            ->default(UserStatus::Active->value)
                            ->required(),
                    ]),

                Section::make('Role Assignments (D-004 & D-026)')
                    ->description('Assign one or more approved roles. Administrators or Instructors may also hold the Trainee / Customer role when purchasing training.')
                    ->schema([
                        CheckboxList::make('role_slugs')
                            ->label('Roles')
                            ->options(fn (): array => Role::query()->orderBy('id')->pluck('name', 'slug')->all())
                            ->required()
                            ->minItems(1)
                            ->columns(3)
                            ->afterStateHydrated(function (CheckboxList $component, ?User $record): void {
                                if ($record !== null) {
                                    $component->state($record->roles()->pluck('slug')->all());
                                }
                            })
                            ->dehydrated(false),
                    ]),

                Section::make('Customer Profile & Company Billing (D-001, D-002, D-028)')
                    ->description('Contact information and optional company billing details used for orders and receipts.')
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        Select::make('profile_customer_type')
                            ->label('Customer Type')
                            ->options(collect(CustomerType::cases())->mapWithKeys(fn (CustomerType $t) => [$t->value => $t->label()]))
                            ->default(CustomerType::Individual->value)
                            ->dehydrated(false),
                        TextInput::make('profile_phone')
                            ->label('Contact Phone')
                            ->maxLength(30)
                            ->dehydrated(false),
                        TextInput::make('profile_job_title')
                            ->label('Job Title / Designation')
                            ->maxLength(120)
                            ->dehydrated(false),
                        TextInput::make('profile_company_name')
                            ->label('Company Name')
                            ->maxLength(200)
                            ->dehydrated(false),
                        TextInput::make('profile_company_registration_no')
                            ->label('Company Registration / SSM No.')
                            ->maxLength(50)
                            ->dehydrated(false),
                        TextInput::make('profile_billing_contact_name')
                            ->label('Billing Contact Name')
                            ->maxLength(150)
                            ->dehydrated(false),
                        TextInput::make('profile_billing_email')
                            ->label('Billing Email')
                            ->email()
                            ->maxLength(255)
                            ->dehydrated(false),
                        TextInput::make('profile_billing_phone')
                            ->label('Billing Phone')
                            ->maxLength(30)
                            ->dehydrated(false),
                        TextInput::make('profile_billing_address_line_1')
                            ->label('Billing Address Line 1')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->dehydrated(false),
                        TextInput::make('profile_billing_address_line_2')
                            ->label('Billing Address Line 2')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->dehydrated(false),
                        TextInput::make('profile_billing_city')
                            ->label('Billing City')
                            ->maxLength(100)
                            ->dehydrated(false),
                        TextInput::make('profile_billing_state')
                            ->label('Billing State')
                            ->maxLength(100)
                            ->dehydrated(false),
                        TextInput::make('profile_billing_postcode')
                            ->label('Billing Postcode')
                            ->maxLength(20)
                            ->dehydrated(false),
                        TextInput::make('profile_billing_country')
                            ->label('Billing Country (ISO-2)')
                            ->default('MY')
                            ->maxLength(2)
                            ->dehydrated(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->color('info'),
                TextColumn::make('customerProfile.customer_type')
                    ->label('Buyer Type')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof CustomerType ? $state->label() : ($state ? ucfirst((string) $state) : '—'))
                    ->placeholder('—'),
                TextColumn::make('customerProfile.company_name')
                    ->label('Company')
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (UserStatus|string $state): string => $state instanceof UserStatus ? $state->label() : ucfirst($state))
                    ->color(fn (UserStatus|string $state): string => match ($state instanceof UserStatus ? $state : UserStatus::tryFrom((string) $state)) {
                        UserStatus::Active => 'success',
                        UserStatus::Suspended => 'warning',
                        UserStatus::Deactivated => 'danger',
                        default => 'gray',
                    }),
                IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => $record->email_verified_at !== null),
                IconColumn::make('two_factor_confirmed_at')
                    ->label('MFA')
                    ->boolean()
                    ->getStateUsing(fn (User $record): bool => $record->hasConfirmedTwoFactor()),
                TextColumn::make('last_login_at')
                    ->label('Last Login (MYT)')
                    ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('Never'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(UserStatus::cases())->mapWithKeys(fn (UserStatus $s) => [$s->value => $s->label()])),
                SelectFilter::make('roles')
                    ->relationship('roles', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('changeStatus')
                    ->label('Change Status')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('warning')
                    ->visible(fn (User $record): bool => $record->id !== Auth::id())
                    ->schema([
                        Select::make('status')
                            ->label('Target Account Status')
                            ->options(collect(UserStatus::cases())->mapWithKeys(fn (UserStatus $s) => [$s->value => $s->label()]))
                            ->required(),
                        Textarea::make('status_reason')
                            ->label('Audit Reason')
                            ->required()
                            ->minLength(5)
                            ->maxLength(500)
                            ->placeholder('Explain why this account status is being updated.'),
                    ])
                    ->action(function (User $record, array $data): void {
                        $oldStatus = $record->status->value;
                        $newStatus = $data['status'];

                        $record->forceFill([
                            'status' => $newStatus,
                            'status_reason' => $data['status_reason'],
                            'status_changed_at' => now(),
                            'status_changed_by' => Auth::id(),
                        ])->save();

                        AuditLog::record(
                            event: 'user.status_changed',
                            auditableType: 'users',
                            auditableId: $record->id,
                            oldValues: ['status' => $oldStatus],
                            newValues: ['status' => $newStatus],
                            reason: $data['status_reason'],
                        );

                        app(EtmsNotificationService::class)->notifyUser(
                            user: $record,
                            eventType: 'user.status_changed',
                            title: 'Account Status Updated',
                            message: "Your Ecohydrotech ETMS account status has been changed from {$oldStatus} to {$newStatus}.",
                            actionUrl: route('portal.dashboard', [], false),
                            actionLabel: 'View Account',
                            details: [
                                'Previous Status' => ucfirst($oldStatus),
                                'New Status' => ucfirst($newStatus),
                                'Reason' => $data['status_reason'],
                            ],
                        );

                        Notification::make()
                            ->title('User account status updated')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function syncCustomerProfileFromFormData(User $user, array $data): void
    {
        $hasProfileInput = collect([
            'profile_phone',
            'profile_job_title',
            'profile_company_name',
            'profile_company_registration_no',
            'profile_billing_contact_name',
            'profile_billing_email',
            'profile_billing_phone',
            'profile_billing_address_line_1',
            'profile_billing_address_line_2',
            'profile_billing_city',
            'profile_billing_state',
            'profile_billing_postcode',
        ])->contains(fn (string $key): bool => filled($data[$key] ?? null));

        if (! $user->isCustomer() && ! $hasProfileInput && ! $user->customerProfile()->exists()) {
            return;
        }

        $user->customerProfile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'customer_type' => $data['profile_customer_type'] ?? CustomerType::Individual->value,
                'phone' => $data['profile_phone'] ?? null,
                'job_title' => $data['profile_job_title'] ?? null,
                'company_name' => $data['profile_company_name'] ?? null,
                'company_registration_no' => $data['profile_company_registration_no'] ?? null,
                'billing_contact_name' => $data['profile_billing_contact_name'] ?? null,
                'billing_email' => $data['profile_billing_email'] ?? null,
                'billing_phone' => $data['profile_billing_phone'] ?? null,
                'billing_address_line_1' => $data['profile_billing_address_line_1'] ?? null,
                'billing_address_line_2' => $data['profile_billing_address_line_2'] ?? null,
                'billing_city' => $data['profile_billing_city'] ?? null,
                'billing_state' => $data['profile_billing_state'] ?? null,
                'billing_postcode' => $data['profile_billing_postcode'] ?? null,
                'billing_country' => strtoupper((string) ($data['profile_billing_country'] ?: 'MY')),
            ],
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
