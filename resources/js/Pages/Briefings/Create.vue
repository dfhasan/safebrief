<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { useT } from '@/i18n';

const t = useT();
const form = useForm({ incident_text: '' });

const useExample = () => {
    form.incident_text = t(
        'Night shift, Dock 3. A deckhand slipped on the wet stairs to the lower deck while carrying a toolbox. The anti-slip strip on the third step was missing and two stairwell lights were out. He twisted his ankle; first aid was given on site and he was back on light duties the next day.',
    );
};
</script>

<template>
    <Head :title="$t('New briefing')" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-bold">{{ $t('New briefing') }}</h1>
        </template>

        <div class="mx-auto mt-6 grid max-w-6xl gap-6 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
            <form class="panel p-5 sm:p-6 lg:col-span-2" @submit.prevent="form.post(route('briefings.store'))">
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <label for="incident_text" class="font-bold">{{ $t('Incident or near-miss report') }}</label>
                    <button type="button" class="text-sm font-semibold text-ink-soft underline underline-offset-4 hover:text-ink" @click="useExample">
                        {{ $t('Use an example report') }}
                    </button>
                </div>
                <p class="mt-1 text-sm text-ink-soft">
                    {{ $t('Paste it as written. Remove names and personal details first.') }}
                </p>
                <textarea dir="auto"
                    id="incident_text"
                    v-model="form.incident_text"
                    rows="12"
                    required
                    class="field mt-4 leading-relaxed"
                    :placeholder="$t('What happened, where, and what was found afterwards…')"
                />
                <InputError :message="form.errors.incident_text" />

                <div class="mt-5 flex flex-wrap items-center justify-end gap-3">
                    <p v-if="form.processing" class="text-sm text-ink-soft" role="status">{{ $t('Drafting, this takes a few seconds…') }}</p>
                    <PrimaryButton :disabled="form.processing">
                        {{ form.processing ? $t('Drafting…') : $t('Draft briefing') }}
                    </PrimaryButton>
                </div>
            </form>

            <aside class="self-start rounded-lg border border-line bg-white/60 p-5 sm:p-6">
                <h2 class="font-bold">{{ $t('What happens next') }}</h2>
                <ol class="mt-3 space-y-3 text-sm text-ink-soft">
                    <li class="flex gap-3">
                        <span class="font-bold text-ink">1</span>{{ $t('The AI drafts a short briefing and a 3-question check.') }}
                    </li>
                    <li class="flex gap-3">
                        <span class="font-bold text-ink">2</span>{{ $t('You check it against the report and fix anything wrong.') }}
                    </li>
                    <li class="flex gap-3">
                        <span class="font-bold text-ink">3</span>{{ $t('You publish it. Nothing reaches your crew before that.') }}
                    </li>
                </ol>
            </aside>
        </div>
    </AuthenticatedLayout>
</template>
