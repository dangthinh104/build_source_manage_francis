
<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    filename: String,
    subfolder: String,
    logData: Object, // Contains data, links, file_size
});

// State
const isFullScreen = ref(false);
const isExpanded = ref(false);

// Computed
const isErrorFile = computed(() => props.filename?.toLowerCase().includes('error'));

// Methods
const toggleExpand = () => {
    isExpanded.value = !isExpanded.value;
};

const toggleFullScreen = () => {
    isFullScreen.value = !isFullScreen.value;
};

const downloadLog = () => {
    window.location.href = route('logs.download', { 
        subfolder: props.subfolder, 
        filename: props.filename 
    });
};
</script>

<template>
    <Head :title="`Raw Log: ${filename}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl flex items-center justify-center shadow-lg bg-gradient-to-br" :class="isErrorFile ? 'from-rose-400 to-red-500' : 'from-emerald-400 to-teal-500'">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-xl text-slate-800 leading-tight">Raw Log Viewer</h2>
                    <p class="text-sm text-slate-500">{{ subfolder }}/{{ filename }}</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- File Info Card -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-slate-100">
                <div class="bg-gradient-to-r from-slate-50 to-slate-100 px-6 py-4 border-b border-slate-200">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 shrink-0 rounded-xl flex items-center justify-center" :class="isErrorFile ? 'bg-rose-100' : 'bg-emerald-100'">
                                <svg class="h-5 w-5" :class="isErrorFile ? 'text-rose-600' : 'text-emerald-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-lg font-semibold text-slate-900 font-mono">{{ filename }}</h1>
                                <p class="text-xs text-slate-500">{{ logData.file_size }} bytes • {{ logData.total }} lines</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <Link
                                :href="route('logs.advance', { subfolder, filename })"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-violet-500 to-purple-500 text-white rounded-lg font-medium hover:from-violet-600 hover:to-purple-600 shadow-sm hover:shadow-md transition-all duration-200"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                Switch to Advance
                            </Link>
                            <Link
                                :href="route('logs.index', { subfolder })"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-100 text-slate-700 rounded-lg font-medium hover:bg-slate-200 transition-all duration-200"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Back to Folder
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="px-6 py-4 bg-white border-b border-slate-100 flex flex-wrap items-center gap-3">
                    <button
                        @click="toggleExpand"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-amber-500 to-orange-500 text-white rounded-lg font-medium hover:from-amber-600 hover:to-orange-600 shadow-sm hover:shadow-md transition-all duration-200"
                    >
                        <svg v-if="!isExpanded" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                        <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4H4m5 0L4 9m11-5h5v5m0-5l-5 5M9 15v5H4m5 0l-5-5m15 5h-5v-5m5 5l-5-5" />
                        </svg>
                        {{ isExpanded ? 'Collapse' : 'Expand' }}
                    </button>

                    <button
                        @click="toggleFullScreen"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-100 text-slate-700 rounded-lg font-medium hover:bg-slate-200 transition-all duration-200"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                        Full Screen
                    </button>

                    <button
                        @click="downloadLog"
                        class="inline-flex items-center gap-1.5 px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-lg font-medium hover:from-emerald-600 hover:to-teal-600 shadow-sm hover:shadow-md transition-all duration-200"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                        Download
                    </button>
                </div>

                <!-- Log Content -->
                <div class="relative">
                    <pre 
                        class="font-mono p-6 overflow-auto transition-all duration-300 bg-gray-900 custom-scrollbar-dark"
                        :class="[
                            isExpanded ? 'max-h-[80vh]' : 'max-h-96',
                            isErrorFile ? 'text-rose-400' : 'text-green-400'
                        ]"
                    ><div v-for="(line, index) in logData.data" :key="index"><span class="select-none opacity-30 w-8 inline-block text-right mr-4">{{ line.line_number }}</span>{{ line.raw }}</div></pre>
                </div>

                 <!-- Pagination -->
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50" v-if="logData.links && logData.links.length > 3">
                    <Pagination :links="logData.links" />
                </div>
            </div>
        </div>

        <!-- Full Screen Modal -->
        <Teleport to="body">
            <div 
                v-if="isFullScreen"
                class="fixed inset-0 z-50 bg-gray-900/95 flex flex-col"
            >
                <!-- Header -->
                <div class="flex-shrink-0 flex items-center justify-between px-6 py-4 border-b border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-lg flex items-center justify-center" :class="isErrorFile ? 'bg-rose-500/20' : 'bg-emerald-500/20'">
                            <svg class="h-5 w-5" :class="isErrorFile ? 'text-rose-400' : 'text-emerald-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <span class="text-white font-mono font-medium">{{ filename }}</span>
                        <span class="text-xs text-gray-400 bg-gray-800 px-2 py-0.5 rounded">RAW</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            @click="downloadLog"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-500/20 text-emerald-400 rounded-lg font-medium hover:bg-emerald-500/30 transition-all duration-200"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Download
                        </button>
                        <button
                            @click="toggleFullScreen"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/10 text-white rounded-lg font-medium hover:bg-white/20 transition-all duration-200"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Close
                        </button>
                    </div>
                </div>
                <!-- Content -->
                <div class="flex-1 overflow-auto p-6 custom-scrollbar-dark">
                    <pre 
                        class="font-mono whitespace-pre-wrap break-words text-sm"
                        :class="isErrorFile ? 'text-rose-400' : 'text-green-400'"
                    ><div v-for="(line, index) in logData.data" :key="index"><span class="select-none opacity-30 w-8 inline-block text-right mr-4">{{ line.line_number }}</span>{{ line.raw }}</div></pre>
                    <div class="px-6 py-4 border-t border-gray-700 bg-gray-800" v-if="logData.links && logData.links.length > 3">
                        <Pagination :links="logData.links" class="text-gray-300" />
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
pre {
    white-space: pre-wrap;
    word-wrap: break-word;
    font-size: 0.75rem;
    font-family: "JetBrains Mono", "Fira Code", "Courier New", Courier, monospace;
    line-height: 1.6;
}
</style>
