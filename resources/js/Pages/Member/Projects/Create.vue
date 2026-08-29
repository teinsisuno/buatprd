<script setup>
import MemberLayout from '@/Layouts/MemberLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Buat PRD" />
    <MemberLayout>
        <template #header>
            <h1 class="text-2xl font-bold">Buat PRD Baru</h1>
            <p class="mt-1 text-sm" style="color: var(--text-muted);">Wizard 8 Langkah akan hadir di Fase C. Sekarang placeholder.</p>
        </template>
        <div class="container-base py-12">
            <div class="card py-16 text-center">
                <h2 class="text-xl font-bold">Wizard 8 Langkah — Coming Soon di Fase C</h2>
                <p class="mx-auto mt-2 max-w-lg text-sm" style="color: var(--text-muted);">Langkah 1: Problem & Vision → Langkah 8: Output & Versioning. Semua dengan AI per langkah sesuai IDEA.md.</p>
                <p class="mt-4 text-xs" style="color: var(--text-soft);">PRD: docs/PRD-BUATPRD-PRODUCTION.md Bab 5.3</p>
            </div>
        </div>
    </MemberLayout>
</template>
