<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="$t('Choose a new password')" />

        <h1 class="text-2xl font-bold">{{ $t('Choose a new password') }}</h1>

        <form class="mt-8 space-y-5" @submit.prevent="submit">
            <div>
                <InputLabel for="email" :value="$t('Email')" />
                <TextInput id="email" type="email" class="mt-1.5" v-model="form.email" required autofocus autocomplete="username" />
                <InputError :message="form.errors.email" />
            </div>
            <div>
                <InputLabel for="password" :value="$t('New password')" />
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
            <PrimaryButton class="w-full" :disabled="form.processing">{{ $t('Save new password') }}</PrimaryButton>
        </form>
    </GuestLayout>
</template>
