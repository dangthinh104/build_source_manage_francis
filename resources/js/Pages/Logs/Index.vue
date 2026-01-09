
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue'

const props = defineProps({
    folders: Array,
    files: Array,
    subfolder: String,
    notFound: Boolean,
    requestedFolder: String,
    basePathError: String,
});

// Create a computed property using Composition API
const previousSubfolder = computed(() => {
    return props.subfolder.split('/').slice(0, -1).join('/') || null;
});

</script>

<template>
    <Head title="Log PM2" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center shadow-lg">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-xl text-slate-800 leading-tight">PM2 Logs</h2>
                    <p class="text-sm text-slate-500">{{ subfolder ? subfolder : 'Root directory' }}</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Base Path Error Banner -->
            <div v-if="basePathError" class="bg-gradient-to-r from-rose-50 to-red-50 border border-rose-200 rounded-2xl p-6 shadow-lg">
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 shrink-0 rounded-full bg-gradient-to-br from-rose-400 to-red-500 flex items-center justify-center shadow-md">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-rose-900">Log Directory Not Found</h3>
                        <p class="text-sm text-rose-700 mt-1">{{ basePathError }}</p>
                        <p class="text-sm text-rose-600 mt-2">
                            Go to <a href="/parameters" class="underline font-medium hover:text-rose-800">Parameters</a> to update the LOG_PM2_PATH setting.
                        </p>
                    </div>
                </div>
            </div>

            <!-- 404 Warning Banner -->
            <div v-if="notFound && !basePathError" class="bg-gradient-to-r from-primary-50 to-primary-100 border border-primary-200 rounded-2xl p-6 shadow-lg">
                <div class="flex items-start gap-4">
                    <div class="h-12 w-12 shrink-0 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center shadow-md">
                        <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-primary-900">Folder Not Found</h3>
                        <p class="text-sm text-primary-700 mt-1">
                            The folder <code class="bg-primary-100 px-2 py-0.5 rounded font-mono text-primary-800">{{ requestedFolder }}</code> does not exist.
                        </p>
                        <p class="text-sm text-primary-600 mt-2">
                            Showing all available log folders below. Please select the correct one.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Breadcrumb Navigation -->
            <div class="bg-white rounded-2xl shadow-lg px-6 py-4 border border-slate-100">
                <div class="flex items-center gap-3 text-sm">
                    <div class="h-8 w-8 rounded-lg bg-slate-100 flex items-center justify-center">
                        <svg class="h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <Link
                            :href="route('logs.index')"
                            class="text-primary hover:text-primary-600 font-medium transition-colors"
                        >
                            Root
                        </Link>
                        <template v-if="subfolder">
                            <svg class="h-4 w-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                            <span class="text-slate-600 font-medium">{{ subfolder }}</span>
                        </template>
                    </div>
                    <Link
                        v-if="subfolder"
                        :href="route('logs.index', { subfolder: previousSubfolder })"
                        class="ml-auto inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 text-slate-600 rounded-lg font-medium hover:bg-slate-200 transition-all duration-200"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        <span>Back</span>
                    </Link>
                </div>
            </div>

            <!-- Folders Card -->
            <div v-if="folders.length > 0" class="bg-white rounded-2xl shadow-lg overflow-hidden border border-slate-100">
                <div class="bg-gradient-to-r from-slate-50 to-slate-100 px-6 py-4 border-b border-slate-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                            <svg class="h-5 w-5 shrink-0 text-primary-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                            </svg>
                            Log Folders
                        </h2>
                        <span class="text-sm text-slate-500 bg-white px-3 py-1 rounded-full border border-slate-200">
                            {{ folders.length }} folders
                        </span>
                    </div>
                </div>

                <!-- Table View -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    Folder Name
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    Files
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    Latest Error
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    Mapped Site
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr
                                v-for="folder in folders"
                                :key="folder.name"
                                class="hover:bg-primary-50/50 transition-colors group"
                            >
                                <!-- Folder Name -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 shrink-0 rounded-lg bg-gradient-to-br from-primary-100 to-primary-200 group-hover:from-primary-200 group-hover:to-primary-300 flex items-center justify-center transition-colors">
                                            <svg class="h-5 w-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ folder.name }}</p>
                                            <p class="text-xs text-slate-500">{{ folder.total_files }} total files</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Files Count -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center gap-1.5 px-2.5 py-1 bg-rose-50 rounded-lg">
                                            <svg class="h-4 w-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <span class="text-sm font-semibold text-rose-700">{{ folder.error_count }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 rounded-lg">
                                            <svg class="h-4 w-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span class="text-sm font-semibold text-emerald-700">{{ folder.out_count }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Latest Error -->
                                <td class="px-6 py-4">
                                    <div v-if="folder.latest_error_file" class="max-w-xs">
                                        <p class="text-xs font-mono text-rose-600 truncate">{{ folder.latest_error_file }}</p>
                                        <p class="text-xs text-slate-400">{{ folder.latest_error_time }}</p>
                                    </div>
                                    <div v-else class="flex items-center gap-1.5 text-emerald-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="text-xs font-medium">No errors</span>
                                    </div>
                                </td>

                                <!-- Mapped Site -->
                                <td class="px-6 py-4">
                                    <div v-if="folder.site">
                                        <a
                                            :href="`https://${folder.site.site_name}`"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 text-sm text-primary hover:text-primary-600 font-medium"
                                        >
                                            {{ folder.site.site_name }}
                                            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                        </a>
                                        <p class="text-xs text-slate-400">Port: {{ folder.site.port_pm2 }}</p>
                                    </div>
                                    <span v-else class="text-sm text-slate-400 italic">No site linked</span>
                                </td>

                                <!-- Actions -->
                                <td class="px-6 py-4 text-right">
                                    <Link
                                        :href="route('logs.subfolder', { subfolder: (subfolder ? subfolder + '/' : '') + folder.name })"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 btn-primary text-white rounded-lg font-medium shadow-md hover:shadow-lg transition-all duration-200"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                        <span>View Files</span>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Files Card -->
            <div v-if="files.length > 0" class="bg-white rounded-2xl shadow-lg overflow-hidden border border-slate-100">
                <div class="bg-gradient-to-r from-slate-50 to-slate-100 px-6 py-4 border-b border-slate-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                            <svg class="h-5 w-5 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Log Files
                        </h2>
                        <span class="text-sm text-slate-500 bg-white px-3 py-1 rounded-full border border-slate-200">
                            {{ files.length }} files
                        </span>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">
                    <div
                        v-for="file in files"
                        :key="file"
                        class="px-6 py-4 hover:bg-gradient-to-r hover:from-slate-50 hover:to-white transition-all duration-200 flex items-center justify-between group"
                    >
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <div class="h-10 w-10 shrink-0 rounded-xl bg-gradient-to-br from-emerald-100 to-teal-100 flex items-center justify-center">
                                <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-slate-900 font-mono truncate">{{ file }}</span>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <Link
                                :href="route('logs.advance', { subfolder, filename: file })"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-violet-500 to-purple-500 text-white rounded-lg font-medium hover:from-violet-600 hover:to-purple-600 shadow-sm hover:shadow-md transition-all duration-200"
                            >
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                </svg>
                                <span>Advance</span>
                            </Link>
                            <Link
                                :href="route('logs.raw', { subfolder, filename: file })"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 text-slate-700 rounded-lg font-medium hover:bg-slate-200 transition-all duration-200"
                            >
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                </svg>
                                <span>Raw</span>
                            </Link>
                            <Link
                                :href="route('logs.download', { subfolder, filename: file })"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-100 text-slate-700 rounded-lg font-medium hover:bg-slate-200 transition-all duration-200"
                            >
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                                <span>Download</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="folders.length === 0 && files.length === 0 && !notFound" class="bg-white rounded-2xl shadow-lg p-12 border border-slate-100">
                <div class="flex flex-col items-center justify-center text-slate-500">
                    <div class="h-20 w-20 rounded-full bg-gradient-to-br from-slate-100 to-slate-200 flex items-center justify-center mb-4">
                        <svg class="h-10 w-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                    </div>
                    <p class="text-lg font-semibold text-slate-700 mb-1">No folders or files found</p>
                    <p class="text-sm text-slate-400">This directory is empty</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
