<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="dark min-h-screen" style="background-color: var(--bg-main); color: var(--text-main);">
        <div class="relative flex min-h-screen items-stretch overflow-hidden">
            <div class="hero-orb pointer-events-none" style="width: 32rem; height: 32rem; top: -12rem; left: -10rem; background: color-mix(in srgb, var(--brand) 16%, transparent);"></div>
            <div class="hero-orb pointer-events-none" style="width: 24rem; height: 24rem; right: -8rem; bottom: -8rem; background: color-mix(in srgb, var(--brand-secondary) 12%, transparent);"></div>

            <aside class="relative hidden w-1/2 flex-col justify-between border-r px-12 py-10 lg:flex xl:px-20" style="border-color: var(--border-soft); background: linear-gradient(145deg, color-mix(in srgb, var(--brand) 10%, var(--bg-main)), var(--bg-main) 58%);">
                <Link href="/" class="relative z-10 flex w-fit items-center gap-3" aria-label="Kembali ke halaman utama">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl text-white shadow-glow" style="background: linear-gradient(135deg, var(--brand), var(--brand-secondary));">
                        <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </span>
                    <span class="text-lg font-bold">BuatPRD</span>
                </Link>

                <div class="relative z-10 max-w-lg pb-10">
                    <div class="badge mb-6">Workspace produk yang lebih tajam</div>
                    <h1 class="text-4xl font-bold leading-tight xl:text-5xl">Dari ide mentah menjadi PRD yang siap dikerjakan.</h1>
                    <p class="mt-6 max-w-md text-base leading-relaxed" style="color: var(--text-muted);">Buat struktur produk yang jelas, selaraskan tim, dan mulai membangun lebih cepat dengan bantuan AI.</p>
                    <div class="mt-10 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-xl border p-4" style="border-color: var(--border-soft); background-color: color-mix(in srgb, var(--bg-card) 70%, transparent);">
                            <div class="text-2xl font-bold" style="color: var(--brand);">3x</div>
                            <div class="mt-1" style="color: var(--text-muted);">lebih cepat menyusun draft</div>
                        </div>
                        <div class="rounded-xl border p-4" style="border-color: var(--border-soft); background-color: color-mix(in srgb, var(--bg-card) 70%, transparent);">
                            <div class="text-2xl font-bold" style="color: var(--success);">100%</div>
                            <div class="mt-1" style="color: var(--text-muted);">fokus pada ide Anda</div>
                        </div>
                    </div>
                </div>
            </aside>

            <main class="relative flex w-full items-center justify-center px-6 py-10 sm:px-10 lg:w-1/2 xl:px-20">
                <div class="w-full max-w-md">
                    <div class="mb-8 flex items-center justify-between lg:hidden">
                        <Link href="/" class="flex items-center gap-2.5" aria-label="Kembali ke halaman utama">
                            <span class="flex h-9 w-9 items-center justify-center rounded-lg text-white" style="background: linear-gradient(135deg, var(--brand), var(--brand-secondary));">
                                <svg class="h-4 w-4" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                            </span>
                            <span class="font-bold">BuatPRD</span>
                        </Link>
                    </div>
                    <div class="card animate-slide-up p-6 sm:p-8">
                        <slot />
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>
