<script setup>
defineProps({
    value: {
        type: String,
    },
});
</script>

<template>
    <label class="block text-sm font-semibold" style="color: var(--text-main);">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
