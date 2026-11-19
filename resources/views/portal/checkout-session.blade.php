<x-layouts.portal title="Session Booking Checkout">
    @php
        $defaultCustomerType = $profile->customer_type?->value === 'company' ? 'company_sponsored' : 'individual';
        $selectedCustomerType = old('customer_type', $defaultCustomerType);
        $isCompanySponsored = $selectedCustomerType === 'company_sponsored';
    @endphp

    <div class="space-y-6">
        <div class="flex items-center justify-between gap-3">
            <a
                href="{{ route('sessions.show', $session) }}"
                class="inline-flex items-center gap-1.5 text-sm font-medium text-portal-muted transition hover:text-portal-primary"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 18-6-6 6-6" />
                </svg>
                <span>Back to Session Details</span>
            </a>
        </div>

        <x-portal.page-header
            title="Session Booking Checkout"
            description="Confirm your registration details to temporarily reserve your seat and proceed to online payment."
        >
            <x-slot:badges>
                @if ($session->module?->code)
                    <span class="inline-flex items-center rounded-md bg-portal-primary-soft px-2.5 py-1 text-[13px] font-semibold text-portal-primary">
                        {{ $session->module->code }}
                    </span>
                @endif
                @if ($session->session_code)
                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-[13px] font-medium text-portal-text">
                        {{ $session->session_code }}
                    </span>
                @endif
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

        @if ($existingHold)
            <x-portal.alert
                variant="warning"
                title="Active Seat Reservation Found ({{ $existingHold->booking_reference }})"
            >
                <p>
                    You already hold a seat for this session until {{ $existingHold->reservation_expires_at?->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') }} MYT. You can resume payment on your existing reservation.
                </p>
                <x-slot:action>
                    <x-portal.button :href="route('portal.bookings.show', $existingHold)" variant="primary" size="sm">
                        Resume Existing Hold
                    </x-portal.button>
                </x-slot:action>
            </x-portal.alert>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Checkout Form --}}
            <div class="lg:col-span-2">
                <form method="POST" action="{{ route('checkout.session.store', $session) }}" class="space-y-6">
                    @csrf

                    <x-portal.card title="1. Attendee &amp; Registration Type">
                        <div class="space-y-5">
                            <div class="grid gap-4 rounded-xl bg-portal-bg p-4 ring-1 ring-portal-border sm:grid-cols-2">
                                <div>
                                    <p class="text-sm font-medium text-portal-muted">Attendee Name</p>
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
                                        id="checkout-session-customer-type"
                                        name="customer_type"
                                        label="Booking Type"
                                        :required="true"
                                    >
                                        <option value="individual" @selected(! $isCompanySponsored)>Individual</option>
                                        <option value="company_sponsored" @selected($isCompanySponsored)>Company Sponsored</option>
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

                                <div id="checkout-session-company-wrap" class="sm:col-span-2 {{ $isCompanySponsored ? '' : 'hidden' }}">
                                    <x-portal.input
                                        id="checkout-session-company-name"
                                        name="company_name"
                                        label="Sponsoring Company / Organisation"
                                        :value="old('company_name', $profile->company_name)"
                                        autocomplete="organization"
                                        :required="$isCompanySponsored"
                                    />
                                </div>
                            </div>
                        </div>
                    </x-portal.card>

                    <x-portal.card title="2. Billing Address" subtitle="Saved automatically to your trainee profile for future bookings.">
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
                                    Proceeding reserves your seat for 15 minutes during online payment.
                                </p>
                                <x-portal.button type="submit" variant="primary" size="lg">
                                    Reserve Seat &amp; Pay {{ $session->formattedPrice() }}
                                </x-portal.button>
                            </div>
                        </x-slot:footer>
                    </x-portal.card>
                </form>
            </div>

            {{-- Summary Sidebar --}}
            <div class="space-y-6">
                <x-portal.card title="Session Summary">
                    <div class="space-y-4">
                        <div>
                            <h2 class="text-base font-semibold text-portal-text">
                                {{ $session->title }}
                            </h2>
                            @if ($session->module?->title)
                                <p class="mt-0.5 text-sm text-portal-muted">{{ $session->module->title }}</p>
                            @endif
                        </div>

                        <dl class="space-y-3 border-t border-portal-border pt-4 text-sm">
                            <div>
                                <dt class="text-portal-muted">Schedule</dt>
                                <dd class="mt-0.5 font-medium text-portal-text">{{ $session->formattedSchedule() }}</dd>
                            </div>
                            <div>
                                <dt class="text-portal-muted">Venue</dt>
                                <dd class="mt-0.5 text-portal-text">
                                    {{ $session->venue_name ?: 'TBA' }}{{ $session->venue_city ? ', '.$session->venue_city : '' }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-portal-muted">Available Seats</dt>
                                <dd class="font-semibold text-portal-text">{{ $session->remainingSeats() }} remaining</dd>
                            </div>
                        </dl>

                        <div class="flex items-center justify-between border-t border-portal-border pt-4">
                            <span class="text-[15px] font-semibold text-portal-text">Total Payable</span>
                            <span class="text-xl font-bold tabular-nums text-portal-primary">
                                {{ $session->formattedPrice() }}
                            </span>
                        </div>
                    </div>
                </x-portal.card>

                <x-portal.alert variant="info" title="15-Minute Seat Hold">
                    <p>
                        Submitting creates a pending booking and holds one seat for 15 minutes while you complete online payment via Billplz. Confirmed seats include access to this module's digital materials.
                    </p>
                </x-portal.alert>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const select = document.getElementById('checkout-session-customer-type');
            const companyWrap = document.getElementById('checkout-session-company-wrap');
            const companyInput = document.getElementById('checkout-session-company-name');

            if (!select || !companyWrap) {
                return;
            }

            const sync = () => {
                const show = select.value === 'company_sponsored';
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
