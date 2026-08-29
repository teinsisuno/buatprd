<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';

const user = computed(() => usePage().props.auth.user);
const search = ref('');
const filter = ref('Semua');
const showComposer = ref(false);
const draftTitle = ref('');
const notice = ref('');

const projects = ref([
    { title: 'BuatPRD 2.0', description: 'Platform generator PRD untuk tim produk modern.', status: 'Selesai', updatedAt: 'Hari ini, 09:42', sections: 12, tone: 'blue' },
    { title: 'Onboarding Mobile', description: 'Alur onboarding baru untuk pengguna trial.', status: 'Dalam proses', updatedAt: 'Kemarin, 16:18', sections: 8, tone: 'violet' },
    { title: 'Insight Dashboard', description: 'Dashboard analitik untuk membantu keputusan produk.', status: 'Draft', updatedAt: '12 Jun 2024', sections: 4, tone: 'amber' },
    { title: 'Workspace Collaboration', description: 'Kolaborasi real-time untuk product squad.', status: 'Selesai', updatedAt: '08 Jun 2024', sections: 15, tone: 'emerald' },
]);

const firstName = computed(() => user.value?.name?.split(' ')[0] || 'Product builder');

const filteredProjects = computed(() => {
    const query = search.value.trim().toLowerCase();

    return projects.value.filter((project) => {
        const matchesSearch = !query || `${project.title} ${project.description}`.toLowerCase().includes(query);
        const matchesFilter = filter.value === 'Semua' || project.status === filter.value;

        return matchesSearch && matchesFilter;
    });
});

const statusStyle = (status) => {
    if (status === 'Selesai') return 'color: var(--success); background-color: color-mix(in srgb, var(--success) 12%, transparent);';
    if (status === 'Dalam proses') return 'color: var(--brand); background-color: color-mix(in srgb, var(--brand) 12%, transparent);';
    return 'color: var(--warning); background-color: color-mix(in srgb, var(--warning) 12%, transparent);';
};

const toneStyle = (tone) => {
    const colors = {
        blue: 'color-mix(in srgb, var(--brand) 14%, transparent)',
        violet: 'color-mix(in srgb, var(--brand-secondary) 14%, transparent)',
        amber: 'color-mix(in srgb, var(--warning) 14%, transparent)',
        emerald: 'color-mix(in srgb, var(--success) 14%, transparent)',
    };

    return `background-color: ${colors[tone] || colors.blue};`;
};

const createDraft = () => {
    const title = draftTitle.value.trim();

    if (!title) return;

    projects.value.unshift({
        title,
        description: 'Draft baru yang siap dikembangkan bersama AI.',
        status: 'Draft',
        updatedAt: 'Baru saja',
        sections: 0,
        tone: 'blue',
    });
    draftTitle.value = '';
    showComposer.value = false;
    notice.value = `Draft “${title}” berhasil dibuat.`;
    window.setTimeout(() => { notice.value = ''; }, 3500);
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout @create-prd="showComposer = true">
        <template #header>
            <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-center">
                <div>
                    <p class="text-sm font-medium" style="color: var(--brand);">Workspace pribadi</p>
                    <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">Selamat datang, {{ firstName }}.</h1>
                    <p class="mt-2 text-sm" style="color: var(--text-muted);">Mari ubah ide berikutnya menjadi produk yang terarah.</p>
                </div>
                <button type="button" class="btn-primary min-h-11" @click="showComposer = true">
                    <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Buat PRD
                </button>
            </div>
        </template>

        <div class="container-base py-8 sm:py-10">
            <div v-if="notice" class="mb-6 flex items-center gap-3 rounded-xl border px-4 py-3 text-sm" style="border-color: color-mix(in srgb, var(--success) 30%, var(--border-soft)); color: var(--success); background-color: color-mix(in srgb, var(--success) 8%, transparent);" role="status">
                <svg class="h-5 w-5 shrink-0" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                {{ notice }}
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="card">
                    <div class="flex items-start justify-between"><span class="flex h-10 w-10 items-center justify-center rounded-xl" style="background-color: color-mix(in srgb, var(--brand) 12%, transparent); color: var(--brand);"><svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5A3.375 3.375 0 0010.125 2.25H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg></span><span class="text-xs font-semibold" style="color: var(--success);">+2 bulan ini</span></div>
                    <p class="mt-5 text-sm" style="color: var(--text-muted);">Total PRD</p><p class="mt-1 text-3xl font-bold">{{ projects.length }}</p>
                </div>
                <div class="card">
                    <div class="flex items-start justify-between"><span class="flex h-10 w-10 items-center justify-center rounded-xl" style="background-color: color-mix(in srgb, var(--success) 12%, transparent); color: var(--success);"><svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m6.303 3.701c.07-.686.07-1.38 0-2.066a11.95 11.95 0 00-3.44-6.256 11.95 11.95 0 00-6.256-3.44 11.95 11.95 0 00-2.066 0 11.95 11.95 0 00-6.256 3.44 11.95 11.95 0 000 8.322 11.95 11.95 0 006.256 3.44c.686.07 1.38.07 2.066 0a11.95 11.95 0 006.256-3.44 11.95 11.95 0 003.44-6.256z" /></svg></span><span class="text-xs" style="color: var(--text-soft);">60% dari total</span></div>
                    <p class="mt-5 text-sm" style="color: var(--text-muted);">Selesai</p><p class="mt-1 text-3xl font-bold">{{ projects.filter((project) => project.status === 'Selesai').length }}</p>
                </div>
                <div class="card">
                    <div class="flex items-start justify-between"><span class="flex h-10 w-10 items-center justify-center rounded-xl" style="background-color: color-mix(in srgb, var(--warning) 12%, transparent); color: var(--warning);"><svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></span><span class="text-xs" style="color: var(--text-soft);">Perlu perhatian</span></div>
                    <p class="mt-5 text-sm" style="color: var(--text-muted);">Dalam proses</p><p class="mt-1 text-3xl font-bold">{{ projects.filter((project) => project.status === 'Dalam proses').length }}</p>
                </div>
                <div class="card">
                    <div class="flex items-start justify-between"><span class="flex h-10 w-10 items-center justify-center rounded-xl" style="background-color: color-mix(in srgb, var(--brand-secondary) 12%, transparent); color: var(--brand-secondary);"><svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-2.846 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 002.455-2.456z" /></svg></span><span class="text-xs" style="color: var(--success);">Aktif</span></div>
                    <p class="mt-5 text-sm" style="color: var(--text-muted);">Section ditulis</p><p class="mt-1 text-3xl font-bold">39</p>
                </div>
            </div>

            <div v-if="showComposer" class="card mt-8 border-brand/50" style="border-color: color-mix(in srgb, var(--brand) 50%, var(--border-soft));">
                <div class="flex items-start justify-between gap-4"><div><p class="text-sm font-semibold" style="color: var(--brand);">Mulai dari ide</p><h2 class="mt-1 text-xl font-bold">Buat draft PRD baru</h2><p class="mt-1 text-sm" style="color: var(--text-muted);">Masukkan nama produk dulu. Detailnya bisa Anda kembangkan setelah draft dibuat.</p></div><button type="button" class="btn-ghost px-2" aria-label="Tutup form" @click="showComposer = false"><svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12" /></svg></button></div>
                <form class="mt-5 flex flex-col gap-3 sm:flex-row" @submit.prevent="createDraft"><input v-model="draftTitle" class="input-base min-h-11 flex-1" placeholder="Contoh: Aplikasi booking lapangan" autofocus /><button type="submit" class="btn-primary min-h-11">Buat draft</button></form>
            </div>

            <div id="recent-prds" class="mt-10 grid gap-8 xl:grid-cols-[minmax(0,1fr)_18rem]">
                <section>
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end"><div><p class="text-sm font-medium" style="color: var(--brand);">Koleksi Anda</p><h2 class="mt-1 text-xl font-bold">PRD terbaru</h2></div><div class="flex flex-col gap-3 sm:flex-row"><label class="relative"><span class="sr-only">Cari PRD</span><svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2" style="color: var(--text-soft);" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-4.35-4.35m2.1-5.4a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" /></svg><input v-model="search" class="input-base min-h-10 w-full pl-9 sm:w-52" placeholder="Cari PRD..." /></label><select v-model="filter" class="input-base min-h-10 sm:w-36"><option>Semua</option><option>Selesai</option><option>Dalam proses</option><option>Draft</option></select></div></div>
                    <div class="mt-5 space-y-3">
                        <article v-for="project in filteredProjects" :key="project.title" class="card-hover group flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                            <div class="flex min-w-0 items-center gap-4"><span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-sm font-bold" :style="toneStyle(project.tone)">{{ project.title.slice(0, 2).toUpperCase() }}</span><div class="min-w-0"><h3 class="truncate font-semibold">{{ project.title }}</h3><p class="mt-1 truncate text-sm" style="color: var(--text-muted);">{{ project.description }}</p><p class="mt-2 text-xs" style="color: var(--text-soft);">Diperbarui {{ project.updatedAt }} <span class="px-1">·</span> {{ project.sections }} section</p></div></div>
                            <div class="flex items-center justify-between gap-4 sm:justify-end"><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :style="statusStyle(project.status)">{{ project.status }}</span><button type="button" class="btn-ghost px-2 opacity-70 group-hover:opacity-100" :aria-label="`Buka ${project.title}`"><svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg></button></div>
                        </article>
                        <div v-if="filteredProjects.length === 0" class="card py-12 text-center"><p class="font-semibold">Tidak ada PRD yang cocok.</p><p class="mt-2 text-sm" style="color: var(--text-muted);">Coba ubah kata kunci atau filter Anda.</p></div>
                    </div>
                </section>

                <aside class="card h-fit">
                    <div class="flex items-center justify-between"><h2 class="font-semibold">Aktivitas terbaru</h2><span class="badge px-2.5 py-1 text-xs">Live</span></div>
                    <div class="mt-6 space-y-5">
                        <div class="flex gap-3"><span class="mt-1 h-2 w-2 shrink-0 rounded-full" style="background-color: var(--success); box-shadow: 0 0 0 4px color-mix(in srgb, var(--success) 12%, transparent);"></span><div><p class="text-sm leading-snug">PRD <strong>BuatPRD 2.0</strong> ditandai selesai.</p><p class="mt-1 text-xs" style="color: var(--text-soft);">2 jam lalu</p></div></div>
                        <div class="flex gap-3"><span class="mt-1 h-2 w-2 shrink-0 rounded-full" style="background-color: var(--brand); box-shadow: 0 0 0 4px color-mix(in srgb, var(--brand) 12%, transparent);"></span><div><p class="text-sm leading-snug">Anda memperbarui <strong>Onboarding Mobile</strong>.</p><p class="mt-1 text-xs" style="color: var(--text-soft);">Kemarin</p></div></div>
                        <div class="flex gap-3"><span class="mt-1 h-2 w-2 shrink-0 rounded-full" style="background-color: var(--warning); box-shadow: 0 0 0 4px color-mix(in srgb, var(--warning) 12%, transparent);"></span><div><p class="text-sm leading-snug">Draft <strong>Insight Dashboard</strong> menunggu review.</p><p class="mt-1 text-xs" style="color: var(--text-soft);">3 hari lalu</p></div></div>
                    </div>
                    <button type="button" class="btn-secondary mt-7 w-full">Lihat semua aktivitas</button>
                </aside>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
