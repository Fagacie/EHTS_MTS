<?php

namespace App\Filament\Resources;

use App\Enums\BookingStatus;
use App\Filament\Resources\BookingResource\Pages\ListBookings;
use App\Models\Booking;
use App\Models\User;
use App\Services\Booking\SessionBookingService;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static string|UnitEnum|null $navigationGroup = 'Commerce & Finance';

    protected static ?string $navigationLabel = 'Session Bookings';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'booking_reference';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('booking_reference')
                    ->label('Booking Ref')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('user.name')
                    ->label('Participant')
                    ->description(fn (Booking $record): string => $record->user?->email ?? '')
                    ->searchable(),
                TextColumn::make('trainingSession.session_code')
                    ->label('Session')
                    ->description(fn (Booking $record): string => $record->trainingSession?->title ?? '')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('order.total_amount')
                    ->label('Amount')
                    ->money('MYR'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (BookingStatus|string $state): string => $state instanceof BookingStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn (BookingStatus|string $state): string => ($state instanceof BookingStatus ? $state : BookingStatus::tryFrom((string) $state))?->badgeColor() ?? 'gray'),
                TextColumn::make('reservation_expires_at')
                    ->label('Hold Expires (MYT)')
                    ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('confirmed_at')
                    ->label('Confirmed (MYT)')
                    ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('training_session_id')
                    ->label('Training Session')
                    ->relationship('trainingSession', 'title'),
                SelectFilter::make('status')
                    ->options(collect(BookingStatus::cases())->mapWithKeys(fn (BookingStatus $s) => [$s->value => $s->label()])),
            ])
            ->recordActions([
                Action::make('confirmBooking')
                    ->label('Confirm Seat')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record): bool => in_array($record->status, [BookingStatus::PendingPayment, BookingStatus::Expired], true))
                    ->action(function (Booking $record, SessionBookingService $bookingService): void {
                        /** @var User $admin */
                        $admin = Auth::user();
                        try {
                            $bookingService->confirmBooking($record, $admin, 'Administrator manual seat confirmation.');
                            Notification::make()->title('Booking confirmed and seat allocated')->success()->send();
                        } catch (DomainException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('cancelBooking')
                    ->label('Cancel Booking')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Booking $record): bool => in_array($record->status, [BookingStatus::PendingPayment, BookingStatus::Confirmed], true))
                    ->schema([
                        Textarea::make('cancellation_reason')
                            ->label('Cancellation Reason')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (Booking $record, array $data, SessionBookingService $bookingService): void {
                        /** @var User $admin */
                        $admin = Auth::user();
                        $bookingService->cancelBookingByAdmin($record, $admin, (string) $data['cancellation_reason']);
                        Notification::make()->title('Booking cancelled and seat count updated')->warning()->send();
                    }),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
        ];
    }
}
