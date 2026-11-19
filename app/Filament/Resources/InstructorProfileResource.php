<?php

namespace App\Filament\Resources;

use App\Enums\RoleSlug;
use App\Filament\Resources\InstructorProfileResource\Pages\CreateInstructorProfile;
use App\Filament\Resources\InstructorProfileResource\Pages\EditInstructorProfile;
use App\Filament\Resources\InstructorProfileResource\Pages\ListInstructorProfiles;
use App\Models\AuditLog;
use App\Models\InstructorProfile;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class InstructorProfileResource extends Resource
{
    protected static ?string $model = InstructorProfile::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static string|UnitEnum|null $navigationGroup = 'Users & Access';

    protected static ?string $navigationLabel = 'Instructor Profiles';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'display_name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Instructor Identity & Credentials (FR-15, ADM-18)')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Instructor Account')
                            ->options(fn (): array => User::query()
                                ->whereHas('roles', fn ($q) => $q->where('slug', RoleSlug::Instructor->value))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('Select a user holding the Instructor role.'),
                        TextInput::make('display_name')
                            ->label('Public Display Name (with Honorifics)')
                            ->placeholder('Ir. Dr. Ahmad Faiz bin Razak, P.Eng')
                            ->required()
                            ->maxLength(150),
                        TextInput::make('professional_title')
                            ->label('Professional Title')
                            ->placeholder('Principal Hydrologist & Certified Flood Modeller')
                            ->maxLength(150),
                        TextInput::make('qualifications')
                            ->label('Academic & Professional Qualifications')
                            ->placeholder('PhD (Hydrology), M.Eng (Water Resources), BEM P.Eng, IEM')
                            ->maxLength(255),
                        TextInput::make('short_bio')
                            ->label('Short Summary Bio')
                            ->maxLength(500)
                            ->columnSpanFull(),
                        TagsInput::make('specialisations')
                            ->label('Domain Specialisations')
                            ->placeholder('Press Enter to add each specialisation')
                            ->columnSpanFull(),
                        Textarea::make('biography')
                            ->label('Professional Biography')
                            ->rows(5)
                            ->columnSpanFull(),
                    ]),

                Section::make('Photograph & Directory Visibility')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('photo_storage_key')
                            ->label('Profile Photograph')
                            ->disk('public')
                            ->directory('instructors/photos')
                            ->image()
                            ->maxSize(5120),
                        Toggle::make('is_public')
                            ->label('Publish on Public Instructor Directory')
                            ->helperText('When enabled, this profile appears on /instructors and public session detail pages.')
                            ->default(false)
                            ->inline(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('display_name')
            ->columns([
                TextColumn::make('display_name')
                    ->label('Display Name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('user.email')
                    ->label('Account Email')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('professional_title')
                    ->label('Title')
                    ->limit(40)
                    ->placeholder('—'),
                TextColumn::make('qualifications')
                    ->label('Qualifications')
                    ->limit(35)
                    ->placeholder('—'),
                IconColumn::make('is_public')
                    ->label('Public Directory')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Updated (MYT)')
                    ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                    ->dateTime('d M Y, h:i A')
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_public')
                    ->label('Public Directory Visibility'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('togglePublic')
                    ->label(fn (InstructorProfile $record): string => $record->is_public ? 'Hide Profile' : 'Publish Profile')
                    ->icon(fn (InstructorProfile $record): string => $record->is_public ? 'heroicon-o-eye-slash' : 'heroicon-o-globe-alt')
                    ->color(fn (InstructorProfile $record): string => $record->is_public ? 'warning' : 'success')
                    ->action(function (InstructorProfile $record): void {
                        $oldState = $record->is_public;
                        $record->update(['is_public' => ! $oldState]);

                        AuditLog::record(
                            event: $record->is_public ? 'instructor_profile.published' : 'instructor_profile.hidden',
                            auditableType: InstructorProfile::class,
                            auditableId: $record->id,
                            oldValues: ['is_public' => $oldState],
                            newValues: ['is_public' => $record->is_public],
                        );

                        Notification::make()
                            ->title($record->is_public ? 'Instructor profile published' : 'Instructor profile hidden from public directory')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInstructorProfiles::route('/'),
            'create' => CreateInstructorProfile::route('/create'),
            'edit' => EditInstructorProfile::route('/{record}/edit'),
        ];
    }
}
