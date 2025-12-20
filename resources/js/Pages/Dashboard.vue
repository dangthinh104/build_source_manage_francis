<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { Bar } from 'vue-chartjs';
import { Chart, BarElement, CategoryScale, LinearScale, Tooltip, Legend } from 'chart.js';

Chart.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend);

import { computed, toRefs } from 'vue';

const props = defineProps({
    totalSites: Number,
    totalBuilds: Number,
    successRate: Number,
    failedCount: Number,
    todayBuilds: Number,
    yesterdayBuilds: Number,
    buildsTrend: Number,
    avgDuration: String,
    topBuilders: Array,
    recentBuilds: Array,
    buildsLast7: Array,
    siteHealth: Array,
    problemSites: Array,
});

const { totalSites, totalBuilds, successRate, failedCount, todayBuilds, buildsTrend, avgDuration, topBuilders, recentBuilds, buildsLast7, siteHealth, problemSites } = toRefs(props);

// Stacked chart data for success/failed
const chartData = computed(() => {
    const labels = (buildsLast7.value || []).map((d) => d.label);
    const successData = (buildsLast7.value || []).map((d) => d.success);
    const failedData = (buildsLast7.value || []).map((d) => d.failed);
    return {
        labels,
        datasets: [
            {
                label: 'Success',
                backgroundColor: '#10b981',
                data: successData,
            },
            {
                label: 'Failed',
                backgroundColor: '#ef4444',
                data: failedData,
            },
        ],
    };
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: true, position: 'top' },
        tooltip: { enabled: true },
    },
    scales: {
        x: { stacked: true, grid: { display: false } },
        y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1 } },
    },
};

// Trend arrow and color
const trendIcon = computed(() => buildsTrend.value >= 0 ? '↑' : '↓');
const trendColor = computed(() => buildsTrend.value >= 0 ? 'text-emerald-600' : 'text-rose-600');
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Dashboard</h2>
        </template>

        <div class="py-6">
            <div class="max-w-10xl mx-auto space-y-6">
                <!-- Summary Cards Row 1 -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
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
                        <p class="text-sm text-emerald-600 mt-1">{{ totalBuilds - failedCount }} successful</p>
                    </div>
                    <div class="rounded-2xl bg-white p-6 shadow">
                        <p class="text-sm text-slate-500">Today's Builds</p>
                        <div class="flex items-baseline gap-2">
                            <h3 class="text-2xl font-bold text-slate-900">{{ todayBuilds }}</h3>
                            <span v-if="buildsTrend !== 0" :class="trendColor" class="text-sm font-semibold">
                                {{ trendIcon }} {{ Math.abs(buildsTrend) }}%
                            </span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">vs yesterday</p>
                    </div>
                </div>

                <!-- Summary Cards Row 2 -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="rounded-2xl bg-white p-6 shadow">
                        <p class="text-sm text-slate-500">Average Build Duration</p>
                        <h3 class="text-2xl font-bold text-slate-900">{{ avgDuration || '—' }}</h3>
                    </div>
                    <div class="rounded-2xl bg-white p-6 shadow">
                        <p class="text-sm text-slate-500">Failed Today</p>
                        <h3 class="text-2xl font-bold text-rose-600">{{ failedCount }}</h3>
                    </div>
                    <div class="rounded-2xl bg-white p-6 shadow">
                        <p class="text-sm text-slate-500">Problem Sites</p>
                        <div v-if="problemSites && problemSites.length > 0" class="mt-2 space-y-1">
                            <div v-for="site in problemSites" :key="site.id" class="flex justify-between text-sm">
                                <Link :href="route('my_site.show', site.id)" class="text-primary hover:underline truncate">{{ site.site_name }}</Link>
                                <span class="text-rose-600 font-semibold">{{ site.fail_count }} fails</span>
                            </div>
                        </div>
                        <p v-else class="text-sm text-slate-500 mt-2">No failed builds 🎉</p>
                    </div>
                </div>

                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column: Chart + Recent Builds -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Build Trend Chart -->
                        <div class="rounded-2xl bg-white p-6 shadow">
                            <h4 class="text-lg font-semibold mb-4">Build Trend (Last 7 Days)</h4>
                            <div class="w-full h-48 bg-slate-50 rounded-lg p-4">
                                <div v-if="buildsLast7 && buildsLast7.length > 0 && chartData.labels && chartData.labels.length > 0" class="h-full">
                                    <Bar :data="chartData" :options="chartOptions" />
                                </div>
                                <div v-else class="flex items-center justify-center h-full text-sm text-slate-500">No builds in the last 7 days</div>
                            </div>
                        </div>

                        <!-- Recent Builds Table -->
                        <div class="rounded-2xl bg-white p-6 shadow">
                            <h4 class="text-lg font-semibold mb-4">Recent Builds</h4>
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
                    </div>

                    <!-- Right Column: Site Health + Top Builders -->
                    <div class="space-y-6">
                        <!-- Site Health -->
                        <div class="rounded-2xl bg-white p-6 shadow">
                            <h4 class="text-lg font-semibold mb-4">Site Health</h4>
                            <div v-if="siteHealth && siteHealth.length > 0" class="space-y-3">
                                <div v-for="site in siteHealth" :key="site.id" class="flex items-center justify-between">
                                    <div class="flex-1 min-w-0">
                                        <Link :href="route('my_site.show', site.id)" class="text-sm font-medium text-primary hover:underline truncate block">{{ site.site_name }}</Link>
                                        <p class="text-xs text-slate-500">{{ site.total_builds }} builds · {{ site.last_build_ago }}</p>
                                    </div>
                                    <div class="ml-3">
                                        <span 
                                            :class="site.success_rate >= 80 ? 'bg-emerald-100 text-emerald-700' : site.success_rate >= 50 ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700'"
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold"
                                        >
                                            {{ site.success_rate }}%
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <p v-else class="text-sm text-slate-500">No site data available</p>
                        </div>

                        <!-- Top Builders -->
                        <div class="rounded-2xl bg-white p-6 shadow">
                            <h4 class="text-lg font-semibold mb-4">Top Builders</h4>
                            <ul v-if="topBuilders && topBuilders.length > 0" class="space-y-3">
                                <li v-for="(u, idx) in topBuilders" :key="u.id" class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <span class="w-6 h-6 rounded-full bg-primary-100 text-primary-700 text-xs font-bold flex items-center justify-center">{{ idx + 1 }}</span>
                                        <div class="text-sm font-medium">{{ u.name }}</div>
                                    </div>
                                    <div class="text-xs text-slate-500">{{ u.builds_count }} builds</div>
                                </li>
                            </ul>
                            <p v-else class="text-sm text-slate-500">No builder data</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

