<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ transactions: Object, filters: Object, stats: Object });
const formatRupiah = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n || 0);
const filterStatus = (s) => router.get(route('admin.transactions.index'), { status: s || undefined, type: props.filters.type || undefined }, { preserveState: true, replace: true });
const filterType = (t) => router.get(route('admin.transactions.index'), { status: props.filters.status || undefined, type: t || undefined }, { preserveState: true, replace: true });

const showApprove = ref(null);
const showReject = ref(null);
const approveForm = useForm({ admin_note: '' });
const rejectForm = useForm({ admin_note: '' });

const openApprove = (trx) => { showApprove.value = trx; approveForm.admin_note = ''; approveForm.clearErrors(); };
const openReject = (trx) => { showReject.value = trx; rejectForm.admin_note = ''; rejectForm.clearErrors(); };
const closeModals = () => { showApprove.value = null; showReject.value = null; };

const doApprove = () => {
    approveForm.post(route('admin.transactions.approve', showApprove.value.id), { preserveScroll: true, onSuccess: () => closeModals() });
};
const doReject = () => {
    rejectForm.post(route('admin.transactions.reject', showReject.value.id), { preserveScroll: true, onSuccess: () => closeModals() });
};
const statusStyle = (s) => s === 'pending' ? 'background: color-mix(in srgb, var(--warning) 14%, transparent); color: var(--warning);' : s === 'approved' ? 'background: color-mix(in srgb, var(--success) 14%, transparent); color: var(--success);' : 'background: color-mix(in srgb, var(--danger) 14%, transparent); color: var(--danger);';
</script>

<template>
    <Head title="Transaksi" />
    <AdminLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Transaksi & Keuangan</h1>
                    <p class="mt-1 text-sm" style="color: var(--text-muted);">Approve / Reject bukti transfer manual. Revenue dihitung dari approved saja.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="rounded-full px-3 py-1.5 text-xs font-semibold" style="background: var(--bg-elevated);">{{ formatRupiah(stats?.revenue || 0) }} revenue</span>
                    <span class="rounded-full px-3 py-1.5 text-xs font-semibold" :style="statusStyle('pending')">{{ stats?.pending || 0 }} pending</span>
                </div>
            </div>
        </template>

        <div class="container-base py-8 space-y-6">
            <!-- Stats + filters -->
            <div class="card">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex gap-2">
                        <button @click="filterStatus('')" class="btn-ghost text-xs" :style="!filters.status ? 'background: var(--bg-elevated); color: var(--text-main); border-color: var(--border-soft);' : ''">Semua</button>
                        <button @click="filterStatus('pending')" class="btn-ghost text-xs" :style="filters.status==='pending' ? 'background: var(--warning); color: white;' : ''">Pending</button>
                        <button @click="filterStatus('approved')" class="btn-ghost text-xs" :style="filters.status==='approved' ? 'background: var(--success); color: white;' : ''">Approved</button>
                        <button @click="filterStatus('rejected')" class="btn-ghost text-xs border" :style="filters.status==='rejected' ? 'background: var(--danger); color: white;' : ''">Rejected</button>
                    </div>
                    <div class="flex gap-2">
                        <button @click="filterType('')" class="btn-ghost text-xs" :style="!filters.type ? 'background: var(--bg-elevated);' : ''">Semua Tipe</button>
                        <button @click="filterType('subscription')" class="btn-ghost text-xs" :style="filters.type==='subscription' ? 'background: var(--brand); color: white;' : ''">Subscription</button>
                        <button @click="filterType('topup')" class="btn-ghost text-xs" :style="filters.type==='topup' ? 'background: var(--brand); color: white;' : ''">Top Up</button>
                    </div>
                </div>
            </div>

            <div class="card p-0 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead><tr class="border-b text-xs" style="border-color: var(--border-soft); color: var(--text-soft); background: var(--bg-elevated);"><th class="py-3 px-4">User</th><th class="py-3">Tipe / Paket</th><th class="py-3">Nominal</th><th class="py-3">Bukti</th><th class="py-3">Status</th><th class="py-3 text-right px-4">Aksi</th></tr></thead>
                        <tbody>
                            <tr v-for="trx in transactions.data" :key="trx.id" class="border-b last:border-0 hover:bg-white/[0.02]" style="border-color: var(--border-soft);">
                                <td class="py-4 px-4">
                                    <p class="font-medium">{{ trx.user?.name }}</p>
                                    <p class="text-xs" style="color: var(--text-soft);">{{ trx.user?.email }}</p>
                                    <p class="text-xs font-mono" style="color: var(--text-soft);">#{{ String(trx.id).padStart(6,'0') }} • {{ new Date(trx.created_at).toLocaleDateString('id-ID') }}</p>
                                </td>
                                <td class="py-4">
                                    <p class="font-medium">{{ trx.tier?.name || (trx.type==='topup' ? 'Top Up' : '-') }}</p>
                                    <p class="text-xs" style="color: var(--text-soft);">{{ trx.type }} <span v-if="trx.coupon_code" class="rounded px-1 py-0.5 text-[10px] font-bold" style="background: var(--bg-elevated);">{{ trx.coupon_code }}</span></p>
                                </td>
                                <td class="py-4">
                                    <p class="font-semibold">{{ formatRupiah(trx.amount) }}</p>
                                    <p v-if="trx.discount_amount>0" class="text-xs" style="color: var(--success);">-{{ formatRupiah(trx.discount_amount) }} diskon</p>
                                    <p class="text-xs font-semibold">{{ formatRupiah(trx.amount - (trx.discount_amount||0)) }} net</p>
                                </td>
                                <td class="py-4">
                                    <a v-if="trx.proof_path" :href="route('admin.transactions.proof', trx.id)" target="_blank" class="text-xs font-semibold underline" style="color: var(--brand);">Lihat Bukti</a>
                                    <span v-else class="text-xs" style="color: var(--text-soft);">—</span>
                                </td>
                                <td class="py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :style="statusStyle(trx.status)">{{ trx.status }}</span><p v-if="trx.admin_note" class="mt-1 max-w-[16ch] truncate text-xs" style="color: var(--text-soft);">{{ trx.admin_note }}</p></td>
                                <td class="py-4 text-right px-4">
                                    <div v-if="trx.status==='pending'" class="flex justify-end gap-2">
                                        <button @click="openApprove(trx)" class="btn-primary px-3 py-1.5 text-xs">Approve</button>
                                        <button @click="openReject(trx)" class="btn-ghost border px-3 py-1.5 text-xs" style="border-color: var(--border-soft); color: var(--danger);">Reject</button>
                                    </div>
                                    <div v-else class="flex justify-end gap-2">
                                        <a :href="route('admin.transactions.invoice', trx.id)" class="text-xs underline" style="color: var(--brand);">Invoice</a>
                                        <span class="text-xs" style="color: var(--text-soft);">by {{ trx.approver?.name || '-' }}</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="transactions.data.length === 0" class="py-12 text-center">
                        <p class="font-semibold">Belum ada transaksi.</p>
                        <p class="mt-1 text-sm" style="color: var(--text-muted);">Transaksi muncul setelah member upload bukti transfer.</p>
                    </div>
                </div>
                <div v-if="transactions.links && transactions.data.length>0" class="flex flex-wrap gap-2 p-4 border-t" style="border-color: var(--border-soft);">
                    <Link v-for="link in transactions.links" :key="link.label" :href="link.url || '#'" :class="['rounded-lg px-3 py-1.5 text-xs border', link.active ? 'btn-primary border-transparent' : 'btn-ghost']" style="border-color: var(--border-soft);" v-html="link.label" />
                </div>
            </div>
        </div>

        <!-- Approve modal -->
        <div v-if="showApprove" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModals"></div>
            <div class="relative w-full max-w-lg rounded-2xl border p-6 shadow-2xl" style="background: var(--bg-card); border-color: var(--border-soft);">
                <h3 class="font-bold">Approve Transaksi #{{ String(showApprove.id).padStart(6,'0') }}</h3>
                <p class="mt-1 text-sm" style="color: var(--text-muted);">{{ showApprove.user?.name }} • {{ showApprove.tier?.name || showApprove.type }} • {{ formatRupiah(showApprove.amount) }}</p>
                <div class="mt-4 rounded-xl p-3 text-sm" style="background: color-mix(in srgb, var(--success) 8%, transparent); border: 1px solid color-mix(in srgb, var(--success) 20%, transparent);">
                    Tier akan aktif 30 hari, kuota reset. Top up akan tambah kredit.
                </div>
                <form @submit.prevent="doApprove" class="mt-5 space-y-4">
                    <div>
                        <label class="text-xs font-semibold">Catatan Admin (opsional)</label>
                        <textarea v-model="approveForm.admin_note" rows="3" class="input-base mt-1 w-full" placeholder="Terima kasih, pembayaran diverifikasi..."></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" @click="closeModals" class="btn-ghost flex-1">Batal</button>
                        <button type="submit" :disabled="approveForm.processing" class="btn-primary flex-1 bg-green-600 hover:bg-green-700">{{ approveForm.processing ? 'Memproses...' : 'Ya, Approve' }}</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reject modal -->
        <div v-if="showReject" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModals"></div>
            <div class="relative w-full max-w-lg rounded-2xl border p-6 shadow-2xl" style="background: var(--bg-card); border-color: var(--border-soft);">
                <h3 class="font-bold">Reject Transaksi #{{ String(showReject.id).padStart(6,'0') }}</h3>
                <p class="mt-1 text-sm" style="color: var(--text-muted);">Harus isi alasan — member akan lihat di riwayat.</p>
                <form @submit.prevent="doReject" class="mt-5 space-y-4">
                    <div>
                        <label class="text-xs font-semibold">Alasan Reject *</label>
                        <textarea v-model="rejectForm.admin_note" rows="3" required class="input-base mt-1 w-full" placeholder="Bukti tidak jelas / nominal tidak sesuai..."></textarea>
                        <p v-if="rejectForm.errors.admin_note" class="mt-1 text-xs" style="color: var(--danger);">{{ rejectForm.errors.admin_note }}</p>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" @click="closeModals" class="btn-ghost flex-1">Batal</button>
                        <button type="submit" :disabled="rejectForm.processing" class="btn-primary flex-1" style="background: var(--danger);">{{ rejectForm.processing ? 'Memproses...' : 'Reject' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
