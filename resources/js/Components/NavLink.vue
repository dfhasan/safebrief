<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const classes = computed(() =>
    props.active
        ? 'inline-flex items-center h-full px-1 border-b-[3px] border-hazard text-sm font-semibold text-white'
        : 'inline-flex items-center h-full px-1 border-b-[3px] border-transparent text-sm font-medium text-white/70 hover:text-white transition',
);
</script>

<template>
    <Link :href="href" :class="classes" :aria-current="active ? 'page' : undefined">
        <slot />
    </Link>
</template>
