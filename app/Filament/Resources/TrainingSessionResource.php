<?php

namespace App\Filament\Resources;

use App\Enums\RecordingAccessPolicy;
use App\Enums\SessionStatus;
use App\Filament\Resources\TrainingSessionResource\Pages\CreateTrainingSession;
use App\Filament\Resources\TrainingSessionResource\Pages\EditTrainingSession;
use App\Filament\Resources\TrainingSessionResource\Pages\ListTrainingSessions;
use App\Filament\Resources\TrainingSessionResource\RelationManagers\InstructorsRelationManager;
use App\Models\AuditLog;
use App\Models\Module;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Booking\SessionBookingService;
use App\Services\Notification\EtmsNotificationService;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use UnitEnum;

class TrainingSessionResource extends Resource
{
    protected static ?string $model = TrainingSession::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static string|UnitEnum|null $navigationGroup = 'Physical Sessions & Delivery';

    protected static ?string $navigationLabel = 'Training Sessions';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Session Identity & Module Assignment (FR-03, ADM-06)')
                    ->columns(2)
                    ->schema([
                        Select::make('module_id')
                            ->label('Training Module')
                            ->options(fn (): array => Module::query()->orderBy('code')->get()->mapWithKeys(fn (Module $m) => [$m->id => "{$m->code} — {$m->title}"])->all())
                            ->searchable()
                            ->required(),
                        TextInput::make('session_code')
                            ->label('Session Code')
                            ->placeholder('SES-2026-001')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),
                        TextInput::make('title')
                            ->label('Session Title')
                            ->required()
                            ->maxLength(200)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, Set $set): void {
                                if ($operation === 'create' && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(220)
                            ->unique(ignoreRecord: true),
                        Textarea::make('description')
                            ->label('Session Overview & Cohort Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Schedule, Registration Window & Timezone (D-029, D-031)')
                    ->description('All timestamps are displayed in Malaysia Standard Time (Asia/Kuala_Lumpur). Registration closing date must strictly precede the session start time (D-029).')
                    ->columns(3)
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Session Starts At (MYT)')
                            ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                            ->seconds(false)
                            ->required(),
                        DateTimePicker::make('ends_at')
                            ->label('Session Ends At (MYT)')
                            ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                            ->seconds(false)
                            ->after('starts_at')
                            ->required(),
                        TextInput::make('duration_minutes')
                            ->label('Instructional Duration (Minutes)')
                            ->numeric()
                            ->default(480)
                            ->minValue(1)
                            ->required(),
                        DateTimePicker::make('registration_opens_at')
                            ->label('Registration Opens At (Optional)')
                            ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                            ->seconds(false),
                        DateTimePicker::make('registration_closes_at')
                            ->label('Registration Closes At (MYT)')
                            ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                            ->seconds(false)
                            ->before('starts_at')
                            ->required(),
                        TextInput::make('timezone')
                            ->label('Display Timezone')
                            ->default('Asia/Kuala_Lumpur')
                            ->required()
                            ->maxLength(64),
                    ]),

                Section::make('Physical Venue & Location')
                    ->columns(2)
                    ->schema([
                        TextInput::make('venue_name')
                            ->label('Venue Name')
                            ->placeholder('Ecohydrotech Training Centre, Hall A')
                            ->required()
                            ->maxLength(200),
                        TextInput::make('venue_map_url')
                            ->label('Google Maps / Location Link (Optional)')
                            ->url()
                            ->maxLength(500),
                        Textarea::make('venue_address')
                            ->label('Street Address')
                            ->rows(2)
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('venue_city')
                            ->label('City')
                            ->default('Shah Alam')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('venue_state')
                            ->label('State')
                            ->default('Selangor')
                            ->required()
                            ->maxLength(100),
                    ]),

                Section::make('Capacity, Commercial Pricing & Recording Policy (D-017, D-018, D-033)')
                    ->columns(2)
                    ->schema([
                        TextInput::make('capacity')
                            ->label('Seat Capacity')
                            ->numeric()
                            ->minValue(1)
                            ->default(25)
                            ->required(),
                        TextInput::make('price')
                            ->label('Session Registration Price (MYR)')
                            ->numeric()
                            ->prefix('RM')
                            ->minValue(0)
                            ->step('0.01')
                            ->required(),
                        Select::make('recording_access_policy')
                            ->label('Post-Session Recording Access Policy (D-017)')
                            ->options(collect(RecordingAccessPolicy::cases())->mapWithKeys(fn (RecordingAccessPolicy $p) => [$p->value => $p->label()]))
                            ->default(RecordingAccessPolicy::AttendeesOnly->value)
                            ->required(),
                        Select::make('status')
                            ->label('Lifecycle Status')
                            ->options(collect(SessionStatus::cases())->mapWithKeys(fn (SessionStatus $s) => [$s->value => $s->label()]))
                            ->default(SessionStatus::Draft->value)
                            ->required(),
                        Textarea::make('completion_notes')
                            ->label('Instructor / Admin Completion Notes')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('starts_at', 'asc')
            ->columns([
                TextColumn::make('session_code')
                    ->label('Code')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(38),
                TextColumn::make('module.code')
                    ->label('Module')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('starts_at')
                    ->label('Starts (MYT)')
                    ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
                TextColumn::make('venue_city')
                    ->label('Venue')
                    ->formatStateUsing(fn (TrainingSession $record): string => "{$record->venue_name} ({$record->venue_city})"),
                TextColumn::make('seats_summary')
                    ->label('Seats (Conf / Hold / Cap)')
                    ->fontFamily('mono')
                    ->state(fn (TrainingSession $record): string => "{$record->confirmed_seats_count} / {$record->reserved_seats_count} / {$record->capacity}"),
                TextColumn::make('price')
                    ->label('Price')
                    ->money('MYR')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (SessionStatus|string $state): string => $state instanceof SessionStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn (SessionStatus|string $state): string => ($state instanceof SessionStatus ? $state : SessionStatus::tryFrom((string) $state))?->badgeColor() ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('module_id')
                    ->label('Module')
                    ->relationship('module', 'title'),
                SelectFilter::make('status')
                    ->options(collect(SessionStatus::cases())->mapWithKeys(fn (SessionStatus $s) => [$s->value => $s->label()])),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-globe-alt')
                    ->color('success')
                    ->visible(fn (TrainingSession $record): bool => in_array($record->status, [SessionStatus::Draft, SessionStatus::RegistrationClosed], true))
                    ->action(function (TrainingSession $record): void {
                        $oldStatus = $record->status->value;
                        $record->update([
                            'status' => SessionStatus::Published->value,
                            'published_at' => $record->published_at ?? now(),
                            'updated_by' => Auth::id(),
                        ]);

                        AuditLog::record(
                            event: 'session.published',
                            auditableType: TrainingSession::class,
                            auditableId: $record->id,
                            oldValues: ['status' => $oldStatus],
                            newValues: ['status' => SessionStatus::Published->value],
                        );

                        Notification::make()->title('Training session published')->success()->send();
                    }),
                Action::make('closeRegistration')
                    ->label('Close Reg')
                    ->icon('heroicon-o-lock-closed')
                    ->color('warning')
                    ->visible(fn (TrainingSession $record): bool => in_array($record->status, [SessionStatus::Published, SessionStatus::Full], true))
                    ->action(function (TrainingSession $record): void {
                        $oldStatus = $record->status->value;
                        $record->update([
                            'status' => SessionStatus::RegistrationClosed->value,
                            'updated_by' => Auth::id(),
                        ]);

                        AuditLog::record(
                            event: 'session.registration_closed',
                            auditableType: TrainingSession::class,
                            auditableId: $record->id,
                            oldValues: ['status' => $oldStatus],
                            newValues: ['status' => SessionStatus::RegistrationClosed->value],
                        );

                        Notification::make()->title('Session registration closed')->warning()->send();
                    }),
                Action::make('markCompleted')
                    ->label('Complete')
                    ->icon('heroicon-o-check-badge')
                    ->color('info')
                    ->visible(fn (TrainingSession $record): bool => in_array($record->status, [SessionStatus::Published, SessionStatus::Full, SessionStatus::RegistrationClosed, SessionStatus::InProgress], true))
                    ->action(function (TrainingSession $record): void {
                        $oldStatus = $record->status->value;
                        $record->update([
                            'status' => SessionStatus::Completed->value,
                            'completed_at' => $record->completed_at ?? now(),
                            'completed_by' => Auth::id(),
                            'updated_by' => Auth::id(),
                        ]);

                        AuditLog::record(
                            event: 'session.completed',
                            auditableType: TrainingSession::class,
                            auditableId: $record->id,
                            oldValues: ['status' => $oldStatus],
                            newValues: ['status' => SessionStatus::Completed->value],
                        );

                        Notification::make()->title('Training session marked completed')->success()->send();
                    }),
                Action::make('notifyParticipants')
                    ->label('Notify Update')
                    ->icon('heroicon-o-bell-alert')
                    ->color('primary')
                    ->schema([
                        Textarea::make('update_message')
                            ->label('Session Update Message')
                            ->helperText('Sent to all confirmed participants and assigned instructors.')
                            ->required()
                            ->maxLength(800),
                    ])
                    ->action(function (TrainingSession $record, array $data, EtmsNotificationService $notifier): void {
                        $summary = trim((string) $data['update_message']);
                        $bookings = $record->bookings()
                            ->where('status', 'confirmed')
                            ->with('user')
                            ->get();
                        $instructors = $record->instructors()->get();
                        $sent = 0;

                        foreach ($bookings as $booking) {
                            if (! $booking->user) {
                                continue;
                            }

                            $notifier->notifyUser(
                                user: $booking->user,
                                eventType: 'session.updated',
                                title: "Session Update: {$record->title}",
                                message: $summary,
                                actionUrl: route('portal.bookings.show', $booking, false),
                                actionLabel: 'View Session Booking',
                                details: [
                                    'Session' => "{$record->session_code} — {$record->title}",
                                    'Starts (MYT)' => $record->starts_at?->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))->format('d M Y, h:i A') ?? 'TBA',
                                    'Venue' => trim("{$record->venue_name}, {$record->venue_city}", ', '),
                                ],
                                reference: $record->session_code,
                            );
                            $sent++;
                        }

                        foreach ($instructors as $instructor) {
                            $notifier->notifyUser(
                                user: $instructor,
                                eventType: 'session.updated',
                                title: "Assigned Session Update: {$record->title}",
                                message: $summary,
                                actionUrl: route('instructor.sessions.show', $record, false),
                                actionLabel: 'Open Instructor Session',
                                details: [
                                    'Session' => "{$record->session_code} — {$record->title}",
                                    'Starts (MYT)' => $record->starts_at?->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))->format('d M Y, h:i A') ?? 'TBA',
                                ],
                                reference: $record->session_code,
                            );
                            $sent++;
                        }

                        AuditLog::record(
                            event: 'session.update_notified',
                            auditableType: TrainingSession::class,
                            auditableId: $record->id,
                            oldValues: null,
                            newValues: ['recipients_notified' => $sent],
                            reason: $summary,
                        );

                        Notification::make()->title("Session update queued for {$sent} recipient(s).")->success()->send();
                    }),
                Action::make('reconcileSeats')
                    ->label('Reconcile Seats')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function (TrainingSession $record, SessionBookingService $bookingService): void {
                        $bookingService->reconcileSessionCounters($record->id, 'Manual administrator seat reconciliation.');
                        Notification::make()->title('Seat counters reconciled from active bookings')->success()->send();
                    }),
                Action::make('cancelSession')
                    ->label('Cancel Session')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (TrainingSession $record): bool => ! in_array($record->status, [SessionStatus::Cancelled, SessionStatus::Completed], true))
                    ->schema([
                        Textarea::make('cancellation_reason')
                            ->label('Mandatory Cancellation Reason (BR-16, D-021)')
                            ->helperText('Cancelling this session automatically cancels all active bookings and releases reserved seats.')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (TrainingSession $record, array $data, SessionBookingService $bookingService): void {
                        /** @var User $admin */
                        $admin = Auth::user();
                        try {
                            $bookingService->cancelSessionByAdmin($record, $admin, (string) $data['cancellation_reason']);
                            Notification::make()->title('Training session cancelled and bookings released')->danger()->send();
                        } catch (DomainException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                DeleteAction::make()
                    ->visible(fn (TrainingSession $record): bool => ! $record->bookings()->exists()),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            InstructorsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTrainingSessions::route('/'),
            'create' => CreateTrainingSession::route('/create'),
            'edit' => EditTrainingSession::route('/{record}/edit'),
        ];
    }
}
