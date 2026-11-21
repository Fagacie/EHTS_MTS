<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Plus, Search, Users, X } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import type { EtmsSharedProps } from '@/types/etms';

interface UserRow {
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

interface Props extends EtmsSharedProps {
    users: { data: UserRow[]; from: number | null; to: number | null; total: number; links: { url: string | null; label: string; active: boolean }[] };
    filters: { q: string; status: string };
    statuses: { value: string; label: string }[];
    roles: { value: string; label: string }[];
}

const props = defineProps<Props>();
const filters = reactive({ ...props.filters });
const modalOpen = ref(false);
const editing = ref<UserRow | null>(null);
const busy = ref(false);
const photoPreview = ref<string | null>(null);
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

function apply(): void {
    router.get('/admin-vue/users', filters, { preserveState: true, replace: true });
}

function clear(): void {
    filters.q = '';
    filters.status = '';
    apply();
}

function beginCreate(): void {
    editing.value = null;
    Object.assign(form, {
        name: '', email: '', password: '', status: 'active', roles: [],
        profile_phone: '', profile_job_title: '',
        profile_billing_contact_name: '', profile_billing_email: '', profile_billing_phone: '',
        profile_billing_address_line_1: '', profile_billing_address_line_2: '',
        profile_billing_city: '', profile_billing_state: '', profile_billing_postcode: '',
        profile_billing_country: 'MY',
        instructor_professional_title: '', instructor_qualifications: '',
        instructor_short_bio: '', instructor_biography: '', instructor_specialisations: '',
        instructor_is_public: false,
        photo: null,
    });
    photoPreview.value = null;
    modalOpen.value = true;
}

function beginEdit(user: UserRow): void {
    editing.value = user;
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
    form.photo = (event.target as HTMLInputElement).files?.[0] ?? null;
    photoPreview.value = form.photo ? URL.createObjectURL(form.photo) : editing.value?.photoUrl ?? null;
}

function save(): void {
    if (form.roles.length === 0) return;
    busy.value = true;
    const data = {
        ...form,
        instructor_specialisations: form.instructor_specialisations.split(',').map((value) => value.trim()).filter(Boolean),
        photo: form.photo ?? undefined,
    };

    if (editing.value) {
        router.put(`/admin-vue/users/${editing.value.id}`, data, {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => { busy.value = false; },
            onSuccess: () => { modalOpen.value = false; },
        });
        return;
    }

    router.post('/admin-vue/users', data, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => { busy.value = false; },
        onSuccess: () => { modalOpen.value = false; },
    });
}

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
        onFinish: () => { busy.value = false; statusAction.value = null; },
    });
}

const deleting = ref<UserRow | null>(null);
function destroyUser(): void {
    if (!deleting.value) return;
    router.delete(`/admin-vue/users/${deleting.value.id}`, {
        preserveScroll: true,
        onFinish: () => { deleting.value = null; },
    });
}
</script>

<template>
<AdminLayout>
    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div><p class="text-sm font-medium text-[#0F4C81]">Administration</p><h1 class="mt-1 text-2xl font-semibold">Users and roles</h1><p class="mt-1 text-sm text-slate-500">Create and manage administrator, instructor and customer accounts.</p></div>
        <button type="button" class="inline-flex h-10 items-center gap-2 rounded-md bg-[#0F4C81] px-4 text-sm font-medium text-white" @click="beginCreate"><Plus :size="16"/>Create user</button>
    </div>

    <Card><CardContent class="p-4"><form class="grid gap-3 md:grid-cols-[minmax(0,1fr)_14rem_auto_auto]" @submit.prevent="apply"><label class="relative"><Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="16"/><input v-model="filters.q" class="h-10 w-full rounded-md border pl-9 pr-3 text-sm" placeholder="Name or email"/></label><select v-model="filters.status" class="h-10 rounded-md border px-3 text-sm"><option value="">All statuses</option><option v-for="status in statuses" :key="status.value" :value="status.value">{{status.label}}</option></select><button class="h-10 rounded-md bg-slate-900 px-4 text-sm text-white">Apply</button><button type="button" class="h-10 rounded-md border px-3 text-sm" @click="clear"><X :size="14" class="mr-1 inline"/>Clear</button></form></CardContent></Card>

    <Card class="mt-6 overflow-hidden"><div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="border-b bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">User</th><th class="px-4 py-3">Roles</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Security</th><th class="px-4 py-3 text-right">Actions</th></tr></thead><tbody class="divide-y"><tr v-for="user in users.data" :key="user.id">    <td class="px-4 py-4"><div class="flex items-center gap-3"><img v-if="user.photoUrl" :src="user.photoUrl" alt="" class="h-9 w-9 rounded-full object-cover"/><span v-else class="grid h-9 w-9 place-items-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">{{user.name.slice(0,1).toUpperCase()}}</span><div><p class="font-medium">{{user.name}}</p><p class="text-xs text-slate-500">{{user.email}}</p></div></div></td><td class="px-4 py-4"><span v-for="role in user.roleLabels" :key="role" class="mr-1 rounded-full bg-slate-100 px-2 py-1 text-xs">{{role}}</span></td><td class="px-4 py-4">{{user.status}}</td><td class="px-4 py-4 text-xs text-slate-600">Email {{user.verified?'verified':'unverified'}}</td><td class="px-4 py-4"><div class="flex justify-end gap-2"><button type="button" class="rounded-md border border-amber-300 px-3 py-1.5 text-xs text-amber-800" @click="beginStatus(user)">Change status</button><button type="button" class="rounded-md border px-3 py-1.5 text-xs text-[#0F4C81]" @click="beginEdit(user)">Edit</button><button type="button" class="rounded-md border border-red-300 px-3 py-1.5 text-xs text-red-700" @click="deleting=user">Delete</button></div></td></tr><tr v-if="!users.data.length"><td colspan="5" class="p-12 text-center text-slate-500"><Users class="mx-auto mb-2" :size="24"/>No users found.</td></tr></tbody></table></div></Card>

    <div v-if="modalOpen" class="fixed inset-0 z-50 grid place-items-center bg-slate-900/40 p-4" role="dialog" aria-modal="true">
        <div class="max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-lg bg-white p-6 shadow-xl">
            <div class="flex items-start justify-between gap-4"><div><h2 class="text-lg font-semibold">{{editing?'Edit user':'Create user'}}</h2><p class="mt-1 text-sm text-slate-500">{{editing?'Update account details and role-specific information.':'Create an account and enter only the fields required by the selected role.'}}</p></div><button type="button" class="grid h-9 w-9 place-items-center rounded-md text-slate-500 hover:bg-slate-100" aria-label="Close" @click="modalOpen=false"><X :size="18"/></button></div>
            <form class="mt-5 space-y-5" @submit.prevent="save">
                <section class="grid gap-4 md:grid-cols-2">
                    <label class="text-sm">Full name<input v-model="form.name" required maxlength="150" class="mt-1 h-10 w-full rounded-md border px-3"/></label>
                    <label class="text-sm">Email address<input v-model="form.email" type="email" required maxlength="255" class="mt-1 h-10 w-full rounded-md border px-3"/></label>
                    <label class="text-sm">{{editing?'New password (leave blank to keep)':'Password'}}<input v-model="form.password" type="password" :required="!editing" minlength="8" class="mt-1 h-10 w-full rounded-md border px-3"/></label>
                    <label class="text-sm">Account status<select v-model="form.status" required class="mt-1 h-10 w-full rounded-md border px-3"><option v-for="status in statuses" :key="status.value" :value="status.value">{{status.label}}</option></select></label>
                    <div class="md:col-span-2 flex items-center gap-4"><img v-if="photoPreview" :src="photoPreview" alt="User photo" class="h-20 w-20 rounded-full object-cover border"/><span v-else class="grid h-20 w-20 place-items-center rounded-full bg-slate-100 text-xl font-bold text-slate-600">{{(form.name||'U').slice(0,1).toUpperCase()}}</span><label class="text-sm">Profile image<input type="file" accept="image/*" class="mt-1 block w-full text-sm" @change="handlePhoto"/></label></div>
                </section>
                <section class="rounded-lg border p-4"><h3 class="text-sm font-semibold">Roles</h3><div class="mt-3 grid gap-3 sm:grid-cols-3"><label v-for="role in roles" :key="role.value" class="flex items-center gap-2 text-sm"><input v-model="form.roles" type="checkbox" :value="role.value"/>{{role.label}}</label></div></section>

                <section v-if="isInstructor" class="grid gap-4 rounded-lg border p-4 md:grid-cols-2"><h3 class="text-sm font-semibold md:col-span-2">Instructor profile</h3><label class="text-sm">Professional title<input v-model="form.instructor_professional_title" maxlength="150" class="mt-1 h-10 w-full rounded-md border px-3"/></label><label class="text-sm">Qualifications<input v-model="form.instructor_qualifications" maxlength="255" class="mt-1 h-10 w-full rounded-md border px-3"/></label><label class="text-sm md:col-span-2">Short summary<textarea v-model="form.instructor_short_bio" rows="2" maxlength="500" class="mt-1 w-full rounded-md border px-3 py-2"/></label><label class="text-sm md:col-span-2">Biography<textarea v-model="form.instructor_biography" rows="4" class="mt-1 w-full rounded-md border px-3 py-2"/></label><label class="text-sm md:col-span-2">Specialisations <span class="text-xs text-slate-500">(comma separated)</span><input v-model="form.instructor_specialisations" class="mt-1 h-10 w-full rounded-md border px-3"/></label><label class="flex items-center gap-2 text-sm md:col-span-2"><input v-model="form.instructor_is_public" type="checkbox"/>Publish in public instructor directory</label></section>

                <section v-if="isCustomer" class="grid gap-4 rounded-lg border p-4 md:grid-cols-2"><h3 class="text-sm font-semibold md:col-span-2">Trainee profile</h3><label class="text-sm">Phone<input v-model="form.profile_phone" maxlength="30" class="mt-1 h-10 w-full rounded-md border px-3"/></label><label class="text-sm">Job title<input v-model="form.profile_job_title" maxlength="120" class="mt-1 h-10 w-full rounded-md border px-3"/></label><label class="text-sm">Billing contact<input v-model="form.profile_billing_contact_name" maxlength="150" class="mt-1 h-10 w-full rounded-md border px-3"/></label><label class="text-sm">Billing email<input v-model="form.profile_billing_email" type="email" maxlength="255" class="mt-1 h-10 w-full rounded-md border px-3"/></label><label class="text-sm">Billing phone<input v-model="form.profile_billing_phone" maxlength="30" class="mt-1 h-10 w-full rounded-md border px-3"/></label><label class="text-sm md:col-span-2">Address line 1<input v-model="form.profile_billing_address_line_1" maxlength="255" class="mt-1 h-10 w-full rounded-md border px-3"/></label><label class="text-sm md:col-span-2">Address line 2<input v-model="form.profile_billing_address_line_2" maxlength="255" class="mt-1 h-10 w-full rounded-md border px-3"/></label><label class="text-sm">City<input v-model="form.profile_billing_city" maxlength="100" class="mt-1 h-10 w-full rounded-md border px-3"/></label><label class="text-sm">State<input v-model="form.profile_billing_state" maxlength="100" class="mt-1 h-10 w-full rounded-md border px-3"/></label><label class="text-sm">Postcode<input v-model="form.profile_billing_postcode" maxlength="20" class="mt-1 h-10 w-full rounded-md border px-3"/></label><label class="text-sm">Country (ISO-2)<input v-model="form.profile_billing_country" maxlength="2" class="mt-1 h-10 w-full rounded-md border px-3"/></label></section>

                <div class="flex justify-end gap-2"><button type="button" class="h-10 rounded-md border px-4 text-sm" @click="modalOpen=false">Cancel</button><button type="submit" :disabled="busy||form.roles.length===0" class="h-10 rounded-md bg-[#0F4C81] px-4 text-sm text-white">{{busy?'Saving…':editing?'Save user':'Create user'}}</button></div>
            </form>
        </div>
    </div>

    <div v-if="statusAction" class="fixed inset-0 z-50 grid place-items-center bg-slate-900/40 p-4"><div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl"><h2 class="text-lg font-semibold">Change account status</h2><p class="mt-1 text-sm text-slate-500">{{statusAction.name}} · {{statusAction.email}}</p><select v-model="statusForm.status" class="mt-4 h-10 w-full rounded-md border px-3 text-sm"><option v-for="status in statuses" :key="status.value" :value="status.value">{{status.label}}</option></select><textarea v-model="statusForm.status_reason" class="mt-3 min-h-24 w-full rounded-md border p-3 text-sm" placeholder="Mandatory audit reason" maxlength="500"/><div class="mt-4 flex justify-end gap-2"><button type="button" class="h-10 rounded-md border px-4 text-sm" @click="statusAction=null">Cancel</button><button type="button" :disabled="busy||statusForm.status_reason.trim().length<5" class="h-10 rounded-md bg-[#0F4C81] px-4 text-sm text-white" @click="submitStatus">Save status</button></div></div></div>

    <div v-if="deleting" class="fixed inset-0 z-50 grid place-items-center bg-slate-900/40 p-4"><div class="w-full max-w-md rounded-lg bg-white p-6"><h2 class="text-lg font-semibold">Delete user</h2><p class="mt-2 text-sm text-slate-500">{{deleting.name}} · {{deleting.email}}</p><p class="mt-2 text-xs text-red-700">Protected users or users with records cannot be deleted.</p><div class="mt-4 flex justify-end gap-2"><button class="h-10 rounded-md border px-4 text-sm" @click="deleting=null">Cancel</button><button class="h-10 rounded-md bg-red-700 px-4 text-sm text-white" @click="destroyUser">Delete user</button></div></div></div>
</AdminLayout>
</template>
