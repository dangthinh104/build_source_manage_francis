<script setup>
defineOptions({ inheritAttrs: false });

const props = defineProps({
    type: {
        type: String,
        default: 'submit',
    },
    loading: {
        type: Boolean,
        default: false,
    },
    loadingText: {
        type: String,
        default: 'Processing...',
    },
});
</script>

<template>
    <button
        :type="props.type"
        :disabled="props.loading || $attrs.disabled"
        class="relative inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-4 py-2.5 text-xs font-semibold uppercase tracking-wide text-white shadow-lg shadow-indigo-500/30 transition-all duration-300 ease-in-out hover:from-indigo-500 hover:to-indigo-600 hover:shadow-xl hover:shadow-indigo-500/40 hover:-translate-y-0.5 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 disabled:hover:translate-y-0"
        v-bind="$attrs"
    >
        <span :class="props.loading ? 'opacity-0' : 'opacity-100 transition-opacity duration-150'">
        <slot />
        </span>
        <span
            v-if="props.loading"
            class="pointer-events-none absolute inset-0 flex items-center justify-center gap-2 rounded-xl bg-indigo-600/90 text-[0.7rem] font-semibold tracking-wide text-white"
        >
            <svg class="h-4 w-4 animate-spin text-white" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3.5-3.5L12 0v4a8 8 0 01-8 8z" />
            </svg>
            {{ props.loadingText }}
        </span>
    </button>
</template>
