<script setup>
import LanguageSwitch from '@/Components/LanguageSwitch.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    token: String,
    crewMember: String,
    briefing: Object,
    questions: Array,
    result: Object,
});

const form = useForm({ answers: props.questions.map(() => null) });
const answered = computed(() => form.answers.filter((a) => a !== null).length);
const submit = () => form.post(route('crew.submit', props.token), { preserveScroll: true });
</script>

<template>
    <Head :title="briefing.title" />

    <div class="min-h-screen bg-paper pb-10">
        <header class="bg-ink text-white">
            <div class="h-2 bg-hazard" aria-hidden="true" />
            <div class="mx-auto flex max-w-xl items-center justify-between gap-3 px-4 py-3">
                <p class="text-sm text-white/80">{{ $t('Safety briefing for :name', { name: crewMember }) }}</p>
                <LanguageSwitch />
            </div>
        </header>

        <main class="mx-auto max-w-xl space-y-4 px-4 pt-5">
            <article class="panel p-5">
                <h1 class="text-2xl font-bold leading-tight" dir="auto">{{ briefing.title }}</h1>
                <p class="mt-3 whitespace-pre-line text-[1.0625rem] leading-relaxed" dir="auto">{{ briefing.summary }}</p>

                <h2 class="mt-6 font-bold">{{ $t('What you must do') }}</h2>
                <ul class="mt-3 space-y-3">
                    <li v-for="(a, i) in briefing.key_actions" :key="i" class="flex gap-3 rounded-md bg-hazard-wash p-3">
                        <svg viewBox="0 0 20 20" class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true">
                            <rect width="20" height="20" rx="4" fill="#15233B" />
                            <path d="m5.5 10.5 3 3 6-7" fill="none" stroke="#FFC20E" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <span class="leading-snug" dir="auto">{{ a }}</span>
                    </li>
                </ul>
            </article>

            <section v-if="result" class="panel overflow-hidden">
                <div class="px-5 py-5" :class="result.score === result.total ? 'bg-signal text-white' : 'bg-ink text-white'">
                    <p class="text-sm opacity-80">{{ $t('Thanks, you are done.') }}</p>
                    <p class="mt-1 text-3xl font-bold tabular-nums">{{ $t('You scored :score / :total', { score: result.score, total: result.total }) }}</p>
                </div>
                <ol class="divide-y divide-line">
                    <li v-for="(q, qi) in questions" :key="qi" class="px-5 py-4">
                        <p class="font-semibold" dir="auto">{{ qi + 1 }}. {{ q.prompt }}</p>
                        <p class="mt-1.5 flex items-start gap-2 text-sm" :class="result.answers[qi] === q.correct_index ? 'text-signal' : 'text-alarm'">
                            <span class="font-bold">{{ result.answers[qi] === q.correct_index ? '✓' : '✗' }}</span>
                            <span>{{ $t('Your answer') }}: {{ q.options[result.answers[qi]] }}</span>
                        </p>
                        <p v-if="result.answers[qi] !== q.correct_index" class="mt-1 ps-5 text-sm text-ink-soft">
                            {{ $t('Correct answer') }}: <strong class="text-ink" dir="auto">{{ q.options[q.correct_index] }}</strong>
                        </p>
                    </li>
                </ol>
            </section>

            <form v-else class="panel p-5" @submit.prevent="submit">
                <div class="flex items-baseline justify-between">
                    <h2 class="text-lg font-bold">{{ $t('Quick check') }}</h2>
                    <p class="text-sm tabular-nums text-ink-muted">{{ $t(':n of :total answered', { n: answered, total: questions.length }) }}</p>
                </div>

                <fieldset v-for="(q, qi) in questions" :key="qi" class="mt-5">
                    <legend class="font-semibold" dir="auto">{{ qi + 1 }}. {{ q.prompt }}</legend>
                    <div class="mt-2 space-y-2">
                        <label
                            v-for="(o, oi) in q.options"
                            :key="oi"
                            class="flex min-h-[3.25rem] cursor-pointer items-center gap-3 rounded-md border-2 px-4 py-2 transition"
                            :class="form.answers[qi] === oi ? 'border-ink bg-ink/5' : 'border-line bg-white hover:border-ink-muted'"
                        >
                            <input
                                type="radio"
                                class="h-5 w-5 border-line text-ink focus:ring-ink"
                                :name="`q${qi}`"
                                :value="oi"
                                v-model="form.answers[qi]"
                                required
                            />
                            <span dir="auto">{{ o }}</span>
                        </label>
                    </div>
                </fieldset>

                <p v-if="Object.keys(form.errors).length" class="mt-4 text-sm font-medium text-alarm" role="alert">
                    {{ $t('Answer every question.') }}
                </p>

                <button
                    class="mt-6 min-h-[3.25rem] w-full rounded-md bg-hazard text-base font-bold text-ink shadow-[inset_0_-3px_0_rgba(21,35,59,.25)] transition hover:bg-hazard-deep disabled:cursor-not-allowed disabled:bg-line disabled:text-ink-muted disabled:shadow-none"
                    :disabled="form.processing || form.answers.includes(null)"
                >
                    {{ $t('Submit answers') }}
                </button>
            </form>
        </main>
    </div>
</template>
