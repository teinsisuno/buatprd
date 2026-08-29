<script setup>
import MemberLayout from '@/Layouts/MemberLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    membership: Object,
    options: Array,
    payment: Object,
    transactions: Object,
});

const formatRupiah = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n || 0);
const statusStyle = (s) => s==='pending' ? 'background: color-mix(in srgb, var(--warning) 14%, transparent); color: var(--warning);' : s==='approved' ? 'background: color-mix(in srgb, var(--success) 14%, transparent); color: var(--success);' : 'background: color-mix(in srgb, var(--danger) 14%, transparent); color: var(--danger);';

const selectedAmount = ref(null);
const form = useForm({ amount: null, proof: null });
const selectOption = (amt) => { selectedAmount.value = amt; form.amount = amt; };
const onFile = (e) => { form.proof = e.target.files[0] || null; };
const submit = () => {
    if (!form.amount || !form.proof) return;
    form.post(route('member.topup.store'), { preserveScroll: true, onSuccess: () => { selectedAmount.value=null; form.reset(); } });
};
</script>

<template>
    <Head title="Top Up" />
    <MemberLayout>
        <template #header>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Wallet / Top Up</h1>
                    <p class="mt-1 text-sm" style="color: var(--text-muted);">Beli kredit AI eceran jika kuota paket habis sebelum 30 hari. 1 kredit = 1x Generate AI.</p>
                </div>
                <div class="rounded-xl border px-4 py-3 text-center" style="border-color: var(--border-soft); background: var(--bg-elevated);">
                    <p class="text-xs" style="color: var(--text-soft);">Saldo Kredit Saat Ini</p>
                    <p class="text-2xl font-bold" style="color: var(--brand);">{{ membership?.credit_balance ?? 0 }} <span class="text-sm font-normal" style="color: var(--text-soft);">kredit</span></p>
                </div>
            </div>
        </template>

        <div class="container-base py-8 space-y-8">
            <!-- Options -->
            <div>
                <h2 class="font-semibold">Pilih Nominal Top Up</h2>
                <p class="mt-1 text-xs" style="color: var(--text-soft);">Transfer manual, upload bukti, approve admin → kredit langsung masuk. Tidak hangus saat renew paket.</p>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                    <button v-for="opt in options" :key="opt.amount" type="button" @click="selectOption(opt.amount)" class="card text-left transition hover:scale-[1.01]" :style="selectedAmount===opt.amount ? 'border-color: var(--brand); background: color-mix(in srgb, var(--brand) 8%, var(--bg-card)); box-shadow: 0 0 0 2px color-mix(in srgb, var(--brand) 20%, transparent);' : ''">
                        <div class="flex items-center justify-between">
                            <p class="font-bold">{{ formatRupiah(opt.amount) }}</p>
                            <span v-if="opt.bonus>0" class="rounded-full px-2 py-1 text-xs font-bold" style="background: var(--success); color: white;">+{{ opt.bonus }} bonus</span>
                        </div>
                        <p class="mt-2 text-2xl font-bold">{{ opt.label }}</p>
                        <p class="text-xs" style="color: var(--text-soft);">1 kredit = 1 generate</p>
                        <p class="mt-3 text-xs font-semibold" :style="selectedAmount===opt.amount ? 'color: var(--brand);' : 'color: var(--text-soft);'">{{ selectedAmount===opt.amount ? '✓ Terpilih' : 'Pilih →' }}</p>
                    </button>
                </div>
            </div>

            <!-- Form + Rekening -->
            <div class="grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                <form @submit.prevent="submit" class="card">
                    <h3 class="font-semibold">Upload Bukti Top Up</h3>
                    <div v-if="!selectedAmount" class="mt-4 rounded-xl p-6 text-center text-sm" style="background: var(--bg-elevated); color: var(--text-muted);">Pilih nominal di atas dulu.</div>
                    <div v-else class="mt-4 space-y-4">
                        <div class="rounded-xl p-3 text-sm" style="background: color-mix(in srgb, var(--brand) 8%, transparent); border: 1px solid color-mix(in srgb, var(--brand) 20%, transparent);">
                            Kamu akan top up <b>{{ options.find(o=>o.amount===selectedAmount)?.label }}</b> seharga <b>{{ formatRupiah(selectedAmount) }}</b>.
                        </div>
                        <div>
                            <label class="text-xs font-semibold">File Bukti Transfer *</label>
                            <input type="file" accept=".jpg,.jpeg,.png,.webp" @change="onFile" class="input-base mt-1 w-full" required />
                            <p v-if="form.errors.proof" class="mt-1 text-xs" style="color: var(--danger);">{{ form.errors.proof }}</p>
                            <p v-if="form.errors.amount" class="mt-1 text-xs" style="color: var(--danger);">{{ form.errors.amount }}</p>
                            <p v-if="form.proof" class="mt-1 text-xs" style="color: var(--text-soft);">{{ form.proof.name }}</p>
                        </div>
                        <div class="flex gap-3">
                            <button type="button" @click="selectedAmount=null; form.reset();" class="btn-ghost flex-1">Batal</button>
                            <button type="submit" :disabled="form.processing || !form.proof" class="btn-primary flex-1">{{ form.processing ? 'Mengirim...' : 'Kirim Bukti Top Up →' }}</button>
                        </div>
                        <p class="text-xs text-center" style="color: var(--text-soft);">Verifikasi 1x24 jam. Kredit tidak hangus saat perpanjang paket.</p>
                    </div>
                </form>

                <div class="card">
                    <h3 class="font-semibold">Rekening Tujuan (sama dengan Billing)</h3>
                    <p class="mt-1 text-xs" style="color: var(--text-soft);">{{ payment.instructions }}</p>
                    <div class="mt-4 space-y-3">
                        <div class="rounded-xl border p-4" style="border-color: var(--border-soft); background: var(--bg-elevated);">
                            <p class="text-xs font-bold" style="color: var(--text-soft);">{{ payment.bank_name }}</p>
                            <p class="font-mono text-lg font-bold">{{ payment.bank_number }}</p>
                            <p class="text-xs" style="color: var(--text-muted);">a.n. {{ payment.bank_holder }}</p>
                        </div>
                        <div class="rounded-xl border p-4" style="border-color: var(--border-soft);">
                            <p class="text-xs font-bold" style="color: var(--text-soft);">{{ payment.bank_name_2 }}</p>
                            <p class="font-mono text-lg font-bold">{{ payment.bank_number_2 }}</p>
                            <p class="text-xs" style="color: var(--text-muted);">a.n. {{ payment.bank_holder_2 }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Riwayat -->
            <div class="card">
                <h3 class="font-semibold">Riwayat Top Up</h3>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead><tr class="border-b text-xs" style="border-color: var(--border-soft); color: var(--text-soft);"><th class="py-3">Tanggal</th><th class="py-3">Nominal</th><th class="py-3">Kredit</th><th class="py-3">Status</th><th class="py-3 text-right">Aksi</th></tr></thead>
                        <tbody>
                            <tr v-for="trx in transactions.data" :key="trx.id" class="border-b last:border-0" style="border-color: var(--border-soft);">
                                <td class="py-3 text-xs">{{ new Date(trx.created_at).toLocaleDateString('id-ID') }} <span class="font-mono" style="color: var(--text-soft);">#{{ String(trx.id).padStart(6,'0') }}</span></td>
                                <td class="py-3 font-semibold">{{ formatRupiah(trx.amount) }}</td>
                                <td class="py-3 text-xs">{{ {25000:50,50000:120,100000:270}[trx.amount] || Math.floor(trx.amount/500) }} kredit</td>
                                <td class="py-3"><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :style="statusStyle(trx.status)">{{ trx.status }}</span></td>
                                <td class="py-3 text-right"><a v-if="trx.proof_path" :href="route('member.transactions.proof', trx.id)" target="_blank" class="text-xs underline" style="color: var(--brand);">Bukti</a><span v-else class="text-xs" style="color: var(--text-soft);">—</span></td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="transactions.data.length===0" class="py-10 text-center text-sm" style="color: var(--text-muted);">Belum ada transaksi top up.</div>
                </div>
                <div v-if="transactions.links" class="mt-4 flex flex-wrap gap-2">
                    <Link v-for="link in transactions.links" :key="link.label" :href="link.url || '#'" :class="['rounded-lg px-3 py-1.5 text-xs border', link.active ? 'btn-primary border-transparent' : 'btn-ghost']" style="border-color: var(--border-soft);" v-html="link.label" />
                </div>
            </div>
        </div>
    </MemberLayout>
</template>
