<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ coupons: Object, tiers: Array });
const showing = ref(false);
const editing = ref(null);
const form = useForm({
    code: '', name: '', discount_percent: 20, max_uses: 100, max_uses_per_user: 1, applicable_tier_id: null, is_active: true, expires_at: '',
});
const openCreate = () => { editing.value=null; form.reset(); form.code='LAUNCH50'; form.discount_percent=50; form.max_uses=100; form.clearErrors(); showing.value=true; };
const openEdit = (c) => { editing.value=c; form.code=c.code; form.name=c.name||''; form.discount_percent=c.discount_percent; form.max_uses=c.max_uses; form.max_uses_per_user=c.max_uses_per_user; form.applicable_tier_id=c.applicable_tier_id; form.is_active=c.is_active; form.expires_at=c.expires_at ? c.expires_at.slice(0,16) : ''; form.clearErrors(); showing.value=true; };
const close = () => { showing.value=false; editing.value=null; };
const submit = () => {
    if (editing.value) form.put(route('admin.coupons.update', editing.value.id), { onSuccess: close, preserveScroll:true });
    else form.post(route('admin.coupons.store'), { onSuccess: close, preserveScroll:true });
};
const destroy = (c) => { if(confirm('Hapus kupon '+c.code+'?')) { form.delete(route('admin.coupons.destroy', c.id), { preserveScroll:true }); } };
</script>
<template>
    <Head title="Kupon & Diskon" />
    <AdminLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div><h1 class="text-2xl font-bold">Kupon & Diskon</h1><p class="mt-1 text-sm" style="color: var(--text-muted);">Kode promo untuk potongan paket. Ex: LAUNCH50 50% untuk 100 pemakaian.</p></div>
                <button @click="openCreate" class="btn-primary">+ Buat Kupon</button>
            </div>
        </template>
        <div class="container-base py-8">
            <div class="card">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead><tr class="border-b text-xs" style="border-color: var(--border-soft); color: var(--text-soft);"><th class="py-3">Kode</th><th class="py-3">Diskon</th><th class="py-3">Paket</th><th class="py-3">Pemakaian</th><th class="py-3">Expired</th><th class="py-3">Status</th><th class="py-3 text-right">Aksi</th></tr></thead>
                        <tbody>
                            <tr v-for="c in coupons.data" :key="c.id" class="border-b last:border-0" style="border-color: var(--border-soft);">
                                <td class="py-3 font-mono font-bold">{{ c.code }}</td>
                                <td class="py-3">{{ c.discount_percent }}%</td>
                                <td class="py-3 text-xs" style="color: var(--text-soft);">{{ c.tier?.name || 'Semua paket' }}</td>
                                <td class="py-3 text-xs">{{ c.used_count }}/{{ c.max_uses ?? '∞' }}</td>
                                <td class="py-3 text-xs" style="color: var(--text-soft);">{{ c.expires_at ? new Date(c.expires_at).toLocaleDateString('id-ID') : '—' }}</td>
                                <td class="py-3"><span class="rounded-full px-2 py-1 text-xs font-semibold" :style="c.is_active ? 'background: color-mix(in srgb, var(--success) 12%, transparent); color: var(--success);' : 'background: var(--bg-elevated); color: var(--text-soft);'">{{ c.is_active ? 'Aktif' : 'Nonaktif' }}</span></td>
                                <td class="py-3 text-right"><button @click="openEdit(c)" class="text-xs font-semibold mr-3" style="color: var(--brand);">Edit</button><button @click="destroy(c)" class="text-xs" style="color: var(--danger);">Hapus</button></td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="coupons.data.length===0" class="py-10 text-center text-sm" style="color: var(--text-muted);">Belum ada kupon. Buat LAUNCH50 untuk launch.</div>
                </div>
            </div>
        </div>

        <div v-if="showing" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60" @click="close"></div>
            <div class="relative w-full max-w-xl rounded-2xl border p-6" style="background: var(--bg-card); border-color: var(--border-soft);">
                <h3 class="font-bold">{{ editing ? 'Edit Kupon' : 'Buat Kupon' }}</h3>
                <form @submit.prevent="submit" class="mt-4 space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="text-xs font-semibold">Kode *</label><input v-model="form.code" class="input-base mt-1 w-full font-mono uppercase" placeholder="LAUNCH50" /><p v-if="form.errors.code" class="text-xs mt-1" style="color: var(--danger);">{{ form.errors.code }}</p></div>
                        <div><label class="text-xs font-semibold">Diskon % *</label><input v-model.number="form.discount_percent" type="number" class="input-base mt-1 w-full" min="1" max="100" /></div>
                    </div>
                    <div><label class="text-xs font-semibold">Nama / Deskripsi</label><input v-model="form.name" class="input-base mt-1 w-full" placeholder="Promo launching" /></div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div><label class="text-xs">Max Uses (kosong = ∞)</label><input v-model.number="form.max_uses" type="number" class="input-base mt-1 w-full" placeholder="100" /></div>
                        <div><label class="text-xs">Max / user</label><input v-model.number="form.max_uses_per_user" type="number" class="input-base mt-1 w-full" /></div>
                        <div><label class="text-xs">Berlaku untuk paket</label><select v-model="form.applicable_tier_id" class="input-base mt-1 w-full"><option :value="null">Semua paket</option><option v-for="t in tiers" :key="t.id" :value="t.id">{{ t.name }}</option></select></div>
                    </div>
                    <div><label class="text-xs">Expired At (opsional)</label><input v-model="form.expires_at" type="datetime-local" class="input-base mt-1 w-full" /></div>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.is_active" /> Aktif</label>
                    <div class="flex gap-3 pt-2"><button type="button" @click="close" class="btn-ghost flex-1">Batal</button><button type="submit" :disabled="form.processing" class="btn-primary flex-1">{{ form.processing ? 'Menyimpan...' : 'Simpan' }}</button></div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
