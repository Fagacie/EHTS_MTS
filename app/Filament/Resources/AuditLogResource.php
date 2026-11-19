<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages\ListAuditLogs;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|UnitEnum|null $navigationGroup = 'System & Governance';

    protected static ?int $navigationSort = 10;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Timestamp (MYT)')
                    ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                    ->dateTime('d M Y, h:i:s A')
                    ->sortable(),
                TextColumn::make('event')
                    ->badge()
                    ->searchable()
                    ->color('info'),
                TextColumn::make('actor.name')
                    ->label('Actor')
                    ->placeholder('System / Guest')
                    ->searchable(),
                TextColumn::make('actor_role')
                    ->label('Role')
                    ->placeholder('—'),
                TextColumn::make('auditable_type')
                    ->label('Target Table'),
                TextColumn::make('auditable_id')
                    ->label('Record ID'),
                TextColumn::make('reason')
                    ->limit(40)
                    ->placeholder('—'),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->options(fn (): array => AuditLog::query()->distinct()->pluck('event', 'event')->all()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }
}
