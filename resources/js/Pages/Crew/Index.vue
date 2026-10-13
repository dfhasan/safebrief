<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useT } from '@/i18n';

defineProps({ crewMembers: Array });

const t = useT();
const nameInput = ref(null);
const form = useForm({ name: '', contact: '' });
const add = () =>
    form.post(route('crew.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            nameInput.value?.focus();
        },
    });
const remove = (m) => {
    if (confirm(t('Remove :name? Their briefing links stop working and their answers are deleted.', { name: m.name }))) {
        router.delete(route('crew.destroy', m.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head :title="$t('Crew')" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-2xl font-bold">{{ $t('Crew') }}</h1>
            <p class="mt-1 max-w-2xl text-ink-soft">
                {{ $t('The people who receive your briefings. They never need an account: each one gets a personal link.') }}
            </p>
        </template>

        <div class="mx-auto mt-6 grid max-w-6xl gap-6 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
            <form class="panel self-start p-5" @submit.prevent="add">
                <h2 class="font-bold">{{ $t('Add a crew member') }}</h2>
                <div class="mt-4 space-y-4">
                    <div>
                        <label for="crew-name" class="block text-sm font-semibold">{{ $t('Name') }}</label>
                        <TextInput id="crew-name" ref="nameInput" v-model="form.name" class="mt-1.5" :placeholder="$t('Name')" required />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div>
                        <label for="crew-contact" class="block text-sm font-semibold">
                            {{ $t('Phone or email') }} <span class="font-normal text-ink-muted">({{ $t('optional') }})</span>
                        </label>
                        <TextInput id="crew-contact" v-model="form.contact" class="mt-1.5" :placeholder="$t('Phone or email (optional)')" />
                        <InputError :message="form.errors.contact" />
                    </div>
                    <PrimaryButton class="w-full" :disabled="form.processing">{{ $t('Add to crew') }}</PrimaryButton>
                </div>
            </form>

            <section class="panel lg:col-span-2">
                <div class="border-b border-line px-5 py-4">
                    <h2 class="font-bold">
                        {{ $t('Your crew') }}
                        <span v-if="crewMembers.length" class="font-normal text-ink-muted">({{ crewMembers.length }})</span>
                    </h2>
                </div>

                <div v-if="!crewMembers.length" class="px-5 py-12 text-center">
                    <p class="font-semibold">{{ $t('No crew members yet') }}</p>
                    <p class="mx-auto mt-1 max-w-sm text-sm text-ink-soft">
                        {{ $t('Add the people on your site or vessel. A name is enough to start.') }}
                    </p>
                </div>

                <ul v-else class="divide-y divide-line">
                    <li v-for="m in crewMembers" :key="m.id" class="flex items-center justify-between gap-4 px-5 py-3">
                        <div class="min-w-0">
                            <p class="truncate font-semibold" dir="auto">{{ m.name }}</p>
                            <p class="truncate text-sm text-ink-muted">
                                <template v-if="m.contact" dir="auto">{{ m.contact }} · </template>
                                {{ $t(':done of :total briefings completed', { done: m.completed_count, total: m.crew_links_count }) }}
                            </p>
                        </div>
                        <button
                            type="button"
                            class="shrink-0 rounded-md px-3 py-2 text-sm font-medium text-alarm hover:bg-alarm-wash"
                            @click="remove(m)"
                        >
                            {{ $t('Remove') }}
                        </button>
                    </li>
                </ul>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
