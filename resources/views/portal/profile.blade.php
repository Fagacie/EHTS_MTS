<x-layouts.portal title="Profile & Billing Details">

    <div class="space-y-6">
        <x-portal.page-header
            title="Profile & Billing Details"
            description="Keep your trainee contact details and billing address updated for faster registration and certificate records."
        >
            <x-slot:actions>
                <x-portal.button :href="route('portal.security.edit')" variant="secondary">
                    Password &amp; Security
                </x-portal.button>
            </x-slot:actions>
        </x-portal.page-header>

        @if ($errors->any())
            <x-portal.alert variant="danger" title="Please check the highlighted details">
                <p>Some fields could not be saved. Review the messages below and try again.</p>
            </x-portal.alert>
        @endif

        <form method="POST" action="{{ route('portal.profile.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    {{-- Personal & Trainee Account Details --}}
                    <x-portal.card title="Personal Details" subtitle="Your full name appears on training registrations and issued certificates.">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <x-portal.input
                                    name="name"
                                    label="Full Name"
                                    :value="old('name', $user->name)"
                                    autocomplete="name"
                                    :required="true"
                                    hint="Use your official full name as you wish it to appear on certificates."
                                />
                            </div>

                            <div>
                                <x-portal.input
                                    type="email"
                                    name="email"
                                    label="Email Address"
                                    :value="old('email', $user->email)"
                                    autocomplete="email"
                                    :required="true"
                                    hint="Changing your email requires re-verification."
                                />
                            </div>

                            <div>
                                <x-portal.input
                                    type="tel"
                                    name="phone"
                                    label="Mobile / Contact Phone"
                                    :value="old('phone', $profile->phone)"
                                    autocomplete="tel"
                                    placeholder="+60 12-345 6789"
                                    :optional="true"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <x-portal.input
                                    name="job_title"
                                    label="Role / Job Title"
                                    :value="old('job_title', $profile->job_title)"
                                    autocomplete="organization-title"
                                    placeholder="e.g. Water Resources Engineer"
                                    :optional="true"
                                />
                            </div>
                        </div>
                    </x-portal.card>

                    {{-- Billing Address --}}
                    <x-portal.card title="Billing Address" subtitle="Saved for your future session and module checkout orders.">
                        <div class="grid gap-5 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <x-portal.input
                                    name="billing_address_line_1"
                                    label="Address Line 1"
                                    :value="old('billing_address_line_1', $profile->billing_address_line_1)"
                                    autocomplete="address-line1"
                                    placeholder="Street address or suite"
                                    :optional="true"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <x-portal.input
                                    name="billing_address_line_2"
                                    label="Address Line 2"
                                    :value="old('billing_address_line_2', $profile->billing_address_line_2)"
                                    autocomplete="address-line2"
                                    placeholder="Apartment, building, or floor"
                                    :optional="true"
                                />
                            </div>

                            <div>
                                <x-portal.input
                                    name="billing_city"
                                    label="City"
                                    :value="old('billing_city', $profile->billing_city)"
                                    autocomplete="address-level2"
                                    :optional="true"
                                />
                            </div>

                            <div>
                                <x-portal.input
                                    name="billing_state"
                                    label="State / Wilayah"
                                    :value="old('billing_state', $profile->billing_state)"
                                    autocomplete="address-level1"
                                    :optional="true"
                                />
                            </div>

                            <div>
                                <x-portal.input
                                    name="billing_postcode"
                                    label="Postcode"
                                    :value="old('billing_postcode', $profile->billing_postcode)"
                                    autocomplete="postal-code"
                                    :optional="true"
                                />
                            </div>

                            <div>
                                <x-portal.input
                                    name="billing_country"
                                    label="Country Code"
                                    :value="old('billing_country', $profile->billing_country ?: 'MY')"
                                    maxlength="2"
                                    autocomplete="country"
                                    hint="Two-letter country code (e.g. MY)."
                                />
                            </div>
                        </div>

                        <x-slot:footer>
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="text-sm text-portal-muted">
                                    Fields marked <span class="text-rose-600">*</span> are required.
                                </p>
                                <x-portal.button type="submit" variant="primary">
                                    Save Profile Changes
                                </x-portal.button>
                            </div>
                        </x-slot:footer>
                    </x-portal.card>
                </div>

                {{-- Account Readiness Sidebar --}}
                <div class="space-y-6">
                    <x-portal.card title="Account Summary">
                        <dl class="space-y-3.5 text-[15px]">
                            <div>
                                <dt class="text-sm font-medium text-portal-muted">Signed-in Email</dt>
                                <dd class="mt-0.5 break-all font-medium text-portal-text">{{ $user->email }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-sm font-medium text-portal-muted">Email Verification</dt>
                                <dd>
                                    @if ($user->email_verified_at)
                                        <x-portal.status-badge status="verified" label="Verified" />
                                    @else
                                        <x-portal.status-badge status="warning" label="Unverified" />
                                    @endif
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-sm font-medium text-portal-muted">Checkout Profile</dt>
                                <dd>
                                    @if ($profile->isComplete())
                                        <x-portal.status-badge status="completed" label="Ready" />
                                    @else
                                        <x-portal.status-badge status="warning" label="Incomplete" />
                                    @endif
                                </dd>
                            </div>
                        </dl>
                    </x-portal.card>

                    <x-portal.card title="Need to update your password?">
                        <p class="text-[14px] leading-relaxed text-portal-muted">
                            Manage your login password in Security Settings without affecting your saved billing profile.
                        </p>
                        <div class="mt-4">
                            <x-portal.button :href="route('portal.security.edit')" variant="secondary" class="w-full justify-center">
                                Go to Password &amp; Security
                            </x-portal.button>
                        </div>
                    </x-portal.card>
                </div>
            </div>
        </form>
    </div>

</x-layouts.portal>
