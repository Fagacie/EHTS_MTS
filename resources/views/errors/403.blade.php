<x-layouts.auth title="Access Restricted" heading="Access Restricted (403)" subheading="Your current account roles do not permit access to this portal or action.">
    <div class="space-y-4">
        <div class="rounded-lg border border-status-danger-border bg-status-danger-bg px-4 py-3 text-xs text-status-danger-text">
            {{ $exception->getMessage() ?: 'You do not have the required role or permission to view this resource.' }}
        </div>

        <div class="flex flex-wrap gap-2">
            @auth
                <x-eht.button href="{{ auth()->user()->defaultDashboardUrl() }}" variant="primary" class="w-full sm:w-auto">
                    Go to My Portal
                </x-eht.button>
            @endauth
            <x-eht.button href="{{ route('home') }}" variant="outline" class="w-full sm:w-auto">
                Return Home
            </x-eht.button>
        </div>
    </div>
</x-layouts.auth>
