<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Trial ended" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-xl px-4 py-16 text-center">
            <h1 class="text-2xl font-bold text-gray-900">Your 14-day trial has ended</h1>
            <p class="mt-3 text-gray-600">
                Your briefings and crew results are kept. Contact us to continue using SafeBrief.
            </p>
        </div>
    </AuthenticatedLayout>
</template>
