<?php

namespace App\Http\Controllers\Portal;

use App\Enums\EntitlementStatus;
use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModuleEntitlement;
use App\Services\Payment\PaymentOrchestrationService;
use App\Support\PortalPresenter;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ModuleCheckoutController extends Controller
{
    public function show(Request $request, Module $module): InertiaResponse|RedirectResponse
    {
        $module->loadMissing(['category', 'publishedMaterials', 'publishedVideos']);

        abort_unless(
            $module->isPublished() && $module->category?->is_active && $module->is_available_independently && $module->independent_price !== null,
            404,
        );

        $user = $request->user()->loadMissing('customerProfile');

        $alreadyEntitled = ModuleEntitlement::query()
            ->where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('status', EntitlementStatus::Active->value)
            ->exists();

        if ($alreadyEntitled) {
            return redirect()
                ->route('catalogue.modules.show', $module)
                ->with('status', 'You already hold an active lifetime entitlement for this module.');
        }

        return Inertia::render(
            'Checkout/Module',
            PortalPresenter::checkoutModuleData($module, $user, $user->customerProfile),
        );
    }

    public function store(
        Request $request,
        Module $module,
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
            $order = $paymentService->createModulePurchaseOrder($request->user(), $module, [
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

            $payment = $paymentService->initiatePaymentForOrder($order);
        } catch (DomainException|RuntimeException $e) {
            return redirect()
                ->back()
                ->withInput()
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
            ->with('status', 'Module order created. Proceed to Billplz payment below.');
    }
}
