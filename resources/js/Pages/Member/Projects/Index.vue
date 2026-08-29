<script setup>
import MemberLayout from '@/Layouts/MemberLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
const props = defineProps({ projects: Object, filters: Object });
const search = ref(props.filters?.search || '');
const status = ref(props.filters?.status || '');
let t;
watch(search, v => { clearTimeout(t); t=setTimeout(()=> apply(),400); });
const apply = () => router.get(route('member.projects.index'), { search: search.value, status: status.value }, { preserveState:true, replace:true });
</script>

<template>
    <Head title="Project" />
    <MemberLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Project — PRD Workspace</h1>
                    <p class="mt-1 text-sm" style="color: var(--text-muted);">Kelola semua PRD. Wizard 8 Langkah penuh hadir di Fase C.</p>
                </div>
                <Link :href="route('member.projects.create')" class="btn-primary">+ Buat PRD Baru</Link>
            </div>
        </template>

        <div class="container-base py-8">
            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="relative flex-1">
                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2" style="color: var(--text-soft);" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-4.35-4.35m2.1-5.4a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" /></svg>
                    <input v-model="search" class="input-base pl-9" placeholder="Cari project..." />
                </div>
                <select v-model="status" class="input-base sm:w-40" @change="apply">
                    <option value="">Semua Status</option>
                    <option value="draft">Draft</option>
                    <option value="in_progress">In Progress</option>
                    <option value="completed">Selesai</option>
                </select>
            </div>

            <div v-if="projects.data.length === 0" class="card mt-6 py-16 text-center">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl" style="background: var(--bg-elevated);">📄</div>
                <h3 class="mt-4 font-semibold">Belum ada project</h3>
                <p class="mt-1 text-sm" style="color: var(--text-muted);">Buat project pertama, lalu susun 8 langkah PRD dengan AI.</p>
                <Link :href="route('member.projects.create')" class="btn-primary mt-6">Buat Project Pertama</Link>
            </div>

            <div v-else class="mt-6 grid gap-3">
                <article v-for="p in projects.data" :key="p.id" class="card-hover flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="font-semibold">{{ p.title }}</h3>
                        <p class="mt-1 text-xs" style="color: var(--text-soft);">{{ p.status }} • {{ p.sections_count }}/8 langkah • {{ p.progress }}% • {{ new Date(p.updated_at).toLocaleDateString('id-ID') }}</p>
                    </div>
                    <span class="rounded-full px-3 py-1 text-xs font-semibold self-start sm:self-center" style="background: var(--bg-elevated);">{{ p.slug.slice(0,12) }}</span>
                </article>
            </div>

            <div v-if="projects.links" class="mt-6 flex flex-wrap gap-2">
                <Link v-for="link in projects.links" :key="link.label" :href="link.url || '#'" :class="['rounded-lg px-3 py-1.5 text-xs', link.active ? 'btn-primary' : 'btn-ghost border']" style="border-color: var(--border-soft);" v-html="link.label" />
            </div>
        </div>
    </MemberLayout>
</template>
