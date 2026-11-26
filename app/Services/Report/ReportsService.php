<?php

namespace App\Services\Report;

use App\Enums\AttendanceReviewStatus;
use App\Enums\AttendanceStatus;
use App\Enums\BookingStatus;
use App\Enums\CertificateStatus;
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
use App\Models\Attendance;
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

        $individualCustomers = $totalCustomers;
        $companyCustomers = 0;

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

        $tz = config('etms.display_timezone', 'Asia/Kuala_Lumpur');

        // 9. Attendance summary & detailed records
        $attendanceRecords = Attendance::query()
            ->with([
                'user:id,name,email',
                'trainingSession:id,session_code,title,starts_at',
            ])
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('created_at', '<=', $end))
            ->latest('created_at')
            ->get();

        $attendanceTotal = $attendanceRecords->count();
        $attendancePresent = $attendanceRecords->filter(
            fn (Attendance $a): bool => $a->attendance_status === AttendanceStatus::Present
        )->count();
        $attendanceAbsent = $attendanceRecords->filter(
            fn (Attendance $a): bool => $a->attendance_status === AttendanceStatus::Absent
        )->count();
        $attendanceVerified = $attendanceRecords->filter(
            fn (Attendance $a): bool => $a->review_status === AttendanceReviewStatus::Verified
        )->count();
        $attendancePendingReview = $attendanceRecords->filter(
            fn (Attendance $a): bool => in_array($a->review_status, [
                AttendanceReviewStatus::Submitted,
                AttendanceReviewStatus::Draft,
                AttendanceReviewStatus::CorrectionRequested,
            ], true)
        )->count();
        $attendanceRate = $attendanceTotal > 0 ? round(($attendancePresent / $attendanceTotal) * 100, 1) : 0.0;

        $attendanceRows = $attendanceRecords->map(fn (Attendance $a): array => [
            'id' => $a->id,
            'session_id' => $a->training_session_id,
            'session_code' => $a->trainingSession?->session_code ?? '—',
            'session_title' => $a->trainingSession?->title ?? '—',
            'trainee_name' => $a->user?->name ?? '—',
            'trainee_email' => $a->user?->email ?? '—',
            'attendance_status' => $a->attendance_status instanceof AttendanceStatus ? $a->attendance_status->label() : ucfirst((string) $a->attendance_status),
            'review_status' => $a->review_status instanceof AttendanceReviewStatus ? $a->review_status->label() : ucfirst((string) $a->review_status),
            'recorded_at' => $a->recorded_at?->timezone($tz)->format('d M Y, H:i') ?? '—',
            'verified_at' => $a->verified_at?->timezone($tz)->format('d M Y, H:i') ?? '—',
            'remarks' => $a->remarks ?? '',
        ])->values()->all();

        // 10. Session capacity & participant lists
        $sessionReports = TrainingSession::query()
            ->with([
                'instructors:id,name',
                'bookings' => fn ($q) => $q->where('status', BookingStatus::Confirmed->value)
                    ->with(['user:id,name,email', 'attendance']),
                'certificates' => fn ($q) => $q->where('status', CertificateStatus::Issued->value),
            ])
            ->when($start, fn ($q) => $q->where('starts_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('starts_at', '<=', $end))
            ->orderByDesc('starts_at')
            ->get()
            ->map(function (TrainingSession $s) use ($tz): array {
                $issuedUserIds = $s->certificates->pluck('user_id')->all();
                $participants = $s->bookings->map(function (Booking $b) use ($issuedUserIds, $tz): array {
                    $att = $b->attendance;

                    return [
                        'booking_id' => $b->id,
                        'booking_reference' => $b->booking_reference,
                        'trainee_name' => $b->user?->name ?? '—',
                        'trainee_email' => $b->user?->email ?? '—',
                        'confirmed_at' => $b->confirmed_at?->timezone($tz)->format('d M Y') ?? '—',
                        'attendance_status' => $att?->attendance_status instanceof AttendanceStatus
                            ? $att->attendance_status->label()
                            : 'Unrecorded',
                        'review_status' => $att?->review_status instanceof AttendanceReviewStatus
                            ? $att->review_status->label()
                            : 'Pending',
                        'certificate_issued' => in_array($b->user_id, $issuedUserIds, true),
                    ];
                })->values()->all();

                $presentCount = collect($participants)->where('attendance_status', 'Present')->count();
                $verifiedCount = collect($participants)->where('review_status', 'Verified')->count();

                return [
                    'id' => $s->id,
                    'session_code' => $s->session_code,
                    'title' => $s->title,
                    'starts_at_label' => $s->starts_at?->timezone($tz)->format('d M Y, H:i') ?? 'TBA',
                    'venue' => collect([$s->venue_name, $s->venue_city])->filter()->implode(', ') ?: 'Online / TBA',
                    'instructors' => $s->instructors->pluck('name')->implode(', ') ?: 'Unassigned',
                    'capacity' => (int) $s->capacity,
                    'confirmed_seats' => (int) $s->confirmed_seats_count,
                    'present_count' => $presentCount,
                    'verified_count' => $verifiedCount,
                    'certificates_count' => $s->certificates->count(),
                    'status' => $s->status instanceof SessionStatus ? $s->status->label() : ucfirst((string) $s->status),
                    'participants' => $participants,
                ];
            })->values()->all();

        // 11. Detailed bookings ledger
        $bookingRows = Booking::query()
            ->with(['user:id,name,email', 'trainingSession:id,session_code,title'])
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('created_at', '<=', $end))
            ->latest('created_at')
            ->limit(150)
            ->get()
            ->map(fn (Booking $b): array => [
                'id' => $b->id,
                'reference' => $b->booking_reference,
                'trainee_name' => $b->user?->name ?? '—',
                'trainee_email' => $b->user?->email ?? '—',
                'session_code' => $b->trainingSession?->session_code ?? '—',
                'session_title' => $b->trainingSession?->title ?? '—',
                'status' => $b->status instanceof BookingStatus ? $b->status->label() : ucfirst((string) $b->status),
                'reserved_at' => $b->reserved_at?->timezone($tz)->format('d M Y, H:i') ?? '—',
                'confirmed_at' => $b->confirmed_at?->timezone($tz)->format('d M Y, H:i') ?? '—',
            ])->values()->all();

        // 12. Detailed payments & refunds ledgers
        $paymentRows = Payment::query()
            ->with(['order:id,order_number,order_type,customer_name,customer_email', 'user:id,name,email'])
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('created_at', '<=', $end))
            ->latest('created_at')
            ->limit(150)
            ->get()
            ->map(fn (Payment $p): array => [
                'id' => $p->id,
                'payment_reference' => $p->payment_reference,
                'order_number' => $p->order?->order_number ?? '—',
                'order_type' => $p->order?->order_type instanceof OrderType ? $p->order->order_type->label() : 'Order',
                'customer_name' => $p->order?->customer_name ?? $p->user?->name ?? '—',
                'customer_email' => $p->order?->customer_email ?? $p->user?->email ?? '—',
                'gateway' => ucfirst((string) ($p->gateway ?: 'manual')),
                'amount_myr' => round((float) $p->amount, 2),
                'status' => $p->status instanceof PaymentStatus ? $p->status->label() : ucfirst((string) $p->status),
                'timestamp' => ($p->paid_at ?? $p->created_at)?->timezone($tz)->format('d M Y, H:i') ?? '—',
            ])->values()->all();

        $refundRows = Refund::query()
            ->with(['order:id,order_number', 'user:id,name,email'])
            ->when($start, fn ($q) => $q->where('requested_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('requested_at', '<=', $end))
            ->latest('requested_at')
            ->limit(150)
            ->get()
            ->map(fn (Refund $r): array => [
                'id' => $r->id,
                'refund_reference' => $r->refund_reference,
                'order_number' => $r->order?->order_number ?? '—',
                'customer_name' => $r->user?->name ?? '—',
                'customer_email' => $r->user?->email ?? '—',
                'amount_myr' => round((float) $r->amount, 2),
                'method' => $r->refund_method?->label() ?? 'Pending',
                'status' => $r->status instanceof RefundStatus ? $r->status->label() : ucfirst((string) $r->status),
                'reason' => $r->reason ?? '',
                'requested_at' => $r->requested_at?->timezone($tz)->format('d M Y, H:i') ?? '—',
                'processed_at' => $r->processed_at?->timezone($tz)->format('d M Y, H:i') ?? '—',
            ])->values()->all();

        // 13. Detailed certificates ledger
        $certificateRows = Certificate::query()
            ->when($start, fn ($q) => $q->where('issued_at', '>=', $start))
            ->when($end, fn ($q) => $q->where('issued_at', '<=', $end))
            ->latest('issued_at')
            ->limit(150)
            ->get()
            ->map(fn (Certificate $c): array => [
                'id' => $c->id,
                'certificate_number' => $c->certificate_number,
                'trainee_name' => $c->trainee_name_snapshot,
                'record_type' => ucfirst((string) $c->record_type),
                'training_code' => $c->training_code_snapshot ?? '—',
                'training_title' => $c->training_title_snapshot,
                'completion_date' => $c->completion_date?->format('d M Y') ?? '—',
                'issued_at' => $c->issued_at?->timezone($tz)->format('d M Y') ?? '—',
                'status' => $c->status instanceof CertificateStatus ? $c->status->label() : ucfirst((string) $c->status),
            ])->values()->all();

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
            'attendance_summary' => [
                'total' => $attendanceTotal,
                'present' => $attendancePresent,
                'absent' => $attendanceAbsent,
                'verified' => $attendanceVerified,
                'pending_review' => $attendancePendingReview,
                'rate_percent' => $attendanceRate,
            ],
            'popular_modules' => $popularModules,
            'instructor_stats' => $instructorStats,
            'session_reports' => $sessionReports,
            'booking_rows' => $bookingRows,
            'attendance_rows' => $attendanceRows,
            'payment_rows' => $paymentRows,
            'refund_rows' => $refundRows,
            'certificate_rows' => $certificateRows,
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
            'attendance' => [
                'filename' => "etms-attendance-report-{$stamp}.csv",
                'headers' => ['Session Code', 'Session Title', 'Trainee Name', 'Trainee Email', 'Attendance Status', 'Review Status', 'Recorded At (MYT)', 'Verified At (MYT)', 'Remarks'],
                'rows' => Attendance::query()
                    ->with(['user:id,name,email', 'trainingSession:id,session_code,title'])
                    ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
                    ->when($end, fn ($q) => $q->where('created_at', '<=', $end))
                    ->latest('created_at')
                    ->get()
                    ->map(fn (Attendance $a): array => [
                        $a->trainingSession?->session_code ?? '—',
                        $a->trainingSession?->title ?? '—',
                        $a->user?->name ?? '—',
                        $a->user?->email ?? '—',
                        $a->attendance_status instanceof AttendanceStatus ? $a->attendance_status->label() : (string) $a->attendance_status,
                        $a->review_status instanceof AttendanceReviewStatus ? $a->review_status->label() : (string) $a->review_status,
                        $a->recorded_at?->timezone($tz)->format('Y-m-d H:i:s') ?? '',
                        $a->verified_at?->timezone($tz)->format('Y-m-d H:i:s') ?? '',
                        $a->remarks ?? '',
                    ])->all(),
            ],
            'sessions' => [
                'filename' => "etms-sessions-participants-report-{$stamp}.csv",
                'headers' => ['Session Code', 'Session Title', 'Start Date (MYT)', 'Venue', 'Instructors', 'Capacity', 'Confirmed Seats', 'Status'],
                'rows' => TrainingSession::query()
                    ->with('instructors:id,name')
                    ->when($start, fn ($q) => $q->where('starts_at', '>=', $start))
                    ->when($end, fn ($q) => $q->where('starts_at', '<=', $end))
                    ->orderByDesc('starts_at')
                    ->get()
                    ->map(fn (TrainingSession $s): array => [
                        $s->session_code,
                        $s->title,
                        $s->starts_at?->timezone($tz)->format('Y-m-d H:i:s') ?? '',
                        collect([$s->venue_name, $s->venue_city])->filter()->implode(', '),
                        $s->instructors->pluck('name')->implode(', '),
                        $s->capacity,
                        $s->confirmed_seats_count,
                        $s->status instanceof SessionStatus ? $s->status->label() : (string) $s->status,
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
