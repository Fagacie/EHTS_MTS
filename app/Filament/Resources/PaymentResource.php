<?php

namespace App\Filament\Resources;

use App\Enums\PaymentStatus;
use App\Enums\RefundMethod;
use App\Filament\Resources\PaymentResource\Pages\ListPayments;
use App\Models\Payment;
use App\Models\User;
use App\Services\Payment\PaymentOrchestrationService;
use App\Services\Payment\RefundService;
use BackedEnum;
use DomainException;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use UnitEnum;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static string|UnitEnum|null $navigationGroup = 'Commerce & Finance';

    protected static ?string $navigationLabel = 'Payments (Billplz)';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'payment_reference';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('payment_reference')
                    ->label('Payment Ref')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->description(fn (Payment $record): string => $record->user?->email ?? '')
                    ->searchable(),
                TextColumn::make('gateway_bill_id')
                    ->label('Billplz Bill ID')
                    ->fontFamily('mono')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Charged')
                    ->money('MYR')
                    ->sortable(),
                TextColumn::make('refunded_amount')
                    ->label('Refunded')
                    ->money('MYR'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (PaymentStatus|string $state): string => $state instanceof PaymentStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn (PaymentStatus|string $state): string => ($state instanceof PaymentStatus ? $state : PaymentStatus::tryFrom((string) $state))?->badgeColor() ?? 'gray'),
                TextColumn::make('paid_at')
                    ->label('Paid (MYT)')
                    ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(PaymentStatus::cases())->mapWithKeys(fn (PaymentStatus $s) => [$s->value => $s->label()])),
            ])
            ->recordActions([
                Action::make('verifyViaApi')
                    ->label('Verify via Billplz API')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->visible(fn (Payment $record): bool => filled($record->gateway_bill_id))
                    ->action(function (Payment $record, PaymentOrchestrationService $paymentService): void {
                        /** @var User $admin */
                        $admin = Auth::user();
                        try {
                            $paymentService->verifyPaymentViaApi($record, $admin);
                            Notification::make()->title('Payment verified against Billplz API')->success()->send();
                        } catch (DomainException|RuntimeException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('recordRefund')
                    ->label('Record Refund')
                    ->icon('heroicon-o-receipt-refund')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Payment $record): bool => in_array($record->status, [PaymentStatus::Successful, PaymentStatus::PartiallyRefunded], true) && $record->refundableBalance() > 0)
                    ->schema(fn (Payment $record): array => [
                        TextInput::make('amount')
                            ->label('Refund Amount (MYR)')
                            ->numeric()
                            ->prefix('RM')
                            ->default($record->refundableBalance())
                            ->minValue(0.01)
                            ->maxValue($record->refundableBalance())
                            ->step('0.01')
                            ->required(),
                        Select::make('refund_method')
                            ->label('Disbursement Method')
                            ->options(collect(RefundMethod::cases())->mapWithKeys(fn (RefundMethod $m) => [$m->value => $m->label()]))
                            ->default(RefundMethod::ManualBankTransfer->value)
                            ->required(),
                        TextInput::make('disbursement_reference')
                            ->label('Disbursement / Bank Transfer Reference (Optional)')
                            ->maxLength(100),
                        Textarea::make('reason')
                            ->label('Mandatory Refund Reason')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (Payment $record, array $data, RefundService $refundService): void {
                        /** @var User $admin */
                        $admin = Auth::user();
                        try {
                            $refundService->recordAndCompleteAdminRefund(
                                payment: $record,
                                admin: $admin,
                                amount: (float) $data['amount'],
                                method: (string) $data['refund_method'],
                                reason: (string) $data['reason'],
                                disbursementReference: $data['disbursement_reference'] ?? null,
                            );
                            Notification::make()->title('Refund recorded and downstream entitlements/bookings updated')->success()->send();
                        } catch (DomainException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
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
            'index' => ListPayments::route('/'),
        ];
    }
}
