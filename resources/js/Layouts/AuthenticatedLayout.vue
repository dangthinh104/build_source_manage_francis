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
const preferences = computed(() => page.props.preferences || {
    theme_color: 'indigo',
    content_width: 'wide',
    sidebar_style: 'gradient',
    compact_mode: false
});

// Theme color classes
const themeColors = computed(() => {
    const color = preferences.value.theme_color || 'indigo';
    return {
        primary: `${color}-600`,
        primaryHover: `${color}-500`,
        primaryLight: `${color}-50`,
        primaryText: `${color}-600`,
        primaryShadow: `${color}-500/30`,
        primaryShadowHover: `${color}-500/40`,
        primaryRing: `${color}-400/20`,
    };
});

// Content width classes
const contentWidthClass = computed(() => {
    const width = preferences.value.content_width || 'wide';
    return {
        'default': 'max-w-6xl lg:max-w-7xl 2xl:max-w-6xl',
        'wide': 'max-w-7xl lg:max-w-[90rem] 2xl:max-w-[100rem]',
        'full': 'max-w-full'
    }[width];
});

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
        {
            key: 'parameters',
            label: 'Parameters',
            routeName: 'parameters.index',
            patterns: ['parameters.*'],
            adminOnly: true,
            iconPath: ['M3 7h18', 'M3 12h18', 'M3 17h18'],
        },
        {
            key: 'settings',
            label: 'Settings',
            routeName: 'profile.edit',
            patterns: ['profile.*'],
            iconPath: ['M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', 'M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
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
                class="hidden md:flex flex-col bg-gradient-to-b from-slate-900 via-slate-900 to-slate-800 text-white shadow-2xl shadow-slate-900/50 transition-all duration-300 relative"
                :class="desktopSidebarCollapsed ? 'w-20' : 'w-72'"
            >
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/10 via-transparent to-transparent pointer-events-none"></div>
                <div class="flex items-center justify-between px-5 py-6 border-b border-white/10 relative z-10">
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
                        class="text-white/60 hover:text-white transition-all duration-300"
                        @click="toggleDesktopSidebar"
                        aria-label="Toggle sidebar width"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 12h16M4 6h10M10 18h10" />
                        </svg>
                    </button>
                            </div>

                <nav class="flex-1 overflow-y-auto px-3 py-6 space-y-1 relative z-10 custom-scrollbar">
                    <Link
                        v-for="item in navItems"
                        :key="item.key"
                        :href="route(item.routeName)"
                        class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition-all duration-300"
                        :class="[
                            isActive(item)
                                ? 'bg-gradient-to-r from-indigo-500 to-indigo-600 text-white shadow-lg shadow-indigo-500/40 ring-2 ring-indigo-400/20'
                                : 'text-slate-300 hover:bg-white/10 hover:text-white hover:shadow-md',
                            desktopSidebarCollapsed ? 'justify-center px-0' : '',
                        ]"
                    >
                        <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                            <path
                                v-for="(segment, index) in item.iconPath"
                                :key="`${item.key}-${index}`"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                :d="segment"
                            />
                        </svg>
                        <span v-if="!desktopSidebarCollapsed">{{ item.label }}</span>
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
                class="fixed inset-y-0 left-0 z-50 w-72 bg-gradient-to-b from-slate-900 via-slate-900 to-slate-800 text-white shadow-2xl shadow-slate-900/80 transform transition-transform duration-300 md:hidden"
                :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
                role="dialog"
                aria-modal="true"
            >
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/10 via-transparent to-transparent pointer-events-none"></div>
                <div class="flex flex-col h-full relative z-10">
                    <div class="flex items-center justify-between px-5 py-6 border-b border-white/10">
                        <div class="flex items-center gap-3">
                            <ApplicationLogo class="h-10 w-auto text-white" />
                            <span class="font-semibold tracking-wide uppercase text-sm text-slate-100">
                                Francis Manage
                            </span>
                        </div>
                        <button
                            type="button"
                            class="text-white/70 hover:text-white transition-all duration-300"
                            @click="closeMobileSidebar"
                            aria-label="Close navigation"
                        >
                            <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-1 custom-scrollbar">
                        <Link
                            v-for="item in navItems"
                            :key="`mobile-${item.key}`"
                            :href="route(item.routeName)"
                            class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium transition-all duration-300"
                            :class="
                                isActive(item)
                                    ? 'bg-gradient-to-r from-indigo-500 to-indigo-600 text-white shadow-lg shadow-indigo-500/40 ring-2 ring-indigo-400/20'
                                    : 'text-slate-300 hover:bg-white/10 hover:text-white hover:shadow-md'
                            "
                            @click="closeMobileSidebar"
                        >
                            <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
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
                <header class="bg-white/95 backdrop-blur-xl border-b border-slate-200/80 sticky top-0 z-30 shadow-sm shadow-slate-200/50">
                    <div class="flex items-center justify-between px-4 sm:px-6 lg:px-10 2xl:px-16 py-4">
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                class="inline-flex md:hidden items-center justify-center rounded-xl border border-slate-200 bg-white p-2 text-slate-600 shadow-sm transition-all duration-300 hover:bg-slate-50 hover:border-slate-300"
                                @click="openMobileSidebar"
                                aria-label="Open navigation"
                                aria-expanded="mobileSidebarOpen"
                            >
                                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
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
                                    class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 shadow-sm hover:text-slate-900 transition-all duration-300 hover:bg-slate-50 hover:border-indigo-300 hover:shadow-md hover:shadow-indigo-100/50"
                                            >
                                    <span class="hidden sm:inline">{{ user.email }}</span>
                                                <svg
                                        class="h-4 w-4 text-slate-400 transition-transform duration-300 group-hover:rotate-180"
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

                <main class="flex-1 bg-gradient-to-br from-slate-50 via-slate-100 to-slate-50">
                    <div class="mx-auto w-full px-4 sm:px-6 lg:px-10 2xl:px-16 py-6 sm:py-8 lg:py-10 2xl:py-12 space-y-6" :class="contentWidthClass">
                        <div class="rounded-3xl bg-white/90 backdrop-blur-sm shadow-xl shadow-slate-200/70 ring-1 ring-slate-200/50 transition-all duration-300 hover:shadow-2xl hover:shadow-slate-200/80">
                            <div
                                v-if="$slots.header"
                                class="border-b border-slate-100 bg-gradient-to-br from-slate-50/80 to-white px-6 sm:px-8 lg:px-10 py-6 rounded-t-3xl"
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
