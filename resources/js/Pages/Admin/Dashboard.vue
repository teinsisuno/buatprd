<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: Object,
    tierDistribution: Array,
    recentTransactions: Array,
    recentUsers: Array,
});

const formatRupiah = (n) => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(n || 0);
</script>

<template>
    <Head title="Admin Dashboard" />
    <AdminLayout>
        <template #header>
            <div>
                <p class="text-sm font-medium" style="color: var(--brand);">Admin Panel • Production</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">Dashboard Admin</h1>
                <p class="mt-2 text-sm" style="color: var(--text-muted);">Kelola pengguna, paket, dan transaksi transfer manual.</p>
            </div>
        </template>

        <div class="container-base py-8 sm:py-10">
            <!-- Stats -->
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="card">
                    <p class="text-sm" style="color: var(--text-muted);">Total User</p>
                    <p class="mt-1 text-3xl font-bold">{{ stats.total_users }}</p>
                    <p class="mt-2 text-xs" style="color: var(--text-soft);">{{ stats.total_members }} member</p>
                </div>
                <div class="card">
                    <p class="text-sm" style="color: var(--text-muted);">Total Project</p>
                    <p class="mt-1 text-3xl font-bold">{{ stats.total_projects }}</p>
                    <p class="mt-2 text-xs" style="color: var(--success);">Live</p>
                </div>
                <div class="card border-warning/30" style="border-color: color-mix(in srgb, var(--warning) 30%, var(--border-soft));">
                    <p class="text-sm" style="color: var(--text-muted);">Pending Transaksi</p>
                    <p class="mt-1 text-3xl font-bold" style="color: var(--warning);">{{ stats.pending_transactions }}</p>
                    <Link :href="route('admin.transactions.index')" class="mt-2 inline-block text-xs font-semibold" style="color: var(--brand);">Lihat →</Link>
                </div>
                <div class="card">
                    <p class="text-sm" style="color: var(--text-muted);">Revenue Bulan Ini</p>
                    <p class="mt-1 text-2xl font-bold">{{ formatRupiah(stats.revenue_this_month) }}</p>
                    <p class="mt-2 text-xs" style="color: var(--text-soft);">Approved saja</p>
                </div>
            </div>

            <!-- Tier distribution + recent -->
            <div class="mt-8 grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
                <div class="card">
                    <h2 class="font-semibold">Distribusi Tier Aktif</h2>
                    <div class="mt-5 space-y-3">
                        <div v-for="tier in tierDistribution" :key="tier.id" class="flex items-center justify-between rounded-xl px-4 py-3" style="background: var(--bg-elevated);">
                            <div>
                                <p class="text-sm font-semibold">{{ tier.name }}</p>
                                <p class="text-xs" style="color: var(--text-soft);">{{ formatRupiah(tier.price) }} / 30 hari</p>
                            </div>
                            <span class="rounded-full px-3 py-1 text-xs font-bold" style="background: color-mix(in srgb, var(--brand) 12%, transparent); color: var(--brand);">{{ tier.memberships_count }} aktif</span>
                        </div>
                    </div>
                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <Link :href="route('admin.users.index')" class="btn-secondary justify-center">Kelola User</Link>
                        <Link :href="route('admin.memberships.index')" class="btn-primary justify-center">Kelola Membership</Link>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="card">
                        <h3 class="font-semibold">Transaksi Terbaru</h3>
                        <div v-if="recentTransactions.length === 0" class="mt-4 text-sm" style="color: var(--text-muted);">Belum ada transaksi.</div>
                        <div v-else class="mt-4 space-y-3">
                            <div v-for="trx in recentTransactions" :key="trx.id" class="flex items-center justify-between text-sm">
                                <div>
                                    <p class="font-medium">{{ trx.user?.name }}</p>
                                    <p class="text-xs" style="color: var(--text-soft);">{{ trx.tier?.name }} • {{ formatRupiah(trx.amount) }}</p>
                                </div>
                                <span class="rounded-full px-2 py-1 text-xs font-semibold" :style="trx.status === 'pending' ? 'background: color-mix(in srgb, var(--warning) 14%, transparent); color: var(--warning);' : trx.status === 'approved' ? 'background: color-mix(in srgb, var(--success) 14%, transparent); color: var(--success);' : 'background: color-mix(in srgb, var(--danger) 14%, transparent); color: var(--danger);'">{{ trx.status }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <h3 class="font-semibold">User Terbaru</h3>
                        <div class="mt-4 space-y-3">
                            <div v-for="u in recentUsers" :key="u.id" class="flex items-center gap-3 text-sm">
                                <span class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold" style="background: color-mix(in srgb, var(--brand) 12%, transparent); color: var(--brand);">{{ u.name.charAt(0).toUpperCase() }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium">{{ u.name }}</p>
                                    <p class="truncate text-xs" style="color: var(--text-soft);">{{ u.email }} • {{ u.active_membership?.tier?.name || 'no tier' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Production menu preview -->
            <div class="card mt-8" style="background: linear-gradient(135deg, color-mix(in srgb, var(--bg-card) 98%, transparent), color-mix(in srgb, var(--brand) 6%, var(--bg-card)));">
                <h3 class="font-semibold">Menu Production</h3>
                <p class="mt-1 text-sm" style="color: var(--text-muted);">Struktur /admin sudah Production Ready. Yang masih Coming Soon akan diisi di Fase B-C.</p>
                <div class="mt-4 grid gap-2 sm:grid-cols-3 text-sm">
                    <Link :href="route('admin.users.index')" class="card-hover p-4">👥 User Management →</Link>
                    <Link :href="route('admin.transactions.index')" class="card-hover p-4">💳 Transaksi →</Link>
                    <Link :href="route('admin.settings.index')" class="card-hover p-4">⚙️ Setting →</Link>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
