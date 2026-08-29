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

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head title="Verifikasi Email" />

        <div class="mb-8">
            <p class="text-sm font-medium" style="color: var(--brand);">Satu langkah lagi</p>
            <h1 class="mt-2 text-2xl font-bold">Verifikasi email Anda</h1>
        </div>

        <div class="mb-5 text-sm leading-relaxed" style="color: var(--text-muted);">
            Terima kasih sudah mendaftar. Klik tautan yang kami kirim ke email Anda untuk mulai menggunakan BuatPRD.
        </div>

        <div
            class="mb-4 rounded-lg border px-3 py-2 text-sm font-medium"
            style="border-color: color-mix(in srgb, var(--success) 30%, var(--border-soft)); color: var(--success); background-color: color-mix(in srgb, var(--success) 8%, transparent);"
            v-if="verificationLinkSent"
        >
            Tautan verifikasi baru telah dikirim ke email pendaftaran Anda.
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex items-center justify-between">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Kirim ulang email verifikasi
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-sm font-medium underline-offset-4 hover:underline"
                    style="color: var(--text-muted);"
                    >Keluar</Link
                >
            </div>
        </form>
    </GuestLayout>
</template>
