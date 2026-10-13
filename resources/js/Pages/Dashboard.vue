<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    briefings: Array,
    draftCount: Number,
    funnel: Array,
    timeToFirstValue: String,
});

const page = usePage();
const pct = (done, total) => (total ? Math.round((done / total) * 100) : 0);
const when = (at) =>
    at ? new Date(at).toLocaleString(page.props.locale === 'ar' ? 'ar' : 'en-GB', { dateStyle: 'medium', timeStyle: 'short' }) : null;

const totals = computed(() => {
    const sent = props.briefings.reduce((n, b) => n + b.crew_links_count, 0);
    const done = props.briefings.reduce((n, b) => n + b.completed_count, 0);
    const scored = props.briefings.reduce((n, b) => n + (b.average_score !== null ? Number(b.average_score) * b.completed_count : 0), 0);
    return { published: props.briefings.length, sent, done, rate: pct(done, sent), score: done ? (scored / done).toFixed(1) : null };
});
</script>

<template>
    <Head :title="$t('Dashboard')" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold">{{ $t('Dashboard') }}</h1>
                </div>
                <Link
                    :href="route('briefings.create')"
                    class="inline-flex min-h-[2.75rem] items-center rounded-md bg-ink px-5 text-sm font-semibold text-white hover:bg-ink-soft"
                >
                    {{ $t('New briefing') }}
                </Link>
            </div>
        </template>

        <dl class="mx-auto mt-6 grid max-w-6xl grid-cols-2 gap-px overflow-hidden px-4 sm:px-6 lg:grid-cols-4 lg:px-8">
            <div class="border border-line bg-white px-5 py-4 max-lg:rounded-ss-lg lg:rounded-s-lg">
                <dt class="text-sm text-ink-soft">{{ $t('Published briefings') }}</dt>
                <dd class="mt-1 text-2xl font-bold tabular-nums">{{ totals.published }}</dd>
            </div>
            <div class="border border-line bg-white px-5 py-4 max-lg:rounded-se-lg">
                <dt class="text-sm text-ink-soft">{{ $t('Crew links sent') }}</dt>
                <dd class="mt-1 text-2xl font-bold tabular-nums">{{ totals.sent }}</dd>
            </div>
            <div class="border border-line bg-white px-5 py-4 max-lg:rounded-es-lg">
                <dt class="text-sm text-ink-soft">{{ $t('Completed') }}</dt>
                <dd class="mt-1 text-2xl font-bold tabular-nums">
                    {{ totals.done }}
                    <span v-if="totals.sent" class="text-base font-semibold text-signal">{{ totals.rate }}%</span>
                </dd>
            </div>
            <div class="border border-line bg-white px-5 py-4 max-lg:rounded-ee-lg lg:rounded-e-lg">
                <dt class="text-sm text-ink-soft">{{ $t('Average score') }}</dt>
                <dd class="mt-1 text-2xl font-bold tabular-nums">{{ totals.score !== null ? totals.score + ' / 3' : '–' }}</dd>
            </div>
        </dl>

        <div class="mx-auto mt-6 grid max-w-6xl items-start gap-6 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
            <section class="panel lg:col-span-2" :aria-label="$t('Briefing completion')">
                <div class="flex items-center justify-between border-b border-line px-5 py-4">
                    <h2 class="font-bold">{{ $t('Briefing completion') }}</h2>
                    <Link
                        v-if="draftCount"
                        :href="route('briefings.index')"
                        class="rounded-full bg-hazard-wash px-3 py-1 text-sm font-semibold text-ink hover:bg-hazard"
                    >
                        {{ $t(':count drafts to review', { count: draftCount }) }}
                    </Link>
                </div>

                <div v-if="!briefings.length" class="px-5 py-10 text-center">
                    <p class="font-semibold">{{ $t('No published briefings yet') }}</p>
                    <p class="mx-auto mt-1 max-w-sm text-sm text-ink-soft">
                        {{ $t('Completion for each briefing shows here once you publish one and send crew their links.') }}
                    </p>
                    <Link
                        :href="route('briefings.create')"
                        class="mt-4 inline-block font-semibold text-ink underline underline-offset-4"
                    >
                        {{ $t('Paste your first incident report') }}
                    </Link>
                </div>

                <ul v-else class="divide-y divide-line">
                    <li v-for="b in briefings" :key="b.id">
                        <Link :href="route('briefings.show', b.id)" class="block px-5 py-4 hover:bg-paper/60">
                            <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                                <span class="font-semibold" dir="auto">{{ b.title }}</span>
                                <span class="text-sm tabular-nums text-ink-soft">
                                    {{ $t(':done / :total completed', { done: b.completed_count, total: b.crew_links_count }) }}
                                    <template v-if="b.average_score !== null">
                                        · {{ $t('avg score :score/3', { score: Number(b.average_score).toFixed(1) }) }}
                                    </template>
                                </span>
                            </div>
                            <div
                                class="mt-2.5 h-2 overflow-hidden rounded-full bg-paper"
                                role="progressbar"
                                :aria-valuenow="pct(b.completed_count, b.crew_links_count)"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            >
                                <div class="h-full rounded-full bg-signal" :style="{ width: pct(b.completed_count, b.crew_links_count) + '%' }" />
                            </div>
                            <p v-if="!b.crew_links_count" class="mt-2 text-sm text-ink-muted">{{ $t('No crew links sent yet.') }}</p>
                        </Link>
                    </li>
                </ul>
            </section>

            <section class="panel self-start" :aria-label="$t('Trial funnel')">
                <div class="border-b border-line px-5 py-4">
                    <h2 class="font-bold">{{ $t('Trial funnel') }}</h2>
                    <p class="mt-1 text-sm text-ink-soft">
                        {{ $t('Time to first value') }}:
                        <strong class="text-ink">{{ timeToFirstValue ?? $t('not reached yet') }}</strong>
                    </p>
                </div>

                <ol class="relative px-5 py-4">
                    <li v-for="(s, i) in funnel" :key="s.step" class="relative flex gap-3 pb-4 last:pb-0">
                        <span
                            v-if="i < funnel.length - 1"
                            class="absolute start-3 top-6 h-[calc(100%-1.25rem)] w-px -translate-x-1/2 rtl:translate-x-1/2"
                            :class="s.at && funnel[i + 1].at ? 'bg-signal' : 'bg-line'"
                            aria-hidden="true"
                        />
                        <span
                            class="relative z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                            :class="s.at ? 'bg-signal text-white' : 'border border-line bg-white text-ink-muted'"
                        >
                            <svg v-if="s.at" viewBox="0 0 16 16" class="h-3.5 w-3.5" aria-hidden="true">
                                <path d="m3.5 8.5 3 3 6-7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <template v-else>{{ i + 1 }}</template>
                        </span>
                        <div class="min-w-0 pt-0.5">
                            <p class="text-sm" :class="s.at ? 'font-semibold' : 'text-ink-muted'">{{ $t(s.label) }}</p>
                            <p v-if="s.at" class="text-xs text-ink-muted">{{ when(s.at) }}</p>
                        </div>
                    </li>
                </ol>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
