<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const showingMobile = ref(false);
const page = usePage();
const isActive = (pattern) => route().current(pattern);
const user = computed(() => page.props.auth?.user);
</script>

<template>
    <div class="dark min-h-screen" style="background-color: var(--bg-main); color: var(--text-main);">
        <div class="flex min-h-screen">
            <!-- Sidebar Desktop -->
            <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r lg:flex" style="border-color: var(--border-soft); background-color: var(--bg-sidebar);">
                <div class="flex h-20 items-center border-b px-6" style="border-color: var(--border-soft);">
                    <Link :href="route('admin.dashboard')" class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl text-white" style="background: linear-gradient(135deg, var(--brand), var(--brand-secondary));">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                        </span>
                        <span class="font-bold">BuatPRD</span>
                        <span class="rounded-full px-2 py-0.5 text-[0.6rem] font-bold uppercase tracking-widest" style="background: var(--brand); color: white;">Admin</span>
                    </Link>
                </div>

                <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-6" aria-label="Admin nav">
                    <div>
                        <p class="mb-3 px-3 text-[0.68rem] font-bold uppercase tracking-[0.16em]" style="color: var(--text-soft);">Utama</p>
                        <Link :href="route('admin.dashboard')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition" :style="isActive('admin.dashboard') ? 'background-color: color-mix(in srgb, var(--brand) 12%, transparent); color: var(--brand);' : 'color: var(--text-muted);'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h5.25c.621 0 1.125.504 1.125 1.125v5.25c0 .621-.504 1.125-1.125 1.125h-5.25A1.125 1.125 0 013 18.375v-5.25zM13.5 4.125C13.5 3.504 14.004 3 14.625 3h5.25C20.496 3 21 3.504 21 4.125v5.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-5.25zM13.5 13.125c0-.621.504-1.125 1.125-1.125h5.25c.621 0 1.125.504 1.125 1.125v5.25c0 .621-.504 1.125-1.125 1.125h-5.25a1.125 1.125 0 01-1.125-1.125v-5.25zM3 4.125C3 3.504 3.504 3 4.125 3h5.25c.621 0 1.125.504 1.125 1.125v5.25c0 .621-.504 1.125-1.125 1.125h-5.25A1.125 1.125 0 013 9.375v-5.25z" /></svg>
                            Dashboard
                        </Link>
                    </div>

                    <div>
                        <p class="mb-3 px-3 text-[0.68rem] font-bold uppercase tracking-[0.16em]" style="color: var(--text-soft);">Kelola Pengguna</p>
                        <Link :href="route('admin.users.index')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition" :style="isActive('admin.users.*') ? 'background-color: color-mix(in srgb, var(--brand) 12%, transparent); color: var(--brand);' : 'color: var(--text-muted);'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                            User Management
                        </Link>
                        <Link :href="route('admin.memberships.index')" class="mt-1 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition" :style="isActive('admin.memberships.*') ? 'background-color: color-mix(in srgb, var(--brand) 12%, transparent); color: var(--brand);' : 'color: var(--text-muted);'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /></svg>
                            Membership
                        </Link>
                        <Link :href="route('admin.paket.index')" class="mt-1 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition" :style="isActive('admin.paket.*') ? 'background-color: color-mix(in srgb, var(--brand) 12%, transparent); color: var(--brand);' : 'color: var(--text-muted);'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.403 16.556 18.25 12 18.25s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" /></svg>
                            Paket Management
                        </Link>
                    </div>

                    <div>
                        <p class="mb-3 px-3 text-[0.68rem] font-bold uppercase tracking-[0.16em]" style="color: var(--text-soft);">Keuangan</p>
                        <Link :href="route('admin.transactions.index')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition" :style="isActive('admin.transactions.*') ? 'background-color: color-mix(in srgb, var(--brand) 12%, transparent); color: var(--brand);' : 'color: var(--text-muted);'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 9.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75m0 9.75V6A2.25 2.25 0 016 3.75h10.5A2.25 2.25 0 0118.75 6v6.75A2.25 2.25 0 0116.5 15H3.75z" /></svg>
                            Transaksi
                        </Link>
                    </div>

                    <div>
                        <p class="mb-3 px-3 text-[0.68rem] font-bold uppercase tracking-[0.16em]" style="color: var(--text-soft);">Konten & Bantuan</p>
                        <Link :href="route('admin.templates.index')" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition" :style="isActive('admin.templates.*') ? 'background-color: color-mix(in srgb, var(--brand) 12%, transparent); color: var(--brand);' : 'color: var(--text-muted);'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5A3.375 3.375 0 0010.125 2.25H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                            Template PRD
                        </Link>
                        <Link :href="route('admin.tickets.index')" class="mt-1 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition" :style="isActive('admin.tickets.*') ? 'background-color: color-mix(in srgb, var(--brand) 12%, transparent); color: var(--brand);' : 'color: var(--text-muted);'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Tiket Support
                        </Link>
                        <Link :href="route('admin.settings.index')" class="mt-1 flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition" :style="isActive('admin.settings.*') ? 'background-color: color-mix(in srgb, var(--brand) 12%, transparent); color: var(--brand);' : 'color: var(--text-muted);'">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /></svg>
                            Setting
                        </Link>
                    </div>
                </nav>

                <div class="border-t p-4" style="border-color: var(--border-soft);">
                    <Dropdown align="left" width="48">
                        <template #trigger>
                            <button type="button" class="flex w-full items-center gap-3 rounded-lg p-2 text-left transition hover:bg-white/5">
                                <span class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-bold" style="background-color: color-mix(in srgb, var(--brand) 15%, transparent); color: var(--brand);">{{ (user?.name || 'A').charAt(0).toUpperCase() }}</span>
                                <span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold">{{ user?.name }}</span><span class="block truncate text-xs" style="color: var(--text-soft);">{{ user?.email }}</span></span>
                                <svg class="h-4 w-4" style="color: var(--text-soft);" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
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
                <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b px-5 sm:px-8" style="border-color: var(--border-soft); background-color: color-mix(in srgb, var(--bg-main) 88%, transparent); backdrop-filter: blur(16px);">
                    <div class="flex items-center gap-3">
                        <button type="button" class="btn-ghost px-2 lg:hidden" aria-label="Buka menu" @click="showingMobile = !showingMobile">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h16M4 18h16" /></svg>
                        </button>
                        <div class="hidden text-sm sm:block" style="color: var(--text-muted);">Admin / <span style="color: var(--text-main);">{{ $page.props.title || 'Dashboard' }}</span></div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="hidden sm:inline-flex rounded-full px-2.5 py-1 text-xs font-semibold" style="background: color-mix(in srgb, var(--brand) 14%, transparent); color: var(--brand);">{{ user?.roles?.[0] }}</span>
                        <Link :href="route('member.dashboard')" class="btn-ghost px-3 text-xs">Ke Member View →</Link>
                    </div>
                </header>

                <div v-if="showingMobile" class="border-b p-4 lg:hidden space-y-1" style="border-color: var(--border-soft); background-color: var(--bg-sidebar);">
                    <Link :href="route('admin.dashboard')" class="block rounded-lg px-3 py-2.5 text-sm font-semibold" :style="isActive('admin.dashboard') ? 'color: var(--brand);' : 'color: var(--text-muted);'">Dashboard</Link>
                    <Link :href="route('admin.users.index')" class="block rounded-lg px-3 py-2.5 text-sm" style="color: var(--text-muted);">User Management</Link>
                    <Link :href="route('admin.memberships.index')" class="block rounded-lg px-3 py-2.5 text-sm" style="color: var(--text-muted);">Membership</Link>
                    <Link :href="route('admin.transactions.index')" class="block rounded-lg px-3 py-2.5 text-sm" style="color: var(--text-muted);">Transaksi</Link>
                    <Link :href="route('logout')" method="post" as="button" class="block w-full rounded-lg px-3 py-2.5 text-left text-sm" style="color: var(--text-muted);">Keluar</Link>
                </div>

                <div v-if="$page.props.flash?.success || $page.props.flash?.error" class="mx-5 mt-5 sm:mx-8">
                    <div v-if="$page.props.flash.success" class="rounded-xl border px-4 py-3 text-sm" style="border-color: color-mix(in srgb, var(--success) 30%, var(--border-soft)); color: var(--success); background-color: color-mix(in srgb, var(--success) 8%, transparent);">{{ $page.props.flash.success }}</div>
                    <div v-if="$page.props.flash.error" class="rounded-xl border px-4 py-3 text-sm" style="border-color: color-mix(in srgb, var(--danger) 30%, var(--border-soft)); color: var(--danger); background-color: color-mix(in srgb, var(--danger) 8%, transparent);">{{ $page.props.flash.error }}</div>
                </div>

                <header v-if="$slots.header" class="border-b px-5 py-6 sm:px-8" style="border-color: var(--border-soft);"><div class="mx-auto max-w-[90rem]"><slot name="header" /></div></header>
                <main class="flex-1"><slot /></main>
            </div>
        </div>
    </div>
</template>
