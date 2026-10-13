<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="$t('Log in')" />

        <h1 class="text-2xl font-bold">{{ $t('Log in to SafeBrief') }}</h1>
        <p class="mt-2 text-ink-soft">
            {{ $t('New here?') }}
            <Link :href="route('register')" class="font-semibold text-ink underline underline-offset-4">
                {{ $t('Start a free trial') }}
            </Link>
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

            <div>
                <div class="flex items-baseline justify-between">
                    <InputLabel for="password" :value="$t('Password')" />
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-sm text-ink-soft underline underline-offset-4 hover:text-ink"
                    >
                        {{ $t('Forgot your password?') }}
                    </Link>
                </div>
                <TextInput id="password" type="password" class="mt-1.5" v-model="form.password" required autocomplete="current-password" />
                <InputError :message="form.errors.password" />
            </div>

            <label class="flex items-center gap-2.5">
                <Checkbox name="remember" v-model:checked="form.remember" />
                <span class="text-sm text-ink-soft">{{ $t('Remember me') }}</span>
            </label>

            <PrimaryButton class="w-full" :disabled="form.processing">{{ $t('Log in') }}</PrimaryButton>
        </form>
    </GuestLayout>
</template>
