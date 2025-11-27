
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
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Log PM2</h2>
            <h4 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Log Files in {{ subfolder ? subfolder : '/var/www/html/log_pm2' }}</h4>
        </template>


        <div class="py-12">
            <div class="max-w-10xl mx-auto sm:px-12 lg:px-12 space-y-12">
                <div class="p-6  min-h-screen dark:bg-gray-800">
                    <div class="max-w-8xl mx-auto bg-white p-4 shadow rounded" style="min-height: 20rem;">

                        <div class="mb-4">
                            <!-- Show the back button if inside a subfolder -->
                            <Link v-if="subfolder" :href="route('logs.index', { subfolder: previousSubfolder })" class="text-blue-500 underline">
                                Back
                            </Link>
                        </div>

                        <!-- Display Folders -->
                        <div v-if="folders.length > 0">
                            <h2 class="font-semibold text-lg">Folders:</h2>
                            <ul class="mb-4">
                                <li v-for="folder in folders" :key="folder">
                                    <Link
                                        :href="route('logs.index', { subfolder: (subfolder ? subfolder + '/' : '') + folder })"
                                        class="text-blue-500 underline"
                                    >
                                        {{ folder }}
                                    </Link>
                                </li>
                            </ul>
                        </div>

                        <!-- Display Files -->
                        <div v-if="files.length > 0">
                            <h2 class="font-semibold text-lg">Files:</h2>
                            <ul>
                                <li v-for="file in files" :key="file" class="flex justify-between items-center">
                                    <Link
                                        :href="route('logs.view', { subfolder, filename: file })"
                                        class="text-blue-500 underline mr-4"
                                    >
                                        {{ file }}
                                    </Link>
                                    <div>
                                        <Link
                                            :href="route('logs.view', { subfolder, filename: file })"
                                            class="text-blue-500 underline mr-4"
                                        >
                                            View
                                        </Link>
                                        <Link
                                            :href="route('logs.download', { subfolder, filename: file })"
                                            class="text-blue-500 underline"
                                        >
                                            Download
                                        </Link>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <!-- No Folders or Files -->
                        <div v-if="folders.length === 0 && files.length === 0">
                            <p>No folders or files found.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
