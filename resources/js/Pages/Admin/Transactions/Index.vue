<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
defineProps({ transactions: Object, filters: Object });
const formatRupiah = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n || 0);
const filterStatus = (s) => router.get(route('admin.transactions.index'), { status: s }, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Transaksi" />
    <AdminLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Transaksi & Keuangan</h1>
                    <p class="mt-1 text-sm" style="color: var(--text-muted);">Approve / Reject bukti transfer manual. Sumber revenue.</p>
                </div>
                <div class="flex gap-2">
                    <button @click="filterStatus('')" class="btn-ghost text-xs" :style="!filters.status ? 'background: var(--bg-elevated); color: var(--text-main);' : ''">Semua</button>
                    <button @click="filterStatus('pending')" class="btn-ghost text-xs">Pending</button>
                    <button @click="filterStatus('approved')" class="btn-ghost text-xs">Approved</button>
                </div>
            </div>
        </template>

        <div class="container-base py-8">
            <div class="card">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead><tr class="border-b text-xs" style="border-color: var(--border-soft); color: var(--text-soft);"><th class="py-3">User</th><th class="py-3">Paket</th><th class="py-3">Nominal</th><th class="py-3">Bukti</th><th class="py-3">Status</th><th class="py-3 text-right">Aksi</th></tr></thead>
                        <tbody>
                            <tr v-for="trx in transactions.data" :key="trx.id" class="border-b last:border-0" style="border-color: var(--border-soft);">
                                <td class="py-4">
                                    <p class="font-medium">{{ trx.user?.name }}</p>
                                    <p class="text-xs" style="color: var(--text-soft);">{{ trx.user?.email }}</p>
                                </td>
                                <td class="py-4">{{ trx.tier?.name || '-' }} <span class="text-xs" style="color: var(--text-soft);">({{ trx.type }})</span></td>
                                <td class="py-4 font-semibold">{{ formatRupiah(trx.amount) }}</td>
                                <td class="py-4">
                                    <span v-if="trx.proof_path" class="text-xs" style="color: var(--brand);">Lihat Bukti</span>
                                    <span v-else class="text-xs" style="color: var(--text-soft);">—</span>
                                </td>
                                <td class="py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :style="trx.status === 'pending' ? 'background: color-mix(in srgb, var(--warning) 14%, transparent); color: var(--warning);' : trx.status === 'approved' ? 'background: color-mix(in srgb, var(--success) 14%, transparent); color: var(--success);' : 'background: color-mix(in srgb, var(--danger) 14%, transparent); color: var(--danger);'">{{ trx.status }}</span></td>
                                <td class="py-4 text-right">
                                    <button v-if="trx.status === 'pending'" type="button" class="btn-primary px-3 py-1.5 text-xs">Approve</button>
                                    <span v-else class="text-xs" style="color: var(--text-soft);">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="transactions.data.length === 0" class="py-12 text-center">
                        <p class="font-semibold">Belum ada transaksi.</p>
                        <p class="mt-1 text-sm" style="color: var(--text-muted);">Transaksi muncul setelah member upload bukti transfer.</p>
                    </div>
                </div>
                <div v-if="transactions.links" class="mt-6 flex flex-wrap gap-2">
                    <Link v-for="link in transactions.links" :key="link.label" :href="link.url || '#'" :class="['rounded-lg px-3 py-1.5 text-xs', link.active ? 'btn-primary' : 'btn-ghost border']" style="border-color: var(--border-soft);" v-html="link.label" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
