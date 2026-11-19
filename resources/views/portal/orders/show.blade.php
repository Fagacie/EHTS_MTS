<x-layouts.portal title="Order {{ $order->order_number }}">
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
            :description="$order->order_type->label().' · Placed '.$order->created_at?->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A').' MYT'"
        >
            <x-slot:actions>
                @if ($order->status === \App\Enums\OrderStatus::PendingPayment && (! $order->expires_at || $order->expires_at->isFuture()))
                    <form method="POST" action="{{ route('portal.orders.pay', $order) }}">
                        @csrf
                        <x-portal.button type="submit" variant="primary">
                            Complete Online Payment
                        </x-portal.button>
                    </form>
                @endif
            </x-slot:actions>
        </x-portal.page-header>

        @if (session('error'))
            <x-portal.alert variant="danger" title="Payment Action Unavailable">
                <p>{{ session('error') }}</p>
            </x-portal.alert>
        @endif

        @if ($order->status === \App\Enums\OrderStatus::PendingPayment)
            <x-portal.alert variant="warning" title="Awaiting Payment Confirmation">
                <p>
                    This order will be marked as paid once verified payment confirmation is received from the payment gateway.
                    @if ($order->expires_at)
                        Hold window ends {{ $order->expires_at->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') }} MYT.
                    @endif
                </p>
            </x-portal.alert>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                {{-- Order Line Items --}}
                <x-portal.card title="Order Items">
                    <div class="divide-y divide-portal-border">
                        @foreach ($order->items as $item)
                            <div class="flex flex-col gap-2 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="text-base font-semibold text-portal-text">
                                        {{ $item->item_title_snapshot }}
                                    </p>
                                    <p class="mt-0.5 text-sm text-portal-muted">
                                        @if ($item->item_code_snapshot)
                                            <span>{{ $item->item_code_snapshot }} · </span>
                                        @endif
                                        <span>Quantity: {{ $item->quantity }}</span>
                                    </p>
                                </div>
                                <div class="text-base font-semibold tabular-nums text-portal-text sm:text-right">
                                    {{ $order->currency }} {{ number_format((float) $item->line_total, 2) }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-5 flex items-center justify-between border-t border-portal-border pt-4">
                        <span class="text-base font-semibold text-portal-text">Total Amount</span>
                        <span class="text-lg font-semibold tabular-nums text-portal-text">
                            {{ $order->formattedTotal() }}
                        </span>
                    </div>
                </x-portal.card>

                {{-- Linked Entitlements / Bookings --}}
                @if ($order->bookings->isNotEmpty() || $order->moduleEntitlements->isNotEmpty())
                    <x-portal.card title="Associated Booking & Learning Access">
                        <div class="space-y-3">
                            @foreach ($order->bookings as $linkedBooking)
                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-portal-bg px-4 py-3 ring-1 ring-portal-border">
                                    <div>
                                        <p class="font-mono text-sm font-semibold text-portal-primary">
                                            {{ $linkedBooking->booking_reference }}
                                        </p>
                                        <p class="mt-0.5 text-sm text-portal-muted">
                                            Physical Session Reservation
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <x-portal.status-badge :status="$linkedBooking->status" :label="$linkedBooking->status->label()" />
                                        <x-portal.button :href="route('portal.bookings.show', $linkedBooking)" variant="secondary" size="sm">
                                            Open Booking
                                        </x-portal.button>
                                    </div>
                                </div>
                            @endforeach

                            @foreach ($order->moduleEntitlements as $entitlement)
                                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-portal-bg px-4 py-3 ring-1 ring-portal-border">
                                    <div>
                                        <p class="text-[15px] font-semibold text-portal-text">
                                            {{ $entitlement->module?->title ?? 'Training Module Access' }}
                                        </p>
                                        <p class="mt-0.5 text-sm text-portal-muted">
                                            Digital module learning access
                                        </p>
                                    </div>
                                    @if ($entitlement->module)
                                        <x-portal.button :href="route('portal.modules.show', $entitlement->module)" variant="secondary" size="sm">
                                            Open Module
                                        </x-portal.button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </x-portal.card>
                @endif

                {{-- Payment Transactions --}}
                <x-portal.card title="Payment History">
                    @if ($order->payments->isEmpty())
                        <p class="text-[15px] text-portal-muted">No payment attempts have been recorded for this order yet.</p>
                    @else
                        <div class="space-y-3">
                            @foreach ($order->payments as $payment)
                                <div class="flex flex-col gap-3 rounded-lg bg-portal-bg p-4 ring-1 ring-portal-border sm:flex-row sm:items-center sm:justify-between">
                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-mono text-sm font-semibold text-portal-text">
                                                {{ $payment->payment_reference }}
                                            </span>
                                            <x-portal.status-badge :status="$payment->status" :label="$payment->status->label()" />
                                        </div>
                                        <p class="text-sm text-portal-muted">
                                            Channel: {{ ucfirst((string) $payment->gateway) }}
                                            @if ($payment->paid_at)
                                                · Paid {{ $payment->paid_at->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') }} MYT
                                            @elseif ($payment->created_at)
                                                · Initiated {{ $payment->created_at->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') }} MYT
                                            @endif
                                        </p>
                                    </div>

                                    <div class="text-base font-semibold tabular-nums text-portal-text">
                                        {{ $payment->currency }} {{ number_format((float) $payment->amount, 2) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-portal.card>

                {{-- Refunds --}}
                @if ($order->refunds->isNotEmpty())
                    <x-portal.card title="Refund Records">
                        <div class="space-y-3">
                            @foreach ($order->refunds as $refund)
                                <div class="flex flex-col gap-3 rounded-lg bg-portal-bg p-4 ring-1 ring-portal-border sm:flex-row sm:items-center sm:justify-between">
                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-mono text-sm font-semibold text-portal-text">
                                                {{ $refund->refund_reference }}
                                            </span>
                                            <x-portal.status-badge :status="$refund->status" :label="$refund->status->label()" />
                                        </div>
                                        <p class="text-sm text-portal-muted">
                                            {{ $refund->refund_method->label() }}
                                            @if ($refund->reason)
                                                · {{ $refund->reason }}
                                            @endif
                                        </p>
                                    </div>

                                    <div class="text-base font-semibold tabular-nums text-portal-text">
                                        {{ $refund->currency }} {{ number_format((float) $refund->amount, 2) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </x-portal.card>
                @endif
            </div>

            {{-- Billing Summary Sidebar --}}
            <div class="space-y-6">
                <x-portal.card title="Billing &amp; Trainee Details">
                    <dl class="space-y-3.5 text-[15px]">
                        <div>
                            <dt class="text-sm font-medium text-portal-muted">Customer Name</dt>
                            <dd class="mt-0.5 font-medium text-portal-text">{{ $order->customer_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-portal-muted">Email</dt>
                            <dd class="mt-0.5 text-portal-text">{{ $order->customer_email }}</dd>
                        </div>
                        @if ($order->customer_phone)
                            <div>
                                <dt class="text-sm font-medium text-portal-muted">Phone</dt>
                                <dd class="mt-0.5 text-portal-text">{{ $order->customer_phone }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-sm font-medium text-portal-muted">Account Category</dt>
                            <dd class="mt-0.5 text-portal-text">{{ ucwords(str_replace('_', ' ', (string) $order->customer_type)) }}</dd>
                        </div>
                        @if ($order->company_name)
                            <div>
                                <dt class="text-sm font-medium text-portal-muted">Company / Organisation</dt>
                                <dd class="mt-0.5 font-medium text-portal-text">{{ $order->company_name }}</dd>
                            </div>
                        @endif
                        @php
                            $billingAddress = collect([
                                $order->billing_address_line_1,
                                $order->billing_address_line_2,
                                trim(($order->billing_postcode ?? '').' '.($order->billing_city ?? '')),
                                $order->billing_state,
                                $order->billing_country,
                            ])->filter()->implode(', ');
                        @endphp
                        @if ($billingAddress !== '')
                            <div>
                                <dt class="text-sm font-medium text-portal-muted">Billing Address</dt>
                                <dd class="mt-0.5 text-sm leading-relaxed text-portal-text">{{ $billingAddress }}</dd>
                            </div>
                        @endif
                    </dl>
                </x-portal.card>
            </div>
        </div>
    </div>
</x-layouts.portal>
