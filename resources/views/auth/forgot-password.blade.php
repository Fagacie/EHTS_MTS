<x-layouts.auth title="Forgot Password" heading="Reset your password" subheading="Enter your registered email address to receive a password reset link.">
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-slate-800">Email address <span class="text-rose-600">*</span></label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                required
                autofocus
                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none focus:ring-2 focus:ring-eht-teal-600/20"
            >
        </div>

        <x-eht.button type="submit" variant="primary" size="lg" class="w-full">
            Email Password Reset Link
        </x-eht.button>
    </form>

    <p class="mt-6 border-t border-slate-200 pt-4 text-center text-xs text-slate-600">
        Remembered your password?
        <a href="{{ route('login') }}" class="font-semibold text-eht-navy-700 hover:underline">Back to sign in</a>
    </p>
</x-layouts.auth>
