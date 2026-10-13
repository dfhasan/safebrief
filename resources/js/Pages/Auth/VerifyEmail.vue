<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <GuestLayout>
        <Head :title="$t('Verify your email')" />

        <h1 class="text-2xl font-bold">{{ $t('Verify your email') }}</h1>
        <p class="mt-2 text-ink-soft">
            {{ $t('Open the link we just emailed you to verify your address. No email? Send another one.') }}
        </p>

        <p v-if="verificationLinkSent" class="mt-6 rounded-md bg-signal-wash px-4 py-3 text-sm font-medium text-signal" role="status">
            {{ $t('A new verification link has been sent to your email address.') }}
        </p>

        <form class="mt-8 flex items-center justify-between gap-4" @submit.prevent="submit">
            <PrimaryButton :disabled="form.processing">{{ $t('Resend verification email') }}</PrimaryButton>
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="text-sm text-ink-soft underline underline-offset-4 hover:text-ink"
            >
                {{ $t('Log out') }}
            </Link>
        </form>
    </GuestLayout>
</template>
