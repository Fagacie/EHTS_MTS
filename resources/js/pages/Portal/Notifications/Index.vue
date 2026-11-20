<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import PortalStatusBadge from '@/components/portal/PortalStatusBadge.vue';
import PublicPagination from '@/components/public/PublicPagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { useRoutes } from '@/composables/useRoutes';
import PortalLayout from '@/layouts/PortalLayout.vue';
import { cn } from '@/lib/utils';
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
        <div class="mx-auto max-w-[1440px] space-y-5">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-[24px] font-extrabold leading-tight tracking-tight text-[#282338] sm:text-[26px]">
                        Notifications
                    </h1>
                    <p class="mt-1 text-[13.5px] text-[#625D6D]">
                        Stay updated on your seat reservations, payment confirmations, and certificate availability.
                    </p>
                </div>

                <Button
                    v-if="props.unreadCount > 0"
                    type="button"
                    variant="outline"
                    class="shrink-0"
                    :disabled="busyAll"
                    @click="markAllAsRead"
                >
                    {{ busyAll ? 'Updating...' : 'Mark All Read' }}
                </Button>
            </header>

            <Card class="p-4 sm:px-5">
                <div
                    class="flex flex-wrap items-center gap-2"
                    role="navigation"
                    aria-label="Filter notifications"
                >
                    <Link
                        :href="`${routes.portalNotifications}?filter=all`"
                        preserve-scroll
                        :class="
                            cn(
                                'inline-flex min-h-8.5 items-center rounded-[8px] px-3.5 py-1.5 text-[12.5px] font-bold transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40',
                                props.filter !== 'unread'
                                    ? 'bg-[#7057D9] text-white'
                                    : 'border border-[#E5E1EB] bg-white text-[#403B4B] hover:border-[#B9ADE9] hover:bg-[#FAF9FE] hover:text-[#282338]',
                            )
                        "
                    >
                        All Notifications
                    </Link>

                    <Link
                        :href="`${routes.portalNotifications}?filter=unread`"
                        preserve-scroll
                        :class="
                            cn(
                                'inline-flex min-h-8.5 items-center rounded-[8px] px-3.5 py-1.5 text-[12.5px] font-bold tabular-nums transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#7057D9]/40',
                                props.filter === 'unread'
                                    ? 'bg-[#7057D9] text-white'
                                    : 'border border-[#E5E1EB] bg-white text-[#403B4B] hover:border-[#B9ADE9] hover:bg-[#FAF9FE] hover:text-[#282338]',
                            )
                        "
                    >
                        Unread ({{ props.unreadCount }})
                    </Link>
                </div>
            </Card>

            <Card
                v-if="props.notifications.length === 0"
                class="flex flex-col items-center justify-center px-6 py-12 text-center"
            >
                <p class="text-[16px] font-bold text-[#282338]">
                    {{ props.filter === 'unread' ? 'No unread notifications' : 'No notifications yet' }}
                </p>
                <p class="mt-1.5 max-w-lg text-[13.5px] text-[#625D6D]">
                    {{
                        props.filter === 'unread'
                            ? 'You are up to date. All recent notifications have already been read.'
                            : 'Updates about bookings, payments, session materials, and certificates will appear here.'
                    }}
                </p>

                <Button
                    v-if="props.filter === 'unread'"
                    as-child
                    variant="outline"
                    class="mt-6"
                >
                    <Link :href="`${routes.portalNotifications}?filter=all`" preserve-scroll>
                        View All Notifications
                    </Link>
                </Button>
            </Card>

            <template v-else>
                <div class="space-y-3">
                    <Card
                        v-for="notification in props.notifications"
                        :key="notification.id"
                        :class="
                            cn(
                                'p-4 sm:p-5',
                                notification.isUnread
                                    ? 'border-[#D9D0F2] bg-[#F1EDFB]/45'
                                    : 'border-[#E5E1EB] bg-white',
                            )
                        "
                    >
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0 space-y-2">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <h2 class="text-[15px] font-bold text-[#282338]">
                                        {{ notification.title }}
                                    </h2>

                                    <PortalStatusBadge
                                        v-if="notification.isUnread"
                                        status="unread"
                                        label="Unread"
                                    />

                                    <Badge
                                        v-if="notification.reference"
                                        variant="outline"
                                        class="tabular-nums"
                                    >
                                        {{ notification.reference }}
                                    </Badge>
                                </div>

                                <p
                                    v-if="notification.message !== ''"
                                    class="text-[13.5px] leading-relaxed text-[#292536]"
                                >
                                    {{ notification.message }}
                                </p>

                                <dl
                                    v-if="notification.details.length > 0"
                                    class="flex flex-wrap gap-x-5 gap-y-1.5 pt-0.5 text-[12.5px] text-[#625D6D]"
                                >
                                    <div
                                        v-for="pair in notification.details"
                                        :key="pair.label"
                                    >
                                        <dt class="inline font-bold text-[#282338]">
                                            {{ pair.label }}:
                                        </dt>
                                        <dd class="ml-1 inline">{{ pair.value }}</dd>
                                    </div>
                                </dl>

                                <p
                                    v-else-if="notification.detailText"
                                    class="text-[12.5px] text-[#625D6D]"
                                >
                                    {{ notification.detailText }}
                                </p>

                                <p class="text-[12px] text-[#625D6D] tabular-nums">
                                    {{ notification.createdAtLabel }}
                                </p>
                            </div>

                            <div class="flex shrink-0 flex-wrap items-center gap-2.5 sm:justify-end">
                                <template v-if="notification.actionUrl">
                                    <Button
                                        v-if="notification.isUnread"
                                        type="button"
                                        size="sm"
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
                                    >
                                        {{ notification.actionLabel }}
                                    </Button>
                                </template>

                                <Button
                                    v-if="notification.isUnread"
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    :disabled="busyNotificationId === notification.id"
                                    @click="markSingleAsRead(notification.id, notification.markReadUrl, false)"
                                >
                                    Mark Read
                                </Button>
                            </div>
                        </div>
                    </Card>
                </div>

                <PublicPagination
                    :pagination="props.pagination"
                    item-label="notifications"
                />
            </template>
        </div>
    </PortalLayout>
</template>
