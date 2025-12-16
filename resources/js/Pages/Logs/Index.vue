
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {computed, ref} from 'vue'
const props = defineProps({
    folders: Array,
    files: Array,
    subfolder: String,
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
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Log PM2</h2>
            <h4 class="font-semibold text-xl text-slate-800 leading-tight">Log Files in {{ subfolder ? subfolder : '/var/www/html/log_pm2' }}</h4>
        </template>


        <div class="space-y-6">
            <!-- Breadcrumb Navigation -->
            <div class="bg-white rounded-2xl shadow-lg px-6 py-4">
                <div class="flex items-center gap-2 text-sm">
                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                    <Link v-if="subfolder" :href="route('logs.index', { subfolder: previousSubfolder })" class="text-primary hover:text-primary font-medium transition-colors">
                        <svg class="h-4 w-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Back
                    </Link>
                    <span class="text-slate-600 font-medium">{{ subfolder || '/var/www/html/log_pm2' }}</span>
                </div>
            </div>

            <!-- Folders Card -->
            <div v-if="folders.length > 0" class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-slate-50 to-slate-100 px-6 py-4 border-b border-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                        <svg class="h-5 w-5 shrink-0 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        Folders
                    </h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <Link
                            v-for="folder in folders"
                            :key="folder"
                            :href="route('logs.index', { subfolder: (subfolder ? subfolder + '/' : '') + folder })"
                            class="flex items-center gap-3 p-4 bg-gradient-to-br from-slate-50 to-white border border-slate-200 rounded-xl hover:shadow-md hover:border-primary hover:-translate-y-0.5 transition-all duration-200"
                        >
                            <div class="h-10 w-10 shrink-0 rounded-lg bg-primary-50 flex items-center justify-center">
                                <svg class="h-6 w-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                </svg>
                            </div>
                            <span class="font-medium text-slate-900">{{ folder }}</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Files Card -->
            <div v-if="files.length > 0" class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-slate-50 to-slate-100 px-6 py-4 border-b border-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                        <svg class="h-5 w-5 shrink-0 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Log Files
                        <span class="ml-auto text-sm font-normal text-slate-600">{{ files.length }} files</span>
                    </h2>
                </div>
                <div class="divide-y divide-slate-100">
                    <div
                        v-for="file in files"
                        :key="file"
                        class="px-6 py-4 hover:bg-slate-50 transition-colors duration-150 flex items-center justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 shrink-0 rounded-lg bg-slate-100 flex items-center justify-center">
                                <svg class="h-5 w-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <span class="text-sm font-medium text-slate-900 font-mono">{{ file }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <Link
                                :href="route('logs.view', { subfolder, filename: file })"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary-50 text-primary rounded-lg font-medium hover:bg-primary-50 transition-all duration-200"
                            >
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>View</span>
                            </Link>
                            <Link
                                :href="route('logs.download', { subfolder, filename: file })"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 text-slate-600 rounded-lg font-medium hover:bg-slate-200 transition-all duration-200"
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
            <div v-if="folders.length === 0 && files.length === 0" class="bg-white rounded-2xl shadow-lg p-12">
                <div class="flex flex-col items-center justify-center text-slate-500">
                    <svg class="h-16 w-16 mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                    </svg>
                    <p class="text-lg font-medium mb-1">No folders or files found</p>
                    <p class="text-sm text-slate-400">This directory is empty</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
