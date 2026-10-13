<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useT } from '@/i18n';

const page = usePage();
const t = useT();

const COPY = {
    crew: {
        label: 'Add your crew',
        hint: 'Add the people who need to read your briefings. A name is enough, they never need an account.',
        cta: 'Add crew',
    },
    report: {
        label: 'Paste a report',
        hint: 'Paste any incident or near-miss report. The AI drafts a short briefing and a 3-question check.',
        cta: 'Paste a report',
    },
    publish: {
        label: 'Review and publish',
        hint: 'Check the AI draft against the report, fix anything that is wrong, then publish it.',
        cta: 'Review the draft',
    },
    invite: {
        label: 'Send links',
        hint: 'On the published briefing, pick your crew and send each person their own link.',
        cta: 'Send links',
    },
    complete: {
        label: 'First completion',
        hint: 'Waiting for someone to open their link and answer. The briefing page shows who has opened it.',
        cta: 'See who has opened it',
    },
};

const trail = computed(() => page.props.onboarding);
const steps = computed(() => trail.value?.steps ?? []);
const currentIndex = computed(() => steps.value.findIndex((s) => s.key === trail.value?.current));
const current = computed(() => steps.value[currentIndex.value]);
</script>

<template>
    <section v-if="current" class="border-b border-line bg-white" :aria-label="t('Setup steps')">
        <div class="mx-auto max-w-6xl px-4 py-4 sm:px-6 lg:px-8">
            <ol class="grid grid-cols-5 gap-1.5 sm:gap-3">
                <li v-for="(s, i) in steps" :key="s.key" class="min-w-0">
                    <div
                        class="h-1.5 rounded-full"
                        :class="s.done ? 'bg-signal' : i === currentIndex ? 'bg-hazard' : 'bg-line'"
                    />
                    <div class="mt-2 hidden items-center gap-2 sm:flex">
                        <span
                            class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                            :class="
                                s.done
                                    ? 'bg-signal text-white'
                                    : i === currentIndex
                                      ? 'bg-hazard text-ink'
                                      : 'border border-line text-ink-muted'
                            "
                        >
                            <svg v-if="s.done" viewBox="0 0 16 16" class="h-3.5 w-3.5" aria-hidden="true">
                                <path d="m3.5 8.5 3 3 6-7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <template v-else>{{ i + 1 }}</template>
                        </span>
                        <span
                            class="truncate text-sm"
                            :class="i === currentIndex ? 'font-semibold text-ink' : s.done ? 'text-ink-soft' : 'text-ink-muted'"
                        >
                            {{ t(COPY[s.key].label) }}
                            <span v-if="s.done" class="sr-only">({{ t('done') }})</span>
                        </span>
                    </div>
                </li>
            </ol>

            <div
                class="mt-4 flex flex-col gap-3 rounded-md border-s-[6px] border-hazard bg-hazard-wash px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-ink">
                        {{ t('Step :n of 5', { n: currentIndex + 1 }) }}: {{ t(COPY[current.key].label) }}
                    </p>
                    <p class="mt-0.5 text-sm text-ink-soft">{{ t(COPY[current.key].hint) }}</p>
                </div>
                <Link
                    :href="current.href"
                    class="inline-flex min-h-[2.75rem] shrink-0 items-center justify-center rounded-md bg-hazard px-5 text-sm font-bold text-ink shadow-[inset_0_-2px_0_rgba(21,35,59,.25)] transition hover:bg-hazard-deep"
                >
                    {{ t(COPY[current.key].cta) }}
                </Link>
            </div>
        </div>
    </section>
</template>
