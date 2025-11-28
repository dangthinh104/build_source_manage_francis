<script setup>
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

const featureCards = [
    {
        title: 'Centralized Parameters',
        description: 'Manage infrastructure secrets, build flags, and PM2 ports from a single source of truth before every deployment.',
        icon: 'M4 7h16M4 12h10M4 17h7',
    },
    {
        title: 'Observability-Ready',
        description: 'Inspect logs, build history, and contributor activity without leaving the dashboard, keeping incidents transparent.',
        icon: 'M5 13l4 4L19 7',
    },
    {
        title: 'Investor Insights',
        description: 'Track room performance, contributor profits, and billing pulses so partners see returns in real time.',
        icon: 'M3 10h4l3 8 4-16 3 8h4',
    },
];
</script>

<template>
    <Head title="Welcome" />
    <div class="min-h-screen bg-slate-50 text-slate-700">
        <div class="mx-auto flex min-h-screen w-full max-w-6xl flex-col px-6 py-10 lg:py-16">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-primary">FRANCIS BUILD MANAGE</p>
                    <h1 class="text-2xl font-bold text-slate-900">Operate every rental house from one glass pane.</h1>
                </div>

                <div v-if="props.canLogin" class="flex flex-wrap gap-3">
                    <template v-if="$page.props.auth?.user">
                        <Link :href="route('dashboard')" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Dashboard</Link>
                    </template>
                    <template v-else>
                        <Link :href="route('login')" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white">Log in</Link>
                        <Link
                            v-if="props.canRegister"
                            :href="route('register')"
                            class="rounded-full btn-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:btn-primary-overlay"
                        >
                            Get started
                        </Link>
                    </template>
                </div>
            </header>

            <section class="mt-16 grid gap-12 lg:grid-cols-2 lg:items-center">
                <div class="space-y-6">
                    <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Fast handover. Confident builds.</p>
                    <h2 class="text-4xl font-semibold text-slate-900">
                        Build, monitor, and settle every sub-rental contract without tab fatigue.
                    </h2>
                    <p class="text-lg text-slate-600">
                        Francis centralizes houses, rooms, utilities, and contributor payouts. Ship updates with one click,
                        collect payments with clarity, and keep landlords, tenants, and investors in sync.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <Link
                            v-if="$page.props.auth?.user"
                            :href="route('dashboard')"
                            class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-900/30"
                        >
                            Go to dashboard
                        </Link>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-slate-900/30"
                            >
                                Log in to manage houses
                            </Link>
                            <Link
                                v-if="props.canRegister"
                                :href="route('register')"
                                class="rounded-2xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-white"
                            >
                                Create an account
                            </Link>
                        </template>
                    </div>
                </div>

                <div class="rounded-3xl p-8 text-white shadow-2xl" style="background: linear-gradient(90deg, rgb(var(--color-primary)), rgb(var(--color-primary-500))); box-shadow: 0 30px 60px rgb(var(--color-primary) / 0.25);">
                    <p class="text-sm uppercase tracking-[0.3em] text-white/80">Realtime overview</p>
                    <div class="mt-8 space-y-6">
                        <div class="rounded-2xl bg-white/15 p-4">
                            <p class="text-sm text-white/70">Active houses</p>
                            <p class="text-3xl font-semibold">40+</p>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="rounded-2xl bg-white/15 p-4">
                                <p class="text-sm text-white/70">Monthly bills</p>
                                <p class="text-2xl font-semibold">$32k</p>
                            </div>
                            <div class="rounded-2xl bg-white/15 p-4">
                                <p class="text-sm text-white/70">Investors</p>
                                <p class="text-2xl font-semibold">18</p>
                            </div>
                        </div>
                        <p class="text-sm text-white/80">
                            Laravel v{{ props.laravelVersion }}  PHP v{{ props.phpVersion }}  Resilient by design
                        </p>
                    </div>
                </div>
            </section>

            <section class="mt-16 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <article
                    v-for="card in featureCards"
                    :key="card.title"
                    class="rounded-2xl bg-white p-6 shadow-lg shadow-slate-200/70 ring-1 ring-slate-100"
                >
                    <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 text-primary">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                            <path :d="card.icon" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-900">{{ card.title }}</h3>
                    <p class="mt-2 text-sm text-slate-600">{{ card.description }}</p>
                </article>
            </section>

            <footer class="mt-16 border-t border-slate-200 pt-8 text-center text-sm text-slate-500">
                Built with Laravel v{{ props.laravelVersion }} and PHP v{{ props.phpVersion }}  optimized for house & contributor ops.
            </footer>
        </div>
    </div>
</template>
