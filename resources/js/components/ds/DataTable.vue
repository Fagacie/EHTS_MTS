<script setup lang="ts" generic="T extends Record<string, unknown>">
import { useSlots } from 'vue';

export interface DataTableColumn {
    key: string;
    label: string;
    align?: 'left' | 'center' | 'right';
    tabular?: boolean;
    headerClass?: string;
    cellClass?: string;
}

const props = withDefaults(
    defineProps<{
        columns: DataTableColumn[];
        rows: T[];
        rowKey?: keyof T | ((row: T, index: number) => string | number);
        emptyTitle?: string;
        emptyDescription?: string;
    }>(),
    {
        emptyTitle: 'No records found',
        emptyDescription: 'Try adjusting your search or filter criteria.',
    },
);

const slots = useSlots();

function resolveKey(row: T, index: number): string | number {
    if (typeof props.rowKey === 'function') {
        return props.rowKey(row, index);
    }
    if (props.rowKey && row[props.rowKey] !== undefined && row[props.rowKey] !== null) {
        return String(row[props.rowKey]);
    }
    if ('id' in row && (typeof row.id === 'string' || typeof row.id === 'number')) {
        return row.id;
    }
    return index;
}

function alignClass(align?: 'left' | 'center' | 'right'): string {
    if (align === 'right') return 'text-right';
    if (align === 'center') return 'text-center';
    return 'text-left';
}
</script>

<template>
    <div class="overflow-hidden rounded-2xl bg-white shadow-card ring-1 ring-neutral-200/80">
        <div v-if="rows.length > 0" class="overflow-x-auto">
            <table class="min-w-full border-collapse">
                <thead>
                    <tr class="border-b border-neutral-200/80 bg-sand-50">
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            scope="col"
                            class="px-5 py-3.5 text-[11px] font-semibold tracking-[0.08em] text-neutral-500 uppercase whitespace-nowrap"
                            :class="[alignClass(column.align), column.headerClass]"
                        >
                            {{ column.label }}
                        </th>
                        <th
                            v-if="slots.actions"
                            scope="col"
                            class="px-5 py-3.5 text-right text-[11px] font-semibold tracking-[0.08em] text-neutral-500 uppercase whitespace-nowrap"
                        >
                            Action
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100">
                    <tr
                        v-for="(row, index) in rows"
                        :key="resolveKey(row, index)"
                        class="transition-colors duration-150 hover:bg-sand-50/75"
                    >
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            class="px-5 py-4 text-sm text-ink align-middle"
                            :class="[
                                alignClass(column.align),
                                column.tabular ? 'tabular' : '',
                                column.cellClass,
                            ]"
                        >
                            <slot
                                :name="`cell-${column.key}`"
                                :row="row"
                                :value="row[column.key]"
                                :index="index"
                            >
                                {{ row[column.key] ?? '—' }}
                            </slot>
                        </td>
                        <td
                            v-if="slots.actions"
                            class="px-5 py-4 text-right text-sm align-middle whitespace-nowrap"
                        >
                            <slot name="actions" :row="row" :index="index" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-else class="px-6 py-14 text-center">
            <slot name="empty">
                <p class="text-base font-semibold text-navy-900">{{ emptyTitle }}</p>
                <p class="mt-1.5 text-sm text-muted">{{ emptyDescription }}</p>
            </slot>
        </div>
    </div>
</template>
