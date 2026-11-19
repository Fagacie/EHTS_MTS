<?php

namespace App\Filament\Resources;

use App\Enums\AnnouncementStatus;
use App\Enums\RoleSlug;
use App\Filament\Resources\AnnouncementResource\Pages\CreateAnnouncement;
use App\Filament\Resources\AnnouncementResource\Pages\EditAnnouncement;
use App\Filament\Resources\AnnouncementResource\Pages\ListAnnouncements;
use App\Models\Announcement;
use App\Models\Module;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Notification\EtmsNotificationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class AnnouncementResource extends Resource
{
    protected static ?string $model = Announcement::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-megaphone';

    protected static string|UnitEnum|null $navigationGroup = 'Communications & Reports';

    protected static ?string $navigationLabel = 'Announcements';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Announcement Content')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(200)
                            ->columnSpanFull(),
                        Textarea::make('body')
                            ->label('Message Body')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                        Toggle::make('send_email')
                            ->label('Send Email Notification')
                            ->default(true),
                        Toggle::make('send_in_app')
                            ->label('Send In-App Portal Notification')
                            ->default(true),
                    ]),
                Section::make('Target Audience')
                    ->columns(2)
                    ->schema([
                        Select::make('audience_type')
                            ->label('Audience')
                            ->options([
                                'all_customers' => 'All Active Customers / Trainees',
                                'session_participants' => 'Specific Session Participants & Instructors',
                                'module_entitled_users' => 'Module Entitled Trainees',
                                'role' => 'Specific Platform Role',
                            ])
                            ->default('all_customers')
                            ->live()
                            ->required(),
                        Select::make('training_session_id')
                            ->label('Training Session')
                            ->options(fn () => TrainingSession::query()->orderByDesc('starts_at')->pluck('title', 'id'))
                            ->searchable()
                            ->visible(fn (Get $get): bool => $get('audience_type') === 'session_participants')
                            ->required(fn (Get $get): bool => $get('audience_type') === 'session_participants'),
                        Select::make('module_id')
                            ->label('Training Module')
                            ->options(fn () => Module::query()->orderBy('title')->pluck('title', 'id'))
                            ->searchable()
                            ->visible(fn (Get $get): bool => $get('audience_type') === 'module_entitled_users')
                            ->required(fn (Get $get): bool => $get('audience_type') === 'module_entitled_users'),
                        Select::make('target_role')
                            ->label('Role')
                            ->options(collect(RoleSlug::cases())->mapWithKeys(fn (RoleSlug $r) => [$r->value => $r->label()]))
                            ->visible(fn (Get $get): bool => $get('audience_type') === 'role')
                            ->required(fn (Get $get): bool => $get('audience_type') === 'role'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->limit(40)
                    ->weight('semibold'),
                TextColumn::make('audience_type')
                    ->label('Audience')
                    ->state(fn (Announcement $record): string => $record->audienceLabel())
                    ->badge()
                    ->color('info'),
                IconColumn::make('send_email')
                    ->label('Email')
                    ->boolean(),
                IconColumn::make('send_in_app')
                    ->label('In-App')
                    ->boolean(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (AnnouncementStatus|string $state): string => $state instanceof AnnouncementStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn (AnnouncementStatus|string $state): string => ($state instanceof AnnouncementStatus ? $state : AnnouncementStatus::tryFrom((string) $state))?->badgeColor() ?? 'gray'),
                TextColumn::make('recipients_count')
                    ->label('Recipients')
                    ->counts('recipients'),
                TextColumn::make('sent_at')
                    ->label('Sent (MYT)')
                    ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('Not sent'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(AnnouncementStatus::cases())->mapWithKeys(fn (AnnouncementStatus $s) => [$s->value => $s->label()])),
            ])
            ->recordActions([
                Action::make('sendNow')
                    ->label('Send Now')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Announcement $record): bool => in_array($record->status, [
                        AnnouncementStatus::Draft,
                        AnnouncementStatus::Scheduled,
                        AnnouncementStatus::Failed,
                    ], true))
                    ->action(function (Announcement $record, EtmsNotificationService $notifier): void {
                        /** @var User $admin */
                        $admin = Auth::user();
                        $count = $notifier->dispatchAnnouncement($record, $admin);
                        Notification::make()
                            ->title("Announcement dispatched to {$count} recipient(s).")
                            ->success()
                            ->send();
                    }),
                EditAction::make()
                    ->visible(fn (Announcement $record): bool => $record->status !== AnnouncementStatus::Sent),
                DeleteAction::make()
                    ->visible(fn (Announcement $record): bool => $record->status === AnnouncementStatus::Draft),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncements::route('/'),
            'create' => CreateAnnouncement::route('/create'),
            'edit' => EditAnnouncement::route('/{record}/edit'),
        ];
    }
}
