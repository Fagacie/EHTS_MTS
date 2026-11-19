<?php

namespace App\Filament\Resources;

use App\Enums\NotificationDeliveryStatus;
use App\Filament\Resources\NotificationDeliveryResource\Pages\ListNotificationDeliveries;
use App\Models\NotificationDelivery;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class NotificationDeliveryResource extends Resource
{
    protected static ?string $model = NotificationDelivery::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-envelope-open';

    protected static string|UnitEnum|null $navigationGroup = 'Communications & Reports';

    protected static ?string $navigationLabel = 'Notification Deliveries';

    protected static ?int $navigationSort = 20;

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
                    ->label('Queued (MYT)')
                    ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
                TextColumn::make('event_type')
                    ->label('Event')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('recipient_email')
                    ->label('Recipient Email')
                    ->searchable(),
                TextColumn::make('subject')
                    ->limit(42)
                    ->searchable(),
                TextColumn::make('mailer')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (NotificationDeliveryStatus|string $state): string => $state instanceof NotificationDeliveryStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn (NotificationDeliveryStatus|string $state): string => ($state instanceof NotificationDeliveryStatus ? $state : NotificationDeliveryStatus::tryFrom((string) $state))?->badgeColor() ?? 'gray'),
                TextColumn::make('sent_at')
                    ->label('Sent (MYT)')
                    ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('—'),
                TextColumn::make('error_message')
                    ->label('Failure / Error')
                    ->limit(45)
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(NotificationDeliveryStatus::cases())->mapWithKeys(fn (NotificationDeliveryStatus $s) => [$s->value => $s->label()])),
                SelectFilter::make('event_type')
                    ->options([
                        'booking.confirmed' => 'Booking Confirmed',
                        'booking.cancelled' => 'Booking Cancelled',
                        'booking.expired' => 'Booking Expired',
                        'payment.succeeded' => 'Payment Succeeded',
                        'payment.failed' => 'Payment Failed',
                        'refund.completed' => 'Refund Completed',
                        'session.reminder' => 'Session Reminder',
                        'session.updated' => 'Session Updated',
                        'session.cancelled' => 'Session Cancelled',
                        'session.announcement' => 'Announcement',
                        'module.access_granted' => 'Module Access Granted',
                        'certificate.issued' => 'Certificate Issued',
                        'certificate.revoked' => 'Certificate Revoked',
                    ]),
            ])
            ->recordActions([
                Action::make('inspectError')
                    ->label('Error Detail')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn (NotificationDelivery $record): bool => ! empty($record->error_message))
                    ->modalHeading('Delivery Failure Details')
                    ->modalContent(fn (NotificationDelivery $record) => view('filament.partials.notification-error', ['record' => $record]))
                    ->modalSubmitAction(false),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotificationDeliveries::route('/'),
        ];
    }
}
