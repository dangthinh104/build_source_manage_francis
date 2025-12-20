
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";

const props = defineProps({
    filename: String,
    content: String,
    subfolder: String,
});

// Expand state
const isExpanded = ref(false);
const isFullScreen = ref(false);

// Computed download URL
const downloadUrl = computed(() => {
    return route('logs.download', { subfolder: props.subfolder, filename: props.filename });
});

// Toggle expand
const toggleExpand = () => {
    isExpanded.value = !isExpanded.value;
};

// Toggle fullscreen
const toggleFullScreen = () => {
    isFullScreen.value = !isFullScreen.value;
};

// Direct download - use native browser download
const downloadLog = () => {
    // Create a blob from the content and trigger download
    const blob = new Blob([props.content], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = props.filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
};

// Determine if file is an error log
const isErrorFile = computed(() => {
    return props.filename?.toLowerCase().includes('error');
});
</script>

<template>
    <Head :title="`Log: ${filename}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl flex items-center justify-center shadow-lg bg-gradient-to-br" :class="isErrorFile ? 'from-rose-400 to-red-500' : 'from-emerald-400 to-teal-500'">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-xl text-slate-800 leading-tight">Log Viewer</h2>
                    <p class="text-sm text-slate-500">{{ subfolder }}</p>
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
                                <p class="text-xs text-slate-500">{{ content?.length || 0 }} characters</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
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
                        class="font-mono p-6 overflow-auto transition-all duration-300 bg-gray-900 text-green-400"
                        :class="[
                            isExpanded ? 'max-h-[80vh]' : 'max-h-96',
                            isErrorFile ? 'text-rose-400' : 'text-green-400'
                        ]"
                    >{{ content }}</pre>
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
                <div class="flex-1 overflow-auto p-6">
                    <pre 
                        class="font-mono whitespace-pre-wrap break-words text-sm"
                        :class="isErrorFile ? 'text-rose-400' : 'text-green-400'"
                    >{{ content }}</pre>
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
