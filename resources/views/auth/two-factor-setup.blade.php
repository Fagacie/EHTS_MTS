<x-layouts.auth title="Administrator MFA Setup" heading="Enable Administrator MFA" subheading="SRS Section 14 requires TOTP multi-factor authentication before accessing the Filament 5 Admin Panel.">
    <div class="space-y-5">
        <div class="flex flex-col items-center rounded-xl border border-slate-200 bg-slate-50 p-4">
            <img src="{{ $qrCodeDataUri }}" alt="Authenticator QR Code" class="h-44 w-44 rounded-lg border border-slate-200 bg-white p-2">
            <p class="mt-3 text-xs text-slate-600">Scan this QR code in Google Authenticator, 1Password, or Microsoft Authenticator, or enter the key manually:</p>
            <code class="mt-1.5 rounded bg-white px-2.5 py-1 text-xs font-semibold text-eht-navy-900 border border-slate-200 tabular-nums-mono">{{ $secret }}</code>
        </div>

        <form method="POST" action="{{ route('two-factor.setup.confirm') }}" class="space-y-4">
            @csrf

            <div>
                <label for="code" class="block text-sm font-medium text-slate-800">6-Digit Authenticator Code <span class="text-rose-600">*</span></label>
                <input
                    id="code"
                    name="code"
                    type="text"
                    inputmode="numeric"
                    maxlength="6"
                    required
                    autofocus
                    placeholder="000000"
                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-center text-lg font-semibold tracking-widest text-slate-900 tabular-nums-mono focus:border-eht-teal-600 focus:outline-none focus:ring-2 focus:ring-eht-teal-600/20"
                >
            </div>

            <x-eht.button type="submit" variant="primary" size="lg" class="w-full">
                Verify &amp; Activate MFA
            </x-eht.button>
        </form>
    </div>
</x-layouts.auth>
