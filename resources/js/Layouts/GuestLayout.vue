<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LanguageSwitch from '@/Components/LanguageSwitch.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="grid min-h-screen bg-paper lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">
        <aside class="relative hidden flex-col justify-between overflow-hidden bg-ink p-10 text-white lg:flex">
            <Link href="/" class="flex items-center gap-2.5">
                <ApplicationLogo class="h-9 w-9" />
                <span class="text-xl font-bold tracking-tight">SafeBrief</span>
            </Link>

            <div class="max-w-md">
                <p class="text-3xl font-semibold leading-snug">
                    {{ $t('From incident report to a briefing your crew has read and understood.') }}
                </p>
                <ol class="mt-8 space-y-3 text-white/80">
                    <li class="flex gap-3">
                        <span class="font-bold text-hazard">1</span>{{ $t('Paste the report') }}
                    </li>
                    <li class="flex gap-3">
                        <span class="font-bold text-hazard">2</span>{{ $t('Review the AI draft and publish') }}
                    </li>
                    <li class="flex gap-3">
                        <span class="font-bold text-hazard">3</span>{{ $t('Each crew member answers on their phone') }}
                    </li>
                </ol>
            </div>

            <div class="h-3 w-full rounded-sm bg-hazard" aria-hidden="true" />
        </aside>

        <div class="flex flex-col">
            <div class="flex items-center justify-between px-6 py-5 sm:px-10">
                <Link href="/" class="flex items-center gap-2.5 lg:invisible">
                    <ApplicationLogo class="h-8 w-8" />
                    <span class="text-lg font-bold tracking-tight">SafeBrief</span>
                </Link>
                <LanguageSwitch tone="light" />
            </div>

            <div class="flex flex-1 items-start justify-center px-6 pb-16 pt-4 sm:items-center sm:px-10">
                <div class="w-full max-w-md">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
