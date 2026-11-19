<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shopping-bag';

    protected static string|UnitEnum|null $navigationGroup = 'Commerce & Finance';

    protected static ?string $navigationLabel = 'Orders';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_number')
                    ->label('Order #')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->description(fn (Order $record): string => $record->company_name ?: $record->customer_email)
                    ->searchable(['customer_name', 'customer_email', 'company_name']),
                TextColumn::make('order_type')
                    ->label('Order Type')
                    ->badge()
                    ->formatStateUsing(fn (OrderType|string $state): string => $state instanceof OrderType ? $state->label() : ucfirst((string) $state))
                    ->color(fn (OrderType|string $state): string => ($state instanceof OrderType ? $state : OrderType::tryFrom((string) $state)) === OrderType::SessionBooking ? 'info' : 'primary'),
                TextColumn::make('total_amount')
                    ->label('Total (MYR)')
                    ->money('MYR')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus|string $state): string => $state instanceof OrderStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn (OrderStatus|string $state): string => ($state instanceof OrderStatus ? $state : OrderStatus::tryFrom((string) $state))?->badgeColor() ?? 'gray'),
                TextColumn::make('paid_at')
                    ->label('Paid (MYT)')
                    ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Created (MYT)')
                    ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                    ->dateTime('d M Y, h:i A')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('order_type')
                    ->label('Order Type')
                    ->options(collect(OrderType::cases())->mapWithKeys(fn (OrderType $t) => [$t->value => $t->label()])),
                SelectFilter::make('status')
                    ->options(collect(OrderStatus::cases())->mapWithKeys(fn (OrderStatus $s) => [$s->value => $s->label()])),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
        ];
    }
}
