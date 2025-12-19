<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Bar } from 'vue-chartjs';
import { Chart, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';

Chart.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend);

import { computed, toRefs } from 'vue';

const props = defineProps({
    totalSites: Number,
    totalBuilds: Number,
    successRate: Number,
    failedCount: Number,
    topBuilders: Array,
    recentBuilds: Array,
    buildsLast7: Array,
});

const { totalSites, totalBuilds, successRate, failedCount, topBuilders, recentBuilds, buildsLast7 } = toRefs(props);

const chartData = computed(() => {
    const labels = (buildsLast7.value || []).map((d) => d.label);
    const data = (buildsLast7.value || []).map((d) => d.count);
    return {
        labels,
        datasets: [
            {
                label: 'Builds',
                backgroundColor: 'rgb(var(--color-primary))',
                data,
            },
        ],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: { enabled: true },
    },
    scales: {
        x: { grid: { display: false } },
        y: { beginAtZero: true, ticks: { stepSize: 1 } },
    },
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Dashboard</h2>
        </template>


        <div class="py-6">
            <div class="max-w-10xl mx-auto space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="rounded-2xl bg-white p-6 shadow">
                        <p class="text-sm text-slate-500">Total Sites</p>
                        <h3 class="text-2xl font-bold text-slate-900">{{ totalSites }}</h3>
                    </div>
                    <div class="rounded-2xl bg-white p-6 shadow">
                        <p class="text-sm text-slate-500">Total Builds</p>
                        <h3 class="text-2xl font-bold text-slate-900">{{ totalBuilds }}</h3>
                    </div>
                    <div class="rounded-2xl bg-white p-6 shadow">
                        <p class="text-sm text-slate-500">Success Rate</p>
                        <h3 class="text-2xl font-bold text-slate-900">{{ successRate }}%</h3>
                        <p class="text-sm text-green-600 mt-1">{{ totalBuilds - failedCount }} successful</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="lg:col-span-2 rounded-2xl bg-white p-6 shadow">
                        <h4 class="text-lg font-semibold mb-4">Recent Builds</h4>
                        <div class="mb-6">
                            <p class="text-sm text-slate-500 mb-2">Builds over last 7 days</p>
                            <div class="w-full h-40 bg-slate-50 rounded-lg p-4">
                                <div v-if="buildsLast7 && buildsLast7.length > 0">
                                    <Bar :chart-data="chartData" :chart-options="chartOptions" />
                                </div>
                                <div v-else class="flex items-center justify-center h-full text-sm text-slate-500">No builds in the last 7 days</div>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm text-slate-600">
                                <thead class="text-xs uppercase text-slate-500">
                                    <tr>
                                        <th class="px-4 py-2">Site</th>
                                        <th class="px-4 py-2">Builder</th>
                                        <th class="px-4 py-2">Status</th>
                                        <th class="px-4 py-2">Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="b in recentBuilds" :key="b.id" class="border-t">
                                        <td class="px-4 py-3">{{ b.site_name }}</td>
                                        <td class="px-4 py-3">{{ b.user_name }}</td>
                                        <td class="px-4 py-3">
                                            <span :class="b.status === 'success' ? 'inline-flex items-center px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 text-xs' : 'inline-flex items-center px-3 py-1 rounded-full bg-rose-50 text-rose-600 text-xs'">{{ b.status }}</span>
                                        </td>
                                        <td class="px-4 py-3">{{ b.created_at }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="rounded-2xl bg-white p-6 shadow">
                        <h4 class="text-lg font-semibold mb-4">Top Builders</h4>
                        <ul class="space-y-3">
                            <li v-for="u in topBuilders" :key="u.id" class="flex items-center justify-between">
                                <div>
                                    <div class="text-sm font-medium">{{ u.name }}</div>
                                    <div class="text-xs text-slate-500">{{ u.builds_count }} builds</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
