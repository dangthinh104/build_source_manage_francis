<script setup>
import { computed, ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const desktopSidebarCollapsed = ref(false);
const mobileSidebarOpen = ref(false);

const user = computed(() => page.props.auth?.user ?? { name: 'User', email: '' });
const isAdmin = computed(() => user.value?.role === 'Admin');

const navItems = computed(() => {
    const items = [
        {
            key: 'dashboard',
            label: 'Dashboard',
            routeName: 'dashboard',
            patterns: ['dashboard'],
            adminOnly: true,
            iconPath: ['M4 6h6v6H4z', 'M14 6h6v6h-6z', 'M4 16h6v6H4z', 'M14 16h6v6h-6z'],
        },
        {
            key: 'logs',
            label: 'Log PM2',
            routeName: 'logs.index',
            patterns: ['logs.*'],
            iconPath: ['M4 6h16', 'M4 12h16', 'M4 18h10'],
        },
        {
            key: 'users',
            label: 'Users',
            routeName: 'users.index',
            patterns: ['users.*'],
            adminOnly: true,
            iconPath: ['M17 20h5v-2a4 4 0 00-4-4h-1', 'M7 20H2v-2a4 4 0 014-4h1', 'M12 12a4 4 0 100-8 4 4 0 000 8z'],
        },
        {
            key: 'env',
            label: 'ENV Variables',
            routeName: 'envVariables.index',
            patterns: ['envVariables.*'],
            iconPath: ['M12 2a10 10 0 00-9.95 9.05', 'M12 2v10l4 2', 'M2 12a10 10 0 0010 10'],
        },
    ];

    return items.filter((item) => (item.adminOnly ? isAdmin.value : true));
});

const isActive = (item) => {
    const patterns = item.patterns ?? [item.routeName];
    return patterns.some((pattern) => route().current(pattern));
};

const toggleDesktopSidebar = () => {
    desktopSidebarCollapsed.value = !desktopSidebarCollapsed.value;
};

const openMobileSidebar = () => {
    mobileSidebarOpen.value = true;
};

const closeMobileSidebar = () => {
    mobileSidebarOpen.value = false;
};
</script>

<template>
    <div class="min-h-screen bg-slate-100 text-slate-900" @keydown.esc.window="closeMobileSidebar">
        <div class="flex min-h-screen">
            <aside
                class="hidden md:flex flex-col bg-slate-900 text-white shadow-xl shadow-slate-900/40 transition-all duration-300"
                :class="desktopSidebarCollapsed ? 'w-20' : 'w-72'"
            >
                <div class="flex items-center justify-between px-5 py-6 border-b border-white/10">
                    <Link
                        :href="isAdmin ? route('dashboard') : route('logs.index')"
                        class="flex items-center gap-3"
                    >
                        <ApplicationLogo class="h-10 w-auto text-white" />
                        <span
                            v-if="!desktopSidebarCollapsed"
                            class="font-semibold tracking-wide uppercase text-sm text-slate-100"
                        >
                            Francis Manage
                        </span>
                                </Link>

                    <button
                        type="button"
                        class="text-white/60 hover:text-white transition"
                        @click="toggleDesktopSidebar"
                        aria-label="Toggle sidebar width"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16M4 6h10M10 18h10" />
                        </svg>
                    </button>
                            </div>

                <nav class="flex-1 overflow-y-auto px-3 py-6 space-y-1">
                    <Link
                        v-for="item in navItems"
                        :key="item.key"
                        :href="route(item.routeName)"
                        class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition"
                        :class="[
                            isActive(item)
                                ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-900/40'
                                : 'text-slate-300 hover:bg-white/10 hover:text-white',
                            desktopSidebarCollapsed ? 'justify-center px-0' : '',
                        ]"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                            <path
                                v-for="(segment, index) in item.iconPath"
                                :key="`${item.key}-${index}`"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                :d="segment"
                            />
                        </svg>
                        <span v-if="!sidebarCollapsed">{{ item.label }}</span>
                                </Link>
                </nav>
                <div class="px-5 py-6 border-t border-white/10 text-xs text-slate-400">
                    <p v-if="!desktopSidebarCollapsed">Signed in as</p>
                    <p class="font-semibold text-slate-100 truncate">{{ user.name }}</p>
                            </div>
            </aside>

            <transition name="fade">
                <div
                    v-if="mobileSidebarOpen"
                    class="fixed inset-0 bg-slate-900/60 z-40 md:hidden"
                    @click="closeMobileSidebar"
                    aria-hidden="true"
                />
            </transition>
            <div
                class="fixed inset-y-0 left-0 z-50 w-72 bg-slate-900 text-white shadow-2xl shadow-slate-900/60 transform transition-transform duration-300 md:hidden"
                :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                role="dialog"
                aria-modal="true"
            >
                <div class="flex flex-col h-full">
                    <div class="flex items-center justify-between px-5 py-6 border-b border-white/10">
                        <div class="flex items-center gap-3">
                            <ApplicationLogo class="h-10 w-auto text-white" />
                            <span class="font-semibold tracking-wide uppercase text-sm text-slate-100">
                                Francis Manage
                            </span>
                        </div>
                        <button
                            type="button"
                            class="text-white/70 hover:text-white transition"
                            @click="closeMobileSidebar"
                            aria-label="Close navigation"
                        >
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-1">
                        <Link
                            v-for="item in navItems"
                            :key="`mobile-${item.key}`"
                            :href="route(item.routeName)"
                            class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition"
                            :class="
                                isActive(item)
                                    ? 'bg-indigo-500 text-white shadow-lg shadow-indigo-900/40'
                                    : 'text-slate-300 hover:bg-white/10 hover:text-white'
                            "
                            @click="closeMobileSidebar"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                <path
                                    v-for="(segment, index) in item.iconPath"
                                    :key="`mobile-${item.key}-${index}`"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    :d="segment"
                                />
                            </svg>
                            <span>{{ item.label }}</span>
                        </Link>
                    </nav>
                    <div class="px-5 py-6 border-t border-white/10 text-xs text-slate-400">
                        <p class="font-semibold text-slate-100 truncate">{{ user.name }}</p>
                        <p class="text-slate-300 text-xs truncate">{{ user.email }}</p>
                            </div>
                            </div>
                            </div>

            <div class="flex-1 flex flex-col min-h-screen">
                <header class="bg-white/80 backdrop-blur border-b border-slate-200">
                    <div class="flex items-center justify-between px-4 sm:px-6 lg:px-10 2xl:px-16 py-4">
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                class="inline-flex md:hidden items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-600 shadow-sm"
                                @click="openMobileSidebar"
                                aria-label="Open navigation"
                                aria-expanded="mobileSidebarOpen"
                            >
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h10M4 18h8" />
                                </svg>
                            </button>
                            <div>
                                <p class="text-sm text-slate-500">Welcome back</p>
                                <p class="font-semibold text-slate-900">{{ user.name }}</p>
                            </div>
                        </div>
                        <Dropdown align="right" width="56">
                                    <template #trigger>
                                            <button
                                                type="button"
                                    class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 shadow-sm hover:text-slate-900"
                                            >
                                    <span class="hidden sm:inline">{{ user.email }}</span>
                                                <svg
                                        class="h-4 w-4 text-slate-400"
                                                    xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                                >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10l4 4 4-4" />
                                                </svg>
                                            </button>
                                    </template>
                                    <template #content>
                                <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                                        <DropdownLink :href="route('logout')" method="post" as="button">
                                            Log Out
                                        </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                </header>

                <main class="flex-1 bg-slate-100/60">
                    <div class="mx-auto w-full max-w-6xl lg:max-w-7xl 2xl:max-w-6xl px-4 sm:px-6 lg:px-10 2xl:px-16 py-8 lg:py-10 2xl:py-12 space-y-6">
                        <div class="rounded-3xl bg-white shadow-xl shadow-slate-200/70 ring-1 ring-slate-100">
                            <div
                                v-if="$slots.header"
                                class="border-b border-slate-100 bg-slate-50/60 px-6 sm:px-8 lg:px-10 py-6 rounded-t-3xl"
                            >
                                <slot name="header" />
                            </div>
                            <div class="px-6 sm:px-8 lg:px-10 py-8 lg:py-10">
                                <slot />
                        </div>
                        </div>
                    </div>
                </main>
                </div>
        </div>
    </div>
</template>
