<?php

namespace App\Filament\Widgets;

use App\Enums\BookingStatus;
use App\Enums\CertificateStatus;
use App\Enums\CustomerType;
use App\Enums\ModuleStatus;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\OrderStatus;
use App\Enums\RefundStatus;
use App\Enums\RoleSlug;
use App\Enums\SessionStatus;
use App\Enums\UserStatus;
use App\Models\Booking;
use App\Models\Certificate;
use App\Models\CustomerProfile;
use App\Models\Module;
use App\Models\NotificationDelivery;
use App\Models\Order;
use App\Models\Refund;
use App\Models\TrainingSession;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class FoundationOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static ?bool $isLazyLoaded = false;

    protected function getStats(): array
    {
        $activeUsers = User::query()
            ->where('status', UserStatus::Active->value)
            ->count();

        $customerCount = User::query()
            ->whereHas('roles', fn ($q) => $q->where('slug', RoleSlug::Customer->value))
            ->count();

        $companyCustomers = CustomerProfile::query()
            ->where('customer_type', CustomerType::Company->value)
            ->count();

        $publishedModules = Module::query()
            ->where('status', ModuleStatus::Published->value)
            ->count();

        $upcomingSessions = TrainingSession::query()
            ->whereIn('status', [
                SessionStatus::Published->value,
                SessionStatus::Full->value,
                SessionStatus::RegistrationClosed->value,
            ])
            ->where('starts_at', '>', now())
            ->count();

        $confirmedBookings = Booking::query()
            ->where('status', BookingStatus::Confirmed->value)
            ->count();

        $grossRevenue = (float) Order::query()
            ->whereIn('status', [
                OrderStatus::Paid->value,
                OrderStatus::PartiallyRefunded->value,
                OrderStatus::Refunded->value,
            ])
            ->sum('total_amount');

        $completedRefunds = Schema::hasTable('refunds')
            ? (float) Refund::query()->where('status', RefundStatus::Completed->value)->sum('amount')
            : 0.0;

        $netRevenue = max(0.0, $grossRevenue - $completedRefunds);

        $issuedCertificates = Schema::hasTable('certificates')
            ? Certificate::query()->where('status', CertificateStatus::Issued->value)->count()
            : 0;

        $sentDeliveries = Schema::hasTable('notification_deliveries')
            ? NotificationDelivery::query()->where('status', NotificationDeliveryStatus::Sent->value)->count()
            : 0;
        $failedDeliveries = Schema::hasTable('notification_deliveries')
            ? NotificationDelivery::query()->where('status', NotificationDeliveryStatus::Failed->value)->count()
            : 0;

        return [
            Stat::make('Net Revenue (MYR)', 'MYR '.number_format($netRevenue, 2))
                ->description('Gross MYR '.number_format($grossRevenue, 2).' | Refunds MYR '.number_format($completedRefunds, 2))
                ->color('success'),
            Stat::make('Registered Customers', (string) $customerCount)
                ->description("{$activeUsers} active users | {$companyCustomers} corporate accounts")
                ->color('primary'),
            Stat::make('Sessions & Modules', "{$upcomingSessions} upcoming / {$publishedModules} modules")
                ->description("{$confirmedBookings} confirmed physical bookings")
                ->color('info'),
            Stat::make('Certificates & Emails', "{$issuedCertificates} certificates")
                ->description("{$sentDeliveries} emails sent | {$failedDeliveries} failed")
                ->color('warning'),
        ];
    }
}
