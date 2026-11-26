<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight } from 'lucide-vue-next';
import type { UpcomingSessionItem } from '@/types/etms';

const props = defineProps<{
    session: UpcomingSessionItem;
}>();

const subtitle = computed(() => {
    const parts = [props.session.dateRangeLabel];
    if (props.session.venueLocation) {
        parts.push(props.session.venueLocation);
    } else if (props.session.venueName) {
        parts.push(props.session.venueName);
    }
    return parts.join(' · ');
});
</script>

<template>
    <Link
        :href="session.detailUrl"
        class="group block rounded-2xl p-2 -m-2 transition-all duration-200 hover:bg-sand-50/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
    >
        <!-- Borderless Photo-First Media -->
        <div class="relative aspect-[16/10] overflow-hidden rounded-2xl bg-navy-900 shadow-card transition-shadow duration-200 group-hover:shadow-card-hover">
            <img
                v-if="session.coverImageUrl"
                :src="session.coverImageUrl"
                :alt="session.title"
                loading="lazy"
                class="h-full w-full object-cover transition-transform duration-300 ease-out group-hover:scale-[1.04]"
            />
            <div
                v-else
                class="flex h-full w-full flex-col justify-between bg-navy-900 p-5 text-white"
            >
                <span class="tabular-nums-mono text-xs font-medium text-white/60">
                    {{ session.sessionCode }}
                </span>
                <p class="line-clamp-2 text-base font-semibold leading-snug text-white">
                    {{ session.title }}
                </p>
            </div>

            <!-- Subtle Seat Dot Badge -->
            <span
                v-if="session.isOpenForBooking && session.remainingSeats > 0"
                class="tabular absolute top-3 left-3 inline-flex items-center gap-1.5 rounded-full bg-white/95 px-2.5 py-1 text-[11px] font-semibold text-navy-950 shadow-xs backdrop-blur-sm"
            >
                <span
                    class="size-1.5 rounded-full"
                    :class="session.remainingSeats <= 5 ? 'bg-warning-600' : 'bg-success-600'"
                    aria-hidden="true"
                />
                {{ session.remainingSeats }} seats left
            </span>
        </div>

        <!-- Minimal Editorial Metadata (Title, Date/City, Price + Hover Action Link) -->
        <div class="px-1 pt-3.5 pb-1">
            <p class="tabular line-clamp-1 text-xs font-medium text-muted">
                {{ subtitle }}
            </p>

            <h3 class="mt-1 line-clamp-2 text-[16px] font-semibold leading-snug text-navy-950 transition-colors duration-150 group-hover:text-primary-600">
                {{ session.title }}
            </h3>

            <div class="mt-3 flex items-center justify-between gap-3">
                <span class="tabular text-sm font-bold text-navy-950">
                    {{ session.priceLabel }}
                </span>

                <span class="inline-flex items-center gap-1 text-xs font-semibold text-muted transition-colors duration-150 group-hover:text-coral-600">
                    <span class="relative after:absolute after:bottom-0 after:left-0 after:h-[1.5px] after:w-0 after:bg-coral-500 after:transition-all after:duration-200 group-hover:after:w-full">
                        {{ session.isOpenForBooking ? 'Reserve seat' : 'View session' }}
                    </span>
                    <ArrowUpRight
                        class="size-3.5 transition-transform duration-200 group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                        aria-hidden="true"
                    />
                </span>
            </div>
        </div>
    </Link>
</template>
