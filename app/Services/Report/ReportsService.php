<?php

namespace App\Services\Report;

use App\Enums\BookingStatus;
use App\Enums\CertificateStatus;
use App\Enums\CustomerType;
use App\Enums\EntitlementStatus;
use App\Enums\ModuleStatus;
use App\Enums\OrderItemType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\RoleSlug;
use App\Enums\SessionStatus;
use App\Enums\UserStatus;
use App\Models\Booking;
use App\Models\Certificate;
use App\Models\CustomerProfile;
use App\Models\Module;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportsService
{
    /**
     * Resolve UTC start/end DateTime bounds from optional MYT date strings (`YYYY-MM-DD`).
     *
     * @return array{from_myt: ?string, to_myt: ?string, start_utc: ?Carbon, end_utc: ?Carbon}
     */
    public function resolveDateRange(?string $fromDate = null, ?string $toDate = null): array
    {
        $tz = config('etms.display_timezone', 'Asia/Kuala_Lumpur');

        $fromMyt = is_string($fromDate) && trim($fromDate) !== '' ? trim($fromDate) : null;
        $toMyt = is_string($toDate) && trim($toDate) !== '' ? trim($toDate) : null;

        $startUtc = $fromMyt !== null ? Carbon::parse($fromMyt, $tz)->startOfDay()->utc() : null;
        $endUtc = $toMyt !== null ? Carbon::parse($toMyt, $tz)->endOfDay()->utc() : null;

        if ($startUtc !== null && $endUtc !== null && $startUtc->gt($endUtc)) {
            [$startUtc, $endUtc] = [$endUtc->copy()->startOfDay()->utc(), $startUtc->copy()->endOfDay()->utc()];
            [$fromMyt, $toMyt] = [$toMyt, $fromMyt];
        }

        return [
            'from_myt' => $fromMyt,
            'to_myt' => $toMyt,
            'start_utc' => $startUtc,
            'end_utc' => $endUtc,
        ];
    }

    /**
     * Compile the complete Stage 9 executive & operational report dataset.
     *
     * @return array<string, mixed>
     */
    public function generate(?string $fromDate = null, ?string $toDate = null): array
    {
        $range = $this->resolveDateRange($fromDate, $toDate);
        $start = $range['start_utc'];
        $end = $range['end_utc'];

        // 1. Customer statistics
        $customersBase = User::query()
            ->whereHas('roles', fn ($q) => $q->where('slug', RoleSlug::Customer->value));

        $totalCustomers = (clone $customersBase)->count();
        $activeCustomers = (clone $customersBase)->where('status', UserStatus::Active->value)->count();
        $newCustomers = (clone $customersBase)
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('created_at', '<=', $end))
            ->count();

        $individualCustomers = CustomerProfile::query()
            ->where('customer_type', CustomerType::Individual->value)
            ->count();
        $companyCustomers = CustomerProfile::query()
            ->where('customer_type', CustomerType::Company->value)
            ->count();

        // 2. Modules & Published Sessions
        $totalModules = Module::query()->count();
        $publishedModules = Module::query()->where('status', ModuleStatus::Published->value)->count();
        $independentModules = Module::query()
            ->where('status', ModuleStatus::Published->value)
            ->where('is_available_independently', true)
            ->count();

        $sessionsBase = TrainingSession::query()
            ->when($start, fn ($q) => $q->where('starts_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('starts_at', '<=', $end));

        $totalSessions = (clone $sessionsBase)->count();
        $publishedSessions = (clone $sessionsBase)
            ->whereIn('status', [SessionStatus::Published->value, SessionStatus::Full->value, SessionStatus::RegistrationClosed->value])
            ->count();
        $completedSessions = (clone $sessionsBase)->where('status', SessionStatus::Completed->value)->count();
        $cancelledSessions = (clone $sessionsBase)->where('status', SessionStatus::Cancelled->value)->count();

        // 3. Booking & participant statistics
        $bookingsQuery = Booking::query()
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('created_at', '<=', $end));

        $totalBookings = (clone $bookingsQuery)->count();
        $confirmedBookings = (clone $bookingsQuery)->where('status', BookingStatus::Confirmed->value)->count();
        $pendingBookings = (clone $bookingsQuery)->where('status', BookingStatus::PendingPayment->value)->count();
        $expiredBookings = (clone $bookingsQuery)->where('status', BookingStatus::Expired->value)->count();
        $cancelledBookings = (clone $bookingsQuery)->where('status', BookingStatus::Cancelled->value)->count();
        $refundedBookings = (clone $bookingsQuery)->where('status', BookingStatus::Refunded->value)->count();
        $uniqueParticipants = (clone $bookingsQuery)
            ->where('status', BookingStatus::Confirmed->value)
            ->distinct('user_id')
            ->count('user_id');

        // 4. Revenue & payment summaries (MYR)
        $paidOrderStatuses = [
            OrderStatus::Paid->value,
            OrderStatus::PartiallyRefunded->value,
            OrderStatus::Refunded->value,
        ];

        $paidOrdersQuery = Order::query()
            ->whereIn('status', $paidOrderStatuses)
            ->when($start, fn ($q) => $q->where('paid_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('paid_at', '<=', $end));

        $paidOrdersCount = (clone $paidOrdersQuery)->count();
        $grossRevenueMyr = round((float) (clone $paidOrdersQuery)->sum('total_amount'), 2);
        $sessionRevenueMyr = round((float) (clone $paidOrdersQuery)->where('order_type', OrderType::SessionBooking->value)->sum('total_amount'), 2);
        $moduleRevenueMyr = round((float) (clone $paidOrdersQuery)->where('order_type', OrderType::ModulePurchase->value)->sum('total_amount'), 2);

        $paymentsQuery = Payment::query()
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('created_at', '<=', $end));

        $successfulPaymentsCount = Payment::query()
            ->whereIn('status', [
                PaymentStatus::Successful->value,
                PaymentStatus::PartiallyRefunded->value,
                PaymentStatus::Refunded->value,
            ])
            ->when($start, fn ($q) => $q->where('paid_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('paid_at', '<=', $end))
            ->count();

        $failedPaymentsCount = (clone $paymentsQuery)
            ->whereIn('status', [PaymentStatus::Failed->value, PaymentStatus::Cancelled->value])
            ->count();
        $pendingPaymentsCount = (clone $paymentsQuery)
            ->where('status', PaymentStatus::Pending->value)
            ->count();

        // 5. Refund summaries (MYR)
        $completedRefundsQuery = Refund::query()
            ->where('status', RefundStatus::Completed->value)
            ->when($start, fn ($q) => $q->where('processed_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('processed_at', '<=', $end));

        $completedRefundsCount = (clone $completedRefundsQuery)->count();
        $completedRefundsMyr = round((float) (clone $completedRefundsQuery)->sum('amount'), 2);

        $pendingRefundsQuery = Refund::query()
            ->whereIn('status', [
                RefundStatus::Requested->value,
                RefundStatus::Approved->value,
                RefundStatus::Processing->value,
            ])
            ->when($start, fn ($q) => $q->where('requested_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('requested_at', '<=', $end));

        $pendingRefundsCount = (clone $pendingRefundsQuery)->count();
        $pendingRefundsMyr = round((float) (clone $pendingRefundsQuery)->sum('amount'), 2);
        $rejectedRefundsCount = Refund::query()
            ->where('status', RefundStatus::Rejected->value)
            ->when($start, fn ($q) => $q->where('updated_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('updated_at', '<=', $end))
            ->count();

        $netRevenueMyr = round(max(0.0, $grossRevenueMyr - $completedRefundsMyr), 2);

        // 6. Most popular training modules
        $popularModules = $this->computePopularModules($start, $end, $paidOrderStatuses);

        // 7. Instructor & session statistics
        $instructorStats = $this->computeInstructorStats($start, $end);

        // 8. Certificate issuance statistics
        $certificatesIssued = Certificate::query()
            ->where('status', CertificateStatus::Issued->value)
            ->when($start, fn ($q) => $q->where('issued_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('issued_at', '<=', $end))
            ->count();

        $certificatesRevoked = Certificate::query()
            ->where('status', CertificateStatus::Revoked->value)
            ->when($start, fn ($q) => $q->where('revoked_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('revoked_at', '<=', $end))
            ->count();

        $sessionCertificatesCount = Certificate::query()
            ->where('record_type', 'session')
            ->where('status', CertificateStatus::Issued->value)
            ->when($start, fn ($q) => $q->where('issued_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('issued_at', '<=', $end))
            ->count();

        $moduleCertificatesCount = Certificate::query()
            ->where('record_type', 'module')
            ->where('status', CertificateStatus::Issued->value)
            ->when($start, fn ($q) => $q->where('issued_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('issued_at', '<=', $end))
            ->count();

        return [
            'filters' => [
                'from' => $range['from_myt'],
                'to' => $range['to_myt'],
            ],
            'customers' => [
                'total' => $totalCustomers,
                'active' => $activeCustomers,
                'new_in_period' => $newCustomers,
                'individual' => $individualCustomers,
                'company' => $companyCustomers,
            ],
            'catalogue' => [
                'total_modules' => $totalModules,
                'published_modules' => $publishedModules,
                'independent_modules' => $independentModules,
                'total_sessions' => $totalSessions,
                'published_sessions' => $publishedSessions,
                'completed_sessions' => $completedSessions,
                'cancelled_sessions' => $cancelledSessions,
            ],
            'bookings' => [
                'total' => $totalBookings,
                'confirmed' => $confirmedBookings,
                'pending' => $pendingBookings,
                'expired' => $expiredBookings,
                'cancelled' => $cancelledBookings,
                'refunded' => $refundedBookings,
                'unique_participants' => $uniqueParticipants,
            ],
            'revenue' => [
                'paid_orders_count' => $paidOrdersCount,
                'gross_myr' => $grossRevenueMyr,
                'session_myr' => $sessionRevenueMyr,
                'module_myr' => $moduleRevenueMyr,
                'net_myr' => $netRevenueMyr,
                'successful_payments' => $successfulPaymentsCount,
                'failed_payments' => $failedPaymentsCount,
                'pending_payments' => $pendingPaymentsCount,
            ],
            'refunds' => [
                'completed_count' => $completedRefundsCount,
                'completed_myr' => $completedRefundsMyr,
                'pending_count' => $pendingRefundsCount,
                'pending_myr' => $pendingRefundsMyr,
                'rejected_count' => $rejectedRefundsCount,
            ],
            'certificates' => [
                'issued' => $certificatesIssued,
                'revoked' => $certificatesRevoked,
                'session_type' => $sessionCertificatesCount,
                'module_type' => $moduleCertificatesCount,
            ],
            'popular_modules' => $popularModules,
            'instructor_stats' => $instructorStats,
        ];
    }

    /**
     * Generate rows and headers for CSV exports.
     *
     * @return array{filename: string, headers: list<string>, rows: list<list<string|int|float>>}
     */
    public function buildCsvDataset(string $dataset, ?string $fromDate = null, ?string $toDate = null): array
    {
        $range = $this->resolveDateRange($fromDate, $toDate);
        $start = $range['start_utc'];
        $end = $range['end_utc'];
        $tz = config('etms.display_timezone', 'Asia/Kuala_Lumpur');
        $stamp = now($tz)->format('Ymd-His');

        return match ($dataset) {
            'bookings' => [
                'filename' => "etms-bookings-report-{$stamp}.csv",
                'headers' => ['Booking Reference', 'Trainee Name', 'Trainee Email', 'Session Code', 'Session Title', 'Status', 'Reserved At (MYT)', 'Confirmed At (MYT)'],
                'rows' => Booking::query()
                    ->with(['user:id,name,email', 'trainingSession:id,session_code,title'])
                    ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
                    ->when($end, fn ($q) => $q->where('created_at', '<=', $end))
                    ->latest('created_at')
                    ->get()
                    ->map(fn (Booking $b): array => [
                        $b->booking_reference,
                        $b->user?->name ?? '—',
                        $b->user?->email ?? '—',
                        $b->trainingSession?->session_code ?? '—',
                        $b->trainingSession?->title ?? '—',
                        $b->status instanceof BookingStatus ? $b->status->label() : (string) $b->status,
                        $b->reserved_at?->timezone($tz)->format('Y-m-d H:i:s') ?? '',
                        $b->confirmed_at?->timezone($tz)->format('Y-m-d H:i:s') ?? '',
                    ])->all(),
            ],
            'revenue' => [
                'filename' => "etms-revenue-orders-report-{$stamp}.csv",
                'headers' => ['Order Number', 'Order Type', 'Customer Name', 'Customer Email', 'Currency', 'Amount (MYR)', 'Status', 'Paid At (MYT)'],
                'rows' => Order::query()
                    ->whereIn('status', [OrderStatus::Paid->value, OrderStatus::PartiallyRefunded->value, OrderStatus::Refunded->value])
                    ->when($start, fn ($q) => $q->where('paid_at', '>=', $start))
                    ->when($end, fn ($q) => $q->where('paid_at', '<=', $end))
                    ->latest('paid_at')
                    ->get()
                    ->map(fn (Order $o): array => [
                        $o->order_number,
                        $o->order_type instanceof OrderType ? $o->order_type->label() : (string) $o->order_type,
                        $o->customer_name,
                        $o->customer_email,
                        $o->currency ?: 'MYR',
                        number_format((float) $o->total_amount, 2, '.', ''),
                        $o->status instanceof OrderStatus ? $o->status->label() : (string) $o->status,
                        $o->paid_at?->timezone($tz)->format('Y-m-d H:i:s') ?? '',
                    ])->all(),
            ],
            'refunds' => [
                'filename' => "etms-refunds-report-{$stamp}.csv",
                'headers' => ['Refund Reference', 'Order Number', 'Customer Email', 'Currency', 'Amount (MYR)', 'Method', 'Status', 'Reason', 'Requested At (MYT)', 'Processed At (MYT)'],
                'rows' => Refund::query()
                    ->with(['order:id,order_number', 'user:id,email'])
                    ->when($start, fn ($q) => $q->where('requested_at', '>=', $start))
                    ->when($end, fn ($q) => $q->where('requested_at', '<=', $end))
                    ->latest('requested_at')
                    ->get()
                    ->map(fn (Refund $r): array => [
                        $r->refund_reference,
                        $r->order?->order_number ?? '—',
                        $r->user?->email ?? '—',
                        $r->currency ?: 'MYR',
                        number_format((float) $r->amount, 2, '.', ''),
                        $r->refund_method?->label() ?? 'Pending',
                        $r->status instanceof RefundStatus ? $r->status->label() : (string) $r->status,
                        $r->reason,
                        $r->requested_at?->timezone($tz)->format('Y-m-d H:i:s') ?? '',
                        $r->processed_at?->timezone($tz)->format('Y-m-d H:i:s') ?? '',
                    ])->all(),
            ],
            'popular_modules' => [
                'filename' => "etms-popular-modules-report-{$stamp}.csv",
                'headers' => ['Module Code', 'Module Title', 'Category', 'Status', 'Confirmed Session Bookings', 'Independent Purchases', 'Active Entitlements', 'Gross Revenue (MYR)'],
                'rows' => collect($this->generate($fromDate, $toDate)['popular_modules'])
                    ->map(fn (array $row): array => [
                        $row['code'],
                        $row['title'],
                        $row['category'],
                        $row['status'],
                        $row['confirmed_bookings'],
                        $row['independent_purchases'],
                        $row['active_entitlements'],
                        number_format((float) $row['revenue_myr'], 2, '.', ''),
                    ])->all(),
            ],
            'instructors' => [
                'filename' => "etms-instructor-statistics-{$stamp}.csv",
                'headers' => ['Instructor Name', 'Email', 'Assigned Sessions', 'Completed Sessions', 'Upcoming Sessions', 'Confirmed Participants', 'Issued Certificates'],
                'rows' => collect($this->generate($fromDate, $toDate)['instructor_stats'])
                    ->map(fn (array $row): array => [
                        $row['name'],
                        $row['email'],
                        $row['assigned_sessions'],
                        $row['completed_sessions'],
                        $row['upcoming_sessions'],
                        $row['confirmed_participants'],
                        $row['certificates_issued'],
                    ])->all(),
            ],
            'certificates' => [
                'filename' => "etms-certificates-report-{$stamp}.csv",
                'headers' => ['Certificate Number', 'Trainee Name', 'Record Type', 'Training Code', 'Training Title', 'Completion Date', 'Issued At (MYT)', 'Status', 'Revocation Reason'],
                'rows' => Certificate::query()
                    ->when($start, fn ($q) => $q->where('issued_at', '>=', $start))
                    ->when($end, fn ($q) => $q->where('issued_at', '<=', $end))
                    ->latest('issued_at')
                    ->get()
                    ->map(fn (Certificate $c): array => [
                        $c->certificate_number,
                        $c->trainee_name_snapshot,
                        ucfirst($c->record_type),
                        $c->training_code_snapshot,
                        $c->training_title_snapshot,
                        $c->completion_date?->format('Y-m-d') ?? '',
                        $c->issued_at?->timezone($tz)->format('Y-m-d H:i:s') ?? '',
                        $c->status instanceof CertificateStatus ? $c->status->label() : (string) $c->status,
                        $c->revocation_reason ?? '',
                    ])->all(),
            ],
            default => abort(404, 'Unknown report export dataset.'),
        };
    }

    /**
     * @param  list<string>  $paidOrderStatuses
     * @return list<array<string, mixed>>
     */
    protected function computePopularModules(?Carbon $start, ?Carbon $end, array $paidOrderStatuses): array
    {
        $modules = Module::query()->with('category:id,name')->get();

        $bookings = Booking::query()
            ->where('status', BookingStatus::Confirmed->value)
            ->when($start, fn ($q) => $q->where('confirmed_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('confirmed_at', '<=', $end))
            ->with('trainingSession:id,module_id')
            ->get();

        $sessionBookingsByModule = [];
        foreach ($bookings as $booking) {
            $moduleId = $booking->trainingSession?->module_id;
            if ($moduleId) {
                $sessionBookingsByModule[$moduleId] = ($sessionBookingsByModule[$moduleId] ?? 0) + 1;
            }
        }

        $paidItems = OrderItem::query()
            ->whereHas('order', function ($q) use ($paidOrderStatuses, $start, $end): void {
                $q->whereIn('status', $paidOrderStatuses)
                    ->when($start, fn ($oq) => $oq->where('paid_at', '>=', $start))
                    ->when($end, fn ($oq) => $oq->where('paid_at', '<=', $end));
            })
            ->with('trainingSession:id,module_id')
            ->get();

        $independentCounts = [];
        $revenueByModule = [];

        foreach ($paidItems as $item) {
            $moduleId = $item->module_id ?: $item->trainingSession?->module_id;
            if (! $moduleId) {
                continue;
            }

            $revenueByModule[$moduleId] = ($revenueByModule[$moduleId] ?? 0.0) + (float) $item->line_total;

            if ($item->item_type === OrderItemType::Module) {
                $independentCounts[$moduleId] = ($independentCounts[$moduleId] ?? 0) + (int) $item->quantity;
            }
        }

        $entitlementsByModule = Module::query()
            ->withCount(['entitlements as active_entitlements_count' => fn ($q) => $q->where('status', EntitlementStatus::Active->value)])
            ->pluck('active_entitlements_count', 'id')
            ->all();

        $rows = [];
        foreach ($modules as $module) {
            $confirmedCount = $sessionBookingsByModule[$module->id] ?? 0;
            $independentCount = $independentCounts[$module->id] ?? 0;
            $revenueMyr = round($revenueByModule[$module->id] ?? 0.0, 2);

            $rows[] = [
                'id' => $module->id,
                'code' => $module->code,
                'title' => $module->title,
                'category' => $module->category?->name ?? 'Uncategorised',
                'status' => $module->status instanceof ModuleStatus ? $module->status->label() : ucfirst((string) $module->status),
                'confirmed_bookings' => $confirmedCount,
                'independent_purchases' => $independentCount,
                'active_entitlements' => (int) ($entitlementsByModule[$module->id] ?? 0),
                'total_enrolments' => $confirmedCount + $independentCount,
                'revenue_myr' => $revenueMyr,
            ];
        }

        usort($rows, static function (array $a, array $b): int {
            return ($b['total_enrolments'] <=> $a['total_enrolments'])
                ?: ($b['revenue_myr'] <=> $a['revenue_myr'])
                ?: strcmp($a['code'], $b['code']);
        });

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function computeInstructorStats(?Carbon $start, ?Carbon $end): array
    {
        $instructors = User::query()
            ->whereHas('roles', fn ($q) => $q->where('slug', RoleSlug::Instructor->value))
            ->with([
                'instructorProfile:id,user_id,display_name',
                'assignedSessions' => function ($q) use ($start, $end): void {
                    $q->when($start, fn ($sq) => $sq->where('starts_at', '>=', $start))
                        ->when($end, fn ($sq) => $sq->where('starts_at', '<=', $end));
                },
            ])
            ->orderBy('name')
            ->get();

        $now = now();
        $rows = [];

        foreach ($instructors as $instructor) {
            /** @var Collection<int, TrainingSession> $sessions */
            $sessions = $instructor->assignedSessions;
            $sessionIds = $sessions->pluck('id')->all();

            $completed = $sessions->where('status', SessionStatus::Completed)->count();
            $upcoming = $sessions->filter(function (TrainingSession $s) use ($now): bool {
                return in_array($s->status, [SessionStatus::Published, SessionStatus::Full, SessionStatus::RegistrationClosed], true)
                    && $s->starts_at?->gt($now);
            })->count();

            $confirmedParticipants = (int) $sessions
                ->reject(fn (TrainingSession $s): bool => $s->status === SessionStatus::Cancelled)
                ->sum('confirmed_seats_count');

            $certsIssued = empty($sessionIds)
                ? 0
                : Certificate::query()
                    ->where('status', CertificateStatus::Issued->value)
                    ->whereIn('training_session_id', $sessionIds)
                    ->count();

            $rows[] = [
                'id' => $instructor->id,
                'name' => $instructor->instructorProfile?->display_name ?: $instructor->name,
                'email' => $instructor->email,
                'assigned_sessions' => $sessions->count(),
                'completed_sessions' => $completed,
                'upcoming_sessions' => $upcoming,
                'confirmed_participants' => $confirmedParticipants,
                'certificates_issued' => $certsIssued,
            ];
        }

        usort($rows, static fn (array $a, array $b): int => $b['assigned_sessions'] <=> $a['assigned_sessions']);

        return $rows;
    }
}
