<script setup lang="ts">
import { computed, type HTMLAttributes } from 'vue';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

const props = defineProps<{
    status: string;
    label: string;
    class?: HTMLAttributes['class'];
}>();

const toneClass = computed<string>(() => {
    switch (props.status) {
        case 'confirmed':
        case 'paid':
        case 'successful':
        case 'issued':
        case 'active':
        case 'completed':
        case 'published':
        case 'verified':
        case 'present':
        case 'ready':
            return 'border-transparent bg-[#E8F4ED] text-[#28734F]';
        case 'pending_payment':
        case 'pending':
        case 'processing':
        case 'submitted':
        case 'requested':
        case 'approved':
        case 'warning':
        case 'correction_requested':
            return 'border-transparent bg-[#FBF2E3] text-[#8B5A17]';
        case 'refunded':
        case 'partially_refunded':
        case 'expired':
        case 'failed':
        case 'cancelled':
        case 'revoked':
        case 'absent':
        case 'rejected':
            return 'border-transparent bg-[#FBECEE] text-[#A33C48]';
        case 'unread':
        case 'primary':
        case 'info':
        case 'excused':
        case 'draft':
        default:
            return 'border-transparent bg-[#F1EDFB] text-[#5942B5]';
    }
});
</script>

<template>
    <Badge
        variant="secondary"
        :class="cn('px-2.5 py-0.5 text-[11.5px] font-bold', toneClass, props.class)"
    >
        {{ label }}
    </Badge>
</template>
