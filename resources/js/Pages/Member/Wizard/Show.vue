<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import MemberLayout from '@/Layouts/MemberLayout.vue';
import FolderNode from '@/Components/Wizard/FolderNode.vue';
import AssistantBubble from '@/Components/Wizard/AssistantBubble.vue';

const props = defineProps({
    project: Object,
    step: Number,
    stepTitle: String,
    section: Object,
    allSections: Array,
    l1final: [Object, String],
    l2final: [Object, String],
    availableModels: Array,
});

const page = usePage();
const message = ref('');
const attachments = ref([]);
const fileInput = ref(null);
const selectedModel = ref('');
const loading = ref(false);
const error = ref('');
const quotaError = ref(false);
const history = ref(props.section?.history ? [...props.section.history] : []);
const finalOutput = ref(props.section?.final_output ? JSON.parse(JSON.stringify(props.section.final_output)) : null);
const modelOptions = ref([]);
const stackInput = ref('');
const industryInput = ref('');
// L3 states
const activeTab = ref('architecture'); // architecture | modules | folder
const newModuleName = ref('');
const newModuleDesc = ref('');
const newModulePriority = ref('Must');
const bulkModules = ref('');
const showBulk = ref(false);

watch(() => props.section, (v) => {
    history.value = v?.history ? [...v.history] : [];
    finalOutput.value = v?.final_output ? JSON.parse(JSON.stringify(v.final_output)) : null;
}, { deep: true });

const stepsMeta = [
    { n:1, title:'Problem & Vision' },
    { n:2, title:'Success Metrics' },
    { n:3, title:'Functional' },
    { n:4, title:'Diagram' },
    { n:5, title:'Database' },
    { n:6, title:'NFR' },
    { n:7, title:'UX Flow' },
    { n:8, title:'Output' },
];

const progress = computed(() => {
    const filled = (props.allSections || []).filter(s=>s.has_content).length;
    return Math.round(filled/8*100);
});

const isStepLocked = (n) => n > 3; // L4-8 coming soon

const parseModels = (data) => {
    const flat = [];
    if (Array.isArray(data)) {
        data.forEach(item => {
            if (item.value) {
                flat.push({ value: item.value, label: item.display || item.value });
            } else if (item.model && item.provider) {
                flat.push({ value: `${item.provider}:${item.model}`, label: `${item.provider}:${item.model}` });
            } else if (Array.isArray(item.models)) {
                item.models.forEach(m => {
                    const id = typeof m === 'string' ? m : (m.id || m.model || '');
                    if (id) flat.push({ value: `${item.provider}:${id}`, label: `${item.provider}:${id}` });
                });
            } else if (typeof item === 'string') {
                flat.push({ value: item, label: item });
            }
        });
    } else if (data && typeof data === 'object') {
        Object.entries(data).forEach(([provider, models]) => {
            if (Array.isArray(models)) models.forEach(m=> flat.push({ value: `${provider}:${m.id||m.model||m}`, label: `${provider}:${m.id||m.model||m}` }));
            else if (models?.models) models.models.forEach(m=> flat.push({ value: `${provider}:${m.id}`, label: `${provider}:${m.id}` }));
        });
    }
    return flat;
};

// init from server prop moved into onMounted to avoid setup-time proxy recursion
onMounted(async () => {
    if (props.availableModels?.length) {
        const init = parseModels(props.availableModels);
        modelOptions.value = init;
        if (init.length && !selectedModel.value) selectedModel.value = init[0].value;
    }
    try {
        const res = await fetch(route('member.ai.models'), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
        if (res.ok) {
            const data = await res.json();
            const flat = parseModels(data);
            if (flat.length) {
                modelOptions.value = flat;
                if (!selectedModel.value) selectedModel.value = flat[0].value;
            }
        } else {
            console.warn('[wizard] /member/ai/models status', res.status, await res.text());
        }
    } catch (e) {
        console.warn('[wizard] fetch models failed', e);
    }
});

const onFiles = (e) => {
    const files = Array.from(e.target.files || []);
    if (files.length + attachments.value.length > 3) {
        error.value = 'Maks 3 file per pesan';
        return;
    }
    attachments.value.push(...files);
    error.value = '';
};
const removeFile = (i) => attachments.value.splice(i,1);

const sendChat = async () => {
    if (!message.value.trim() && attachments.value.length===0) return;
    error.value=''; quotaError.value=false; loading.value=true;
    const fd = new FormData();
    fd.append('message', message.value);
    if (selectedModel.value) fd.append('model', selectedModel.value);
    if (stackInput.value) fd.append('stack', stackInput.value);
    if (industryInput.value) fd.append('industry', industryInput.value);
    attachments.value.forEach(f=> fd.append('attachments[]', f));
    try {
        const res = await fetch(route('member.wizard.chat', [props.project.id, props.step]), {
            method:'POST',
            headers:{ 'X-CSRF-TOKEN': page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content, 'Accept':'application/json' },
            body: fd,
        });
        const data = await res.json();
        if (!res.ok) {
            error.value = data.error || 'Gagal generate';
            if (data.code==='quota_exceeded') quotaError.value=true;
            if (data.code==='max_chats_reached') error.value = data.error;
            loading.value=false; return;
        }
        history.value = data.history || [];
        finalOutput.value = data.final_output || finalOutput.value;
        message.value=''; attachments.value=[]; if(fileInput.value) fileInput.value.value='';
    } catch(e){ error.value = e.message; } finally{ loading.value=false; }
};

const saveFinal = async () => {
    if (!finalOutput.value) return;
    loading.value=true; error.value='';
    try{
        const res = await fetch(route('member.wizard.final', [props.project.id, props.step]), {
            method:'PUT',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN': page.props.csrf_token || document.querySelector('meta[name="csrf-token"]')?.content, 'Accept':'application/json'},
            body: JSON.stringify({ final_output: finalOutput.value })
        });
        const data = await res.json();
        if(!res.ok) throw new Error(data.message||'Gagal simpan');
        finalOutput.value = data.final_output;
    }catch(e){ error.value=e.message; } finally{ loading.value=false; }
};

// L3 helpers
const arch = computed(()=> finalOutput.value?.architecture || null);
const modules = computed(()=> finalOutput.value?.modules || []);
const folderRoot = computed(()=> finalOutput.value?.folder_structure?.root || props.project.slug || 'project');
const folderTree = computed(()=> {
    const fs = finalOutput.value?.folder_structure;
    if(!fs) return [];
    if(Array.isArray(fs.tree)) return fs.tree;
    if(Array.isArray(fs)) return fs;
    return [];
});

const toggleModule = (id) => {
    const m = finalOutput.value.modules.find(x=>x.id===id);
    if(m) m.checked = !m.checked;
};
const addModule = () => {
    if(!newModuleName.value.trim()) return;
    if(!finalOutput.value) finalOutput.value={ architecture: arch.value, modules:[], folder_structure:{root:folderRoot.value, tree:[]} };
    if(!finalOutput.value.modules) finalOutput.value.modules=[];
    finalOutput.value.modules.push({ id: 'mod_'+Date.now(), nama:newModuleName.value.trim(), deskripsi:newModuleDesc.value.trim(), prioritas:newModulePriority.value, estimasi:'', checked:true });
    newModuleName.value=''; newModuleDesc.value='';
};
const addBulk = () => {
    const lines = bulkModules.value.split('\n').map(s=>s.trim()).filter(Boolean);
    if(!lines.length) return;
    if(!finalOutput.value) finalOutput.value={modules:[]};
    if(!finalOutput.value.modules) finalOutput.value.modules=[];
    lines.forEach(l=>{
        const [nama, ...rest] = l.split('-');
        finalOutput.value.modules.push({ id:'mod_'+Date.now()+Math.random().toString(36).slice(2,5), nama:nama.trim(), deskripsi:rest.join('-').trim(), prioritas:'Should', checked:true });
    });
    bulkModules.value=''; showBulk.value=false;
};
const removeModule = (id) => {
    finalOutput.value.modules = finalOutput.value.modules.filter(x=>x.id!==id);
};
const selectArch = (id) => {
    if(finalOutput.value?.architecture) finalOutput.value.architecture.selected = id;
};

const downloadZip = () => {
    window.location.href = route('member.wizard.zip', props.project.id);
};

// folder tree inline edit
const folderEditPath = ref('');
const folderEditVal = ref('');
const startRename = (path, name) => { folderEditPath.value=path; folderEditVal.value=name; };
const commitRename = (node) => {
    if(folderEditVal.value.trim()) node.name = folderEditVal.value.trim();
    folderEditPath.value='';
};
const addNode = (parent, type) => {
    const name = type==='folder' ? 'NewFolder' : 'file.txt';
    if(!parent.children) parent.children=[];
    parent.children.push({ name, type, children: type==='folder' ? [] : undefined });
};
const removeByPath = (path) => {
    const idxs = path.split('-').map(Number);
    if(!finalOutput.value?.folder_structure?.tree) return;
    let arr = finalOutput.value.folder_structure.tree;
    for(let i=0;i<idxs.length-1;i++){
        const idx = idxs[i];
        arr = arr[idx]?.children;
        if(!arr) return;
    }
    arr.splice(idxs[idxs.length-1],1);
};
const handleRemove = (node, path) => removeByPath(path);
const addRootNode = (type) => {
    if(!finalOutput.value) finalOutput.value={ architecture: arch.value, modules: modules.value, folder_structure:{root:folderRoot.value, tree:[]} };
    if(!finalOutput.value.folder_structure) finalOutput.value.folder_structure={root:folderRoot.value, tree:[]};
    if(!finalOutput.value.folder_structure.tree) finalOutput.value.folder_structure.tree=[];
    finalOutput.value.folder_structure.tree.push({ name: type==='folder'?'NewFolder':'file.txt', type, children: type==='folder'?[]:undefined });
};
</script>

<template>
    <Head :title="`${stepTitle} — ${project.title}`" />
    <MemberLayout>
        <template #header>
            <div class="flex flex-col gap-3">
                <div class="flex flex-wrap items-center gap-2 text-xs" style="color: var(--text-soft);">
                    <Link :href="route('member.projects.index')" class="hover:underline">Projects</Link>
                    <span>/</span><span style="color: var(--text-main);">{{ project.title }}</span>
                    <span class="rounded-full px-2 py-0.5 text-[0.65rem] font-bold" style="background: var(--bg-elevated);">{{ progress }}% • {{ project.progress }}% progress</span>
                </div>
                <h1 class="text-xl font-bold">Langkah {{ step }} — {{ stepTitle }}</h1>
                <div class="flex gap-1.5 overflow-x-auto pb-1">
                    <template v-for="s in stepsMeta" :key="s.n">
                        <Link v-if="s.n!==step && !isStepLocked(s.n)" :href="route('member.wizard.show',[project.id,s.n])" :class="['whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-semibold border', allSections?.find(x=>x.step===s.n)?.has_content ? 'border-transparent' : '']" :style="allSections?.find(x=>x.step===s.n)?.has_content ? 'background: var(--brand); color:white;' : 'border-color: var(--border-soft); color: var(--text-muted);'">
                            {{ s.n }}. {{ s.title }}
                        </Link>
                        <span v-else-if="s.n===step" class="whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-bold" style="background: var(--brand); color: white;">{{ s.n }}. {{ s.title }}</span>
                        <span v-else class="whitespace-nowrap rounded-full px-3 py-1.5 text-xs" style="border:1px solid var(--border-soft); color: var(--text-soft); opacity:0.6;">{{ s.n }}. {{ s.title }} 🔒</span>
                    </template>
                </div>
            </div>
        </template>

        <div class="container-base py-6">
            <!-- Locked notice L4+ -->
            <div v-if="isStepLocked(step)" class="card py-12 text-center">
                <p class="font-semibold">Langkah {{ step }} — Coming Soon</p>
                <p class="mt-1 text-sm" style="color: var(--text-muted);">Fokus sekarang L1-L3. L4-L8 akan hadir setelah wizard inti selesai.</p>
                <Link :href="route('member.wizard.show',[project.id,3])" class="btn-primary mt-4">Ke Langkah 3</Link>
            </div>

            <!-- L1 & L2 Chat -->
            <div v-else-if="step===1 || step===2" class="grid gap-6 lg:grid-cols-[1.35fr_0.85fr]">
                <!-- Chat column -->
                <div class="flex flex-col rounded-2xl border" style="border-color: var(--border-soft); background: var(--bg-card);">
                    <div class="border-b px-4 py-3 flex items-center justify-between" style="border-color: var(--border-soft);">
                        <p class="text-sm font-semibold">{{ step===1 ? 'Ideation Partner — Problem & Vision' : 'Metric Suggester — KPI' }}</p>
                        <span class="text-xs" style="color: var(--text-soft);">{{ history.length }} pesan • max 10</span>
                    </div>

                    <div class="flex-1 overflow-y-auto p-4 space-y-4" style="min-height: 420px; max-height: 62vh;">
                        <div v-if="history.length===0" class="rounded-xl border border-dashed p-6 text-center" style="border-color: var(--border-soft); background: var(--bg-elevated);">
                            <p class="text-sm font-semibold">Mulai chat</p>
                            <p class="mt-1 text-xs" style="color: var(--text-muted);">
                                <span v-if="step===1">Contoh: "Aku mau bikin app kasir UMKM offline-first, owner sering rekap manual di buku"</span>
                                <span v-else>Contoh: "SaaS HRIS untuk 50-200 karyawan, target activation 60% minggu pertama"</span>
                            </p>
                        </div>

                        <AssistantBubble v-for="(m,i) in history" :key="i" :msg="m" :step="step" />

                        <div v-if="loading" class="flex justify-start">
                            <div class="rounded-2xl border px-4 py-3 text-sm animate-pulse" style="background: var(--bg-elevated); border-color: var(--border-soft);">AI sedang berpikir…</div>
                        </div>
                    </div>

                    <!-- Input bar -->
                    <div class="border-t p-3 space-y-2" style="border-color: var(--border-soft); background: var(--bg-card);">
                        <div v-if="error" class="rounded-xl border px-3 py-2 text-xs" style="border-color: color-mix(in srgb, var(--danger) 30%, var(--border-soft)); background: color-mix(in srgb, var(--danger) 8%, transparent); color: var(--danger);">
                            {{ error }}
                            <Link v-if="quotaError" :href="route('member.billing.index')" class="ml-2 underline font-bold">Upgrade →</Link>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <input v-if="step===2" v-model="industryInput" placeholder="Industri (opsional, ex: SaaS)" class="input-base flex-1 min-w-[140px] text-xs" />
                            <input v-if="step===3" v-model="stackInput" placeholder="Stack (ex: Laravel, Vue)" class="input-base flex-1 min-w-[140px] text-xs" />
                            <select v-model="selectedModel" class="input-base text-xs w-44">
                                <option value="">Model default</option>
                                <option v-for="m in modelOptions" :key="m.value || m" :value="m.value || m">{{ m.label || m }}</option>
                            </select>
                            <button type="button" class="btn-ghost border text-xs px-3" style="border-color: var(--border-soft);" @click="fileInput?.click()">+ File</button>
                            <input ref="fileInput" type="file" class="hidden" multiple accept=".pdf,.txt,.md,.jpg,.jpeg,.png,.webp" @change="onFiles" />
                        </div>

                        <div v-if="attachments.length" class="flex flex-wrap gap-1.5">
                            <span v-for="(f,idx) in attachments" :key="idx" class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs border" style="border-color: var(--border-soft); background: var(--bg-elevated);">
                                {{ f.name }} <button type="button" class="ml-1 font-bold" @click="removeFile(idx)">×</button>
                            </span>
                        </div>

                        <div class="flex gap-2">
                            <textarea v-model="message" rows="2" :placeholder="step===1 ? 'Jelaskan ide kasarmu…' : 'Jelaskan target industri & angka…'" class="input-base flex-1 resize-none text-sm" @keydown.enter.exact.prevent="sendChat" @keydown.enter.shift.exact="message+='\n'"></textarea>
                            <button type="button" class="btn-primary self-end px-5" :disabled="loading || (!message.trim() && attachments.length===0)" @click="sendChat">
                                <span v-if="loading">…</span><span v-else>Send</span>
                            </button>
                        </div>
                        <p class="text-[0.65rem]" style="color: var(--text-soft);">Enter kirim • Shift+Enter baris baru • Maks 10 chat/step • 3 file 5MB</p>
                    </div>
                </div>

                <!-- Structured output -->
                <div class="space-y-3">
                    <div class="card">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-sm">Ringkasan Terstruktur</h3>
                            <button v-if="finalOutput" type="button" class="btn-secondary text-xs py-1 px-3" @click="saveFinal" :disabled="loading">Simpan Final</button>
                        </div>

                        <div v-if="!finalOutput" class="mt-4 rounded-xl border border-dashed p-6 text-center" style="border-color: var(--border-soft);">
                            <p class="text-xs" style="color: var(--text-muted);">Belum ada output. Kirim 1 pesan untuk generate.</p>
                        </div>

                        <div v-else class="mt-3 space-y-3 text-sm">
                            <!-- L1 render -->
                            <template v-if="step===1">
                                <div class="rounded-xl p-3 border" style="border-color: var(--border-soft); background: linear-gradient(145deg, var(--bg-card), color-mix(in srgb, var(--brand) 6%, var(--bg-card)));">
                                    <p class="text-xs font-bold" style="color: var(--brand);">Problem Statement</p>
                                    <p class="mt-1 leading-relaxed">{{ finalOutput.problem_statement }}</p>
                                </div>
                                <div v-if="finalOutput.value_proposition" class="rounded-xl p-3 border" style="border-color: var(--border-soft); background: color-mix(in srgb, var(--brand-secondary) 10%, transparent);">
                                    <p class="text-xs font-bold">Value Proposition</p>
                                    <p class="mt-1">{{ finalOutput.value_proposition }}</p>
                                </div>
                                <div v-if="finalOutput.target_personas?.length">
                                    <p class="text-xs font-bold">Target Personas</p>
                                    <div class="mt-1 space-y-1.5">
                                        <div v-for="p in finalOutput.target_personas" :key="p.nama" class="rounded-lg border p-2.5" style="border-color: var(--border-soft);">
                                            <p class="text-xs font-semibold">{{ p.nama }}</p>
                                            <p class="text-xs" style="color: var(--text-muted);">{{ p.deskripsi }} • Pain: {{ p.pain }}</p>
                                        </div>
                                    </div>
                                </div>
                                <details class="text-xs"><summary class="cursor-pointer font-semibold" style="color: var(--text-muted);">Asumsi & Pertanyaan</summary><ul class="mt-1 list-disc pl-4 space-y-0.5" style="color: var(--text-muted);"><li v-for="a in finalOutput.asumsi" :key="a">{{ a }}</li><li v-for="q in finalOutput.pertanyaan_klarifikasi" :key="q">{{ q }}</li></ul></details>
                            </template>

                            <!-- L2 render -->
                            <template v-else-if="step===2">
                                <p class="text-xs font-bold" style="color: var(--brand);">Industri: {{ finalOutput.industri_terdeteksi }}</p>
                                <div class="overflow-x-auto rounded-xl border" style="border-color: var(--border-soft);">
                                    <table class="w-full text-xs">
                                        <thead style="background: var(--bg-elevated);"><tr><th class="text-left p-2">KPI</th><th class="text-left p-2">Target</th><th class="p-2">Tipe</th></tr></thead>
                                        <tbody><tr v-for="k in finalOutput.kpis" :key="k.nama" class="border-t" style="border-color: var(--border-soft);"><td class="p-2"><span class="font-semibold">{{ k.nama }}</span><span class="block" style="color: var(--text-muted);">{{ k.definisi }}</span></td><td class="p-2">{{ k.target }}</td><td class="p-2"><span class="rounded-full px-2 py-0.5 text-[0.65rem] font-bold" :style="k.tipe==='Leading' ? 'background: color-mix(in srgb, var(--brand) 14%, transparent); color:var(--brand);' : 'background: color-mix(in srgb, var(--success) 14%, transparent); color:var(--success);'">{{ k.tipe }}</span></td></tr></tbody>
                                    </table>
                                </div>
                                <div class="rounded-xl border p-2.5" style="border-color: var(--border-soft);"><p class="text-xs font-bold">North Star</p><p class="text-xs" style="color: var(--text-muted);">{{ finalOutput.north_star?.metric }} — {{ finalOutput.north_star?.alasan }}</p></div>
                            </template>
                        </div>
                    </div>

                    <div class="card py-3">
                        <p class="text-xs font-semibold">Navigasi</p>
                        <div class="mt-2 flex gap-2">
                            <Link v-if="step>1" :href="route('member.wizard.show',[project.id, step-1])" class="btn-ghost border flex-1 text-xs justify-center" style="border-color: var(--border-soft);">← Sebelumnya</Link>
                            <Link :href="route('member.wizard.show',[project.id, step+1])" class="btn-primary flex-1 text-xs justify-center">Lanjut →</Link>
                        </div>
                        <p class="mt-2 text-[0.65rem]" style="color: var(--text-soft);">Output tersimpan otomatis sebagai Final. Bisa lompat step.</p>
                    </div>
                </div>
            </div>

            <!-- L3 3 Tabs -->
            <div v-else-if="step===3" class="space-y-4">
                <!-- Chat mini for L3 -->
                <div class="card">
                    <div class="flex flex-wrap gap-2 items-end">
                        <input v-model="stackInput" placeholder="Stack pilihan (ex: Laravel, Vue, MySQL) — kosongkan untuk auto" class="input-base flex-1 min-w-[180px] text-sm" />
                        <select v-model="selectedModel" class="input-base text-xs w-44">
                            <option value="">Model default</option>
                            <option v-for="m in modelOptions" :key="m.value || m" :value="m.value || m">{{ m.label || m }}</option>
                        </select>
                        <button type="button" class="btn-ghost border text-xs" style="border-color: var(--border-soft);" @click="fileInput?.click()">+ File</button>
                        <input ref="fileInput" type="file" class="hidden" multiple accept=".pdf,.txt,.md,.jpg,.jpeg,.png,.webp" @change="onFiles" />
                    </div>
                    <div v-if="attachments.length" class="mt-2 flex flex-wrap gap-1.5">
                        <span v-for="(f,idx) in attachments" :key="idx" class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs border" style="border-color: var(--border-soft); background: var(--bg-elevated);">{{ f.name }} <button @click="removeFile(idx)" class="ml-1 font-bold">×</button></span>
                    </div>
                    <div class="mt-2 flex gap-2">
                        <textarea v-model="message" rows="2" placeholder="Ceritakan kebutuhan fungsional / vibe produk untuk generate arsitektur + modul + folder…" class="input-base flex-1 text-sm"></textarea>
                        <button class="btn-primary self-end" :disabled="loading || (!message.trim() && attachments.length===0)" @click="sendChat">{{ loading ? '…' : 'Generate L3 ✨' }}</button>
                    </div>
                    <p v-if="error" class="mt-2 text-xs" style="color: var(--danger);">{{ error }}</p>
                    <p class="mt-1 text-[0.65rem]" style="color: var(--text-soft);">1 call AI hemat kredit → hasilkan 3 tab sekaligus</p>
                    <div v-if="history.length" class="mt-3 max-h-40 overflow-y-auto rounded-xl border p-2 space-y-1 text-xs" style="border-color: var(--border-soft); background: var(--bg-elevated);">
                        <div v-for="(m,i) in history.slice(-4)" :key="i" class="rounded px-2 py-1" :style="m.role==='user' ? 'background: var(--brand); color:white;' : 'background: var(--bg-card);'">{{ m.role }}: {{ m.text.slice(0,120) }}</div>
                    </div>
                </div>

                <div class="flex gap-1 rounded-xl p-1 w-fit" style="background: var(--bg-elevated);">
                    <button type="button" :class="['rounded-lg px-3 py-1.5 text-xs font-semibold', activeTab==='architecture' ? 'bg-white shadow text-black' : '']" style="color: activeTab==='architecture' ? 'var(--brand)' : 'var(--text-muted)'" @click="activeTab='architecture'">1. Arsitektur</button>
                    <button type="button" :class="['rounded-lg px-3 py-1.5 text-xs font-semibold', activeTab==='modules' ? 'bg-white shadow' : '']" @click="activeTab='modules'">2. Modul ({{ modules.length }})</button>
                    <button type="button" :class="['rounded-lg px-3 py-1.5 text-xs font-semibold', activeTab==='folder' ? 'bg-white shadow' : '']" @click="activeTab='folder'">3. Struktur Folder</button>
                </div>

                <!-- Tab Architecture -->
                <div v-if="activeTab==='architecture'" class="card">
                    <div v-if="!arch" class="py-8 text-center text-xs" style="color: var(--text-muted);">Belum ada arsitektur. Klik Generate L3 di atas.</div>
                    <template v-else>
                        <div class="rounded-xl p-4 border" style="border-color: color-mix(in srgb, var(--brand) 30%, var(--border-soft)); background: color-mix(in srgb, var(--brand) 8%, transparent);">
                            <p class="text-xs font-bold" style="color: var(--brand);">Rekomendasi AI — {{ arch.recommended }}</p>
                            <p class="mt-1 text-sm font-semibold">{{ (arch.stack||[]).join(' • ') }}</p>
                            <p class="mt-1 text-xs" style="color: var(--text-muted);">{{ arch.alasan }}</p>
                        </div>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            <button v-for="op in arch.opsi" :key="op.id" type="button" @click="selectArch(op.id)" :class="['text-left rounded-xl border p-3 transition', arch.selected===op.id || (!arch.selected && op.cocok) ? 'ring-2' : '']" :style="arch.selected===op.id || (!arch.selected && op.cocok) ? 'border-color: var(--brand); box-shadow: 0 0 0 3px var(--brand-glow);' : 'border-color: var(--border-soft);'">
                                <p class="text-xs font-bold">{{ op.label }} <span v-if="op.cocok" class="ml-1 rounded-full px-1.5 py-0.5 text-[0.6rem]" style="background: var(--brand); color:white;">cocok</span></p>
                                <p class="mt-1 text-[0.65rem]" style="color: var(--success);">+ {{ (op.pro||[]).join(' • ') }}</p>
                                <p class="text-[0.65rem]" style="color: var(--danger);">− {{ (op.kontra||[]).join(' • ') }}</p>
                            </button>
                        </div>
                        <button type="button" class="btn-secondary mt-3 text-xs" @click="saveFinal">Simpan Pilihan</button>
                    </template>
                </div>

                <!-- Tab Modules -->
                <div v-if="activeTab==='modules'" class="card">
                    <div class="flex flex-wrap gap-2">
                        <input v-model="newModuleName" placeholder="Nama modul" class="input-base flex-1 min-w-[140px] text-xs" />
                        <input v-model="newModuleDesc" placeholder="Deskripsi" class="input-base flex-1 min-w-[180px] text-xs" />
                        <select v-model="newModulePriority" class="input-base w-24 text-xs"><option>Must</option><option>Should</option><option>Could</option></select>
                        <button type="button" class="btn-primary text-xs" @click="addModule">+ Tambah</button>
                        <button type="button" class="btn-ghost border text-xs" style="border-color: var(--border-soft);" @click="showBulk=!showBulk">Bulk</button>
                    </div>
                    <div v-if="showBulk" class="mt-2 flex gap-2">
                        <textarea v-model="bulkModules" rows="3" placeholder="1 baris = 1 modul, format: Nama - deskripsi" class="input-base flex-1 text-xs"></textarea>
                        <button type="button" class="btn-secondary text-xs self-start" @click="addBulk">Tambah Bulk</button>
                    </div>

                    <div v-if="!modules.length" class="mt-4 py-8 text-center text-xs" style="color: var(--text-muted);">Belum ada modul. Generate L3 atau tambah manual.</div>
                    <div v-else class="mt-3 space-y-1.5">
                        <label v-for="m in modules" :key="m.id" class="flex items-start gap-2 rounded-lg border p-2.5 cursor-pointer hover:opacity-90" style="border-color: var(--border-soft); background: var(--bg-elevated);">
                            <input type="checkbox" :checked="m.checked" @change="toggleModule(m.id)" class="mt-0.5" />
                            <span class="flex-1">
                                <span class="text-xs font-semibold">{{ m.nama }} <span class="ml-1 rounded-full px-1.5 py-0.5 text-[0.6rem] font-bold" :style="m.prioritas==='Must' ? 'background: var(--brand); color:white;' : m.prioritas==='Should' ? 'background: var(--brand-secondary); color:white;' : 'background: var(--border-soft);'">{{ m.prioritas }}</span></span>
                                <span class="block text-[0.7rem]" style="color: var(--text-muted);">{{ m.deskripsi }} <span v-if="m.estimasi" class="ml-1">• {{ m.estimasi }}</span></span>
                            </span>
                            <button type="button" class="text-xs font-bold" style="color: var(--danger);" @click.stop="removeModule(m.id)">×</button>
                        </label>
                    </div>
                    <p class="mt-2 text-xs" style="color: var(--text-soft);">Terpilih {{ modules.filter(m=>m.checked).length }}/{{ modules.length }} modul</p>
                    <button type="button" class="btn-secondary mt-2 text-xs" @click="saveFinal">Simpan Modul</button>
                </div>

                <!-- Tab Folder -->
                <div v-if="activeTab==='folder'" class="card">
                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="btn-ghost border text-xs" style="border-color: var(--border-soft);" @click="addRootNode('folder')">+ Folder Root</button>
                        <button type="button" class="btn-ghost border text-xs" style="border-color: var(--border-soft);" @click="addRootNode('file')">+ File Root</button>
                        <button type="button" class="btn-secondary text-xs" @click="saveFinal">Simpan Struktur</button>
                        <button type="button" class="btn-ghost border text-xs" style="border-color: var(--border-soft);" @click="downloadZip">Download ZIP</button>
                    </div>
                    <p class="mt-2 text-xs" style="color: var(--text-soft);">Root: {{ folderRoot }} • Klik nama untuk rename • dinamis, bukan file fisik</p>

                    <div v-if="!folderTree.length" class="mt-4 py-8 text-center text-xs" style="color: var(--text-muted);">Belum ada struktur. Generate L3 dulu atau tambah manual.</div>
                    <div v-else class="mt-3 rounded-xl border p-3 space-y-1 font-mono text-xs" style="border-color: var(--border-soft); background: var(--bg-elevated);">
                        <FolderNode v-for="(node, idx) in folderTree" :key="idx" :node="node" :path="String(idx)" :depth="0" :editPath="folderEditPath" :editVal="folderEditVal" @rename="(p,n)=>startRename(p,n)" @commit="commitRename" @add="addNode" @remove="handleRemove" @updateEdit="(v)=>folderEditVal=v" />
                    </div>
                    <button type="button" class="btn-ghost mt-2 text-xs" @click="()=>{ if(confirm('Reset ke saran AI?')){ /* keep as is, user can re-generate */ } }">Copy sebagai Text</button>
                </div>

                <div class="flex gap-2">
                    <Link v-if="step>1" :href="route('member.wizard.show',[project.id, step-1])" class="btn-ghost border text-xs" style="border-color: var(--border-soft);">← Sebelumnya</Link>
                    <Link :href="route('member.wizard.show',[project.id, 4])" class="btn-primary text-xs">Lanjut ke Diagram →</Link>
                </div>
            </div>
        </div>
    </MemberLayout>
</template>
