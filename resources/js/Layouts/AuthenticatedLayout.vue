<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import LanguageSwitch from '@/Components/LanguageSwitch.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import StepTrail from '@/Components/StepTrail.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

const nav = [
    { label: 'Dashboard', route: 'dashboard', active: 'dashboard' },
    { label: 'Briefings', route: 'briefings.index', active: 'briefings.*' },
    { label: 'Crew', route: 'crew.index', active: 'crew.index' },
];
</script>

<template>
    <div class="min-h-screen bg-paper">
        <nav class="bg-ink text-white">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-stretch justify-between gap-4">
                    <div class="flex items-stretch gap-8">
                        <Link :href="route('dashboard')" class="flex items-center gap-2.5">
                            <ApplicationLogo class="h-8 w-8" />
                            <span class="text-lg font-bold tracking-tight">SafeBrief</span>
                        </Link>

                        <div class="hidden items-stretch gap-6 sm:flex">
                            <NavLink
                                v-for="item in nav"
                                :key="item.route"
                                :href="route(item.route)"
                                :active="route().current(item.active)"
                            >
                                {{ $t(item.label) }}
                            </NavLink>
                        </div>
                    </div>

                    <div class="hidden items-center gap-3 sm:flex">
                        <span
                            v-if="$page.props.trial"
                            class="text-sm"
                            :class="$page.props.trial.active ? 'text-white/70' : 'font-semibold text-hazard'"
                        >
                            <template v-if="$page.props.trial.active">
                                {{ $t(':days days left in trial', { days: $page.props.trial.daysLeft }) }}
                            </template>
                            <template v-else>{{ $t('Trial ended') }}</template>
                        </span>

                        <LanguageSwitch />

                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button
                                    type="button"
                                    class="inline-flex min-h-[2.25rem] items-center gap-2 rounded-md px-2 text-sm font-medium text-white/85 transition hover:text-white"
                                >
                                    {{ $page.props.auth.user.name }}
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <div class="border-b border-line px-4 py-2 text-xs text-ink-muted">
                                    {{ $page.props.trial?.company }}
                                </div>
                                <DropdownLink :href="route('profile.edit')">{{ $t('Profile') }}</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">
                                    {{ $t('Log out') }}
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>

                    <!-- Small screens -->
                    <div class="flex items-center gap-2 sm:hidden">
                        <LanguageSwitch />
                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-md text-white/80 hover:bg-white/10 hover:text-white"
                            :aria-label="$t('Menu')"
                            :aria-expanded="showingNavigationDropdown"
                            @click="showingNavigationDropdown = !showingNavigationDropdown"
                        >
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <path
                                    v-if="!showingNavigationDropdown"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div v-show="showingNavigationDropdown" class="border-t border-white/10 sm:hidden">
                <div class="py-2">
                    <ResponsiveNavLink
                        v-for="item in nav"
                        :key="item.route"
                        :href="route(item.route)"
                        :active="route().current(item.active)"
                    >
                        {{ $t(item.label) }}
                    </ResponsiveNavLink>
                </div>
                <div class="border-t border-white/10 py-3">
                    <div class="px-4 pb-2">
                        <div class="font-semibold">{{ $page.props.auth.user.name }}</div>
                        <div class="text-sm text-white/60">{{ $page.props.auth.user.email }}</div>
                        <div v-if="$page.props.trial" class="mt-1 text-sm text-white/60">
                            {{ $page.props.trial.company }} ·
                            <template v-if="$page.props.trial.active">
                                {{ $t(':days days left in trial', { days: $page.props.trial.daysLeft }) }}
                            </template>
                            <template v-else>{{ $t('Trial ended') }}</template>
                        </div>
                    </div>
                    <ResponsiveNavLink :href="route('profile.edit')">{{ $t('Profile') }}</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('logout')" method="post" as="button">{{ $t('Log out') }}</ResponsiveNavLink>
                </div>
            </div>
        </nav>

        <StepTrail />

        <div v-if="$page.props.flash?.status" class="mx-auto max-w-6xl px-4 pt-6 sm:px-6 lg:px-8">
            <p
                class="flex items-start gap-2 rounded-md border border-signal/30 bg-signal-wash px-4 py-3 text-sm font-medium text-signal"
                role="status"
            >
                <svg viewBox="0 0 16 16" class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true">
                    <path d="m3.5 8.5 3 3 6-7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                {{ $page.props.flash.status }}
            </p>
        </div>

        <header v-if="$slots.header" class="mx-auto max-w-6xl px-4 pt-8 sm:px-6 lg:px-8">
            <slot name="header" />
        </header>

        <main class="pb-16">
            <slot />
        </main>
    </div>
</template>
