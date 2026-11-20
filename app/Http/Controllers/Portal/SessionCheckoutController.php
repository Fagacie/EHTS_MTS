<?php

namespace App\Http\Controllers\Portal;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\TrainingSession;
use App\Services\Booking\SessionBookingService;
use App\Services\Payment\PaymentOrchestrationService;
use App\Support\PortalPresenter;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SessionCheckoutController extends Controller
{
    public function show(
        Request $request,
        TrainingSession $session,
        SessionBookingService $bookingService,
    ): InertiaResponse|RedirectResponse {
        $session = $bookingService->reconcileSessionCounters($session->id);
        $session->load(['module.category', 'instructors.instructorProfile']);

        $user = $request->user()->loadMissing('customerProfile');

        $existingBooking = $session->bookings()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                BookingStatus::PendingPayment->value,
                BookingStatus::Confirmed->value,
            ])
            ->first();

        if ($existingBooking && $existingBooking->status === BookingStatus::Confirmed) {
            return redirect()
                ->route('portal.bookings.show', $existingBooking)
                ->with('status', 'You already hold a confirmed seat for this training session.');
        }

        $existingHold = ($existingBooking && $existingBooking->isHoldActive()) ? $existingBooking : null;

        return Inertia::render(
            'Checkout/Session',
            PortalPresenter::checkoutSessionData($session, $user, $user->customerProfile, $existingHold),
        );
    }

    public function store(
        Request $request,
        TrainingSession $session,
        SessionBookingService $bookingService,
        PaymentOrchestrationService $paymentService,
    ): RedirectResponse|SymfonyResponse {
        $validated = $request->validate([
            'customer_type' => ['required', 'in:individual,company,company_sponsored'],
            'phone' => ['nullable', 'string', 'max:30'],
            'company_name' => ['nullable', 'required_if:customer_type,company_sponsored,company', 'string', 'max:200'],
            'billing_address_line1' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:100'],
            'billing_state' => ['nullable', 'string', 'max:100'],
            'billing_postcode' => ['nullable', 'string', 'max:20'],
        ]);

        try {
            $booking = $bookingService->reserveSeat($request->user(), $session, [
                'customer_type' => $validated['customer_type'],
                'phone' => $validated['phone'] ?? null,
                'company_name' => $validated['company_name'] ?? null,
                'billing_address' => array_filter([
                    'line1' => $validated['billing_address_line1'] ?? null,
                    'city' => $validated['billing_city'] ?? null,
                    'state' => $validated['billing_state'] ?? null,
                    'postcode' => $validated['billing_postcode'] ?? null,
                    'country' => 'MY',
                ]),
            ]);

            $payment = $paymentService->initiatePaymentForOrder($booking->order);
        } catch (DomainException|RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }

        if ($request->boolean('redirect_gateway') && ! blank($payment->gateway_payment_url)) {
            if ($request->header('X-Inertia')) {
                return Inertia::location($payment->gateway_payment_url);
            }

            return redirect()->away($payment->gateway_payment_url);
        }

        return redirect()
            ->route('portal.bookings.show', $booking)
            ->with(
                'status',
                sprintf(
                    'Seat reserved (%s). Your 15-minute reservation hold is active until %s MYT.',
                    $booking->booking_reference,
                    $booking->reservation_expires_at?->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A'),
                ),
            );
    }
}
