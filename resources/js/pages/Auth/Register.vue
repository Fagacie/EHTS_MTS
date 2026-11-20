<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { cn } from '@/lib/utils';
import type { AuthRegisterProps } from '@/types/etms';

const props = defineProps<AuthRegisterProps>();

const form = useForm({
    name: '',
    email: '',
    phone: '',
    customer_type: 'individual' as 'individual' | 'company',
    company_name: '',
    company_registration_no: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirm = ref(false);
const isCompany = computed(() => form.customer_type === 'company');

function submitRegistration(): void {
    form.post(props.submitUrl, {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
}
</script>

<template>
    <Head title="Create Account" />

    <AuthLayout
        heading="Create customer account"
        subheading="Register as an individual learner or company training purchaser."
        :wide="true"
    >
        <form class="space-y-4" @submit.prevent="submitRegistration">
            <div>
                <Label for="name" class="mb-1.5">
                    Full name <span aria-hidden="true">*</span>
                </Label>
                <Input
                    id="name"
                    v-model="form.name"
                    name="name"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Full legal name (printed on certificates)"
                    :aria-invalid="form.errors.name ? 'true' : undefined"
                />
                <p
                    v-if="form.errors.name"
                    class="mt-1.5 text-[14px] font-semibold text-[#272d38]"
                >
                    {{ form.errors.name }}
                </p>
                <p v-else class="mt-1 text-[13px] text-[#272d38]/65">
                    Your full name appears on training bookings and official certificates.
                </p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <Label for="email" class="mb-1.5">
                        Email address <span aria-hidden="true">*</span>
                    </Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        name="email"
                        type="email"
                        required
                        autocomplete="email"
                        placeholder="name@company.com.my"
                        :aria-invalid="form.errors.email ? 'true' : undefined"
                    />
                    <p
                        v-if="form.errors.email"
                        class="mt-1.5 text-[14px] font-semibold text-[#272d38]"
                    >
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <Label for="phone" class="mb-1.5">
                        Phone number
                        <span class="ml-1 font-normal text-[#272d38]/55">(Optional)</span>
                    </Label>
                    <Input
                        id="phone"
                        v-model="form.phone"
                        name="phone"
                        type="text"
                        autocomplete="tel"
                        placeholder="+6012-345 6789"
                        :aria-invalid="form.errors.phone ? 'true' : undefined"
                    />
                    <p
                        v-if="form.errors.phone"
                        class="mt-1.5 text-[14px] font-semibold text-[#272d38]"
                    >
                        {{ form.errors.phone }}
                    </p>
                </div>
            </div>

            <fieldset class="space-y-2">
                <legend class="block text-[15px] font-semibold text-[#272d38]">
                    Purchase profile type
                </legend>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <label
                        :class="
                            cn(
                                'flex cursor-pointer items-start gap-2.5 rounded-xl border p-3.5 transition-colors',
                                !isCompany
                                    ? 'border-[#272d38] bg-[#272d38]/[0.04]'
                                    : 'border-[#272d38]/15 bg-white hover:border-[#272d38]/35',
                            )
                        "
                    >
                        <input
                            v-model="form.customer_type"
                            type="radio"
                            name="customer_type"
                            value="individual"
                            class="mt-0.5 h-4 w-4 accent-[#272d38]"
                        >
                        <span>
                            <span class="block text-[14px] font-bold text-[#272d38]">Individual</span>
                            <span class="block text-[13px] text-[#272d38]/65">Personal training receipt</span>
                        </span>
                    </label>

                    <label
                        :class="
                            cn(
                                'flex cursor-pointer items-start gap-2.5 rounded-xl border p-3.5 transition-colors',
                                isCompany
                                    ? 'border-[#272d38] bg-[#272d38]/[0.04]'
                                    : 'border-[#272d38]/15 bg-white hover:border-[#272d38]/35',
                            )
                        "
                    >
                        <input
                            v-model="form.customer_type"
                            type="radio"
                            name="customer_type"
                            value="company"
                            class="mt-0.5 h-4 w-4 accent-[#272d38]"
                        >
                        <span>
                            <span class="block text-[14px] font-bold text-[#272d38]">Company Buyer</span>
                            <span class="block text-[13px] text-[#272d38]/65">Include company billing</span>
                        </span>
                    </label>
                </div>
            </fieldset>

            <div
                v-if="isCompany"
                class="space-y-3 rounded-xl border border-[#272d38]/15 bg-[#272d38]/[0.02] p-4"
            >
                <p class="text-[13px] leading-relaxed text-[#272d38]/70">
                    Company billing details appear on your order receipt. Bookings remain registered to your personal account.
                </p>

                <div>
                    <Label for="company_name" class="mb-1.5">
                        Company name <span aria-hidden="true">*</span>
                    </Label>
                    <Input
                        id="company_name"
                        v-model="form.company_name"
                        name="company_name"
                        type="text"
                        :required="isCompany"
                        placeholder="Ecohydro Engineering Sdn. Bhd."
                        :aria-invalid="form.errors.company_name ? 'true' : undefined"
                    />
                    <p
                        v-if="form.errors.company_name"
                        class="mt-1.5 text-[14px] font-semibold text-[#272d38]"
                    >
                        {{ form.errors.company_name }}
                    </p>
                </div>

                <div>
                    <Label for="company_registration_no" class="mb-1.5">
                        Company registration / SSM No.
                        <span class="ml-1 font-normal text-[#272d38]/55">(Optional)</span>
                    </Label>
                    <Input
                        id="company_registration_no"
                        v-model="form.company_registration_no"
                        name="company_registration_no"
                        type="text"
                        placeholder="202001012345 (1368665-X)"
                    />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <Label for="password" class="mb-1.5">
                        Password <span aria-hidden="true">*</span>
                    </Label>
                    <div class="relative">
                        <Input
                            id="password"
                            v-model="form.password"
                            name="password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            autocomplete="new-password"
                            :aria-invalid="form.errors.password ? 'true' : undefined"
                            class="pr-14"
                        />
                        <button
                            type="button"
                            :aria-pressed="showPassword"
                            :aria-label="showPassword ? 'Hide password' : 'Show password'"
                            class="absolute inset-y-0 right-0 flex min-w-12 items-center justify-center rounded-r-lg text-[13px] font-semibold text-[#272d38]/65 hover:text-[#272d38]"
                            @click="showPassword = !showPassword"
                        >
                            {{ showPassword ? 'Hide' : 'Show' }}
                        </button>
                    </div>
                    <p
                        v-if="form.errors.password"
                        class="mt-1.5 text-[14px] font-semibold text-[#272d38]"
                    >
                        {{ form.errors.password }}
                    </p>
                </div>

                <div>
                    <Label for="password_confirmation" class="mb-1.5">
                        Confirm password <span aria-hidden="true">*</span>
                    </Label>
                    <div class="relative">
                        <Input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            name="password_confirmation"
                            :type="showConfirm ? 'text' : 'password'"
                            required
                            autocomplete="new-password"
                            class="pr-14"
                        />
                        <button
                            type="button"
                            :aria-pressed="showConfirm"
                            :aria-label="showConfirm ? 'Hide confirm password' : 'Show confirm password'"
                            class="absolute inset-y-0 right-0 flex min-w-12 items-center justify-center rounded-r-lg text-[13px] font-semibold text-[#272d38]/65 hover:text-[#272d38]"
                            @click="showConfirm = !showConfirm"
                        >
                            {{ showConfirm ? 'Hide' : 'Show' }}
                        </button>
                    </div>
                </div>
            </div>

            <Button type="submit" size="lg" class="w-full" :disabled="form.processing">
                {{ form.processing ? 'Creating Account...' : 'Create Customer Account' }}
            </Button>
        </form>

        <Separator />

        <p class="text-center text-[14px] text-[#272d38]/65">
            Already registered?
            <Link
                :href="loginUrl"
                class="ml-1 font-semibold text-[#272d38] hover:underline underline-offset-4"
            >
                Sign in here
            </Link>
        </p>
    </AuthLayout>
</template>
