<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="BuatPRD - AI-Powered PRD Generator" />

    <div class="dark min-h-screen" style="background-color: var(--bg-main); color: var(--text-main);">
        <!-- Navbar -->
        <nav class="fixed inset-x-0 top-0 z-50 border-b backdrop-blur-xl" style="border-color: var(--border-soft); background-color: color-mix(in srgb, var(--bg-main) 80%, transparent);">
            <div class="container-base flex items-center justify-between py-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl" style="background: linear-gradient(135deg, var(--brand), var(--brand-secondary));">
                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span class="text-lg font-bold" style="color: var(--text-main);">BuatPRD</span>
                </div>

                <div v-if="canLogin" class="flex items-center gap-3">
                    <Link
                        v-if="$page.props.auth?.user"
                        :href="route('dashboard')"
                        class="btn-primary"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="route('login')"
                            class="nav-link"
                        >
                            Login
                        </Link>
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="btn-primary"
                        >
                            Daftar Sekarang
                        </Link>
                    </template>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="relative overflow-hidden px-6 pb-24 pt-32 lg:px-12 lg:pb-32 lg:pt-40" style="background: radial-gradient(circle at 78% 18%, color-mix(in srgb, var(--brand) 15%, transparent), transparent 26rem), radial-gradient(circle at 18% 75%, color-mix(in srgb, var(--brand-secondary) 7%, transparent), transparent 22rem), var(--bg-main);">
            <div class="absolute inset-0 bg-grid-pattern opacity-30"></div>
            <div class="hero-orb animate-float pointer-events-none" style="width: 28rem; height: 28rem; top: -16rem; right: 18%; background: var(--brand);"></div>
            <div class="hero-orb animate-float-delayed pointer-events-none" style="width: 20rem; height: 20rem; bottom: -10rem; left: 4%; background: var(--brand-secondary);"></div>

            <div class="container-base relative z-10">
                <div class="grid items-center gap-14 lg:grid-cols-[0.9fr_1.1fr] lg:gap-20">
                    <div class="animate-fade-in text-center lg:text-left">
                        <div class="badge mb-7 w-fit lg:mx-0 mx-auto" style="background-color: color-mix(in srgb, var(--brand) 12%, transparent);">
                            <span class="relative flex h-2 w-2"><span class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-75" style="background-color: var(--brand-secondary);"></span><span class="relative inline-flex h-2 w-2 rounded-full" style="background-color: var(--brand-secondary);"></span></span>
                            AI workspace untuk product team
                        </div>
                        <h1 class="text-balance text-4xl font-bold leading-[1.08] tracking-tight sm:text-5xl lg:text-6xl xl:text-7xl" style="color: var(--text-main);">
                            Beri bentuk pada ide yang <span class="gradient-text">layak dibangun.</span>
                        </h1>
                        <p class="mx-auto mt-7 max-w-xl text-base leading-7 sm:text-lg lg:mx-0" style="color: var(--text-muted);">
                            Buat PRD profesional dengan struktur yang jelas, konteks yang lengkap, dan arah yang bisa langsung dipahami tim Anda.
                        </p>
                        <div class="mt-9 flex flex-col gap-3 sm:flex-row sm:justify-center lg:justify-start">
                            <Link v-if="$page.props.auth?.user" :href="route('dashboard')" class="btn-primary min-h-12 px-7 text-base">
                                Buka workspace
                                <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                            </Link>
                            <Link v-else :href="canRegister ? route('register') : route('login')" class="btn-primary min-h-12 px-7 text-base">
                                Mulai secara gratis
                                <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
                            </Link>
                            <a href="#fitur" class="btn-secondary min-h-12 px-7 text-base">Lihat cara kerja</a>
                        </div>
                        <div class="mt-9 flex flex-wrap justify-center gap-x-6 gap-y-3 text-sm lg:justify-start" style="color: var(--text-muted);">
                            <span class="flex items-center gap-2"><span class="flex h-5 w-5 items-center justify-center rounded-full" style="background: color-mix(in srgb, var(--success) 14%, transparent); color: var(--success);">✓</span>Gratis untuk mulai</span>
                            <span class="flex items-center gap-2"><span class="flex h-5 w-5 items-center justify-center rounded-full" style="background: color-mix(in srgb, var(--success) 14%, transparent); color: var(--success);">✓</span>Tanpa kartu kredit</span>
                        </div>
                    </div>

                    <div class="relative mx-auto w-full max-w-2xl animate-slide-up lg:ml-auto">
                        <div class="absolute -inset-8 rounded-full opacity-40 blur-3xl" style="background: conic-gradient(from 210deg, transparent, color-mix(in srgb, var(--brand) 40%, transparent), color-mix(in srgb, var(--brand-secondary) 28%, transparent), transparent 70%);"></div>
                        <div class="relative overflow-hidden rounded-3xl border p-3 shadow-2xl" style="border-color: color-mix(in srgb, var(--brand) 35%, var(--border-soft)); background: color-mix(in srgb, var(--bg-card) 92%, transparent); box-shadow: 0 30px 90px -35px var(--brand-glow);">
                            <div class="flex items-center justify-between border-b px-3 pb-3" style="border-color: var(--border-soft);">
                                <div class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" style="background: #ff746d;"></span><span class="h-2.5 w-2.5 rounded-full" style="background: var(--brand-secondary);"></span><span class="h-2.5 w-2.5 rounded-full" style="background: var(--success);"></span></div>
                                <span class="rounded-full px-2.5 py-1 text-[0.65rem] font-semibold" style="background: color-mix(in srgb, var(--success) 12%, transparent); color: var(--success);">AI synthesis ready</span>
                            </div>
                            <div class="grid min-h-[22rem] grid-cols-[7.5rem_1fr] gap-3 pt-3 sm:grid-cols-[9rem_1fr]">
                                <div class="hidden space-y-2 rounded-2xl p-3 sm:block" style="background: color-mix(in srgb, var(--bg-elevated) 64%, transparent);">
                                    <div class="mb-5 h-2 w-16 rounded-full" style="background: var(--brand);"></div>
                                    <div class="rounded-lg px-2 py-2 text-[0.65rem] font-semibold" style="background: color-mix(in srgb, var(--brand) 15%, transparent); color: var(--brand);">Overview</div>
                                    <div class="px-2 py-2 text-[0.65rem]" style="color: var(--text-soft);">User stories</div>
                                    <div class="px-2 py-2 text-[0.65rem]" style="color: var(--text-soft);">Requirements</div>
                                    <div class="px-2 py-2 text-[0.65rem]" style="color: var(--text-soft);">Success metrics</div>
                                </div>
                                <div class="rounded-2xl border p-5 sm:p-6" style="border-color: var(--border-soft); background: linear-gradient(145deg, color-mix(in srgb, var(--bg-elevated) 72%, transparent), var(--bg-card));">
                                    <div class="flex items-start justify-between gap-4"><div><p class="text-[0.65rem] font-semibold uppercase tracking-[0.18em]" style="color: var(--brand);">Product brief / 01</p><h2 class="mt-3 text-xl font-bold sm:text-2xl">BuatPRD 2.0</h2></div><span class="flex h-9 w-9 items-center justify-center rounded-xl" style="background: color-mix(in srgb, var(--brand-secondary) 14%, transparent); color: var(--brand-secondary);"><svg class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m9-9H3" /></svg></span></div>
                                    <p class="mt-3 max-w-sm text-xs leading-5 sm:text-sm" style="color: var(--text-muted);">Platform untuk menyusun dokumen produk dari ide awal hingga siap dieksekusi.</p>
                                    <div class="mt-6 flex flex-wrap gap-2"><span class="rounded-full px-2.5 py-1 text-[0.65rem] font-semibold" style="background: color-mix(in srgb, var(--brand) 14%, transparent); color: var(--brand);">Problem space</span><span class="rounded-full px-2.5 py-1 text-[0.65rem] font-semibold" style="background: color-mix(in srgb, var(--brand-secondary) 14%, transparent); color: var(--brand-secondary);">B2B SaaS</span></div>
                                    <div class="mt-8 space-y-4"><div class="flex items-center justify-between text-xs"><span style="color: var(--text-muted);">Completeness</span><strong>82%</strong></div><div class="h-2 overflow-hidden rounded-full" style="background: var(--border-soft);"><div class="h-full w-[82%] rounded-full" style="background: linear-gradient(90deg, var(--brand), var(--brand-secondary));"></div></div><div class="grid grid-cols-3 gap-2 pt-2"><div class="rounded-xl p-3" style="background: color-mix(in srgb, var(--bg-main) 55%, transparent);"><p class="text-[0.6rem]" style="color: var(--text-soft);">Stories</p><p class="mt-1 text-sm font-bold">18</p></div><div class="rounded-xl p-3" style="background: color-mix(in srgb, var(--bg-main) 55%, transparent);"><p class="text-[0.6rem]" style="color: var(--text-soft);">Criteria</p><p class="mt-1 text-sm font-bold">24</p></div><div class="rounded-xl p-3" style="background: color-mix(in srgb, var(--bg-main) 55%, transparent);"><p class="text-[0.6rem]" style="color: var(--text-soft);">Signals</p><p class="mt-1 text-sm font-bold">09</p></div></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section id="fitur" class="relative overflow-hidden px-6 py-24 lg:px-12" style="background: linear-gradient(180deg, var(--bg-main), color-mix(in srgb, var(--bg-elevated) 34%, var(--bg-main)) 52%, var(--bg-main));">
            <!-- Top divider gradient -->
            <div class="absolute inset-x-0 top-0 h-px" style="background: linear-gradient(90deg, transparent, var(--border-soft), transparent);"></div>

            <div class="container-base">
                <div class="text-center">
                    <div class="badge mx-auto mb-6 w-fit">Fitur Unggulan</div>
                    <h2 class="section-title">Kenapa BuatPRD?</h2>
                    <p class="section-subtitle">
                        Hemat waktu dan tingkatkan kualitas dokumen produk Anda dengan AI.
                    </p>
                </div>

                <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Feature 1 -->
                    <div class="card-hover landing-card group">
                        <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-xl transition-all duration-300 group-hover:scale-110" style="background: linear-gradient(135deg, color-mix(in srgb, var(--brand) 15%, transparent), color-mix(in srgb, var(--brand) 5%, transparent));">
                            <svg class="h-7 w-7" style="color: var(--brand);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold" style="color: var(--text-main);">Generasi Cepat</h3>
                        <p class="mt-2.5 text-sm leading-relaxed" style="color: var(--text-muted);">PRD lengkap tersusun dalam hitungan menit, bukan berjam-jam.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="card-hover landing-card group">
                        <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-xl transition-all duration-300 group-hover:scale-110" style="background: linear-gradient(135deg, color-mix(in srgb, var(--brand) 15%, transparent), color-mix(in srgb, var(--brand-secondary) 10%, transparent));">
                            <svg class="h-7 w-7" style="color: var(--brand);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 00-2.455 2.456z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold" style="color: var(--text-main);">AI-Powered</h3>
                        <p class="mt-2.5 text-sm leading-relaxed" style="color: var(--text-muted);">Menggunakan AI untuk memahami kebutuhan produk Anda dan menyusun struktur yang tepat.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="card-hover landing-card group">
                        <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-xl transition-all duration-300 group-hover:scale-110" style="background: linear-gradient(135deg, color-mix(in srgb, var(--brand) 15%, transparent), color-mix(in srgb, var(--success) 10%, transparent));">
                            <svg class="h-7 w-7" style="color: var(--brand);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold" style="color: var(--text-main);">Struktur Lengkap</h3>
                        <p class="mt-2.5 text-sm leading-relaxed" style="color: var(--text-muted);">Mencakup user stories, acceptance criteria, wireframe specs, dan section lainnya.</p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="card-hover landing-card group">
                        <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-xl transition-all duration-300 group-hover:scale-110" style="background: linear-gradient(135deg, color-mix(in srgb, var(--success) 15%, transparent), color-mix(in srgb, var(--success) 5%, transparent));">
                            <svg class="h-7 w-7" style="color: var(--success);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold" style="color: var(--text-main);">Aman & Privat</h3>
                        <p class="mt-2.5 text-sm leading-relaxed" style="color: var(--text-muted);">Data produk Anda aman dan tidak dibagikan ke pihak ketiga.</p>
                    </div>

                    <!-- Feature 5 -->
                    <div class="card-hover landing-card group">
                        <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-xl transition-all duration-300 group-hover:scale-110" style="background: linear-gradient(135deg, color-mix(in srgb, var(--warning) 15%, transparent), color-mix(in srgb, var(--warning) 5%, transparent));">
                            <svg class="h-7 w-7" style="color: var(--warning);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold" style="color: var(--text-main);">Export Multi-Format</h3>
                        <p class="mt-2.5 text-sm leading-relaxed" style="color: var(--text-muted);">Download PRD dalam format Markdown, PDF, atau langsung ke Notion.</p>
                    </div>

                    <!-- Feature 6 -->
                    <div class="card-hover landing-card group">
                        <div class="mb-5 flex h-14 w-14 items-center justify-center rounded-xl transition-all duration-300 group-hover:scale-110" style="background: linear-gradient(135deg, color-mix(in srgb, var(--brand-secondary) 15%, transparent), color-mix(in srgb, var(--brand-secondary) 5%, transparent));">
                            <svg class="h-7 w-7" style="color: var(--brand-secondary);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold" style="color: var(--text-main);">Kustomisasi Fleksibel</h3>
                        <p class="mt-2.5 text-sm leading-relaxed" style="color: var(--text-muted);">Sesuaikan output PRD sesuai kebutuhan dan preferensi tim Anda.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section class="relative overflow-hidden px-6 py-24 lg:px-12" style="background: linear-gradient(180deg, var(--bg-main), color-mix(in srgb, var(--brand) 4%, var(--bg-main)), var(--bg-main));">
            <div class="absolute inset-x-0 top-0 h-px" style="background: linear-gradient(90deg, transparent, var(--border-soft), transparent);"></div>

            <div class="container-base">
                <div class="text-center">
                    <div class="badge mx-auto mb-6 w-fit">Cara Kerja</div>
                    <h2 class="section-title">Tiga Langkah Sederhana</h2>
                    <p class="section-subtitle">
                        Mulai dari ide hingga dokumen PRD profesional.
                    </p>
                </div>

                <div class="relative mt-20">
                    <!-- Connection Line (Desktop) -->
                    <div class="absolute left-1/2 top-0 hidden h-full w-px -translate-x-1/2 lg:block" style="background: linear-gradient(180deg, var(--brand), color-mix(in srgb, var(--brand) 0%, transparent));"></div>

                    <div class="space-y-16 lg:space-y-24">
                        <!-- Step 1 -->
                        <div class="relative flex flex-col items-center lg:flex-row lg:items-center lg:gap-16">
                            <div class="flex-1 text-center lg:text-right">
                                <div class="mb-3 text-xs font-bold uppercase tracking-widest" style="color: var(--brand);">Langkah 1</div>
                                <h3 class="text-2xl font-bold" style="color: var(--text-main);">Jelaskan Produk Anda</h3>
                                <p class="mt-3 text-base leading-relaxed" style="color: var(--text-muted);">Ceritakan tentang produk Anda, tujuan, dan target pengguna secara singkat. AI kami siap memahami.</p>
                            </div>
                            <div class="relative z-10 flex h-20 w-20 flex-shrink-0 items-center justify-center rounded-2xl shadow-glow" style="background: var(--bg-card); border: 2px solid var(--brand);">
                                <span class="text-3xl font-bold" style="color: var(--brand);">1</span>
                            </div>
                            <div class="hidden flex-1 lg:block"></div>
                        </div>

                        <!-- Step 2 -->
                        <div class="relative flex flex-col items-center lg:flex-row lg:items-center lg:gap-16">
                            <div class="hidden flex-1 lg:block"></div>
                            <div class="relative z-10 flex h-20 w-20 flex-shrink-0 items-center justify-center rounded-2xl shadow-glow" style="background: var(--bg-card); border: 2px solid var(--brand);">
                                <span class="text-3xl font-bold" style="color: var(--brand);">2</span>
                            </div>
                            <div class="flex-1 text-center lg:text-left">
                                <div class="mb-3 text-xs font-bold uppercase tracking-widest" style="color: var(--brand);">Langkah 2</div>
                                <h3 class="text-2xl font-bold" style="color: var(--text-main);">AI Menyusun PRD</h3>
                                <p class="mt-3 text-base leading-relaxed" style="color: var(--text-muted);">AI kami akan menganalisis input Anda dan menyusun struktur PRD lengkap dengan semua section yang dibutuhkan.</p>
                            </div>
                        </div>

                        <!-- Step 3 -->
                        <div class="relative flex flex-col items-center lg:flex-row lg:items-center lg:gap-16">
                            <div class="flex-1 text-center lg:text-right">
                                <div class="mb-3 text-xs font-bold uppercase tracking-widest" style="color: var(--brand);">Langkah 3</div>
                                <h3 class="text-2xl font-bold" style="color: var(--text-main);">Download & Bagikan</h3>
                                <p class="mt-3 text-base leading-relaxed" style="color: var(--text-muted);">Review, edit jika perlu, lalu download atau bagikan ke tim Anda dalam format yang Anda inginkan.</p>
                            </div>
                            <div class="relative z-10 flex h-20 w-20 flex-shrink-0 items-center justify-center rounded-2xl shadow-glow" style="background: var(--bg-card); border: 2px solid var(--brand);">
                                <span class="text-3xl font-bold" style="color: var(--brand);">3</span>
                            </div>
                            <div class="hidden flex-1 lg:block"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="relative px-6 py-24 lg:px-12" style="background: color-mix(in srgb, var(--bg-elevated) 30%, var(--bg-main));">
            <div class="absolute inset-x-0 top-0 h-px" style="background: linear-gradient(90deg, transparent, var(--border-soft), transparent);"></div>

            <div class="container-base">
                <div class="grid grid-cols-2 gap-8 rounded-3xl border px-6 py-10 sm:px-10 lg:grid-cols-4" style="border-color: var(--border-soft); background: linear-gradient(135deg, color-mix(in srgb, var(--bg-card) 92%, transparent), color-mix(in srgb, var(--brand) 5%, var(--bg-card))); box-shadow: 0 24px 70px -50px rgba(0, 0, 0, 0.95);">
                    <div class="text-center">
                        <div class="text-4xl font-bold lg:text-5xl gradient-text">100+</div>
                        <div class="mt-2 text-sm" style="color: var(--text-muted);">PRD Dibuat</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold lg:text-5xl gradient-text">50+</div>
                        <div class="mt-2 text-sm" style="color: var(--text-muted);">Tim Aktif</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold lg:text-5xl gradient-text">99%</div>
                        <div class="mt-2 text-sm" style="color: var(--text-muted);">Kepuasan</div>
                    </div>
                    <div class="text-center">
                        <div class="text-4xl font-bold lg:text-5xl gradient-text">3x</div>
                        <div class="mt-2 text-sm" style="color: var(--text-muted);">Lebih Cepat</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="relative overflow-hidden px-6 py-24 lg:px-12 lg:py-32" style="background: var(--bg-main);">
            <div class="absolute inset-x-0 top-0 h-px" style="background: linear-gradient(90deg, transparent, color-mix(in srgb, var(--brand) 45%, var(--border-soft)), transparent);"></div>

            <div class="relative mx-auto max-w-5xl overflow-hidden rounded-3xl border px-6 py-20 text-center sm:px-12" style="border-color: color-mix(in srgb, var(--brand) 34%, var(--border-soft)); background: radial-gradient(circle at 50% -20%, color-mix(in srgb, var(--brand) 22%, transparent), transparent 28rem), linear-gradient(135deg, color-mix(in srgb, var(--bg-card) 98%, transparent), color-mix(in srgb, var(--brand) 7%, var(--bg-card))); box-shadow: 0 30px 90px -55px var(--brand-glow);">
                <div class="absolute inset-0 bg-grid-pattern opacity-20"></div>
                <div class="relative mx-auto max-w-3xl">
                <h2 class="text-3xl font-bold sm:text-4xl lg:text-5xl" style="color: var(--text-main);">Siap Mulai?</h2>
                <p class="mx-auto mt-6 max-w-lg text-lg" style="color: var(--text-muted);">
                    Daftar sekarang dan mulai buat PRD pertama Anda secara gratis. Tanpa kartu kredit.
                </p>
                <Link
                    :href="canRegister ? route('register') : route('login')"
                    class="btn-primary mt-10 inline-flex items-center gap-2 px-10 py-4 text-base shadow-glow-lg hover:shadow-glow-xl"
                >
                    {{ canRegister ? 'Daftar Gratis Sekarang' : 'Login' }}
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="relative border-t" style="border-color: var(--border-soft); background: radial-gradient(circle at 12% 0%, color-mix(in srgb, var(--brand) 9%, transparent), transparent 24rem), var(--bg-sidebar);">
            <div class="container-base py-16">
                <div class="grid gap-12 md:grid-cols-4">
                    <!-- Brand -->
                    <div class="md:col-span-1">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl" style="background: linear-gradient(135deg, var(--brand), var(--brand-secondary));">
                                <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="text-lg font-bold" style="color: var(--text-main);">BuatPRD</span>
                        </div>
                        <p class="mt-4 text-sm leading-relaxed" style="color: var(--text-soft);">
                            AI-Powered PRD Generator untuk tim produk profesional.
                        </p>
                    </div>

                    <!-- Links -->
                    <div>
                        <h4 class="text-sm font-semibold uppercase tracking-wider" style="color: var(--text-main);">Produk</h4>
                        <ul class="mt-4 space-y-3">
                            <li><a href="#" class="footer-link">Fitur</a></li>
                            <li><a href="#" class="footer-link">Harga</a></li>
                            <li><a href="#" class="footer-link">Integrasi</a></li>
                            <li><a href="#" class="footer-link">API</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold uppercase tracking-wider" style="color: var(--text-main);">Perusahaan</h4>
                        <ul class="mt-4 space-y-3">
                            <li><a href="#" class="footer-link">Tentang Kami</a></li>
                            <li><a href="#" class="footer-link">Blog</a></li>
                            <li><a href="#" class="footer-link">Karir</a></li>
                            <li><a href="#" class="footer-link">Kontak</a></li>
                        </ul>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold uppercase tracking-wider" style="color: var(--text-main);">Legal</h4>
                        <ul class="mt-4 space-y-3">
                            <li><a href="#" class="footer-link">Kebijakan Privasi</a></li>
                            <li><a href="#" class="footer-link">Syarat & Ketentuan</a></li>
                            <li><a href="#" class="footer-link">Kebijakan Cookie</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Bottom -->
                <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t pt-8 md:flex-row" style="border-color: var(--border-soft);">
                    <div class="text-sm" style="color: var(--text-soft);">
                        &copy; {{ new Date().getFullYear() }} BuatPRD. All rights reserved.
                    </div>
                    <div class="flex items-center gap-6">
                        <a href="#" class="transition-colors" style="color: var(--text-soft);" aria-label="Twitter">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        </a>
                        <a href="#" class="transition-colors" style="color: var(--text-soft);" aria-label="GitHub">
                            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
