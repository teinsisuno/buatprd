<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
defineProps({ title: String });
</script>

<template>
    <Head :title="title" />
    <AdminLayout>
        <template #header>
            <h1 class="text-2xl font-bold">{{ title }}</h1>
            <p class="mt-1 text-sm" style="color: var(--text-muted);">Fitur ini akan hadir di Fase B-C. Struktur Production sudah siap, tinggal isi logic.</p>
        </template>
        <div class="container-base py-12">
            <div class="card py-16 text-center">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl" style="background: color-mix(in srgb, var(--brand) 12%, transparent); color: var(--brand);">🚧</div>
                <h2 class="mt-4 text-xl font-bold">{{ title }} — Coming Soon</h2>
                <p class="mx-auto mt-2 max-w-md text-sm" style="color: var(--text-muted);">Halaman ini sudah terdaftar di route Production. Implementasi detail menyusul setelah Fondasi & Transaksi beres.</p>
                <p class="mt-6 text-xs" style="color: var(--text-soft);">PRD: docs/PRD-BUATPRD-PRODUCTION.md Bab 6</p>
            </div>
        </div>
    </AdminLayout>
</template>
