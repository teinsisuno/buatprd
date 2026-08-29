<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    users: Object,
    tiers: Array,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const role = ref(props.filters?.role || '');
const tier = ref(props.filters?.tier || '');

let timeout;
watch(search, (v) => {
    clearTimeout(timeout);
    timeout = setTimeout(() => applyFilter(), 400);
});
const applyFilter = () => {
    router.get(route('admin.users.index'), { search: search.value, role: role.value, tier: tier.value }, { preserveState: true, replace: true });
};
</script>

<template>
    <Head title="User Management" />
    <AdminLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold">User Management</h1>
                    <p class="mt-1 text-sm" style="color: var(--text-muted);">Kelola semua user, role, dan tier. Superadmin bisa kelola Admin.</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-semibold" style="background: var(--bg-elevated); color: var(--text-muted);">{{ users.total }} user</span>
            </div>
        </template>

        <div class="container-base py-8">
            <div class="card">
                <div class="flex flex-col gap-3 sm:flex-row">
                    <div class="relative flex-1">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2" style="color: var(--text-soft);" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-4.35-4.35m2.1-5.4a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z" /></svg>
                        <input v-model="search" class="input-base pl-9" placeholder="Cari nama atau email..." />
                    </div>
                    <select v-model="role" class="input-base sm:w-40" @change="applyFilter">
                        <option value="">Semua Role</option>
                        <option value="superadmin">Superadmin</option>
                        <option value="admin">Admin</option>
                        <option value="member">Member</option>
                    </select>
                    <select v-model="tier" class="input-base sm:w-40" @change="applyFilter">
                        <option value="">Semua Tier</option>
                        <option v-for="t in tiers" :key="t.id" :value="t.slug">{{ t.name }}</option>
                    </select>
                </div>

                <div class="mt-6 overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b text-xs" style="border-color: var(--border-soft); color: var(--text-soft);">
                                <th class="py-3 font-semibold">User</th>
                                <th class="py-3 font-semibold">Role</th>
                                <th class="py-3 font-semibold">Tier</th>
                                <th class="py-3 font-semibold">Expired</th>
                                <th class="py-3 font-semibold text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="u in users.data" :key="u.id" class="border-b last:border-0" style="border-color: var(--border-soft);">
                                <td class="py-4">
                                    <div class="flex items-center gap-3">
                                        <span class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold" style="background: color-mix(in srgb, var(--brand) 12%, transparent); color: var(--brand);">{{ u.name.charAt(0).toUpperCase() }}</span>
                                        <div>
                                            <p class="font-medium">{{ u.name }}</p>
                                            <p class="text-xs" style="color: var(--text-soft);">{{ u.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :style="u.roles[0]?.name === 'superadmin' ? 'background: var(--brand); color: white;' : u.roles[0]?.name === 'admin' ? 'background: color-mix(in srgb, var(--warning) 14%, transparent); color: var(--warning);' : 'background: var(--bg-elevated); color: var(--text-muted);'">{{ u.roles[0]?.name || '-' }}</span>
                                </td>
                                <td class="py-4">
                                    <span v-if="u.active_membership" class="rounded-full px-2.5 py-1 text-xs font-semibold" style="background: color-mix(in srgb, var(--success) 12%, transparent); color: var(--success);">{{ u.active_membership.tier?.name }}</span>
                                    <span v-else class="text-xs" style="color: var(--text-soft);">—</span>
                                </td>
                                <td class="py-4 text-xs" style="color: var(--text-soft);">{{ u.active_membership?.expired_at ? new Date(u.active_membership.expired_at).toLocaleDateString('id-ID') : '—' }}</td>
                                <td class="py-4 text-right">
                                    <button type="button" class="btn-ghost px-2 text-xs">Kelola</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-if="users.data.length === 0" class="py-12 text-center">
                        <p class="font-semibold">Tidak ada user.</p>
                        <p class="mt-1 text-sm" style="color: var(--text-muted);">Coba ubah filter.</p>
                    </div>
                </div>

                <div v-if="users.links" class="mt-6 flex flex-wrap gap-2">
                    <Link v-for="link in users.links" :key="link.label" :href="link.url || '#'" :class="['rounded-lg px-3 py-1.5 text-xs', link.active ? 'btn-primary px-3 py-1.5' : 'btn-ghost border']" style="border-color: var(--border-soft);" v-html="link.label" />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
