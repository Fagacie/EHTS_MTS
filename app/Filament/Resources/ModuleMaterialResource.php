<?php

namespace App\Filament\Resources;

use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Filament\Resources\ModuleMaterialResource\Pages\CreateModuleMaterial;
use App\Filament\Resources\ModuleMaterialResource\Pages\EditModuleMaterial;
use App\Filament\Resources\ModuleMaterialResource\Pages\ListModuleMaterials;
use App\Models\Module;
use App\Models\ModuleMaterial;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ModuleMaterialResource extends Resource
{
    protected static ?string $model = ModuleMaterial::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-arrow-down';

    protected static string|UnitEnum|null $navigationGroup = 'Training & Catalogue';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Module Materials';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Learning Material Metadata & Private Server Storage (FR-07, D-018)')
                    ->description('Materials are stored in private company server storage outside the web root and delivered only to administrators or customers holding an active module entitlement.')
                    ->columns(2)
                    ->schema([
                        Select::make('module_id')
                            ->label('Parent Training Module')
                            ->options(fn (): array => Module::query()->orderBy('code')->get()->mapWithKeys(fn (Module $m) => [$m->id => "{$m->code} — {$m->title}"])->all())
                            ->searchable()
                            ->required(),
                        TextInput::make('title')
                            ->required()
                            ->maxLength(200),
                        Select::make('material_type')
                            ->label('Material Type')
                            ->options(collect(MaterialType::cases())->mapWithKeys(fn (MaterialType $t) => [$t->value => $t->label()]))
                            ->default(MaterialType::Pdf->value)
                            ->required(),
                        Select::make('status')
                            ->label('Publication Status')
                            ->options(collect(MaterialStatus::cases())->mapWithKeys(fn (MaterialStatus $s) => [$s->value => $s->label()]))
                            ->default(MaterialStatus::Published->value)
                            ->required(),
                        FileUpload::make('storage_key')
                            ->label('Upload File (PDF, Presentation, Worksheet, Document, Image, ZIP)')
                            ->disk('materials')
                            ->directory('modules')
                            ->visibility('private')
                            ->storeFileNamesIn('original_filename')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-powerpoint',
                                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'text/csv',
                                'text/plain',
                                'application/zip',
                                'image/png',
                                'image/jpeg',
                            ])
                            ->maxSize(102400)
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('sort_order')
                            ->label('Curriculum Sort Order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->required(),
                        Toggle::make('is_downloadable')
                            ->label('Allow Signed Download by Entitled Learners')
                            ->default(true)
                            ->inline(false),
                        Textarea::make('description')
                            ->label('Guidance Notes / Description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('module.code')
                    ->label('Module')
                    ->fontFamily('mono')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('material_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (MaterialType|string $state): string => $state instanceof MaterialType ? $state->label() : ucfirst((string) $state)),
                TextColumn::make('original_filename')
                    ->label('Filename')
                    ->fontFamily('mono')
                    ->limit(28),
                TextColumn::make('file_size_bytes')
                    ->label('Size')
                    ->formatStateUsing(fn (ModuleMaterial $record): string => $record->formattedFileSize()),
                IconColumn::make('is_downloadable')
                    ->label('Downloadable')
                    ->boolean(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (MaterialStatus|string $state): string => $state instanceof MaterialStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn (MaterialStatus|string $state): string => ($state instanceof MaterialStatus ? $state : MaterialStatus::tryFrom((string) $state))?->badgeColor() ?? 'gray'),
            ])
            ->filters([
                SelectFilter::make('module_id')
                    ->label('Module')
                    ->relationship('module', 'title'),
                SelectFilter::make('material_type')
                    ->options(collect(MaterialType::cases())->mapWithKeys(fn (MaterialType $t) => [$t->value => $t->label()])),
                SelectFilter::make('status')
                    ->options(collect(MaterialStatus::cases())->mapWithKeys(fn (MaterialStatus $s) => [$s->value => $s->label()])),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->url(fn (ModuleMaterial $record): string => route('materials.download', $record)),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModuleMaterials::route('/'),
            'create' => CreateModuleMaterial::route('/create'),
            'edit' => EditModuleMaterial::route('/{record}/edit'),
        ];
    }
}
