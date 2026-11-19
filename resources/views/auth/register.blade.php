<x-layouts.auth title="Create Account" heading="Create customer account" subheading="Used by both individual learners and company purchasers (D-001 / D-002).">
    <form method="POST" action="{{ route('register.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-slate-800">Full name <span class="text-rose-600">*</span></label>
            <input
                id="name"
                name="name"
                type="text"
                value="{{ old('name') }}"
                required
                autofocus
                autocomplete="name"
                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none focus:ring-2 focus:ring-eht-teal-600/20"
                placeholder="Full legal name (printed on certificates)"
            >
            <p class="mt-1 text-xs text-slate-500">Your full name will appear on training bookings and official certificates (D-023).</p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-800">Email address <span class="text-rose-600">*</span></label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    required
                    autocomplete="email"
                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none focus:ring-2 focus:ring-eht-teal-600/20"
                    placeholder="name@company.com.my"
                >
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-slate-800">Phone number <span class="text-xs font-normal text-slate-500">(Optional)</span></label>
                <input
                    id="phone"
                    name="phone"
                    type="text"
                    value="{{ old('phone') }}"
                    autocomplete="tel"
                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none focus:ring-2 focus:ring-eht-teal-600/20"
                    placeholder="+6012-345 6789"
                >
            </div>
        </div>

        <fieldset class="space-y-2">
            <legend class="block text-sm font-medium text-slate-800">Purchase profile type</legend>
            <div class="grid grid-cols-2 gap-3">
                <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-slate-300 p-3 text-xs hover:bg-slate-50">
                    <input
                        type="radio"
                        name="customer_type"
                        value="individual"
                        {{ old('customer_type', 'individual') === 'individual' ? 'checked' : '' }}
                        onchange="document.getElementById('register-company-fields').classList.add('hidden')"
                        class="mt-0.5 text-eht-navy-700 focus:ring-eht-teal-600"
                    >
                    <span>
                        <strong class="block text-slate-900">Individual</strong>
                        <span class="text-slate-500">Personal training receipt</span>
                    </span>
                </label>

                <label class="flex cursor-pointer items-start gap-2.5 rounded-lg border border-slate-300 p-3 text-xs hover:bg-slate-50">
                    <input
                        type="radio"
                        name="customer_type"
                        value="company"
                        {{ old('customer_type') === 'company' ? 'checked' : '' }}
                        onchange="document.getElementById('register-company-fields').classList.remove('hidden')"
                        class="mt-0.5 text-eht-navy-700 focus:ring-eht-teal-600"
                    >
                    <span>
                        <strong class="block text-slate-900">Company Buyer</strong>
                        <span class="text-slate-500">Include company billing</span>
                    </span>
                </label>
            </div>
        </fieldset>

        <div id="register-company-fields" class="{{ old('customer_type') === 'company' ? '' : 'hidden' }} space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs text-slate-600">
                Company billing details appear on your order receipt. Bookings remain registered to your personal account (D-023).
            </p>
            <div>
                <label for="company_name" class="block text-xs font-semibold text-slate-800">Company name <span class="text-rose-600">*</span></label>
                <input
                    id="company_name"
                    name="company_name"
                    type="text"
                    value="{{ old('company_name') }}"
                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none"
                    placeholder="Ecohydro Engineering Sdn. Bhd."
                >
            </div>
            <div>
                <label for="company_registration_no" class="block text-xs font-semibold text-slate-800">Company registration / SSM No. <span class="text-slate-400 font-normal">(Optional)</span></label>
                <input
                    id="company_registration_no"
                    name="company_registration_no"
                    type="text"
                    value="{{ old('company_registration_no') }}"
                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none"
                    placeholder="202001012345 (1368665-X)"
                >
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="password" class="block text-sm font-medium text-slate-800">Password <span class="text-rose-600">*</span></label>
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
                <label for="password_confirmation" class="block text-sm font-medium text-slate-800">Confirm password <span class="text-rose-600">*</span></label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    autocomplete="new-password"
                    class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-eht-teal-600 focus:outline-none focus:ring-2 focus:ring-eht-teal-600/20"
                >
            </div>
        </div>

        <x-eht.button type="submit" variant="primary" size="lg" class="w-full">
            Create Customer Account
        </x-eht.button>
    </form>

    <p class="mt-6 border-t border-slate-200 pt-4 text-center text-xs text-slate-600">
        Already registered?
        <a href="{{ route('login') }}" class="font-semibold text-eht-navy-700 hover:underline">Sign in here</a>
    </p>
</x-layouts.auth>
