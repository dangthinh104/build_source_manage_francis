<template>
    <button
        class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-red-600 to-red-500 border border-transparent rounded-xl font-semibold text-xs text-white uppercase tracking-widest hover:from-red-500 hover:to-red-600 hover:shadow-lg hover:shadow-red-500/30 hover:-translate-y-0.5 active:from-red-700 active:to-red-600 focus:outline-none focus:ring-2 focus:ring-red-400 focus:ring-offset-2 disabled:opacity-50 disabled:hover:translate-y-0 transition-all ease-in-out duration-300"
    >
        <slot />
    </button>
</template>
