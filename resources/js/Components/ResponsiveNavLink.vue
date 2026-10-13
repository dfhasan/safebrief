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
        ? 'block w-full ps-4 pe-4 py-3 border-s-4 border-hazard text-start text-base font-semibold text-white bg-white/5'
        : 'block w-full ps-4 pe-4 py-3 border-s-4 border-transparent text-start text-base font-medium text-white/75 hover:text-white hover:bg-white/5 transition',
);
</script>

<template>
    <Link :href="href" :class="classes" :aria-current="active ? 'page' : undefined">
        <slot />
    </Link>
</template>
