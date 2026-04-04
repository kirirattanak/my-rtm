<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Settings', href: '/settings/profile' },
    { title: 'Password', href: '/settings/password' },
];

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function save() {
    form.put(route('password.update'), {
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Change Password" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-xl space-y-6">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Change Password</h1>
                <p class="text-sm text-slate-500 mt-0.5">Make sure you use a strong, unique password.</p>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <form @submit.prevent="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Current Password</label>
                        <input v-model="form.current_password" type="password" required autocomplete="current-password"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                            :class="{ 'border-red-400': form.errors.current_password }" />
                        <InputError :message="form.errors.current_password" class="mt-1" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">New Password</label>
                        <input v-model="form.password" type="password" required autocomplete="new-password"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                            :class="{ 'border-red-400': form.errors.password }" />
                        <InputError :message="form.errors.password" class="mt-1" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Confirm New Password</label>
                        <input v-model="form.password_confirmation" type="password" required autocomplete="new-password"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50" />
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="form.processing"
                            class="bg-primary text-primary-foreground text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50">
                            Update Password
                        </button>
                        <span v-if="form.recentlySuccessful" class="text-sm text-emerald-600">Updated.</span>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
