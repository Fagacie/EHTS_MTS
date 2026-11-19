<x-layouts.portal title="Module Purchase Checkout">
    @php
        $selectedCustomerType = old('customer_type', $profile->customer_type?->value ?? 'individual');
        $isCompany = $selectedCustomerType === 'company';
        $videoCount = $module->publishedVideos()->count();
        $materialCount = $module->publishedMaterials()->count();
    @endphp

    <div class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <a
                href="{{ route('catalogue.modules.show', $module) }}"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-portal-muted transition hover:text-portal-primary"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 18-6-6 6-6" />
                </svg>
                <span>Back to Module Overview</span>
            </a>
        </div>

        <x-portal.page-header
            title="Independent Module Checkout"
            description="Complete your billing details to unlock lifetime digital access to this module's learning materials and videos."
        >
            <x-slot:badges>
                @if ($module->category)
                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-[13px] font-medium text-portal-text">
                        {{ $module->category->name }}
                    </span>
                @endif
                <span class="inline-flex items-center rounded-md bg-portal-primary-soft px-2.5 py-1 text-[13px] font-semibold text-portal-primary">
                    {{ $module->code }}
                </span>
            </x-slot:badges>
        </x-portal.page-header>

        @if (session('error'))
            <x-portal.alert variant="danger" title="Unable to Continue Checkout">
                <p>{{ session('error') }}</p>
            </x-portal.alert>
        @endif

        @if ($errors->any())
            <x-portal.alert variant="danger" title="Please check the highlighted details">
                <p>Complete all required registration and billing fields before proceeding to payment.</p>
            </x-portal.alert>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Billing Form --}}
            <div class="lg:col-span-2">
                <form method="POST" action="{{ route('checkout.module.store', $module) }}" class="space-y-6">
                    @csrf

                    <x-portal.card title="1. Learner &amp; Billing Classification">
                        <div class="space-y-5">
                            <div class="grid gap-4 rounded-xl bg-portal-bg p-4 ring-1 ring-portal-border sm:grid-cols-2">
                                <div>
                                    <p class="text-sm font-medium text-portal-muted">Learner Name</p>
                                    <p class="mt-0.5 text-[15px] font-semibold text-portal-text">{{ $user->name }}</p>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-portal-muted">Account Email</p>
                                    <p class="mt-0.5 break-all text-[15px] text-portal-text">{{ $user->email }}</p>
                                </div>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <x-portal.select
                                        id="checkout-module-customer-type"
                                        name="customer_type"
                                        label="Account Type"
                                        :required="true"
                                    >
                                        <option value="individual" @selected(! $isCompany)>Individual</option>
                                        <option value="company" @selected($isCompany)>Company / Organisation</option>
                                    </x-portal.select>
                                </div>

                                <div>
                                    <x-portal.input
                                        type="tel"
                                        name="phone"
                                        label="Contact Phone Number"
                                        :value="old('phone', $profile->phone)"
                                        autocomplete="tel"
                                        placeholder="+60 12-345 6789"
                                        :required="true"
                                    />
                                </div>

                                <div id="checkout-module-company-wrap" class="sm:col-span-2 {{ $isCompany ? '' : 'hidden' }}">
                                    <x-portal.input
                                        id="checkout-module-company-name"
                                        name="company_name"
                                        label="Company / Organisation Name"
                                        :value="old('company_name', $profile->company_name)"
                                        autocomplete="organization"
                                        :required="$isCompany"
                                    />
                                </div>
                            </div>
                        </div>
                    </x-portal.card>

                    <x-portal.card title="2. Billing Address">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <x-portal.input
                                    name="billing_address_line1"
                                    label="Billing Street Address"
                                    :value="old('billing_address_line1', $profile->billing_address_line_1)"
                                    autocomplete="address-line1"
                                    :required="true"
                                />
                            </div>

                            <div>
                                <x-portal.input
                                    name="billing_city"
                                    label="City"
                                    :value="old('billing_city', $profile->billing_city)"
                                    autocomplete="address-level2"
                                    :required="true"
                                />
                            </div>

                            <div>
                                <x-portal.input
                                    name="billing_state"
                                    label="State / Wilayah"
                                    :value="old('billing_state', $profile->billing_state)"
                                    autocomplete="address-level1"
                                    :required="true"
                                />
                            </div>

                            <div>
                                <x-portal.input
                                    name="billing_postcode"
                                    label="Postcode"
                                    :value="old('billing_postcode', $profile->billing_postcode)"
                                    autocomplete="postal-code"
                                    :required="true"
                                />
                            </div>
                        </div>

                        <x-slot:footer>
                            <div class="flex flex-wrap items-center justify-between gap-4">
                                <p class="text-sm text-portal-muted">
                                    Access unlocks immediately upon verified online payment confirmation.
                                </p>
                                <x-portal.button type="submit" variant="primary" size="lg">
                                    Proceed to Pay {{ $module->formattedIndependentPrice() }}
                                </x-portal.button>
                            </div>
                        </x-slot:footer>
                    </x-portal.card>
                </form>
            </div>

            {{-- Summary Sidebar --}}
            <div class="space-y-6">
                <x-portal.card title="Module Purchase Summary">
                    <div class="space-y-4">
                        <div>
                            <h2 class="text-base font-semibold text-portal-text">
                                {{ $module->title }}
                            </h2>
                            @if ($module->summary)
                                <p class="mt-1 text-sm leading-relaxed text-portal-muted">{{ $module->summary }}</p>
                            @endif
                        </div>

                        <dl class="space-y-3 border-t border-portal-border pt-4 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-portal-muted">Access Type</dt>
                                <dd class="font-medium text-portal-text">Lifetime Digital Access</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-portal-muted">Video Lessons</dt>
                                <dd class="font-medium text-portal-text">{{ $videoCount }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-portal-muted">Downloadable Materials</dt>
                                <dd class="font-medium text-portal-text">{{ $materialCount }}</dd>
                            </div>
                        </dl>

                        <div class="flex items-center justify-between border-t border-portal-border pt-4">
                            <span class="text-[15px] font-semibold text-portal-text">Total Payable</span>
                            <span class="text-xl font-bold tabular-nums text-portal-primary">
                                {{ $module->formattedIndependentPrice() }}
                            </span>
                        </div>
                    </div>
                </x-portal.card>

                <x-portal.alert variant="info" title="Self-Paced Digital Learning">
                    <p>
                        Independent module purchases include on-demand video lessons and reference materials, separate from physical session seat reservations.
                    </p>
                </x-portal.alert>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const select = document.getElementById('checkout-module-customer-type');
            const companyWrap = document.getElementById('checkout-module-company-wrap');
            const companyInput = document.getElementById('checkout-module-company-name');

            if (!select || !companyWrap) {
                return;
            }

            const sync = () => {
                const show = select.value === 'company';
                companyWrap.classList.toggle('hidden', !show);
                if (companyInput) {
                    if (show) {
                        companyInput.setAttribute('required', 'required');
                    } else {
                        companyInput.removeAttribute('required');
                    }
                }
            };

            select.addEventListener('change', sync);
            sync();
        })();
    </script>
</x-layouts.portal>
