<script setup>
import { ref } from 'vue';
import MemberLayout from '@/Layouts/MemberLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
const form = useForm({ title:'', description:'' });
const submit = () => form.post(route('member.projects.store'));
</script>
<template>
    <Head title="Buat PRD" />
    <MemberLayout>
        <template #header>
            <h1 class="text-2xl font-bold">Buat PRD Baru</h1>
            <p class="mt-1 text-sm" style="color: var(--text-muted);">Mulai Wizard 8 Langkah — L1 Problem & Vision (chat AI) siap.</p>
        </template>
        <div class="container-base py-8 max-w-2xl">
            <div class="card">
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="text-xs font-semibold">Judul Project *</label>
                        <input v-model="form.title" class="input-base mt-1" placeholder="ex: Kasir UMKM Offline-First" required maxlength="120" />
                        <p v-if="form.errors.title" class="mt-1 text-xs" style="color: var(--danger);">{{ form.errors.title }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold">Deskripsi singkat</label>
                        <textarea v-model="form.description" rows="3" class="input-base mt-1" placeholder="1-2 kalimat ide kasar…" maxlength="500"></textarea>
                        <p v-if="form.errors.description" class="mt-1 text-xs" style="color: var(--danger);">{{ form.errors.description }}</p>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="btn-primary" :disabled="form.processing">{{ form.processing ? 'Membuat…' : 'Buat & Masuk Wizard →' }}</button>
                    </div>
                    <p class="text-xs" style="color: var(--text-soft);">Limit project sesuai tier. L1-L3 langsung bisa chat AI setelah buat.</p>
                </form>
            </div>
        </div>
    </MemberLayout>
</template>
