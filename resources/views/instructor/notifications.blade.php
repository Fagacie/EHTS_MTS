<x-layouts.instructor title="Notifications" breadcrumb="Notifications">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Instructor Notification Centre</h1>
                <p class="mt-1 text-sm text-slate-600">
                    Assigned training session schedules, participant reminders, session updates, and platform announcements.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('instructor.notifications.index') }}" class="rounded-lg px-3 py-2 text-xs font-semibold transition {{ $filter === 'all' ? 'bg-eht-navy-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">
                    All
                </a>
                <a href="{{ route('instructor.notifications.index', ['filter' => 'unread']) }}" class="rounded-lg px-3 py-2 text-xs font-semibold transition {{ $filter === 'unread' ? 'bg-eht-navy-900 text-white' : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' }}">
                    Unread ({{ $unreadCount }})
                </a>
                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('instructor.notifications.read-all') }}">
                        @csrf
                        <x-eht.button type="submit" size="sm" variant="outline">
                            Mark All Read
                        </x-eht.button>
                    </form>
                @endif
            </div>
        </div>

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="space-y-3">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data ?? [];
                    $isUnread = $notification->read_at === null;
                @endphp
                <div class="rounded-xl border p-5 transition {{ $isUnread ? 'border-eht-teal-300 bg-white shadow-sm' : 'border-slate-200 bg-slate-50/70' }}">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0 flex-1 space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                @if ($isUnread)
                                    <span class="inline-flex h-2 w-2 rounded-full bg-eht-teal-600" aria-hidden="true"></span>
                                    <span class="rounded-full bg-eht-teal-50 px-2 py-0.5 text-[11px] font-semibold text-eht-teal-700">Unread</span>
                                @else
                                    <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[11px] font-semibold text-slate-600">Read</span>
                                @endif
                                @if (! empty($data['reference']))
                                    <span class="rounded bg-slate-100 px-2 py-0.5 font-mono text-[11px] text-slate-600">{{ $data['reference'] }}</span>
                                @endif
                                <span class="text-xs text-slate-400">
                                    {{ $notification->created_at?->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))->format('d M Y, h:i A') }} MYT
                                </span>
                            </div>

                            <h2 class="text-base font-semibold text-slate-900">{{ $data['title'] ?? 'Notification' }}</h2>
                            <p class="text-sm leading-relaxed text-slate-600">{{ $data['message'] ?? '' }}</p>

                            @if (! empty($data['details']) && is_array($data['details']))
                                <div class="flex flex-wrap gap-x-5 gap-y-1 pt-1 text-xs text-slate-500">
                                    @foreach ($data['details'] as $label => $value)
                                        <span><strong class="font-medium text-slate-700">{{ $label }}:</strong> {{ $value }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="flex shrink-0 flex-wrap items-center gap-2">
                            @if (! empty($data['action_url']))
                                <form method="POST" action="{{ route('instructor.notifications.read', $notification->id) }}">
                                    @csrf
                                    <input type="hidden" name="open" value="1">
                                    <x-eht.button type="submit" size="sm" variant="primary">
                                        {{ $data['action_label'] ?? 'Open Session' }}
                                    </x-eht.button>
                                </form>
                            @endif

                            @if ($isUnread)
                                <form method="POST" action="{{ route('instructor.notifications.read', $notification->id) }}">
                                    @csrf
                                    <x-eht.button type="submit" size="sm" variant="outline">
                                        Mark Read
                                    </x-eht.button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-slate-200 bg-white p-10 text-center">
                    <p class="text-sm font-semibold text-slate-800">
                        {{ $filter === 'unread' ? 'No unread notifications' : 'No notifications yet' }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500">
                        Session schedule updates, participant reminders, and announcements will appear here.
                    </p>
                </div>
            @endforelse
        </div>

        <div>
            {{ $notifications->links() }}
        </div>
    </div>
</x-layouts.instructor>
