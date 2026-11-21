<script setup lang="ts">
import { reactive, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { GraduationCap, Plus, Search, X } from 'lucide-vue-next';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { Card, CardContent } from '@/components/ui/card';
import type { EtmsSharedProps } from '@/types/etms';

interface Profile {
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

interface Props extends EtmsSharedProps {
    profiles: { data: Profile[]; from: number | null; to: number | null; total: number; links: { url: string | null; label: string; active: boolean }[] };
    filters: { q: string; public: string };
}

const props = defineProps<Props>();
const filters = reactive({ ...props.filters });
const modalOpen = ref(false);
const editing = ref<Profile | null>(null);
const busy = ref(false);
const photoPreview = ref<string | null>(null);
const form = reactive({
    name: '',
    email: '',
    password: '',
    professional_title: '',
    qualifications: '',
    short_bio: '',
    biography: '',
    specialisations: '',
    is_public: false,
    photo: null as File | null,
});

function apply(): void {
    router.get('/admin-vue/instructor-profiles', filters, { preserveState: true, replace: true });
}

function beginCreate(): void {
    editing.value = null;
    Object.assign(form, {
        name: '', email: '', password: '',
        professional_title: '', qualifications: '', short_bio: '',
        biography: '', specialisations: '', is_public: false, photo: null,
    });
    modalOpen.value = true;
    photoPreview.value = null;
}

function beginEdit(profile: Profile): void {
    editing.value = profile;
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
    photoPreview.value = profile.photoUrl;
}

function handlePhoto(event: Event): void {
    form.photo = (event.target as HTMLInputElement).files?.[0] ?? null;
    photoPreview.value = form.photo ? URL.createObjectURL(form.photo) : photoPreview.value;
}

function save(): void {
    busy.value = true;
    const data = {
        ...form,
        specialisations: form.specialisations.split(',').map((value) => value.trim()).filter(Boolean),
        photo: form.photo ?? undefined,
    };

    if (editing.value) {
        router.post(`/admin-vue/instructor-profiles/${editing.value.id}`, data, {
            forceFormData: true,
            preserveScroll: true,
            onFinish: () => { busy.value = false; },
            onSuccess: () => { modalOpen.value = false; },
        });
        return;
    }

    router.post('/admin-vue/instructor-profiles', data, {
        forceFormData: true,
        preserveScroll: true,
        onFinish: () => { busy.value = false; },
        onSuccess: () => { modalOpen.value = false; },
    });
}
</script>

<template>
<AdminLayout>
    <div class="mb-6 flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-medium text-[#0F4C81]">Training management</p>
            <h1 class="mt-1 text-2xl font-semibold">Instructors</h1>
        </div>
        <button type="button" class="inline-flex h-10 items-center gap-2 rounded-md bg-[#0F4C81] px-4 text-sm font-medium text-white" @click="beginCreate">
            <Plus :size="16"/> Create instructor
        </button>
    </div>

    <Card>
        <CardContent class="p-4">
            <form class="grid gap-3 md:grid-cols-[minmax(0,1fr)_10rem_auto]" @submit.prevent="apply">
                <label class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" :size="16"/>
                    <input v-model="filters.q" class="h-10 w-full rounded-md border pl-9 text-sm" placeholder="Name or email"/>
                </label>
                <select v-model="filters.public" class="h-10 rounded-md border px-3 text-sm">
                    <option value="">All profiles</option>
                    <option value="1">Public</option>
                    <option value="0">Hidden</option>
                </select>
                <button class="h-10 rounded-md bg-slate-900 px-4 text-sm text-white">Apply</button>
            </form>
        </CardContent>
    </Card>

    <Card class="mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Instructor</th><th class="px-4 py-3">Title</th><th class="px-4 py-3">Visibility</th><th class="px-4 py-3 text-right">Actions</th></tr></thead>
                <tbody class="divide-y">
                    <tr v-for="profile in profiles.data" :key="profile.id">
                        <td class="px-4 py-4"><div class="flex items-center gap-3"><img v-if="profile.photoUrl" :src="profile.photoUrl" alt="" class="h-10 w-10 rounded-full object-cover"/><span v-else class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-xs font-bold text-slate-600">{{(profile.name||profile.email||'I').slice(0,1).toUpperCase()}}</span><div><p class="font-medium">{{ profile.name }}</p><p class="text-xs text-slate-500">{{ profile.email }}</p></div></div></td>
                        <td class="px-4 py-4">{{ profile.professionalTitle || '—' }}</td>
                        <td class="px-4 py-4"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs">{{ profile.isPublic ? 'Public' : 'Hidden' }}</span></td>
                        <td class="px-4 py-4 text-right"><div class="flex justify-end gap-2">
                            <button type="button" class="rounded-md border px-3 py-1.5 text-xs" @click="beginEdit(profile)">Edit</button>
                            <button type="button" class="rounded-md border px-3 py-1.5 text-xs" @click="router.post(`/admin-vue/instructor-profiles/${profile.id}/toggle`, {}, { preserveScroll: true })">{{ profile.isPublic ? 'Hide' : 'Publish' }}</button>
                            <button type="button" class="rounded-md border border-red-200 px-3 py-1.5 text-xs text-red-700" @click="router.delete(`/admin-vue/instructor-profiles/${profile.id}`, { preserveScroll: true })">Delete</button>
                        </div></td>
                    </tr>
                    <tr v-if="!profiles.data.length"><td colspan="4" class="p-12 text-center text-slate-500"><GraduationCap class="mx-auto mb-2" :size="24"/>No instructors found.</td></tr>
                </tbody>
            </table>
        </div>
    </Card>

    <div v-if="modalOpen" class="fixed inset-0 z-50 grid place-items-center bg-slate-900/40 p-4" role="dialog" aria-modal="true">
        <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-lg bg-white p-6 shadow-xl">
            <div class="flex items-start justify-between gap-4">
                <div><h2 class="text-lg font-semibold">{{ editing ? 'Edit instructor' : 'Create instructor' }}</h2><p class="mt-1 text-sm text-slate-500">{{ editing ? 'Update account and public profile details.' : 'Create a login account and instructor profile.' }}</p></div>
                <button type="button" class="grid h-9 w-9 place-items-center rounded-md text-slate-500 hover:bg-slate-100" aria-label="Close" @click="modalOpen=false"><X :size="18"/></button>
            </div>
            <form class="mt-5 grid gap-4 md:grid-cols-2" @submit.prevent="save">
                <label class="text-sm">Full name<input v-model="form.name" required maxlength="150" class="mt-1 h-10 w-full rounded-md border px-3"/></label>
                <label class="text-sm">Email address<input v-model="form.email" type="email" required maxlength="255" class="mt-1 h-10 w-full rounded-md border px-3"/></label>
                <label class="text-sm md:col-span-2">{{ editing ? 'New password (leave blank to keep)' : 'Password' }}<input v-model="form.password" :required="!editing" type="password" minlength="8" class="mt-1 h-10 w-full rounded-md border px-3"/></label>
                <label class="text-sm">Professional title<input v-model="form.professional_title" maxlength="150" class="mt-1 h-10 w-full rounded-md border px-3"/></label>
                <label class="text-sm">Qualifications<input v-model="form.qualifications" maxlength="255" class="mt-1 h-10 w-full rounded-md border px-3"/></label>
                <label class="text-sm md:col-span-2">Short summary<textarea v-model="form.short_bio" rows="2" maxlength="500" class="mt-1 w-full rounded-md border px-3 py-2"/></label>
                <label class="text-sm md:col-span-2">Biography<textarea v-model="form.biography" rows="4" class="mt-1 w-full rounded-md border px-3 py-2"/></label>
                <label class="text-sm md:col-span-2">Specialisations <span class="text-xs text-slate-500">(comma separated)</span><input v-model="form.specialisations" class="mt-1 h-10 w-full rounded-md border px-3"/></label>
                <div class="md:col-span-2 flex items-center gap-4"><img v-if="photoPreview" :src="photoPreview" alt="Instructor photo" class="h-20 w-20 rounded-full object-cover border"/><span v-else class="grid h-20 w-20 place-items-center rounded-full bg-slate-100 text-xl font-bold text-slate-600">{{(form.name||'I').slice(0,1).toUpperCase()}}</span><label class="text-sm">Instructor photograph<input type="file" accept="image/*" class="mt-1 block w-full text-sm" @change="handlePhoto"/></label></div>
                <label class="flex items-center gap-2 text-sm md:col-span-2"><input v-model="form.is_public" type="checkbox"/> Publish in public directory</label>
                <div class="flex justify-end gap-2 md:col-span-2">
                    <button type="button" class="h-10 rounded-md border px-4 text-sm" @click="modalOpen=false">Cancel</button>
                    <button type="submit" :disabled="busy" class="h-10 rounded-md bg-[#0F4C81] px-4 text-sm text-white">{{ busy ? 'Saving…' : editing ? 'Save instructor' : 'Create instructor' }}</button>
                </div>
            </form>
        </div>
    </div>
</AdminLayout>
</template>
