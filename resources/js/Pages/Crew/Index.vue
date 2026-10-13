<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

defineProps({ crewMembers: Array });

const form = useForm({ name: '', contact: '' });
const add = () => form.post(route('crew.store'), { preserveScroll: true, onSuccess: () => form.reset() });
const remove = (m) => {
    if (confirm(`Remove ${m.name}? Their briefing links stop working.`)) {
        router.delete(route('crew.destroy', m.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Crew" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Crew</h2>
        </template>

        <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
            <form class="flex flex-wrap items-start gap-3 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="add">
                <div class="min-w-[10rem] flex-1">
                    <TextInput v-model="form.name" placeholder="Name" class="block w-full" required />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="min-w-[10rem] flex-1">
                    <TextInput v-model="form.contact" placeholder="Phone or email (optional)" class="block w-full" />
                    <InputError :message="form.errors.contact" />
                </div>
                <PrimaryButton :disabled="form.processing">Add</PrimaryButton>
            </form>

            <div class="rounded-lg bg-white shadow-sm">
                <p v-if="!crewMembers.length" class="p-6 text-sm text-gray-600">No crew members yet.</p>
                <ul v-else class="divide-y divide-gray-100">
                    <li v-for="m in crewMembers" :key="m.id" class="flex items-center justify-between px-6 py-3 text-sm">
                        <span>
                            <span class="font-medium text-gray-900">{{ m.name }}</span>
                            <span v-if="m.contact" class="ms-2 text-gray-500">{{ m.contact }}</span>
                        </span>
                        <button type="button" class="text-red-600 hover:underline" @click="remove(m)">Remove</button>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
