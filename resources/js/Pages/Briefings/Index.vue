<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

defineProps({ briefings: Array });

const page = usePage();
const day = (at) => (at ? new Date(at).toLocaleDateString(page.props.locale === 'ar' ? 'ar' : 'en-GB', { dateStyle: 'medium' }) : '');
const pct = (done, total) => (total ? Math.round((done / total) * 100) : 0);
</script>

<template>
    <Head :title="$t('Briefings')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h1 class="text-2xl font-bold">{{ $t('Briefings') }}</h1>
                <Link
                    :href="route('briefings.create')"
                    class="inline-flex min-h-[2.75rem] items-center rounded-md bg-ink px-5 text-sm font-semibold text-white hover:bg-ink-soft"
                >
                    {{ $t('New briefing') }}
                </Link>
            </div>
        </template>

        <div class="mx-auto mt-6 max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="panel">
                <div v-if="!briefings.length" class="px-5 py-12 text-center">
                    <p class="font-semibold">{{ $t('No briefings yet') }}</p>
                    <p class="mx-auto mt-1 max-w-sm text-sm text-ink-soft">
                        {{ $t('Paste an incident or near-miss report and the AI drafts the first one for you to review.') }}
                    </p>
                    <Link :href="route('briefings.create')" class="mt-4 inline-block font-semibold text-ink underline underline-offset-4">
                        {{ $t('Paste a report') }}
                    </Link>
                </div>

                <ul v-else class="divide-y divide-line">
                    <li v-for="b in briefings" :key="b.id">
                        <Link
                            :href="route('briefings.show', b.id)"
                            class="grid gap-3 px-5 py-4 hover:bg-paper/60 sm:grid-cols-[minmax(0,1fr)_12rem_7rem] sm:items-center"
                        >
                            <div class="min-w-0">
                                <p class="truncate font-semibold" dir="auto">{{ b.title }}</p>
                                <p class="text-sm text-ink-muted">
                                    {{ b.status === 'published' ? $t('Published :date', { date: day(b.published_at) }) : $t('Drafted :date', { date: day(b.created_at) }) }}
                                </p>
                            </div>

                            <div v-if="b.status === 'published'" class="text-sm">
                                <span class="tabular-nums text-ink-soft">
                                    {{ $t(':done / :total completed', { done: b.completed_count, total: b.crew_links_count }) }}
                                </span>
                                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-paper">
                                    <div class="h-full rounded-full bg-signal" :style="{ width: pct(b.completed_count, b.crew_links_count) + '%' }" />
                                </div>
                            </div>
                            <p v-else class="text-sm font-medium text-ink-soft">{{ $t('Needs your review') }}</p>

                            <span
                                class="justify-self-start rounded-full px-2.5 py-0.5 text-xs font-bold sm:justify-self-end"
                                :class="b.status === 'published' ? 'bg-signal-wash text-signal' : 'bg-hazard text-ink'"
                            >
                                {{ $t(b.status === 'published' ? 'Published' : 'Draft') }}
                            </span>
                        </Link>
                    </li>
                </ul>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
