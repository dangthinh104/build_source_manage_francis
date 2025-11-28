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
        class="block w-full px-4 py-2.5 text-start text-sm leading-5 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:bg-indigo-50 transition-all duration-200 ease-in-out font-medium"
    >
        <slot />
    </Link>
</template>
