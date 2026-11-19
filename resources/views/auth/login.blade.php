<x-layouts.auth title="Sign In" heading="Sign in to ETMS" subheading="Access your Trainee, Instructor, or Administrator account.">
    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
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
                autocomplete="email"
                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-eht-teal-600 focus:outline-none focus:ring-2 focus:ring-eht-teal-600/20"
                placeholder="name@company.com.my"
            >
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="block text-sm font-medium text-slate-800">Password <span class="text-rose-600">*</span></label>
                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-eht-navy-700 hover:underline">
                    Forgot password?
                </a>
            </div>
            <input
                id="password"
                name="password"
                type="password"
                required
                autocomplete="current-password"
                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none focus:ring-2 focus:ring-eht-teal-600/20"
            >
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-slate-300 text-eht-navy-700 focus:ring-eht-teal-600">
            <span>Keep me signed in on this device</span>
        </label>

        <x-eht.button type="submit" variant="primary" size="lg" class="w-full">
            Sign In
        </x-eht.button>
    </form>

    <p class="mt-6 border-t border-slate-200 pt-4 text-center text-xs text-slate-600">
        New to Ecohydrotech Training?
        <a href="{{ route('register') }}" class="font-semibold text-eht-navy-700 hover:underline">Create a customer account</a>
    </p>
</x-layouts.auth>
