<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useT } from '@/i18n';

const props = defineProps({
    briefing: Object,
    links: Array,
    crewMembers: Array,
});

const t = useT();
const isDraft = computed(() => props.briefing.status === 'draft');

const form = useForm({
    title: props.briefing.title,
    summary: props.briefing.summary,
    key_actions: [...props.briefing.key_actions],
    questions: props.briefing.questions.map((q) => ({ ...q, options: [...q.options] })),
});

const save = (options = {}) => form.put(route('briefings.update', props.briefing.id), { preserveScroll: true, ...options });

const publish = () => {
    if (!confirm(t('Publish this briefing? After publishing it can no longer be edited.'))) {
        return;
    }
    const send = () => router.post(route('briefings.publish', props.briefing.id), {}, { preserveScroll: true });
    // Unsaved edits are saved first, so what gets published is what the manager sees.
    form.isDirty ? save({ onSuccess: send }) : send();
};

// Crew links
const invitedIds = computed(() => new Set(props.links.map((l) => l.crew_member_id)));
const notInvited = computed(() => props.crewMembers.filter((m) => !invitedIds.value.has(m.id)));
const invite = useForm({ crew_member_ids: [] });
const selectAll = () => (invite.crew_member_ids = notInvited.value.map((m) => m.id));
const sendInvites = () =>
    invite.post(route('briefings.links.store', props.briefing.id), {
        preserveScroll: true,
        onSuccess: () => invite.reset(),
    });

const completed = computed(() => props.links.filter((l) => l.completed_at).length);

const copied = ref(null);
const copy = async (link) => {
    try {
        // The Clipboard API only exists on https:// (and localhost); fall back for plain http.
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(link.url);
        } else {
            const area = Object.assign(document.createElement('textarea'), { value: link.url, readOnly: true });
            area.style.cssText = 'position:fixed;opacity:0';
            document.body.appendChild(area);
            area.select();
            document.execCommand('copy');
            area.remove();
        }
        copied.value = link.id;
        setTimeout(() => copied.value === link.id && (copied.value = null), 2500);
    } catch {
        window.prompt(t('Copy this link:'), link.url);
    }
};

const whatsapp = (link) =>
    'https://wa.me/?text=' +
    encodeURIComponent(t('Safety briefing: :title. Please read it and answer 3 quick questions: :url', { title: props.briefing.title, url: link.url }));
</script>

<template>
    <Head :title="briefing.title" />

    <AuthenticatedLayout>
        <template #header>
            <nav class="text-sm text-ink-muted" :aria-label="$t('Breadcrumb')">
                <Link :href="route('briefings.index')" class="hover:text-ink hover:underline">{{ $t('Briefings') }}</Link>
            </nav>
            <div class="mt-1 flex flex-wrap items-start justify-between gap-3">
                <h1 class="max-w-3xl text-2xl font-bold" dir="auto">{{ form.title || briefing.title }}</h1>
                <span
                    class="mt-1 rounded-full px-2.5 py-0.5 text-xs font-bold"
                    :class="isDraft ? 'bg-hazard text-ink' : 'bg-signal-wash text-signal'"
                >
                    {{ $t(isDraft ? 'Draft' : 'Published') }}
                </span>
            </div>
        </template>

        <div class="mx-auto mt-6 grid max-w-6xl gap-6 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
            <!-- DRAFT: edit the AI draft -->
            <form v-if="isDraft" class="panel lg:col-span-2" @submit.prevent="save()">
                <div class="flex gap-3 border-b border-line bg-hazard-wash px-5 py-4">
                    <svg viewBox="0 0 24 24" class="mt-0.5 h-5 w-5 shrink-0 text-ink" aria-hidden="true">
                        <path d="M12 3 2 21h20L12 3Z" fill="#FFC20E" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
                        <path d="M12 10v5m0 2.5v.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                    <p class="text-sm text-ink">
                        <strong>{{ $t('This is an AI draft.') }}</strong>
                        {{ $t('Check every fact against the original report and fix anything that is wrong before you publish.') }}
                    </p>
                </div>

                <div class="space-y-6 p-5 sm:p-6">
                    <div>
                        <label for="title" class="block text-sm font-semibold">{{ $t('Title') }}</label>
                        <input dir="auto" id="title" v-model="form.title" class="field mt-1.5" />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div>
                        <label for="summary" class="block text-sm font-semibold">{{ $t('What happened') }}</label>
                        <textarea dir="auto" id="summary" v-model="form.summary" rows="4" class="field mt-1.5 leading-relaxed" />
                        <InputError :message="form.errors.summary" />
                    </div>

                    <fieldset>
                        <legend class="text-sm font-semibold">{{ $t('What the crew must do') }}</legend>
                        <div v-for="(a, i) in form.key_actions" :key="i" class="mt-2 flex items-center gap-2">
                            <input dir="auto" v-model="form.key_actions[i]" class="field" :aria-label="$t('Action :n', { n: i + 1 })" />
                            <button
                                v-if="form.key_actions.length > 1"
                                type="button"
                                class="shrink-0 rounded-md px-2 py-2 text-sm font-medium text-alarm hover:bg-alarm-wash"
                                @click="form.key_actions.splice(i, 1)"
                            >
                                {{ $t('Remove') }}
                            </button>
                        </div>
                        <button
                            v-if="form.key_actions.length < 6"
                            type="button"
                            class="mt-2 text-sm font-semibold text-ink underline underline-offset-4"
                            @click="form.key_actions.push('')"
                        >
                            {{ $t('Add action') }}
                        </button>
                        <InputError :message="form.errors.key_actions" />
                    </fieldset>

                    <div class="space-y-4">
                        <h2 class="text-sm font-semibold">{{ $t('3-question check') }}</h2>
                        <fieldset v-for="(q, qi) in form.questions" :key="qi" class="rounded-md border border-line p-4">
                            <legend class="px-1 text-xs font-bold text-ink-muted">{{ $t('Question :n', { n: qi + 1 }) }}</legend>
                            <input dir="auto" v-model="q.prompt" class="field" :aria-label="$t('Question :n', { n: qi + 1 })" />
                            <InputError :message="form.errors[`questions.${qi}.prompt`]" />
                            <p class="mt-3 text-xs text-ink-muted">{{ $t('Tick the correct answer.') }}</p>
                            <div
                                v-for="(o, oi) in q.options"
                                :key="oi"
                                class="mt-2 flex items-center gap-3 rounded-md border px-3 py-1.5"
                                :class="q.correct_index === oi ? 'border-signal bg-signal-wash' : 'border-transparent'"
                            >
                                <input
                                    type="radio"
                                    class="h-5 w-5 border-line text-signal focus:ring-signal"
                                    :name="`correct-${qi}`"
                                    :value="oi"
                                    v-model="q.correct_index"
                                    :aria-label="$t('Mark as correct answer')"
                                />
                                <input dir="auto" v-model="q.options[oi]" class="field text-sm" :aria-label="$t('Option :n', { n: oi + 1 })" />
                                <span v-if="q.correct_index === oi" class="hidden shrink-0 text-xs font-bold text-signal sm:inline">
                                    {{ $t('Correct') }}
                                </span>
                            </div>
                            <InputError :message="form.errors[`questions.${qi}.correct_index`]" />
                        </fieldset>
                    </div>
                </div>

                <div class="sticky bottom-0 flex flex-wrap items-center justify-end gap-3 rounded-b-lg border-t border-line bg-white/95 px-5 py-3 backdrop-blur">
                    <span v-if="form.isDirty" class="me-auto text-sm text-ink-muted">{{ $t('Unsaved changes') }}</span>
                    <SecondaryButton type="submit" :disabled="form.processing || !form.isDirty">{{ $t('Save draft') }}</SecondaryButton>
                    <PrimaryButton type="button" :disabled="form.processing" @click="publish">{{ $t('Publish') }}</PrimaryButton>
                </div>
            </form>

            <!-- PUBLISHED: what the crew sees -->
            <article v-else class="panel p-5 sm:p-6 lg:col-span-2">
                <h2 class="text-sm font-semibold text-ink-muted">{{ $t('What happened') }}</h2>
                <p class="mt-1 whitespace-pre-line leading-relaxed" dir="auto">{{ briefing.summary }}</p>

                <h2 class="mt-6 text-sm font-semibold text-ink-muted">{{ $t('What the crew must do') }}</h2>
                <ul class="mt-2 space-y-2">
                    <li v-for="(a, i) in briefing.key_actions" :key="i" class="flex gap-3">
                        <span class="mt-2 h-2 w-2 shrink-0 rounded-sm bg-hazard ring-1 ring-ink" aria-hidden="true" />
                        <span dir="auto">{{ a }}</span>
                    </li>
                </ul>

                <h2 class="mt-6 text-sm font-semibold text-ink-muted">{{ $t('3-question check') }}</h2>
                <ol class="mt-2 space-y-4">
                    <li v-for="(q, qi) in briefing.questions" :key="qi">
                        <p class="font-medium" dir="auto">{{ qi + 1 }}. {{ q.prompt }}</p>
                        <ul class="mt-1.5 space-y-1 text-sm">
                            <li
                                v-for="(o, oi) in q.options"
                                :key="oi"
                                class="rounded px-2 py-1"
                                :class="oi === q.correct_index ? 'bg-signal-wash font-semibold text-signal' : 'text-ink-soft'"
                                dir="auto"
                            >
                                {{ o }}
                                <span v-if="oi === q.correct_index" class="sr-only">({{ $t('Correct') }})</span>
                            </li>
                        </ul>
                    </li>
                </ol>

                <details class="mt-6 border-t border-line pt-4 text-sm">
                    <summary class="cursor-pointer font-semibold text-ink-soft">{{ $t('Original incident report') }}</summary>
                    <p class="mt-2 whitespace-pre-line text-ink-soft" dir="auto">{{ briefing.incident_text }}</p>
                </details>
            </article>

            <!-- Side column -->
            <aside class="space-y-6 self-start lg:sticky lg:top-6">
                <section v-if="isDraft" class="panel p-5">
                    <h2 class="font-bold">{{ $t('Original incident report') }}</h2>
                    <p class="mt-2 max-h-[28rem] overflow-y-auto whitespace-pre-line text-sm leading-relaxed text-ink-soft" dir="auto">{{ briefing.incident_text }}</p>
                    <p class="mt-4 border-t border-line pt-4 text-sm text-ink-muted">{{ $t('Publish the briefing to send it to your crew.') }}</p>
                </section>

                <section v-else class="panel">
                    <div class="border-b border-line px-5 py-4">
                        <h2 class="font-bold">{{ $t('Crew') }}</h2>
                        <p v-if="links.length" class="mt-1 text-sm text-ink-soft">
                            {{ $t(':done of :total completed', { done: completed, total: links.length }) }}
                        </p>
                    </div>

                    <div class="px-5 py-4">
                        <p v-if="!crewMembers.length" class="text-sm text-ink-soft">
                            {{ $t('You have no crew yet.') }}
                            <Link :href="route('crew.index')" class="font-semibold text-ink underline underline-offset-4">{{ $t('Add crew') }}</Link>
                        </p>

                        <p v-else-if="!notInvited.length" class="text-sm text-ink-muted">{{ $t('Everyone in your crew has a link.') }}</p>

                        <form v-else @submit.prevent="sendInvites">
                            <div class="flex items-baseline justify-between">
                                <p class="text-sm font-semibold">{{ $t('Send to') }}</p>
                                <button type="button" class="text-sm text-ink-soft underline underline-offset-4 hover:text-ink" @click="selectAll">
                                    {{ $t('Select all') }}
                                </button>
                            </div>
                            <div class="mt-2 space-y-1">
                                <label
                                    v-for="m in notInvited"
                                    :key="m.id"
                                    class="flex cursor-pointer items-center gap-3 rounded-md px-2 py-2 text-sm hover:bg-paper"
                                >
                                    <input
                                        type="checkbox"
                                        class="h-5 w-5 rounded border-line text-ink focus:ring-ink"
                                        :value="m.id"
                                        v-model="invite.crew_member_ids"
                                    />
                                    <span dir="auto">{{ m.name }}</span>
                                </label>
                            </div>
                            <InputError :message="invite.errors.crew_member_ids" />
                            <PrimaryButton class="mt-3 w-full" :disabled="!invite.crew_member_ids.length || invite.processing">
                                {{ $t('Create links') }}
                            </PrimaryButton>
                        </form>
                    </div>

                    <ul v-if="links.length" class="divide-y divide-line border-t border-line">
                        <li v-for="l in links" :key="l.id" class="px-5 py-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-medium" dir="auto">{{ l.crew_member }}</span>
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-bold"
                                    :class="
                                        l.completed_at
                                            ? 'bg-signal-wash text-signal'
                                            : l.opened_at
                                              ? 'bg-hazard-wash text-ink'
                                              : 'bg-paper text-ink-muted'
                                    "
                                >
                                    <template v-if="l.completed_at">{{ $t('Done · :score/3', { score: l.score }) }}</template>
                                    <template v-else-if="l.opened_at">{{ $t('Opened') }}</template>
                                    <template v-else>{{ $t('Not opened') }}</template>
                                </span>
                            </div>
                            <div v-if="!l.completed_at" class="mt-2 flex gap-2">
                                <button
                                    type="button"
                                    class="inline-flex min-h-[2.25rem] items-center rounded-md border border-line px-3 text-xs font-semibold hover:border-ink"
                                    @click="copy(l)"
                                >
                                    {{ copied === l.id ? $t('Copied') : $t('Copy link') }}
                                </button>
                                <a
                                    :href="whatsapp(l)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex min-h-[2.25rem] items-center rounded-md border border-line px-3 text-xs font-semibold hover:border-ink"
                                >
                                    {{ $t('Send on WhatsApp') }}
                                </a>
                            </div>
                        </li>
                    </ul>
                </section>
            </aside>
        </div>
    </AuthenticatedLayout>
</template>
