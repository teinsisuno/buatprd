<script setup>
import MemberLayout from '@/Layouts/MemberLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>
<template>
    <Head title="Billing" />
    <MemberLayout>
        <template #header><h1 class="text-2xl font-bold">Langganan & Billing</h1><p class="mt-1 text-sm" style="color: var(--text-muted);">Upgrade paket, lihat invoice, histori pembayaran.</p></template>
        <div class="container-base py-12"><div class="card py-16 text-center"><h2 class="font-bold">Billing — Coming Soon Fase B</h2><p class="mt-2 text-sm" style="color: var(--text-muted);">Flow transfer manual + upload bukti + approve admin.</p></div></div>
    </MemberLayout>
</template>
