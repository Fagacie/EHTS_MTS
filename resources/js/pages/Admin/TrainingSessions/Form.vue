<script setup lang="ts">
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    FileText,
    ImagePlus,
    Pencil,
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

interface InstructorAssignment {
    instructor_id: string;
    role_label: string;
}

interface MaterialRow {
    id: number;
    title: string;
    description: string | null;
    material_type: string;
    status: string;
    original_filename: string | null;
    is_downloadable: boolean;
}

interface Props extends EtmsSharedProps {
    mode: 'create' | 'edit';
    session?: Record<string, any>;
    statuses: Option[];
    recordingPolicies: Option[];
    instructors: Option[];
    materialTypes?: Option[];
    materialStatuses?: Option[];
}

const props = defineProps<Props>();
const page = usePage();

const serverErrors = computed(() => {
    const errors = (page.props as { errors?: Record<string, string> }).errors ?? {};
    return Object.values(errors).filter(Boolean);
});

const form = reactive({
    title: props.session?.title ?? '',
    description: props.session?.description ?? '',
    starts_at: props.session?.starts_at ?? '',
    ends_at: props.session?.ends_at ?? '',
    registration_opens_at: props.session?.registration_opens_at ?? '',
    registration_closes_at: props.session?.registration_closes_at ?? '',
    venue_name: props.session?.venue_name ?? '',
    venue_address: props.session?.venue_address ?? '',
    venue_city: props.session?.venue_city ?? 'Shah Alam',
    venue_state: props.session?.venue_state ?? 'Selangor',
    capacity: props.session?.capacity ?? 25,
    price: props.session?.price ?? '',
    recording_access_policy: props.session?.recording_access_policy ?? 'attendees_only',
    status: props.session?.status ?? 'draft',
    instructors: [...(props.session?.instructors ?? [])] as InstructorAssignment[],
    cover_image: null as File | null,
});

const busy = ref(false);
const coverInputRef = ref<HTMLInputElement | null>(null);
const objectCoverUrl = ref<string | null>(null);
const coverPreview = computed(() => objectCoverUrl.value ?? (props.session?.cover_image_url as string | null) ?? null);

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

const materialForm = reactive({
    title: '',
    description: '',
    material_type: 'pdf',
    status: 'published',
    is_downloadable: true,
    file: null as File | null,
});
const materialFileRef = ref<HTMLInputElement | null>(null);
const editingMaterial = ref<MaterialRow | null>(null);
const editingMaterialFile = ref<File | null>(null);

function addInstructor(): void {
    form.instructors.push({ instructor_id: '', role_label: 'Lead Instructor' });
}

function submit(): void {
    busy.value = true;
    if (props.mode === 'create') {
        router.post('/admin-vue/sessions', form, {
            forceFormData: true,
            onFinish: () => {
                busy.value = false;
            },
        });
    } else {
        router.post(
            `/admin-vue/sessions/${props.session!.id}`,
            { ...form, _method: 'put' },
            {
                forceFormData: true,
                onFinish: () => {
                    busy.value = false;
                },
            },
        );
    }
}

function onMaterialFile(event: Event): void {
    materialForm.file = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function uploadMaterial(): void {
    if (!props.session || !materialForm.file) return;
    router.post(`/admin-vue/sessions/${props.session.id}/materials`, materialForm as any, {
        forceFormData: true,
        preserveScroll: true,
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

function deleteMaterial(material: MaterialRow): void {
    router.delete(`/admin-vue/sessions/${props.session!.id}/materials/${material.id}`, {
        preserveScroll: true,
    });
}

function beginEditMaterial(material: MaterialRow): void {
    editingMaterial.value = { ...material };
    editingMaterialFile.value = null;
}

function onEditMaterialFile(event: Event): void {
    editingMaterialFile.value = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function saveMaterialEdit(): void {
    if (!editingMaterial.value || !props.session) return;
    router.post(
        `/admin-vue/sessions/${props.session.id}/materials/${editingMaterial.value.id}`,
        {
            title: editingMaterial.value.title,
            description: editingMaterial.value.description,
            material_type: editingMaterial.value.material_type,
            status: editingMaterial.value.status,
            is_downloadable: editingMaterial.value.is_downloadable,
            file: editingMaterialFile.value,
        } as any,
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                editingMaterial.value = null;
                editingMaterialFile.value = null;
            },
        },
    );
}
</script>

<template>
    <Head :title="mode === 'create' ? 'Create Training Session' : 'Edit Training Session'" />

    <AdminLayout :breadcrumb="mode === 'create' ? 'Create Session' : 'Edit Session'">
        <div class="mx-auto max-w-[1200px] space-y-6">
            <!-- Top Back Link & Header -->
            <div class="space-y-3">
                <Link
                    href="/admin-vue/sessions"
                    class="inline-flex items-center gap-1.5 text-xs font-medium text-muted transition-colors hover:text-navy-950"
                >
                    <ArrowLeft class="size-3.5" aria-hidden="true" />
                    <span>Training Sessions</span>
                </Link>

                <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h1 class="type-page-title">
                            {{ mode === 'create' ? 'Create training session' : 'Edit training session' }}
                        </h1>
                        <p class="mt-1 text-sm text-muted">
                            {{
                                mode === 'create'
                                    ? 'Configure schedule, venue, pricing, and instructor assignments.'
                                    : 'Update session details, schedule, instructors, and teaching materials.'
                            }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <Link
                            href="/admin-vue/sessions"
                            class="inline-flex h-9 items-center rounded-lg bg-white px-4 text-xs font-semibold text-muted ring-1 ring-line transition-colors hover:text-navy-950"
                        >
                            Cancel
                        </Link>
                        <button
                            type="button"
                            class="inline-flex h-9 items-center rounded-lg bg-navy-950 px-5 text-xs font-semibold text-white transition-colors hover:bg-navy-900 disabled:opacity-50"
                            :disabled="busy"
                            @click="submit"
                        >
                            {{ busy ? 'Saving...' : mode === 'create' ? 'Create session' : 'Save changes' }}
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

            <!-- Main Form Grid -->
            <form class="grid gap-6 lg:grid-cols-12 lg:items-start" @submit.prevent="submit">
                <!-- Left Main Column (8 cols) -->
                <div class="space-y-6 lg:col-span-8">
                    <!-- Unified Session Surface -->
                    <div class="divide-y divide-line rounded-2xl bg-white shadow-card ring-1 ring-neutral-200/80">
                        <!-- 1. Basic Info & Cover Image -->
                        <section class="space-y-5 p-6">
                            <h2 class="text-sm font-semibold text-navy-950">Session details</h2>

                            <div>
                                <label for="session-title" class="block text-xs font-medium text-ink">
                                    Session title <span class="text-danger-600">*</span>
                                </label>
                                <input
                                    id="session-title"
                                    v-model="form.title"
                                    type="text"
                                    required
                                    maxlength="200"
                                    placeholder="e.g. Urban Stormwater Modelling with SWMM"
                                    class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink placeholder:text-muted/60 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                />
                            </div>

                            <div>
                                <label for="session-description" class="block text-xs font-medium text-ink">
                                    Description
                                </label>
                                <textarea
                                    id="session-description"
                                    v-model="form.description"
                                    rows="4"
                                    placeholder="Outline the session focus, target participants, and key practical outcomes..."
                                    class="mt-1.5 w-full rounded-lg border border-line-strong/80 bg-white p-3.5 text-sm text-ink placeholder:text-muted/60 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                />
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
                                        class="group relative h-28 w-48 shrink-0 overflow-hidden rounded-xl bg-sand-50 ring-1 ring-line transition-all hover:ring-primary-600/40"
                                        @click="coverInputRef?.click()"
                                    >
                                        <img
                                            v-if="coverPreview"
                                            :src="coverPreview"
                                            alt="Session cover preview"
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

                        <!-- 2. Schedule & Registration Window -->
                        <section class="space-y-4 p-6">
                            <h2 class="text-sm font-semibold text-navy-950">Schedule &amp; registration window</h2>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="session-starts" class="block text-xs font-medium text-ink">
                                        Starts <span class="text-danger-600">*</span>
                                    </label>
                                    <input
                                        id="session-starts"
                                        v-model="form.starts_at"
                                        type="datetime-local"
                                        required
                                        class="tabular mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3 text-sm text-ink transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                    />
                                </div>

                                <div>
                                    <label for="session-ends" class="block text-xs font-medium text-ink">
                                        Ends <span class="text-danger-600">*</span>
                                    </label>
                                    <input
                                        id="session-ends"
                                        v-model="form.ends_at"
                                        type="datetime-local"
                                        required
                                        class="tabular mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3 text-sm text-ink transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                    />
                                </div>

                                <div>
                                    <label for="session-reg-opens" class="block text-xs font-medium text-ink">
                                        Registration opens
                                    </label>
                                    <input
                                        id="session-reg-opens"
                                        v-model="form.registration_opens_at"
                                        type="datetime-local"
                                        :max="form.registration_closes_at || form.starts_at"
                                        class="tabular mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3 text-sm text-ink transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                    />
                                </div>

                                <div>
                                    <label for="session-reg-closes" class="block text-xs font-medium text-ink">
                                        Registration closes <span class="text-danger-600">*</span>
                                    </label>
                                    <input
                                        id="session-reg-closes"
                                        v-model="form.registration_closes_at"
                                        type="datetime-local"
                                        required
                                        :max="form.starts_at"
                                        class="tabular mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3 text-sm text-ink transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                    />
                                </div>
                            </div>
                        </section>

                        <!-- 3. Venue & Location -->
                        <section class="space-y-4 p-6">
                            <h2 class="text-sm font-semibold text-navy-950">Venue &amp; location</h2>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label for="session-venue" class="block text-xs font-medium text-ink">
                                        Venue name <span class="text-danger-600">*</span>
                                    </label>
                                    <input
                                        id="session-venue"
                                        v-model="form.venue_name"
                                        type="text"
                                        required
                                        maxlength="200"
                                        placeholder="e.g. Ecohydrotech Training Centre"
                                        class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink placeholder:text-muted/60 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                    />
                                </div>

                                <div class="sm:col-span-2">
                                    <label for="session-address" class="block text-xs font-medium text-ink">
                                        Street address <span class="text-danger-600">*</span>
                                    </label>
                                    <textarea
                                        id="session-address"
                                        v-model="form.venue_address"
                                        rows="2"
                                        required
                                        maxlength="255"
                                        placeholder="Full street address, building, or floor"
                                        class="mt-1.5 w-full rounded-lg border border-line-strong/80 bg-white p-3 text-sm text-ink placeholder:text-muted/60 transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                    />
                                </div>

                                <div>
                                    <label for="session-city" class="block text-xs font-medium text-ink">
                                        City <span class="text-danger-600">*</span>
                                    </label>
                                    <input
                                        id="session-city"
                                        v-model="form.venue_city"
                                        type="text"
                                        required
                                        maxlength="100"
                                        class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                    />
                                </div>

                                <div>
                                    <label for="session-state" class="block text-xs font-medium text-ink">
                                        State <span class="text-danger-600">*</span>
                                    </label>
                                    <input
                                        id="session-state"
                                        v-model="form.venue_state"
                                        type="text"
                                        required
                                        maxlength="100"
                                        class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink transition-colors focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                    />
                                </div>
                            </div>
                        </section>

                        <!-- 4. Assigned Instructors -->
                        <section class="space-y-4 p-6">
                            <div class="flex items-center justify-between gap-3">
                                <h2 class="text-sm font-semibold text-navy-950">Assigned instructors</h2>
                                <button
                                    type="button"
                                    class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-sand-50 px-3 text-xs font-semibold text-navy-950 ring-1 ring-line transition-colors hover:bg-sand-100"
                                    @click="addInstructor"
                                >
                                    <Plus class="size-3.5" aria-hidden="true" />
                                    <span>Add instructor</span>
                                </button>
                            </div>

                            <p v-if="form.instructors.length === 0" class="text-xs text-muted">
                                No instructors assigned yet. Add one or more instructors to display on the session page.
                            </p>

                            <div v-else class="space-y-2.5">
                                <div
                                    v-for="(assignment, index) in form.instructors"
                                    :key="index"
                                    class="grid gap-2.5 sm:grid-cols-[1fr_1fr_auto]"
                                >
                                    <select
                                        v-model="assignment.instructor_id"
                                        required
                                        class="h-10 rounded-lg border border-line-strong/80 bg-white px-3 text-sm text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                    >
                                        <option value="">Select instructor</option>
                                        <option
                                            v-for="instructor in instructors"
                                            :key="instructor.value"
                                            :value="instructor.value"
                                        >
                                            {{ instructor.label }}
                                        </option>
                                    </select>

                                    <input
                                        v-model="assignment.role_label"
                                        type="text"
                                        required
                                        maxlength="80"
                                        placeholder="Role label (e.g. Lead Instructor)"
                                        class="h-10 rounded-lg border border-line-strong/80 bg-white px-3 text-sm text-ink placeholder:text-muted/60 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                    />

                                    <button
                                        type="button"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-muted ring-1 ring-line transition-colors hover:bg-danger-50 hover:text-danger-700 hover:ring-danger-600/20"
                                        title="Remove instructor"
                                        aria-label="Remove instructor"
                                        @click="form.instructors.splice(index, 1)"
                                    >
                                        <Trash2 class="size-4" aria-hidden="true" />
                                    </button>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- Edit Mode Only: Session Teaching Materials -->
                    <section
                        v-if="mode === 'edit'"
                        class="rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80"
                    >
                        <h2 class="text-sm font-semibold text-navy-950">Session teaching materials</h2>

                        <div class="mt-4 grid gap-3 rounded-xl bg-sand-50/80 p-4 ring-1 ring-line/70 sm:grid-cols-2">
                            <input
                                v-model="materialForm.title"
                                type="text"
                                placeholder="Material title"
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
                                <option v-for="status in materialStatuses" :key="status.value" :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                            <div class="flex items-center gap-2">
                                <input
                                    ref="materialFileRef"
                                    type="file"
                                    accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.csv,.txt,.zip,.png,.jpg,.jpeg"
                                    class="sr-only"
                                    @change="onMaterialFile"
                                />
                                <button
                                    type="button"
                                    class="inline-flex h-9 flex-1 items-center justify-center gap-1.5 truncate rounded-lg bg-white px-3 text-xs font-semibold text-navy-950 ring-1 ring-line hover:bg-sand-50"
                                    @click="materialFileRef?.click()"
                                >
                                    <Upload class="size-3.5 shrink-0" aria-hidden="true" />
                                    <span class="truncate">{{ materialForm.file?.name || 'Choose file' }}</span>
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
                                    <span>Downloadable by confirmed session attendees</span>
                                </label>
                                <button
                                    type="button"
                                    :disabled="!materialForm.file || !materialForm.title"
                                    class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-navy-950 px-3.5 text-xs font-semibold text-white transition-colors hover:bg-navy-900 disabled:opacity-45"
                                    @click="uploadMaterial"
                                >
                                    <Upload class="size-3.5" aria-hidden="true" />
                                    <span>Upload material</span>
                                </button>
                            </div>
                        </div>

                        <ul v-if="session?.materials?.length" class="mt-4 divide-y divide-line/70">
                            <li
                                v-for="material in session.materials"
                                :key="material.id"
                                class="flex items-center justify-between gap-3 py-3"
                            >
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <FileText class="size-4 shrink-0 text-primary-600" aria-hidden="true" />
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-navy-950">{{ material.title }}</p>
                                        <p class="truncate text-xs text-muted">{{ material.original_filename }}</p>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center gap-2">
                                    <StatusBadge :tone="material.status === 'published' ? 'success' : 'neutral'">
                                        {{ material.status }}
                                    </StatusBadge>
                                    <button
                                        type="button"
                                        class="inline-flex size-8 items-center justify-center rounded-lg text-muted hover:bg-sand-100 hover:text-navy-950"
                                        aria-label="Edit material"
                                        @click="beginEditMaterial(material)"
                                    >
                                        <Pencil class="size-3.5" aria-hidden="true" />
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex size-8 items-center justify-center rounded-lg text-muted hover:bg-danger-50 hover:text-danger-700"
                                        aria-label="Delete material"
                                        @click="deleteMaterial(material)"
                                    >
                                        <Trash2 class="size-3.5" aria-hidden="true" />
                                    </button>
                                </div>
                            </li>
                        </ul>

                        <div
                            v-if="editingMaterial"
                            class="mt-4 grid gap-3 rounded-xl border border-primary-600/25 bg-primary-50/30 p-4 sm:grid-cols-2"
                        >
                            <input
                                v-model="editingMaterial.title"
                                type="text"
                                class="h-9 rounded-lg border border-line-strong/80 bg-white px-3 text-xs text-ink"
                            />
                            <select
                                v-model="editingMaterial.material_type"
                                class="h-9 rounded-lg border border-line-strong/80 bg-white px-3 text-xs text-ink"
                            >
                                <option v-for="type in materialTypes" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                            <select
                                v-model="editingMaterial.status"
                                class="h-9 rounded-lg border border-line-strong/80 bg-white px-3 text-xs text-ink"
                            >
                                <option v-for="status in materialStatuses" :key="status.value" :value="status.value">
                                    {{ status.label }}
                                </option>
                            </select>
                            <input
                                type="file"
                                accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.csv,.txt,.zip,.png,.jpg,.jpeg"
                                class="text-xs text-muted"
                                @change="onEditMaterialFile"
                            />
                            <textarea
                                v-model="editingMaterial.description"
                                rows="2"
                                class="rounded-lg border border-line-strong/80 bg-white p-2.5 text-xs text-ink sm:col-span-2"
                            />
                            <div class="flex flex-wrap items-center justify-between gap-2 sm:col-span-2">
                                <label class="flex items-center gap-2 text-xs text-ink">
                                    <input
                                        v-model="editingMaterial.is_downloadable"
                                        type="checkbox"
                                        class="size-4 rounded border-line-strong accent-navy-950"
                                    />
                                    <span>Downloadable</span>
                                </label>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="h-8 rounded-lg bg-white px-3 text-xs font-semibold text-muted ring-1 ring-line hover:text-navy-950"
                                        @click="editingMaterial = null"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="button"
                                        class="h-8 rounded-lg bg-navy-950 px-3.5 text-xs font-semibold text-white hover:bg-navy-900"
                                        @click="saveMaterialEdit"
                                    >
                                        Save material
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Right Sticky Column (4 cols): Pricing, Capacity & Publishing -->
                <aside class="space-y-5 lg:sticky lg:top-24 lg:col-span-4">
                    <div class="space-y-4 rounded-2xl bg-white p-6 shadow-card ring-1 ring-neutral-200/80">
                        <h2 class="text-sm font-semibold text-navy-950">Pricing &amp; publishing</h2>

                        <div>
                            <label for="session-status" class="block text-xs font-medium text-ink">
                                Lifecycle status <span class="text-danger-600">*</span>
                            </label>
                            <select
                                id="session-status"
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
                            <label for="session-price" class="block text-xs font-medium text-ink">
                                Registration fee ({{ app.currency }}) <span class="text-danger-600">*</span>
                            </label>
                            <div class="relative mt-1.5">
                                <span class="pointer-events-none absolute top-1/2 left-3.5 -translate-y-1/2 text-xs font-semibold text-muted">
                                    {{ app.currency }}
                                </span>
                                <input
                                    id="session-price"
                                    v-model="form.price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    required
                                    placeholder="0.00"
                                    class="tabular h-10 w-full rounded-lg border border-line-strong/80 bg-white pr-3.5 pl-13 text-sm font-semibold text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                />
                            </div>
                        </div>

                        <div>
                            <label for="session-capacity" class="block text-xs font-medium text-ink">
                                Seat capacity <span class="text-danger-600">*</span>
                            </label>
                            <input
                                id="session-capacity"
                                v-model="form.capacity"
                                type="number"
                                min="1"
                                required
                                class="tabular mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm font-semibold text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            />
                        </div>

                        <div>
                            <label for="session-recording-policy" class="block text-xs font-medium text-ink">
                                Recording access policy
                            </label>
                            <select
                                id="session-recording-policy"
                                v-model="form.recording_access_policy"
                                class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3 text-sm text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                            >
                                <option
                                    v-for="policy in recordingPolicies"
                                    :key="policy.value"
                                    :value="policy.value"
                                >
                                    {{ policy.label }}
                                </option>
                            </select>
                        </div>

                        <div class="space-y-2 border-t border-line pt-4">
                            <button
                                type="submit"
                                :disabled="busy"
                                class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-navy-950 px-4 text-xs font-semibold text-white transition-colors hover:bg-navy-900 disabled:opacity-50"
                            >
                                {{ busy ? 'Saving...' : mode === 'create' ? 'Create session' : 'Save changes' }}
                            </button>

                            <Link
                                href="/admin-vue/sessions"
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
