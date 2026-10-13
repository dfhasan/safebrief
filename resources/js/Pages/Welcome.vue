<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Safety briefings your crew actually read" />

    <div class="min-h-screen bg-gray-50">
        <header class="mx-auto flex max-w-5xl items-center justify-between px-4 py-6">
            <span class="text-lg font-bold text-gray-900">SafeBrief</span>
            <nav v-if="canLogin" class="flex gap-4 text-sm">
                <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="text-gray-700">Dashboard</Link>
                <template v-else>
                    <Link :href="route('login')" class="text-gray-700">Log in</Link>
                    <Link v-if="canRegister" :href="route('register')" class="font-semibold text-indigo-600">
                        Start free trial
                    </Link>
                </template>
            </nav>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-16 text-center">
            <h1 class="text-4xl font-bold tracking-tight text-gray-900">
                Turn an incident report into a crew-ready safety briefing in under a minute.
            </h1>
            <p class="mt-6 text-lg text-gray-600">
                Paste the report. Review the AI draft and its 3-question check. Send each crew member a link,
                and see who has read and understood it.
            </p>
            <Link
                v-if="canRegister && !$page.props.auth.user"
                :href="route('register')"
                class="mt-8 inline-block rounded-md bg-gray-900 px-6 py-3 font-semibold text-white"
            >
                Start your 14-day free trial
            </Link>
            <p class="mt-3 text-sm text-gray-500">No card. No sales call.</p>
        </main>
    </div>
</template>
