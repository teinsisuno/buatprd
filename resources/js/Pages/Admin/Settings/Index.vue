<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
const props = defineProps({ settings: Array, groups: Object });
</script>
<template>
    <Head title="Pengaturan Sistem" />
    <AdminLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold">Pengaturan Sistem</h1>
                <p class="mt-1 text-sm" style="color: var(--text-muted);">Kelola pengaturan umum, pembayaran, dan AI. Hanya Superadmin.</p>
            </div>
        </template>
        <div class="container-base py-8 space-y-6">
            <div class="grid gap-4 sm:grid-cols-3">
                <Link :href="route('admin.ai-providers.index')" class="card hover:border-brand/50">
                    <h3 class="font-semibold">AI Providers</h3>
                    <p class="mt-1 text-sm" style="color: var(--text-muted);">Multi provider, fetch model, pilih yang aktif. Wizard member pakai ini.</p>
                    <p class="mt-3 text-xs font-semibold" style="color: var(--brand);">Kelola →</p>
                </Link>
                <div class="card">
                    <h3 class="font-semibold">Pembayaran</h3>
                    <p class="mt-1 text-sm" style="color: var(--text-muted);">Rekening BCA/Mandiri ada di <code>config/buatprd.php</code>. Fase D akan pindah ke DB settings.</p>
                    <p class="mt-3 text-xs" style="color: var(--text-soft);">Saat ini 2 rekening aktif.</p>
                </div>
                <div class="card">
                    <h3 class="font-semibold">Umum & Keamanan</h3>
                    <p class="mt-1 text-sm" style="color: var(--text-muted);">Nama app, logo, maintenance mode — coming soon Fase D.</p>
                </div>
            </div>

            <div class="card">
                <h3 class="font-semibold">Semua Setting (DB)</h3>
                <p class="mt-1 text-xs" style="color: var(--text-soft);">{{ settings.length }} baris di tabel settings</p>
                <div v-if="settings.length===0" class="mt-4 rounded-xl p-6 text-center text-sm" style="background: var(--bg-elevated); color: var(--text-muted);">Belum ada setting custom. AI Providers disimpan di tabel terpisah <code>ai_providers</code> (ter-encrypt).</div>
                <div v-else class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead><tr class="border-b text-xs" style="border-color: var(--border-soft); color: var(--text-soft);"><th class="py-2">Key</th><th class="py-2">Group</th><th class="py-2">Value</th></tr></thead>
                        <tbody>
                            <tr v-for="s in settings" :key="s.id" class="border-b" style="border-color: var(--border-soft);">
                                <td class="py-2 font-mono text-xs">{{ s.key }}</td>
                                <td class="py-2 text-xs">{{ s.group }}</td>
                                <td class="py-2 text-xs font-mono" style="color: var(--text-soft);">{{ s.is_encrypted ? '•••••• (encrypted)' : (s.value?.slice(0,60) || '—') }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
