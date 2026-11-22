<script setup lang="ts">
import { reactive, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { AlertCircle, Download, Film, Trash2, Upload } from 'lucide-vue-next';
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

const serverErrors = () => {
    const errors = (page.props as { errors?: Record<string, string> }).errors ?? {};
    return Object.values(errors).filter(Boolean);
};

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
const coverPreview = ref<string | null>(props.module?.cover_image_url ?? null);

function onCoverImage(event: Event): void {
    form.cover_image = (event.target as HTMLInputElement).files?.[0] ?? null;
    coverPreview.value = form.cover_image
        ? URL.createObjectURL(form.cover_image)
        : (props.module?.cover_image_url ?? null);
}

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
const materialFileKey = ref(0);

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
            materialFileKey.value++;
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
const videoFileKey = ref(0);

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
            videoFileKey.value++;
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
    <Head :title="mode === 'create' ? 'Create Standalone Module' : `Edit ${module?.title}`" />

    <AdminLayout>
        <Link href="/admin-vue/modules" class="mb-4 inline-block text-sm text-neutral-600 hover:text-neutral-900">
            ← Back to standalone modules
        </Link>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-semibold text-neutral-900">
                {{ mode === 'create' ? 'Create standalone learning module' : 'Edit standalone learning module' }}
            </h1>
            <button
                v-if="mode === 'edit' && module"
                type="button"
                class="rounded-md border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50"
                @click="deleteModule"
            >
                Delete module
            </button>
        </div>

        <form class="mt-6 max-w-5xl space-y-6" @submit.prevent="submitModule">
            <section
                v-if="serverErrors().length"
                role="alert"
                class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800"
            >
                <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                <div>
                    <p class="font-semibold">Please correct the following before saving:</p>
                    <ul class="mt-1 list-disc space-y-0.5 pl-5">
                        <li v-for="error in serverErrors()" :key="error">{{ error }}</li>
                    </ul>
                </div>
            </section>

            <section class="grid gap-4 rounded-lg border border-neutral-200 bg-white p-5 md:grid-cols-2">
                <label class="text-sm">
                    Module title <span class="text-red-600">*</span>
                    <input
                        v-model="form.title"
                        required
                        maxlength="200"
                        placeholder="e.g. Applied Urban Stormwater Management (MSMA 2nd Edition)"
                        class="mt-1 h-10 w-full rounded-md border border-neutral-300 px-3"
                    />
                </label>

                <label class="text-sm">
                    Module code
                    <input
                        v-model="form.code"
                        maxlength="50"
                        placeholder="Auto-generated if left blank (e.g. EHT-HYD-101)"
                        class="mt-1 h-10 w-full rounded-md border border-neutral-300 px-3 font-mono"
                    />
                </label>

                <label class="text-sm md:col-span-2">
                    Short summary
                    <input
                        v-model="form.summary"
                        maxlength="500"
                        placeholder="Concise summary displayed on the public standalone modules catalogue"
                        class="mt-1 h-10 w-full rounded-md border border-neutral-300 px-3"
                    />
                </label>

                <label class="text-sm md:col-span-2">
                    Full description &amp; syllabus <span class="text-red-600">*</span>
                    <textarea
                        v-model="form.description"
                        required
                        rows="5"
                        class="mt-1 w-full rounded-md border border-neutral-300 p-3"
                    />
                </label>

                <div class="flex items-center gap-4 md:col-span-2">
                    <img
                        v-if="coverPreview"
                        :src="coverPreview"
                        alt="Module cover"
                        class="h-24 w-40 rounded-md border object-cover"
                    />
                    <span
                        v-else
                        class="grid h-24 w-40 place-items-center rounded-md border bg-neutral-50 text-xs text-neutral-500"
                    >
                        No cover
                    </span>
                    <label class="text-sm">
                        Module cover image
                        <input type="file" accept="image/*" class="mt-1 block text-sm" @change="onCoverImage" />
                    </label>
                </div>
            </section>

            <section class="grid gap-4 rounded-lg border border-neutral-200 bg-white p-5 md:grid-cols-2">
                <label class="text-sm">
                    Independent purchase price ({{ app.currency }}) <span class="text-red-600">*</span>
                    <input
                        v-model="form.independent_price"
                        type="number"
                        min="0"
                        step="0.01"
                        required
                        class="mt-1 h-10 w-full rounded-md border border-neutral-300 px-3 font-mono"
                    />
                </label>

                <label class="text-sm">
                    Publication status
                    <select v-model="form.status" required class="mt-1 h-10 w-full rounded-md border border-neutral-300 px-3">
                        <option v-for="status in statuses" :key="status.value" :value="status.value">
                            {{ status.label }}
                        </option>
                    </select>
                </label>
            </section>

            <section class="rounded-lg border border-neutral-200 bg-white p-5">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-neutral-900">Learning objectives</h2>
                    <button
                        type="button"
                        class="rounded-md border border-neutral-300 px-3 py-1 text-xs font-medium hover:bg-neutral-50"
                        @click="addObjective"
                    >
                        Add objective
                    </button>
                </div>
                <div
                    v-for="(_, index) in form.learning_objectives"
                    :key="index"
                    class="mt-2.5 flex items-center gap-2"
                >
                    <input
                        v-model="form.learning_objectives[index]"
                        maxlength="500"
                        placeholder="Learning outcome or engineering skill"
                        class="h-10 flex-1 rounded-md border border-neutral-300 px-3 text-sm"
                    />
                    <button
                        type="button"
                        class="rounded-md border border-neutral-200 px-3 py-2 text-xs text-red-700 hover:bg-red-50"
                        @click="form.learning_objectives.splice(index, 1)"
                    >
                        Remove
                    </button>
                </div>
                <p v-if="form.learning_objectives.length === 0" class="mt-2 text-xs text-neutral-500">
                    Optional bullet points displayed on the module overview page.
                </p>
            </section>

            <div class="flex justify-end gap-2">
                <Link href="/admin-vue/modules" class="rounded-md border border-neutral-300 px-4 py-2 text-sm">
                    Cancel
                </Link>
                <button
                    type="submit"
                    :disabled="busy"
                    class="rounded-md bg-[#0F4C81] px-4 py-2 text-sm font-medium text-white hover:bg-[#0F4C81]/90 disabled:opacity-50"
                >
                    {{ busy ? 'Saving…' : mode === 'create' ? 'Create module' : 'Save module changes' }}
                </button>
            </div>
        </form>

        <section
            v-if="mode === 'edit' && module"
            class="mt-8 max-w-5xl rounded-lg border border-neutral-200 bg-white p-5"
        >
            <h2 class="text-base font-semibold text-neutral-900">
                Standalone Module Learning Materials ({{ module.materials.length }})
            </h2>
            <p class="mt-1 text-sm text-neutral-500">
                Upload workbooks, calculation sheets, and technical manuals included with this standalone module purchase.
            </p>

            <div class="mt-4 grid gap-3 rounded-lg border border-neutral-200 bg-neutral-50/60 p-4 md:grid-cols-2">
                <label class="text-sm">
                    Material title <span class="text-red-600">*</span>
                    <input
                        v-model="materialForm.title"
                        placeholder="e.g. MSMA 2nd Edition Design Calculation Workbook"
                        class="mt-1 h-10 w-full rounded-md border border-neutral-300 bg-white px-3 text-sm"
                    />
                </label>

                <label class="text-sm">
                    Material type
                    <select
                        v-model="materialForm.material_type"
                        class="mt-1 h-10 w-full rounded-md border border-neutral-300 bg-white px-3 text-sm"
                    >
                        <option v-for="type in materialTypes" :key="type.value" :value="type.value">
                            {{ type.label }}
                        </option>
                    </select>
                </label>

                <label class="text-sm">
                    Status
                    <select
                        v-model="materialForm.status"
                        class="mt-1 h-10 w-full rounded-md border border-neutral-300 bg-white px-3 text-sm"
                    >
                        <option v-for="st in materialStatuses" :key="st.value" :value="st.value">
                            {{ st.label }}
                        </option>
                    </select>
                </label>

                <label class="text-sm">
                    File <span class="text-red-600">*</span>
                    <input
                        :key="materialFileKey"
                        type="file"
                        accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.csv,.txt,.zip,.png,.jpg,.jpeg"
                        class="mt-1 block w-full rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm"
                        @change="onMaterialFile"
                    />
                </label>

                <label class="text-sm md:col-span-2">
                    Description
                    <textarea
                        v-model="materialForm.description"
                        rows="2"
                        placeholder="Optional description of what this file contains"
                        class="mt-1 w-full rounded-md border border-neutral-300 bg-white p-2.5 text-sm"
                    />
                </label>

                <div class="flex items-center justify-between md:col-span-2">
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="materialForm.is_downloadable" type="checkbox" />
                        Allow entitled customers to download
                    </label>
                    <button
                        type="button"
                        :disabled="materialBusy || !materialForm.file || !materialForm.title.trim()"
                        class="inline-flex h-10 items-center gap-2 rounded-md bg-[#0F4C81] px-4 text-sm font-medium text-white disabled:opacity-50"
                        @click="uploadMaterial"
                    >
                        <Upload :size="16" />
                        {{ materialBusy ? 'Uploading…' : 'Upload material' }}
                    </button>
                </div>
            </div>

            <div v-if="module.materials.length > 0" class="mt-5 divide-y divide-neutral-200 rounded-lg border border-neutral-200">
                <div
                    v-for="mat in module.materials"
                    :key="mat.id"
                    class="flex flex-col justify-between gap-3 p-4 sm:flex-row sm:items-center"
                >
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium text-neutral-900">{{ mat.title }}</p>
                            <span class="rounded border border-neutral-200 bg-neutral-50 px-2 py-0.5 text-xs text-neutral-600">
                                {{ mat.typeLabel }}
                            </span>
                            <span class="rounded border border-neutral-200 bg-neutral-50 px-2 py-0.5 text-xs font-medium text-neutral-700">
                                {{ mat.statusLabel }}
                            </span>
                        </div>
                        <p v-if="mat.description" class="mt-0.5 text-xs text-neutral-600">{{ mat.description }}</p>
                        <p class="mt-1 font-mono text-xs text-neutral-500">
                            {{ mat.original_filename }} · {{ mat.fileSizeLabel }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <a
                            :href="mat.downloadUrl"
                            class="inline-flex items-center gap-1 rounded-md border border-neutral-300 px-3 py-1.5 text-xs font-medium text-neutral-700 hover:bg-neutral-50"
                        >
                            <Download :size="14" />
                            Download
                        </a>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-md border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50"
                            @click="removeMaterial(mat.id)"
                        >
                            <Trash2 :size="14" />
                            Remove
                        </button>
                    </div>
                </div>
            </div>
            <p v-else class="mt-4 rounded-lg border border-neutral-200 p-6 text-center text-sm text-neutral-500">
                No learning materials have been uploaded to this standalone module yet.
            </p>
        </section>

        <section
            v-if="mode === 'edit' && module"
            class="mt-6 max-w-5xl rounded-lg border border-neutral-200 bg-white p-5"
        >
            <h2 class="flex items-center gap-2 text-base font-semibold text-neutral-900">
                <Film :size="16" class="text-[#0F4C81]" />
                Standalone Module Video Lessons ({{ (module.videos ?? []).length }})
            </h2>
            <p class="mt-1 text-sm text-neutral-500">
                Upload self-paced video lessons included with this standalone module. Entitled customers stream them inside their Learning Portal.
            </p>

            <div class="mt-4 grid gap-3 rounded-lg border border-neutral-200 bg-neutral-50/60 p-4 md:grid-cols-2">
                <label class="text-sm">
                    Video lesson title <span class="text-red-600">*</span>
                    <input
                        v-model="videoForm.title"
                        placeholder="e.g. Lesson 1: Runoff Hydrograph Basics"
                        class="mt-1 h-10 w-full rounded-md border border-neutral-300 bg-white px-3 text-sm"
                    />
                </label>

                <label class="text-sm">
                    Publication status
                    <select
                        v-model="videoForm.status"
                        class="mt-1 h-10 w-full rounded-md border border-neutral-300 bg-white px-3 text-sm"
                    >
                        <option v-for="st in materialStatuses" :key="st.value" :value="st.value">
                            {{ st.label }}
                        </option>
                    </select>
                </label>

                <label class="text-sm md:col-span-2">
                    Video file (MP4, MOV, MKV, WebM) <span class="text-red-600">*</span>
                    <input
                        :key="videoFileKey"
                        type="file"
                        accept="video/mp4,video/quicktime,video/x-matroska,video/webm"
                        class="mt-1 block w-full rounded-md border border-neutral-300 bg-white px-3 py-1.5 text-sm"
                        @change="onVideoFile"
                    />
                </label>

                <label class="text-sm md:col-span-2">
                    Description
                    <textarea
                        v-model="videoForm.description"
                        rows="2"
                        placeholder="Optional summary of topics covered in this lesson"
                        class="mt-1 w-full rounded-md border border-neutral-300 bg-white p-2.5 text-sm"
                    />
                </label>

                <div class="flex justify-end md:col-span-2">
                    <button
                        type="button"
                        :disabled="videoBusy || !videoForm.file || !videoForm.title.trim()"
                        class="inline-flex h-10 items-center gap-2 rounded-md bg-[#0F4C81] px-4 text-sm font-medium text-white disabled:opacity-50"
                        @click="uploadVideo"
                    >
                        <Upload :size="16" />
                        {{ videoBusy ? 'Uploading…' : 'Upload video lesson' }}
                    </button>
                </div>
            </div>

            <div
                v-if="(module.videos ?? []).length > 0"
                class="mt-5 divide-y divide-neutral-200 rounded-lg border border-neutral-200"
            >
                <div
                    v-for="vid in module.videos"
                    :key="vid.id"
                    class="flex flex-col justify-between gap-3 p-4 sm:flex-row sm:items-center"
                >
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium text-neutral-900">{{ vid.title }}</p>
                            <span class="rounded border border-neutral-200 bg-neutral-50 px-2 py-0.5 text-xs text-neutral-600">
                                {{ vid.encodingLabel }}
                            </span>
                            <span class="rounded border border-neutral-200 bg-neutral-50 px-2 py-0.5 text-xs font-medium text-neutral-700">
                                {{ vid.statusLabel }}
                            </span>
                        </div>
                        <p v-if="vid.description" class="mt-0.5 text-xs text-neutral-600">{{ vid.description }}</p>
                        <p class="mt-1 font-mono text-xs text-neutral-500">
                            {{ vid.original_filename ?? 'Video file' }} · {{ vid.durationLabel }}
                        </p>
                    </div>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 rounded-md border border-red-300 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50"
                        @click="removeVideo(vid.id)"
                    >
                        <Trash2 :size="14" />
                        Remove
                    </button>
                </div>
            </div>
            <p v-else class="mt-4 rounded-lg border border-neutral-200 p-6 text-center text-sm text-neutral-500">
                No video lessons have been uploaded to this standalone module yet.
            </p>
        </section>
    </AdminLayout>
</template>
