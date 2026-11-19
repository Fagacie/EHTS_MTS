<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Order;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class MonthlyRevenueChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static ?bool $isLazyLoaded = false;

    protected ?string $heading = 'Monthly Revenue Trend (MYR)';

    protected ?string $description = 'Paid orders across the past 6 months (MYT), split by session bookings and independent module purchases.';

    protected ?string $maxHeight = '260px';

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $tz = config('etms.display_timezone', 'Asia/Kuala_Lumpur');
        $startMonth = now($tz)->startOfMonth()->subMonths(5);
        $months = [];

        for ($i = 0; $i < 6; $i++) {
            $month = $startMonth->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $months[$key] = [
                'label' => $month->format('M Y'),
                'session' => 0.0,
                'module' => 0.0,
            ];
        }

        $orders = Order::query()
            ->whereIn('status', [
                OrderStatus::Paid->value,
                OrderStatus::PartiallyRefunded->value,
                OrderStatus::Refunded->value,
            ])
            ->where('paid_at', '>=', $startMonth->copy()->utc())
            ->get(['order_type', 'total_amount', 'paid_at']);

        foreach ($orders as $order) {
            if (! $order->paid_at) {
                continue;
            }

            $bucket = Carbon::parse($order->paid_at)->timezone($tz)->format('Y-m');
            if (! isset($months[$bucket])) {
                continue;
            }

            if ($order->order_type === OrderType::SessionBooking) {
                $months[$bucket]['session'] += (float) $order->total_amount;
            } else {
                $months[$bucket]['module'] += (float) $order->total_amount;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Physical Session Bookings (MYR)',
                    'data' => array_map(fn (array $m): float => round($m['session'], 2), array_values($months)),
                    'backgroundColor' => '#0F4C81',
                    'borderRadius' => 4,
                ],
                [
                    'label' => 'Independent Module Purchases (MYR)',
                    'data' => array_map(fn (array $m): float => round($m['module'], 2), array_values($months)),
                    'backgroundColor' => '#0D9488',
                    'borderRadius' => 4,
                ],
            ],
            'labels' => array_column(array_values($months), 'label'),
        ];
    }
}
