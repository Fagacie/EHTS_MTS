<?php

namespace App\Http\Controllers\Portal;

use App\Enums\EntitlementStatus;
use App\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Models\SessionDocumentEntitlement;
use App\Models\TrainingSession;
use App\Services\Payment\PaymentOrchestrationService;
use App\Support\PortalPresenter;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SessionDocumentCheckoutController extends Controller
{
    public function show(Request $request, TrainingSession $session): InertiaResponse|RedirectResponse
    {
        $session->loadMissing('mainDocumentMaterial');

        abort_unless(
            $session->status === SessionStatus::Published
                && $session->is_available_independently
                && $session->independent_price !== null
                && $session->mainDocumentMaterial?->status?->value === 'published',
            404,
        );

        $user = $request->user()->loadMissing('customerProfile');
        $alreadyEntitled = SessionDocumentEntitlement::query()
            ->where('user_id', $user->id)
            ->where('session_material_id', $session->main_document_material_id)
            ->where('status', EntitlementStatus::Active->value)
            ->exists();

        if ($alreadyEntitled) {
            return redirect()
                ->route('sessions.show', $session)
                ->with('status', 'You already own this session document.');
        }

        return Inertia::render(
            'Checkout/Module',
            PortalPresenter::checkoutSessionDocumentData($session, $user, $user->customerProfile),
        );
    }

    public function store(
        Request $request,
        TrainingSession $session,
        PaymentOrchestrationService $paymentService,
    ): RedirectResponse|SymfonyResponse {
        $validated = $request->validate([
            'phone' => ['nullable', 'string', 'max:30'],
            'billing_address_line1' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:100'],
            'billing_state' => ['nullable', 'string', 'max:100'],
            'billing_postcode' => ['nullable', 'string', 'max:20'],
        ]);

        try {
            $order = $paymentService->createSessionDocumentPurchaseOrder($request->user(), $session, [
                'phone' => $validated['phone'] ?? null,
                'billing_address' => array_filter([
                    'line1' => $validated['billing_address_line1'] ?? null,
                    'city' => $validated['billing_city'] ?? null,
                    'state' => $validated['billing_state'] ?? null,
                    'postcode' => $validated['billing_postcode'] ?? null,
                    'country' => 'MY',
                ]),
            ]);

            $payment = $paymentService->initiatePaymentForOrder($order);
        } catch (DomainException|RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        if (! blank($payment->gateway_payment_url)) {
            return $request->header('X-Inertia')
                ? Inertia::location($payment->gateway_payment_url)
                : redirect()->away($payment->gateway_payment_url);
        }

        return redirect()
            ->route('portal.orders.show', $order)
            ->with('status', 'Document order created. Proceed to Billplz payment below.');
    }
}
