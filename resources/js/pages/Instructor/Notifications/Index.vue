<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Bell, CheckCheck, Clock, ExternalLink } from 'lucide-vue-next';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import PublicPagination from '@/components/public/PublicPagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useRoutes } from '@/composables/useRoutes';
import InstructorLayout from '@/layouts/InstructorLayout.vue';
import { cn } from '@/lib/utils';
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
    <Head title="Instructor Notifications — Ecohydrotech Training" />

    <InstructorLayout
        breadcrumb="Notifications"
        :unread-count="props.unreadCount"
        title="Instructor Notification Centre"
        subtitle="Assigned training session schedules, participant reminders, session updates, and platform announcements."
    >
        <div class="mx-auto max-w-4xl space-y-6">
            <!-- Header Bar -->
            <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[#E5E1EB] bg-white p-5 sm:p-6 shadow-sm">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-[#292536]">
                        Instructor Notification Centre
                    </h1>
                    <p class="mt-1 text-sm text-[#625D6D]">
                        Assigned training sessions, attendance reminders, and platform announcements.
                    </p>
                </div>

                <div v-if="props.unreadCount > 0">
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="h-9 rounded-xl border-[#E5E1EB] px-3.5 text-xs font-semibold text-[#292536] hover:bg-[#F7F6FA]"
                        :disabled="busyAll"
                        @click="markAllAsRead"
                    >
                        <CheckCheck class="mr-1.5 h-3.5 w-3.5 text-[#7057D9]" />
                        {{ busyAll ? 'Updating...' : 'Mark All Read' }}
                    </Button>
                </div>
            </div>

            <!-- Filter Card -->
            <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-[#E5E1EB] bg-white p-4 shadow-sm">
                <Link
                    :href="props.allUrl || `${routes.instructorNotifications}?filter=all`"
                    preserve-scroll
                    :class="[
                        'rounded-xl px-3.5 py-1.5 text-xs font-bold transition',
                        props.filter !== 'unread'
                            ? 'bg-[#7057D9] text-white shadow-none'
                            : 'border border-[#E5E1EB] bg-[#F7F6FA] text-[#625D6D] hover:bg-[#EAE5F8] hover:text-[#5942B5]'
                    ]"
                >
                    All Notifications
                </Link>

                <Link
                    :href="props.unreadUrl || `${routes.instructorNotifications}?filter=unread`"
                    preserve-scroll
                    :class="[
                        'rounded-xl px-3.5 py-1.5 text-xs font-bold transition tabular-nums',
                        props.filter === 'unread'
                            ? 'bg-[#7057D9] text-white shadow-none'
                            : 'border border-[#E5E1EB] bg-[#F7F6FA] text-[#625D6D] hover:bg-[#EAE5F8] hover:text-[#5942B5]'
                    ]"
                >
                    Unread ({{ props.unreadCount }})
                </Link>
            </div>

            <!-- Empty State -->
            <div
                v-if="props.notifications.length === 0"
                class="rounded-2xl border border-[#E5E1EB] bg-white p-12 text-center shadow-sm"
            >
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-[#F1EDFB] text-[#7057D9]">
                    <Bell class="h-6 w-6" />
                </div>
                <h3 class="mt-3 text-base font-bold text-[#292536]">
                    {{ props.filter === 'unread' ? 'No unread notifications' : 'No notifications yet' }}
                </h3>
                <p class="mx-auto mt-1 max-w-sm text-xs text-[#625D6D]">
                    {{
                        props.filter === 'unread'
                            ? 'You are all caught up! All notifications have been read.'
                            : 'Assigned session updates and administrative messages will appear here.'
                    }}
                </p>
                <div v-if="props.filter === 'unread'" class="mt-4">
                    <Button as-child variant="outline" size="sm" class="rounded-xl border-[#E5E1EB] text-xs font-semibold">
                        <Link :href="props.allUrl || routes.instructorNotifications">
                            View All Notifications
                        </Link>
                    </Button>
                </div>
            </div>

            <!-- Notifications List -->
            <template v-else>
                <div class="space-y-3">
                    <div
                        v-for="notification in props.notifications"
                        :key="notification.id"
                        :class="[
                            'rounded-2xl border p-5 shadow-sm transition',
                            notification.isUnread
                                ? 'border-[#7057D9]/30 bg-white ring-1 ring-[#7057D9]/15'
                                : 'border-[#E5E1EB] bg-white'
                        ]"
                    >
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 flex-1 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <PortalStatusBadge
                                        v-if="notification.isUnread"
                                        status="unread"
                                        label="Unread"
                                    />
                                    <PortalStatusBadge
                                        v-else
                                        status="draft"
                                        label="Read"
                                    />

                                    <span
                                        v-if="notification.reference"
                                        class="rounded-md bg-[#F1EDFB] px-2 py-0.5 font-mono text-[11px] font-bold text-[#5942B5]"
                                    >
                                        {{ notification.reference }}
                                    </span>

                                    <span class="font-mono text-xs text-[#625D6D]">
                                        {{ notification.createdAtLabel }}
                                    </span>
                                </div>

                                <h2 class="text-base font-bold text-[#292536]">
                                    {{ notification.title }}
                                </h2>

                                <p v-if="notification.message" class="text-sm leading-relaxed text-[#625D6D]">
                                    {{ notification.message }}
                                </p>

                                <dl
                                    v-if="notification.details && notification.details.length > 0"
                                    class="flex flex-wrap gap-x-5 gap-y-1 pt-1 text-xs text-[#625D6D]"
                                >
                                    <div
                                        v-for="pair in notification.details"
                                        :key="pair.label"
                                    >
                                        <dt class="inline font-bold text-[#292536]">{{ pair.label }}:</dt>
                                        <dd class="ml-1 inline">{{ pair.value }}</dd>
                                    </div>
                                </dl>

                                <p v-else-if="notification.detailText" class="text-xs text-[#625D6D]">
                                    {{ notification.detailText }}
                                </p>
                            </div>

                            <div class="flex shrink-0 flex-wrap items-center gap-2 sm:justify-end">
                                <template v-if="notification.actionUrl">
                                    <Button
                                        v-if="notification.isUnread"
                                        type="button"
                                        size="sm"
                                        class="h-8 rounded-xl bg-[#7057D9] px-3 text-xs font-semibold text-white shadow-none hover:bg-[#5942B5]"
                                        :disabled="busyNotificationId === notification.id"
                                        @click="markSingleAsRead(notification.id, notification.markReadUrl, true)"
                                    >
                                        {{ notification.actionLabel }}
                                    </Button>

                                    <Button
                                        v-else-if="notification.actionIsInertia"
                                        as-child
                                        variant="outline"
                                        size="sm"
                                        class="h-8 rounded-xl border-[#E5E1EB] px-3 text-xs font-semibold text-[#292536] hover:bg-[#F7F6FA]"
                                    >
                                        <Link :href="notification.actionUrl">
                                            {{ notification.actionLabel }}
                                        </Link>
                                    </Button>

                                    <Button
                                        v-else
                                        as="a"
                                        :href="notification.actionUrl"
                                        variant="outline"
                                        size="sm"
                                        class="h-8 rounded-xl border-[#E5E1EB] px-3 text-xs font-semibold text-[#292536] hover:bg-[#F7F6FA]"
                                    >
                                        {{ notification.actionLabel }}
                                    </Button>
                                </template>

                                <Button
                                    v-if="notification.isUnread"
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    class="h-8 rounded-xl border-[#E5E1EB] px-3 text-xs font-semibold text-[#292536] hover:bg-[#F7F6FA]"
                                    :disabled="busyNotificationId === notification.id"
                                    @click="markSingleAsRead(notification.id, notification.markReadUrl, false)"
                                >
                                    Mark Read
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>

                <PublicPagination
                    :pagination="props.pagination"
                    item-label="notifications"
                />
            </template>
        </div>
    </InstructorLayout>
</template>
