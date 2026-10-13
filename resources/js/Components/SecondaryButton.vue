<script setup>
defineProps({
    type: {
        type: String,
        default: 'button',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex min-h-[2.75rem] items-center justify-center gap-2 rounded-md border border-line bg-white px-5 text-sm font-semibold text-ink transition hover:border-ink disabled:cursor-not-allowed disabled:opacity-40"
    >
        <slot />
    </button>
</template>
