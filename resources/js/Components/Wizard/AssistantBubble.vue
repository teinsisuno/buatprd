<script setup>
// Bubble chat wizard: assistant message dengan `structured` dirender rapi per step,
// JSON mentah disembunyikan di belakang toggle (sebelumnya tampil apa adanya).
import { ref, computed } from 'vue';

const props = defineProps({
    msg: { type: Object, required: true },
    step: { type: Number, default: 1 },
});

const showRaw = ref(false);
const s = computed(() => props.msg.structured || null);

const hasL1Extra = computed(() =>
    (props.msg.structured?.asumsi?.length) || (props.msg.structured?.pertanyaan_klarifikasi?.length)
);
</script>

<template>
    <div :class="['flex', msg.role === 'user' ? 'justify-end' : 'justify-start']">
        <div
            :class="['max-w-[86%] rounded-2xl px-4 py-3 text-sm leading-relaxed', msg.role === 'user' ? 'rounded-br-sm' : 'rounded-bl-sm border']"
            :style="msg.role === 'user' ? 'background: var(--brand); color:white;' : 'background: var(--bg-elevated); border-color: var(--border-soft);'"
        >
            <!-- USER -->
            <template v-if="msg.role === 'user'">
                <p class="whitespace-pre-wrap break-words">{{ msg.text }}</p>
                <div v-if="msg.attachments?.length" class="mt-2 flex flex-wrap gap-1.5">
                    <span v-for="a in msg.attachments" :key="a.path" class="rounded-full px-2 py-1 text-[0.65rem] font-medium" style="background: rgba(255,255,255,0.18);">{{ a.orig }}</span>
                </div>
            </template>

            <!-- ASSISTANT: structured render -->
            <template v-else-if="s">
                <!-- L1: Problem & Vision -->
                <template v-if="step === 1">
                    <p class="whitespace-pre-wrap break-words font-medium">{{ s.problem_statement }}</p>
                    <p v-if="s.value_proposition" class="mt-1.5 text-xs font-semibold" style="color: var(--brand);">UVP — {{ s.value_proposition }}</p>
                    <p v-if="s.target_personas?.length" class="mt-1.5 text-xs" style="color: var(--text-muted);">
                        👥 {{ s.target_personas.length }} persona: {{ s.target_personas.map(p => p.nama).join(', ') }}
                    </p>
                    <ul v-if="s.pain_points?.length" class="mt-1.5 list-disc pl-4 text-xs space-y-0.5" style="color: var(--text-muted);">
                        <li v-for="p in s.pain_points" :key="p">{{ p }}</li>
                    </ul>
                    <details v-if="hasL1Extra" class="mt-1.5 text-xs">
                        <summary class="cursor-pointer font-semibold" style="color: var(--text-muted);">Asumsi & Pertanyaan</summary>
                        <ul class="mt-1 list-disc pl-4 space-y-0.5" style="color: var(--text-muted);">
                            <li v-for="a in s.asumsi" :key="a">{{ a }}</li>
                            <li v-for="q in s.pertanyaan_klarifikasi" :key="q" style="color: var(--brand);">Q: {{ q }}</li>
                        </ul>
                    </details>
                </template>

                <!-- L2: Success Metrics -->
                <template v-else-if="step === 2">
                    <p class="text-xs font-bold" style="color: var(--brand);">KPI — {{ s.industri_terdeteksi || '-' }}</p>
                    <p class="mt-1 text-xs" style="color: var(--text-muted);">{{ s.kpis?.length || 0 }} KPI • ⭐ North Star: {{ s.north_star?.metric || '-' }}</p>
                    <ul class="mt-1.5 space-y-1 text-xs">
                        <li v-for="k in s.kpis" :key="k.nama" class="rounded-lg border px-2 py-1.5" style="border-color: var(--border-soft);">
                            <span class="font-semibold">{{ k.nama }}</span> → <span style="color: var(--brand);">{{ k.target }}</span>
                            <span class="block" style="color: var(--text-muted);">{{ k.definisi }} • {{ k.tipe }} • {{ k.cara_ukur }}</span>
                        </li>
                    </ul>
                </template>

                <!-- L3: Functional -->
                <template v-else-if="step === 3">
                    <p class="text-xs font-bold" style="color: var(--brand);">Arsitektur: {{ s.architecture?.recommended || '-' }}</p>
                    <p class="mt-1 text-xs" style="color: var(--text-muted);">{{ (s.architecture?.stack || []).join(' • ') }} • {{ s.modules?.length || 0 }} modul • {{ (s.folder_structure?.tree || []).length }} root folder</p>
                    <p class="mt-1.5 text-xs" style="color: var(--text-muted);">{{ s.architecture?.alasan }}</p>
                </template>

                <!-- Step lain (4-8): fallback ke struktur JSON yang rapi -->
                <template v-else>
                    <p class="whitespace-pre-wrap break-words font-medium">✅ Output terstruktur tersimpan — lihat detail di panel kanan.</p>
                </template>

                <div class="mt-2 flex items-center gap-2">
                    <button type="button" class="text-[0.65rem] underline" style="color: var(--text-soft);" @click="showRaw = !showRaw">
                        {{ showRaw ? 'Sembunyikan JSON' : 'Lihat JSON mentah' }}
                    </button>
                </div>
                <pre v-if="showRaw" class="mt-1.5 max-h-40 overflow-auto whitespace-pre-wrap text-[0.6rem]" style="color: var(--text-muted);">{{ msg.text }}</pre>
            </template>

            <!-- ASSISTANT: plain text (turn klarifikasi, bukan JSON) -->
            <template v-else>
                <p class="whitespace-pre-wrap break-words">{{ msg.text }}</p>
            </template>

            <p class="mt-1.5 text-[0.65rem] opacity-70">
                {{ msg.model }} • {{ new Date(msg.created_at).toLocaleTimeString('id-ID') }}
                <span v-if="msg.mock" class="ml-1 rounded bg-black/15 px-1">MOCK</span>
            </p>
        </div>
    </div>
</template>