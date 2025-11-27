
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import PrimaryButton from "@/Components/PrimaryButton.vue";
const props = defineProps({
    filename: String,
    content: String,
    subfolder: String,
});
const downloadLog = async () => {
    try {
        // Construct the route URL with the passed props (subfolder and filename)
        const downloadUrl = route('logs.download', { subfolder: props.subfolder, filename: props.filename });

        const response = await axios({
            url: downloadUrl,
            method: 'GET',
            responseType: 'blob', // Important to handle file download
        });

        // Create a URL for the downloaded file
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', props.filename); // Set the file name for download
        document.body.appendChild(link);
        link.click();

        // Clean up after the download
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    } catch (error) {
        console.error('Error downloading the log file:', error);
    }
};
</script>

<template>
    <Head title="Log PM2" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Log PM2</h2>
            <h4 class="font-semibold text-xl text-slate-800 leading-tight">Log Files in {{ subfolder ? subfolder : '/var/www/html/log_pm2' }}</h4>
        </template>


        <div class="py-12">
            <div class="max-w-10xl mx-auto sm:px-12 lg:px-12 space-y-12">
                <div class="p-6  min-h-screen bg-white">
                    <div class="max-w-8xl mx-auto bg-white p-4 shadow rounded" style="min-height: 20rem;">
                    <div class="flex justify-between items-center mb-4">
                            <h1 class="text-2xl font-bold">Viewing Log File: {{ filename }}</h1>
                            <!-- Back Button to return to the previous folder -->
                            <Link
                                :href="route('logs.index', { subfolder })"
                                class="text-blue-500 underline"
                            >
                                Back to Folder
                            </Link>
                        </div>

                        <pre class="bg-gray-200 p-4 rounded shadow overflow-auto max-h-96 " style="min-height:100rem">
        {{ content }}
      </pre>

                        <div class="mt-4">
                            <PrimaryButton @click="downloadLog">Download</PrimaryButton>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
pre {
    white-space: pre-wrap;
    word-wrap: break-word;
    font-size: 0.7rem;
    font-family: "Courier New", Courier, "Lucida Console", Monaco, monospace;
    width: 100%; /* Full width */
    border: 1px solid #ccc;
    padding: 10px;
    background-color: #171717;
    color:white;
}
</style>
