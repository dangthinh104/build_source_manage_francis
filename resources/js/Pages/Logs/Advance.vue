
<script setup>
import Pagination from '@/Components/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    filename: String,
    subfolder: String,
    logData: Object, // Contains data, links, file_size
    availableFiles: Array,
    currentQuery: String,
});

// State
const isFullScreen = ref(false);
const autoRefresh = ref(false);
const autoRefreshInterval = ref(null);
const searchQuery = ref(props.currentQuery || '');
const expandedRows = ref(new Set());
const levelFilters = ref({
    ERROR: true,
    HTTP_ERROR: true,
    HTTP: true,
    WARNING: true,
    DEBUG: true,
    INFO: true,
});

// Computed
const isErrorFile = computed(() => props.filename?.toLowerCase().includes('error'));

const filteredLogs = computed(() => {
    return (props.logData.data || []).filter(log => levelFilters.value[log.level]);
});

const levelCounts = computed(() => {
    const counts = { ERROR: 0, HTTP_ERROR: 0, HTTP: 0, WARNING: 0, DEBUG: 0, INFO: 0 };
    (props.logData.data || []).forEach(log => {
        if (counts[log.level] !== undefined) {
            counts[log.level]++;
        }
    });
    return counts;
});

// Methods
const getLevelClasses = (level) => {
    const classes = {
        ERROR: 'bg-rose-500/20 text-rose-400 border-rose-500/30',
        HTTP_ERROR: 'bg-orange-500/20 text-orange-400 border-orange-500/30',
        HTTP: 'bg-emerald-500/20 text-emerald-400 border-emerald-500/30',
        WARNING: 'bg-amber-500/20 text-amber-400 border-amber-500/30',
        DEBUG: 'bg-violet-500/20 text-violet-400 border-violet-500/30',
        INFO: 'bg-sky-500/20 text-sky-400 border-sky-500/30',
    };
    return classes[level] || classes.INFO;
};

const getMessageClasses = (level) => {
    const classes = {
        ERROR: 'text-rose-300',
        HTTP_ERROR: 'text-orange-300',
        HTTP: 'text-emerald-300',
        WARNING: 'text-amber-300',
        DEBUG: 'text-violet-300',
        INFO: 'text-slate-300',
    };
    return classes[level] || classes.INFO;
};

const toggleRow = (lineNumber) => {
    if (expandedRows.value.has(lineNumber)) {
        expandedRows.value.delete(lineNumber);
    } else {
        expandedRows.value.add(lineNumber);
    }
};

const isExpanded = (lineNumber) => expandedRows.value.has(lineNumber);

const goToPage = (page) => {
    // Deprecated with Pagination component, but if needed for custom actions
    // Pagination component uses Links which visit URL directly
};

const executeSearch = () => {
    router.get(route('logs.advance', { 
        subfolder: props.subfolder, 
        filename: props.filename 
    }), { 
        query: searchQuery.value || undefined,
    }, { preserveState: true });
};

const switchFile = (newFilename) => {
    router.get(route('logs.advance', { 
        subfolder: props.subfolder, 
        filename: newFilename 
    }));
};

const refresh = () => {
    router.reload({ only: ['logData'] });
};

const toggleAutoRefresh = () => {
    autoRefresh.value = !autoRefresh.value;
    if (autoRefresh.value) {
        autoRefreshInterval.value = setInterval(refresh, 5000);
    } else {
        clearInterval(autoRefreshInterval.value);
    }
};

const downloadLog = () => {
    window.location.href = route('logs.download', { 
        subfolder: props.subfolder, 
        filename: props.filename 
    });
};

const toggleFullScreen = () => {
    isFullScreen.value = !isFullScreen.value;
};

const toggleLevel = (level) => {
    levelFilters.value[level] = !levelFilters.value[level];
};

// Cleanup
onUnmounted(() => {
    if (autoRefreshInterval.value) {
        clearInterval(autoRefreshInterval.value);
    }
});
</script>

<template>
    <Head :title="`Advance Log: ${filename}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl flex items-center justify-center shadow-lg bg-gradient-to-br from-violet-500 to-purple-600">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-xl text-slate-800 leading-tight">Smart Log Insights</h2>
                    <p class="text-sm text-slate-500">{{ subfolder }}/{{ filename }}</p>
                </div>
            </div>
        </template>

        <div class="space-y-4">
            <!-- CloudWatch-style Controls Bar -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-slate-100">
                <div class="bg-gradient-to-r from-violet-50 to-purple-50 px-6 py-4 border-b border-slate-200">
                    <div class="flex flex-wrap items-center gap-4">
                        <!-- File Selector -->
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-slate-600 font-medium">File:</label>
                            <select 
                                :value="filename"
                                @change="switchFile($event.target.value)"
                                class="px-3 py-1.5 text-sm border border-slate-200 rounded-lg bg-white focus:border-violet-400 focus:ring-1 focus:ring-violet-400"
                            >
                                <option v-for="file in availableFiles" :key="file" :value="file">
                                    {{ file }}
                                </option>
                            </select>
                        </div>

                        <div class="h-6 w-px bg-slate-200"></div>

                        <!-- Search/Query Input -->
                        <div class="flex-1 min-w-[200px] max-w-md">
                            <div class="relative">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Search logs... (press Enter)"
                                    @keydown.enter="executeSearch"
                                    class="w-full pl-9 pr-4 py-1.5 text-sm border border-slate-200 rounded-lg focus:border-violet-400 focus:ring-1 focus:ring-violet-400"
                                />
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <div class="h-6 w-px bg-slate-200"></div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2">
                            <button
                                @click="refresh"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 text-slate-700 rounded-lg font-medium hover:bg-slate-200 transition-all duration-200"
                                title="Refresh"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </button>
                            <button
                                @click="toggleAutoRefresh"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-medium transition-all duration-200"
                                :class="autoRefresh ? 'bg-green-500 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
                                title="Auto-refresh"
                            >
                                <svg class="h-4 w-4" :class="{ 'animate-spin': autoRefresh }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                                {{ autoRefresh ? 'Live' : 'Auto' }}
                            </button>
                            <button
                                @click="toggleFullScreen"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 text-slate-700 rounded-lg font-medium hover:bg-slate-200 transition-all duration-200"
                                title="Fullscreen"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                                </svg>
                            </button>
                            <button
                                @click="downloadLog"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-lg font-medium hover:from-emerald-600 hover:to-teal-600 shadow-sm transition-all duration-200"
                                title="Download"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                            </button>
                            <Link
                                :href="route('logs.raw', { subfolder, filename })"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 text-slate-700 rounded-lg font-medium hover:bg-slate-200 transition-all duration-200"
                                title="Raw View"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                </svg>
                                Raw
                            </Link>
                            <Link
                                :href="route('logs.index', { subfolder })"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 text-slate-700 rounded-lg font-medium hover:bg-slate-200 transition-all duration-200"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Back
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Level Filters -->
                <div class="px-6 py-2 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center gap-2">
                    <span class="text-xs text-slate-500 font-medium">Levels:</span>
                    <button
                        v-for="(enabled, level) in levelFilters"
                        :key="level"
                        @click="toggleLevel(level)"
                        class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-md border transition-all"
                        :class="enabled ? getLevelClasses(level) : 'bg-slate-100 text-slate-400 border-slate-200'"
                    >
                        {{ level.replace('_', ' ') }}
                        <span class="opacity-70">({{ levelCounts[level] }})</span>
                    </button>
                    <span class="ml-auto text-xs text-slate-500">
                        {{ logData.file_size_formatted }} • {{ logData.total || 0 }} entries
                    </span>
                </div>
            </div>

            <!-- Log Entries Table -->
            <div class="bg-gray-900 rounded-2xl shadow-lg overflow-hidden">
                <div class="max-h-[60vh] overflow-auto custom-scrollbar-dark">
                    <table class="w-full">
                        <thead class="bg-gray-800 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider w-40">Timestamp</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider w-24">Level</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Message</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800">
                            <template v-for="(log, index) in filteredLogs" :key="log.line_number">
                                <tr 
                                    class="hover:bg-gray-800/50 transition-colors cursor-pointer"
                                    @click="log.stack_trace?.length > 0 && toggleRow(log.line_number)"
                                >
                                    <td class="px-4 py-2 text-xs text-gray-500 font-mono whitespace-nowrap">
                                        {{ log.timestamp || '--' }}
                                    </td>
                                    <td class="px-4 py-2">
                                        <span 
                                            class="inline-flex items-center text-xs font-medium px-2 py-0.5 rounded border"
                                            :class="getLevelClasses(log.level)"
                                        >
                                            {{ log.level.replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <div class="flex items-start gap-2">
                                            <button
                                                v-if="log.stack_trace?.length > 0"
                                                class="shrink-0 mt-0.5 text-gray-500 hover:text-gray-300 transition-colors"
                                            >
                                                <svg 
                                                    class="h-4 w-4 transition-transform" 
                                                    :class="{ 'rotate-90': isExpanded(log.line_number) }"
                                                    fill="none" 
                                                    stroke="currentColor" 
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                            <span 
                                                class="text-sm font-mono"
                                                :class="[
                                                    getMessageClasses(log.level),
                                                    !isExpanded(log.line_number) ? 'line-clamp-2' : ''
                                                ]"
                                            >{{ log.message }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <!-- Stack Trace Expansion -->
                                <tr v-if="isExpanded(log.line_number) && log.stack_trace?.length" class="bg-gray-800/30">
                                    <td colspan="3" class="px-4 py-2 pl-16">
                                        <div class="text-xs font-mono text-gray-500 space-y-0.5">
                                            <div v-for="(traceLine, i) in log.stack_trace" :key="i" class="text-gray-400">
                                                {{ traceLine }}
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <!-- Empty State -->
                            <tr v-if="filteredLogs.length === 0">
                                <td colspan="3" class="p-12 text-center">
                                    <svg class="h-12 w-12 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <p class="text-gray-500">No matching log entries</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="logData.links && logData.links.length > 3" class="bg-white rounded-2xl shadow-lg px-6 py-4 border border-slate-100">
                <Pagination :links="logData.links" />
            </div>
        </div>

        <!-- Full Screen Modal -->
        <Teleport to="body">
            <div 
                v-if="isFullScreen"
                class="fixed inset-0 z-50 bg-gray-900 flex flex-col"
            >
                <!-- Header -->
                <div class="flex-shrink-0 flex items-center justify-between px-6 py-3 border-b border-gray-700 bg-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-lg flex items-center justify-center bg-violet-500/20">
                            <svg class="h-5 w-5 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <span class="text-white font-mono font-medium">{{ filename }}</span>
                        <span class="text-xs text-violet-400 bg-violet-500/20 px-2 py-0.5 rounded">ADVANCE</span>
                        <span class="text-gray-400 text-sm">{{ filteredLogs.length }} entries</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <button
                            @click="toggleAutoRefresh"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg font-medium transition-all duration-200"
                            :class="autoRefresh ? 'bg-green-500/20 text-green-400' : 'bg-white/10 text-white hover:bg-white/20'"
                        >
                            <svg class="h-4 w-4" :class="{ 'animate-spin': autoRefresh }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            {{ autoRefresh ? 'Live' : 'Auto' }}
                        </button>
                        <button
                            @click="toggleFullScreen"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/10 text-white rounded-lg font-medium hover:bg-white/20 transition-all duration-200"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Exit
                        </button>
                    </div>
                </div>
                <!-- Content -->
                <div class="flex-1 overflow-auto custom-scrollbar-dark">
                    <table class="w-full">
                        <thead class="bg-gray-800 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider w-40">Timestamp</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider w-24">Level</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Message</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800">
                            <template v-for="(log, index) in filteredLogs" :key="'fs-'+log.line_number">
                                <tr 
                                    class="hover:bg-gray-800/50 transition-colors cursor-pointer"
                                    @click="log.stack_trace?.length > 0 && toggleRow(log.line_number)"
                                >
                                    <td class="px-4 py-2 text-xs text-gray-500 font-mono whitespace-nowrap">
                                        {{ log.timestamp || '--' }}
                                    </td>
                                    <td class="px-4 py-2">
                                        <span 
                                            class="inline-flex items-center text-xs font-medium px-2 py-0.5 rounded border"
                                            :class="getLevelClasses(log.level)"
                                        >
                                            {{ log.level.replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <div class="flex items-start gap-2">
                                            <button
                                                v-if="log.stack_trace?.length > 0"
                                                class="shrink-0 mt-0.5 text-gray-500 hover:text-gray-300 transition-colors"
                                            >
                                                <svg 
                                                    class="h-4 w-4 transition-transform" 
                                                    :class="{ 'rotate-90': isExpanded(log.line_number) }"
                                                    fill="none" 
                                                    stroke="currentColor" 
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                            <span 
                                                class="text-sm font-mono break-all"
                                                :class="getMessageClasses(log.level)"
                                            >{{ log.message }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="isExpanded(log.line_number) && log.stack_trace?.length" class="bg-gray-800/30">
                                    <td colspan="3" class="px-4 py-2 pl-16">
                                        <div class="text-xs font-mono text-gray-500 space-y-0.5">
                                            <div v-for="(traceLine, i) in log.stack_trace" :key="i" class="text-gray-400">
                                                {{ traceLine }}
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.font-mono {
    font-family: "JetBrains Mono", "Fira Code", "Courier New", Courier, monospace;
}
</style>
