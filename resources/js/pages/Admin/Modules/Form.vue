<script setup lang="ts">
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    Download,
    FileText,
    Film,
    ImagePlus,
    Plus,
    Trash2,
    Upload,
    X,
} from 'lucide-vue-next';
import StatusBadge from '@/components/ds/StatusBadge.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { EtmsSharedProps } from '@/types/etms';

interface Option {
    value: string;
    label: string;
}

interface ModuleMaterialRow {
    id: number;
    title: string;
    description: string | null;
    material_type: string;
    typeLabel: string;
    status: string;
    statusLabel: string;
    original_filename: string;
    fileSizeLabel: string;
    is_downloadable: boolean;
    sort_order: number;
    downloadUrl: string;
}

interface ModuleVideoRow {
    id: number;
    title: string;
    description: string | null;
    original_filename: string | null;
    durationLabel: string;
    encodingStatus: string;
    encodingLabel: string;
    status: string;
    statusLabel: string;
    sort_order: number;
}

interface Props extends EtmsSharedProps {
    mode: 'create' | 'edit';
    module?: {
        id: number;
        code: string;
        title: string;
        slug: string;
        summary: string | null;
        description: string;
        learning_objectives: string[];
        is_available_independently: boolean;
        independent_price: string | number | null;
        status: string;
        cover_image_url: string | null;
        materials: ModuleMaterialRow[];
        videos?: ModuleVideoRow[];
    };
    statuses: Option[];
    materialTypes: Option[];
    materialStatuses: Option[];
}

const props = defineProps<Props>();
const page = usePage();

const serverErrors = computed(() => {
    const errors = (page.props as { errors?: Record<string, string> }).errors ?? {};
    return Object.values(errors).filter(Boolean);
});

const form = reactive({
    code: props.module?.code ?? '',
    title: props.module?.title ?? '',
    summary: props.module?.summary ?? '',
    description: props.module?.description ?? '',
    learning_objectives: [...(props.module?.learning_objectives ?? [])] as string[],
    independent_price: props.module?.independent_price ?? '',
    is_available_independently: props.module?.is_available_independently ?? true,
    status: props.module?.status ?? 'draft',
    cover_image: null as File | null,
});

const busy = ref(false);
const coverInputRef = ref<HTMLInputElement | null>(null);
const objectCoverUrl = ref<string | null>(null);
const coverPreview = computed(() => objectCoverUrl.value ?? props.module?.cover_image_url ?? null);

function onCoverImage(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    if (objectCoverUrl.value) {
        URL.revokeObjectURL(objectCoverUrl.value);
        objectCoverUrl.value = null;
    }
    form.cover_image = file;
    if (file) {
        objectCoverUrl.value = URL.createObjectURL(file);
    }
}

function clearCoverSelection(): void {
    if (objectCoverUrl.value) {
        URL.revokeObjectURL(objectCoverUrl.value);
        objectCoverUrl.value = null;
    }
    form.cover_image = null;
    if (coverInputRef.value) {
        coverInputRef.value.value = '';
    }
}

onBeforeUnmount(() => {
    if (objectCoverUrl.value) {
        URL.revokeObjectURL(objectCoverUrl.value);
    }
});

function addObjective(): void {
    form.learning_objectives.push('');
}

function submitModule(): void {
    busy.value = true;
    if (props.mode === 'create') {
        router.post('/admin-vue/modules', form as any, {
            forceFormData: true,
            onFinish: () => {
                busy.value = false;
            },
        });
    } else if (props.module) {
        router.post(
            `/admin-vue/modules/${props.module.id}`,
            { ...form, _method: 'put' } as any,
            {
                forceFormData: true,
                onFinish: () => {
                    busy.value = false;
                },
            },
        );
    }
}

function deleteModule(): void {
    if (!props.module || !window.confirm('Delete this standalone learning module?')) {
        return;
    }
    router.delete(`/admin-vue/modules/${props.module.id}`);
}

const materialForm = reactive({
    title: '',
    description: '',
    material_type: 'pdf',
    status: 'published',
    is_downloadable: true,
    file: null as File | null,
});
const materialBusy = ref(false);
const materialFileRef = ref<HTMLInputElement | null>(null);

function onMaterialFile(event: Event): void {
    materialForm.file = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function uploadMaterial(): void {
    if (!props.module || !materialForm.file || !materialForm.title.trim()) {
        return;
    }
    materialBusy.value = true;
    router.post(`/admin-vue/modules/${props.module.id}/materials`, materialForm as any, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            materialBusy.value = false;
        },
        onSuccess: () => {
            materialForm.title = '';
            materialForm.description = '';
            materialForm.file = null;
            if (materialFileRef.value) {
                materialFileRef.value.value = '';
            }
        },
    });
}

function removeMaterial(materialId: number): void {
    if (!props.module || !window.confirm('Remove this learning material from the module?')) {
        return;
    }
    router.delete(`/admin-vue/modules/${props.module.id}/materials/${materialId}`, {
        preserveScroll: true,
    });
}

const videoForm = reactive({
    title: '',
    description: '',
    status: 'published',
    file: null as File | null,
});
const videoBusy = ref(false);
const videoFileRef = ref<HTMLInputElement | null>(null);

function onVideoFile(event: Event): void {
    videoForm.file = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function uploadVideo(): void {
    if (!props.module || !videoForm.file || !videoForm.title.trim()) {
        return;
    }
    videoBusy.value = true;
    router.post(`/admin-vue/modules/${props.module.id}/videos`, videoForm as any, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            videoBusy.value = false;
        },
        onSuccess: () => {
            videoForm.title = '';
            videoForm.description = '';
            videoForm.file = null;
            if (videoFileRef.value) {
                videoFileRef.value.value = '';
            }
        },
    });
}

function removeVideo(videoId: number): void {
    if (!props.module || !window.confirm('Remove this video lesson from the standalone module?')) {
        return;
    }
    router.delete(`/admin-vue/modules/${props.module.id}/videos/${videoId}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="mode === 'create' ? 'Create Module' : `Edit ${module?.title}`" />

    <AdminLayout :breadcrumb="mode === 'create' ? 'Create Module' : 'Edit Module'">
        <div class="mx-auto max-w-[1200px] space-y-6">
            <!-- Top Back Link & Header -->
            <div class="space-y-3">
                <Link
                    href="/admin-vue/modules"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-muted transition-colors hover:text-navy-950"
                >
                    <ArrowLeft class="size-3.5" aria-hidden="true" />
                    <span>Modules</span>
                </Link>

                <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 class="type-page-title">
                            {{ mode === 'create' ? 'Create learning module' : 'Edit learning module' }}
                        </h1>
                        <p class="mt-1 text-sm text-muted">
                            {{
                                mode === 'create'
                                    ? 'Configure module syllabus, cover image, learning outcomes, and standalone price.'
                                    : 'Update module details and manage downloadable materials and video lessons.'
                            }}
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            v-if="mode === 'edit' && module"
                            type="button"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-white px-3.5 text-xs font-semibold text-danger-700 ring-1 ring-danger-600/25 transition-colors hover:bg-danger-50"
                            @click="deleteModule"
                        >
                            <Trash2 class="size-3.5" aria-hidden="true" />
                            <span>Delete</span>
                        </button>

                        <Link
                            href="/admin-vue/modules"
                            class="inline-flex h-9 items-center rounded-lg bg-white px-4 text-xs font-semibold text-muted ring-1 ring-line transition-colors hover:text-navy-950"
                        >
                            Cancel
                        </Link>

                        <button
                            type="button"
                            class="inline-flex h-9 items-center rounded-lg bg-navy-950 px-5 text-xs font-semibold text-white transition-colors hover:bg-navy-900 disabled:opacity-50"
                            :disabled="busy"
                            @click="submitModule"
                        >
                            {{ busy ? 'Saving...' : mode === 'create' ? 'Create module' : 'Save changes' }}
                        </button>
                    </div>
                </header>
            </div>

            <!-- Validation Errors -->
            <section
                v-if="serverErrors.length > 0"
                role="alert"
                class="flex items-start gap-3 rounded-xl bg-danger-50 p-4 text-sm text-danger-700 ring-1 ring-danger-600/20"
            >
                <AlertCircle class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
                <div>
                    <p class="font-semibold">Please correct the following before saving:</p>
                    <ul class="mt-1 list-disc space-y-0.5 pl-5 text-xs">
                        <li v-for="error in serverErrors" :key="error">{{ error }}</li>
                    </ul>
                </div>
            </section>

            <!-- Main 8/4 Layout -->
            <form class="grid gap-6 lg:grid-cols-12 lg:items-start" @submit.prevent="submitModule">
                <!-- Left Main Column (8 cols) -->
                <div class="space-y-6 lg:col-span-8">
                    <!-- Unified Module Details Card -->
                    <div class="divide-y divide-line rounded-2xl bg-white shadow-card ring-1 ring-neutral-200/80">
                        <!-- Basics & Cover -->
                        <section class="space-y-5 p-6">
                            <h2 class="text-sm font-semibold text-navy-950">Module details</h2>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="mod-title" class="block text-xs font-medium text-ink">
                                        Module title <span class="text-danger-600">*</span>
                                    </label>
                                    <input
                                        id="mod-title"
                                        v-model="form.title"
                                        type="text"
                                        required
                                        maxlength="200"
                                        placeholder="e.g. Applied Urban Stormwater Management"
                                        class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink placeholder:text-muted/60 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                    />
                                </div>

                                <div>
                                    <label for="mod-code" class="block text-xs font-medium text-ink">
                                        Module code
                                    </label>
                                    <input
                                        id="mod-code"
                                        v-model="form.code"
                                        type="text"
                                        maxlength="50"
                                        placeholder="Auto-generated if blank (e.g. EHT-HYD-101)"
                                        class="tabular-nums-mono mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink placeholder:text-muted/60 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                    />
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="mod-summary" class="block text-xs font-medium text-ink">
                                        Short summary
                                    </label>
                                    <input
                                        id="mod-summary"
                                        v-model="form.summary"
                                        type="text"
                                        maxlength="500"
                                        placeholder="Concise summary displayed on catalogue cards"
                                        class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink placeholder:text-muted/60 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                    />
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="mod-description" class="block text-xs font-medium text-ink">
                                        Full description &amp; syllabus <span class="text-danger-600">*</span>
                                    </label>
                                    <textarea
                                        id="mod-description"
                                        v-model="form.description"
                                        required
                                        rows="5"
                                        class="mt-1.5 w-full rounded-lg border border-line-strong/80 bg-white p-3.5 text-sm text-ink transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                    />
                                </div>
                            </div>

                            <!-- Cover Image Upload -->
                            <div>
                                <span class="block text-xs font-medium text-ink">Cover image</span>
                                <input
                                    ref="coverInputRef"
                                    type="file"
                                    accept="image/*"
                                    class="sr-only"
                                    @change="onCoverImage"
                                />

                                <div class="mt-2 flex flex-col gap-4 sm:flex-row sm:items-center">
                                    <button
                                        type="button"
                                        class="relative h-28 w-48 shrink-0 overflow-hidden rounded-xl bg-sand-50 ring-1 ring-line transition-all hover:ring-primary-600/40"
                                        @click="coverInputRef?.click()"
                                    >
                                        <img
                                            v-if="coverPreview"
                                            :src="coverPreview"
                                            alt="Module cover preview"
                                            class="size-full object-cover"
                                        />
                                        <span
                                            v-else
                                            class="flex size-full flex-col items-center justify-center gap-1.5 text-muted"
                                        >
                                            <ImagePlus class="size-5" aria-hidden="true" />
                                            <span class="text-xs font-medium">Upload cover</span>
                                        </span>
                                    </button>

                                    <div class="space-y-1.5 text-xs text-muted">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button
                                                type="button"
                                                class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-white px-3 text-xs font-semibold text-navy-950 ring-1 ring-line transition-colors hover:bg-sand-50"
                                                @click="coverInputRef?.click()"
                                            >
                                                <Upload class="size-3.5" aria-hidden="true" />
                                                <span>{{ coverPreview ? 'Replace image' : 'Choose image' }}</span>
                                            </button>

                                            <button
                                                v-if="form.cover_image"
                                                type="button"
                                                class="inline-flex h-8 items-center gap-1 rounded-lg px-2.5 text-xs font-medium text-muted hover:text-danger-600"
                                                @click="clearCoverSelection"
                                            >
                                                <X class="size-3.5" aria-hidden="true" />
                                                <span>Cancel</span>
                                            </button>
                                        </div>
                                        <p>Recommended 16:9 landscape JPG, PNG, or WebP.</p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Learning Objectives -->
                        <section class="space-y-4 p-6">
                            <div class="flex items-center justify-between gap-3">
                                <h2 class="text-sm font-semibold text-navy-950">Learning objectives</h2>
                                <button
                                    type="button"
                                    class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-sand-50 px-3 text-xs font-semibold text-navy-950 ring-1 ring-line transition-colors hover:bg-sand-100"
                                    @click="addObjective"
                                >
                                    <Plus class="size-3.5" aria-hidden="true" />
                                    <span>Add objective</span>
                                </button>
                            </div>

                            <p v-if="form.learning_objectives.length === 0" class="text-xs text-muted">
                                Optional key outcomes displayed on the module overview page.
                            </p>

                            <div v-else class="space-y-2.5">
                                <div
                                    v-for="(_, index) in form.learning_objectives"
                                    :key="index"
                                    class="flex items-center gap-2"
                                >
                                    <input
                                        v-model="form.learning_objectives[index]"
                                        type="text"
                                        maxlength="500"
                                        placeholder="Learning outcome or engineering skill"
                                        class="h-10 flex-1 rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink placeholder:text-muted/60 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                    />
                                    <button
                                        type="button"
                                        class="inline-flex size-10 shrink-0 items-center justify-center rounded-lg text-muted ring-1 ring-line transition-colors hover:bg-danger-50 hover:text-danger-700 hover:ring-danger-600/20"
                                        aria-label="Remove objective"
                                        @click="form.learning_objectives.splice(index, 1)"
                                    >
                                        <Trash2 class="size-4" aria-hidden="true" />
                                    </button>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- Edit Mode Only: Learning Materials -->
                    <section
                        v-if="mode === 'edit' && module"
                        class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-sm font-semibold text-navy-950">
                                Learning materials ({{ module.materials.length }})
                            </h2>
                        </div>

                        <div class="mt-4 grid gap-3 rounded-xl bg-sand-50/80 p-4 ring-1 ring-line/70 sm:grid-cols-2">
                            <input
                                v-model="materialForm.title"
                                type="text"
                                placeholder="Material title *"
                                class="h-9 rounded-lg border border-line-strong/80 bg-white px-3 text-xs text-ink focus:border-primary-600 focus:outline-none"
                            />
                            <select
                                v-model="materialForm.material_type"
                                class="h-9 rounded-lg border border-line-strong/80 bg-white px-3 text-xs text-ink focus:border-primary-600 focus:outline-none"
                            >
                                <option v-for="type in materialTypes" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                            <select
                                v-model="materialForm.status"
                                class="h-9 rounded-lg border border-line-strong/80 bg-white px-3 text-xs text-ink focus:border-primary-600 focus:outline-none"
                            >
                                <option v-for="st in materialStatuses" :key="st.value" :value="st.value">
                                    {{ st.label }}
                                </option>
                            </select>
                            <div class="flex items-center">
                                <input
                                    ref="materialFileRef"
                                    type="file"
                                    accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.csv,.txt,.zip,.png,.jpg,.jpeg"
                                    class="sr-only"
                                    @change="onMaterialFile"
                                />
                                <button
                                    type="button"
                                    class="inline-flex h-9 w-full items-center justify-center gap-1.5 truncate rounded-lg bg-white px-3 text-xs font-semibold text-navy-950 ring-1 ring-line hover:bg-sand-50"
                                    @click="materialFileRef?.click()"
                                >
                                    <Upload class="size-3.5 shrink-0" aria-hidden="true" />
                                    <span class="truncate">{{ materialForm.file?.name || 'Choose file *' }}</span>
                                </button>
                            </div>
                            <textarea
                                v-model="materialForm.description"
                                rows="2"
                                placeholder="Optional short description..."
                                class="rounded-lg border border-line-strong/80 bg-white p-2.5 text-xs text-ink focus:border-primary-600 focus:outline-none sm:col-span-2"
                            />
                            <div class="flex flex-wrap items-center justify-between gap-3 sm:col-span-2">
                                <label class="flex items-center gap-2 text-xs text-ink">
                                    <input
                                        v-model="materialForm.is_downloadable"
                                        type="checkbox"
                                        class="size-4 rounded border-line-strong accent-navy-950"
                                    />
                                    <span>Allow entitled customers to download</span>
                                </label>
                                <button
                                    type="button"
                                    :disabled="materialBusy || !materialForm.file || !materialForm.title.trim()"
                                    class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-navy-950 px-3.5 text-xs font-semibold text-white transition-colors hover:bg-navy-900 disabled:opacity-45"
                                    @click="uploadMaterial"
                                >
                                    <Upload class="size-3.5" aria-hidden="true" />
                                    <span>{{ materialBusy ? 'Uploading...' : 'Upload material' }}</span>
                                </button>
                            </div>
                        </div>

                        <ul v-if="module.materials.length > 0" class="mt-4 divide-y divide-line/70">
                            <li
                                v-for="mat in module.materials"
                                :key="mat.id"
                                class="flex flex-col justify-between gap-3 py-3.5 sm:flex-row sm:items-center"
                            >
                                <div class="flex min-w-0 items-start gap-2.5">
                                    <FileText class="mt-0.5 size-4 shrink-0 text-primary-600" aria-hidden="true" />
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="truncate text-sm font-medium text-navy-950">{{ mat.title }}</p>
                                            <StatusBadge :tone="mat.status === 'published' ? 'success' : 'neutral'">
                                                {{ mat.statusLabel }}
                                            </StatusBadge>
                                        </div>
                                        <p v-if="mat.description" class="mt-0.5 text-xs text-muted">{{ mat.description }}</p>
                                        <p class="tabular mt-0.5 text-xs text-muted/85">
                                            {{ mat.original_filename }} &middot; {{ mat.fileSizeLabel }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <a
                                        :href="mat.downloadUrl"
                                        class="inline-flex h-8 items-center gap-1 rounded-lg bg-sand-50 px-3 text-xs font-semibold text-navy-950 ring-1 ring-line transition-colors hover:bg-sand-100"
                                    >
                                        <Download class="size-3.5" aria-hidden="true" />
                                        <span>Download</span>
                                    </a>
                                    <button
                                        type="button"
                                        class="inline-flex size-8 items-center justify-center rounded-lg text-muted transition-colors hover:bg-danger-50 hover:text-danger-700"
                                        aria-label="Remove material"
                                        @click="removeMaterial(mat.id)"
                                    >
                                        <Trash2 class="size-3.5" aria-hidden="true" />
                                    </button>
                                </div>
                            </li>
                        </ul>
                        <p v-else class="mt-4 text-center text-xs text-muted">
                            No learning materials uploaded yet.
                        </p>
                    </section>

                    <!-- Edit Mode Only: Video Lessons -->
                    <section
                        v-if="mode === 'edit' && module"
                        class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80"
                    >
                        <div class="flex items-center gap-2">
                            <Film class="size-4 text-accent-600" aria-hidden="true" />
                            <h2 class="text-sm font-semibold text-navy-950">
                                Video lessons ({{ (module.videos ?? []).length }})
                            </h2>
                        </div>

                        <div class="mt-4 grid gap-3 rounded-xl bg-sand-50/80 p-4 ring-1 ring-line/70 sm:grid-cols-2">
                            <input
                                v-model="videoForm.title"
                                type="text"
                                placeholder="Video lesson title *"
                                class="h-9 rounded-lg border border-line-strong/80 bg-white px-3 text-xs text-ink focus:border-primary-600 focus:outline-none"
                            />
                            <select
                                v-model="videoForm.status"
                                class="h-9 rounded-lg border border-line-strong/80 bg-white px-3 text-xs text-ink focus:border-primary-600 focus:outline-none"
                            >
                                <option v-for="st in materialStatuses" :key="st.value" :value="st.value">
                                    {{ st.label }}
                                </option>
                            </select>
                            <div class="sm:col-span-2">
                                <input
                                    ref="videoFileRef"
                                    type="file"
                                    accept="video/mp4,video/quicktime,video/x-matroska,video/webm"
                                    class="sr-only"
                                    @change="onVideoFile"
                                />
                                <button
                                    type="button"
                                    class="inline-flex h-9 w-full items-center justify-center gap-1.5 truncate rounded-lg bg-white px-3 text-xs font-semibold text-navy-950 ring-1 ring-line hover:bg-sand-50"
                                    @click="videoFileRef?.click()"
                                >
                                    <Upload class="size-3.5 shrink-0" aria-hidden="true" />
                                    <span class="truncate">{{ videoForm.file?.name || 'Choose video file (MP4, MOV, MKV, WebM) *' }}</span>
                                </button>
                            </div>
                            <textarea
                                v-model="videoForm.description"
                                rows="2"
                                placeholder="Optional lesson summary..."
                                class="rounded-lg border border-line-strong/80 bg-white p-2.5 text-xs text-ink focus:border-primary-600 focus:outline-none sm:col-span-2"
                            />
                            <div class="flex justify-end sm:col-span-2">
                                <button
                                    type="button"
                                    :disabled="videoBusy || !videoForm.file || !videoForm.title.trim()"
                                    class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-navy-950 px-3.5 text-xs font-semibold text-white transition-colors hover:bg-navy-900 disabled:opacity-45"
                                    @click="uploadVideo"
                                >
                                    <Upload class="size-3.5" aria-hidden="true" />
                                    <span>{{ videoBusy ? 'Uploading...' : 'Upload video' }}</span>
                                </button>
                            </div>
                        </div>

                        <ul v-if="(module.videos ?? []).length > 0" class="mt-4 divide-y divide-line/70">
                            <li
                                v-for="vid in module.videos"
                                :key="vid.id"
                                class="flex flex-col justify-between gap-3 py-3.5 sm:flex-row sm:items-center"
                            >
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="truncate text-sm font-medium text-navy-950">{{ vid.title }}</p>
                                        <StatusBadge :tone="vid.encodingStatus === 'ready' ? 'success' : 'warning'">
                                            {{ vid.encodingLabel }}
                                        </StatusBadge>
                                        <StatusBadge :tone="vid.status === 'published' ? 'primary' : 'neutral'">
                                            {{ vid.statusLabel }}
                                        </StatusBadge>
                                    </div>
                                    <p v-if="vid.description" class="mt-0.5 text-xs text-muted">{{ vid.description }}</p>
                                    <p class="tabular mt-0.5 text-xs text-muted/85">
                                        {{ vid.original_filename ?? 'Video file' }} &middot; {{ vid.durationLabel }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="inline-flex size-8 shrink-0 items-center justify-center rounded-lg text-muted transition-colors hover:bg-danger-50 hover:text-danger-700"
                                    aria-label="Remove video"
                                    @click="removeVideo(vid.id)"
                                >
                                    <Trash2 class="size-3.5" aria-hidden="true" />
                                </button>
                            </li>
                        </ul>
                        <p v-else class="mt-4 text-center text-xs text-muted">
                            No video lessons uploaded yet.
                        </p>
                    </section>
                </div>

                <!-- Right Sticky Column (4 cols): Pricing & Publishing -->
                <aside class="space-y-5 lg:sticky lg:top-24 lg:col-span-4">
                    <div class="space-y-4 rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80">
                        <h2 class="text-sm font-semibold text-navy-950">Pricing &amp; publishing</h2>

                        <div>
                            <label for="mod-status" class="block text-xs font-medium text-ink">
                                Publication status <span class="text-danger-600">*</span>
                            </label>
                            <select
                                id="mod-status"
                                v-model="form.status"
                                required
                                class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3 text-sm text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            >
                                <option v-for="status in statuses" :key="status.value" :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label for="mod-price" class="block text-xs font-medium text-ink">
                                Standalone price ({{ app.currency }}) <span class="text-danger-600">*</span>
                            </label>
                            <div class="relative mt-1.5">
                                <span class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-xs font-semibold text-muted">
                                    {{ app.currency }}
                                </span>
                                <input
                                    id="mod-price"
                                    v-model="form.independent_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    required
                                    placeholder="0.00"
                                    class="tabular h-10 w-full rounded-lg border border-line-strong/80 bg-white pr-3.5 pl-13 text-sm font-semibold text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                />
                            </div>
                        </div>

                        <div class="space-y-2 border-t border-line pt-4">
                            <button
                                type="submit"
                                :disabled="busy"
                                class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-navy-950 px-4 text-xs font-semibold text-white transition-colors hover:bg-navy-900 disabled:opacity-50"
                            >
                                {{ busy ? 'Saving...' : mode === 'create' ? 'Create module' : 'Save changes' }}
                            </button>

                            <Link
                                href="/admin-vue/modules"
                                class="inline-flex h-9 w-full items-center justify-center rounded-lg text-xs font-semibold text-muted transition-colors hover:bg-sand-100 hover:text-navy-950"
                            >
                                Cancel
                            </Link>
                        </div>
                    </div>
                </aside>
            </form>
        </div>
    </AdminLayout>
</template>
