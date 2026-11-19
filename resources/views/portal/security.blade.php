<x-layouts.portal title="Password & Security">
    <div class="space-y-6">
        <x-portal.page-header
            title="Password & Security"
            description="Keep your Ecohydrotech account secure by updating your password periodically."
        >
            <x-slot:actions>
                <x-portal.button :href="route('portal.profile.edit')" variant="secondary">
                    Profile &amp; Billing
                </x-portal.button>
            </x-slot:actions>
        </x-portal.page-header>

        @if ($errors->any())
            <x-portal.alert variant="danger" title="Password update could not be saved">
                <p>Please review the highlighted password fields below and try again.</p>
            </x-portal.alert>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <form method="POST" action="{{ route('portal.security.password.update') }}">
                    @csrf
                    @method('PUT')

                    <x-portal.card title="Change Password" subtitle="Choose a strong password that you do not reuse on other websites.">
                        <div class="space-y-5">
                            {{-- Current Password --}}
                            <div>
                                <label for="security-current-password" class="portal-label mb-1.5">
                                    <span>Current Password</span>
                                    <span class="text-rose-600" aria-hidden="true">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        id="security-current-password"
                                        name="current_password"
                                        type="password"
                                        required
                                        autocomplete="current-password"
                                        @error('current_password') aria-invalid="true" aria-describedby="security-current-password-error" @enderror
                                        class="portal-input pr-12 @error('current_password') border-rose-400 focus:border-rose-500 focus:ring-rose-500/15 @enderror"
                                    >
                                    <button
                                        type="button"
                                        data-toggle-password="security-current-password"
                                        aria-pressed="false"
                                        aria-label="Show current password"
                                        class="absolute inset-y-0 right-0 flex min-w-11 items-center justify-center rounded-r-lg text-portal-muted transition hover:text-portal-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-portal-primary"
                                    >
                                        <span class="text-[13px] font-medium" data-toggle-label>Show</span>
                                    </button>
                                </div>
                                @error('current_password')
                                    <p id="security-current-password-error" class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- New Password --}}
                            <div>
                                <label for="security-new-password" class="portal-label mb-1.5">
                                    <span>New Password</span>
                                    <span class="text-rose-600" aria-hidden="true">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        id="security-new-password"
                                        name="password"
                                        type="password"
                                        required
                                        minlength="8"
                                        autocomplete="new-password"
                                        @error('password') aria-invalid="true" aria-describedby="security-new-password-error" @else aria-describedby="security-new-password-hint" @enderror
                                        class="portal-input pr-12 @error('password') border-rose-400 focus:border-rose-500 focus:ring-rose-500/15 @enderror"
                                    >
                                    <button
                                        type="button"
                                        data-toggle-password="security-new-password"
                                        aria-pressed="false"
                                        aria-label="Show new password"
                                        class="absolute inset-y-0 right-0 flex min-w-11 items-center justify-center rounded-r-lg text-portal-muted transition hover:text-portal-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-portal-primary"
                                    >
                                        <span class="text-[13px] font-medium" data-toggle-label>Show</span>
                                    </button>
                                </div>
                                @error('password')
                                    <p id="security-new-password-error" class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>
                                @else
                                    <p id="security-new-password-hint" class="mt-1.5 text-[13px] text-portal-muted">
                                        Use at least 8 characters with a mix of letters, numbers, or symbols.
                                    </p>
                                @enderror
                            </div>

                            {{-- Confirm New Password --}}
                            <div>
                                <label for="security-confirm-password" class="portal-label mb-1.5">
                                    <span>Confirm New Password</span>
                                    <span class="text-rose-600" aria-hidden="true">*</span>
                                </label>
                                <div class="relative">
                                    <input
                                        id="security-confirm-password"
                                        name="password_confirmation"
                                        type="password"
                                        required
                                        minlength="8"
                                        autocomplete="new-password"
                                        @error('password_confirmation') aria-invalid="true" aria-describedby="security-confirm-password-error" @enderror
                                        class="portal-input pr-12 @error('password_confirmation') border-rose-400 focus:border-rose-500 focus:ring-rose-500/15 @enderror"
                                    >
                                    <button
                                        type="button"
                                        data-toggle-password="security-confirm-password"
                                        aria-pressed="false"
                                        aria-label="Show confirm new password"
                                        class="absolute inset-y-0 right-0 flex min-w-11 items-center justify-center rounded-r-lg text-portal-muted transition hover:text-portal-text focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-portal-primary"
                                    >
                                        <span class="text-[13px] font-medium" data-toggle-label>Show</span>
                                    </button>
                                </div>
                                @error('password_confirmation')
                                    <p id="security-confirm-password-error" class="mt-1.5 text-sm text-rose-700">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <x-slot:footer>
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="text-sm text-portal-muted">Your other active login details remain unchanged.</p>
                                <x-portal.button type="submit" variant="primary">
                                    Update Password
                                </x-portal.button>
                            </div>
                        </x-slot:footer>
                    </x-portal.card>
                </form>
            </div>

            {{-- Account Protection Status --}}
            <div class="space-y-6">
                <x-portal.card title="Account Status">
                    <dl class="space-y-3.5 text-[15px]">
                        <div>
                            <dt class="text-sm font-medium text-portal-muted">Account Email</dt>
                            <dd class="mt-0.5 break-all font-medium text-portal-text">{{ $user->email }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-sm font-medium text-portal-muted">Email Status</dt>
                            <dd>
                                @if ($user->email_verified_at)
                                    <x-portal.status-badge status="verified" label="Verified" />
                                @else
                                    <x-portal.status-badge status="warning" label="Unverified" />
                                @endif
                            </dd>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-sm font-medium text-portal-muted">Account State</dt>
                            <dd>
                                <x-portal.status-badge status="active" label="Active" />
                            </dd>
                        </div>
                    </dl>
                </x-portal.card>

                <x-portal.card title="Security Tips">
                    <ul class="space-y-2.5 text-[14px] leading-relaxed text-portal-muted">
                        <li>Avoid reusing passwords across work and personal accounts.</li>
                        <li>Review your recent orders and notifications periodically.</li>
                        <li>Always sign out when accessing the portal on shared computers.</li>
                    </ul>
                </x-portal.card>
            </div>
        </div>
    </div>

    <script>
        (function () {
            document.querySelectorAll('[data-toggle-password]').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const inputId = btn.getAttribute('data-toggle-password');
                    const input = inputId ? document.getElementById(inputId) : null;
                    const labelEl = btn.querySelector('[data-toggle-label]');
                    if (!input || !labelEl) {
                        return;
                    }

                    const willShow = input.type === 'password';
                    input.type = willShow ? 'text' : 'password';
                    btn.setAttribute('aria-pressed', willShow ? 'true' : 'false');
                    btn.setAttribute('aria-label', willShow ? 'Hide password' : 'Show password');
                    labelEl.textContent = willShow ? 'Hide' : 'Show';
                });
            });
        })();
    </script>
</x-layouts.portal>
