<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head :title="$t('Trial ended')" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-xl px-4 py-16">
            <div class="panel overflow-hidden">
                <div class="h-3 bg-hazard" aria-hidden="true" />
                <div class="p-6 sm:p-8">
                    <h1 class="text-2xl font-bold">{{ $t('Your 14-day trial has ended') }}</h1>
                    <p class="mt-3 leading-relaxed text-ink-soft">
                        {{ $t('Your briefings and crew results are kept, and crew links you already sent keep working.') }}
                        {{ $t('To create new briefings, contact us to continue with SafeBrief.') }}
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
