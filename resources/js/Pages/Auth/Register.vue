<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    company_name: '',
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="$t('Start free trial')" />

        <h1 class="text-2xl font-bold">{{ $t('Start your 14-day free trial') }}</h1>
        <p class="mt-2 text-ink-soft">
            {{ $t('No card, no sales call.') }}
            {{ $t('Already have an account?') }}
            <Link :href="route('login')" class="font-semibold text-ink underline underline-offset-4">{{ $t('Log in') }}</Link>
        </p>

        <form class="mt-8 space-y-5" @submit.prevent="submit">
            <div>
                <InputLabel for="company_name" :value="$t('Company')" />
                <TextInput id="company_name" type="text" class="mt-1.5" v-model="form.company_name" required autofocus autocomplete="organization" />
                <InputError :message="form.errors.company_name" />
            </div>

            <div>
                <InputLabel for="name" :value="$t('Your name')" />
                <TextInput id="name" type="text" class="mt-1.5" v-model="form.name" required autocomplete="name" />
                <InputError :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="email" :value="$t('Work email')" />
                <TextInput id="email" type="email" class="mt-1.5" v-model="form.email" required autocomplete="username" />
                <InputError :message="form.errors.email" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <InputLabel for="password" :value="$t('Password')" />
                    <TextInput id="password" type="password" class="mt-1.5" v-model="form.password" required autocomplete="new-password" />
                    <InputError :message="form.errors.password" />
                </div>
                <div>
                    <InputLabel for="password_confirmation" :value="$t('Confirm password')" />
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        class="mt-1.5"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>
            </div>

            <PrimaryButton class="w-full" :disabled="form.processing">{{ $t('Start free trial') }}</PrimaryButton>
        </form>
    </GuestLayout>
</template>
