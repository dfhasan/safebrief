<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    briefing: Object,
    links: Array,
    crewMembers: Array,
});

const isDraft = computed(() => props.briefing.status === 'draft');

const form = useForm({
    title: props.briefing.title,
    summary: props.briefing.summary,
    key_actions: [...props.briefing.key_actions],
    questions: props.briefing.questions.map((q) => ({ ...q, options: [...q.options] })),
});

const save = () => form.put(route('briefings.update', props.briefing.id), { preserveScroll: true });

const publish = () => {
    if (form.isDirty) {
        alert('Save your changes before publishing.');
        return;
    }
    if (confirm('Publish this briefing? It can no longer be edited after publishing.')) {
        router.post(route('briefings.publish', props.briefing.id), {}, { preserveScroll: true });
    }
};

const invited = computed(() => new Set(props.links.map((l) => l.crew_member)));
const invite = useForm({ crew_member_ids: [] });
const sendInvites = () =>
    invite.post(route('briefings.links.store', props.briefing.id), {
        preserveScroll: true,
        onSuccess: () => invite.reset(),
    });

const copied = ref(null);
const copy = async (link) => {
    await navigator.clipboard.writeText(link.url);
    copied.value = link.id;
};
</script>

<template>
    <Head :title="briefing.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    <Link :href="route('briefings.index')" class="text-gray-500 hover:underline">Briefings</Link>
                    / {{ briefing.title }}
                </h2>
                <span
                    class="rounded-full px-2 py-0.5 text-xs font-semibold"
                    :class="isDraft ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800'"
                >
                    {{ briefing.status }}
                </span>
            </div>
        </template>

        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-3 lg:px-8">
            <!-- Draft review / published view -->
            <section class="space-y-4 rounded-lg bg-white p-6 shadow-sm lg:col-span-2">
                <p v-if="isDraft" class="rounded-md bg-amber-50 p-3 text-sm text-amber-900">
                    AI draft. Check every fact against the report and fix anything wrong before publishing.
                </p>

                <fieldset :disabled="!isDraft" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Title</label>
                        <input v-model="form.title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Summary</label>
                        <textarea v-model="form.summary" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                        <InputError :message="form.errors.summary" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Key actions</label>
                        <div v-for="(a, i) in form.key_actions" :key="i" class="mt-1 flex gap-2">
                            <input v-model="form.key_actions[i]" class="block w-full rounded-md border-gray-300 shadow-sm" />
                            <button
                                v-if="isDraft && form.key_actions.length > 1"
                                type="button"
                                class="text-sm text-red-600"
                                @click="form.key_actions.splice(i, 1)"
                            >
                                Remove
                            </button>
                        </div>
                        <button
                            v-if="isDraft && form.key_actions.length < 6"
                            type="button"
                            class="mt-1 text-sm text-indigo-600"
                            @click="form.key_actions.push('')"
                        >
                            + Add action
                        </button>
                        <InputError :message="form.errors.key_actions" />
                    </div>

                    <div v-for="(q, qi) in form.questions" :key="qi" class="rounded-md border border-gray-200 p-4">
                        <label class="block text-sm font-medium text-gray-700">Question {{ qi + 1 }}</label>
                        <input v-model="q.prompt" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" />
                        <InputError :message="form.errors[`questions.${qi}.prompt`]" />
                        <div v-for="(o, oi) in q.options" :key="oi" class="mt-2 flex items-center gap-2">
                            <input
                                type="radio"
                                :name="`correct-${qi}`"
                                :value="oi"
                                v-model="q.correct_index"
                                :title="'Mark as correct answer'"
                            />
                            <input v-model="q.options[oi]" class="block w-full rounded-md border-gray-300 text-sm shadow-sm" />
                        </div>
                        <p class="mt-1 text-xs text-gray-500">The selected radio button marks the correct answer.</p>
                        <InputError :message="form.errors[`questions.${qi}.correct_index`]" />
                    </div>
                </fieldset>

                <div v-if="isDraft" class="flex flex-wrap justify-end gap-3">
                    <SecondaryButton :disabled="form.processing" @click="save">Save draft</SecondaryButton>
                    <PrimaryButton type="button" @click="publish">Publish</PrimaryButton>
                </div>

                <details class="text-sm text-gray-600">
                    <summary class="cursor-pointer">Original incident report</summary>
                    <p class="mt-2 whitespace-pre-line">{{ briefing.incident_text }}</p>
                </details>
            </section>

            <!-- Crew links -->
            <section class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
                <h3 class="font-semibold text-gray-900">Crew</h3>

                <p v-if="isDraft" class="text-sm text-gray-600">Publish the briefing to invite crew.</p>

                <template v-else>
                    <p v-if="!crewMembers.length" class="text-sm text-gray-600">
                        <Link :href="route('crew.index')" class="text-indigo-600 underline">Add crew members</Link> first.
                    </p>

                    <form v-else @submit.prevent="sendInvites" class="space-y-2">
                        <label
                            v-for="m in crewMembers"
                            :key="m.id"
                            class="flex items-center gap-2 text-sm"
                            :class="{ 'text-gray-400': invited.has(m.name) }"
                        >
                            <input
                                type="checkbox"
                                :value="m.id"
                                v-model="invite.crew_member_ids"
                                :disabled="invited.has(m.name)"
                            />
                            {{ m.name }}
                            <span v-if="invited.has(m.name)">(invited)</span>
                        </label>
                        <InputError :message="invite.errors.crew_member_ids" />
                        <PrimaryButton :disabled="!invite.crew_member_ids.length || invite.processing">
                            Create links
                        </PrimaryButton>
                    </form>

                    <ul class="divide-y divide-gray-100 text-sm">
                        <li v-for="l in links" :key="l.id" class="py-2">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-medium">{{ l.crew_member }}</span>
                                <span v-if="l.completed_at" class="text-green-700">Done · {{ l.score }}/3</span>
                                <span v-else-if="l.opened_at" class="text-amber-700">Opened</span>
                                <span v-else class="text-gray-500">Not opened</span>
                            </div>
                            <button type="button" class="mt-1 text-xs text-indigo-600" @click="copy(l)">
                                {{ copied === l.id ? 'Copied' : 'Copy link' }}
                            </button>
                        </li>
                    </ul>
                </template>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
