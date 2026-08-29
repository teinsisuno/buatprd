<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
defineProps({ transaction: Object });
const formatRupiah = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n || 0);
</script>
<template>
    <Head :title="`Transaksi #${String(transaction.id).padStart(6,'0')}`" />
    <AdminLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('admin.transactions.index')" class="btn-ghost px-3 text-xs">← Kembali</Link>
                <h1 class="text-xl font-bold">Detail Transaksi #{{ String(transaction.id).padStart(6,'0') }}</h1>
                <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :style="transaction.status==='pending' ? 'background: var(--warning); color:white;' : transaction.status==='approved' ? 'background: var(--success); color:white;' : 'background: var(--danger); color:white;'">{{ transaction.status }}</span>
            </div>
        </template>
        <div class="container-base py-8 space-y-6">
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="card space-y-3">
                    <h3 class="font-semibold">Info User & Paket</h3>
                    <p class="text-sm"><b>{{ transaction.user?.name }}</b> — {{ transaction.user?.email }}</p>
                    <p class="text-sm">Membership saat ini: {{ transaction.user?.active_membership?.tier?.name || '—' }}</p>
                    <p class="text-sm">Paket dibeli: <b>{{ transaction.tier?.name || (transaction.type==='topup' ? 'Top Up' : '-') }}</b> • {{ transaction.type }}</p>
                    <p class="text-sm">Nominal: <b>{{ formatRupiah(transaction.amount) }}</b> <span v-if="transaction.discount_amount>0" style="color: var(--success);">-{{ formatRupiah(transaction.discount_amount) }} ({{ transaction.coupon_code }})</span> → Net {{ formatRupiah(transaction.amount - (transaction.discount_amount||0)) }}</p>
                    <p class="text-sm">Tanggal: {{ new Date(transaction.created_at).toLocaleString('id-ID') }}</p>
                    <p v-if="transaction.admin_note" class="text-sm rounded-lg p-3" style="background: var(--bg-elevated);">Catatan admin: {{ transaction.admin_note }}</p>
                    <div class="flex gap-2 pt-2">
                        <a v-if="transaction.proof_path" :href="route('admin.transactions.proof', transaction.id)" target="_blank" class="btn-primary text-xs">Lihat Bukti Full</a>
                        <a v-if="transaction.status==='approved'" :href="route('admin.transactions.invoice', transaction.id)" class="btn-ghost border text-xs" style="border-color: var(--border-soft);">Download Invoice</a>
                    </div>
                </div>
                <div class="card">
                    <h3 class="font-semibold">Bukti Transfer</h3>
                    <div v-if="transaction.proof_path" class="mt-4">
                        <img :src="route('admin.transactions.proof', transaction.id)" alt="Bukti" class="max-h-[480px] w-full object-contain rounded-xl border" style="border-color: var(--border-soft);" />
                    </div>
                    <p v-else class="mt-4 text-sm" style="color: var(--text-muted);">Tidak ada bukti.</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
