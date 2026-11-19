<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Date Filters & Quick CSV Exports -->
        <x-filament::section heading="Date Range Filter & CSV Exports" description="Filter analytics across MYT calendar dates (YYYY-MM-DD). Leave blank for all-time totals.">
            <form wire:submit="applyFilters" class="flex flex-wrap items-end gap-4">
                <div>
                    <label for="report-from" class="block text-xs font-semibold text-gray-700 dark:text-gray-200">From Date (MYT)</label>
                    <input
                        id="report-from"
                        type="date"
                        wire:model="fromDate"
                        class="mt-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm dark:border-white/20 dark:bg-gray-900 dark:text-white"
                    >
                </div>
                <div>
                    <label for="report-to" class="block text-xs font-semibold text-gray-700 dark:text-gray-200">To Date (MYT)</label>
                    <input
                        id="report-to"
                        type="date"
                        wire:model="toDate"
                        class="mt-1 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm dark:border-white/20 dark:bg-gray-900 dark:text-white"
                    >
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-filament::button type="submit" icon="heroicon-o-funnel">
                        Apply Filter
                    </x-filament::button>
                    <x-filament::button type="button" color="gray" wire:click="resetFilters">
                        All Time
                    </x-filament::button>
                </div>
            </form>

            @php
                $exportParams = array_filter([
                    'from' => $report['filters']['from'] ?? null,
                    'to' => $report['filters']['to'] ?? null,
                ]);
            @endphp
            <div class="mt-4 flex flex-wrap gap-2 border-t border-gray-200 pt-4 dark:border-white/10">
                <span class="self-center text-xs font-semibold text-gray-500 dark:text-gray-400">Download CSV:</span>
                <a href="{{ route('admin.reports.export', array_merge(['dataset' => 'bookings'], $exportParams)) }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-white/15 dark:bg-white/5 dark:text-gray-200">
                    Bookings CSV
                </a>
                <a href="{{ route('admin.reports.export', array_merge(['dataset' => 'revenue'], $exportParams)) }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-white/15 dark:bg-white/5 dark:text-gray-200">
                    Revenue Orders CSV
                </a>
                <a href="{{ route('admin.reports.export', array_merge(['dataset' => 'refunds'], $exportParams)) }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-white/15 dark:bg-white/5 dark:text-gray-200">
                    Refunds CSV
                </a>
                <a href="{{ route('admin.reports.export', array_merge(['dataset' => 'popular_modules'], $exportParams)) }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-white/15 dark:bg-white/5 dark:text-gray-200">
                    Popular Modules CSV
                </a>
                <a href="{{ route('admin.reports.export', array_merge(['dataset' => 'instructors'], $exportParams)) }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-white/15 dark:bg-white/5 dark:text-gray-200">
                    Instructors CSV
                </a>
                <a href="{{ route('admin.reports.export', array_merge(['dataset' => 'certificates'], $exportParams)) }}" class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:border-white/15 dark:bg-white/5 dark:text-gray-200">
                    Certificates CSV
                </a>
            </div>
        </x-filament::section>

        <!-- Summary KPI Grid -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-filament::section heading="Registered Customers">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $report['customers']['total'] }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $report['customers']['new_in_period'] }} registered in range &middot;
                    {{ $report['customers']['individual'] }} individual / {{ $report['customers']['company'] }} company
                </p>
            </x-filament::section>

            <x-filament::section heading="Modules & Published Sessions">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $report['catalogue']['published_modules'] }} / {{ $report['catalogue']['published_sessions'] }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $report['catalogue']['independent_modules'] }} purchasable modules &middot;
                    {{ $report['catalogue']['completed_sessions'] }} completed &middot; {{ $report['catalogue']['cancelled_sessions'] }} cancelled
                </p>
            </x-filament::section>

            <x-filament::section heading="Bookings & Participants">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $report['bookings']['confirmed'] }} Confirmed</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $report['bookings']['unique_participants'] }} unique participants &middot;
                    {{ $report['bookings']['expired'] }} expired &middot; {{ $report['bookings']['cancelled'] }} cancelled
                </p>
            </x-filament::section>

            <x-filament::section heading="Certificates Issued">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $report['certificates']['issued'] }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    {{ $report['certificates']['session_type'] }} session / {{ $report['certificates']['module_type'] }} module &middot;
                    {{ $report['certificates']['revoked'] }} revoked
                </p>
            </x-filament::section>
        </div>

        <!-- Financial & Payment Summaries (MYR) -->
        <div class="grid gap-4 lg:grid-cols-2">
            <x-filament::section heading="Revenue & Payment Summary (MYR)">
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Gross Revenue</dt>
                        <dd class="mt-0.5 text-lg font-bold text-gray-900 dark:text-white">MYR {{ number_format($report['revenue']['gross_myr'], 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Net Revenue (After Refunds)</dt>
                        <dd class="mt-0.5 text-lg font-bold text-emerald-600">MYR {{ number_format($report['revenue']['net_myr'], 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Session Booking Revenue</dt>
                        <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-200">MYR {{ number_format($report['revenue']['session_myr'], 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Independent Module Revenue</dt>
                        <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-200">MYR {{ number_format($report['revenue']['module_myr'], 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Successful Payments</dt>
                        <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-200">{{ $report['revenue']['successful_payments'] }} ({{ $report['revenue']['paid_orders_count'] }} paid orders)</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Failed / Pending Payments</dt>
                        <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-200">{{ $report['revenue']['failed_payments'] }} failed / {{ $report['revenue']['pending_payments'] }} pending</dd>
                    </div>
                </dl>
            </x-filament::section>

            <x-filament::section heading="Refund Summary (MYR)">
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Completed Refunds Total</dt>
                        <dd class="mt-0.5 text-lg font-bold text-rose-600">MYR {{ number_format($report['refunds']['completed_myr'], 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Pending Refund Queue</dt>
                        <dd class="mt-0.5 text-lg font-bold text-amber-600">MYR {{ number_format($report['refunds']['pending_myr'], 2) }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Completed Refunds Count</dt>
                        <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-200">{{ $report['refunds']['completed_count'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500 dark:text-gray-400">Pending / Rejected Count</dt>
                        <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-200">{{ $report['refunds']['pending_count'] }} pending / {{ $report['refunds']['rejected_count'] }} rejected</dd>
                    </div>
                </dl>
            </x-filament::section>
        </div>

        <!-- Most Popular Training Modules -->
        <x-filament::section heading="Most Popular Training Modules">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-white/10">
                            <th class="py-2 pr-4">Code</th>
                            <th class="py-2 pr-4">Title</th>
                            <th class="py-2 pr-4">Category</th>
                            <th class="py-2 pr-4 text-right">Session Bookings</th>
                            <th class="py-2 pr-4 text-right">Module Purchases</th>
                            <th class="py-2 pr-4 text-right">Active Entitlements</th>
                            <th class="py-2 text-right">Revenue (MYR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse ($report['popular_modules'] as $row)
                            <tr>
                                <td class="py-2.5 pr-4 font-mono text-xs font-semibold">{{ $row['code'] }}</td>
                                <td class="py-2.5 pr-4 font-medium text-gray-900 dark:text-white">{{ $row['title'] }}</td>
                                <td class="py-2.5 pr-4 text-gray-500">{{ $row['category'] }}</td>
                                <td class="py-2.5 pr-4 text-right">{{ $row['confirmed_bookings'] }}</td>
                                <td class="py-2.5 pr-4 text-right">{{ $row['independent_purchases'] }}</td>
                                <td class="py-2.5 pr-4 text-right">{{ $row['active_entitlements'] }}</td>
                                <td class="py-2.5 text-right font-semibold">MYR {{ number_format($row['revenue_myr'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-xs text-gray-400">No training modules found for the selected filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <!-- Instructor & Session Statistics -->
        <x-filament::section heading="Instructor & Session Statistics">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 text-xs uppercase text-gray-500 dark:border-white/10">
                            <th class="py-2 pr-4">Instructor</th>
                            <th class="py-2 pr-4">Email</th>
                            <th class="py-2 pr-4 text-right">Assigned Sessions</th>
                            <th class="py-2 pr-4 text-right">Completed</th>
                            <th class="py-2 pr-4 text-right">Upcoming</th>
                            <th class="py-2 pr-4 text-right">Confirmed Participants</th>
                            <th class="py-2 text-right">Certificates Issued</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        @forelse ($report['instructor_stats'] as $row)
                            <tr>
                                <td class="py-2.5 pr-4 font-medium text-gray-900 dark:text-white">{{ $row['name'] }}</td>
                                <td class="py-2.5 pr-4 text-gray-500">{{ $row['email'] }}</td>
                                <td class="py-2.5 pr-4 text-right">{{ $row['assigned_sessions'] }}</td>
                                <td class="py-2.5 pr-4 text-right">{{ $row['completed_sessions'] }}</td>
                                <td class="py-2.5 pr-4 text-right">{{ $row['upcoming_sessions'] }}</td>
                                <td class="py-2.5 pr-4 text-right">{{ $row['confirmed_participants'] }}</td>
                                <td class="py-2.5 text-right font-semibold">{{ $row['certificates_issued'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-6 text-center text-xs text-gray-400">No instructor records found for the selected filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
