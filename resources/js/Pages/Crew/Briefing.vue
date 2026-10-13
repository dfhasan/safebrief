<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    token: String,
    crewMember: String,
    briefing: Object,
    questions: Array,
    result: Object,
});

const form = useForm({ answers: props.questions.map(() => null) });
const submit = () => form.post(route('crew.submit', props.token), { preserveScroll: true });
</script>

<template>
    <Head :title="briefing.title" />

    <div class="min-h-screen bg-gray-100 px-4 py-6">
        <main class="mx-auto max-w-xl space-y-5">
            <p class="text-sm text-gray-500">Safety briefing for {{ crewMember }}</p>

            <article class="rounded-lg bg-white p-5 shadow-sm">
                <h1 class="text-xl font-bold text-gray-900">{{ briefing.title }}</h1>
                <p class="mt-3 whitespace-pre-line text-gray-800">{{ briefing.summary }}</p>

                <h2 class="mt-5 font-semibold text-gray-900">What to do</h2>
                <ul class="mt-2 list-disc space-y-1 ps-5 text-gray-800">
                    <li v-for="(a, i) in briefing.key_actions" :key="i">{{ a }}</li>
                </ul>
            </article>

            <section v-if="result" class="rounded-lg bg-white p-5 shadow-sm">
                <h2 class="text-lg font-semibold text-gray-900">
                    Thanks, you're done. You scored {{ result.score }} / {{ result.total }}.
                </h2>
                <ol class="mt-4 space-y-3 text-sm">
                    <li v-for="(q, qi) in questions" :key="qi">
                        <p class="font-medium">{{ q.prompt }}</p>
                        <p :class="result.answers[qi] === q.correct_index ? 'text-green-700' : 'text-red-700'">
                            Your answer: {{ q.options[result.answers[qi]] }}
                        </p>
                        <p v-if="result.answers[qi] !== q.correct_index" class="text-gray-700">
                            Correct: {{ q.options[q.correct_index] }}
                        </p>
                    </li>
                </ol>
            </section>

            <form v-else class="space-y-4 rounded-lg bg-white p-5 shadow-sm" @submit.prevent="submit">
                <h2 class="text-lg font-semibold text-gray-900">Quick check</h2>

                <fieldset v-for="(q, qi) in questions" :key="qi">
                    <legend class="font-medium text-gray-900">{{ qi + 1 }}. {{ q.prompt }}</legend>
                    <label
                        v-for="(o, oi) in q.options"
                        :key="oi"
                        class="mt-2 flex items-center gap-3 rounded-md border border-gray-200 p-3 text-gray-800"
                    >
                        <input type="radio" :name="`q${qi}`" :value="oi" v-model="form.answers[qi]" required />
                        {{ o }}
                    </label>
                </fieldset>

                <p v-if="Object.keys(form.errors).length" class="text-sm text-red-600">Answer every question.</p>

                <button
                    class="w-full rounded-md bg-gray-900 py-3 font-semibold text-white disabled:opacity-50"
                    :disabled="form.processing || form.answers.includes(null)"
                >
                    Submit answers
                </button>
            </form>
        </main>
    </div>
</template>
