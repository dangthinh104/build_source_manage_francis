
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import axios from 'axios';
import InputError from "@/Components/InputError.vue";
import { toast } from 'vue3-toastify';

const page = usePage();
const props = defineProps({
    users: Array,
    can_manage_users: Boolean,
});

const successMessage = ref('');
const processingTwoFactor = ref({});
const showConfirmModal = ref(false);
const confirmModalData = ref({
    title: '',
    message: '',
    confirmText: '',
    confirmClass: '',
    onConfirm: null,
});

// Check if current user is super admin
const isSuperAdmin = computed(() => {
    const role = page.props.auth?.user?.role;
    return role === 'super_admin';
});

// Check if current user is admin (but not super admin)
const isAdmin = computed(() => {
    const role = page.props.auth?.user?.role;
    return role === 'admin';
});

// Check if user can be edited/deleted by current user
const canManageUser = (targetUser) => {
    // Super admin can manage everyone
    if (isSuperAdmin.value) {
        return true;
    }

    // Admin can only manage regular users, not other admins or super admins
    if (isAdmin.value) {
        return targetUser.role === 'user';
    }

    return false;
};

// Helper function to check if user has 2FA enabled
const has2FAEnabled = (user) => {
    return user.two_factor_confirmed_at !== null && user.two_factor_confirmed_at !== undefined;
};

// Show confirmation modal
const showConfirmation = (options) => {
    confirmModalData.value = options;
    showConfirmModal.value = true;
};

const closeConfirmModal = () => {
    showConfirmModal.value = false;
};

const handleConfirm = () => {
    if (confirmModalData.value.onConfirm) {
        confirmModalData.value.onConfirm();
    }
    closeConfirmModal();
};

// Delete user with confirmation
const confirmDelete = (userId, userName) => {
    showConfirmation({
        title: 'Delete User',
        message: `Are you sure you want to delete "${userName}"? This action cannot be undone.`,
        confirmText: 'Delete',
        confirmClass: 'bg-red-600 hover:bg-red-700',
        onConfirm: () => deleteUser(userId),
    });
};

// Delete user with axios
const deleteUser = async (userId) => {
    try {
        const response = await axios.post(route('users.destroy', userId));

        if (response.status === 200) {
            if (response.data.status === true) {
                toast.success(response.data.message);
                setTimeout(() => window.location.reload(), 500);
            } else {
                successMessage.value = response.data.message;
                toast.error(response.data.message);
            }
        }
    } catch (error) {
        console.error('Failed to delete user:', error);
        const errorMsg = error.response?.data?.message || 'Error: Could not delete user.';
        toast.error(errorMsg);
    }
};

// Toggle 2FA for user
const confirmToggleTwoFactor = (userId, userName, user) => {
    if (!isSuperAdmin.value) {
        toast.error('Only Super Admin can manage 2FA');
        return;
    }

    const currentStatus = has2FAEnabled(user);
    const action = currentStatus ? 'Disable' : 'Enable';
    const message = currentStatus 
        ? `Are you sure you want to disable 2FA for "${userName}"?` 
        : `Are you sure you want to enable 2FA for "${userName}"? They will need to scan a QR code on their next login.`;
    
    showConfirmation({
        title: `${action} Two-Factor Authentication`,
        message: message,
        confirmText: action,
        confirmClass: currentStatus ? 'bg-orange-600 hover:bg-orange-700' : 'bg-indigo-600 hover:bg-indigo-700',
        onConfirm: () => toggleTwoFactor(userId, currentStatus),
    });
};

const toggleTwoFactor = async (userId, currentStatus) => {
    processingTwoFactor.value[userId] = true;

    try {
        const response = await axios.post(route('users.toggle_two_factor', userId), {
            enable: !currentStatus
        });

        if (response.data.status === true) {
            toast.success(response.data.message);
            // Update the user in the list without full reload
            const userIndex = props.users.findIndex(u => u.id === userId);
            if (userIndex !== -1) {
                // Set to current timestamp if enabling, null if disabling
                props.users[userIndex].two_factor_confirmed_at = currentStatus ? null : new Date().toISOString();
            }
        } else {
            toast.error(response.data.message || 'Failed to toggle 2FA');
        }
    } catch (error) {
        console.error('Failed to toggle 2FA:', error);
        const errorMsg = error.response?.data?.message || 'Error: Could not toggle 2FA.';
        toast.error(errorMsg);
    } finally {
        processingTwoFactor.value[userId] = false;
    }
};
</script>

<template>
    <Head title="User Manager" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Users Manager</h2>
        </template>


        <div class="space-y-6">
            <!-- Success Message -->
            <div v-if="successMessage" class="bg-gradient-to-r from-green-500 to-emerald-500 text-white px-6 py-4 rounded-2xl shadow-lg shadow-green-500/30 flex items-center gap-3 fade-in">
                <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium">{{ successMessage }}</span>
            </div>

            <!-- Header with Add Button -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">User Management</h1>
                    <p class="text-slate-600 mt-1">Manage system users and their roles</p>
                </div>
                <Link
                    :href="route('users.create')"
                    class="inline-flex items-center gap-2 px-5 py-2.5 btn-primary text-white rounded-xl font-semibold shadow-lg transition-all duration-300 hover:shadow-xl hover:-translate-y-0.5"
                >
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                    <span>Add User</span>
                </Link>
            </div>

            <!-- Users Table Card -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <!-- Mobile Card View -->
                <div class="block md:hidden divide-y divide-slate-100">
                    <div 
                        v-for="user in users" 
                        :key="'mobile-' + user.id"
                        class="p-4 space-y-3"
                    >
                        <!-- User Info -->
                        <div class="flex items-center gap-3">
                            <div class="h-12 w-12 shrink-0 rounded-full bg-primary flex items-center justify-center text-white font-semibold text-lg">
                                {{ user.name.charAt(0).toUpperCase() }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-slate-900 truncate">{{ user.name }}</p>
                                <p class="text-sm text-slate-500 truncate">{{ user.email }}</p>
                            </div>
                        </div>

                        <!-- Role & 2FA Status -->
                        <div class="flex items-center justify-between">
                            <span 
                                class="px-3 py-1 rounded-full text-xs font-medium"
                                :class="user.role === 'Admin' ? 'bg-primary-50 text-primary' : 'bg-slate-100 text-slate-700'"
                            >
                                {{ user.role }}
                            </span>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-500">2FA:</span>
                                <span 
                                    class="px-2 py-1 rounded-full text-xs font-medium"
                                    :class="has2FAEnabled(user) ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'"
                                >
                                    {{ has2FAEnabled(user) ? 'On' : 'Off' }}
                                </span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2 pt-2">
                            <Link
                                v-if="canManageUser(user)"
                                :href="route('users.edit', user.id)"
                                class="flex-1 py-2.5 text-center text-xs font-semibold text-primary bg-primary-50 rounded-xl hover:bg-primary-100 transition-colors"
                            >
                                Edit
                            </Link>
                            <button
                                v-if="canManageUser(user)"
                                @click="confirmDelete(user.id, user.name)"
                                class="flex-1 py-2.5 text-center text-xs font-semibold text-rose-600 bg-rose-50 rounded-xl hover:bg-rose-100 transition-colors"
                            >
                                Delete
                            </button>
                            <button
                                v-if="isSuperAdmin"
                                @click="confirmToggleTwoFactor(user.id, user.name, user)"
                                :disabled="processingTwoFactor[user.id]"
                                class="flex-1 py-2.5 text-center text-xs font-semibold text-indigo-600 bg-indigo-50 rounded-xl hover:bg-indigo-100 transition-colors disabled:opacity-50"
                            >
                                {{ has2FAEnabled(user) ? 'Disable 2FA' : 'Enable 2FA' }}
                            </button>
                        </div>
                    </div>
                    <div v-if="users.length === 0" class="p-8 text-center">
                        <svg class="h-12 w-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <p class="text-sm text-slate-500">No users found</p>
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        Name
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                    <div class="flex items-center gap-2">
                                        <svg class="h-4 w-4 shrink-0 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        Email
                                    </div>
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Role</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-slate-700 uppercase tracking-wider">2FA</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 shrink-0 rounded-full bg-primary flex items-center justify-center text-white font-semibold">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-semibold text-slate-900">{{ user.name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-slate-600">{{ user.email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium" :class="user.role === 'Admin' ? 'bg-primary-50 text-primary' : 'bg-slate-100 text-slate-700'">
                                        {{ user.role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center justify-center">
                                        <button
                                            v-if="isSuperAdmin"
                                            @click="confirmToggleTwoFactor(user.id, user.name, user)"
                                            :disabled="processingTwoFactor[user.id]"
                                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                            :class="has2FAEnabled(user) ? 'bg-indigo-600' : 'bg-slate-200'"
                                        >
                                            <span class="sr-only">Toggle 2FA</span>
                                            <span
                                                class="inline-block h-4 w-4 transform rounded-full bg-white shadow-lg transition-transform duration-200"
                                                :class="has2FAEnabled(user) ? 'translate-x-6' : 'translate-x-1'"
                                            />
                                        </button>
                                        <span
                                            v-else
                                            class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                                            :class="has2FAEnabled(user) ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-600'"
                                        >
                                            {{ has2FAEnabled(user) ? 'Enabled' : 'Disabled' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link
                                            v-if="canManageUser(user)"
                                            :href="route('users.edit', user.id)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-primary-50 text-primary rounded-lg font-medium hover:bg-primary-50 transition-all duration-200"
                                        >
                                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            <span>Edit</span>
                                        </Link>
                                        <button
                                            v-if="canManageUser(user)"
                                            @click="confirmDelete(user.id, user.name)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 text-red-600 rounded-lg font-medium hover:bg-red-100 transition-all duration-200"
                                        >
                                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.length === 0">
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-500">
                                        <svg class="h-12 w-12 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        <p class="text-sm font-medium">No users found</p>
                                        <p class="text-xs mt-1">Get started by adding a new user</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Confirmation Modal -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showConfirmModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <!-- Backdrop -->
                        <div 
                            class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity"
                            @click="closeConfirmModal"
                        ></div>
                        
                        <!-- Modal -->
                        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all">
                            <!-- Icon -->
                            <div class="flex justify-center mb-4">
                                <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center">
                                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="text-center mb-6">
                                <h3 class="text-xl font-semibold text-slate-900 mb-2">
                                    {{ confirmModalData.title }}
                                </h3>
                                <p class="text-sm text-slate-600">
                                    {{ confirmModalData.message }}
                                </p>
                            </div>

                            <!-- Actions -->
                            <div class="flex gap-3">
                                <button
                                    @click="closeConfirmModal"
                                    class="flex-1 px-4 py-2.5 bg-slate-100 text-slate-700 rounded-xl font-medium hover:bg-slate-200 transition-colors duration-200"
                                >
                                    Cancel
                                </button>
                                <button
                                    @click="handleConfirm"
                                    class="flex-1 px-4 py-2.5 text-white rounded-xl font-medium shadow-lg transition-all duration-200"
                                    :class="confirmModalData.confirmClass"
                                >
                                    {{ confirmModalData.confirmText }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>
