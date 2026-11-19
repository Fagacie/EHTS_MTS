<x-layouts.auth title="Administrator MFA Verification" heading="Two-Factor Verification" subheading="Enter the 6-digit code from your authenticator app or a recovery code to unlock /admin.">
    <form method="POST" action="{{ route('two-factor.challenge.verify') }}" class="space-y-4">
        @csrf

        <div>
            <label for="code" class="block text-sm font-medium text-slate-800">Authenticator Code</label>
            <input
                id="code"
                name="code"
                type="text"
                inputmode="numeric"
                maxlength="6"
                autofocus
                placeholder="6-digit code"
                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-center text-lg font-semibold tracking-widest text-slate-900 tabular-nums-mono focus:border-eht-teal-600 focus:outline-none focus:ring-2 focus:ring-eht-teal-600/20"
            >
        </div>

        <div class="relative py-1">
            <div class="absolute inset-0 flex items-center" aria-hidden="true">
                <div class="w-full border-t border-slate-200"></div>
            </div>
            <div class="relative flex justify-center">
                <span class="bg-white px-2 text-xs text-slate-500">or use recovery code</span>
            </div>
        </div>

        <div>
            <label for="recovery_code" class="block text-sm font-medium text-slate-800">Recovery Code</label>
            <input
                id="recovery_code"
                name="recovery_code"
                type="text"
                placeholder="XXXX-XXXX"
                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 tabular-nums-mono focus:border-eht-teal-600 focus:outline-none focus:ring-2 focus:ring-eht-teal-600/20"
            >
        </div>

        <x-eht.button type="submit" variant="primary" size="lg" class="w-full">
            Verify &amp; Continue to Admin Panel
        </x-eht.button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <x-eht.button type="submit" variant="ghost" size="sm" class="w-full">
            Cancel &amp; Sign Out
        </x-eht.button>
    </form>
</x-layouts.auth>
