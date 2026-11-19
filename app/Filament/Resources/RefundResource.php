<?php

namespace App\Filament\Resources;

use App\Enums\RefundMethod;
use App\Enums\RefundStatus;
use App\Filament\Resources\RefundResource\Pages\ListRefunds;
use App\Models\Refund;
use App\Models\User;
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
use UnitEnum;

class RefundResource extends Resource
{
    protected static ?string $model = Refund::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-receipt-refund';

    protected static string|UnitEnum|null $navigationGroup = 'Commerce & Finance';

    protected static ?string $navigationLabel = 'Refunds';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'refund_reference';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('requested_at', 'desc')
            ->columns([
                TextColumn::make('refund_reference')
                    ->label('Refund Ref')
                    ->fontFamily('mono')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),
                TextColumn::make('order.order_number')
                    ->label('Order #')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('payment.payment_reference')
                    ->label('Payment Ref')
                    ->fontFamily('mono')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('MYR')
                    ->sortable(),
                TextColumn::make('refund_method')
                    ->label('Method')
                    ->formatStateUsing(fn (RefundMethod|string|null $state): string => $state instanceof RefundMethod ? $state->label() : ($state ? ucfirst((string) $state) : 'Pending Selection')),
                TextColumn::make('source_event')
                    ->label('Source Event')
                    ->fontFamily('mono')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (RefundStatus|string $state): string => $state instanceof RefundStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn (RefundStatus|string $state): string => ($state instanceof RefundStatus ? $state : RefundStatus::tryFrom((string) $state))?->badgeColor() ?? 'gray'),
                TextColumn::make('processed_at')
                    ->label('Processed (MYT)')
                    ->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))
                    ->dateTime('d M Y, h:i A')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(collect(RefundStatus::cases())->mapWithKeys(fn (RefundStatus $s) => [$s->value => $s->label()])),
                SelectFilter::make('request_source')
                    ->options([
                        'system' => 'System Triggered',
                        'administrator' => 'Administrator Initiated',
                    ]),
            ])
            ->recordActions([
                Action::make('approveAndComplete')
                    ->label('Approve & Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Refund $record): bool => in_array($record->status, [RefundStatus::Requested, RefundStatus::Approved, RefundStatus::Processing], true))
                    ->schema([
                        Select::make('refund_method')
                            ->label('Disbursement Method')
                            ->options(collect(RefundMethod::cases())->mapWithKeys(fn (RefundMethod $m) => [$m->value => $m->label()]))
                            ->default(RefundMethod::ManualBankTransfer->value)
                            ->required(),
                        TextInput::make('disbursement_reference')
                            ->label('Disbursement Reference (Optional)')
                            ->maxLength(100),
                    ])
                    ->action(function (Refund $record, array $data, RefundService $refundService): void {
                        /** @var User $admin */
                        $admin = Auth::user();
                        try {
                            $refundService->approveAndCompleteRequestedRefund(
                                refund: $record,
                                admin: $admin,
                                method: (string) $data['refund_method'],
                                disbursementReference: $data['disbursement_reference'] ?? null,
                            );
                            Notification::make()->title('Refund approved and completed')->success()->send();
                        } catch (DomainException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('rejectRefund')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Refund $record): bool => $record->status === RefundStatus::Requested)
                    ->schema([
                        Textarea::make('failure_reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(function (Refund $record, array $data, RefundService $refundService): void {
                        /** @var User $admin */
                        $admin = Auth::user();
                        $refundService->rejectRefund($record, $admin, (string) $data['failure_reason']);
                        Notification::make()->title('Refund request rejected')->warning()->send();
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
            'index' => ListRefunds::route('/'),
        ];
    }
}
