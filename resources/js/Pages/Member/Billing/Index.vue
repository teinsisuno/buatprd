<script setup>
import MemberLayout from '@/Layouts/MemberLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    membership: Object,
    tiers: Array,
    transactions: Object,
    payment: Object,
});

const formatRupiah = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n || 0);
const isCurrentTier = (tier) => props.membership?.tier?.id === tier.id;
const statusStyle = (s) => s==='pending' ? 'background: color-mix(in srgb, var(--warning) 14%, transparent); color: var(--warning);' : s==='approved' ? 'background: color-mix(in srgb, var(--success) 14%, transparent); color: var(--success);' : 'background: color-mix(in srgb, var(--danger) 14%, transparent); color: var(--danger);';
</script>

<template>
    <Head title="Billing" />
    <MemberLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-bold">Langganan & Billing</h1>
                <p class="mt-1 text-sm" style="color: var(--text-muted);">Upgrade paket, lihat invoice, histori pembayaran transfer manual.</p>
            </div>
        </template>

        <div class="container-base py-8 space-y-8">
            <!-- Paket Saya -->
            <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                <div class="card" style="background: linear-gradient(135deg, color-mix(in srgb, var(--brand) 8%, var(--bg-card)), var(--bg-card)); border-color: color-mix(in srgb, var(--brand) 20%, var(--border-soft));">
                    <p class="text-xs font-bold uppercase tracking-widest" style="color: var(--brand);">Paket Saya</p>
                    <div v-if="membership?.tier">
                        <h2 class="mt-2 text-2xl font-bold">{{ membership.tier.name }} <span class="text-sm font-normal" style="color: var(--text-soft);">/ {{ membership.tier.slug }}</span></h2>
                        <p class="mt-1 text-sm" style="color: var(--text-muted);">{{ formatRupiah(membership.tier.price) }} / 30 hari • {{ membership.tier.limits?.max_ai_per_month }} AI / bulan • {{ membership.tier.limits?.max_projects === 9999 ? '∞' : membership.tier.limits?.max_projects }} project</p>
                        <div class="mt-4 grid grid-cols-3 gap-3 text-center">
                            <div class="rounded-xl p-3" style="background: var(--bg-elevated);"><p class="text-xs" style="color: var(--text-soft);">Sisa AI</p><p class="text-lg font-bold">{{ membership.ai_quota_total - membership.ai_quota_used }} <span class="text-xs font-normal">/ {{ membership.ai_quota_total }}</span></p></div>
                            <div class="rounded-xl p-3" style="background: var(--bg-elevated);"><p class="text-xs" style="color: var(--text-soft);">Kredit</p><p class="text-lg font-bold">{{ membership.credit_balance }}</p></div>
                            <div class="rounded-xl p-3" style="background: var(--bg-elevated);"><p class="text-xs" style="color: var(--text-soft);">Expired</p><p class="text-xs font-semibold">{{ membership.expired_at ? new Date(membership.expired_at).toLocaleDateString('id-ID') : '-' }}</p><p class="text-xs" :style="membership.is_expired ? 'color: var(--danger);' : 'color: var(--success);'">{{ membership.is_expired ? 'Expired' : membership.status }}</p></div>
                        </div>
                        <div v-if="membership.is_expired" class="mt-4 rounded-xl px-4 py-3 text-sm" style="background: color-mix(in srgb, var(--danger) 10%, transparent); color: var(--danger); border: 1px solid color-mix(in srgb, var(--danger) 20%, transparent);">Paket expired — perpanjang di bawah untuk lanjutkan akses premium.</div>
                    </div>
                    <div v-else class="mt-4 text-sm" style="color: var(--text-muted);">Belum ada membership aktif.</div>
                    <div class="mt-4 flex gap-2">
                        <Link :href="route('member.usage.index')" class="btn-ghost text-xs border" style="border-color: var(--border-soft);">Lihat Penggunaan</Link>
                    </div>
                </div>

                <div class="card">
                    <h3 class="font-semibold">Instruksi Pembayaran</h3>
                    <p class="mt-1 text-xs" style="color: var(--text-soft);">{{ payment.instructions }}</p>
                    <div class="mt-4 space-y-3">
                        <div class="rounded-xl border p-4" style="border-color: var(--border-soft); background: var(--bg-elevated);">
                            <p class="text-xs font-bold" style="color: var(--text-soft);">{{ payment.bank_name }}</p>
                            <p class="font-mono text-lg font-bold tracking-widest">{{ payment.bank_number }}</p>
                            <p class="text-xs" style="color: var(--text-muted);">a.n. {{ payment.bank_holder }}</p>
                        </div>
                        <div class="rounded-xl border p-4" style="border-color: var(--border-soft);">
                            <p class="text-xs font-bold" style="color: var(--text-soft);">{{ payment.bank_name_2 }}</p>
                            <p class="font-mono text-lg font-bold tracking-widest">{{ payment.bank_number_2 }}</p>
                            <p class="text-xs" style="color: var(--text-muted);">a.n. {{ payment.bank_holder_2 }}</p>
                        </div>
                    </div>
                    <p class="mt-3 text-xs" style="color: var(--text-soft);">Simpan bukti transfer (JPG/PNG max 2MB). Verifikasi 1x24 jam oleh admin.</p>
                </div>
            </div>

            <!-- Pricing Table -->
            <div>
                <h2 class="font-bold text-lg">Upgrade Paket</h2>
                <p class="mt-1 text-sm" style="color: var(--text-muted);">Pilih paket → instruksi transfer → upload bukti → tunggu approve admin (tier naik otomatis).</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div v-for="tier in tiers" :key="tier.id" class="card relative flex flex-col" :style="tier.slug==='standart' ? 'border-color: var(--brand); background: linear-gradient(180deg, color-mix(in srgb, var(--brand) 6%, var(--bg-card)), var(--bg-card));' : ''">
                        <div v-if="tier.slug==='standart'" class="absolute -top-3 left-1/2 -translate-x-1/2 rounded-full px-3 py-1 text-xs font-bold" style="background: var(--brand); color: white;">Best Seller</div>
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold">{{ tier.name }}</h3>
                            <span v-if="isCurrentTier(tier)" class="rounded-full px-2 py-1 text-xs font-bold" style="background: var(--success); color: white;">Aktif</span>
                            <span v-else class="rounded-full px-2 py-1 text-xs font-bold" style="background: var(--bg-elevated); color: var(--text-soft);">{{ tier.slug }}</span>
                        </div>
                        <p class="mt-3 text-2xl font-bold">{{ formatRupiah(tier.price) }} <span class="text-sm font-normal" style="color: var(--text-soft);">/ {{ tier.duration_days }} hari</span></p>
                        <ul class="mt-4 flex-1 space-y-1.5 text-sm">
                            <li v-for="f in tier.features" :key="f" class="flex gap-2"><span style="color: var(--success);">✓</span><span>{{ f }}</span></li>
                        </ul>
                        <div class="mt-4 text-xs rounded-lg p-2.5" style="background: var(--bg-elevated); color: var(--text-soft);">
                            {{ tier.limits.max_projects === 9999 ? '∞' : tier.limits.max_projects }} project • {{ tier.limits.max_ai_per_month }} AI • {{ tier.limits.can_export_pdf ? 'PDF ✓' : 'PDF —' }} • {{ tier.limits.can_mermaid ? 'Mermaid ✓' : '' }}
                        </div>
                        <div class="mt-4">
                            <Link v-if="tier.slug==='default'" href="#" class="btn-ghost w-full justify-center text-xs opacity-50 cursor-not-allowed">Gratis — Sudah Default</Link>
                            <Link v-else-if="isCurrentTier(tier)" href="#" class="btn-ghost w-full justify-center text-xs border" style="border-color: var(--border-soft);">Paket Aktif</Link>
                            <Link v-else :href="route('member.billing.checkout', tier.id)" class="btn-primary w-full justify-center text-sm" :style="tier.slug==='standart' ? '' : 'background: var(--bg-elevated); color: var(--text-main); border: 1px solid var(--border-soft);'">Pilih {{ tier.name }} →</Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Riwayat Tagihan -->
            <div class="card">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold">Riwayat Tagihan</h3>
                    <span class="text-xs" style="color: var(--text-soft);">{{ transactions.total }} transaksi</span>
                </div>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead><tr class="border-b text-xs" style="border-color: var(--border-soft); color: var(--text-soft);"><th class="py-3">Tanggal</th><th class="py-3">Paket / Tipe</th><th class="py-3">Nominal</th><th class="py-3">Status</th><th class="py-3 text-right">Aksi</th></tr></thead>
                        <tbody>
                            <tr v-for="trx in transactions.data" :key="trx.id" class="border-b last:border-0" style="border-color: var(--border-soft);">
                                <td class="py-3 text-xs"><p>{{ new Date(trx.created_at).toLocaleDateString('id-ID') }}</p><p class="font-mono text-xs" style="color: var(--text-soft);">#{{ String(trx.id).padStart(6,'0') }}</p></td>
                                <td class="py-3"><p class="font-medium">{{ trx.tier?.name || (trx.type==='topup' ? 'Top Up' : '-') }}</p><p class="text-xs" style="color: var(--text-soft);">{{ trx.type }} <span v-if="trx.coupon_code" class="font-mono text-xs rounded px-1" style="background: var(--bg-elevated);">{{ trx.coupon_code }}</span></p></td>
                                <td class="py-3"><p class="font-semibold">{{ formatRupiah(trx.amount) }}</p><p v-if="trx.discount_amount>0" class="text-xs" style="color: var(--success);">-{{ formatRupiah(trx.discount_amount) }}</p></td>
                                <td class="py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :style="statusStyle(trx.status)">{{ trx.status }}</span><p v-if="trx.admin_note" class="mt-1 text-xs max-w-[20ch] truncate" style="color: var(--text-soft);">{{ trx.admin_note }}</p></td>
                                <td class="py-3 text-right">
                                    <a v-if="trx.proof_path" :href="route('member.transactions.proof', trx.id)" target="_blank" class="text-xs underline mr-3" style="color: var(--brand);">Bukti</a>
                                    <a v-if="trx.status==='approved'" :href="route('member.transactions.invoice', trx.id)" target="_blank" class="text-xs font-semibold underline" style="color: var(--success);">Invoice</a>
                                    <span v-else-if="trx.status==='pending'" class="text-xs" style="color: var(--warning);">Menunggu</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="transactions.data.length===0" class="py-10 text-center text-sm" style="color: var(--text-muted);">Belum ada transaksi. Pilih paket di atas untuk mulai.</div>
                </div>
                <div v-if="transactions.links && transactions.data.length>0" class="mt-4 flex flex-wrap gap-2">
                    <Link v-for="link in transactions.links" :key="link.label" :href="link.url || '#'" :class="['rounded-lg px-3 py-1.5 text-xs border', link.active ? 'btn-primary border-transparent' : 'btn-ghost']" style="border-color: var(--border-soft);" v-html="link.label" />
                </div>
            </div>
        </div>
    </MemberLayout>
</template>
