<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Filament\Widgets\ChartWidget;

class DeliveryMixChart extends ChartWidget
{
    protected static ?int $sort = 3;

    protected static ?bool $isLazyLoaded = false;

    protected ?string $heading = 'Booking Status Breakdown';

    protected ?string $description = 'Distribution of physical session seat bookings across lifecycle states.';

    protected ?string $maxHeight = '260px';

    protected function getType(): string
    {
        return 'doughnut';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $counts = Booking::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return [
            'datasets' => [
                [
                    'label' => 'Bookings',
                    'data' => [
                        (int) ($counts[BookingStatus::Confirmed->value] ?? 0),
                        (int) ($counts[BookingStatus::PendingPayment->value] ?? 0),
                        (int) ($counts[BookingStatus::Expired->value] ?? 0),
                        (int) ($counts[BookingStatus::Cancelled->value] ?? 0),
                        (int) ($counts[BookingStatus::Refunded->value] ?? 0),
                    ],
                    'backgroundColor' => [
                        '#059669',
                        '#F59E0B',
                        '#94A3B8',
                        '#EF4444',
                        '#0EA5E9',
                    ],
                ],
            ],
            'labels' => [
                'Confirmed',
                'Seat Held (15m)',
                'Expired Holds',
                'Cancelled',
                'Refunded',
            ],
        ];
    }
}
