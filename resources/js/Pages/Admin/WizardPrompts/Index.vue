<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed, nextTick } from 'vue';

const props = defineProps({
    prompts: Array,
    hasEmpty: Boolean,
    allowedVars: Array,
});

const showEdit = ref(false);
const editingStep = ref(null);
const userTemplateRef = ref(null);
const showHistoryFor = ref(null);
const historyVersions = ref([]);
const historyLoading = ref(false);
const historyPrompt = ref(null);

const testStep = ref(null);
const testLoading = ref(false);
const testResult = ref(null);
const testTab = ref('rendered');
const testUserMessage = ref('');

const form = useForm({
    name: '',
    system: '',
    user_template: '',
    json_schema: '',
    is_active: true,
});

const jsonValid = computed(() => {
    if (!form.json_schema || !form.json_schema.trim()) return { ok: true, msg: 'Kosong = tidak ada validasi JSON' };
    try { JSON.parse(form.json_schema); return { ok: true, msg: 'JSON valid ✓' }; }
    catch (e) { return { ok: false, msg: e.message }; }
});

const previewRendered = computed(() => {
    if (!form.user_template) return '—';
    // cheap client preview with dummy
    const d = dummyVars(form.user_template);
    let out = form.user_template;
    for (const [k, v] of Object.entries(d)) {
        out = out.split('{{' + k + '}}').join(v);
    }
    // also alias
    out = out.split('{{industry}}').join(d.industry_or_auto);
    out = out.split('{{project_title}}').join(d.title);
    out = out.replace(/\{\{[^}]+\}\}/g, '-');
    return out;
});

function dummyVars(template) {
    return {
        title: 'Kasir UMKM Offline-First',
        description: 'App kasir untuk toko kelontong, owner rekap manual di buku',
        user_message: testUserMessage.value || 'Aku mau app bisa cetak struk & rekap harian otomatis, mirip https://meterpams.com — bedanya untuk toko kelontong',
        attachment_text: 'Ringkasan MeterPAMS: data pelanggan, area, petugas, tarif progresif/flat, QR, foto meter, print bluetooth, WA.',
        history: 'USER: mau kasir sederhana\nASSISTANT: oke, target UMKM...',
        step1_content: '{"problem_statement":"Owner rekap manual..."}',
        step1_final: '{"problem_statement":"Owner rekap manual..."}',
        step2_final: '{"kpis":[{"nama":"Transaksi/hari"}]}',
        step3_final: '{"architecture":{"recommended":"modular_monolith"}}',
        stack: 'Laravel + Vue + MySQL',
        industry_or_auto: 'UMKM / Retail',
        industry: 'UMKM / Retail',
        project_title: 'Kasir UMKM Offline-First',
        all_steps: '{"step1":"...","step2":"..."}',
    };
}

function openEdit(prompt) {
    editingStep.value = prompt.step;
    form.name = prompt.name;
    form.system = prompt.system === '—' ? '' : prompt.system;
    form.user_template = prompt.user_template === '—' ? '' : prompt.user_template;
    form.json_schema = prompt.json_schema || '';
    form.is_active = prompt.is_active;
    testResult.value = null;
    testStep.value = null;
    showEdit.value = true;
}

function closeEdit() {
    showEdit.value = false;
    editingStep.value = null;
    form.clearErrors();
}

function insertVar(v) {
    const tag = `{{${v}}}`;
    const el = userTemplateRef.value;
    if (!el) {
        form.user_template += (form.user_template ? ' ' : '') + tag;
        return;
    }
    const start = el.selectionStart ?? form.user_template.length;
    const end = el.selectionEnd ?? start;
    const before = form.user_template.slice(0, start);
    const after = form.user_template.slice(end);
    form.user_template = before + tag + after;
    nextTick(() => {
        el.focus();
        const pos = start + tag.length;
        el.setSelectionRange(pos, pos);
    });
}

function submitEdit() {
    if (!editingStep.value) return;
    form.put(route('admin.wizard-prompts.update', editingStep.value), {
        preserveScroll: true,
        onSuccess: () => { showEdit.value = false; },
    });
}

async function doTest(step, fromModal = false) {
    const msg = fromModal ? (testUserMessage.value || undefined) : undefined;
    testLoading.value = true;
    testResult.value = null;
    testStep.value = step;
    testTab.value = 'rendered';
    try {
        const csrf = document.querySelector('meta[name=\"csrf-token\"]')?.content || '';
        const res = await fetch(route('admin.wizard-prompts.test', step), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify(msg ? { user_message: msg } : {}),
        });
        const data = await res.json();
        if (!res.ok && !data.ai_raw) throw new Error(data.message || `HTTP ${res.status}`);
        testResult.value = data;
        if (data.ai_raw && data.ai_raw.error && !data.ai_parsed) testTab.value = 'raw';
        else if (data.ai_parsed) testTab.value = 'parsed';
    } catch (e) {
        testResult.value = { error: e.message, rendered_user: '—', ai_raw: { error: e.message } };
    } finally {
        testLoading.value = false;
    }
}

async function openHistory(prompt) {
    showHistoryFor.value = prompt.step;
    historyLoading.value = true;
    historyVersions.value = [];
    historyPrompt.value = prompt;
    try {
        const res = await fetch(route('admin.wizard-prompts.versions', prompt.step), { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        historyVersions.value = data.versions || [];
        historyPrompt.value = data.prompt || prompt;
    } catch (e) {
        historyVersions.value = [];
    } finally {
        historyLoading.value = false;
    }
}

function closeHistory() {
    showHistoryFor.value = null;
}

function rollback(step, version) {
    if (!confirm(`Rollback L${step} ke v${version}? Ini akan buat versi baru dari snapshot tersebut.`)) return;
    router.post(route('admin.wizard-prompts.rollback', [step, version]), {}, {
        preserveScroll: true,
        onSuccess: () => { closeHistory(); },
    });
}

function snippet(txt, len = 140) {
    if (!txt || txt === '—') return '—';
    const s = txt.replace(/\s+/g, ' ').trim();
    return s.length > len ? s.slice(0, len) + '…' : s;
}

const chipLabel = (v) => '{{' + v + '}}';
</script>

<template>
    <Head title="Wizard Prompts" />
    <AdminLayout>
        <template #header>
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-medium" style="color: var(--brand);">AI & Sistem • Superadmin</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">Wizard Prompts</h1>
                    <p class="mt-2 max-w-2xl text-sm" style="color: var(--text-muted);">
                        Kelola System Prompt + User Template + JSON Schema untuk 8 langkah wizard. Edit di sini langsung mempengaruhi wizard member setelah cache 60 detik — tanpa deploy.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="rounded-full px-3 py-1 text-xs font-bold" style="background: color-mix(in srgb, var(--brand) 12%, transparent); color: var(--brand);">8 Steps</span>
                    <span v-if="hasEmpty" class="rounded-full px-3 py-1 text-xs font-bold" style="background: color-mix(in srgb, var(--warning) 14%, transparent); color: var(--warning);">Belum ada di DB — fallback config</span>
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-[90rem] px-5 py-8 sm:px-8">
            <!-- Banner jika DB kosong -->
            <div v-if="hasEmpty" class="mb-6 rounded-xl border px-4 py-3 text-sm flex gap-3" style="border-color: color-mix(in srgb, var(--warning) 30%, var(--border-soft)); background: color-mix(in srgb, var(--warning) 8%, transparent); color: var(--text-main);">
                <span class="text-lg">⚠️</span>
                <div>
                    <p class="font-semibold" style="color: var(--warning);">Belum ada prompt di DB — wizard pakai config/ai.php</p>
                    <p class="text-xs mt-1" style="color: var(--text-muted);">Jalankan <code class="rounded px-1 py-0.5" style="background: var(--bg-elevated);">php artisan db:seed --class=WizardPromptSeeder</code> atau simpan salah satu kartu untuk seed manual.</p>
                </div>
            </div>

            <!-- Grid 8 cards -->
            <div class="grid gap-4 lg:grid-cols-2">
                <div v-for="p in prompts" :key="p.step" class="card flex flex-col gap-3" :style="p.is_active ? '' : 'border-color: color-mix(in srgb, var(--warning) 30%, var(--border-soft)); background: color-mix(in srgb, var(--warning) 4%, var(--bg-card));'">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg text-xs font-bold text-white" style="background: var(--brand);">L{{ p.step }}</span>
                            <div>
                                <p class="text-sm font-semibold leading-none">{{ p.name }}</p>
                                <p class="text-xs mt-1" style="color: var(--text-soft);">v{{ p.version_count || 1 }} • {{ p.updated_at || 'config' }} • <span :style="p.is_active ? 'color: var(--success);' : 'color: var(--warning);'">{{ p.is_active ? 'Aktif' : 'Nonaktif (pakai config)' }}</span> • <span style="color: var(--text-soft);">{{ p.source }}</span></p>
                            </div>
                        </div>
                        <span v-if="!p.exists_in_db" class="rounded-full px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide" style="background: var(--bg-elevated); color: var(--text-soft);">config</span>
                    </div>

                    <div class="space-y-2">
                        <div>
                            <p class="text-[0.65rem] font-bold uppercase tracking-widest" style="color: var(--text-soft);">System</p>
                            <p class="mt-1 rounded-lg px-3 py-2 text-xs font-mono leading-relaxed line-clamp-2" style="background: var(--bg-elevated); color: var(--text-muted);">{{ snippet(p.system, 160) }}</p>
                        </div>
                        <div>
                            <p class="text-[0.65rem] font-bold uppercase tracking-widest" style="color: var(--text-soft);">User Template</p>
                            <p class="mt-1 rounded-lg px-3 py-2 text-xs font-mono leading-relaxed line-clamp-2" style="background: var(--bg-elevated); color: var(--text-muted);">{{ snippet(p.user_template, 180) }}</p>
                        </div>
                    </div>

                    <div class="mt-1 flex flex-wrap gap-2">
                        <button type="button" class="btn-primary px-3 py-2 text-xs" @click="openEdit(p)">Edit</button>
                        <button type="button" class="btn-secondary px-3 py-2 text-xs" :disabled="testLoading && testStep===p.step" @click="doTest(p.step, false)">
                            <span v-if="testLoading && testStep===p.step">Testing…</span>
                            <span v-else>Test</span>
                        </button>
                        <button type="button" class="btn-ghost px-3 py-2 text-xs border" style="border-color: var(--border-soft);" @click="openHistory(p)">History ({{ p.version_count }})</button>
                    </div>

                    <!-- Inline test result for this card -->
                    <div v-if="testStep===p.step && testResult" class="mt-2 rounded-xl border overflow-hidden" style="border-color: var(--border-soft); background: var(--bg-elevated);">
                        <div class="flex gap-1 p-2 border-b" style="border-color: var(--border-soft);">
                            <button v-for="t in [{k:'rendered',l:'Rendered'},{k:'raw',l:'AI Raw'},{k:'parsed',l:'Parsed JSON'}]" :key="t.k" class="rounded-full px-3 py-1 text-xs font-semibold" :style="testTab===t.k ? 'background: var(--brand); color: white;' : 'background: var(--bg-card); color: var(--text-muted);'" @click="testTab=t.k">{{ t.l }}</button>
                            <span class="ml-auto text-xs py-1" style="color: var(--text-soft);">{{ testResult.latency_ms }}ms • {{ testResult.prompt_source }}</span>
                        </div>
                        <div class="p-3 max-h-64 overflow-auto">
                            <div v-if="testTab==='rendered'" class="space-y-2">
                                <p class="text-xs font-bold" style="color: var(--text-soft);">System</p>
                                <pre class="whitespace-pre-wrap break-words rounded-lg p-3 text-xs font-mono" style="background: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-soft);">{{ testResult.rendered_system }}</pre>
                                <p class="text-xs font-bold mt-2" style="color: var(--text-soft);">User (rendered)</p>
                                <pre class="whitespace-pre-wrap break-words rounded-lg p-3 text-xs font-mono" style="background: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-soft);">{{ testResult.rendered_user }}</pre>
                            </div>
                            <div v-if="testTab==='raw'">
                                <pre class="whitespace-pre-wrap break-words rounded-lg p-3 text-xs font-mono" style="background: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-soft);">{{ JSON.stringify(testResult.ai_raw, null, 2) }}</pre>
                                <p v-if="testResult.ai_raw?.error" class="mt-2 text-xs font-semibold" style="color: var(--danger);">Error: {{ testResult.ai_raw.error }}</p>
                                <p v-if="testResult.parse_error" class="mt-1 text-xs" style="color: var(--warning);">Parse: {{ testResult.parse_error }}</p>
                            </div>
                            <div v-if="testTab==='parsed'">
                                <pre v-if="testResult.ai_parsed" class="whitespace-pre-wrap break-words rounded-lg p-3 text-xs font-mono" style="background: var(--bg-card); color: var(--text-main); border: 1px solid var(--border-soft);">{{ JSON.stringify(testResult.ai_parsed, null, 2) }}</pre>
                                <p v-else class="text-xs" style="color: var(--text-muted);">Tidak ada parsed JSON — cek tab AI Raw. {{ testResult.parse_error || '' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info card efekt ke wizard member -->
            <div class="mt-6 card" style="border-style: dashed;">
                <h3 class="font-semibold text-sm">Efek ke wizard member</h3>
                <p class="mt-2 text-sm leading-relaxed" style="color: var(--text-muted);">
                    Edit di sini langsung mempengaruhi <code class="rounded px-1.5 py-0.5 text-xs" style="background: var(--bg-elevated);">/member/projects/{id}/wizard/{step}</code> setelah cache 60 detik.
                    Gunakan toggle <b>Nonaktif</b> untuk fallback instan ke <code>config/ai.php</code> jika prompt baru bermasalah. Test tidak menyimpan ke project & dibatasi 5×/menit.
                </p>
                <div class="mt-3 flex flex-wrap gap-2 text-xs" style="color: var(--text-soft);">
                    <span class="rounded-full px-2.5 py-1" style="background: var(--bg-elevated);">Clone URL: &#123;&#123;attachment_text&#125;&#125; di L1 otomatis terisi dari fetch URL</span>
                    <span class="rounded-full px-2.5 py-1" style="background: var(--bg-elevated);">L5 multi-tenancy: DB per tenant vs shared tenant_id</span>
                </div>
            </div>
        </div>

        <!-- Modal Edit -->
        <div v-if="showEdit" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 backdrop-blur-sm" style="background: rgba(0,0,0,0.55);" @click="closeEdit"></div>
            <div class="relative w-full max-w-5xl max-h-[92vh] overflow-hidden rounded-2xl shadow-2xl flex flex-col" style="background: var(--bg-card); border: 1px solid var(--border-soft);">
                <div class="flex items-center justify-between px-6 py-4 border-b" style="border-color: var(--border-soft);">
                    <div>
                        <h2 class="text-lg font-bold">Edit Prompt — L{{ editingStep }} · {{ form.name }}</h2>
                        <p class="text-xs mt-1" style="color: var(--text-soft);">Step {{ editingStep }} immutable · variabel harus dari whitelist · cache invalidate otomatis</p>
                    </div>
                    <button class="btn-ghost px-2" @click="closeEdit">✕</button>
                </div>

                <div class="flex-1 overflow-auto">
                    <div class="grid lg:grid-cols-[1.35fr_0.85fr] gap-0">
                        <!-- Left: form -->
                        <div class="p-6 space-y-4 border-r" style="border-color: var(--border-soft);">
                            <div>
                                <label class="text-xs font-bold uppercase tracking-widest" style="color: var(--text-soft);">Nama Step</label>
                                <input v-model="form.name" class="input-base mt-1.5" placeholder="Problem & Vision" maxlength="80" />
                                <p v-if="form.errors.name" class="mt-1 text-xs" style="color: var(--danger);">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold uppercase tracking-widest" style="color: var(--text-soft);">System Prompt</label>
                                    <span class="text-xs" style="color: var(--text-soft);">{{ form.system.length }}/8000</span>
                                </div>
                                <textarea v-model="form.system" rows="8" class="input-base mt-1.5 font-mono text-xs leading-relaxed" placeholder="Kamu Ideation Partner..."></textarea>
                                <p v-if="form.errors.system" class="mt-1 text-xs" style="color: var(--danger);">{{ form.errors.system }}</p>
                            </div>

                            <div>
                                <div class="flex items-center justify-between">
                                    <label class="text-xs font-bold uppercase tracking-widest" style="color: var(--text-soft);">User Template (dengan &#123;&#123;var&#125;&#125;)</label>
                                    <span class="text-xs" style="color: var(--text-soft);">{{ form.user_template.length }}/12000</span>
                                </div>
                                <!-- Chip vars -->
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    <button v-for="v in allowedVars" :key="v" type="button" class="rounded-full px-2.5 py-1 text-xs font-mono font-semibold border hover:opacity-90" style="background: color-mix(in srgb, var(--brand) 10%, transparent); border-color: color-mix(in srgb, var(--brand) 20%, var(--border-soft)); color: var(--brand);" @click="insertVar(v)">{{ chipLabel(v) }}</button>
                                </div>
                                <textarea ref="userTemplateRef" v-model="form.user_template" rows="14" class="input-base mt-2 font-mono text-xs leading-relaxed" placeholder="KONTEKS PROJECT:&#10;Judul: {{title}}..."></textarea>
                                <p v-if="form.errors.user_template" class="mt-1 text-xs" style="color: var(--danger);">{{ form.errors.user_template }}</p>
                                <p class="mt-1 text-xs" style="color: var(--text-soft);">Klik chip untuk insert di posisi cursor. Variabel di luar whitelist akan ditolak saat save.</p>
                            </div>

                            <div>
                                <label class="text-xs font-bold uppercase tracking-widest" style="color: var(--text-soft);">JSON Schema (opsional)</label>
                                <textarea v-model="form.json_schema" rows="4" class="input-base mt-1.5 font-mono text-xs" placeholder='{"type":"object","required":["problem_statement"]}'></textarea>
                                <p class="mt-1 text-xs" :style="jsonValid.ok ? 'color: var(--success);' : 'color: var(--danger);'">{{ jsonValid.msg }}</p>
                                <p v-if="form.errors.json_schema" class="mt-1 text-xs" style="color: var(--danger);">{{ form.errors.json_schema }}</p>
                            </div>

                            <label class="flex items-center gap-3 cursor-pointer rounded-xl px-3 py-3 border" style="border-color: var(--border-soft); background: var(--bg-elevated);">
                                <input type="checkbox" v-model="form.is_active" class="h-4 w-4 rounded" />
                                <span class="text-sm font-semibold">Aktif</span>
                                <span class="text-xs" style="color: var(--text-muted);">— jika nonaktif, wizard pakai fallback config + banner warning</span>
                            </label>
                        </div>

                        <!-- Right: Live Preview -->
                        <div class="p-6 space-y-4" style="background: color-mix(in srgb, var(--bg-elevated) 50%, var(--bg-card));">
                            <h3 class="text-sm font-bold">Live Preview</h3>
                            <p class="text-xs" style="color: var(--text-soft);">Render user_template dengan dummy data (MeterPAMS, UMKM) secara real-time.</p>

                            <div>
                                <label class="text-xs font-semibold" style="color: var(--text-soft);">Coba user_message untuk preview & test</label>
                                <input v-model="testUserMessage" class="input-base mt-1.5 text-xs" placeholder="Aku mau app seperti https://meterpams.com tapi untuk..." />
                            </div>

                            <div>
                                <p class="text-xs font-bold uppercase tracking-widest" style="color: var(--text-soft);">System (preview)</p>
                                <pre class="mt-1.5 whitespace-pre-wrap break-words rounded-xl p-3 text-xs font-mono leading-relaxed" style="background: var(--bg-card); border: 1px solid var(--border-soft); color: var(--text-main);">{{ form.system || '—' }}</pre>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-widest" style="color: var(--text-soft);">User (rendered)</p>
                                <pre class="mt-1.5 max-h-64 overflow-auto whitespace-pre-wrap break-words rounded-xl p-3 text-xs font-mono leading-relaxed" style="background: var(--bg-card); border: 1px solid var(--border-soft); color: var(--text-main);">{{ previewRendered }}</pre>
                            </div>

                            <div v-if="jsonValid.ok && form.json_schema" class="rounded-xl p-3 border" style="border-color: color-mix(in srgb, var(--success) 30%, var(--border-soft)); background: color-mix(in srgb, var(--success) 6%, transparent);">
                                <p class="text-xs font-bold" style="color: var(--success);">JSON Schema valid</p>
                                <pre class="mt-1 whitespace-pre-wrap break-words text-xs font-mono" style="color: var(--text-muted);">{{ form.json_schema }}</pre>
                            </div>

                            <!-- Test inside modal -->
                            <div class="rounded-xl border p-3 space-y-2" style="border-color: var(--border-soft); background: var(--bg-card);">
                                <p class="text-xs font-bold">Test Prompt</p>
                                <p class="text-xs" style="color: var(--text-soft);">Panggil AI dengan model default Utama/Vision (jika ada gambar). Limit 5/menit.</p>
                                <button type="button" class="btn-secondary w-full justify-center text-xs" :disabled="testLoading" @click="doTest(editingStep, true)">
                                    <span v-if="testLoading">Testing… {{ testResult?.latency_ms ? testResult.latency_ms+'ms' : '' }}</span>
                                    <span v-else>Test Prompt (AI)</span>
                                </button>
                                <div v-if="testResult && testStep===editingStep" class="mt-2 rounded-lg border overflow-hidden" style="border-color: var(--border-soft);">
                                    <div class="flex gap-1 p-2 border-b" style="border-color: var(--border-soft); background: var(--bg-elevated);">
                                        <button v-for="t in [{k:'rendered',l:'Rendered'},{k:'raw',l:'AI Raw'},{k:'parsed',l:'Parsed'}]" :key="t.k" class="rounded-full px-2.5 py-1 text-xs font-semibold" :style="testTab===t.k ? 'background: var(--brand); color: white;' : 'background: var(--bg-card); color: var(--text-muted);'" @click="testTab=t.k">{{ t.l }}</button>
                                        <span class="ml-auto text-xs py-1" style="color: var(--text-soft);">{{ testResult.latency_ms }}ms</span>
                                    </div>
                                    <div class="p-2 max-h-56 overflow-auto">
                                        <pre v-if="testTab==='rendered'" class="whitespace-pre-wrap break-words text-xs font-mono" style="color: var(--text-main);">{{ testResult.rendered_user }}</pre>
                                        <pre v-if="testTab==='raw'" class="whitespace-pre-wrap break-words text-xs font-mono" style="color: var(--text-main);">{{ JSON.stringify(testResult.ai_raw, null, 2) }}</pre>
                                        <pre v-if="testTab==='parsed'" class="whitespace-pre-wrap break-words text-xs font-mono" style="color: var(--text-main);">{{ testResult.ai_parsed ? JSON.stringify(testResult.ai_parsed, null, 2) : '— ' + (testResult.parse_error || 'tidak ada JSON valid') }}</pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3 px-6 py-4 border-t" style="border-color: var(--border-soft); background: var(--bg-elevated);">
                    <p v-if="form.errors && Object.keys(form.errors).length" class="text-xs" style="color: var(--danger);">{{ Object.values(form.errors).flat().join(' | ') }}</p>
                    <span v-else class="text-xs" style="color: var(--text-soft);">Saving akan buat versi baru + invalidate cache.</span>
                    <div class="flex gap-2">
                        <button type="button" class="btn-ghost" @click="closeEdit">Batal</button>
                        <button type="button" class="btn-primary" :disabled="form.processing" @click="submitEdit">
                            <span v-if="form.processing">Menyimpan…</span>
                            <span v-else>Simpan</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Drawer -->
        <div v-if="showHistoryFor !== null" class="fixed inset-0 z-50 flex justify-end">
            <div class="absolute inset-0 backdrop-blur-sm" style="background: rgba(0,0,0,0.45);" @click="closeHistory"></div>
            <div class="relative w-full max-w-2xl h-full overflow-auto shadow-2xl" style="background: var(--bg-card); border-left: 1px solid var(--border-soft);">
                <div class="sticky top-0 z-10 px-6 py-4 border-b flex items-center justify-between" style="background: var(--bg-card); border-color: var(--border-soft);">
                    <div>
                        <h3 class="font-bold">History — L{{ showHistoryFor }} · {{ historyPrompt?.name || '' }}</h3>
                        <p class="text-xs" style="color: var(--text-soft);">{{ historyVersions.length }} versi</p>
                    </div>
                    <button class="btn-ghost px-2" @click="closeHistory">✕</button>
                </div>

                <div class="p-6">
                    <div v-if="historyLoading" class="text-sm py-8 text-center" style="color: var(--text-muted);">Memuat…</div>
                    <div v-else-if="historyVersions.length===0" class="text-sm py-8 text-center" style="color: var(--text-muted);">Belum ada versi.</div>
                    <div v-else class="space-y-3">
                        <div v-for="v in historyVersions" :key="v.id" class="rounded-xl border p-4" style="border-color: var(--border-soft); background: var(--bg-elevated);">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-bold">v{{ v.version }} — {{ v.name }}</p>
                                    <p class="text-xs mt-1" style="color: var(--text-soft);">{{ v.created_at }} • oleh {{ v.created_by }}<span v-if="v.change_note"> • {{ v.change_note }}</span></p>
                                </div>
                                <button type="button" class="btn-secondary px-3 py-1.5 text-xs" @click="rollback(showHistoryFor, v.version)">Rollback</button>
                            </div>
                            <details class="mt-3">
                                <summary class="cursor-pointer text-xs font-semibold" style="color: var(--brand);">Lihat system + template</summary>
                                <div class="mt-2 space-y-2">
                                    <pre class="whitespace-pre-wrap break-words rounded-lg p-2 text-xs font-mono" style="background: var(--bg-card); border: 1px solid var(--border-soft);">{{ v.system }}</pre>
                                    <pre class="whitespace-pre-wrap break-words rounded-lg p-2 text-xs font-mono max-h-40 overflow-auto" style="background: var(--bg-card); border: 1px solid var(--border-soft);">{{ v.user_template }}</pre>
                                    <pre v-if="v.json_schema" class="whitespace-pre-wrap break-words rounded-lg p-2 text-xs font-mono" style="background: var(--bg-card); border: 1px solid var(--border-soft);">{{ v.json_schema }}</pre>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
