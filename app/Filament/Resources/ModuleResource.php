<?php

namespace App\Filament\Resources;

use App\Enums\ModuleStatus;
use App\Filament\Resources\ModuleResource\Pages\CreateModule;
use App\Filament\Resources\ModuleResource\Pages\EditModule;
use App\Filament\Resources\ModuleResource\Pages\ListModules;
use App\Filament\Resources\ModuleResource\RelationManagers\MaterialsRelationManager;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Module;
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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use UnitEnum;

class ModuleResource extends Resource
{
    protected static ?string $model = Module::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-book-open';

    protected static string|UnitEnum|null $navigationGroup = 'Training & Catalogue';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Module Identity & Classification (FR-02, D-024)')
                    ->columns(2)
                    ->schema([
                        Select::make('category_id')
                            ->label('Primary Category')
                            ->options(fn (): array => Category::query()->ordered()->pluck('name', 'id')->all())
                            ->searchable()
                            ->required(),
                        TextInput::make('code')
                            ->label('Module Code')
                            ->placeholder('EHT-HYD-101')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true),
                        TextInput::make('title')
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
                        Textarea::make('summary')
                            ->label('Catalogue Summary')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->label('Full Syllabus & Description')
                            ->rows(6)
                            ->required()
                            ->columnSpanFull(),
                        TagsInput::make('learning_objectives')
                            ->label('Learning Objectives')
                            ->placeholder('Press Enter to add each learning objective')
                            ->helperText('Structured list of outcomes displayed on the public module detail page.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Independent Purchase & Commercial Pricing (FR-06, D-005, D-018)')
                    ->description('Physical session prices are configured separately per scheduled session. Enable independent availability to offer lifetime online access to this module.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_available_independently')
                            ->label('Available for Independent Self-Paced Purchase')
                            ->default(true)
                            ->live()
                            ->inline(false),
                        TextInput::make('independent_price')
                            ->label('Independent Purchase Price (MYR)')
                            ->numeric()
                            ->prefix('RM')
                            ->minValue(0)
                            ->step('0.01')
                            ->required(fn (Get $get): bool => (bool) $get('is_available_independently')),
                    ]),

                Section::make('Cover Image & Publication Lifecycle')
                    ->columns(2)
                    ->schema([
                        FileUpload::make('cover_image_path')
                            ->label('Cover Image')
                            ->disk('public')
                            ->directory('modules/covers')
                            ->image()
                            ->maxSize(5120),
                        Select::make('status')
                            ->label('Publication Status')
                            ->options(collect(ModuleStatus::cases())->mapWithKeys(fn (ModuleStatus $s) => [$s->value => $s->label()]))
                            ->default(ModuleStatus::Draft->value)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(45),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                IconColumn::make('is_available_independently')
                    ->label('Self-Paced')
                    ->boolean(),
                TextColumn::make('independent_price')
                    ->label('Module Price')
                    ->money('MYR')
                    ->placeholder('Session Only')
                    ->sortable(),
                TextColumn::make('materials_count')
                    ->counts('materials')
                    ->label('Materials')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (ModuleStatus|string $state): string => $state instanceof ModuleStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn (ModuleStatus|string $state): string => ($state instanceof ModuleStatus ? $state : ModuleStatus::tryFrom((string) $state))?->badgeColor() ?? 'gray'),
                TextColumn::make('published_at')
                    ->label('Published (MYT)')
                    ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name'),
                SelectFilter::make('status')
                    ->options(collect(ModuleStatus::cases())->mapWithKeys(fn (ModuleStatus $s) => [$s->value => $s->label()])),
                TernaryFilter::make('is_available_independently')
                    ->label('Independent Purchase'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('publish')
                    ->label('Publish')
                    ->icon('heroicon-o-globe-alt')
                    ->color('success')
                    ->visible(fn (Module $record): bool => $record->status !== ModuleStatus::Published)
                    ->action(function (Module $record): void {
                        $oldStatus = $record->status->value;
                        $record->update([
                            'status' => ModuleStatus::Published->value,
                            'published_at' => now(),
                            'updated_by' => Auth::id(),
                        ]);

                        AuditLog::record(
                            event: 'module.published',
                            auditableType: 'modules',
                            auditableId: $record->id,
                            oldValues: ['status' => $oldStatus],
                            newValues: ['status' => ModuleStatus::Published->value],
                        );

                        Notification::make()->title('Module published')->success()->send();
                    }),
                Action::make('unpublish')
                    ->label('Unpublish')
                    ->icon('heroicon-o-eye-slash')
                    ->color('warning')
                    ->visible(fn (Module $record): bool => $record->status === ModuleStatus::Published)
                    ->action(function (Module $record): void {
                        $oldStatus = $record->status->value;
                        $record->update([
                            'status' => ModuleStatus::Unpublished->value,
                            'updated_by' => Auth::id(),
                        ]);

                        AuditLog::record(
                            event: 'module.unpublished',
                            auditableType: 'modules',
                            auditableId: $record->id,
                            oldValues: ['status' => $oldStatus],
                            newValues: ['status' => ModuleStatus::Unpublished->value],
                        );

                        Notification::make()->title('Module unpublished')->warning()->send();
                    }),
                Action::make('archive')
                    ->label('Archive')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->visible(fn (Module $record): bool => $record->status !== ModuleStatus::Archived)
                    ->action(function (Module $record): void {
                        $oldStatus = $record->status->value;
                        $record->update([
                            'status' => ModuleStatus::Archived->value,
                            'archived_at' => now(),
                            'updated_by' => Auth::id(),
                        ]);

                        AuditLog::record(
                            event: 'module.archived',
                            auditableType: 'modules',
                            auditableId: $record->id,
                            oldValues: ['status' => $oldStatus],
                            newValues: ['status' => ModuleStatus::Archived->value],
                        );

                        Notification::make()->title('Module archived')->info()->send();
                    }),
                DeleteAction::make()
                    ->visible(fn (Module $record): bool => ! $record->materials()->withTrashed()->exists()),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            MaterialsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModules::route('/'),
            'create' => CreateModule::route('/create'),
            'edit' => EditModule::route('/{record}/edit'),
        ];
    }
}
