<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-2 text-start text-sm leading-5 transition duration-150 ease-in-out hover:bg-white/5 focus:bg-white/5 focus:outline-none"
        style="color: var(--text-main);"
    >
        <slot />
    </Link>
</template>
