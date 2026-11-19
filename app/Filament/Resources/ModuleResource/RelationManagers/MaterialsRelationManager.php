<?php

namespace App\Filament\Resources\ModuleResource\RelationManagers;

use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Models\ModuleMaterial;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MaterialsRelationManager extends RelationManager
{
    protected static string $relationship = 'materials';

    protected static ?string $title = 'Module Learning Materials (Private Server Storage)';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(200),
                Select::make('material_type')
                    ->options(collect(MaterialType::cases())->mapWithKeys(fn (MaterialType $t) => [$t->value => $t->label()]))
                    ->default(MaterialType::Pdf->value)
                    ->required(),
                FileUpload::make('storage_key')
                    ->label('Material File (Private Server Storage)')
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
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->required(),
                Select::make('status')
                    ->options(collect(MaterialStatus::cases())->mapWithKeys(fn (MaterialStatus $s) => [$s->value => $s->label()]))
                    ->default(MaterialStatus::Published->value)
                    ->required(),
                Toggle::make('is_downloadable')
                    ->label('Allow Entitled Download')
                    ->default(true),
                Textarea::make('description')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable()
                    ->weight('semibold'),
                TextColumn::make('material_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (MaterialType|string $state): string => $state instanceof MaterialType ? $state->label() : ucfirst((string) $state)),
                TextColumn::make('original_filename')
                    ->label('Filename')
                    ->fontFamily('mono')
                    ->limit(30),
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
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(fn (array $data): array => ModuleMaterial::enrichStorageMetadata($data, Auth::id())),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->url(fn (ModuleMaterial $record): string => route('materials.download', $record)),
                EditAction::make()
                    ->mutateDataUsing(fn (array $data): array => ModuleMaterial::enrichStorageMetadata($data, Auth::id())),
                DeleteAction::make(),
            ]);
    }
}
