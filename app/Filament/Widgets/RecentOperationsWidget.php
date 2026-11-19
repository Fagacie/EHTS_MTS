<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Enums\SessionStatus;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\TrainingSession;
use Filament\Widgets\Widget;

class RecentOperationsWidget extends Widget
{
    protected static ?int $sort = 4;

    protected static ?bool $isLazyLoaded = false;

    protected string $view = 'filament.widgets.recent-operations-widget';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $tz = config('etms.display_timezone', 'Asia/Kuala_Lumpur');

        $recentBookings = Booking::query()
            ->with(['user:id,name,email', 'trainingSession:id,session_code,title'])
            ->latest('created_at')
            ->limit(5)
            ->get();

        $recentPayments = Payment::query()
            ->with(['order:id,order_number', 'user:id,name'])
            ->latest('created_at')
            ->limit(5)
            ->get();

        $upcomingSessions = TrainingSession::query()
            ->with('module:id,code')
            ->whereIn('status', [
                SessionStatus::Published->value,
                SessionStatus::Full->value,
                SessionStatus::RegistrationClosed->value,
            ])
            ->where('starts_at', '>', now())
            ->orderBy('starts_at')
            ->limit(5)
            ->get();

        $pendingHolds = Booking::query()
            ->where('status', BookingStatus::PendingPayment->value)
            ->where('reservation_expires_at', '>', now())
            ->count();

        return [
            'tz' => $tz,
            'recentBookings' => $recentBookings,
            'recentPayments' => $recentPayments,
            'upcomingSessions' => $upcomingSessions,
            'pendingHolds' => $pendingHolds,
        ];
    }
}
