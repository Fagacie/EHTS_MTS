<x-layouts.auth title="Set New Password" heading="Set new password" subheading="Choose a strong password for your ETMS account.">
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="block text-sm font-medium text-slate-800">Email address <span class="text-rose-600">*</span></label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email', $email) }}"
                required
                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none focus:ring-2 focus:ring-eht-teal-600/20"
            >
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-800">New password <span class="text-rose-600">*</span></label>
            <input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="new-password"
                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none focus:ring-2 focus:ring-eht-teal-600/20"
            >
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-800">Confirm new password <span class="text-rose-600">*</span></label>
            <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                required
                autocomplete="new-password"
                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none focus:ring-2 focus:ring-eht-teal-600/20"
            >
        </div>

        <x-eht.button type="submit" variant="primary" size="lg" class="w-full">
            Reset Password
        </x-eht.button>
    </form>
</x-layouts.auth>
