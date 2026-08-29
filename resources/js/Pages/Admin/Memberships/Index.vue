<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head } from '@inertiajs/vue3';
defineProps({ tiers: Array, memberships: Object });
const formatRupiah = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n || 0);
</script>

<template>
    <Head title="Membership Management" />
    <AdminLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold">Membership & Paket</h1>
                <p class="mt-1 text-sm" style="color: var(--text-muted);">Kelola paket (Product Management) dan membership aktif member.</p>
            </div>
        </template>

        <div class="container-base py-8 space-y-8">
            <!-- Paket cards -->
            <div>
                <h2 class="font-semibold">Paket Management (Product)</h2>
                <p class="mt-1 text-sm" style="color: var(--text-muted);">Ini yang dijual ke member. Harga & limit bisa diubah tanpa deploy.</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div v-for="tier in tiers" :key="tier.id" class="card">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold">{{ tier.name }}</h3>
                            <span class="rounded-full px-2 py-1 text-xs font-bold" :style="tier.slug === 'premium' ? 'background: var(--brand); color: white;' : 'background: var(--bg-elevated); color: var(--text-muted);'">{{ tier.slug }}</span>
                        </div>
                        <p class="mt-2 text-2xl font-bold">{{ formatRupiah(tier.price) }}<span class="text-sm font-normal" style="color: var(--text-soft);"> / {{ tier.duration_days }} hari</span></p>
                        <ul class="mt-4 space-y-1.5 text-sm">
                            <li v-for="f in tier.features" :key="f" class="flex items-center gap-2"><span style="color: var(--success);">✓</span>{{ f }}</li>
                        </ul>
                        <div class="mt-4 grid grid-cols-2 gap-2 text-xs" style="color: var(--text-soft);">
                            <span>Max Project: {{ tier.limits.max_projects === 9999 ? '∞' : tier.limits.max_projects }}</span>
                            <span>AI: {{ tier.limits.max_ai_per_month }}/bln</span>
                        </div>
                        <button type="button" class="btn-secondary mt-4 w-full text-xs">Edit Paket</button>
                    </div>
                </div>
            </div>

            <!-- Membership table -->
            <div class="card">
                <h3 class="font-semibold">Membership Aktif</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead><tr class="border-b text-xs" style="border-color: var(--border-soft); color: var(--text-soft);"><th class="py-3">User</th><th class="py-3">Tier</th><th class="py-3">Kuota</th><th class="py-3">Expired</th><th class="py-3">Status</th></tr></thead>
                        <tbody>
                            <tr v-for="m in memberships.data" :key="m.id" class="border-b last:border-0" style="border-color: var(--border-soft);">
                                <td class="py-3 font-medium">{{ m.user?.name }}</td>
                                <td class="py-3"><span class="rounded-full px-2 py-1 text-xs font-semibold" style="background: var(--bg-elevated);">{{ m.tier?.name }}</span></td>
                                <td class="py-3 text-xs">{{ m.ai_quota_used }}/{{ m.ai_quota_total }} + {{ m.credit_balance }} kredit</td>
                                <td class="py-3 text-xs" style="color: var(--text-soft);">{{ m.expired_at ? new Date(m.expired_at).toLocaleDateString('id-ID') : '-' }}</td>
                                <td class="py-3"><span class="rounded-full px-2 py-1 text-xs font-semibold" :style="m.status === 'active' ? 'background: color-mix(in srgb, var(--success) 12%, transparent); color: var(--success);' : 'background: color-mix(in srgb, var(--danger) 12%, transparent); color: var(--danger);'">{{ m.status }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
