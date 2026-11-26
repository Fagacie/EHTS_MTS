<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Camera,
    Eye,
    EyeOff,
    Pencil,
    Plus,
    RotateCcw,
    Search,
    ShieldAlert,
    Trash2,
    Users,
    X,
} from 'lucide-vue-next';
import {
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogOverlay,
    DialogPortal,
    DialogRoot,
    DialogTitle,
} from 'reka-ui';
import AdminLayout from '@/layouts/AdminLayout.vue';
import DataTable, { type DataTableColumn } from '@/components/ds/DataTable.vue';
import StatusBadge from '@/components/ds/StatusBadge.vue';
import UserAvatar from '@/components/ds/UserAvatar.vue';
import type { EtmsSharedProps } from '@/types/etms';

interface UserRow {
    [key: string]: unknown;
    id: number;
    name: string;
    email: string;
    roles: string[];
    roleLabels: string[];
    status: string;
    statusValue: string;
    verified: boolean;
    photoUrl: string | null;
    profile_phone: string | null;
    profile_job_title: string | null;
    profile_billing_contact_name: string | null;
    profile_billing_email: string | null;
    profile_billing_phone: string | null;
    profile_billing_address_line_1: string | null;
    profile_billing_address_line_2: string | null;
    profile_billing_city: string | null;
    profile_billing_state: string | null;
    profile_billing_postcode: string | null;
    profile_billing_country: string | null;
    instructor_professional_title: string | null;
    instructor_qualifications: string | null;
    instructor_short_bio: string | null;
    instructor_biography: string | null;
    instructor_specialisations: string[];
    instructor_is_public: boolean;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props extends EtmsSharedProps {
    users: {
        data: UserRow[];
        from: number | null;
        to: number | null;
        total: number;
        links: PaginationLink[];
    };
    filters: { q: string; status: string; role?: string };
    statuses: { value: string; label: string }[];
    roles: { value: string; label: string }[];
}

const props = defineProps<Props>();
const page = usePage<EtmsSharedProps>();

const currentUserId = computed(() => page.props.auth?.user?.id ?? null);
const validationErrors = computed<Record<string, string>>(
    () => (page.props.errors as Record<string, string>) ?? {},
);

const filters = reactive({
    q: props.filters.q ?? '',
    status: props.filters.status ?? '',
    role: props.filters.role ?? '',
});

const hasActiveFilters = computed(() => Boolean(filters.q || filters.status || filters.role));

const columns: DataTableColumn[] = [
    { key: 'user', label: 'User' },
    { key: 'roles', label: 'Roles' },
    { key: 'status', label: 'Status' },
    { key: 'actions', label: 'Actions', align: 'right' },
];

function apply(): void {
    router.get(
        '/admin-vue/users',
        {
            ...(filters.q ? { q: filters.q } : {}),
            ...(filters.status ? { status: filters.status } : {}),
            ...(filters.role ? { role: filters.role } : {}),
        },
        { preserveState: true, replace: true },
    );
}

function setRoleFilter(slug: string): void {
    filters.role = slug;
    apply();
}

function clearFilters(): void {
    filters.q = '';
    filters.status = '';
    filters.role = '';
    apply();
}

function statusTone(statusValue: string): 'success' | 'warning' | 'danger' | 'neutral' {
    if (statusValue === 'active') return 'success';
    if (statusValue === 'suspended') return 'warning';
    if (statusValue === 'disabled' || statusValue === 'deactivated') return 'danger';
    return 'neutral';
}

function roleBadgeClass(roleLabel: string): string {
    const r = roleLabel.toLowerCase();
    if (r.includes('admin')) return 'bg-navy-950 text-white';
    if (r.includes('instructor')) return 'bg-accent-600/12 text-accent-700';
    return 'bg-sand-100 text-ink';
}

/* Create / Edit Modal */
const modalOpen = ref(false);
const editing = ref<UserRow | null>(null);
const busy = ref(false);
const showPassword = ref(false);
const photoPreview = ref<string | null>(null);
const photoInput = ref<HTMLInputElement | null>(null);

const form = reactive({
    name: '',
    email: '',
    password: '',
    status: 'active',
    roles: [] as string[],
    profile_phone: '',
    profile_job_title: '',
    profile_billing_contact_name: '',
    profile_billing_email: '',
    profile_billing_phone: '',
    profile_billing_address_line_1: '',
    profile_billing_address_line_2: '',
    profile_billing_city: '',
    profile_billing_state: '',
    profile_billing_postcode: '',
    profile_billing_country: 'MY',
    instructor_professional_title: '',
    instructor_qualifications: '',
    instructor_short_bio: '',
    instructor_biography: '',
    instructor_specialisations: '',
    instructor_is_public: false,
    photo: null as File | null,
});

const isCustomer = computed(() => form.roles.includes('customer'));
const isInstructor = computed(() => form.roles.includes('instructor'));

function beginCreate(): void {
    editing.value = null;
    showPassword.value = false;
    Object.assign(form, {
        name: '',
        email: '',
        password: '',
        status: 'active',
        roles: ['customer'],
        profile_phone: '',
        profile_job_title: '',
        profile_billing_contact_name: '',
        profile_billing_email: '',
        profile_billing_phone: '',
        profile_billing_address_line_1: '',
        profile_billing_address_line_2: '',
        profile_billing_city: '',
        profile_billing_state: '',
        profile_billing_postcode: '',
        profile_billing_country: 'MY',
        instructor_professional_title: '',
        instructor_qualifications: '',
        instructor_short_bio: '',
        instructor_biography: '',
        instructor_specialisations: '',
        instructor_is_public: false,
        photo: null,
    });
    photoPreview.value = null;
    modalOpen.value = true;
}

function beginEdit(user: UserRow): void {
    editing.value = user;
    showPassword.value = false;
    Object.assign(form, {
        name: user.name,
        email: user.email,
        password: '',
        status: user.statusValue,
        roles: [...user.roles],
        profile_phone: user.profile_phone ?? '',
        profile_job_title: user.profile_job_title ?? '',
        profile_billing_contact_name: user.profile_billing_contact_name ?? '',
        profile_billing_email: user.profile_billing_email ?? '',
        profile_billing_phone: user.profile_billing_phone ?? '',
        profile_billing_address_line_1: user.profile_billing_address_line_1 ?? '',
        profile_billing_address_line_2: user.profile_billing_address_line_2 ?? '',
        profile_billing_city: user.profile_billing_city ?? '',
        profile_billing_state: user.profile_billing_state ?? '',
        profile_billing_postcode: user.profile_billing_postcode ?? '',
        profile_billing_country: user.profile_billing_country ?? 'MY',
        instructor_professional_title: user.instructor_professional_title ?? '',
        instructor_qualifications: user.instructor_qualifications ?? '',
        instructor_short_bio: user.instructor_short_bio ?? '',
        instructor_biography: user.instructor_biography ?? '',
        instructor_specialisations: (user.instructor_specialisations ?? []).join(', '),
        instructor_is_public: user.instructor_is_public ?? false,
        photo: null,
    });
    photoPreview.value = user.photoUrl;
    modalOpen.value = true;
}

function handlePhoto(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.photo = file;
    photoPreview.value = file ? URL.createObjectURL(file) : (editing.value?.photoUrl ?? null);
}

function save(): void {
    if (form.roles.length === 0) return;
    busy.value = true;
    const data = {
        ...form,
        instructor_specialisations: form.instructor_specialisations
            .split(',')
            .map((value) => value.trim())
            .filter(Boolean),
        photo: form.photo ?? undefined,
    };

    if (editing.value) {
        router.post(
            `/admin-vue/users/${editing.value.id}`,
            { ...data, _method: 'put' },
            {
                forceFormData: true,
                preserveScroll: true,
                onFinish: () => {
                    busy.value = false;
                },
                onSuccess: () => {
                    modalOpen.value = false;
                },
            },
        );
        return;
    }

    router.post('/admin-vue/users', data, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => {
            busy.value = false;
        },
        onSuccess: () => {
            modalOpen.value = false;
        },
    });
}

/* Change Status Modal */
const statusAction = ref<UserRow | null>(null);
const statusForm = reactive({ status: 'active', status_reason: '' });

function beginStatus(user: UserRow): void {
    statusAction.value = user;
    statusForm.status = user.statusValue;
    statusForm.status_reason = '';
}

function submitStatus(): void {
    if (!statusAction.value || statusForm.status_reason.trim().length < 5) return;
    busy.value = true;
    router.post(`/admin-vue/users/${statusAction.value.id}/status`, statusForm, {
        preserveScroll: true,
        onFinish: () => {
            busy.value = false;
            statusAction.value = null;
        },
    });
}

/* Delete Modal */
const deleting = ref<UserRow | null>(null);
function destroyUser(): void {
    if (!deleting.value) return;
    busy.value = true;
    router.delete(`/admin-vue/users/${deleting.value.id}`, {
        preserveScroll: true,
        onFinish: () => {
            busy.value = false;
            deleting.value = null;
        },
    });
}

function cleanPaginationLabel(label: string): string {
    return label.replace(/&laquo;/g, '←').replace(/&raquo;/g, '→');
}
</script>

<template>
    <Head title="Users & Roles — Admin" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Page Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="type-page-title text-ink">Users &amp; Roles</h1>
                    <p class="mt-1 text-sm text-muted">
                        Manage administrator, instructor, and trainee accounts across the platform.
                    </p>
                </div>

                <button
                    type="button"
                    class="inline-flex h-10 items-center gap-2 rounded-xl bg-navy-950 px-4 text-sm font-semibold text-white transition hover:bg-navy-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
                    @click="beginCreate"
                >
                    <Plus class="size-4" aria-hidden="true" />
                    <span>Create user</span>
                </button>
            </div>

            <!-- Filter Bar -->
            <section class="rounded-2xl border border-line/85 bg-surface p-4 shadow-card">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button
                            type="button"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                            :class="
                                !filters.role
                                    ? 'bg-navy-950 text-white'
                                    : 'bg-sand-100 text-muted hover:text-ink'
                            "
                            @click="setRoleFilter('')"
                        >
                            All roles
                        </button>
                        <button
                            v-for="role in roles"
                            :key="role.value"
                            type="button"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                            :class="
                                filters.role === role.value
                                    ? 'bg-navy-950 text-white'
                                    : 'bg-sand-100 text-muted hover:text-ink'
                            "
                            @click="setRoleFilter(role.value)"
                        >
                            {{ role.label }}
                        </button>
                    </div>

                    <form class="flex flex-wrap items-center gap-2" @submit.prevent="apply">
                        <label class="relative min-w-[210px] flex-1 sm:flex-initial">
                            <Search
                                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted"
                                aria-hidden="true"
                            />
                            <input
                                v-model="filters.q"
                                type="search"
                                placeholder="Search name or email..."
                                class="h-9 w-full rounded-lg border border-line bg-surface pr-3 pl-9 text-sm text-ink placeholder:text-muted/70 focus:border-primary-600 focus:outline-none"
                            />
                        </label>

                        <select
                            v-model="filters.status"
                            class="h-9 rounded-lg border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                            @change="apply"
                        >
                            <option value="">All statuses</option>
                            <option v-for="s in statuses" :key="s.value" :value="s.value">
                                {{ s.label }}
                            </option>
                        </select>

                        <button
                            type="submit"
                            class="inline-flex h-9 items-center rounded-lg bg-navy-950 px-3.5 text-sm font-medium text-white transition hover:bg-navy-900"
                        >
                            Search
                        </button>

                        <button
                            v-if="hasActiveFilters"
                            type="button"
                            class="inline-flex h-9 items-center gap-1.5 rounded-lg border border-line bg-surface px-3 text-sm font-medium text-muted transition hover:bg-sand-100 hover:text-ink"
                            @click="clearFilters"
                        >
                            <RotateCcw class="size-3.5" aria-hidden="true" />
                            <span>Reset</span>
                        </button>
                    </form>
                </div>
            </section>

            <!-- Users Table -->
            <DataTable :columns="columns" :rows="users.data" row-key="id">
                <template #cell-user="{ row }">
                    <div class="flex items-center gap-3">
                        <UserAvatar
                            :name="(row as UserRow).name"
                            :src="(row as UserRow).photoUrl"
                            size="sm"
                        />
                        <div class="min-w-0">
                            <p class="truncate font-medium text-ink">
                                {{ (row as UserRow).name }}
                                <span
                                    v-if="(row as UserRow).id === currentUserId"
                                    class="ml-1.5 rounded-md bg-sand-100 px-1.5 py-0.5 text-[10px] font-semibold text-muted"
                                >
                                    You
                                </span>
                            </p>
                            <p class="truncate text-xs text-muted">{{ (row as UserRow).email }}</p>
                        </div>
                    </div>
                </template>

                <template #cell-roles="{ row }">
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="label in (row as UserRow).roleLabels"
                            :key="label"
                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                            :class="roleBadgeClass(label)"
                        >
                            {{ label }}
                        </span>
                    </div>
                </template>

                <template #cell-status="{ row }">
                    <StatusBadge
                        :tone="statusTone((row as UserRow).statusValue)"
                        :label="(row as UserRow).status"
                    />
                </template>

                <template #cell-actions="{ row }">
                    <div class="flex items-center justify-end gap-1.5">
                        <button
                            type="button"
                            class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-line bg-surface px-2.5 text-xs font-medium text-ink transition hover:bg-sand-100"
                            @click="beginEdit(row as UserRow)"
                        >
                            <Pencil class="size-3.5 text-muted" aria-hidden="true" />
                            <span>Edit</span>
                        </button>
                        <button
                            v-if="(row as UserRow).id !== currentUserId"
                            type="button"
                            class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-line bg-surface px-2.5 text-xs font-medium text-ink transition hover:bg-sand-100"
                            @click="beginStatus(row as UserRow)"
                        >
                            <ShieldAlert class="size-3.5 text-muted" aria-hidden="true" />
                            <span>Status</span>
                        </button>
                        <button
                            v-if="(row as UserRow).id !== currentUserId"
                            type="button"
                            aria-label="Delete user"
                            class="inline-flex size-8 items-center justify-center rounded-lg border border-line bg-surface text-muted transition hover:border-danger-200 hover:bg-danger-50 hover:text-danger-600"
                            @click="deleting = row as UserRow"
                        >
                            <Trash2 class="size-3.5" aria-hidden="true" />
                        </button>
                    </div>
                </template>

                <template #empty>
                    <div class="py-10 text-center">
                        <Users class="mx-auto size-8 text-muted/60" aria-hidden="true" />
                        <p class="mt-2 text-sm font-medium text-ink">No users found</p>
                        <p class="mt-0.5 text-xs text-muted">
                            Try adjusting your search or role filters, or create a new user.
                        </p>
                    </div>
                </template>
            </DataTable>

            <!-- Pagination -->
            <div
                v-if="users.total > 0"
                class="flex flex-col items-center justify-between gap-3 text-xs text-muted sm:flex-row"
            >
                <p>
                    Showing <strong class="font-medium text-ink">{{ users.from ?? 0 }}</strong> to
                    <strong class="font-medium text-ink">{{ users.to ?? 0 }}</strong> of
                    <strong class="font-medium text-ink">{{ users.total }}</strong> users
                </p>

                <nav v-if="users.links.length > 3" class="flex flex-wrap items-center gap-1">
                    <template v-for="(link, idx) in users.links" :key="idx">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            preserve-scroll
                            class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 font-medium transition"
                            :class="
                                link.active
                                    ? 'bg-navy-950 text-white'
                                    : 'border border-line bg-surface text-ink hover:bg-sand-100'
                            "
                        >
                            {{ cleanPaginationLabel(link.label) }}
                        </Link>
                        <span
                            v-else
                            class="inline-flex h-8 min-w-8 items-center justify-center rounded-lg px-2.5 text-muted/50"
                        >
                            {{ cleanPaginationLabel(link.label) }}
                        </span>
                    </template>
                </nav>
            </div>
        </div>

        <!-- Create / Edit User Dialog -->
        <DialogRoot :open="modalOpen" @update:open="modalOpen = $event">
            <DialogPortal>
                <DialogOverlay class="fixed inset-0 z-50 bg-navy-950/45 backdrop-blur-[2px]" />
                <DialogContent
                    class="fixed top-1/2 left-1/2 z-50 max-h-[90vh] w-[94vw] max-w-2xl -translate-x-1/2 -translate-y-1/2 overflow-y-auto rounded-2xl border border-line bg-surface p-6 shadow-elevated focus:outline-none"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <DialogTitle class="text-lg font-semibold text-ink">
                                {{ editing ? 'Edit user' : 'Create user' }}
                            </DialogTitle>
                            <DialogDescription class="mt-1 text-sm text-muted">
                                {{
                                    editing
                                        ? 'Update account details, assigned roles, and profile information.'
                                        : 'Create a new administrator, instructor, or trainee account.'
                                }}
                            </DialogDescription>
                        </div>
                        <DialogClose
                            class="inline-flex size-8 items-center justify-center rounded-lg text-muted transition hover:bg-sand-100 hover:text-ink"
                            aria-label="Close"
                        >
                            <X class="size-4" />
                        </DialogClose>
                    </div>

                    <div
                        v-if="Object.keys(validationErrors).length"
                        class="mt-4 rounded-xl border border-danger-200 bg-danger-50 px-3.5 py-2.5 text-xs text-danger-700"
                    >
                        <p v-for="(msg, key) in validationErrors" :key="key">{{ msg }}</p>
                    </div>

                    <form class="mt-5 space-y-5" @submit.prevent="save">
                        <!-- Avatar Uploader -->
                        <div class="flex items-center gap-4">
                            <button
                                type="button"
                                class="group relative size-16 shrink-0 overflow-hidden rounded-full border border-line bg-sand-100"
                                @click="photoInput?.click()"
                            >
                                <img
                                    v-if="photoPreview"
                                    :src="photoPreview"
                                    alt="User avatar"
                                    class="size-full object-cover"
                                />
                                <span
                                    v-else
                                    class="grid size-full place-items-center text-lg font-semibold text-navy-950"
                                >
                                    {{ (form.name || 'U').slice(0, 1).toUpperCase() }}
                                </span>
                                <span
                                    class="absolute inset-0 flex items-center justify-center bg-navy-950/50 text-white opacity-0 transition group-hover:opacity-100"
                                >
                                    <Camera class="size-4" />
                                </span>
                            </button>
                            <div>
                                <p class="text-sm font-medium text-ink">Profile photo</p>
                                <p class="text-xs text-muted">Optional JPG or PNG, up to 5 MB.</p>
                                <button
                                    type="button"
                                    class="mt-1 text-xs font-medium text-primary-600 hover:underline"
                                    @click="photoInput?.click()"
                                >
                                    {{ photoPreview ? 'Change photo' : 'Upload photo' }}
                                </button>
                                <input
                                    ref="photoInput"
                                    type="file"
                                    accept="image/*"
                                    class="hidden"
                                    @change="handlePhoto"
                                />
                            </div>
                        </div>

                        <!-- Account Details -->
                        <section class="grid gap-4 sm:grid-cols-2">
                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>Full name</span>
                                <input
                                    v-model="form.name"
                                    required
                                    maxlength="150"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                            </label>

                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>Email address</span>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    required
                                    maxlength="255"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                            </label>

                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>{{ editing ? 'New password (optional)' : 'Password' }}</span>
                                <div class="relative">
                                    <input
                                        v-model="form.password"
                                        :type="showPassword ? 'text' : 'password'"
                                        :required="!editing"
                                        minlength="8"
                                        class="h-10 w-full rounded-xl border border-line bg-surface pr-10 pl-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                    />
                                    <button
                                        type="button"
                                        class="absolute top-1/2 right-2.5 -translate-y-1/2 text-muted hover:text-ink"
                                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                        @click="showPassword = !showPassword"
                                    >
                                        <EyeOff v-if="showPassword" class="size-4" />
                                        <Eye v-else class="size-4" />
                                    </button>
                                </div>
                            </label>

                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>Account status</span>
                                <select
                                    v-model="form.status"
                                    required
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                >
                                    <option v-for="s in statuses" :key="s.value" :value="s.value">
                                        {{ s.label }}
                                    </option>
                                </select>
                            </label>
                        </section>

                        <!-- Roles -->
                        <section class="rounded-xl border border-line bg-sand-50/60 p-4">
                            <p class="text-xs font-semibold text-ink">Assigned roles</p>
                            <p class="mt-0.5 text-xs text-muted">Select at least one role for this account.</p>
                            <div class="mt-3 grid gap-2.5 sm:grid-cols-3">
                                <label
                                    v-for="role in roles"
                                    :key="role.value"
                                    class="flex cursor-pointer items-center gap-2.5 rounded-xl border bg-surface px-3 py-2.5 text-sm font-medium transition"
                                    :class="
                                        form.roles.includes(role.value)
                                            ? 'border-navy-950 text-navy-950'
                                            : 'border-line text-muted hover:border-line hover:text-ink'
                                    "
                                >
                                    <input
                                        v-model="form.roles"
                                        type="checkbox"
                                        :value="role.value"
                                        class="size-4 rounded accent-navy-950"
                                    />
                                    <span>{{ role.label }}</span>
                                </label>
                            </div>
                        </section>

                        <!-- Instructor Profile Fields -->
                        <section
                            v-if="isInstructor"
                            class="grid gap-4 rounded-xl border border-line p-4 sm:grid-cols-2"
                        >
                            <h3 class="text-sm font-semibold text-ink sm:col-span-2">Instructor profile</h3>
                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>Professional title</span>
                                <input
                                    v-model="form.instructor_professional_title"
                                    maxlength="150"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                            </label>
                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>Qualifications</span>
                                <input
                                    v-model="form.instructor_qualifications"
                                    maxlength="255"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                            </label>
                            <label class="flex flex-col gap-1 text-xs font-medium text-muted sm:col-span-2">
                                <span>Short summary</span>
                                <textarea
                                    v-model="form.instructor_short_bio"
                                    rows="2"
                                    maxlength="500"
                                    class="rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                            </label>
                            <label class="flex flex-col gap-1 text-xs font-medium text-muted sm:col-span-2">
                                <span>Biography</span>
                                <textarea
                                    v-model="form.instructor_biography"
                                    rows="3"
                                    class="rounded-xl border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                            </label>
                            <label class="flex flex-col gap-1 text-xs font-medium text-muted sm:col-span-2">
                                <span>Specialisations (comma separated)</span>
                                <input
                                    v-model="form.instructor_specialisations"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                            </label>
                            <label class="flex items-center gap-2 text-sm text-ink sm:col-span-2">
                                <input
                                    v-model="form.instructor_is_public"
                                    type="checkbox"
                                    class="size-4 rounded accent-navy-950"
                                />
                                <span>Show in public instructor directory</span>
                            </label>
                        </section>

                        <!-- Trainee / Customer Profile Fields -->
                        <section
                            v-if="isCustomer"
                            class="grid gap-4 rounded-xl border border-line p-4 sm:grid-cols-2"
                        >
                            <h3 class="text-sm font-semibold text-ink sm:col-span-2">Trainee &amp; billing profile</h3>
                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>Phone</span>
                                <input
                                    v-model="form.profile_phone"
                                    maxlength="30"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                            </label>
                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>Job title</span>
                                <input
                                    v-model="form.profile_job_title"
                                    maxlength="120"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                            </label>
                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>Billing contact</span>
                                <input
                                    v-model="form.profile_billing_contact_name"
                                    maxlength="150"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                            </label>
                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>Billing email</span>
                                <input
                                    v-model="form.profile_billing_email"
                                    type="email"
                                    maxlength="255"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                            </label>
                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>Billing phone</span>
                                <input
                                    v-model="form.profile_billing_phone"
                                    maxlength="30"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                            </label>
                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>Country (ISO-2)</span>
                                <input
                                    v-model="form.profile_billing_country"
                                    maxlength="2"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink uppercase focus:border-primary-600 focus:outline-none"
                                />
                            </label>
                            <label class="flex flex-col gap-1 text-xs font-medium text-muted sm:col-span-2">
                                <span>Address line 1</span>
                                <input
                                    v-model="form.profile_billing_address_line_1"
                                    maxlength="255"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                            </label>
                            <label class="flex flex-col gap-1 text-xs font-medium text-muted sm:col-span-2">
                                <span>Address line 2</span>
                                <input
                                    v-model="form.profile_billing_address_line_2"
                                    maxlength="255"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                            </label>
                            <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                <span>City</span>
                                <input
                                    v-model="form.profile_billing_city"
                                    maxlength="100"
                                    class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                />
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                    <span>State</span>
                                    <input
                                        v-model="form.profile_billing_state"
                                        maxlength="100"
                                        class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                    />
                                </label>
                                <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                                    <span>Postcode</span>
                                    <input
                                        v-model="form.profile_billing_postcode"
                                        maxlength="20"
                                        class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                                    />
                                </label>
                            </div>
                        </section>

                        <div class="flex justify-end gap-2 border-t border-line pt-4">
                            <button
                                type="button"
                                class="h-10 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink transition hover:bg-sand-100"
                                @click="modalOpen = false"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="busy || form.roles.length === 0"
                                class="h-10 rounded-xl bg-navy-950 px-4 text-sm font-semibold text-white transition hover:bg-navy-900 disabled:opacity-50"
                            >
                                {{ busy ? 'Saving…' : editing ? 'Save changes' : 'Create user' }}
                            </button>
                        </div>
                    </form>
                </DialogContent>
            </DialogPortal>
        </DialogRoot>

        <!-- Change Account Status Dialog -->
        <DialogRoot :open="Boolean(statusAction)" @update:open="(v) => !v && (statusAction = null)">
            <DialogPortal>
                <DialogOverlay class="fixed inset-0 z-50 bg-navy-950/45 backdrop-blur-[2px]" />
                <DialogContent
                    class="fixed top-1/2 left-1/2 z-50 w-[92vw] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-line bg-surface p-6 shadow-elevated focus:outline-none"
                >
                    <DialogTitle class="text-lg font-semibold text-ink">Change account status</DialogTitle>
                    <DialogDescription class="mt-1 text-sm text-muted">
                        {{ statusAction?.name }} · {{ statusAction?.email }}
                    </DialogDescription>

                    <div class="mt-4 space-y-3">
                        <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                            <span>New status</span>
                            <select
                                v-model="statusForm.status"
                                class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                            >
                                <option v-for="s in statuses" :key="s.value" :value="s.value">
                                    {{ s.label }}
                                </option>
                            </select>
                        </label>

                        <label class="flex flex-col gap-1 text-xs font-medium text-muted">
                            <span>Audit reason (min 5 characters)</span>
                            <textarea
                                v-model="statusForm.status_reason"
                                rows="3"
                                maxlength="500"
                                placeholder="Explain why this account status is being updated..."
                                class="rounded-xl border border-line bg-surface p-3 text-sm text-ink focus:border-primary-600 focus:outline-none"
                            />
                        </label>
                    </div>

                    <div class="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            class="h-10 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink transition hover:bg-sand-100"
                            @click="statusAction = null"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="busy || statusForm.status_reason.trim().length < 5"
                            class="h-10 rounded-xl bg-navy-950 px-4 text-sm font-semibold text-white transition hover:bg-navy-900 disabled:opacity-50"
                            @click="submitStatus"
                        >
                            {{ busy ? 'Updating…' : 'Save status' }}
                        </button>
                    </div>
                </DialogContent>
            </DialogPortal>
        </DialogRoot>

        <!-- Delete Confirmation Dialog -->
        <DialogRoot :open="Boolean(deleting)" @update:open="(v) => !v && (deleting = null)">
            <DialogPortal>
                <DialogOverlay class="fixed inset-0 z-50 bg-navy-950/45 backdrop-blur-[2px]" />
                <DialogContent
                    class="fixed top-1/2 left-1/2 z-50 w-[92vw] max-w-md -translate-x-1/2 -translate-y-1/2 rounded-2xl border border-line bg-surface p-6 shadow-elevated focus:outline-none"
                >
                    <DialogTitle class="text-lg font-semibold text-ink">Delete user</DialogTitle>
                    <DialogDescription class="mt-1 text-sm text-muted">
                        {{ deleting?.name }} · {{ deleting?.email }}
                    </DialogDescription>
                    <p class="mt-3 rounded-xl border border-danger-200 bg-danger-50 px-3.5 py-2.5 text-xs text-danger-700">
                        This action removes the user account. Protected administrators or users with linked training records cannot be deleted.
                    </p>

                    <div class="mt-5 flex justify-end gap-2">
                        <button
                            type="button"
                            class="h-10 rounded-xl border border-line bg-surface px-4 text-sm font-medium text-ink transition hover:bg-sand-100"
                            @click="deleting = null"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="busy"
                            class="h-10 rounded-xl bg-danger-600 px-4 text-sm font-semibold text-white transition hover:bg-danger-700 disabled:opacity-50"
                            @click="destroyUser"
                        >
                            {{ busy ? 'Deleting…' : 'Delete user' }}
                        </button>
                    </div>
                </DialogContent>
            </DialogPortal>
        </DialogRoot>
    </AdminLayout>
</template>
