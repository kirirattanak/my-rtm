<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    user: {
        id: number;
        name: string;
        email: string;
        email_verified_at: string | null;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Settings', href: '/settings/profile' },
    { title: 'Profile', href: '/settings/profile' },
];

const form = useForm({
    name: props.user.name,
    email: props.user.email,
});

function save() {
    form.patch(route('profile.update'));
}
</script>

<template>
    <Head title="Profile Settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 max-w-xl space-y-6 stagger">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Profile</h1>
                <p class="text-sm text-slate-500 mt-0.5">Update your name and email address.</p>
            </div>

            <div class="bg-white border border-slate-200 rounded-xl p-5">
                <form @submit.prevent="save" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                        <input v-model="form.name" type="text" required autocomplete="name"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                            :class="{ 'border-red-400': form.errors.name }" />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input v-model="form.email" type="email" required autocomplete="email"
                            class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50"
                            :class="{ 'border-red-400': form.errors.email }" />
                        <InputError :message="form.errors.email" class="mt-1" />
                        <p v-if="user.email_verified_at === null"
                           class="text-xs text-amber-600 mt-1">
                            Your email address is unverified.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" :disabled="form.processing"
                            class="bg-primary text-primary-foreground text-sm font-medium px-4 py-2 rounded-lg hover:opacity-90 transition-opacity disabled:opacity-50">
                            Save Changes
                        </button>
                        <span v-if="form.recentlySuccessful" class="text-sm text-emerald-600">Saved.</span>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
