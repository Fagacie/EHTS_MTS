<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CredibilityInstructorsSection from '@/components/public/CredibilityInstructorsSection.vue';
import FeaturedProgrammesSection from '@/components/public/FeaturedProgrammesSection.vue';
import HeroSection from '@/components/public/HeroSection.vue';
import TrainingPathwaysSection from '@/components/public/TrainingPathwaysSection.vue';
import UpcomingSessionsTableSection from '@/components/public/UpcomingSessionsTableSection.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { HomepageProps } from '@/types/etms';

defineProps<HomepageProps>();
</script>

<template>
    <Head title="Technical Training in Water, Environment & Engineering" />

    <PublicLayout>
        <HeroSection :assets="assets" />

        <TrainingPathwaysSection :stats="stats" />

        <UpcomingSessionsTableSection :sessions="upcomingSessions" />

        <FeaturedProgrammesSection :modules="featuredModules" />

        <CredibilityInstructorsSection :instructors="instructors" :assets="assets" />
    </PublicLayout>
</template>
