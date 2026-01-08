<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UserPreferencesForm from './Partials/UserPreferencesForm.vue';
import ApiTokenManager from './Partials/ApiTokenManager.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const isAdmin = computed(() => {
    const role = page.props.auth.user.role;
    return role === 'admin' || role === 'super_admin';
});

// Tab state
const activeTab = ref('profile');

const tabs = computed(() => {
    const baseTabs = [
        { id: 'profile', name: 'Profile Information', icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z' },
        { id: 'password', name: 'Password', icon: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' },
        { id: 'preferences', name: 'Preferences', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z' },
    ];
    
    if (isAdmin.value) {
        baseTabs.push({ id: 'api-tokens', name: 'API Tokens', icon: 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z' });
    }
    
    baseTabs.push({ id: 'delete', name: 'Delete Account', icon: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16' });
    
    return baseTabs;
});
</script>

<template>
    <Head title="Profile Settings" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center shadow-lg">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-xl text-slate-800 leading-tight">Profile Settings</h2>
                    <p class="text-sm text-slate-500">Manage your account settings and preferences</p>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Tabs Container -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-slate-100 max-w-full">
                <!-- Tab Headers -->
                <div class="border-b border-slate-200 bg-gradient-to-r from-slate-50 to-slate-100 overflow-hidden">
                    <div class="flex overflow-x-auto scrollbar-hide">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            @click="activeTab = tab.id"
                            :class="[
                                'flex items-center gap-1.5 sm:gap-2 px-3 sm:px-6 py-3 sm:py-4 font-medium text-xs sm:text-sm whitespace-nowrap transition-all duration-200 border-b-2',
                                activeTab === tab.id
                                    ? 'text-primary border-primary bg-white'
                                    : 'text-slate-600 border-transparent hover:text-slate-900 hover:bg-slate-50'
                            ]"
                        >
                            <svg class="h-4 w-4 sm:h-5 sm:w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon" />
                            </svg>
                            <span class="hidden sm:inline">{{ tab.name }}</span>
                        </button>
                    </div>
                </div>

                <!-- Tab Content -->
                <div class="p-6 sm:p-8">
                    <!-- Profile Information Tab -->
                    <div v-show="activeTab === 'profile'" class="animate-fade-in">
                        <UpdateProfileInformationForm
                            :must-verify-email="mustVerifyEmail"
                            :status="status"
                            class="max-w-2xl"
                        />
                    </div>

                    <!-- Password Tab -->
                    <div v-show="activeTab === 'password'" class="animate-fade-in">
                        <UpdatePasswordForm class="max-w-2xl" />
                    </div>

                    <!-- Preferences Tab -->
                    <div v-show="activeTab === 'preferences'" class="animate-fade-in">
                        <UserPreferencesForm class="max-w-2xl" />
                    </div>

                    <!-- API Tokens Tab (Admin Only) -->
                    <div v-if="isAdmin" v-show="activeTab === 'api-tokens'" class="animate-fade-in">
                        <ApiTokenManager class="max-w-full" />
                    </div>

                    <!-- Delete Account Tab -->
                    <div v-show="activeTab === 'delete'" class="animate-fade-in">
                        <DeleteUserForm class="max-w-2xl" />
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

.animate-fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
