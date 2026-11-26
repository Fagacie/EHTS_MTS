<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowUpRight, Bell, Check, CheckCheck } from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import EmptyState from '@/components/ds/EmptyState.vue';
import PublicPagination from '@/components/public/PublicPagination.vue';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import type { PortalNotificationsIndexProps } from '@/types/etms';

const props = defineProps<PortalNotificationsIndexProps>();

const { routes } = useRoutes();
const busyAll = ref(false);
const busyNotificationId = ref<string | null>(null);

function markAllAsRead(): void {
    if (busyAll.value) {
        return;
    }

    busyAll.value = true;
    router.post(
        props.markAllReadUrl,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                busyAll.value = false;
            },
        },
    );
}

function markSingleAsRead(notificationId: string, markReadUrl: string, openAfter: boolean): void {
    if (busyNotificationId.value === notificationId) {
        return;
    }

    busyNotificationId.value = notificationId;
    router.post(
        markReadUrl,
        openAfter ? { open: 1 } : {},
        {
            preserveScroll: !openAfter,
            onFinish: () => {
                busyNotificationId.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Notifications" />

    <PortalLayout
        breadcrumb="Notifications"
        :unread-count="props.unreadCount"
    >
        <div class="mx-auto max-w-[1040px] space-y-6">
            <!-- Header -->
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title">Notifications</h1>
                    <p class="mt-1 text-sm text-muted">
                        Updates on your bookings, payments, course materials, and certificates.
                    </p>
                </div>

                <button
                    v-if="props.unreadCount > 0"
                    type="button"
                    class="inline-flex h-9 shrink-0 items-center gap-1.5 rounded-lg bg-white px-3.5 text-xs font-semibold text-navy-950 ring-1 ring-line transition-colors hover:bg-sand-50 disabled:opacity-50"
                    :disabled="busyAll"
                    @click="markAllAsRead"
                >
                    <CheckCheck class="size-3.5 text-primary-600" aria-hidden="true" />
                    <span>{{ busyAll ? 'Updating...' : 'Mark all read' }}</span>
                </button>
            </header>

            <!-- Simple Filter Pills -->
            <nav
                aria-label="Filter notifications"
                class="flex flex-wrap items-center gap-2"
            >
                <Link
                    :href="`${routes.portalNotifications}?filter=all`"
                    preserve-scroll
                    class="inline-flex items-center rounded-full px-4 py-1.5 text-xs font-semibold transition-colors duration-150"
                    :class="
                        props.filter !== 'unread'
                            ? 'bg-navy-950 text-white'
                            : 'bg-white text-muted ring-1 ring-line hover:text-navy-950'
                    "
                >
                    All
                </Link>

                <Link
                    :href="`${routes.portalNotifications}?filter=unread`"
                    preserve-scroll
                    class="tabular inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-semibold transition-colors duration-150"
                    :class="
                        props.filter === 'unread'
                            ? 'bg-navy-950 text-white'
                            : 'bg-white text-muted ring-1 ring-line hover:text-navy-950'
                    "
                >
                    <span>Unread</span>
                    <span
                        v-if="props.unreadCount > 0"
                        class="text-[11px] opacity-80"
                    >
                        ({{ props.unreadCount }})
                    </span>
                </Link>
            </nav>

            <!-- Empty State -->
            <EmptyState
                v-if="props.notifications.length === 0"
                :icon="Bell"
                :title="props.filter === 'unread' ? 'No unread notifications' : 'No notifications yet'"
                :description="
                    props.filter === 'unread'
                        ? 'You are all caught up.'
                        : 'Updates about your bookings, payments, and certificates will appear here.'
                "
            >
                <template #action>
                    <AppButton
                        v-if="props.filter === 'unread'"
                        :href="`${routes.portalNotifications}?filter=all`"
                        variant="outline"
                        size="sm"
                        pill
                    >
                        View all notifications
                    </AppButton>
                </template>
            </EmptyState>

            <!-- Clean Notifications List -->
            <template v-else>
                <section class="overflow-hidden rounded-2xl bg-white ring-1 ring-neutral-200/80">
                    <ul class="divide-y divide-line/70">
                        <li
                            v-for="notification in props.notifications"
                            :key="notification.id"
                            class="px-5 py-4 transition-colors duration-150 sm:px-6"
                            :class="
                                notification.isUnread
                                    ? 'bg-primary-50/30 hover:bg-primary-50/50'
                                    : 'bg-white hover:bg-sand-50/60'
                            "
                        >
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between sm:gap-6">
                                <!-- Content -->
                                <div class="flex min-w-0 items-start gap-3">
                                    <span
                                        class="mt-1.5 size-2 shrink-0 rounded-full"
                                        :class="notification.isUnread ? 'bg-primary-600' : 'bg-transparent'"
                                        aria-hidden="true"
                                    />

                                    <div class="min-w-0 space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h2
                                                class="text-sm text-navy-950"
                                                :class="notification.isUnread ? 'font-semibold' : 'font-medium'"
                                            >
                                                {{ notification.title }}
                                            </h2>
                                            <span
                                                v-if="notification.reference"
                                                class="tabular-nums-mono text-xs text-muted"
                                            >
                                                &middot; {{ notification.reference }}
                                            </span>
                                        </div>

                                        <p
                                            v-if="notification.message !== ''"
                                            class="text-sm leading-relaxed text-muted"
                                        >
                                            {{ notification.message }}
                                        </p>

                                        <dl
                                            v-if="notification.details.length > 0"
                                            class="flex flex-wrap gap-x-4 gap-y-1 pt-0.5 text-xs text-muted"
                                        >
                                            <div v-for="pair in notification.details" :key="pair.label">
                                                <dt class="inline font-medium text-ink/80">{{ pair.label }}:</dt>
                                                <dd class="ml-1 inline">{{ pair.value }}</dd>
                                            </div>
                                        </dl>

                                        <p
                                            v-else-if="notification.detailText"
                                            class="text-xs text-muted"
                                        >
                                            {{ notification.detailText }}
                                        </p>

                                        <p class="tabular pt-0.5 text-xs text-muted/85">
                                            {{ notification.createdAtLabel }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex shrink-0 items-center gap-2 pl-5 sm:pl-0">
                                    <template v-if="notification.actionUrl">
                                        <button
                                            v-if="notification.isUnread"
                                            type="button"
                                            class="inline-flex h-8 items-center gap-1 rounded-full bg-navy-950 px-3.5 text-xs font-semibold text-white transition-colors hover:bg-navy-900 disabled:opacity-50"
                                            :disabled="busyNotificationId === notification.id"
                                            @click="markSingleAsRead(notification.id, notification.markReadUrl, true)"
                                        >
                                            <span>{{ notification.actionLabel }}</span>
                                            <ArrowUpRight class="size-3.5" aria-hidden="true" />
                                        </button>

                                        <Link
                                            v-else-if="notification.actionIsInertia"
                                            :href="notification.actionUrl"
                                            class="inline-flex h-8 items-center gap-1 rounded-full bg-sand-50 px-3.5 text-xs font-semibold text-navy-950 ring-1 ring-line transition-colors hover:bg-navy-950 hover:text-white hover:ring-navy-950"
                                        >
                                            <span>{{ notification.actionLabel }}</span>
                                            <ArrowUpRight class="size-3.5" aria-hidden="true" />
                                        </Link>

                                        <a
                                            v-else
                                            :href="notification.actionUrl"
                                            class="inline-flex h-8 items-center gap-1 rounded-full bg-sand-50 px-3.5 text-xs font-semibold text-navy-950 ring-1 ring-line transition-colors hover:bg-navy-950 hover:text-white hover:ring-navy-950"
                                        >
                                            <span>{{ notification.actionLabel }}</span>
                                            <ArrowUpRight class="size-3.5" aria-hidden="true" />
                                        </a>
                                    </template>

                                    <button
                                        v-if="notification.isUnread"
                                        type="button"
                                        class="inline-flex h-8 items-center gap-1 rounded-full px-3 text-xs font-medium text-muted transition-colors hover:bg-white hover:text-navy-950 disabled:opacity-50"
                                        :disabled="busyNotificationId === notification.id"
                                        @click="markSingleAsRead(notification.id, notification.markReadUrl, false)"
                                    >
                                        <Check class="size-3.5" aria-hidden="true" />
                                        <span>Mark read</span>
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>
                </section>

                <PublicPagination
                    :pagination="props.pagination"
                    item-label="notifications"
                />
            </template>
        </div>
    </PortalLayout>
</template>
