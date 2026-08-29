<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    providers: Array,
    availableProviders: Array,
    activeModels: Array,
    defaultModelsMap: Object,
});

const showing = ref(false);
const form = useForm({
    provider: '',
    api_key: '',
    base_url: '',
    enabled_models: [],
});

const previewModels = ref([]);
const previewLoading = ref(false);
const previewError = ref('');

const availableToAdd = computed(() => {
    const used = new Set(props.providers.map(p => p.provider));
    return props.availableProviders.filter(p => !used.has(p.value));
});

const openAdd = () => {
    form.reset();
    form.provider = availableToAdd.value[0]?.value || '';
    form.api_key = '';
    form.base_url = '';
    form.enabled_models = [];
    previewModels.value = [];
    previewError.value = '';
    showing.value = true;
    if (form.provider) {
        const defaults = props.defaultModelsMap[form.provider]?.default_models || [];
        previewModels.value = defaults.map(id => ({ id, label: id }));
        form.enabled_models = [...defaults];
    }
};

const onProviderChange = () => {
    const defaults = props.defaultModelsMap[form.provider]?.default_models || [];
    previewModels.value = defaults.map(id => ({ id, label: id }));
    form.enabled_models = [...defaults];
    previewError.value = '';
};

const fetchPreview = async () => {
    if (!form.provider || !form.api_key) { previewError.value = 'Pilih provider & isi API Key dulu'; return; }
    previewLoading.value = true;
    previewError.value = '';
    try {
        const res = await fetch(route('admin.ai-providers.fetchPreview'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
            body: JSON.stringify({ provider: form.provider, api_key: form.api_key, base_url: form.base_url || null }),
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.error || 'Fetch gagal');
        previewModels.value = data.models || [];
        form.enabled_models = previewModels.value.map(m => m.id);
    } catch (e) {
        previewError.value = e.message;
        // fallback to defaults
        const defaults = props.defaultModelsMap[form.provider]?.default_models || [];
        if (previewModels.value.length === 0 && defaults.length) {
            previewModels.value = defaults.map(id => ({ id, label: id }));
            form.enabled_models = [...defaults];
        }
    } finally { previewLoading.value = false; }
};

const toggleModel = (id) => {
    const idx = form.enabled_models.indexOf(id);
    if (idx >= 0) form.enabled_models.splice(idx, 1);
    else form.enabled_models.push(id);
};

const submit = () => {
    // debug: log payload
    console.log('submit ai-provider', { provider: form.provider, has_key: !!form.api_key, enabled: form.enabled_models.length, base_url: form.base_url });
    form.post(route('admin.ai-providers.store'), {
        preserveScroll: true,
        onSuccess: () => { showing.value = false; previewModels.value = []; previewError.value=''; },
        onError: (errors) => {
            console.error('ai-provider store errors', errors);
            // gabungkan error ke previewError agar terlihat
            const msg = Object.values(errors).flat().join(' | ');
            if(msg) previewError.value = msg;
        },
    });
};

const toggleActive = (p) => router.post(route('admin.ai-providers.toggle', p.id), {}, { preserveScroll: true });
const setDefault = (p) => router.post(route('admin.ai-providers.default', p.id), {}, { preserveScroll: true });
const destroy = (p) => { if (confirm(`Hapus provider ${p.label}?`)) router.delete(route('admin.ai-providers.destroy', p.id), { preserveScroll: true }); };
const fetchExisting = (p) => router.post(route('admin.ai-providers.fetch', p.id), {}, { preserveScroll: true });
const testApi = (p) => router.post(route('admin.ai-providers.test', p.id), {}, { preserveScroll: true });

const updateEnabled = (p, checked, modelId) => {
    let next = [...(p.enabled_models || [])];
    if (checked) { if (!next.includes(modelId)) next.push(modelId); }
    else { next = next.filter(m => m !== modelId); }
    router.put(route('admin.ai-providers.update', p.id), { enabled_models: next }, { preserveScroll: true });
};
</script>

<template>
    <Head title="AI Providers" />
    <AdminLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">AI Providers</h1>
                    <p class="mt-1 text-sm" style="color: var(--text-muted);">Tambah multiple provider, fetch model via API Key, centang model yang dipakai. Member pilih model di wizard.</p>
                </div>
                <button @click="openAdd" :disabled="availableToAdd.length===0" class="btn-primary" :style="availableToAdd.length===0 ? 'opacity:.5; cursor:not-allowed;' : ''">+ Tambah Provider</button>
            </div>
        </template>

        <div class="container-base py-8 space-y-6">
            <!-- Summary bar -->
            <div class="card flex flex-wrap items-center gap-3 text-sm">
                <span class="rounded-full px-3 py-1.5 font-semibold" style="background: var(--bg-elevated);">{{ providers.length }} provider terpasang</span>
                <span class="rounded-full px-3 py-1.5 font-semibold" :style="activeModels.length ? 'background: color-mix(in srgb, var(--success) 12%, transparent); color: var(--success);' : 'background: var(--bg-elevated); color: var(--text-soft);'">{{ activeModels.length }} model aktif untuk member</span>
                <span v-if="availableToAdd.length===0" class="text-xs" style="color: var(--text-soft);">Semua provider sudah ditambahkan (7 tersedia)</span>
            </div>

            <!-- Provider cards -->
            <div v-if="providers.length===0" class="card py-12 text-center">
                <p class="font-semibold">Belum ada AI Provider.</p>
                <p class="mt-1 text-sm" style="color: var(--text-muted);">Klik Tambah Provider → pilih OpenAI / Gemini → masukkan API Key → Fetch Models → centang model.</p>
                <button @click="openAdd" class="btn-primary mt-4">Tambah Provider Pertama</button>
            </div>

            <div v-else class="grid gap-6 lg:grid-cols-2">
                <div v-for="p in providers" :key="p.id" class="card relative overflow-hidden">
                    <div v-if="p.is_default" class="absolute left-0 top-0 h-1 w-full" style="background: linear-gradient(90deg, var(--brand), var(--brand-secondary));"></div>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-lg">{{ p.label }}</h3>
                                <span class="rounded-full px-2 py-1 text-xs font-bold" style="background: var(--bg-elevated); color: var(--text-soft);">{{ p.provider }}</span>
                                <span v-if="p.is_default" class="rounded-full px-2 py-1 text-xs font-bold" style="background: var(--brand); color: white;">Default</span>
                                <span v-if="!p.is_active" class="rounded-full px-2 py-1 text-xs font-bold" style="background: var(--danger); color: white;">Nonaktif</span>
                            </div>
                            <p class="mt-1 font-mono text-xs" style="color: var(--text-soft);">{{ p.masked_key }} • {{ p.has_key ? 'API Key terpasang & ter-encrypt' : 'Belum ada key' }}</p>
                            <p v-if="p.base_url" class="text-xs font-mono" style="color: var(--text-soft);">{{ p.base_url }}</p>
                        </div>
                        <button @click="toggleActive(p)" class="btn-ghost text-xs border" :style="p.is_active ? 'border-color: var(--success); color: var(--success);' : 'border-color: var(--border-soft);'">{{ p.is_active ? 'Aktif' : 'Aktifkan' }}</button>
                    </div>

                    <!-- Models -->
                    <div class="mt-4">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold uppercase tracking-widest" style="color: var(--text-soft);">Model {{ p.enabled_models?.length || 0 }}/{{ p.available_models?.length || 0 }} aktif</p>
                            <div class="flex gap-2">
                                <button @click="testApi(p)" class="text-xs font-bold rounded-full px-2.5 py-1" style="background: color-mix(in srgb, var(--success) 12%, transparent); color: var(--success);">Test API</button>
                                <button @click="fetchExisting(p)" class="text-xs font-semibold underline" style="color: var(--brand);">Fetch Models</button>
                                <button v-if="!p.is_default" @click="setDefault(p)" class="text-xs font-semibold" style="color: var(--text-soft);">Jadikan Default</button>
                            </div>
                        </div>

                        <div v-if="p.available_models?.length" class="mt-2 max-h-48 overflow-y-auto rounded-xl border p-3 space-y-1.5" style="border-color: var(--border-soft); background: var(--bg-elevated);">
                            <label v-for="m in p.available_models" :key="m.id" class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-white/5 cursor-pointer">
                                <input type="checkbox" :checked="p.enabled_models?.includes(m.id)" @change="updateEnabled(p, $event.target.checked, m.id)" />
                                <span class="font-mono text-xs flex-1">{{ m.id }}</span>
                                <span class="text-xs" style="color: var(--text-soft);">{{ m.label !== m.id ? m.label : '' }}</span>
                            </label>
                        </div>
                        <p v-else class="mt-2 text-xs" style="color: var(--text-soft);">Belum ada daftar model. Klik Fetch Models setelah isi API Key.</p>
                        <p v-if="p.last_fetched_at" class="mt-1 text-xs" style="color: var(--text-soft);">Last fetch: {{ new Date(p.last_fetched_at).toLocaleString('id-ID') }}</p>
                    </div>

                    <!-- Inline edit API Key -->
                    <div class="mt-4 flex gap-2">
                        <button @click="destroy(p)" class="text-xs" style="color: var(--danger);">Hapus</button>
                        <span class="text-xs" style="color: var(--border-soft);">•</span>
                        <span class="text-xs" style="color: var(--text-soft);">Ganti API Key via edit: hapus & tambah ulang, atau update di toggle fetch.</span>
                    </div>
                </div>
            </div>

            <!-- Member preview -->
            <div class="card">
                <h3 class="font-semibold">Preview untuk Member (wizard)</h3>
                <p class="mt-1 text-sm" style="color: var(--text-muted);">Member akan lihat dropdown ini di setiap langkah wizard. Hanya model yang dicentang & provider aktif yang muncul.</p>
                <div v-if="activeModels.length===0" class="mt-3 text-sm rounded-xl p-4 text-center" style="background: var(--bg-elevated); color: var(--text-soft);">Belum ada model aktif — centang minimal 1 model di atas.</div>
                <div v-else class="mt-3 flex flex-wrap gap-2">
                    <span v-for="m in activeModels" :key="m.value" class="rounded-full border px-3 py-1.5 text-xs font-mono" style="border-color: var(--border-soft); background: var(--bg-elevated);">{{ m.display }}</span>
                </div>
                <div class="mt-4 rounded-xl p-3 text-xs" style="background: color-mix(in srgb, var(--brand) 8%, transparent); border: 1px solid color-mix(in srgb, var(--brand) 15%, transparent);">
                    <b>Flow:</b> Admin tambah provider → fetch → centang → Member di <code>/member/projects/{id}/wizard/{step}</code> tinggal pilih model dari dropdown (default otomatis kepilih).
                </div>
            </div>
        </div>

        <!-- Add modal -->
        <div v-if="showing" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showing=false"></div>
            <div class="relative w-full max-w-xl max-h-[90vh] overflow-y-auto rounded-2xl border p-6 shadow-2xl" style="background: var(--bg-card); border-color: var(--border-soft);">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold">Tambah AI Provider</h3>
                    <button @click="showing=false" class="btn-ghost px-2">✕</button>
                </div>
                <form @submit.prevent="submit" class="mt-5 space-y-4">
                    <div>
                        <label class="text-xs font-semibold">Provider *</label>
                        <select v-model="form.provider" @change="onProviderChange" class="input-base mt-1 w-full" required>
                            <option value="">— Pilih provider —</option>
                            <option v-for="ap in availableToAdd" :key="ap.value" :value="ap.value">{{ ap.label }} ({{ ap.value }})</option>
                        </select>
                        <p v-if="form.errors.provider" class="mt-1 text-xs" style="color: var(--danger);">{{ form.errors.provider }}</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold">API Key *</label>
                        <input v-model="form.api_key" type="password" class="input-base mt-1 w-full font-mono text-sm" placeholder="sk-... atau AIza..." required />
                        <p v-if="form.errors.api_key" class="mt-1 text-xs" style="color: var(--danger);">{{ form.errors.api_key }}</p>
                        <p v-if="form.errors.enabled_models" class="mt-1 text-xs" style="color: var(--danger);">{{ form.errors.enabled_models }}</p>
                        <p class="mt-1 text-xs" style="color: var(--text-soft);">Akan disimpan ter-encrypt (APP_KEY). Tidak tampil plain text lagi.</p>
                    </div>
                    <div>
                        <label class="text-xs font-semibold">Base URL (opsional)</label>
                        <input v-model="form.base_url" class="input-base mt-1 w-full font-mono text-sm" placeholder="https://api.openai.com/v1 — kosongkan jika default" />
                        <p v-if="form.errors.base_url" class="mt-1 text-xs" style="color: var(--danger);">{{ form.errors.base_url }}</p>
                        <p class="text-xs mt-1" style="color: var(--text-soft);">Untuk OpenRouter / proxy self-host.</p>
                    </div>

                    <div class="rounded-xl border p-4 space-y-3" style="border-color: var(--border-soft); background: var(--bg-elevated);">
                        <div class="flex items-center justify-between">
                            <p class="text-xs font-bold uppercase tracking-widest" style="color: var(--text-soft);">Model dari Provider</p>
                            <button type="button" @click="fetchPreview" :disabled="previewLoading" class="btn-secondary text-xs px-3 py-1.5">{{ previewLoading ? 'Fetching...' : 'Fetch Models' }}</button>
                        </div>
                        <p v-if="previewError" class="text-xs rounded-lg px-3 py-2" style="background: color-mix(in srgb, var(--danger) 10%, transparent); color: var(--danger);">{{ previewError }} — fallback ke default list.</p>
                        <p v-if="previewModels.length===0" class="text-xs" style="color: var(--text-soft);">Klik Fetch Models untuk ambil daftar model live via API Key. Kalau gagal, default list akan dipakai.</p>
                        <div v-else class="max-h-48 overflow-y-auto space-y-1 rounded-lg border p-2" style="border-color: var(--border-soft); background: var(--bg-card);">
                            <label v-for="m in previewModels" :key="m.id" class="flex items-center gap-2 px-2 py-1.5 rounded hover:bg-white/5 cursor-pointer text-sm">
                                <input type="checkbox" :checked="form.enabled_models.includes(m.id)" @change="toggleModel(m.id)" />
                                <span class="font-mono text-xs flex-1">{{ m.id }}</span>
                            </label>
                        </div>
                        <p class="text-xs" style="color: var(--text-soft);">{{ form.enabled_models.length }} model akan diaktifkan untuk member.</p>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button type="button" @click="showing=false" class="btn-ghost flex-1">Batal</button>
                        <button type="submit" :disabled="form.processing || !form.provider || !form.api_key" class="btn-primary flex-1" :style="!form.provider || !form.api_key ? 'opacity:.5; cursor:not-allowed;' : ''">{{ form.processing ? 'Menyimpan...' : 'Tambah Provider' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
