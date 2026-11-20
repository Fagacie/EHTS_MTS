<script setup lang="ts">
import { computed, ref } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    BarController,
    BarElement,
    CategoryScale,
    Chart as ChartJS,
    LinearScale,
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
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import type { MonthlyActivityPoint } from '@/types/etms';

ChartJS.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip);

const props = defineProps<{
    series: MonthlyActivityPoint[];
}>();

type ChartMode = 'activity' | 'spend';

const activeMode = ref<ChartMode>('activity');

function setMode(value: string | number): void {
    activeMode.value = value === 'spend' ? 'spend' : 'activity';
}

const hasAnyData = computed(() =>
    props.series.some(
        (point) =>
            point.bookingsCount > 0 ||
            point.ordersCount > 0 ||
            point.paidAmountMyr > 0 ||
            point.trainingHours > 0,
    ),
);

const latestSummaryPoint = computed(() => {
    const activePoints = props.series.filter(
        (point) => point.bookingsCount > 0 || point.ordersCount > 0 || point.paidAmountMyr > 0,
    );

    return activePoints[activePoints.length - 1] ?? props.series[props.series.length - 1] ?? null;
});

const footerCaption = computed(() => {
    const point = latestSummaryPoint.value;
    if (!point) {
        return 'Six-month training activity overview';
    }

    if (activeMode.value === 'spend') {
        const formatted = point.paidAmountMyr.toLocaleString('en-MY', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 2,
        });

        return `${point.fullMonthLabel}: RM ${formatted} in paid orders`;
    }

    const bookingLabel = point.bookingsCount === 1 ? 'booking' : 'bookings';
    const orderLabel = point.ordersCount === 1 ? 'paid order' : 'paid orders';

    return `${point.fullMonthLabel}: ${point.bookingsCount} ${bookingLabel} and ${point.ordersCount} ${orderLabel}`;
});

const chartData = computed<ChartData<'bar'>>(() => {
    const labels = props.series.map((point) => point.monthLabel);

    if (activeMode.value === 'spend') {
        return {
            labels,
            datasets: [
                {
                    label: 'Paid Spend (RM)',
                    data: props.series.map((point) => point.paidAmountMyr),
                    backgroundColor: '#7057D9',
                    hoverBackgroundColor: '#5942B5',
                    borderRadius: 5,
                    borderSkipped: false,
                    barPercentage: 0.45,
                    categoryPercentage: 0.72,
                },
            ],
        };
    }

    return {
        labels,
        datasets: [
            {
                label: 'Bookings',
                data: props.series.map((point) => point.bookingsCount),
                backgroundColor: '#7057D9',
                hoverBackgroundColor: '#5942B5',
                borderRadius: 5,
                borderSkipped: false,
                barPercentage: 0.56,
                categoryPercentage: 0.68,
            },
            {
                label: 'Paid Orders',
                data: props.series.map((point) => point.ordersCount),
                backgroundColor: '#BEB0EE',
                hoverBackgroundColor: '#A592E8',
                borderRadius: 5,
                borderSkipped: false,
                barPercentage: 0.56,
                categoryPercentage: 0.68,
            },
        ],
    };
});

const chartFontFamily = '"Plus Jakarta Sans", Inter, ui-sans-serif, system-ui, sans-serif';

const chartOptions = computed<ChartOptions<'bar'>>(() => ({
    responsive: true,
    maintainAspectRatio: false,
    animation: {
        duration: 200,
    },
    transitions: {
        resize: {
            animation: {
                duration: 0,
            },
        },
    },
    interaction: {
        mode: 'index',
        intersect: false,
    },
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
            displayColors: true,
            callbacks: {
                title: (items) => {
                    const idx = items[0]?.dataIndex ?? 0;
                    return props.series[idx]?.fullMonthLabel ?? '';
                },
                label: (ctx) => {
                    const value = Number(ctx.parsed.y ?? 0);
                    if (activeMode.value === 'spend') {
                        return ` Paid Spend: RM ${value.toLocaleString('en-MY', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}`;
                    }

                    return ` ${ctx.dataset.label}: ${value}`;
                },
            },
        },
    },
    scales: {
        x: {
            grid: {
                display: false,
            },
            border: {
                display: false,
            },
            ticks: {
                color: '#625D6D',
                font: {
                    family: chartFontFamily,
                    size: 12,
                    weight: 500,
                },
            },
        },
        y: {
            beginAtZero: true,
            border: {
                display: false,
            },
            grid: {
                color: '#F0EDF4',
                lineWidth: 1,
            },
            ticks: {
                precision: 0,
                color: '#625D6D',
                font: {
                    family: chartFontFamily,
                    size: 12,
                },
                callback: (value) => {
                    if (activeMode.value === 'spend') {
                        return `RM ${value}`;
                    }

                    return `${value}`;
                },
            },
        },
    },
}));
</script>

<template>
    <Card class="flex h-full flex-col">
        <CardHeader class="flex flex-row flex-wrap items-start justify-between gap-3 space-y-0 pb-3">
            <div class="space-y-1">
                <CardTitle>Training &amp; Spend Activity</CardTitle>
                <CardDescription>
                    Performance over the last six months
                </CardDescription>
            </div>

            <Tabs
                :model-value="activeMode"
                @update:model-value="setMode"
            >
                <TabsList aria-label="Chart metric mode">
                    <TabsTrigger value="activity">Activity</TabsTrigger>
                    <TabsTrigger value="spend">Spend (RM)</TabsTrigger>
                </TabsList>
            </Tabs>
        </CardHeader>

        <CardContent class="flex flex-1 flex-col">
            <div v-if="hasAnyData" class="flex flex-1 flex-col">
                <div class="mb-3 flex flex-wrap items-center gap-5 text-[12.5px] font-medium text-[#403B4B]">
                    <span class="inline-flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-[#7057D9]" aria-hidden="true" />
                        <span>{{ activeMode === 'spend' ? 'Paid Spend (RM)' : 'Bookings' }}</span>
                    </span>
                    <span v-if="activeMode === 'activity'" class="inline-flex items-center gap-2">
                        <span class="h-2 w-2 rounded-full bg-[#BEB0EE]" aria-hidden="true" />
                        <span>Paid Orders</span>
                    </span>
                </div>

                <div class="relative h-48 w-full flex-1">
                    <Bar :data="chartData" :options="chartOptions" />
                </div>

                <p class="mt-3 text-[12px] text-[#625D6D] tabular-nums">
                    {{ footerCaption }}
                </p>
            </div>

            <div
                v-else
                class="flex flex-1 flex-col items-center justify-center rounded-[10px] border border-dashed border-[#D7D1E3] bg-[#FBFAFD] px-6 py-10 text-center"
            >
                <p class="text-[14px] font-bold text-[#282338]">
                    No recent booking or payment activity
                </p>
                <p class="mt-1 max-w-sm text-[12.5px] text-[#625D6D]">
                    Your monthly session bookings and completed orders over the last six months will appear here.
                </p>
            </div>
        </CardContent>
    </Card>
</template>
