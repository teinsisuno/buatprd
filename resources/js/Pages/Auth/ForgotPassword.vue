<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

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
        <Head title="Lupa Password" />

        <div class="mb-8">
            <p class="text-sm font-medium" style="color: var(--brand);">Pemulihan akun</p>
            <h1 class="mt-2 text-2xl font-bold">Atur ulang password</h1>
        </div>

        <div class="mb-5 text-sm leading-relaxed" style="color: var(--text-muted);">
            Masukkan email Anda dan kami akan mengirimkan tautan untuk membuat password baru.
        </div>

        <div
            v-if="status"
            class="mb-4 rounded-lg border px-3 py-2 text-sm font-medium"
            style="border-color: color-mix(in srgb, var(--success) 30%, var(--border-soft)); color: var(--success); background-color: color-mix(in srgb, var(--success) 8%, transparent);"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                    <InputLabel for="email" value="Email kerja" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Kirim tautan reset
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
