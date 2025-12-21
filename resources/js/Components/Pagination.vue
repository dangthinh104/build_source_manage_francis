<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <div v-if="links.length > 1">
        <div class="flex flex-wrap -mb-1">
            <template v-for="(link, key) in links" :key="key">
                <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-slate-400 border border-slate-200 rounded" v-html="link.label" />
                <Link
                    v-else
                    class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded transition-all duration-200"
                    :class="{ 
                        'bg-primary-600 text-white border-primary-600 shadow-sm': link.active, 
                        'bg-white text-slate-700 border-slate-300 hover:border-primary hover:text-primary hover:shadow-sm': !link.active 
                    }"
                    :href="link.url"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
