<script setup lang="ts">
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    DialogClose,
    DialogContent,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
} from 'reka-ui';
import {
    Camera,
    Eye,
    EyeOff,
    GraduationCap,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    Trash2,
    X,
} from 'lucide-vue-next';
import AppButton from '@/components/ds/AppButton.vue';
import DataTable, { type DataTableColumn } from '@/components/ds/DataTable.vue';
import EmptyState from '@/components/ds/EmptyState.vue';
import StatusBadge from '@/components/ds/StatusBadge.vue';
import UserAvatar from '@/components/ds/UserAvatar.vue';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { EtmsSharedProps } from '@/types/etms';

interface ProfileItem {
    id: number;
    userId: number;
    name: string | null;
    email: string | null;
    professionalTitle: string | null;
    qualifications: string | null;
    shortBio: string | null;
    biography: string | null;
    specialisations: string[];
    isPublic: boolean;
    photoUrl: string | null;
}

type ProfileRow = ProfileItem & Record<string, unknown>;

interface Props extends EtmsSharedProps {
    profiles: {
        data: ProfileItem[];
        from: number | null;
        to: number | null;
        total: number;
        last_page?: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { q: string; public: string };
}

const props = defineProps<Props>();
const filters = reactive({ ...props.filters });
const modalOpen = ref(false);
const editing = ref<ProfileItem | null>(null);
const deleteTarget = ref<ProfileItem | null>(null);
const busy = ref(false);
const deleting = ref(false);
const showPassword = ref(false);
const photoInputRef = ref<HTMLInputElement | null>(null);
const objectPhotoUrl = ref<string | null>(null);

const form = reactive({
    name: '',
    email: '',
    password: '',
    professional_title: '',
    qualifications: '',
    short_bio: '',
    biography: '',
    specialisations: '',
    is_public: true,
    photo: null as File | null,
});

const deleteModalOpen = computed({
    get: () => deleteTarget.value !== null,
    set: (open: boolean) => {
        if (!open) deleteTarget.value = null;
    },
});

const hasActiveFilters = computed(
    () => filters.q.trim() !== '' || filters.public !== '',
);

const rows = computed<ProfileRow[]>(() => props.profiles.data as ProfileRow[]);

const displayedPhoto = computed(
    () => objectPhotoUrl.value ?? editing.value?.photoUrl ?? null,
);

const formInitials = computed(() => {
    const parts = (form.name || form.email || 'I').trim().split(/\s+/).filter(Boolean);
    return parts.slice(0, 2).map((p) => p[0]?.toUpperCase() ?? '').join('') || 'I';
});

const columns: DataTableColumn[] = [
    { key: 'instructor', label: 'Instructor' },
    { key: 'title', label: 'Role & Qualifications' },
    { key: 'specialisations', label: 'Specialisations' },
    { key: 'visibility', label: 'Visibility' },
];

function apply(): void {
    router.get(
        '/admin-vue/instructor-profiles',
        {
            q: filters.q.trim() || undefined,
            public: filters.public !== '' ? filters.public : undefined,
        },
        { preserveState: true, replace: true },
    );
}

function selectVisibility(val: string): void {
    filters.public = val;
    apply();
}

function clearFilters(): void {
    filters.q = '';
    filters.public = '';
    apply();
}

function clearObjectPhoto(): void {
    if (objectPhotoUrl.value) {
        URL.revokeObjectURL(objectPhotoUrl.value);
        objectPhotoUrl.value = null;
    }
    if (photoInputRef.value) {
        photoInputRef.value.value = '';
    }
}

function beginCreate(): void {
    editing.value = null;
    showPassword.value = false;
    clearObjectPhoto();
    Object.assign(form, {
        name: '',
        email: '',
        password: '',
        professional_title: '',
        qualifications: '',
        short_bio: '',
        biography: '',
        specialisations: '',
        is_public: true,
        photo: null,
    });
    modalOpen.value = true;
}

function beginEdit(profile: ProfileItem): void {
    editing.value = profile;
    showPassword.value = false;
    clearObjectPhoto();
    Object.assign(form, {
        name: profile.name ?? '',
        email: profile.email ?? '',
        password: '',
        professional_title: profile.professionalTitle ?? '',
        qualifications: profile.qualifications ?? '',
        short_bio: profile.shortBio ?? '',
        biography: profile.biography ?? '',
        specialisations: profile.specialisations.join(', '),
        is_public: profile.isPublic,
        photo: null,
    });
    modalOpen.value = true;
}

function handlePhoto(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    clearObjectPhoto();
    form.photo = file;
    if (file) {
        objectPhotoUrl.value = URL.createObjectURL(file);
    }
}

function save(): void {
    busy.value = true;
    const data = {
        ...form,
        specialisations: form.specialisations
            .split(',')
            .map((value) => value.trim())
            .filter(Boolean),
        photo: form.photo ?? undefined,
    };

    const targetUrl = editing.value
        ? `/admin-vue/instructor-profiles/${editing.value.id}`
        : '/admin-vue/instructor-profiles';

    router.post(targetUrl, data, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            busy.value = false;
        },
        onSuccess: () => {
            modalOpen.value = false;
            clearObjectPhoto();
        },
    });
}

function toggleVisibility(profile: ProfileItem): void {
    router.post(
        `/admin-vue/instructor-profiles/${profile.id}/toggle`,
        {},
        { preserveScroll: true },
    );
}

function confirmDelete(): void {
    if (!deleteTarget.value) return;
    deleting.value = true;
    router.delete(`/admin-vue/instructor-profiles/${deleteTarget.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteTarget.value = null;
        },
    });
}

function decodeLabel(label: string): string {
    return label
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»')
        .replace(/&amp;/g, '&');
}

onBeforeUnmount(() => {
    clearObjectPhoto();
});
</script>

<template>
    <Head title="Instructors" />

    <AdminLayout breadcrumb="Instructors">
        <div class="space-y-6">
            <!-- Header -->
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title">Instructors</h1>
                    <p class="mt-1 text-sm text-muted">
                        Manage instructor accounts, public directory profiles, and biographies.
                    </p>
                </div>

                <AppButton variant="warm" size="sm" pill @click="beginCreate">
                    <Plus class="size-3.5" aria-hidden="true" />
                    <span>Create instructor</span>
                </AppButton>
            </header>

            <!-- Filter & Search Bar -->
            <div class="flex flex-col gap-3 rounded-2xl bg-white p-3.5 shadow-card ring-1 ring-neutral-200/80 xl:flex-row xl:items-center xl:justify-between">
                <div
                    role="navigation"
                    aria-label="Filter instructors by visibility"
                    class="flex flex-wrap items-center gap-1.5"
                >
                    <button
                        type="button"
                        class="inline-flex items-center rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150"
                        :class="
                            filters.public === ''
                                ? 'bg-navy-950 text-white shadow-xs'
                                : 'bg-sand-50 text-muted hover:bg-sand-100 hover:text-navy-950'
                        "
                        @click="selectVisibility('')"
                    >
                        All
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150"
                        :class="
                            filters.public === '1'
                                ? 'bg-navy-950 text-white shadow-xs'
                                : 'bg-sand-50 text-muted hover:bg-sand-100 hover:text-navy-950'
                        "
                        @click="selectVisibility('1')"
                    >
                        Public
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-full px-3.5 py-1.5 text-xs font-semibold transition-colors duration-150"
                        :class="
                            filters.public === '0'
                                ? 'bg-navy-950 text-white shadow-xs'
                                : 'bg-sand-50 text-muted hover:bg-sand-100 hover:text-navy-950'
                        "
                        @click="selectVisibility('0')"
                    >
                        Hidden
                    </button>
                </div>

                <form class="flex items-center gap-2" @submit.prevent="apply">
                    <div class="relative flex-1 xl:w-72">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-3.5 -translate-y-1/2 text-muted"
                            aria-hidden="true"
                        />
                        <input
                            v-model="filters.q"
                            type="search"
                            placeholder="Search name or email..."
                            aria-label="Search instructors"
                            class="h-9 w-full rounded-xl border border-line-strong/80 bg-white pr-3 pl-8.5 text-xs text-ink placeholder:text-muted/75 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                        />
                    </div>

                    <button
                        type="submit"
                        class="inline-flex h-9 items-center rounded-xl bg-navy-950 px-3.5 text-xs font-semibold text-white transition-colors hover:bg-navy-900"
                    >
                        Search
                    </button>

                    <button
                        v-if="hasActiveFilters"
                        type="button"
                        class="inline-flex h-9 items-center gap-1 rounded-xl border border-line bg-white px-3 text-xs font-semibold text-muted transition-colors hover:bg-sand-50 hover:text-navy-950"
                        @click="clearFilters"
                    >
                        <RotateCcw class="size-3.5" aria-hidden="true" />
                        <span>Reset</span>
                    </button>
                </form>
            </div>

            <!-- Empty State -->
            <EmptyState
                v-if="rows.length === 0"
                :icon="GraduationCap"
                title="No instructors found"
                :description="
                    hasActiveFilters
                        ? 'Try clearing your search or visibility filter.'
                        : 'Create your first instructor account to assign them to training sessions.'
                "
            >
                <template #action>
                    <div class="flex flex-wrap items-center justify-center gap-2.5">
                        <AppButton
                            v-if="hasActiveFilters"
                            variant="outline"
                            size="sm"
                            pill
                            @click="clearFilters"
                        >
                            Reset filters
                        </AppButton>
                        <AppButton variant="warm" size="sm" pill @click="beginCreate">
                            <Plus class="size-3.5" aria-hidden="true" />
                            <span>Create instructor</span>
                        </AppButton>
                    </div>
                </template>
            </EmptyState>

            <!-- DataTable + Pagination -->
            <template v-else>
                <DataTable :columns="columns" :rows="rows" row-key="id">
                    <template #cell-instructor="{ row }">
                        <div class="flex items-center gap-3">
                            <UserAvatar
                                :name="row.name || row.email || 'Instructor'"
                                :photo-url="row.photoUrl"
                                size="md"
                            />
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-navy-950">
                                    {{ row.name || 'Unnamed Instructor' }}
                                </p>
                                <p class="truncate text-xs text-muted">{{ row.email }}</p>
                            </div>
                        </div>
                    </template>

                    <template #cell-title="{ row }">
                        <p class="text-sm font-medium text-ink">
                            {{ row.professionalTitle || '—' }}
                        </p>
                        <p v-if="row.qualifications" class="mt-0.5 line-clamp-1 text-xs text-muted">
                            {{ row.qualifications }}
                        </p>
                    </template>

                    <template #cell-specialisations="{ row }">
                        <div v-if="row.specialisations.length > 0" class="flex flex-wrap gap-1.5">
                            <span
                                v-for="spec in row.specialisations.slice(0, 2)"
                                :key="spec"
                                class="inline-flex rounded-full bg-sand-100 px-2.5 py-0.5 text-[11px] font-medium text-ink"
                            >
                                {{ spec }}
                            </span>
                            <span
                                v-if="row.specialisations.length > 2"
                                class="tabular inline-flex rounded-full bg-sand-100 px-2 py-0.5 text-[11px] font-medium text-muted"
                            >
                                +{{ row.specialisations.length - 2 }}
                            </span>
                        </div>
                        <span v-else class="text-xs text-muted">—</span>
                    </template>

                    <template #cell-visibility="{ row }">
                        <StatusBadge :tone="row.isPublic ? 'success' : 'neutral'" dot>
                            {{ row.isPublic ? 'Public' : 'Hidden' }}
                        </StatusBadge>
                    </template>

                    <template #actions="{ row }">
                        <div class="inline-flex items-center justify-end gap-1.5">
                            <button
                                type="button"
                                class="inline-flex h-8 items-center gap-1 rounded-full bg-sand-50 px-3 text-xs font-semibold text-navy-950 ring-1 ring-line transition-colors hover:bg-navy-950 hover:text-white hover:ring-navy-950"
                                @click="beginEdit(row)"
                            >
                                <Pencil class="size-3" aria-hidden="true" />
                                <span>Edit</span>
                            </button>

                            <button
                                type="button"
                                class="inline-flex size-8 items-center justify-center rounded-full text-muted transition-colors hover:bg-sand-100 hover:text-navy-950"
                                :title="row.isPublic ? 'Hide from public directory' : 'Publish to public directory'"
                                :aria-label="row.isPublic ? 'Hide profile' : 'Publish profile'"
                                @click="toggleVisibility(row)"
                            >
                                <EyeOff v-if="row.isPublic" class="size-3.5" aria-hidden="true" />
                                <Eye v-else class="size-3.5" aria-hidden="true" />
                            </button>

                            <button
                                type="button"
                                class="inline-flex size-8 items-center justify-center rounded-full text-muted transition-colors hover:bg-danger-50 hover:text-danger-700"
                                title="Delete instructor"
                                aria-label="Delete instructor"
                                @click="deleteTarget = row"
                            >
                                <Trash2 class="size-3.5" aria-hidden="true" />
                            </button>
                        </div>
                    </template>
                </DataTable>

                <!-- Pagination -->
                <div
                    v-if="profiles.total > 0"
                    class="flex flex-col items-center justify-between gap-3 text-xs text-muted sm:flex-row"
                >
                    <span class="tabular">
                        Showing {{ profiles.from ?? 0 }}–{{ profiles.to ?? 0 }} of {{ profiles.total }} instructors
                    </span>

                    <nav
                        v-if="profiles.links.length > 3"
                        class="flex flex-wrap items-center gap-1"
                        aria-label="Pagination"
                    >
                        <template v-for="link in profiles.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                preserve-scroll
                                class="tabular inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-xs font-semibold transition-colors"
                                :class="
                                    link.active
                                        ? 'bg-navy-950 text-white'
                                        : 'bg-white text-ink ring-1 ring-line hover:bg-sand-50'
                                "
                            >
                                {{ decodeLabel(link.label) }}
                            </Link>
                            <span
                                v-else
                                class="tabular inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-xs font-medium text-muted/45"
                            >
                                {{ decodeLabel(link.label) }}
                            </span>
                        </template>
                    </nav>
                </div>
            </template>
        </div>

        <!-- Reka UI Create / Edit Instructor Modal -->
        <DialogRoot v-model:open="modalOpen">
            <DialogPortal>
                <DialogOverlay class="fixed inset-0 z-50 bg-navy-950/55 backdrop-blur-[2px]" />
                <DialogContent
                    class="fixed top-1/2 left-1/2 z-50 flex max-h-[90vh] w-[calc(100vw-2rem)] max-w-2xl -translate-x-1/2 -translate-y-1/2 flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-neutral-200 focus:outline-none"
                >
                    <div class="flex items-start justify-between gap-3 border-b border-line px-6 py-4">
                        <div>
                            <DialogTitle class="text-base font-bold text-navy-950">
                                {{ editing ? 'Edit instructor' : 'Create instructor' }}
                            </DialogTitle>
                            <p class="mt-0.5 text-xs text-muted">
                                {{ editing ? 'Update account and public profile details.' : 'Create a login account and public instructor profile.' }}
                            </p>
                        </div>
                        <DialogClose
                            class="inline-flex size-8 items-center justify-center rounded-full text-muted hover:bg-sand-100 hover:text-navy-950"
                            aria-label="Close dialog"
                        >
                            <X class="size-4" aria-hidden="true" />
                        </DialogClose>
                    </div>

                    <form class="overflow-y-auto p-6" @submit.prevent="save">
                        <!-- Photo Upload Row -->
                        <div class="mb-6 flex items-center gap-5">
                            <input
                                ref="photoInputRef"
                                type="file"
                                accept="image/*"
                                class="sr-only"
                                @change="handlePhoto"
                            />
                            <button
                                type="button"
                                class="group relative size-20 shrink-0 overflow-hidden rounded-full bg-sand-100 ring-2 ring-line transition-all hover:ring-navy-950/30"
                                @click="photoInputRef?.click()"
                            >
                                <img
                                    v-if="displayedPhoto"
                                    :src="displayedPhoto"
                                    alt="Instructor photo"
                                    class="size-full object-cover"
                                />
                                <span
                                    v-else
                                    class="flex size-full items-center justify-center bg-navy-950 text-lg font-bold text-white"
                                >
                                    {{ formInitials }}
                                </span>
                                <span
                                    class="absolute inset-0 flex items-center justify-center bg-navy-950/55 text-white opacity-0 transition-opacity group-hover:opacity-100"
                                >
                                    <Camera class="size-4" aria-hidden="true" />
                                </span>
                            </button>

                            <div class="space-y-1">
                                <button
                                    type="button"
                                    class="text-xs font-semibold text-primary-600 hover:text-primary-700"
                                    @click="photoInputRef?.click()"
                                >
                                    {{ displayedPhoto ? 'Change photograph' : 'Upload photograph' }}
                                </button>
                                <p class="text-xs text-muted">JPG, PNG, or WebP square portrait recommended.</p>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-ink">
                                    Full name <span class="text-danger-600">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    required
                                    maxlength="150"
                                    class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-ink">
                                    Email address <span class="text-danger-600">*</span>
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    required
                                    maxlength="255"
                                    class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-ink">
                                    {{ editing ? 'New password (leave blank to keep current)' : 'Password' }}
                                    <span v-if="!editing" class="text-danger-600">*</span>
                                </label>
                                <div class="relative mt-1.5">
                                    <input
                                        v-model="form.password"
                                        :required="!editing"
                                        :type="showPassword ? 'text' : 'password'"
                                        minlength="8"
                                        class="h-10 w-full rounded-lg border border-line-strong/80 bg-white pr-10 pl-3.5 text-sm text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                    />
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-muted hover:text-navy-950"
                                        @click="showPassword = !showPassword"
                                    >
                                        <EyeOff v-if="showPassword" class="size-4" aria-hidden="true" />
                                        <Eye v-else class="size-4" aria-hidden="true" />
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-ink">Professional title</label>
                                <input
                                    v-model="form.professional_title"
                                    type="text"
                                    maxlength="150"
                                    placeholder="e.g. Principal Hydrologist"
                                    class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink placeholder:text-muted/60 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-medium text-ink">Qualifications</label>
                                <input
                                    v-model="form.qualifications"
                                    type="text"
                                    maxlength="255"
                                    placeholder="e.g. PhD, P.Eng, CEng"
                                    class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink placeholder:text-muted/60 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-ink">
                                    Specialisations <span class="font-normal text-muted">(comma separated)</span>
                                </label>
                                <input
                                    v-model="form.specialisations"
                                    type="text"
                                    placeholder="e.g. Urban Drainage, HEC-RAS, Flood Modelling"
                                    class="mt-1.5 h-10 w-full rounded-lg border border-line-strong/80 bg-white px-3.5 text-sm text-ink placeholder:text-muted/60 focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-ink">Short summary</label>
                                <textarea
                                    v-model="form.short_bio"
                                    rows="2"
                                    maxlength="500"
                                    class="mt-1.5 w-full rounded-lg border border-line-strong/80 bg-white p-3 text-sm text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-ink">Biography</label>
                                <textarea
                                    v-model="form.biography"
                                    rows="4"
                                    class="mt-1.5 w-full rounded-lg border border-line-strong/80 bg-white p-3 text-sm text-ink focus:border-primary-600 focus:outline-none focus:ring-2 focus:ring-primary-600/15"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <label class="flex items-center gap-2.5 text-sm text-ink">
                                    <input
                                        v-model="form.is_public"
                                        type="checkbox"
                                        class="size-4 rounded border-line-strong accent-navy-950"
                                    />
                                    <span>Publish profile in the public instructor directory</span>
                                </label>
                            </div>
                        </div>

                        <div class="mt-6 flex items-center justify-end gap-2.5 border-t border-line pt-4">
                            <DialogClose
                                class="inline-flex h-9 items-center rounded-lg px-4 text-xs font-semibold text-muted hover:bg-sand-100 hover:text-navy-950"
                            >
                                Cancel
                            </DialogClose>
                            <button
                                type="submit"
                                :disabled="busy"
                                class="inline-flex h-9 items-center rounded-lg bg-navy-950 px-5 text-xs font-semibold text-white transition-colors hover:bg-navy-900 disabled:opacity-50"
                            >
                                {{ busy ? 'Saving...' : editing ? 'Save changes' : 'Create instructor' }}
                            </button>
                        </div>
                    </form>
                </DialogContent>
            </DialogPortal>
        </DialogRoot>

        <!-- Reka UI Delete Confirmation Modal -->
        <DialogRoot v-model:open="deleteModalOpen">
            <DialogPortal>
                <DialogOverlay class="fixed inset-0 z-50 bg-navy-950/55 backdrop-blur-[2px]" />
                <DialogContent
                    v-if="deleteTarget"
                    class="fixed top-1/2 left-1/2 z-50 w-[calc(100vw-2rem)] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-neutral-200 focus:outline-none"
                >
                    <DialogTitle class="text-base font-bold text-navy-950">
                        Delete instructor profile
                    </DialogTitle>
                    <p class="mt-2 text-sm text-muted">
                        Are you sure you want to remove <strong class="text-navy-950">{{ deleteTarget.name || deleteTarget.email }}</strong>?
                    </p>
                    <div class="mt-5 flex items-center justify-end gap-2.5">
                        <DialogClose
                            class="inline-flex h-9 items-center rounded-lg px-4 text-xs font-semibold text-muted hover:bg-sand-100 hover:text-navy-950"
                        >
                            Cancel
                        </DialogClose>
                        <button
                            type="button"
                            :disabled="deleting"
                            class="inline-flex h-9 items-center rounded-lg bg-danger-600 px-4 text-xs font-semibold text-white transition-colors hover:bg-danger-700 disabled:opacity-50"
                            @click="confirmDelete"
                        >
                            {{ deleting ? 'Deleting...' : 'Delete' }}
                        </button>
                    </div>
                </DialogContent>
            </DialogPortal>
        </DialogRoot>
    </AdminLayout>
</template>
