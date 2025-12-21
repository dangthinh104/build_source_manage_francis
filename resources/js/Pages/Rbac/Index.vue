<template>
    <Head title="RBAC Permission Matrix" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Role-Based Access Control Matrix</h2>
        </template>

        <div class="space-y-6">
            <!-- Info Card -->
            <div class="btn-primary text-white px-6 py-4 rounded-2xl shadow-lg flex items-start gap-3">
                <svg class="h-6 w-6 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <p class="font-semibold">Permission Overview</p>
                    <p class="text-sm text-blue-100 mt-1">This matrix shows which permissions are assigned to each role. Green checkmarks indicate granted permissions, while gray X marks indicate denied permissions.</p>
                </div>
            </div>

            <!-- Permission Matrix Card -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-slate-50 to-slate-100 px-6 py-4 border-b border-slate-200">
                    <h3 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                        <svg class="h-5 w-5 shrink-0 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Permission Matrix
                        <span class="ml-auto text-sm font-normal text-slate-600">{{ permissions.length }} permissions</span>
                    </h3>
                </div>

                <!-- Mobile Card View -->
                <div class="block md:hidden divide-y divide-slate-100">
                    <!-- Super Admin Wildcard Card -->
                    <div v-if="hasSuperAdminWildcard" class="p-4 bg-amber-50 border-l-4 border-amber-400">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="h-10 w-10 shrink-0 rounded-full bg-amber-100 flex items-center justify-center">
                                <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-semibold text-slate-900 text-sm">Super Admin: Full Access</p>
                                <p class="text-xs text-slate-600">Wildcard (*) permission grants unrestricted access</p>
                            </div>
                        </div>
                    </div>

                    <!-- Permission Cards -->
                    <div v-for="permission in permissions" :key="'mobile-' + permission" class="p-4 space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 shrink-0 rounded-lg bg-primary-50 flex items-center justify-center">
                                <svg class="h-4 w-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <span class="text-sm font-semibold text-slate-900 font-mono">{{ permission }}</span>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-2">
                            <div v-for="role in roles" :key="role" class="flex flex-col items-center gap-1.5 p-2 rounded-lg border" :class="hasPermission(role, permission) ? 'bg-green-50 border-green-200' : 'bg-slate-50 border-slate-200'">
                                <div class="h-6 w-6 rounded-full flex items-center justify-center" :class="hasPermission(role, permission) ? 'bg-green-100' : 'bg-slate-100'">
                                    <svg v-if="hasPermission(role, permission)" class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <svg v-else class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </div>
                                <span class="text-[10px] font-medium text-slate-700 text-center">{{ formatRoleName(role) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State Mobile -->
                    <div v-if="permissions.length === 0" class="p-8 text-center">
                        <svg class="h-12 w-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <p class="text-sm font-medium text-slate-500">No permissions configured</p>
                        <p class="text-xs text-slate-400 mt-1">Run <code class="bg-slate-100 px-2 py-1 rounded text-xs">php artisan app:import-rights</code></p>
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50 sticky top-0 z-10">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider bg-slate-100">
                                    Permission
                                </th>
                                <th v-for="role in roles" :key="role" class="px-6 py-4 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                    <div class="flex flex-col items-center">
                                        <span>{{ formatRoleName(role) }}</span>
                                        <span class="text-[10px] font-normal text-slate-500 mt-0.5">{{ getRoleBadge(role) }}</span>
                                    </div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <!-- Super Admin Wildcard Row -->
                            <tr v-if="hasSuperAdminWildcard" class="bg-amber-50 border-l-4 border-amber-400">
                                <td colspan="4" class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-10 w-10 shrink-0 rounded-full bg-amber-100 flex items-center justify-center">
                                            <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">Super Admin: Full Access</p>
                                            <p class="text-sm text-slate-600">Super Admin has wildcard (*) permission granting unrestricted access to all system features.</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Permission Rows -->
                            <tr v-for="permission in permissions" :key="permission" class="hover:bg-slate-50 transition-colors duration-150">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 shrink-0 rounded-lg bg-indigo-100 flex items-center justify-center mr-3">
                                            <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                            </svg>
                                        </div>
                                        <span class="text-sm font-medium text-slate-900 font-mono">{{ permission }}</span>
                                    </div>
                                </td>
                                <td v-for="role in roles" :key="role" class="px-6 py-4 text-center">
                                    <div class="flex justify-center">
                                        <!-- Check if role has this permission -->
                                        <div v-if="hasPermission(role, permission)" class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-green-100">
                                            <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <div v-else class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-slate-100">
                                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="permissions.length === 0">
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-500">
                                        <svg class="h-12 w-12 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                        </svg>
                                        <p class="text-sm font-medium">No permissions configured</p>
                                        <p class="text-xs mt-1">Run <code class="bg-slate-100 px-2 py-1 rounded text-xs">php artisan app:import-rights</code> to import permissions</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Legend Card -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h4 class="text-sm font-semibold text-slate-900 mb-4">Legend</h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                            <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-900">Granted</p>
                            <p class="text-xs text-slate-600">Permission is assigned</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-full bg-slate-100 flex items-center justify-center shrink-0">
                            <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-900">Denied</p>
                            <p class="text-xs text-slate-600">Permission not assigned</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="h-8 w-8 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                            <svg class="h-5 w-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-900">Wildcard</p>
                            <p class="text-xs text-slate-600">Full system access</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    permissions: {
        type: Array,
        required: true,
    },
    matrix: {
        type: Object,
        required: true,
    },
    roles: {
        type: Array,
        required: true,
    },
});

// Check if super_admin has wildcard permission
const hasSuperAdminWildcard = computed(() => {
    return props.matrix.super_admin && props.matrix.super_admin.includes('*');
});

// Check if a role has a specific permission
const hasPermission = (role, permission) => {
    const rolePerms = props.matrix[role] || [];
    
    // If role has wildcard, grant all permissions
    if (rolePerms.includes('*')) {
        return true;
    }
    
    return rolePerms.includes(permission);
};

// Format role name for display
const formatRoleName = (role) => {
    return role
        .split('_')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
};

// Get role badge text
const getRoleBadge = (role) => {
    const badges = {
        user: 'Basic',
        admin: 'Elevated',
        super_admin: 'Full Access',
    };
    return badges[role] || role;
};
</script>

<style scoped>
/* Tailwind classes used inline */
</style>
