<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages\CreateCategory;
use App\Filament\Resources\CategoryResource\Pages\EditCategory;
use App\Filament\Resources\CategoryResource\Pages\ListCategories;
use App\Models\AuditLog;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|UnitEnum|null $navigationGroup = 'Training & Catalogue';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category Details (D-024)')
                    ->description('Each training module belongs to one primary category.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(100)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, ?string $state, Set $set): void {
                                if ($operation === 'create' && filled($state)) {
                                    $set('slug', Str::slug($state));
                                }
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(120)
                            ->unique(ignoreRecord: true),
                        TextInput::make('sort_order')
                            ->label('Display Sort Order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Published / Active in Public Catalogue')
                            ->default(true)
                            ->inline(false),
                        Textarea::make('description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('slug')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('modules_count')
                    ->counts('modules')
                    ->label('Modules')
                    ->badge()
                    ->color('info'),
                IconColumn::make('is_active')
                    ->label('Published')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Updated (MYT)')
                    ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                    ->dateTime('d M Y, h:i A')
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Catalogue Visibility')
                    ->trueLabel('Published / Active')
                    ->falseLabel('Archived / Hidden'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn (Category $record): string => $record->is_active ? 'Archive / Hide' : 'Publish')
                    ->icon(fn (Category $record): string => $record->is_active ? 'heroicon-o-archive-box' : 'heroicon-o-check-circle')
                    ->color(fn (Category $record): string => $record->is_active ? 'warning' : 'success')
                    ->action(function (Category $record): void {
                        $old = $record->is_active;
                        $record->update(['is_active' => ! $old]);

                        AuditLog::record(
                            event: $record->is_active ? 'category.published' : 'category.archived',
                            auditableType: 'categories',
                            auditableId: $record->id,
                            oldValues: ['is_active' => $old],
                            newValues: ['is_active' => $record->is_active],
                        );

                        Notification::make()
                            ->title($record->is_active ? 'Category published' : 'Category archived')
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()
                    ->visible(fn (Category $record): bool => ! $record->modules()->withTrashed()->exists()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
