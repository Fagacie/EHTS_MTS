<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, Calendar, Check, Download, FileText, Lock, PlayCircle } from 'lucide-vue-next';
import ProgrammeBanner from '@/components/public/ProgrammeBanner.vue';
import SessionsDataTable from '@/components/public/SessionsDataTable.vue';
import { Badge } from '@/components/ui/badge';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { useAuth } from '@/composables/useAuth';
import { useRoutes } from '@/composables/useRoutes';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { formatDisplayFee, pluralize } from '@/lib/utils';
import type { CatalogueShowProps } from '@/types/etms';

const props = defineProps<CatalogueShowProps>();

const { routes } = useRoutes();
const { isAuthenticated } = useAuth();

const nextOpenSession = computed(
    () =>
        props.upcomingSessions.find((session) => session.isOpenForBooking) ??
        props.upcomingSessions[0] ??
        null,
);

const hasLessonPreview = computed(
    () => props.materials.length > 0 || props.videos.length > 0,
);
</script>

<template>
    <Head :title="module.title" />

    <PublicLayout>
        <section class="border-b border-[#272d38]/10 bg-white py-6 lg:py-8">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <Breadcrumb>
                    <BreadcrumbList>
                        <BreadcrumbItem>
                            <BreadcrumbLink :href="routes.catalogue">Catalogue</BreadcrumbLink>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator />
                        <BreadcrumbItem>
                            <BreadcrumbLink :href="module.categoryUrl">{{ module.categoryName }}</BreadcrumbLink>
                        </BreadcrumbItem>
                        <BreadcrumbSeparator />
                        <BreadcrumbItem>
                            <BreadcrumbPage class="line-clamp-1">{{ module.title }}</BreadcrumbPage>
                        </BreadcrumbItem>
                    </BreadcrumbList>
                </Breadcrumb>
            </div>
        </section>

        <section class="bg-white py-10 lg:py-14">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="grid gap-10 lg:grid-cols-12 lg:items-start">
                    <div class="space-y-10 lg:col-span-8">
                        <div>
                            <div class="overflow-hidden rounded-xl border border-[#272d38]/12 bg-white shadow-[0_1px_2px_rgba(39,45,56,0.04)]">
                                <ProgrammeBanner
                                    :cover-image-url="module.coverImageUrl"
                                    :category-slug="module.categorySlug"
                                    :title="module.title"
                                    aspect-class="aspect-[21/9]"
                                    :bordered-bottom="false"
                                />
                            </div>

                            <div class="mt-6">
                                <p class="text-[14px] text-[#272d38]/65 tabular-nums">
                                    {{ module.categoryName }} &middot; {{ module.code }}
                                </p>
                                <h1 class="mt-2 text-3xl font-bold text-[#272d38] sm:text-4xl">
                                    {{ module.title }}
                                </h1>
                                <p
                                    v-if="module.summary"
                                    class="mt-4 text-[16px] leading-relaxed text-[#272d38]/70"
                                >
                                    {{ module.summary }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="module.learningObjectives.length > 0"
                            class="border-t border-[#272d38]/10 pt-8"
                        >
                            <h2 class="text-[22px] font-bold text-[#272d38]">
                                Learning Outcomes
                            </h2>
                            <ul class="mt-5 grid gap-3.5 sm:grid-cols-2">
                                <li
                                    v-for="objective in module.learningObjectives"
                                    :key="objective"
                                    class="flex items-start gap-2.5 text-[15px] leading-relaxed text-[#272d38]"
                                >
                                    <Check class="mt-1 h-4 w-4 shrink-0 text-[#272d38]" aria-hidden="true" />
                                    <span>{{ objective }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="border-t border-[#272d38]/10 pt-8">
                            <h2 class="text-[22px] font-bold text-[#272d38]">
                                Programme Syllabus &amp; Overview
                            </h2>
                            <div class="mt-4 whitespace-pre-line text-[16px] leading-relaxed text-[#272d38]/75">
                                {{ module.description }}
                            </div>
                        </div>

                        <div class="border-t border-[#272d38]/10 pt-8">
                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                <h2 class="text-[22px] font-bold text-[#272d38]">
                                    Included Materials &amp; Lessons
                                </h2>
                                <p class="text-[14px] text-[#272d38]/65 tabular-nums">
                                    {{ materials.length }} {{ pluralize(materials.length, 'workbook') }}
                                    <template v-if="videos.length > 0">
                                        &middot; {{ videos.length }} {{ pluralize(videos.length, 'lesson recording') }}
                                    </template>
                                </p>
                            </div>

                            <Card v-if="hasLessonPreview" class="mt-5 divide-y divide-[#272d38]/8 overflow-hidden">
                                <div
                                    v-for="material in materials"
                                    :key="`mat-${material.id}`"
                                    class="flex flex-wrap items-center justify-between gap-4 p-4 sm:px-5"
                                >
                                    <div class="flex min-w-0 items-start gap-3">
                                        <FileText class="mt-0.5 h-4 w-4 shrink-0 text-[#272d38]/70" aria-hidden="true" />
                                        <div class="min-w-0">
                                            <p class="text-[15px] font-bold text-[#272d38]">
                                                {{ material.title }}
                                            </p>
                                            <p
                                                v-if="material.description"
                                                class="mt-0.5 text-[14px] text-[#272d38]/65"
                                            >
                                                {{ material.description }}
                                            </p>
                                            <p class="mt-1 text-[14px] text-[#272d38]/60 tabular-nums">
                                                {{ material.typeLabel }} &middot; {{ material.fileSizeLabel }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="shrink-0">
                                        <Button
                                            v-if="material.canDownload && material.downloadUrl"
                                            as="a"
                                            :href="material.downloadUrl"
                                            variant="outline"
                                            size="sm"
                                        >
                                            <Download class="h-4 w-4" aria-hidden="true" />
                                            <span>Download</span>
                                        </Button>
                                        <Badge v-else variant="outline" class="gap-1.5 px-3 py-1 font-normal text-[#272d38]/70">
                                            <Lock class="h-3.5 w-3.5" aria-hidden="true" />
                                            <span>Portal access after purchase</span>
                                        </Badge>
                                    </div>
                                </div>

                                <div
                                    v-for="video in videos"
                                    :key="`vid-${video.id}`"
                                    class="flex flex-wrap items-center justify-between gap-4 p-4 sm:px-5"
                                >
                                    <div class="flex min-w-0 items-start gap-3">
                                        <PlayCircle class="mt-0.5 h-4 w-4 shrink-0 text-[#272d38]/70" aria-hidden="true" />
                                        <div class="min-w-0">
                                            <p class="text-[15px] font-bold text-[#272d38]">
                                                {{ video.title }}
                                            </p>
                                            <p
                                                v-if="video.description"
                                                class="mt-0.5 text-[14px] text-[#272d38]/65"
                                            >
                                                {{ video.description }}
                                            </p>
                                            <p class="mt-1 text-[14px] text-[#272d38]/60 tabular-nums">
                                                Video Lesson &middot; {{ video.durationLabel }}
                                            </p>
                                        </div>
                                    </div>

                                    <Badge variant="outline" class="gap-1.5 px-3 py-1 font-normal text-[#272d38]/70">
                                        <Lock class="h-3.5 w-3.5" aria-hidden="true" />
                                        <span>Portal video access</span>
                                    </Badge>
                                </div>
                            </Card>

                            <p v-else class="mt-4 text-[16px] text-[#272d38]/70">
                                Technical workbooks and reference materials are provided directly inside your learning portal.
                            </p>
                        </div>

                        <div id="programme-sessions" class="border-t border-[#272d38]/10 pt-8">
                            <div class="flex flex-wrap items-end justify-between gap-4">
                                <h2 class="text-2xl font-bold text-[#272d38]">
                                    Upcoming Physical Sessions
                                </h2>

                                <Link
                                    :href="routes.sessions"
                                    class="group inline-flex items-center gap-1.5 text-[15px] font-bold text-[#272d38] hover:underline underline-offset-4"
                                >
                                    <span>All sessions</span>
                                    <ArrowRight class="h-4 w-4 transition-transform duration-150 group-hover:translate-x-0.5" aria-hidden="true" />
                                </Link>
                            </div>

                            <SessionsDataTable
                                v-if="upcomingSessions.length > 0"
                                :sessions="upcomingSessions"
                                class="mt-6"
                            />

                            <Card
                                v-else
                                class="mt-5 p-6 text-[16px] text-[#272d38]/70"
                            >
                                No upcoming physical cohorts are currently scheduled for this programme.
                                <Link
                                    :href="routes.sessions"
                                    class="ml-1 font-bold text-[#272d38] underline underline-offset-4 hover:opacity-80"
                                >
                                    View all scheduled workshops
                                </Link>
                            </Card>
                        </div>
                    </div>

                    <aside class="space-y-6 lg:sticky lg:top-24 lg:col-span-4">
                        <Card
                            v-if="module.isAvailableIndependently && module.independentPriceLabel"
                            class="p-6"
                        >
                            <div class="flex items-center gap-2 text-[14px] font-bold text-[#272d38]">
                                <BookOpen class="h-4 w-4 text-[#272d38]" aria-hidden="true" />
                                <span>Independent Learning</span>
                            </div>

                            <div class="mt-4 flex items-baseline gap-2">
                                <span class="text-3xl font-bold text-[#272d38] tabular-nums">
                                    {{ formatDisplayFee(module.independentPriceLabel) }}
                                </span>
                                <span class="text-[14px] text-[#272d38]/65">one-time fee</span>
                            </div>

                            <ul class="mt-5 space-y-2.5 border-t border-[#272d38]/10 pt-5 text-[14px] text-[#272d38]">
                                <li class="flex items-start gap-2">
                                    <Check class="mt-0.5 h-4 w-4 shrink-0 text-[#272d38]" aria-hidden="true" />
                                    <span>Start anytime with lifetime portal access</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <Check class="mt-0.5 h-4 w-4 shrink-0 text-[#272d38]" aria-hidden="true" />
                                    <span>Downloadable engineering workbooks &amp; video lessons</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <Check class="mt-0.5 h-4 w-4 shrink-0 text-[#272d38]" aria-hidden="true" />
                                    <span>No physical session seat booking required</span>
                                </li>
                            </ul>

                            <div class="mt-6">
                                <template v-if="module.hasActiveEntitlement">
                                    <p class="mb-3 rounded-lg border border-[#272d38]/15 bg-[#272d38]/[0.04] px-3.5 py-2.5 text-[14px] font-bold text-[#272d38]">
                                        Included in your Learning Portal
                                    </p>
                                    <Button as-child class="w-full">
                                        <Link :href="module.portalModuleUrl">
                                            Open in Learning Portal
                                        </Link>
                                    </Button>
                                </template>
                                <template v-else-if="isAuthenticated && module.canPurchaseAsCustomer && module.checkoutUrl">
                                    <Button as-child class="w-full">
                                        <Link :href="module.checkoutUrl">
                                            Purchase Module ({{ formatDisplayFee(module.independentPriceLabel) }})
                                        </Link>
                                    </Button>
                                </template>
                                <template v-else-if="isAuthenticated">
                                    <p class="rounded-lg border border-[#272d38]/12 bg-[#272d38]/[0.03] px-3.5 py-3 text-[14px] text-[#272d38]/70">
                                        Module purchases require a trainee account.
                                    </p>
                                </template>
                                <template v-else>
                                    <Button as-child class="w-full">
                                        <Link :href="routes.login">
                                            Sign in to Purchase ({{ formatDisplayFee(module.independentPriceLabel) }})
                                        </Link>
                                    </Button>
                                </template>
                            </div>

                            <p class="mt-4 text-[14px] leading-relaxed text-[#272d38]/65">
                                Self-paced module access is separate from physical session registration and attendance certificates.
                            </p>
                        </Card>

                        <Card class="p-6">
                            <div class="flex items-center gap-2 text-[14px] font-bold text-[#272d38]">
                                <Calendar class="h-4 w-4 text-[#272d38]" aria-hidden="true" />
                                <span>Physical Workshops</span>
                            </div>

                            <h2 class="mt-3 text-[19px] font-bold text-[#272d38]">
                                {{
                                    upcomingSessions.length > 0
                                        ? `${upcomingSessions.length} ${pluralize(upcomingSessions.length, 'scheduled cohort')}`
                                        : 'In-Person Delivery'
                                }}
                            </h2>

                            <p
                                v-if="!module.isAvailableIndependently"
                                class="mt-2 text-[14px] leading-relaxed text-[#272d38]/70"
                            >
                                This programme is delivered exclusively through instructor-led physical sessions.
                            </p>

                            <template v-if="nextOpenSession">
                                <div class="mt-4 rounded-xl border border-[#272d38]/12 bg-[#272d38]/[0.025] p-4">
                                    <p class="text-[13px] font-bold text-[#272d38]/70">
                                        Next Cohort
                                    </p>
                                    <p class="mt-1 text-[15px] font-bold text-[#272d38] tabular-nums">
                                        {{ nextOpenSession.dateRangeLabel }}
                                    </p>
                                    <p class="mt-0.5 text-[14px] text-[#272d38]/70">
                                        {{ nextOpenSession.venueName }}
                                        <template v-if="nextOpenSession.venueLocation">
                                            &middot; {{ nextOpenSession.venueLocation }}
                                        </template>
                                    </p>
                                    <div class="mt-3 flex items-center justify-between border-t border-[#272d38]/10 pt-3 text-[14px]">
                                        <span class="font-bold text-[#272d38] tabular-nums">
                                            {{ formatDisplayFee(nextOpenSession.priceLabel) }}
                                        </span>
                                        <span class="text-[#272d38]/65 tabular-nums">
                                            {{ nextOpenSession.remainingSeats }} / {{ nextOpenSession.capacity }} seats
                                        </span>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-col gap-2.5">
                                    <Button as-child variant="outline" class="w-full">
                                        <Link :href="nextOpenSession.detailUrl">
                                            <span>View session details</span>
                                            <ArrowRight class="h-4 w-4" aria-hidden="true" />
                                        </Link>
                                    </Button>
                                    <a
                                        href="#programme-sessions"
                                        class="text-center text-[14px] font-bold text-[#272d38] hover:underline underline-offset-4"
                                    >
                                        See all scheduled dates below
                                    </a>
                                </div>
                            </template>

                            <div v-else class="mt-4">
                                <Button as-child variant="outline" class="w-full">
                                    <Link :href="routes.sessions">
                                        <span>Browse all physical sessions</span>
                                        <ArrowRight class="h-4 w-4" aria-hidden="true" />
                                    </Link>
                                </Button>
                            </div>
                        </Card>
                    </aside>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
