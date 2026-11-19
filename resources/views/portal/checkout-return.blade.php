<x-layouts.portal title="Payment Status — {{ $order->order_number }}">
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <a
                href="{{ route('portal.orders.index') }}"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-portal-muted transition hover:text-portal-primary"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 18-6-6 6-6" />
                </svg>
                <span>Back to Orders &amp; Payments</span>
            </a>

            <x-portal.status-badge :status="$order->status" :label="$order->status->label()" />
        </div>

        <x-portal.page-header
            :title="'Order '.$order->order_number"
            :description="'Payment status update for your '.$order->order_type->label().'.'"
        />

        @if ($order->status === \App\Enums\OrderStatus::Paid)
            <x-portal.alert variant="success" title="Payment Confirmed">
                <p>
                    Thank you! Your payment has been verified and confirmed. Your booking or learning module access is now active.
                </p>
                <x-slot:action>
                    <div class="flex flex-wrap gap-2.5">
                        @if ($order->order_type === \App\Enums\OrderType::ModulePurchase)
                            <x-portal.button :href="route('portal.modules.index')" variant="primary" size="sm">
                                Go to My Learning
                            </x-portal.button>
                        @else
                            <x-portal.button :href="route('portal.bookings.index')" variant="primary" size="sm">
                                View My Bookings
                            </x-portal.button>
                        @endif
                    </div>
                </x-slot:action>
            </x-portal.alert>
        @elseif ($order->status === \App\Enums\OrderStatus::PendingPayment)
            <x-portal.alert variant="warning" title="Awaiting Server-Side Payment Confirmation">
                <p>
                    @if ($redirectReportedPaid)
                        Your payment gateway redirect reported a completed transaction. We are awaiting verified webhook confirmation before activating your seat or module entitlement.
                    @else
                        This order is still waiting for verified payment confirmation from the payment gateway.
                    @endif
                </p>
                <x-slot:action>
                    <x-portal.button :href="request()->fullUrl()" variant="secondary" size="sm">
                        Refresh Status
                    </x-portal.button>
                </x-slot:action>
            </x-portal.alert>
        @else
            <x-portal.alert variant="danger" title="Payment {{ $order->status->label() }}">
                <p>
                    This transaction was not completed ({{ $order->status->label() }}). Please inspect your order details or browse available programmes to register again.
                </p>
                <x-slot:action>
                    <x-portal.button :href="route('catalogue.index')" variant="primary" size="sm">
                        Browse Training
                    </x-portal.button>
                </x-slot:action>
            </x-portal.alert>
        @endif

        <x-portal.card title="Transaction Overview">
            <dl class="grid gap-5 text-[15px] sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-sm font-medium text-portal-muted">Order Number</dt>
                    <dd class="mt-1 font-mono text-sm font-semibold text-portal-primary">{{ $order->order_number }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-portal-muted">Order Type</dt>
                    <dd class="mt-1 font-medium text-portal-text">{{ $order->order_type->label() }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-portal-muted">Total Amount</dt>
                    <dd class="mt-1 font-semibold tabular-nums text-portal-text">{{ $order->formattedTotal() }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-portal-muted">Latest Payment Reference</dt>
                    <dd class="mt-1 font-mono text-sm text-portal-text">{{ $payment?->payment_reference ?? '—' }}</dd>
                </div>
            </dl>

            <x-slot:footer>
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <x-portal.button :href="route('portal.orders.show', $order)" variant="secondary">
                        View Order Details
                    </x-portal.button>
                    <x-portal.button :href="route('portal.dashboard')" variant="primary">
                        Return to Dashboard
                    </x-portal.button>
                </div>
            </x-slot:footer>
        </x-portal.card>
    </div>
</x-layouts.portal>
