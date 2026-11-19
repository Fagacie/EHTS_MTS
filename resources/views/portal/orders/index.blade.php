<x-layouts.portal title="Orders & Payments">
    <div class="space-y-6">
        <x-portal.page-header
            title="Orders & Payments"
            description="Review transaction history, payment statuses, and refund updates for your bookings and modules."
        >
            <x-slot:actions>
                <x-portal.button :href="route('catalogue.index')" variant="secondary">
                    Browse Training
                </x-portal.button>
            </x-slot:actions>
        </x-portal.page-header>

        {{-- Filters --}}
        <x-portal.card :padding="false">
            <div class="space-y-3 px-5 py-4 sm:px-6">
                <div class="flex flex-wrap items-center gap-2" role="navigation" aria-label="Filter orders by type">
                    <span class="mr-1 text-sm font-medium text-portal-muted">Type:</span>

                    <a
                        href="{{ route('portal.orders.index', array_filter(['status' => $statusFilter])) }}"
                        class="inline-flex min-h-9 items-center rounded-lg px-3.5 py-1.5 text-sm font-medium transition {{ $typeFilter === '' ? 'bg-portal-primary text-white shadow-xs' : 'bg-portal-bg text-portal-muted hover:bg-portal-surface-muted hover:text-portal-text' }}"
                    >
                        All Types
                    </a>

                    @foreach ($orderTypes as $typeOption)
                        <a
                            href="{{ route('portal.orders.index', array_filter(['type' => $typeOption->value, 'status' => $statusFilter])) }}"
                            class="inline-flex min-h-9 items-center rounded-lg px-3.5 py-1.5 text-sm font-medium transition {{ $typeFilter === $typeOption->value ? 'bg-portal-primary text-white shadow-xs' : 'bg-portal-bg text-portal-muted hover:bg-portal-surface-muted hover:text-portal-text' }}"
                        >
                            {{ $typeOption->label() }}
                        </a>
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center gap-2 border-t border-portal-border pt-3" role="navigation" aria-label="Filter orders by payment status">
                    <span class="mr-1 text-sm font-medium text-portal-muted">Status:</span>

                    <a
                        href="{{ route('portal.orders.index', array_filter(['type' => $typeFilter])) }}"
                        class="inline-flex min-h-9 items-center rounded-lg px-3.5 py-1.5 text-sm font-medium transition {{ $statusFilter === '' ? 'bg-portal-primary text-white shadow-xs' : 'bg-portal-bg text-portal-muted hover:bg-portal-surface-muted hover:text-portal-text' }}"
                    >
                        All Statuses
                    </a>

                    @foreach ($statuses as $statusOption)
                        <a
                            href="{{ route('portal.orders.index', array_filter(['type' => $typeFilter, 'status' => $statusOption->value])) }}"
                            class="inline-flex min-h-9 items-center rounded-lg px-3.5 py-1.5 text-sm font-medium transition {{ $statusFilter === $statusOption->value ? 'bg-portal-primary text-white shadow-xs' : 'bg-portal-bg text-portal-muted hover:bg-portal-surface-muted hover:text-portal-text' }}"
                        >
                            {{ $statusOption->label() }}
                        </a>
                    @endforeach

                    @if ($typeFilter !== '' || $statusFilter !== '')
                        <a
                            href="{{ route('portal.orders.index') }}"
                            class="ml-auto inline-flex min-h-9 items-center px-2 text-sm font-medium text-portal-primary hover:underline"
                        >
                            Reset all filters
                        </a>
                    @endif
                </div>
            </div>
        </x-portal.card>

        @if ($orders->isEmpty())
            <x-portal.empty-state
                title="No orders found"
                :description="($typeFilter !== '' || $statusFilter !== '') ? 'No orders match the currently selected filters.' : 'Your completed or pending bookings and module purchases will appear here.'"
            >
                <x-slot:action>
                    @if ($typeFilter !== '' || $statusFilter !== '')
                        <x-portal.button :href="route('portal.orders.index')" variant="secondary">
                            View All Orders
                        </x-portal.button>
                    @endif
                    <x-portal.button :href="route('catalogue.index')" variant="primary">
                        Explore Training Catalogue
                    </x-portal.button>
                </x-slot:action>
            </x-portal.empty-state>
        @else
            {{-- Mobile Stacked Cards --}}
            <div class="space-y-4 md:hidden">
                @foreach ($orders as $order)
                    @php
                        $primaryItem = $order->items->first();
                    @endphp
                    <x-portal.card :padding="false">
                        <div class="space-y-3.5 p-5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-mono text-sm font-semibold text-portal-primary">
                                    {{ $order->order_number }}
                                </span>
                                <x-portal.status-badge :status="$order->status" :label="$order->status->label()" />
                            </div>

                            <div>
                                <h2 class="text-base font-semibold text-portal-text">
                                    {{ $primaryItem?->item_title_snapshot ?? $order->order_type->label() }}
                                </h2>
                                <p class="mt-0.5 text-sm text-portal-muted">
                                    {{ $order->order_type->label() }}
                                </p>
                            </div>

                            <dl class="space-y-2 border-t border-portal-border pt-3 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-portal-muted">Date</dt>
                                    <dd class="text-portal-text">
                                        {{ $order->created_at?->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') }} MYT
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <dt class="text-portal-muted">Amount</dt>
                                    <dd class="font-semibold tabular-nums text-portal-text">
                                        {{ $order->formattedTotal() }}
                                    </dd>
                                </div>
                            </dl>

                            <x-portal.button :href="route('portal.orders.show', $order)" variant="secondary" class="w-full justify-center">
                                View Order Details
                            </x-portal.button>
                        </div>
                    </x-portal.card>
                @endforeach
            </div>

            {{-- Desktop Table --}}
            <x-portal.table class="hidden md:block">
                <x-slot:head>
                    <tr>
                        <th>Order Number</th>
                        <th>Item</th>
                        <th>Type</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th class="text-right">Action</th>
                    </tr>
                </x-slot:head>

                @foreach ($orders as $order)
                    @php
                        $primaryItem = $order->items->first();
                        $latestPayment = $order->payments->first();
                    @endphp
                    <tr>
                        <td>
                            <div class="font-mono text-sm font-semibold text-portal-primary">
                                {{ $order->order_number }}
                            </div>
                            <div class="mt-0.5 text-[13px] text-portal-muted">
                                {{ $order->created_at?->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') }}
                            </div>
                        </td>
                        <td>
                            <div class="text-[15px] font-medium text-portal-text">
                                {{ $primaryItem?->item_title_snapshot ?? $order->order_type->label() }}
                            </div>
                            @if ($primaryItem?->item_code_snapshot)
                                <div class="mt-0.5 text-[13px] text-portal-muted">
                                    {{ $primaryItem->item_code_snapshot }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="text-sm text-portal-text">
                                {{ $order->order_type->label() }}
                            </div>
                            @if ($latestPayment)
                                <div class="mt-0.5 text-[13px] text-portal-muted">
                                    {{ ucfirst((string) $latestPayment->gateway) }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <span class="font-semibold tabular-nums text-portal-text">
                                {{ $order->formattedTotal() }}
                            </span>
                        </td>
                        <td>
                            <x-portal.status-badge :status="$order->status" :label="$order->status->label()" />
                        </td>
                        <td class="text-right">
                            <x-portal.button :href="route('portal.orders.show', $order)" variant="secondary" size="sm">
                                View Order
                            </x-portal.button>
                        </td>
                    </tr>
                @endforeach
            </x-portal.table>

            @if ($orders->hasPages())
                <div class="pt-1">
                    {{ $orders->links() }}
                </div>
            @endif
        @endif
    </div>
</x-layouts.portal>
