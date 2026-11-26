<script setup lang="ts">
import { computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Save } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { EtmsSharedProps } from '@/types/etms';

interface SystemSettingsData {
    organisation_name: string;
    short_name: string;
    support_email: string;
    support_phone: string;
    company_address: string;
    display_timezone: string;
    currency: string;
    booking_hold_minutes: number;
    auto_close_hours: number;
    default_email_notifications: boolean;
    default_in_app_notifications: boolean;
    booking_confirmation_email: boolean;
    certificate_issued_email: boolean;
}

interface EnvironmentInfo {
    appEnv: string;
    laravelVersion: string;
    phpVersion: string;
    paymentGateway: string;
    videoTokenTtlMinutes: number;
    backupRetentionDays: number;
}

interface Props extends EtmsSharedProps {
    settings: SystemSettingsData;
    environment: EnvironmentInfo;
}

const props = defineProps<Props>();
const page = usePage<EtmsSharedProps>();

const validationErrors = computed<Record<string, string>>(
    () => (page.props.errors as Record<string, string>) ?? {},
);

const form = useForm<SystemSettingsData>({
    organisation_name: props.settings.organisation_name,
    short_name: props.settings.short_name,
    support_email: props.settings.support_email,
    support_phone: props.settings.support_phone,
    company_address: props.settings.company_address,
    display_timezone: props.settings.display_timezone,
    currency: props.settings.currency,
    booking_hold_minutes: props.settings.booking_hold_minutes,
    auto_close_hours: props.settings.auto_close_hours,
    default_email_notifications: props.settings.default_email_notifications,
    default_in_app_notifications: props.settings.default_in_app_notifications,
    booking_confirmation_email: props.settings.booking_confirmation_email,
    certificate_issued_email: props.settings.certificate_issued_email,
});

function submit(): void {
    form.post('/admin-vue/settings', {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="System Settings — Admin" />

    <AdminLayout breadcrumb="Settings">
        <form class="space-y-6" @submit.prevent="submit">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title text-ink">System Settings</h1>
                    <p class="mt-1 text-sm text-muted">
                        Configure organisation details, booking rules, regional defaults, and notification preferences.
                    </p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex h-10 items-center gap-2 rounded-xl bg-navy-950 px-4 text-sm font-semibold text-white transition hover:bg-navy-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600 disabled:opacity-50"
                >
                    <Save class="size-4" aria-hidden="true" />
                    <span>{{ form.processing ? 'Saving…' : 'Save settings' }}</span>
                </button>
            </div>

            <div
                v-if="Object.keys(validationErrors).length"
                class="rounded-2xl border border-danger-200 bg-danger-50 px-4 py-3 text-sm text-danger-700"
            >
                <p v-for="(msg, key) in validationErrors" :key="key">{{ msg }}</p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- Left Column: Editable Settings -->
                <div class="space-y-6 lg:col-span-8">
                    <!-- Organisation & Contact -->
                    <section class="rounded-2xl border border-line/85 bg-surface p-5 shadow-card">
                        <div class="border-b border-line/80 pb-3.5">
                            <h2 class="text-base font-semibold text-ink">Organisation &amp; contact</h2>
                            <p class="mt-0.5 text-xs text-muted">
                                Used across system notifications, receipts, and administrative reports.
                            </p>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>Organisation name</span>
                                <input
                                    v-model="form.organisation_name"
                                    required
                                    maxlength="150"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                            </label>

                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>Platform short name</span>
                                <input
                                    v-model="form.short_name"
                                    required
                                    maxlength="30"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                            </label>

                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>Support email</span>
                                <input
                                    v-model="form.support_email"
                                    type="email"
                                    required
                                    maxlength="255"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                            </label>

                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>Support phone</span>
                                <input
                                    v-model="form.support_phone"
                                    maxlength="40"
                                    placeholder="+60 3-xxxx xxxx"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                            </label>

                            <label class="flex flex-col gap-1 text-xs font-medium text-muted sm:col-span-2">
                                <span>Company address</span>
                                <textarea
                                    v-model="form.company_address"
                                    rows="2"
                                    maxlength="500"
                                    class="rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                            </label>
                        </div>
                    </section>

                    <!-- Regional & Booking Rules -->
                    <section class="rounded-2xl border border-line/85 bg-surface p-5 shadow-card">
                        <div class="border-b border-line/80 pb-3.5">
                            <h2 class="text-base font-semibold text-ink">Regional &amp; booking rules</h2>
                            <p class="mt-0.5 text-xs text-muted">
                                Default display timezone, billing currency, and seat reservation hold behaviour.
                            </p>
                        </div>

                        <div class="mt-4 grid gap-4 sm:grid-cols-2">
                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>Display timezone</span>
                                <select
                                    v-model="form.display_timezone"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                >
                                    <option value="Asia/Kuala_Lumpur">Asia/Kuala_Lumpur (MYT, UTC+8)</option>
                                    <option value="Asia/Singapore">Asia/Singapore (SGT, UTC+8)</option>
                                    <option value="UTC">UTC</option>
                                </select>
                            </label>

                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>Default currency</span>
                                <select
                                    v-model="form.currency"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                >
                                    <option value="MYR">MYR — Malaysian Ringgit</option>
                                    <option value="SGD">SGD — Singapore Dollar</option>
                                    <option value="USD">USD — US Dollar</option>
                                    <option value="NGN">NGN — Nigerian Naira</option>
                                </select>
                            </label>

                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>Seat reservation hold (minutes)</span>
                                <input
                                    v-model.number="form.booking_hold_minutes"
                                    type="number"
                                    min="5"
                                    max="60"
                                    required
                                    class="tabular h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                                <span class="text-[11px] font-normal text-muted">
                                    Unpaid session seats expire automatically after this window (default 15 min).
                                </span>
                            </label>

                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>Auto-close registration (hours before start)</span>
                                <input
                                    v-model.number="form.auto_close_hours"
                                    type="number"
                                    min="0"
                                    max="168"
                                    required
                                    class="tabular h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                                <span class="text-[11px] font-normal text-muted">
                                    Set to 0 to keep registration open until manually closed or start time.
                                </span>
                            </label>
                        </div>
                    </section>

                    <!-- Notification Preferences -->
                    <section class="rounded-2xl border border-line/85 bg-surface p-5 shadow-card">
                        <div class="border-b border-line/80 pb-3.5">
                            <h2 class="text-base font-semibold text-ink">Notification defaults</h2>
                            <p class="mt-0.5 text-xs text-muted">
                                Control automated transactional alerts and default announcement delivery channels.
                            </p>
                        </div>

                        <div class="mt-4 divide-y divide-line/70">
                            <label class="flex cursor-pointer items-center justify-between gap-4 py-3">
                                <div>
                                    <p class="text-sm font-medium text-ink">Booking confirmation emails</p>
                                    <p class="text-xs text-muted">
                                        Email trainees automatically when a session booking payment is verified.
                                    </p>
                                </div>
                                <input
                                    v-model="form.booking_confirmation_email"
                                    type="checkbox"
                                    class="size-4 shrink-0 rounded accent-navy-950"
                                />
                            </label>

                            <label class="flex cursor-pointer items-center justify-between gap-4 py-3">
                                <div>
                                    <p class="text-sm font-medium text-ink">Certificate issued emails</p>
                                    <p class="text-xs text-muted">
                                        Notify trainees by email when an official PDF certificate is published.
                                    </p>
                                </div>
                                <input
                                    v-model="form.certificate_issued_email"
                                    type="checkbox"
                                    class="size-4 shrink-0 rounded accent-navy-950"
                                />
                            </label>

                            <label class="flex cursor-pointer items-center justify-between gap-4 py-3">
                                <div>
                                    <p class="text-sm font-medium text-ink">Default announcement email channel</p>
                                    <p class="text-xs text-muted">
                                        Pre-select email delivery when drafting new announcements.
                                    </p>
                                </div>
                                <input
                                    v-model="form.default_email_notifications"
                                    type="checkbox"
                                    class="size-4 shrink-0 rounded accent-navy-950"
                                />
                            </label>

                            <label class="flex cursor-pointer items-center justify-between gap-4 py-3">
                                <div>
                                    <p class="text-sm font-medium text-ink">Default announcement in-app channel</p>
                                    <p class="text-xs text-muted">
                                        Pre-select in-app portal notifications when drafting new announcements.
                                    </p>
                                </div>
                                <input
                                    v-model="form.default_in_app_notifications"
                                    type="checkbox"
                                    class="size-4 shrink-0 rounded accent-navy-950"
                                />
                            </label>
                        </div>
                    </section>
                </div>

                <!-- Right Column: Runtime Diagnostics -->
                <aside class="space-y-6 lg:col-span-4">
                    <section class="rounded-2xl border border-line/85 bg-surface p-5 shadow-card">
                        <div class="border-b border-line/80 pb-3.5">
                            <h2 class="text-base font-semibold text-ink">Runtime environment</h2>
                            <p class="mt-0.5 text-xs text-muted">
                                Read-only server configuration managed via environment variables.
                            </p>
                        </div>

                        <dl class="mt-3 divide-y divide-line/70 text-sm">
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-muted">Environment</dt>
                                <dd class="font-medium text-ink capitalize">{{ environment.appEnv }}</dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-muted">Laravel</dt>
                                <dd class="tabular font-medium text-ink">v{{ environment.laravelVersion }}</dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-muted">PHP</dt>
                                <dd class="tabular font-medium text-ink">{{ environment.phpVersion }}</dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-muted">Payment gateway</dt>
                                <dd class="font-medium text-ink">{{ environment.paymentGateway }}</dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-muted">Video token TTL</dt>
                                <dd class="tabular font-medium text-ink">{{ environment.videoTokenTtlMinutes }} min</dd>
                            </div>
                            <div class="flex items-center justify-between py-2.5">
                                <dt class="text-muted">Backup retention</dt>
                                <dd class="tabular font-medium text-ink">{{ environment.backupRetentionDays }} days</dd>
                            </div>
                        </dl>
                    </section>
                </aside>
            </div>
        </form>
    </AdminLayout>
</template>
