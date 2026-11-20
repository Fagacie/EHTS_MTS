<?php

namespace App\Http\Controllers\Portal;

use App\Enums\BookingStatus;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Booking\SessionBookingService;
use App\Services\Payment\PaymentOrchestrationService;
use App\Support\PortalPresenter;
use App\Support\PublicCataloguePresenter;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class OrderController extends Controller
{
    public function index(Request $request, SessionBookingService $bookingService): InertiaResponse
    {
        $user = $request->user();

        $pendingSessionIds = Booking::query()
            ->where('user_id', $user->id)
            ->where('status', BookingStatus::PendingPayment->value)
            ->pluck('training_session_id')
            ->unique();

        foreach ($pendingSessionIds as $sessionId) {
            $bookingService->reconcileSessionCounters((int) $sessionId);
        }

        $statusFilter = trim((string) $request->query('status', ''));
        $typeFilter = trim((string) $request->query('type', ''));

        $ordersQuery = Order::query()
            ->with(['items', 'payments', 'bookings.trainingSession', 'moduleEntitlements.module'])
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        if ($statusFilter !== '' && OrderStatus::tryFrom($statusFilter) !== null) {
            $ordersQuery->where('status', $statusFilter);
        }

        if ($typeFilter !== '' && OrderType::tryFrom($typeFilter) !== null) {
            $ordersQuery->where('order_type', $typeFilter);
        }

        $paginator = $ordersQuery->paginate(10)->withQueryString();

        return Inertia::render('Portal/Orders/Index', [
            'orders' => $paginator->getCollection()
                ->map(static fn (Order $order): array => PortalPresenter::orderListItem($order))
                ->values()
                ->all(),
            'pagination' => PublicCataloguePresenter::paginationMeta($paginator),
            'statusFilter' => $statusFilter,
            'typeFilter' => $typeFilter,
            'statuses' => array_map(
                static fn (OrderStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
                OrderStatus::cases(),
            ),
            'orderTypes' => array_map(
                static fn (OrderType $orderType): array => [
                    'value' => $orderType->value,
                    'label' => $orderType->label(),
                ],
                OrderType::cases(),
            ),
        ]);
    }

    public function show(Request $request, Order $order, SessionBookingService $bookingService): InertiaResponse
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        if ($order->order_type === OrderType::SessionBooking) {
            $sessionId = $order->bookings()->value('training_session_id');
            if ($sessionId) {
                $bookingService->reconcileSessionCounters((int) $sessionId);
                $order->refresh();
            }
        }

        $order->load([
            'items.trainingSession',
            'items.module',
            'payments.refunds',
            'bookings.trainingSession',
            'moduleEntitlements.module',
            'refunds',
        ]);

        return Inertia::render('Portal/Orders/Show', [
            'order' => PortalPresenter::orderDetail($order),
        ]);
    }

    public function pay(
        Request $request,
        Order $order,
        PaymentOrchestrationService $paymentService,
    ): RedirectResponse|SymfonyResponse {
        abort_unless($order->user_id === $request->user()->id, 403);

        try {
            $payment = $paymentService->initiatePaymentForOrder($order);
        } catch (DomainException|RuntimeException $e) {
            return redirect()
                ->route('portal.orders.show', $order)
                ->with('error', $e->getMessage());
        }

        if (! blank($payment->gateway_payment_url)) {
            if ($request->header('X-Inertia')) {
                return Inertia::location($payment->gateway_payment_url);
            }

            return redirect()->away($payment->gateway_payment_url);
        }

        return redirect()
            ->route('portal.orders.show', $order)
            ->with('status', 'Payment attempt created ('.$payment->payment_reference.').');
    }

    /**
     * Customer browser return page after Billplz checkout (`/checkout/return`, TRN-06).
     * Strictly read-only: NEVER trusts browser query parameters as payment authority (Section 6.2).
     */
    public function returnPage(Request $request, SessionBookingService $bookingService): InertiaResponse
    {
        $user = $request->user();
        $orderNumber = trim((string) $request->query('order', ''));
        $billplzParams = $request->query('billplz', []);
        $billId = is_array($billplzParams) && isset($billplzParams['id'])
            ? (string) $billplzParams['id']
            : trim((string) $request->query('bill_id', ''));

        $order = null;
        $payment = null;

        if ($orderNumber !== '') {
            $order = Order::query()
                ->where('user_id', $user->id)
                ->where('order_number', $orderNumber)
                ->first();
        }

        if ($order === null && $billId !== '') {
            $payment = Payment::query()
                ->where('user_id', $user->id)
                ->where('gateway_bill_id', $billId)
                ->first();
            $order = $payment?->order;
        }

        abort_if($order === null, 404);

        if ($order->order_type === OrderType::SessionBooking) {
            $sessionId = $order->bookings()->value('training_session_id');
            if ($sessionId) {
                $bookingService->reconcileSessionCounters((int) $sessionId);
                $order->refresh();
            }
        }

        $order->load(['items', 'payments', 'bookings.trainingSession', 'moduleEntitlements.module']);
        $latestPayment = $payment ?? $order->payments->first();
        $redirectReportedPaid = is_array($billplzParams) && (($billplzParams['paid'] ?? '') === 'true');

        return Inertia::render(
            'Checkout/Return',
            PortalPresenter::checkoutReturnData($order, $latestPayment, $redirectReportedPaid, $request->fullUrl()),
        );
    }
}
