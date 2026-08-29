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
        <Head title="Konfirmasi Password" />

        <div class="mb-8">
            <p class="text-sm font-medium" style="color: var(--brand);">Pemeriksaan keamanan</p>
            <h1 class="mt-2 text-2xl font-bold">Konfirmasi password Anda</h1>
        </div>

        <div class="mb-5 text-sm leading-relaxed" style="color: var(--text-muted);">
            Ini adalah area aman. Konfirmasi password sebelum melanjutkan.
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="password" value="Password saat ini" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 flex justify-end">
                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Lanjutkan
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
