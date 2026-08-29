<script setup>
import { ref, reactive, computed } from 'vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps({ tiers: Array, memberships: Object });
const formatRupiah = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n || 0);

const showing = ref(false);
const editing = ref(null);
const form = useForm({
    name: '',
    slug: '',
    price: 0,
    duration_days: 30,
    limits: { max_projects: 2, max_ai_per_month: 10, can_export_pdf: false, can_mermaid: false, can_share: false, can_template_premium: false },
    features: [''],
    is_active: true,
    sort_order: 1,
});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.name = '';
    form.slug = '';
    form.price = 49000;
    form.duration_days = 30;
    form.limits = { max_projects: 10, max_ai_per_month: 100, can_export_pdf: true, can_mermaid: false, can_share: false, can_template_premium: false };
    form.features = ['Export PDF & Markdown', 'Template Dasar'];
    form.is_active = true;
    form.sort_order = (props.tiers.length + 1);
    form.clearErrors();
    showing.value = true;
};
const openEdit = (tier) => {
    editing.value = tier;
    form.name = tier.name;
    form.slug = tier.slug;
    form.price = tier.price;
    form.duration_days = tier.duration_days;
    form.limits = { ...tier.limits };
    form.features = tier.features?.length ? [...tier.features] : [''];
    form.is_active = tier.is_active;
    form.sort_order = tier.sort_order;
    form.clearErrors();
    showing.value = true;
};
const close = () => { showing.value = false; editing.value = null; };

const slugify = () => {
    form.slug = form.name.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
};

const submit = () => {
    if (editing.value) {
        form.put(route('admin.memberships.update', editing.value.id), { onSuccess: () => close(), preserveScroll: true });
    } else {
        form.post(route('admin.memberships.store'), { onSuccess: () => close(), preserveScroll: true });
    }
};

const destroyTier = (tier) => {
    if (!confirm(`Hapus paket ${tier.name}?`)) return;
    router.delete(route('admin.memberships.destroy', tier.id), { preserveScroll: true });
};

const addFeature = () => form.features.push('');
const removeFeature = (i) => form.features.splice(i, 1);

const pendingBadge = (t) => t.slug === 'default' ? 'Gratis' : t.is_active ? 'Aktif' : 'Nonaktif';
</script>

<template>
    <Head title="Membership Management" />
    <AdminLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Membership & Paket</h1>
                    <p class="mt-1 text-sm" style="color: var(--text-muted);">Kelola paket yang dijual ke member. Harga & limit bisa diubah tanpa deploy.</p>
                </div>
                <button @click="openCreate" class="btn-primary">+ Buat Paket</button>
            </div>
        </template>

        <div class="container-base py-8 space-y-8">
            <!-- Paket cards -->
            <div>
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold">Paket Management ({{ tiers.length }} paket)</h2>
                    <span class="text-xs" style="color: var(--text-soft);">Drag reorder Fase D</span>
                </div>
                <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <div v-for="tier in tiers" :key="tier.id" class="card relative overflow-hidden">
                        <div v-if="tier.slug==='premium'" class="absolute inset-x-0 top-0 h-1" style="background: linear-gradient(90deg, var(--brand), var(--brand-secondary));"></div>
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold">{{ tier.name }}</h3>
                            <span class="rounded-full px-2 py-1 text-xs font-bold" :style="tier.slug === 'premium' ? 'background: var(--brand); color: white;' : tier.slug==='default' ? 'background: var(--bg-elevated); color: var(--text-soft);' : 'background: color-mix(in srgb, var(--brand) 12%, transparent); color: var(--brand);'">{{ pendingBadge(tier) }}</span>
                        </div>
                        <p class="mt-2 text-2xl font-bold">{{ formatRupiah(tier.price) }}<span class="text-sm font-normal" style="color: var(--text-soft);"> / {{ tier.duration_days }} hari</span></p>
                        <p class="mt-1 text-xs font-mono" style="color: var(--text-soft);">/{{ tier.slug }} • sort #{{ tier.sort_order }}</p>
                        <ul class="mt-4 space-y-1.5 text-sm">
                            <li v-for="f in tier.features" :key="f" class="flex items-center gap-2"><span style="color: var(--success);">✓</span>{{ f }}</li>
                        </ul>
                        <div class="mt-4 grid grid-cols-2 gap-2 text-xs rounded-lg p-3" style="background: var(--bg-elevated); color: var(--text-soft);">
                            <span>Max Project: <b style="color: var(--text-main);">{{ tier.limits.max_projects === 9999 ? '∞' : tier.limits.max_projects }}</b></span>
                            <span>AI: <b style="color: var(--text-main);">{{ tier.limits.max_ai_per_month }}/bln</b></span>
                            <span>PDF: {{ tier.limits.can_export_pdf ? '✓' : '—' }}</span>
                            <span>Mermaid: {{ tier.limits.can_mermaid ? '✓' : '—' }}</span>
                        </div>
                        <div class="mt-4 flex gap-2">
                            <button @click="openEdit(tier)" class="btn-secondary flex-1 text-xs">Edit</button>
                            <button @click="destroyTier(tier)" :disabled="tier.slug==='default'" class="btn-ghost flex-1 text-xs border" style="border-color: var(--border-soft);" :style="tier.slug==='default' ? 'opacity:.4; cursor:not-allowed;' : ''">Hapus</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Membership table -->
            <div class="card">
                <h3 class="font-semibold">Membership Aktif ({{ memberships.total || memberships.data.length }} user)</h3>
                <p class="mt-1 text-xs" style="color: var(--text-muted);">1 user 1 membership aktif. History ada di transaksi.</p>
                <div class="mt-4 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead><tr class="border-b text-xs" style="border-color: var(--border-soft); color: var(--text-soft);"><th class="py-3">User</th><th class="py-3">Tier</th><th class="py-3">Kuota</th><th class="py-3">Expired</th><th class="py-3">Status</th></tr></thead>
                        <tbody>
                            <tr v-for="m in memberships.data" :key="m.id" class="border-b last:border-0 hover:bg-white/[0.02]" style="border-color: var(--border-soft);">
                                <td class="py-3"><p class="font-medium">{{ m.user?.name }}</p><p class="text-xs" style="color: var(--text-soft);">{{ m.user?.email }}</p></td>
                                <td class="py-3"><span class="rounded-full px-2 py-1 text-xs font-semibold" style="background: var(--bg-elevated);">{{ m.tier?.name }}</span></td>
                                <td class="py-3 text-xs">{{ m.ai_quota_used }}/{{ m.ai_quota_total }} + {{ m.credit_balance }} kredit</td>
                                <td class="py-3 text-xs" style="color: var(--text-soft);">{{ m.expired_at ? new Date(m.expired_at).toLocaleDateString('id-ID') : '-' }}</td>
                                <td class="py-3"><span class="rounded-full px-2 py-1 text-xs font-semibold" :style="m.status === 'active' ? 'background: color-mix(in srgb, var(--success) 12%, transparent); color: var(--success);' : 'background: color-mix(in srgb, var(--danger) 12%, transparent); color: var(--danger);'">{{ m.status }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="memberships.data.length===0" class="py-10 text-center text-sm" style="color: var(--text-muted);">Belum ada membership.</div>
                </div>
                <div v-if="memberships.links" class="mt-6 flex flex-wrap gap-2">
                    <a v-for="link in memberships.links" :key="link.label" :href="link.url || '#'" :class="['rounded-lg px-3 py-1.5 text-xs border', link.active ? 'btn-primary border-transparent' : 'btn-ghost']" style="border-color: var(--border-soft);" v-html="link.label"></a>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div v-if="showing" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="close"></div>
            <div class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl border p-6 shadow-2xl" style="background: var(--bg-card); border-color: var(--border-soft);">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-bold">{{ editing ? 'Edit Paket: ' + editing.name : 'Buat Paket Baru' }}</h3>
                    <button @click="close" class="btn-ghost px-2">✕</button>
                </div>
                <form @submit.prevent="submit" class="mt-6 space-y-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-xs font-semibold">Nama Paket</label>
                            <input v-model="form.name" @blur="slugify" class="input-base mt-1 w-full" placeholder="Basic" />
                            <p v-if="form.errors.name" class="mt-1 text-xs" style="color: var(--danger);">{{ form.errors.name }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-semibold">Slug (unik)</label>
                            <input v-model="form.slug" class="input-base mt-1 w-full font-mono text-sm" placeholder="basic" />
                            <p v-if="form.errors.slug" class="mt-1 text-xs" style="color: var(--danger);">{{ form.errors.slug }}</p>
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div>
                            <label class="text-xs font-semibold">Harga (Rp)</label>
                            <input v-model.number="form.price" type="number" class="input-base mt-1 w-full" />
                            <p v-if="form.errors.price" class="mt-1 text-xs" style="color: var(--danger);">{{ form.errors.price }}</p>
                            <p class="mt-1 text-xs" style="color: var(--text-soft);">{{ formatRupiah(form.price) }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-semibold">Durasi (hari)</label>
                            <input v-model.number="form.duration_days" type="number" class="input-base mt-1 w-full" />
                            <p v-if="form.errors.duration_days" class="mt-1 text-xs" style="color: var(--danger);">{{ form.errors.duration_days }}</p>
                        </div>
                        <div>
                            <label class="text-xs font-semibold">Sort Order</label>
                            <input v-model.number="form.sort_order" type="number" class="input-base mt-1 w-full" />
                        </div>
                    </div>
                    <div class="rounded-xl border p-4 space-y-4" style="border-color: var(--border-soft); background: var(--bg-elevated);">
                        <p class="text-xs font-bold uppercase tracking-widest" style="color: var(--text-soft);">Limits (JSON)</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div><label class="text-xs">Max Projects</label><input v-model.number="form.limits.max_projects" type="number" class="input-base mt-1 w-full" /></div>
                            <div><label class="text-xs">Max AI / bulan</label><input v-model.number="form.limits.max_ai_per_month" type="number" class="input-base mt-1 w-full" /></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                            <label class="flex items-center gap-2"><input type="checkbox" v-model="form.limits.can_export_pdf" /> Export PDF</label>
                            <label class="flex items-center gap-2"><input type="checkbox" v-model="form.limits.can_mermaid" /> Mermaid</label>
                            <label class="flex items-center gap-2"><input type="checkbox" v-model="form.limits.can_share" /> Share</label>
                            <label class="flex items-center gap-2"><input type="checkbox" v-model="form.limits.can_template_premium" /> Template Premium</label>
                        </div>
                        <p v-if="form.errors['limits.max_projects']" class="text-xs" style="color: var(--danger);">{{ form.errors['limits.max_projects'] }}</p>
                    </div>
                    <div>
                        <div class="flex items-center justify-between"><label class="text-xs font-semibold">Fitur (checklist pricing table)</label><button type="button" @click="addFeature" class="text-xs" style="color: var(--brand);">+ Tambah</button></div>
                        <div class="mt-2 space-y-2">
                            <div v-for="(f, i) in form.features" :key="i" class="flex gap-2">
                                <input v-model="form.features[i]" class="input-base flex-1" :placeholder="`Fitur ${i+1}`" />
                                <button type="button" @click="removeFeature(i)" class="btn-ghost px-2 text-xs" :disabled="form.features.length===1">✕</button>
                            </div>
                        </div>
                        <p v-if="form.errors.features" class="mt-1 text-xs" style="color: var(--danger);">{{ form.errors.features }}</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.is_active" /> Aktif (tampil di pricing)</label>
                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="close" class="btn-ghost flex-1">Batal</button>
                        <button type="submit" :disabled="form.processing" class="btn-primary flex-1">{{ form.processing ? 'Menyimpan...' : editing ? 'Simpan Perubahan' : 'Buat Paket' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
