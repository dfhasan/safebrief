<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

defineProps({ tone: { type: String, default: 'dark' } });

const page = usePage();
// A full page load, so <html lang/dir> and every string switch together.
const next = computed(() => (page.props.locale === 'ar' ? { code: 'en', label: 'English' } : { code: 'ar', label: 'عربي' }));
</script>

<template>
    <a
        :href="route('locale', next.code)"
        :lang="next.code"
        class="inline-flex min-h-[2.25rem] items-center rounded-md border px-3 text-sm font-semibold transition"
        :class="tone === 'dark' ? 'border-white/25 text-white hover:border-white' : 'border-line bg-white text-ink hover:border-ink'"
    >
        {{ next.label }}
    </a>
</template>
