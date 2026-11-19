@php
    $record = $getRecord();
    $logs = $record->historyLogs();
@endphp

<div class="space-y-3">
    @if ($logs->isEmpty())
        <p class="text-sm text-gray-500">No audit history entries recorded for this certificate yet.</p>
    @else
        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
            <table class="min-w-full divide-y divide-gray-200 text-left text-sm dark:divide-white/10">
                <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:bg-white/5 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-2.5">Timestamp (MYT)</th>
                        <th class="px-4 py-2.5">Event</th>
                        <th class="px-4 py-2.5">Actor</th>
                        <th class="px-4 py-2.5">Reason / Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @foreach ($logs as $log)
                        <tr>
                            <td class="px-4 py-2.5 font-mono text-xs whitespace-nowrap">
                                {{ $log->created_at?->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))->format('d M Y, h:i:s A') }}
                            </td>
                            <td class="px-4 py-2.5">
                                <span class="inline-flex rounded-full bg-sky-50 px-2.5 py-0.5 text-xs font-semibold text-sky-800 dark:bg-sky-500/20 dark:text-sky-300">
                                    {{ $log->event }}
                                </span>
                            </td>
                            <td class="px-4 py-2.5 text-xs">
                                {{ $log->actor?->name ?? 'System' }}
                            </td>
                            <td class="px-4 py-2.5 text-xs text-gray-600 dark:text-gray-300">
                                {{ $log->reason ?: '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
