<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const showingNavigationDropdown = ref(false);
defineEmits(['create-prd']);
</script>

<template>
    <div class="dark min-h-screen" style="background-color: var(--bg-main); color: var(--text-main);">
        <div class="flex min-h-screen">
            <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r lg:flex" style="border-color: var(--border-soft); background-color: var(--bg-sidebar);">
                <div class="flex h-20 items-center border-b px-6" style="border-color: var(--border-soft);">
                    <Link :href="route('dashboard')" class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl text-white shadow-glow" style="background: linear-gradient(135deg, var(--brand), var(--brand-secondary));">
                            <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </span>
                        <span class="font-bold">BuatPRD</span>
                    </Link>
                </div>

                <nav class="flex-1 space-y-7 px-4 py-7" aria-label="Navigasi utama">
                    <div>
                        <p class="mb-3 px-3 text-[0.68rem] font-bold uppercase tracking-[0.16em]" style="color: var(--text-soft);">Workspace</p>
                        <Link :href="route('dashboard')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-semibold transition" :style="route().current('dashboard') ? 'background-color: color-mix(in srgb, var(--brand) 12%, transparent); color: var(--brand);' : 'color: var(--text-muted);'">
                            <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h5.25c.621 0 1.125.504 1.125 1.125v5.25c0 .621-.504 1.125-1.125 1.125h-5.25A1.125 1.125 0 013 18.375v-5.25zM13.5 4.125C13.5 3.504 14.004 3 14.625 3h5.25C20.496 3 21 3.504 21 4.125v5.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-5.25zM13.5 13.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v5.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-5.25zM3 4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v5.25c0 .621-.504 1.125-1.125 1.125h-5.25A1.125 1.125 0 013 9.375v-5.25z" /></svg>
                            Dashboard
                        </Link>
                        <button type="button" class="mt-1 flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition" style="color: var(--text-muted);" @click="$emit('create-prd')">
                            <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            Buat PRD Baru
                        </button>
                    </div>
                    <div>
                        <p class="mb-3 px-3 text-[0.68rem] font-bold uppercase tracking-[0.16em]" style="color: var(--text-soft);">Pengaturan</p>
                        <Link :href="route('profile.edit')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition" style="color: var(--text-muted);">
                            <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                            Profil Anda
                        </Link>
                    </div>
                </nav>

                <div class="border-t p-4" style="border-color: var(--border-soft);">
                    <Dropdown align="left" width="48">
                        <template #trigger>
                            <button type="button" class="flex w-full items-center gap-3 rounded-lg p-2 text-left transition hover:bg-white/5">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold" style="background-color: color-mix(in srgb, var(--brand) 15%, transparent); color: var(--brand);">{{ ($page.props.auth.user.name || 'U').charAt(0).toUpperCase() }}</span>
                                <span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold">{{ $page.props.auth.user.name }}</span><span class="block truncate text-xs" style="color: var(--text-soft);">{{ $page.props.auth.user.email }}</span></span>
                                <svg class="h-4 w-4" style="color: var(--text-soft);" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                            </button>
                        </template>
                        <template #content>
                            <DropdownLink :href="route('profile.edit')">Profil</DropdownLink>
                            <DropdownLink :href="route('logout')" method="post" as="button">Keluar</DropdownLink>
                        </template>
                    </Dropdown>
                </div>
            </aside>

            <div class="flex min-w-0 flex-1 flex-col lg:pl-64">
                <header class="sticky top-0 z-30 flex h-20 items-center justify-between border-b px-5 sm:px-8" style="border-color: var(--border-soft); background-color: color-mix(in srgb, var(--bg-main) 88%, transparent); backdrop-filter: blur(16px);">
                    <div class="flex items-center gap-3">
                        <button type="button" class="btn-ghost px-2 lg:hidden" aria-label="Buka menu" @click="showingNavigationDropdown = !showingNavigationDropdown">
                            <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        </button>
                        <div class="hidden text-sm sm:block" style="color: var(--text-muted);">Workspace / <span style="color: var(--text-main);">{{ route().current('dashboard') ? 'Dashboard' : 'Pengaturan' }}</span></div>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" class="btn-ghost hidden px-3 sm:inline-flex" aria-label="Notifikasi">
                            <svg class="h-5 w-5" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.31 6.022c1.733.64 3.555 1.093 5.454 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                        </button>
                        <Link :href="route('profile.edit')" class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold lg:hidden" style="background-color: color-mix(in srgb, var(--brand) 15%, transparent); color: var(--brand);">{{ ($page.props.auth.user.name || 'U').charAt(0).toUpperCase() }}</Link>
                    </div>
                </header>

                <div v-if="showingNavigationDropdown" class="border-b p-4 lg:hidden" style="border-color: var(--border-soft); background-color: var(--bg-sidebar);">
                    <Link :href="route('dashboard')" class="block rounded-lg px-3 py-2.5 text-sm font-semibold" style="color: var(--brand);">Dashboard</Link>
                    <Link :href="route('profile.edit')" class="mt-1 block rounded-lg px-3 py-2.5 text-sm" style="color: var(--text-muted);">Profil Anda</Link>
                    <Link :href="route('logout')" method="post" as="button" class="mt-1 block w-full rounded-lg px-3 py-2.5 text-left text-sm" style="color: var(--text-muted);">Keluar</Link>
                </div>

                <header v-if="$slots.header" class="border-b px-5 py-6 sm:px-8" style="border-color: var(--border-soft);">
                    <div class="mx-auto max-w-[90rem]"><slot name="header" /></div>
                </header>
                <main class="flex-1"><slot /></main>
            </div>
        </div>
    </div>
</template>
