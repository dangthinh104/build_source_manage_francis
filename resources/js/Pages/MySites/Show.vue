<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    site: Object,
    buildHistories: Array,
});

const viewingLog = ref(false);
const currentLog = ref({ output_log: '', created_at: '', status: '' });

const viewLog = (history) => {
    currentLog.value = history;
    viewingLog.value = true;
};

const closeLog = () => {
    viewingLog.value = false;
};
</script>

<template>
    <Head :title="`Build History - ${site.site_name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-slate-800">Build History</h2>
                    <p class="text-sm text-slate-600">{{ site.site_name }}</p>
                </div>
                <Link :href="route('my_site.index')" class="text-sm text-primary hover:underline">
                    ← Back to Sites
                </Link>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto">
                <div class="rounded-3xl bg-white shadow-2xl ring-1 ring-slate-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h3 class="text-lg font-semibold text-slate-900">Build Records</h3>
                        <p class="text-sm text-slate-500">{{ buildHistories.length }} total builds</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-6 py-3">Build ID</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">Builder</th>
                                    <th class="px-6 py-3">Build Date</th>
                                    <th class="px-6 py-3">Duration</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-if="buildHistories.length === 0">
                                    <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                        No build history yet.
                                    </td>
                                </tr>
                                <tr
                                    v-for="(history, index) in buildHistories"
                                    :key="history.id"
                                    :class="index % 2 === 0 ? 'bg-white' : 'bg-slate-50/60'"
                                    class="transition hover:bg-primary-50"
                                >
                                    <td class="px-6 py-4 font-semibold text-slate-800">#{{ history.id }}</td>
                                    <td class="px-6 py-4">
                                        <span
                                            :class="{
                                                'bg-emerald-50 text-emerald-600': history.status === 'success',
                                                'bg-rose-50 text-rose-600': history.status === 'failed',
                                                'bg-amber-50 text-amber-600': history.status === 'building'
                                            }"
                                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                        >
                                            {{ history.status || 'unknown' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="font-medium text-slate-700">{{ history.user?.name || 'System' }}</p>
                                        <p class="text-xs text-slate-500">{{ history.user?.email || '—' }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="font-medium text-slate-700">{{ new Date(history.created_at).toLocaleDateString() }}</p>
                                        <p class="text-xs text-slate-500">{{ new Date(history.created_at).toLocaleTimeString() }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-slate-500">
                                        {{ history.duration || '—' }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <SecondaryButton
                                            class="text-xs"
                                            @click="viewLog(history)"
                                        >
                                            View Log
                                        </SecondaryButton>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="viewingLog" @close="closeLog" maxWidth="3xl">
            <div class="p-6 space-y-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Build Log #{{ currentLog.id }}</h2>
                    <p class="text-sm text-slate-600">
                        Status: <span
                            :class="{
                                'text-emerald-600': currentLog.status === 'success',
                                'text-rose-600': currentLog.status === 'failed',
                                'text-amber-600': currentLog.status === 'building'
                            }"
                            class="font-semibold"
                        >{{ currentLog.status }}</span>
                        · {{ new Date(currentLog.created_at).toLocaleString() }}
                    </p>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-slate-700 mb-2">Output</h4>
                    <pre class="bg-gray-900 text-green-400 font-mono p-4 rounded-md overflow-x-auto whitespace-pre-wrap text-sm max-h-96">{{ currentLog.output_log || 'No log available' }}</pre>
                </div>
                <div class="flex justify-end">
                    <SecondaryButton @click="closeLog">Close</SecondaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
