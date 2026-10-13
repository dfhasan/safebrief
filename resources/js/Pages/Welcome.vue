<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import LanguageSwitch from '@/Components/LanguageSwitch.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head :title="$t('Safety briefings your crew actually read')" />

    <div class="min-h-screen bg-paper">
        <div class="bg-ink text-white">
            <header class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-5 sm:px-6 lg:px-8">
                <Link href="/" class="flex items-center gap-2.5">
                    <ApplicationLogo class="h-8 w-8" />
                    <span class="text-lg font-bold tracking-tight">SafeBrief</span>
                </Link>
                <nav v-if="canLogin" class="flex items-center gap-3 text-sm">
                    <LanguageSwitch />
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')" class="font-semibold">{{ $t('Dashboard') }}</Link>
                    <template v-else>
                        <Link :href="route('login')" class="px-2 font-medium text-white/80 hover:text-white">{{ $t('Log in') }}</Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="hidden min-h-[2.25rem] items-center rounded-md bg-hazard px-4 font-bold text-ink hover:bg-hazard-deep sm:inline-flex"
                        >
                            {{ $t('Start free trial') }}
                        </Link>
                    </template>
                </nav>
            </header>

            <section class="mx-auto grid max-w-6xl items-center gap-12 px-4 pb-16 pt-8 sm:px-6 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:px-8 lg:pb-24 lg:pt-14">
                <div>
                    <h1 class="max-w-[18ch] text-4xl font-bold leading-[1.1] sm:text-5xl">
                        {{ $t('Turn an incident report into a briefing your crew has read and understood.') }}
                    </h1>
                    <p class="mt-6 max-w-[52ch] text-lg leading-relaxed text-white/75">
                        {{ $t('Paste the report. Review the AI draft and its 3-question check. Send each crew member a personal link, and see who has read it and who got the answers right.') }}
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        <Link
                            v-if="canRegister && !$page.props.auth.user"
                            :href="route('register')"
                            class="inline-flex min-h-[3.25rem] items-center rounded-md bg-hazard px-6 text-base font-bold text-ink shadow-[inset_0_-3px_0_rgba(21,35,59,.25)] hover:bg-hazard-deep"
                        >
                            {{ $t('Start your 14-day free trial') }}
                        </Link>
                        <Link
                            v-else-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="inline-flex min-h-[3.25rem] items-center rounded-md bg-hazard px-6 text-base font-bold text-ink hover:bg-hazard-deep"
                        >
                            {{ $t('Go to dashboard') }}
                        </Link>
                        <p class="text-sm text-white/60">{{ $t('No card. No sales call.') }}</p>
                    </div>
                </div>

                <!-- What the crew member sees on their phone -->
                <div class="mx-auto w-full max-w-[19rem]" aria-hidden="true">
                    <div class="rounded-[2.25rem] border-[10px] border-[#0B1526] bg-paper shadow-2xl">
                        <div class="h-1.5 rounded-t-[1.5rem] bg-hazard" />
                        <div class="space-y-3 p-3.5 text-ink">
                            <div class="rounded-lg border border-line bg-white p-3.5">
                                <p class="text-[0.95rem] font-bold leading-snug">{{ $t('Wet stairs at Dock 3: hold the rail') }}</p>
                                <p class="mt-1.5 text-xs leading-relaxed text-ink-soft">
                                    {{ $t('A deckhand slipped on wet stairs. The anti-slip strip was missing and two lights were out.') }}
                                </p>
                                <div class="mt-2.5 space-y-1.5">
                                    <p class="flex gap-2 rounded bg-hazard-wash px-2 py-1.5 text-xs font-medium">
                                        <span class="mt-0.5 h-3 w-3 shrink-0 rounded-sm bg-ink" />{{ $t('Keep one hand on the rail') }}
                                    </p>
                                    <p class="flex gap-2 rounded bg-hazard-wash px-2 py-1.5 text-xs font-medium">
                                        <span class="mt-0.5 h-3 w-3 shrink-0 rounded-sm bg-ink" />{{ $t('Report missing strips and dead lights') }}
                                    </p>
                                </div>
                            </div>
                            <div class="rounded-lg border border-line bg-white p-3.5">
                                <p class="text-xs font-semibold">1. {{ $t('What do you report the same shift?') }}</p>
                                <p class="mt-2 rounded-md border-2 border-ink bg-ink/5 px-2.5 py-2 text-xs">{{ $t('Missing anti-slip strips') }}</p>
                                <p class="mt-1.5 rounded-md border-2 border-line px-2.5 py-2 text-xs text-ink-soft">{{ $t('Only injuries') }}</p>
                                <p class="mt-3 rounded-md bg-hazard py-2 text-center text-xs font-bold">{{ $t('Submit answers') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold">{{ $t('How it works') }}</h2>
            <ol class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <li v-for="(s, i) in [
                    ['Paste the report', 'Any incident or near-miss report, as written.'],
                    ['Review the draft', 'The AI writes a short briefing and 3 questions. You check and fix it.'],
                    ['Send the links', 'Each crew member gets a personal link. No app, no account.'],
                    ['See who understood', 'Track who opened it, who finished, and their scores.'],
                ]" :key="i" class="border-t-4 pt-4" :class="i === 0 ? 'border-hazard' : 'border-ink'">
                    <p class="text-sm font-bold text-ink-muted">{{ i + 1 }}</p>
                    <h3 class="mt-1 font-bold">{{ $t(s[0]) }}</h3>
                    <p class="mt-1 text-sm leading-relaxed text-ink-soft">{{ $t(s[1]) }}</p>
                </li>
            </ol>
        </section>

        <footer class="border-t border-line">
            <div class="mx-auto max-w-6xl px-4 py-6 text-sm text-ink-muted sm:px-6 lg:px-8">SafeBrief</div>
        </footer>
    </div>
</template>
