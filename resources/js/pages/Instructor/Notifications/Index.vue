<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Bell, CheckCheck } from 'lucide-vue-next';
import PublicPagination from '@/components/public/PublicPagination.vue';
import { useRoutes } from '@/composables/useRoutes';
import InstructorLayout from '@/layouts/InstructorLayout.vue';
import type { InstructorNotificationsProps } from '@/types/etms';

interface Props extends InstructorNotificationsProps {
    allUrl?: string;
    unreadUrl?: string;
}

const props = defineProps<Props>();
const { routes } = useRoutes();

const busyAll = ref(false);
const busyNotificationId = ref<string | null>(null);

function markAllAsRead(): void {
    if (busyAll.value) return;

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
    if (busyNotificationId.value === notificationId) return;

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
    <Head title="Notifications — Ecohydrotech Training" />

    <InstructorLayout breadcrumb="Notifications" :unread-count="props.unreadCount">
        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title text-ink">Notifications</h1>
                    <p class="mt-1 text-sm text-muted">
                        Session assignments, attendance reminders, and platform announcements.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="inline-flex items-center gap-1 rounded-xl border border-line bg-surface p-1">
                        <Link
                            :href="props.allUrl || `${routes.instructorNotifications}?filter=all`"
                            preserve-scroll
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                            :class="
                                props.filter !== 'unread'
                                    ? 'bg-navy-950 text-white'
                                    : 'text-muted hover:text-ink'
                            "
                        >
                            All
                        </Link>
                        <Link
                            :href="props.unreadUrl || `${routes.instructorNotifications}?filter=unread`"
                            preserve-scroll
                            class="tabular rounded-lg px-3 py-1.5 text-xs font-medium transition"
                            :class="
                                props.filter === 'unread'
                                    ? 'bg-navy-950 text-white'
                                    : 'text-muted hover:text-ink'
                            "
                        >
                            Unread ({{ props.unreadCount }})
                        </Link>
                    </div>

                    <button
                        v-if="props.unreadCount > 0"
                        type="button"
                        :disabled="busyAll"
                        class="inline-flex h-9 items-center gap-1.5 rounded-xl border border-line bg-surface px-3.5 text-xs font-medium text-ink transition hover:bg-sand-100 disabled:opacity-50"
                        @click="markAllAsRead"
                    >
                        <CheckCheck class="size-3.5 text-muted" aria-hidden="true" />
                        <span>{{ busyAll ? 'Updating…' : 'Mark all read' }}</span>
                    </button>
                </div>
            </div>

            <!-- Empty State -->
            <section
                v-if="props.notifications.length === 0"
                class="rounded-2xl border border-line/85 bg-surface px-6 py-14 text-center shadow-card"
            >
                <Bell class="mx-auto size-8 text-muted/60" aria-hidden="true" />
                <p class="mt-2.5 text-sm font-semibold text-ink">
                    {{ props.filter === 'unread' ? 'No unread notifications' : 'No notifications yet' }}
                </p>
                <p class="mx-auto mt-1 max-w-sm text-xs text-muted">
                    {{
                        props.filter === 'unread'
                            ? 'All caught up — every notification has been read.'
                            : 'Assigned session updates and administrative messages will appear here.'
                    }}
                </p>
                <div v-if="props.filter === 'unread'" class="mt-4">
                    <Link
                        :href="props.allUrl || routes.instructorNotifications"
                        class="inline-flex h-9 items-center rounded-xl border border-line bg-surface px-4 text-xs font-medium text-ink transition hover:bg-sand-100"
                    >
                        View all notifications
                    </Link>
                </div>
            </section>

            <!-- Hairline-Divided Notifications List -->
            <template v-else>
                <section class="overflow-hidden rounded-2xl border border-line/85 bg-surface shadow-card">
                    <ul class="divide-y divide-line/70">
                        <li
                            v-for="notification in props.notifications"
                            :key="notification.id"
                            class="px-5 py-4 transition-colors hover:bg-sand-50/50"
                            :class="notification.isUnread ? 'bg-primary-50/25' : ''"
                        >
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div class="min-w-0 flex-1 space-y-1.5">
                                    <div class="flex flex-wrap items-center gap-2 text-xs">
                                        <span
                                            v-if="notification.isUnread"
                                            class="size-2 shrink-0 rounded-full bg-primary-600"
                                            aria-label="Unread"
                                        />
                                        <span
                                            v-if="notification.reference"
                                            class="tabular-nums-mono rounded-md bg-sand-100 px-2 py-0.5 text-[11px] font-semibold text-navy-950"
                                        >
                                            {{ notification.reference }}
                                        </span>
                                        <span class="tabular text-muted">
                                            {{ notification.createdAtLabel }}
                                        </span>
                                    </div>

                                    <h2 class="text-sm font-semibold text-ink">
                                        {{ notification.title }}
                                    </h2>

                                    <p v-if="notification.message" class="text-sm leading-relaxed text-muted">
                                        {{ notification.message }}
                                    </p>

                                    <dl
                                        v-if="notification.details && notification.details.length > 0"
                                        class="flex flex-wrap gap-x-5 gap-y-1 pt-1 text-xs text-muted"
                                    >
                                        <div v-for="pair in notification.details" :key="pair.label">
                                            <dt class="inline font-medium text-ink">{{ pair.label }}:</dt>
                                            <dd class="ml-1 inline">{{ pair.value }}</dd>
                                        </div>
                                    </dl>
                                    <p v-else-if="notification.detailText" class="text-xs text-muted">
                                        {{ notification.detailText }}
                                    </p>
                                </div>

                                <div class="flex shrink-0 flex-wrap items-center gap-2 sm:justify-end">
                                    <template v-if="notification.actionUrl">
                                        <button
                                            v-if="notification.isUnread"
                                            type="button"
                                            :disabled="busyNotificationId === notification.id"
                                            class="inline-flex h-8 items-center rounded-lg bg-navy-950 px-3 text-xs font-semibold text-white transition hover:bg-navy-900 disabled:opacity-50"
                                            @click="markSingleAsRead(notification.id, notification.markReadUrl, true)"
                                        >
                                            {{ notification.actionLabel }}
                                        </button>

                                        <Link
                                            v-else-if="notification.actionIsInertia"
                                            :href="notification.actionUrl"
                                            class="inline-flex h-8 items-center rounded-lg border border-line bg-surface px-3 text-xs font-medium text-ink transition hover:bg-sand-100"
                                        >
                                            {{ notification.actionLabel }}
                                        </Link>

                                        <a
                                            v-else
                                            :href="notification.actionUrl"
                                            class="inline-flex h-8 items-center rounded-lg border border-line bg-surface px-3 text-xs font-medium text-ink transition hover:bg-sand-100"
                                        >
                                            {{ notification.actionLabel }}
                                        </a>
                                    </template>

                                    <button
                                        v-if="notification.isUnread"
                                        type="button"
                                        :disabled="busyNotificationId === notification.id"
                                        class="inline-flex h-8 items-center rounded-lg border border-line bg-surface px-3 text-xs font-medium text-muted transition hover:bg-sand-100 hover:text-ink disabled:opacity-50"
                                        @click="markSingleAsRead(notification.id, notification.markReadUrl, false)"
                                    >
                                        Mark read
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
    </InstructorLayout>
</template>
