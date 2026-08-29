<script setup>
import MemberLayout from '@/Layouts/MemberLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    stats: Object,
    recentProjects: Array,
    tiers: Array,
});

const showComposer = ref(false);
const draftTitle = ref('');

</script>

<template>
    <Head title="Member Dashboard" />
    <MemberLayout @create-prd="showComposer = true">
        <template #header>
            <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-medium" style="color: var(--brand);">Workspace pribadi • {{ $page.props.membership?.tier?.name || 'No Tier' }}</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">Halo, {{ $page.props.auth.user.name?.split(' ')[0] || 'Builder' }}.</h1>
                    <p class="mt-2 text-sm" style="color: var(--text-muted);">Lanjutkan PRD-mu — {{ stats.total_projects }} project, {{ stats.ai_remaining }} AI tersisa.</p>
                </div>
                <button type="button" class="btn-primary min-h-11" @click="showComposer = true">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Buat PRD Baru
                </button>
            </div>
        </template>

        <div class="container-base py-8 sm:py-10">
            <!-- Stats -->
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="card">
                    <p class="text-sm" style="color: var(--text-muted);">Total Project</p>
                    <p class="mt-1 text-3xl font-bold">{{ stats.total_projects }}</p>
                    <p class="mt-2 text-xs" style="color: var(--text-soft);">{{ stats.completed_projects }} selesai</p>
                </div>
                <div class="card">
                    <p class="text-sm" style="color: var(--text-muted);">Sisa AI Bulan Ini</p>
                    <p class="mt-1 text-3xl font-bold">{{ stats.ai_remaining }}</p>
                    <p class="mt-2 text-xs" style="color: var(--text-soft);">+ {{ stats.credit_balance }} kredit</p>
                </div>
                <div class="card" style="background: linear-gradient(135deg, color-mix(in srgb, var(--brand) 10%, transparent), transparent);">
                    <p class="text-sm" style="color: var(--text-muted);">Paket Aktif</p>
                    <p class="mt-1 text-xl font-bold">{{ $page.props.membership?.tier?.name || '-' }}</p>
                    <p class="mt-2 text-xs" style="color: var(--text-soft);">Exp: {{ $page.props.membership?.expired_at ? new Date($page.props.membership.expired_at).toLocaleDateString('id-ID') : '-' }}</p>
                </div>
                <div class="card">
                    <p class="text-sm" style="color: var(--text-muted);">Status</p>
                    <p class="mt-2">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold" :style="$page.props.membership?.is_expired ? 'background: color-mix(in srgb, var(--danger) 12%, transparent); color: var(--danger);' : 'background: color-mix(in srgb, var(--success) 12%, transparent); color: var(--success);'">{{ $page.props.membership?.is_expired ? 'Expired' : 'Aktif' }}</span>
                    </p>
                    <Link :href="route('member.billing.index')" class="mt-3 inline-block text-xs font-semibold" style="color: var(--brand);">Kelola Billing →</Link>
                </div>
            </div>

            <!-- Composer -->
            <div v-if="showComposer" class="card mt-8" style="border-color: color-mix(in srgb, var(--brand) 30%, var(--border-soft));">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-sm font-semibold" style="color: var(--brand);">Mulai dari ide</p>
                        <h2 class="mt-1 text-lg font-bold">Buat draft PRD baru</h2>
                        <p class="mt-1 text-sm" style="color: var(--text-muted);">Judul dulu, 8 langkah wizard menyusul di Fase C.</p>
                    </div>
                    <button type="button" class="btn-ghost px-2" @click="showComposer = false">✕</button>
                </div>
                <div class="mt-4 flex gap-3">
                    <input v-model="draftTitle" class="input-base flex-1" placeholder="Contoh: Aplikasi booking lapangan" />
                    <Link :href="route('member.projects.index')" class="btn-primary">Buat</Link>
                </div>
            </div>

            <!-- Recent projects -->
            <div class="mt-10 grid gap-8 xl:grid-cols-[minmax(0,1fr)_18rem]">
                <section>
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold">Project Terbaru</h2>
                        <Link :href="route('member.projects.index')" class="text-xs font-semibold" style="color: var(--brand);">Lihat semua →</Link>
                    </div>
                    <div v-if="recentProjects.length === 0" class="card mt-4 py-12 text-center">
                        <p class="font-semibold">Belum ada project.</p>
                        <p class="mt-1 text-sm" style="color: var(--text-muted);">Klik Buat PRD Baru untuk mulai Wizard 8 Langkah.</p>
                    </div>
                    <div v-else class="mt-4 space-y-3">
                        <article v-for="p in recentProjects" :key="p.id" class="card-hover flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold">{{ p.title }}</h3>
                                <p class="mt-1 text-xs" style="color: var(--text-soft);">{{ p.status }} • {{ p.sections_count || 0 }}/8 langkah • {{ new Date(p.updated_at).toLocaleDateString('id-ID') }}</p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold" style="background: var(--bg-elevated);">{{ p.progress }}%</span>
                        </article>
                    </div>
                </section>

                <aside class="card h-fit">
                    <h3 class="font-semibold">Paket Tersedia</h3>
                    <div class="mt-4 space-y-3">
                        <div v-for="t in tiers" :key="t.id" class="rounded-xl border p-3" style="border-color: var(--border-soft);">
                            <p class="text-sm font-semibold">{{ t.name }} — Rp {{ Number(t.price).toLocaleString('id-ID') }}</p>
                            <p class="mt-1 text-xs" style="color: var(--text-soft);">{{ t.features?.slice(0,2).join(' • ') }}</p>
                        </div>
                    </div>
                    <Link :href="route('member.billing.index')" class="btn-primary mt-4 w-full justify-center">Upgrade Paket</Link>
                </aside>
            </div>
        </div>
    </MemberLayout>
</template>
