<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head :title="$t('Reset your password')" />

        <h1 class="text-2xl font-bold">{{ $t('Reset your password') }}</h1>
        <p class="mt-2 text-ink-soft">
            {{ $t('Enter your email and we will send you a link to choose a new password.') }}
        </p>

        <p v-if="status" class="mt-6 rounded-md bg-signal-wash px-4 py-3 text-sm font-medium text-signal" role="status">
            {{ status }}
        </p>

        <form class="mt-8 space-y-5" @submit.prevent="submit">
            <div>
                <InputLabel for="email" :value="$t('Email')" />
                <TextInput id="email" type="email" class="mt-1.5" v-model="form.email" required autofocus autocomplete="username" />
                <InputError :message="form.errors.email" />
            </div>

            <PrimaryButton class="w-full" :disabled="form.processing">{{ $t('Email me a reset link') }}</PrimaryButton>
            <Link :href="route('login')" class="block text-center text-sm text-ink-soft underline underline-offset-4 hover:text-ink">
                {{ $t('Back to log in') }}
            </Link>
        </form>
    </GuestLayout>
</template>
