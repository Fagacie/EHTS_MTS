<x-layouts.auth title="Verify Email" heading="Verify your email address" subheading="Before accessing your ETMS portal, please confirm your email using the verification link we sent.">
    <div class="space-y-4">
        <p class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-700">
            Signed in as <span class="font-semibold">{{ auth()->user()->email }}</span>.
        </p>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-eht.button type="submit" variant="primary" size="lg" class="w-full">
                Resend Verification Email
            </x-eht.button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <x-eht.button type="submit" variant="outline" size="md" class="w-full">
                Sign Out
            </x-eht.button>
        </form>
    </div>
</x-layouts.auth>
