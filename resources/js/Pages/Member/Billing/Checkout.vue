<script setup>
import MemberLayout from '@/Layouts/MemberLayout.vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    tier: Object,
    payment: Object,
    coupon: Object,
    coupon_error: String,
});

const formatRupiah = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n || 0);

const couponCode = ref('');
const previewCoupon = ref(props.coupon || null);

const netAmount = computed(() => {
    const discount = previewCoupon.value ? Math.round(props.tier.price * previewCoupon.value.discount_percent / 100) : 0;
    return props.tier.price - discount;
});
const discountAmount = computed(() => props.tier.price - netAmount.value);

const form = useForm({
    proof: null,
    coupon_code: '',
});

const checkCoupon = () => {
    if (!couponCode.value.trim()) return;
    router.get(route('member.billing.checkout', props.tier.id), { coupon: couponCode.value.trim() }, { preserveState: true, replace: true, onSuccess: () => { previewCoupon.value = props.coupon; form.coupon_code = couponCode.value.trim(); }});
};

const submit = () => {
    form.coupon_code = couponCode.value.trim() || previewCoupon.value?.code || '';
    form.post(route('member.billing.store', props.tier.id), { preserveScroll: true });
};

const onFile = (e) => { form.proof = e.target.files[0] || null; };
</script>

<template>
    <Head :title="`Checkout ${tier.name}`" />
    <MemberLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link :href="route('member.billing.index')" class="btn-ghost px-3 text-xs">← Kembali</Link>
                <div>
                    <h1 class="text-xl font-bold">Checkout — {{ tier.name }}</h1>
                    <p class="text-sm" style="color: var(--text-muted);">Transfer manual • Upload bukti • Approve 1x24 jam</p>
                </div>
            </div>
        </template>

        <div class="container-base py-8">
            <div class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">
                <!-- Ringkasan & Instruksi -->
                <div class="space-y-6">
                    <div class="card">
                        <h3 class="font-semibold">Ringkasan Pesanan</h3>
                        <div class="mt-4 rounded-xl border p-4" style="border-color: var(--border-soft); background: var(--bg-elevated);">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-bold">{{ tier.name }} — {{ tier.slug }}</p>
                                    <p class="text-xs" style="color: var(--text-soft);">{{ tier.duration_days }} hari • {{ tier.limits.max_ai_per_month }} AI / bulan • {{ tier.limits.max_projects === 9999 ? '∞' : tier.limits.max_projects }} project</p>
                                    <ul class="mt-2 space-y-1 text-xs">
                                        <li v-for="f in tier.features" :key="f" class="flex gap-2"><span style="color: var(--success);">✓</span>{{ f }}</li>
                                    </ul>
                                </div>
                                <p class="text-lg font-bold">{{ formatRupiah(tier.price) }}</p>
                            </div>
                            <div v-if="previewCoupon" class="mt-3 flex items-center justify-between rounded-lg px-3 py-2 text-sm" style="background: color-mix(in srgb, var(--success) 12%, transparent); color: var(--success);">
                                <span>Kupon {{ previewCoupon.code }} — {{ previewCoupon.discount_percent }}% off</span>
                                <span class="font-bold">-{{ formatRupiah(discountAmount) }}</span>
                            </div>
                            <div class="mt-3 flex items-center justify-between border-t pt-3" style="border-color: var(--border-soft);">
                                <span class="font-semibold">Total Transfer</span>
                                <span class="text-xl font-bold" style="color: var(--brand);">{{ formatRupiah(netAmount) }}</span>
                            </div>
                            <p v-if="coupon_error" class="mt-2 text-xs" style="color: var(--danger);">{{ coupon_error }}</p>
                        </div>

                        <div class="mt-6">
                            <label class="text-xs font-semibold">Punya Kupon?</label>
                            <div class="mt-1 flex gap-2">
                                <input v-model="couponCode" class="input-base flex-1 font-mono uppercase" placeholder="LAUNCH50" @keyup.enter="checkCoupon" />
                                <button type="button" @click="checkCoupon" class="btn-secondary text-xs">Cek</button>
                            </div>
                            <p v-if="previewCoupon" class="mt-1 text-xs" style="color: var(--success);">Kupon valid — diskon akan dipotong saat approve.</p>
                        </div>
                    </div>

                    <div class="card">
                        <h3 class="font-semibold">Cara Bayar — Transfer Manual</h3>
                        <ol class="mt-3 space-y-3 text-sm list-decimal list-inside">
                            <li>Transfer <b>{{ formatRupiah(netAmount) }}</b> tepat (jangan dibulatkan) ke salah satu rekening di samping.</li>
                            <li>Simpan bukti (screenshot / foto struk) — JPG/PNG/WEBP max 2MB.</li>
                            <li>Upload bukti di form sebelah → Submit → Status jadi <b>Pending</b>.</li>
                            <li>Admin cek mutasi &amp; approve 1x24 jam → tier naik otomatis + kuota reset.</li>
                        </ol>
                        <div class="mt-4 rounded-xl p-3 text-xs" style="background: color-mix(in srgb, var(--warning) 10%, transparent); border: 1px solid color-mix(in srgb, var(--warning) 20%, transparent); color: var(--warning);">
                            Jangan transfer ke rekening selain yang tercantum. Hubungi admin jika ragu.
                        </div>
                    </div>
                </div>

                <!-- Form Upload -->
                <div class="space-y-6">
                    <div class="card">
                        <h3 class="font-semibold">Rekening Tujuan</h3>
                        <div class="mt-4 space-y-3">
                            <div class="rounded-xl border p-4 text-center" style="border-color: var(--brand); background: color-mix(in srgb, var(--brand) 6%, transparent);">
                                <p class="text-xs font-bold tracking-widest" style="color: var(--brand);">{{ payment.bank_name }}</p>
                                <p class="mt-1 font-mono text-xl font-bold tracking-widest">{{ payment.bank_number }}</p>
                                <p class="text-sm">a.n. {{ payment.bank_holder }}</p>
                                <button type="button" class="btn-ghost mt-2 text-xs" @click="navigator.clipboard.writeText(payment.bank_number)">Copy No. Rekening</button>
                            </div>
                            <div class="rounded-xl border p-4 text-center" style="border-color: var(--border-soft);">
                                <p class="text-xs font-bold tracking-widest" style="color: var(--text-soft);">{{ payment.bank_name_2 }}</p>
                                <p class="mt-1 font-mono text-xl font-bold tracking-widest">{{ payment.bank_number_2 }}</p>
                                <p class="text-sm">a.n. {{ payment.bank_holder_2 }}</p>
                            </div>
                        </div>
                        <p class="mt-3 text-center text-xs font-semibold">Total yang harus ditransfer: <span style="color: var(--brand);">{{ formatRupiah(netAmount) }}</span></p>
                    </div>

                    <form @submit.prevent="submit" class="card" enctype="multipart/form-data">
                        <h3 class="font-semibold">Upload Bukti Transfer</h3>
                        <p class="mt-1 text-xs" style="color: var(--text-muted);">Wajib: JPG/PNG/WEBP max 2MB. Pastikan nominal terlihat jelas.</p>

                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="text-xs font-semibold">File Bukti *</label>
                                <input type="file" accept=".jpg,.jpeg,.png,.webp" @change="onFile" class="input-base mt-1 w-full text-sm" required />
                                <p v-if="form.errors.proof" class="mt-1 text-xs" style="color: var(--danger);">{{ form.errors.proof }}</p>
                                <p v-if="form.proof" class="mt-1 text-xs" style="color: var(--text-soft);">{{ form.proof.name }} — {{ (form.proof.size/1024).toFixed(0) }} KB</p>
                            </div>
                            <div v-if="previewCoupon">
                                <p class="text-xs" style="color: var(--text-soft);">Kupon terpasang: <span class="font-mono font-bold" style="color: var(--success);">{{ previewCoupon.code }}</span> — admin akan potong {{ formatRupiah(discountAmount) }} saat approve.</p>
                            </div>
                            <div v-if="form.errors.coupon_code" class="text-xs" style="color: var(--danger);">{{ form.errors.coupon_code }}</div>

                            <div class="flex gap-3 pt-2">
                                <Link :href="route('member.billing.index')" class="btn-ghost flex-1 justify-center">Batal</Link>
                                <button type="submit" :disabled="form.processing || !form.proof" class="btn-primary flex-1 justify-center" :style="!form.proof ? 'opacity: .6; cursor: not-allowed;' : ''">{{ form.processing ? 'Mengirim...' : 'Kirim Bukti →' }}</button>
                            </div>
                            <p v-if="$page.props.flash?.error" class="text-xs text-center" style="color: var(--danger);">{{ $page.props.flash.error }}</p>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </MemberLayout>
</template>
