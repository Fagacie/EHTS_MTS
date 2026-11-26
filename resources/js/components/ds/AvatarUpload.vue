<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';
import { Camera, UserRound, X } from 'lucide-vue-next';

const props = withDefaults(
    defineProps<{
        modelValue?: File | null;
        initialUrl?: string | null;
        label?: string;
        hint?: string;
        error?: string | null;
        id?: string;
    }>(),
    {
        modelValue: null,
        initialUrl: null,
        label: 'Profile photo',
        hint: 'Optional · JPG, PNG or WebP up to 5 MB',
        error: null,
        id: 'avatar-upload',
    },
);

const emit = defineEmits<{
    (e: 'update:modelValue', file: File | null): void;
}>();

const inputRef = ref<HTMLInputElement | null>(null);
const previewUrl = ref<string | null>(props.initialUrl);
let objectUrl: string | null = null;

watch(
    () => props.initialUrl,
    (next) => {
        if (!props.modelValue) {
            previewUrl.value = next;
        }
    },
);

function revokeObjectUrl(): void {
    if (objectUrl) {
        URL.revokeObjectURL(objectUrl);
        objectUrl = null;
    }
}

function openPicker(): void {
    inputRef.value?.click();
}

function handleFileChange(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    revokeObjectUrl();

    if (file) {
        objectUrl = URL.createObjectURL(file);
        previewUrl.value = objectUrl;
        emit('update:modelValue', file);
    } else {
        previewUrl.value = props.initialUrl;
        emit('update:modelValue', null);
    }
}

function clearSelection(): void {
    revokeObjectUrl();
    if (inputRef.value) {
        inputRef.value.value = '';
    }
    previewUrl.value = props.initialUrl;
    emit('update:modelValue', null);
}

onBeforeUnmount(() => {
    revokeObjectUrl();
});
</script>

<template>
    <div class="flex flex-col items-center text-center sm:flex-row sm:items-center sm:gap-4 sm:text-left">
        <div class="relative shrink-0">
            <button
                type="button"
                class="group relative flex size-20 cursor-pointer items-center justify-center overflow-hidden rounded-full bg-sand-100 text-navy-700 ring-2 ring-white shadow-card transition-all duration-150 hover:ring-primary-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                :aria-label="previewUrl ? 'Change profile photo' : 'Upload profile photo'"
                @click="openPicker"
            >
                <img
                    v-if="previewUrl"
                    :src="previewUrl"
                    alt="Profile avatar preview"
                    class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
                />
                <UserRound
                    v-else
                    class="size-9 text-navy-700/70 transition-colors group-hover:text-navy-950"
                    aria-hidden="true"
                />

                <span
                    class="absolute inset-0 flex items-center justify-center bg-navy-950/40 text-white opacity-0 transition-opacity duration-150 group-hover:opacity-100"
                    aria-hidden="true"
                >
                    <Camera class="size-5" />
                </span>
            </button>

            <button
                v-if="modelValue"
                type="button"
                aria-label="Remove selected photo"
                class="absolute -top-0.5 -right-0.5 flex size-6 cursor-pointer items-center justify-center rounded-full bg-navy-950 text-white shadow-xs transition-colors hover:bg-danger-600"
                @click="clearSelection"
            >
                <X class="size-3.5" aria-hidden="true" />
            </button>
            <span
                v-else
                class="pointer-events-none absolute right-0 bottom-0 flex size-6.5 items-center justify-center rounded-full bg-navy-950 text-white ring-2 ring-white"
                aria-hidden="true"
            >
                <Camera class="size-3.5" />
            </span>
        </div>

        <div class="mt-3 sm:mt-0">
            <p class="text-sm font-semibold text-navy-950">
                {{ label }}
            </p>
            <p class="mt-0.5 text-xs text-muted">
                {{ hint }}
            </p>
            <div class="mt-2 flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                <button
                    type="button"
                    class="cursor-pointer rounded-full border border-line-strong bg-white px-3 py-1 text-xs font-semibold text-navy-950 transition-colors hover:bg-sand-50"
                    @click="openPicker"
                >
                    {{ previewUrl ? 'Change photo' : 'Upload photo' }}
                </button>
                <button
                    v-if="modelValue"
                    type="button"
                    class="cursor-pointer text-xs font-medium text-muted transition-colors hover:text-danger-700"
                    @click="clearSelection"
                >
                    Remove
                </button>
            </div>
            <p v-if="error" class="mt-1.5 text-xs font-semibold text-danger-600">
                {{ error }}
            </p>
        </div>

        <input
            :id="id"
            ref="inputRef"
            type="file"
            name="photo"
            accept="image/png,image/jpeg,image/webp"
            class="sr-only"
            @change="handleFileChange"
        />
    </div>
</template>
