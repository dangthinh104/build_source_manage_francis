<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import { toast } from 'vue3-toastify';

const props = defineProps({
    activeJobs: {
        type: Array,
        default: () => [],
    },
    failedJobs: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({ active_count: 0, failed_count: 0 }),
    },
});

const activeTab = ref('active');
const showExceptionModal = ref(false);
const selectedJob = ref(null);
const loading = ref(false);

const tabs = [
    { key: 'active', label: 'Active Jobs', count: computed(() => props.stats.active_count) },
    { key: 'failed', label: 'Failed Jobs', count: computed(() => props.stats.failed_count) },
];

const retryJob = (uuid) => {
    loading.value = true;
    router.post(route('queues.retry', uuid), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast('Job queued for retry', { type: 'success' });
        },
        onFinish: () => {
            loading.value = false;
        },
    });
};

const deleteJob = (uuid) => {
    if (!confirm('Are you sure you want to delete this failed job?')) return;
    
    loading.value = true;
    router.delete(route('queues.destroy', uuid), {
        preserveScroll: true,
        onSuccess: () => {
            toast('Job deleted', { type: 'success' });
        },
        onFinish: () => {
            loading.value = false;
        },
    });
};

const retryAll = () => {
    if (!confirm('Are you sure you want to retry ALL failed jobs?')) return;
    
    loading.value = true;
    router.post(route('queues.retry-all'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast('All jobs queued for retry', { type: 'success' });
        },
        onFinish: () => {
            loading.value = false;
        },
    });
};

const flushAll = () => {
    if (!confirm('Are you sure you want to DELETE ALL failed jobs? This cannot be undone.')) return;
    
    loading.value = true;
    router.post(route('queues.flush'), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast('All failed jobs deleted', { type: 'success' });
        },
        onFinish: () => {
            loading.value = false;
        },
    });
};

const showException = (job) => {
    selectedJob.value = job;
    showExceptionModal.value = true;
};

const closeModal = () => {
    showExceptionModal.value = false;
    selectedJob.value = null;
};

const refresh = () => {
    router.reload({ preserveScroll: true });
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Queue Manager</h1>
                    <p class="text-sm text-slate-500 mt-1">Monitor and manage background jobs</p>
                </div>
                <SecondaryButton @click="refresh" :disabled="loading">
                    <svg class="w-4 h-4 mr-2" :class="{ 'animate-spin': loading }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </SecondaryButton>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-6 border border-blue-100">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-100 rounded-xl">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-blue-600 font-medium">Active Jobs</p>
                            <p class="text-3xl font-bold text-blue-900">{{ stats.active_count }}</p>
                        </div>
                    </div>
                </div>
                <div class="bg-gradient-to-br from-rose-50 to-red-50 rounded-2xl p-6 border border-rose-100">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-rose-100 rounded-xl">
                            <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm text-rose-600 font-medium">Failed Jobs</p>
                            <p class="text-3xl font-bold text-rose-900">{{ stats.failed_count }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="border-b border-slate-200">
                <nav class="flex gap-4">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        @click="activeTab = tab.key"
                        class="px-4 py-3 text-sm font-medium border-b-2 transition-colors"
                        :class="activeTab === tab.key
                            ? 'border-primary text-primary'
                            : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                    >
                        {{ tab.label }}
                        <span
                            class="ml-2 px-2 py-0.5 text-xs rounded-full"
                            :class="activeTab === tab.key
                                ? 'bg-primary-50 text-primary'
                                : 'bg-slate-100 text-slate-600'"
                        >
                            {{ tab.count.value }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- Active Jobs Tab -->
            <div v-if="activeTab === 'active'" class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <!-- Mobile Card View -->
                <div class="block md:hidden divide-y divide-slate-100">
                    <div 
                        v-for="job in activeJobs" 
                        :key="'mobile-active-' + job.id"
                        class="p-4 space-y-2"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono text-slate-500">#{{ job.id }}</span>
                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded-lg">{{ job.queue }}</span>
                        </div>
                        <p class="font-medium text-slate-900 text-sm">{{ job.payload.shortName }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ job.payload.displayName }}</p>
                        <div class="flex justify-between text-xs text-slate-500 pt-1">
                            <span>Attempts: {{ job.attempts }}</span>
                            <span>{{ job.created_at }}</span>
                        </div>
                    </div>
                    <div v-if="activeJobs.length === 0" class="p-8 text-center">
                        <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                        </svg>
                        <p class="text-sm text-slate-500">No active jobs</p>
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Job</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Queue</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Attempts</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Available At</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-for="job in activeJobs" :key="job.id" class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 text-sm font-mono text-slate-600">{{ job.id }}</td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-medium text-slate-900">{{ job.payload.shortName }}</span>
                                    <p class="text-xs text-slate-500 truncate max-w-xs" :title="job.payload.displayName">
                                        {{ job.payload.displayName }}
                                    </p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded-lg">
                                        {{ job.queue }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ job.attempts }}</td>
                                <td class="px-6 py-4 text-sm text-slate-500">{{ job.available_at }}</td>
                                <td class="px-6 py-4 text-sm text-slate-500">{{ job.created_at }}</td>
                            </tr>
                            <tr v-if="activeJobs.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                    <svg class="w-12 h-12 mx-auto text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                    </svg>
                                    <p>No active jobs in the queue</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Failed Jobs Tab -->
            <div v-if="activeTab === 'failed'" class="space-y-4">
                <!-- Bulk Actions -->
                <div v-if="failedJobs.length > 0" class="flex gap-3">
                    <PrimaryButton @click="retryAll" :disabled="loading">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Retry All
                    </PrimaryButton>
                    <SecondaryButton @click="flushAll" :disabled="loading" class="text-rose-600 hover:text-rose-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Flush All
                    </SecondaryButton>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <!-- Mobile Card View -->
                    <div class="block md:hidden divide-y divide-slate-100">
                        <div 
                            v-for="job in failedJobs" 
                            :key="'mobile-failed-' + job.uuid"
                            class="p-4 space-y-3"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-mono text-slate-500">{{ job.uuid.substring(0, 8) }}...</span>
                                <span class="px-2 py-1 text-xs font-medium bg-rose-100 text-rose-700 rounded-lg">{{ job.queue }}</span>
                            </div>
                            <p class="font-medium text-slate-900 text-sm">{{ job.payload.shortName }}</p>
                            <button
                                @click="showException(job)"
                                class="text-xs text-slate-500 line-clamp-2 text-left"
                            >
                                {{ job.exception }}
                            </button>
                            <p class="text-xs text-slate-500">Failed: {{ job.failed_at_human }}</p>
                            <div class="flex gap-2">
                                <button
                                    @click="retryJob(job.uuid)"
                                    :disabled="loading"
                                    class="flex-1 py-2.5 text-center text-xs font-semibold text-blue-600 bg-blue-50 rounded-xl hover:bg-blue-100 transition-colors disabled:opacity-50"
                                >
                                    Retry
                                </button>
                                <button
                                    @click="deleteJob(job.uuid)"
                                    :disabled="loading"
                                    class="flex-1 py-2.5 text-center text-xs font-semibold text-rose-600 bg-rose-50 rounded-xl hover:bg-rose-100 transition-colors disabled:opacity-50"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                        <div v-if="failedJobs.length === 0" class="p-8 text-center">
                            <svg class="w-12 h-12 mx-auto text-emerald-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm text-emerald-600">No failed jobs!</p>
                        </div>
                    </div>

                    <!-- Desktop Table View -->
                    <div class="hidden md:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">UUID</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Job</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Queue</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Failed At</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Exception</th>
                                    <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="job in failedJobs" :key="job.uuid" class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="text-xs font-mono text-slate-500">{{ job.uuid.substring(0, 8) }}...</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm font-medium text-slate-900">{{ job.payload.shortName }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs font-medium bg-rose-100 text-rose-700 rounded-lg">
                                            {{ job.queue }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-500">{{ job.failed_at_human }}</td>
                                    <td class="px-6 py-4">
                                        <button
                                            @click="showException(job)"
                                            class="text-sm text-slate-600 hover:text-slate-900 text-left max-w-xs truncate block"
                                            :title="job.exception"
                                        >
                                            {{ job.exception }}
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <button
                                                @click="retryJob(job.uuid)"
                                                :disabled="loading"
                                                class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                                                title="Retry"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                            </button>
                                            <button
                                                @click="deleteJob(job.uuid)"
                                                :disabled="loading"
                                                class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                                title="Delete"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="failedJobs.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                        <svg class="w-12 h-12 mx-auto text-emerald-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="text-emerald-600">No failed jobs — everything is running smoothly!</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Exception Modal -->
        <Modal :show="showExceptionModal" @close="closeModal" max-width="4xl">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-slate-900 mb-4">Exception Details</h3>
                <div v-if="selectedJob" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-slate-500">Job:</span>
                            <span class="ml-2 font-medium">{{ selectedJob.payload.shortName }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500">UUID:</span>
                            <span class="ml-2 font-mono text-xs">{{ selectedJob.uuid }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500">Failed At:</span>
                            <span class="ml-2">{{ selectedJob.failed_at }}</span>
                        </div>
                        <div>
                            <span class="text-slate-500">Queue:</span>
                            <span class="ml-2">{{ selectedJob.queue }}</span>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-slate-500 mb-2">Stack Trace:</p>
                        <pre class="bg-slate-900 text-green-400 p-4 rounded-lg overflow-auto max-h-96 text-xs font-mono">{{ selectedJob.exception_full }}</pre>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="closeModal">Close</SecondaryButton>
                    <PrimaryButton v-if="selectedJob" @click="retryJob(selectedJob.uuid); closeModal()">
                        Retry Job
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
