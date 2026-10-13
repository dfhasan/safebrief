<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const trial = computed(() => usePage().props.trial);
const mailto = computed(() => `mailto:${trial.value.contactEmail}?subject=${encodeURIComponent('SafeBrief: ' + trial.value.company)}`);

const copied = ref(false);
const copyEmail = async () => {
    try {
        await navigator.clipboard.writeText(trial.value.contactEmail);
        copied.value = true;
    } catch {
        window.prompt('', trial.value.contactEmail);
    }
};
</script>

<template>
    <Head :title="$t('Trial ended')" />

    <AuthenticatedLayout>
        <div class="mx-auto max-w-xl px-4 py-12 sm:py-16">
            <div class="panel overflow-hidden">
                <div class="h-3 bg-hazard" aria-hidden="true" />
                <div class="p-6 sm:p-8">
                    <h1 class="text-2xl font-bold">{{ $t('Your 14-day trial has ended') }}</h1>
                    <p class="mt-3 leading-relaxed text-ink-soft">
                        {{ $t('Your briefings and crew results are kept, and crew links you already sent keep working.') }}
                    </p>

                    <div class="mt-6 rounded-md bg-paper p-4">
                        <p class="font-semibold">{{ $t('To keep using SafeBrief, email us') }}</p>
                        <p class="mt-1 text-sm text-ink-soft">{{ $t('We switch your account back on with all its data.') }}</p>
                        <div class="mt-4 flex flex-wrap items-center gap-3">
                            <a
                                :href="mailto"
                                class="inline-flex min-h-[2.75rem] items-center rounded-md bg-hazard px-5 text-sm font-bold text-ink shadow-[inset_0_-2px_0_rgba(21,35,59,.25)] hover:bg-hazard-deep"
                            >
                                {{ $t('Email :email', { email: trial.contactEmail }) }}
                            </a>
                            <button type="button" class="text-sm font-semibold text-ink-soft underline underline-offset-4 hover:text-ink" @click="copyEmail">
                                {{ copied ? $t('Copied') : $t('Copy address') }}
                            </button>
                        </div>
                    </div>

                    <p class="mt-6 text-sm text-ink-muted">
                        {{ $t('You can still change your') }}
                        <Link :href="route('profile.edit')" class="font-semibold text-ink underline underline-offset-4">{{ $t('profile') }}</Link>
                        {{ $t('or') }}
                        <Link :href="route('logout')" method="post" as="button" class="font-semibold text-ink underline underline-offset-4">
                            {{ $t('log out') }}</Link
                        >.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
