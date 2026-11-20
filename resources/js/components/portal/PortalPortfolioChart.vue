<script setup lang="ts">
import { computed } from 'vue';
import { Doughnut } from 'vue-chartjs';
import {
    ArcElement,
    Chart as ChartJS,
    DoughnutController,
    Tooltip,
    type ChartData,
    type ChartOptions,
} from 'chart.js';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { PortfolioMixItem } from '@/types/etms';

ChartJS.register(DoughnutController, ArcElement, Tooltip);

const props = defineProps<{
    items: PortfolioMixItem[];
}>();

const palette = [
    '#7057D9',
    '#9B86E8',
    '#BEB0EE',
    '#DDD6F1',
];

const chartFontFamily = '"Plus Jakarta Sans", Inter, ui-sans-serif, system-ui, sans-serif';

const totalCount = computed(() =>
    props.items.reduce((sum, item) => sum + item.count, 0),
);

const chartData = computed<ChartData<'doughnut'>>(() => ({
    labels: props.items.map((item) => item.label),
    datasets: [
        {
            data: props.items.map((item) => item.count),
            backgroundColor: props.items.map((_, index) => palette[index % palette.length]),
            borderWidth: 2,
            borderColor: '#FFFFFF',
            hoverOffset: 2,
        },
    ],
}));

const chartOptions: ChartOptions<'doughnut'> = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '72%',
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: '#282338',
            titleColor: '#FFFFFF',
            bodyColor: '#FFFFFF',
            titleFont: {
                family: chartFontFamily,
                size: 12.5,
                weight: 700,
            },
            bodyFont: {
                family: chartFontFamily,
                size: 12.5,
            },
            padding: 10,
            cornerRadius: 8,
            callbacks: {
                label: (ctx) => ` ${ctx.label}: ${ctx.parsed}`,
            },
        },
    },
};
</script>

<template>
    <Card class="flex h-full flex-col">
        <CardHeader class="pb-2">
            <CardTitle>Learning Portfolio</CardTitle>
            <CardDescription>
                Your current learning credentials
            </CardDescription>
        </CardHeader>

        <CardContent class="flex flex-1 flex-col justify-between">
            <template v-if="totalCount > 0">
                <div class="relative mx-auto my-3 h-32 w-32">
                    <Doughnut :data="chartData" :options="chartOptions" />
                    <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center text-center">
                        <span class="text-[23px] leading-none font-extrabold text-[#282338] tabular-nums">
                            {{ totalCount }}
                        </span>
                        <span class="mt-1 text-[11px] font-medium text-[#625D6D]">
                            Total
                        </span>
                    </div>
                </div>

                <ul class="mt-3 divide-y divide-[#F0EDF4] border-t border-[#F0EDF4]">
                    <li
                        v-for="(item, index) in items"
                        :key="item.key"
                        class="flex items-center justify-between gap-3 py-2 text-[12.5px]"
                    >
                        <span class="flex min-w-0 items-center gap-2.5 text-[#403B4B]">
                            <span
                                class="h-2.5 w-2.5 shrink-0 rounded-full"
                                :style="{ backgroundColor: palette[index % palette.length] }"
                                aria-hidden="true"
                            />
                            <span class="truncate">{{ item.label }}</span>
                        </span>
                        <span class="font-bold text-[#282338] tabular-nums">
                            {{ item.count }}
                        </span>
                    </li>
                </ul>
            </template>

            <div
                v-else
                class="flex flex-1 flex-col items-center justify-center rounded-[10px] border border-dashed border-[#D7D1E3] bg-[#FBFAFD] px-5 py-9 text-center"
            >
                <p class="text-[14px] font-bold text-[#282338]">No portfolio records yet</p>
                <p class="mt-1 text-[12.5px] text-[#625D6D]">
                    Enrol in a workshop or start an independent module to track your learning credentials.
                </p>
            </div>
        </CardContent>
    </Card>
</template>
