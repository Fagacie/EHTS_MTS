<x-layouts.portal title="Notifications">
    <div class="space-y-6">
        <x-portal.page-header
            title="Notifications"
            description="Stay updated on your seat reservations, payment confirmations, and certificate availability."
        >
            @if ($unreadCount > 0)
                <x-slot:actions>
                    <form method="POST" action="{{ route('portal.notifications.read-all') }}">
                        @csrf
                        <x-portal.button type="submit" variant="secondary">
                            Mark All Read
                        </x-portal.button>
                    </form>
                </x-slot:actions>
            @endif
        </x-portal.page-header>

        {{-- Filter Bar --}}
        <x-portal.card :padding="false">
            <div class="flex flex-wrap items-center gap-2 px-5 py-4 sm:px-6" role="navigation" aria-label="Filter notifications">
                <a
                    href="{{ route('portal.notifications.index', ['filter' => 'all']) }}"
                    class="inline-flex min-h-9 items-center rounded-lg px-4 py-1.5 text-sm font-medium transition {{ $filter !== 'unread' ? 'bg-portal-primary text-white shadow-xs' : 'bg-portal-bg text-portal-muted hover:bg-portal-surface-muted hover:text-portal-text' }}"
                >
                    All Notifications
                </a>

                <a
                    href="{{ route('portal.notifications.index', ['filter' => 'unread']) }}"
                    class="inline-flex min-h-9 items-center rounded-lg px-4 py-1.5 text-sm font-medium transition {{ $filter === 'unread' ? 'bg-portal-primary text-white shadow-xs' : 'bg-portal-bg text-portal-muted hover:bg-portal-surface-muted hover:text-portal-text' }}"
                >
                    Unread ({{ $unreadCount }})
                </a>
            </div>
        </x-portal.card>

        @if ($notifications->isEmpty())
            <x-portal.empty-state
                :title="$filter === 'unread' ? 'No unread notifications' : 'No notifications yet'"
                :description="$filter === 'unread' ? 'You are up to date. All recent notifications have already been read.' : 'Updates about bookings, payments, session materials, and certificates will appear here.'"
            >
                @if ($filter === 'unread')
                    <x-slot:action>
                        <x-portal.button :href="route('portal.notifications.index', ['filter' => 'all'])" variant="secondary">
                            View All Notifications
                        </x-portal.button>
                    </x-slot:action>
                @endif
            </x-portal.empty-state>
        @else
            <div class="space-y-3">
                @foreach ($notifications as $notification)
                    @php
                        $data = is_array($notification->data) ? $notification->data : [];
                        $title = $data['title'] ?? 'Account Update';
                        $message = $data['message'] ?? '';
                        $reference = $data['reference'] ?? null;
                        $details = $data['details'] ?? null;
                        $actionUrl = $data['action_url'] ?? null;
                        $actionLabel = $data['action_label'] ?? 'View Details';
                        $isUnread = $notification->read_at === null;
                    @endphp

                    <article class="rounded-xl bg-portal-surface p-5 shadow-xs ring-1 transition sm:p-6 {{ $isUnread ? 'ring-portal-primary/25 bg-portal-primary-soft/25' : 'ring-gray-950/5' }}">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 space-y-2">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    @if ($isUnread)
                                        <span class="inline-flex h-2.5 w-2.5 rounded-full bg-portal-primary" aria-hidden="true"></span>
                                    @endif

                                    <h2 class="text-base font-semibold text-portal-text sm:text-[17px]">
                                        {{ $title }}
                                    </h2>

                                    @if ($isUnread)
                                        <x-portal.status-badge status="primary" label="Unread" />
                                    @endif

                                    @if ($reference)
                                        <span class="rounded-md bg-portal-bg px-2.5 py-1 font-mono text-[13px] font-medium text-portal-text ring-1 ring-inset ring-portal-border">
                                            {{ $reference }}
                                        </span>
                                    @endif
                                </div>

                                @if ($message !== '')
                                    <p class="text-[15px] leading-relaxed text-portal-text">
                                        {{ $message }}
                                    </p>
                                @endif

                                @if (! empty($details) && is_array($details))
                                    <dl class="flex flex-wrap gap-x-5 gap-y-1.5 pt-0.5 text-sm text-portal-muted">
                                        @foreach ($details as $label => $value)
                                            <div>
                                                <dt class="inline font-medium text-portal-text">{{ $label }}:</dt>
                                                <dd class="inline">{{ is_scalar($value) ? $value : json_encode($value) }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                @elseif (is_string($details) && $details !== '')
                                    <p class="text-sm text-portal-muted">
                                        {{ $details }}
                                    </p>
                                @endif

                                <p class="text-[13px] text-portal-muted">
                                    {{ $notification->created_at?->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') }} MYT
                                </p>
                            </div>

                            <div class="flex shrink-0 flex-wrap items-center gap-2.5 sm:justify-end">
                                @if ($actionUrl)
                                    @if ($isUnread)
                                        <form method="POST" action="{{ route('portal.notifications.read', $notification) }}">
                                            @csrf
                                            <input type="hidden" name="open" value="1">
                                            <x-portal.button type="submit" variant="primary" size="sm">
                                                {{ $actionLabel }}
                                            </x-portal.button>
                                        </form>
                                    @else
                                        <x-portal.button :href="$actionUrl" variant="secondary" size="sm">
                                            {{ $actionLabel }}
                                        </x-portal.button>
                                    @endif
                                @endif

                                @if ($isUnread)
                                    <form method="POST" action="{{ route('portal.notifications.read', $notification) }}">
                                        @csrf
                                        <x-portal.button type="submit" variant="secondary" size="sm">
                                            Mark Read
                                        </x-portal.button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            @if ($notifications->hasPages())
                <div class="pt-1">
                    {{ $notifications->links() }}
                </div>
            @endif
        @endif
    </div>
</x-layouts.portal>
