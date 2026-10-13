<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="$t('Confirm your password')" />

        <h1 class="text-2xl font-bold">{{ $t('Confirm your password') }}</h1>
        <p class="mt-2 text-ink-soft">{{ $t('Enter your password again to continue.') }}</p>

        <form class="mt-8 space-y-5" @submit.prevent="submit">
            <div>
                <InputLabel for="password" :value="$t('Password')" />
                <TextInput id="password" type="password" class="mt-1.5" v-model="form.password" required autocomplete="current-password" autofocus />
                <InputError :message="form.errors.password" />
            </div>
            <PrimaryButton class="w-full" :disabled="form.processing">{{ $t('Confirm') }}</PrimaryButton>
        </form>
    </GuestLayout>
</template>
