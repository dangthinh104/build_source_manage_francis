<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { showToast } from '@/Utils/toastHelper';
import { useConfirm } from '@/Composables/useConfirm';

const { confirm } = useConfirm();

const props = defineProps({
    groups: Object,
});

const isModalOpen = ref(false);
const editingGroup = ref(null);
const loading = ref(false);

const form = useForm({
    name: '',
    description: '',
    site_ids: [],
});

const openModal = (group = null) => {
    editingGroup.value = group;
    if (group) {
        form.name = group.name;
        form.description = group.description;
        form.site_ids = group.sites ? group.sites.map(s => s.id) : [];
    } else {
        form.reset();
        form.site_ids = [];
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    editingGroup.value = null;
};

const submit = () => {
    if (editingGroup.value) {
        form.put(route('build_groups.update', editingGroup.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('build_groups.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteGroup = async (id) => {
    const isConfirmed = await confirm({
        title: 'Delete Build Group',
        message: 'Are you sure you want to delete this group? This action cannot be undone.',
        confirmText: 'Delete',
        variant: 'danger',
    });

    if (isConfirmed) {
        router.delete(route('build_groups.destroy', id), {
            preserveScroll: true,
        });
    }
};

const triggerBuild = async (id, groupName) => {
    const isConfirmed = await confirm({
        title: 'Trigger Build',
        message: `Are you sure you want to trigger a build for all sites in "${groupName}"?`,
        confirmText: 'Build All',
        variant: 'primary',
    });

    if (isConfirmed) {
        loading.value = true;
        router.post(route('build_groups.build', id), {}, {
            preserveScroll: true,
            onFinish: () => {
                loading.value = false;
            },
        });
    }
};
</script>

<template>
    <Head title="Build Groups" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Build Groups</h1>
                    <p class="text-sm text-slate-500 mt-1">Manage site collections and trigger batch builds</p>
                </div>
                <PrimaryButton @click="openModal()" class="hidden sm:flex">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Group
                </PrimaryButton>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Mobile Create Button -->
            <div class="sm:hidden">
                <PrimaryButton @click="openModal()" class="w-full justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Group
                </PrimaryButton>
            </div>

            <!-- Stats Summary -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-gradient-to-br from-indigo-50 to-purple-50 rounded-2xl p-4 border border-indigo-100">
                    <p class="text-xs font-medium text-indigo-600 uppercase tracking-wide">Total Groups</p>
                    <p class="text-2xl font-bold text-indigo-900 mt-1">{{ groups.data.length }}</p>
                </div>
                <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-2xl p-4 border border-emerald-100">
                    <p class="text-xs font-medium text-emerald-600 uppercase tracking-wide">Active Groups</p>
                    <p class="text-2xl font-bold text-emerald-900 mt-1">{{ groups.data.filter(g => g.sites_count > 0).length }}</p>
                </div>
                <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-2xl p-4 border border-amber-100">
                    <p class="text-xs font-medium text-amber-600 uppercase tracking-wide">Empty Groups</p>
                    <p class="text-2xl font-bold text-amber-900 mt-1">{{ groups.data.filter(g => g.sites_count === 0).length }}</p>
                </div>
                <div class="bg-gradient-to-br from-blue-50 to-cyan-50 rounded-2xl p-4 border border-blue-100">
                    <p class="text-xs font-medium text-blue-600 uppercase tracking-wide">Total Sites</p>
                    <p class="text-2xl font-bold text-blue-900 mt-1">{{ groups.data.reduce((sum, g) => sum + g.sites_count, 0) }}</p>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="groups.data.length === 0" class="bg-white rounded-2xl border border-slate-200 p-12 text-center">
                <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 mb-4">
                    <svg class="h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-slate-900">No build groups yet</h3>
                <p class="mt-2 text-sm text-slate-500 max-w-sm mx-auto">Create a group to start managing multiple sites together and trigger batch builds.</p>
                <PrimaryButton @click="openModal()" class="mt-6">
                    Create Your First Group
                </PrimaryButton>
            </div>

            <!-- Table View -->
            <div v-else class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <!-- Mobile Card View -->
                <div class="block md:hidden divide-y divide-slate-100">
                    <div 
                        v-for="group in groups.data" 
                        :key="'mobile-' + group.id"
                        class="p-4 space-y-3"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <h3 class="font-semibold text-slate-900">{{ group.name }}</h3>
                                <p class="text-xs text-slate-500 mt-0.5">{{ group.sites_count }} sites</p>
                            </div>
                            <span 
                                class="px-2.5 py-1 text-xs font-medium rounded-full"
                                :class="group.sites_count > 0 
                                    ? 'bg-emerald-100 text-emerald-700' 
                                    : 'bg-slate-100 text-slate-600'"
                            >
                                {{ group.sites_count > 0 ? 'Active' : 'Empty' }}
                            </span>
                        </div>
                        
                        <p class="text-sm text-slate-600 line-clamp-2">
                            {{ group.description || 'No description' }}
                        </p>
                        
                        <div class="flex items-center justify-between text-xs text-slate-500">
                            <span class="flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                {{ group.user?.name || 'Unknown' }}
                            </span>
                        </div>
                        
                        <div class="flex gap-2 pt-2">
                            <button
                                @click="triggerBuild(group.id, group.name)"
                                :disabled="group.sites_count === 0 || loading"
                                class="flex-1 py-2.5 text-center text-xs font-semibold rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="group.sites_count > 0 
                                    ? 'text-white bg-primary hover:bg-primary-dark' 
                                    : 'text-slate-400 bg-slate-100'"
                            >
                                Build All
                            </button>
                            <button
                                @click="openModal(group)"
                                class="py-2.5 px-4 text-xs font-semibold text-indigo-600 bg-indigo-50 rounded-xl hover:bg-indigo-100 transition-colors"
                            >
                                Edit
                            </button>
                            <button
                                @click="deleteGroup(group.id)"
                                class="py-2.5 px-4 text-xs font-semibold text-rose-600 bg-rose-50 rounded-xl hover:bg-rose-100 transition-colors"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Group Name</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-4 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Sites</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Owner</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <tr v-for="group in groups.data" :key="group.id" class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                        </div>
                                        <span class="font-semibold text-slate-900">{{ group.name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-slate-600 max-w-xs truncate" :title="group.description">
                                        {{ group.description || '—' }}
                                    </p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center justify-center w-8 h-8 text-sm font-bold rounded-full bg-slate-100 text-slate-700">
                                        {{ group.sites_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span 
                                        class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full"
                                        :class="group.sites_count > 0 
                                            ? 'bg-emerald-100 text-emerald-700' 
                                            : 'bg-slate-100 text-slate-600'"
                                    >
                                        <span 
                                            class="w-1.5 h-1.5 rounded-full mr-1.5"
                                            :class="group.sites_count > 0 ? 'bg-emerald-500' : 'bg-slate-400'"
                                        ></span>
                                        {{ group.sites_count > 0 ? 'Active' : 'Empty' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-slate-600">{{ group.user?.name || 'Unknown' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="triggerBuild(group.id, group.name)"
                                            :disabled="group.sites_count === 0 || loading"
                                            class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                            :class="group.sites_count > 0 
                                                ? 'text-white bg-primary hover:bg-primary-dark shadow-sm' 
                                                : 'text-slate-400 bg-slate-100'"
                                        >
                                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Build
                                        </button>
                                        <button 
                                            @click="openModal(group)" 
                                            class="p-2 text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                                            title="Edit"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                            </svg>
                                        </button>
                                        <button 
                                            @click="deleteGroup(group.id)" 
                                            class="p-2 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors"
                                            title="Delete"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <div class="flex items-center justify-end" v-if="groups.links && groups.data.length > 0">
                <nav class="inline-flex -space-x-px rounded-xl bg-white shadow-sm border border-slate-200 overflow-hidden" aria-label="Pagination">
                    <template v-for="(link, key) in groups.links" :key="key">
                        <div 
                            v-if="link.url === null" 
                            class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-slate-400 bg-slate-50 cursor-not-allowed" 
                            v-html="link.label" 
                        />
                        <Link 
                            v-else 
                            :href="link.url" 
                            class="relative inline-flex items-center px-4 py-2 text-sm font-medium transition-colors hover:bg-slate-50 focus:z-20" 
                            :class="{ 
                                'z-10 bg-primary text-white hover:bg-primary-dark': link.active, 
                                'text-slate-700 hover:text-slate-900': !link.active 
                            }" 
                            v-html="link.label" 
                        />
                    </template>
                </nav>
            </div>
        </div>

        <!-- Create/Edit Modal -->
        <DialogModal :show="isModalOpen" @close="closeModal">
            <template #title>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-900">
                            {{ editingGroup ? 'Edit Build Group' : 'Create Build Group' }}
                        </h3>
                        <p class="text-sm text-slate-500">
                            {{ editingGroup ? 'Update group settings and sites' : 'Group sites together for batch builds' }}
                        </p>
                    </div>
                </div>
            </template>

            <template #content>
                <div class="space-y-5">
                    <div>
                        <InputLabel for="name" value="Group Name" class="text-sm font-medium text-slate-700" />
                        <TextInput 
                            id="name" 
                            type="text" 
                            class="mt-1.5 block w-full rounded-xl" 
                            v-model="form.name" 
                            required 
                            autofocus 
                            placeholder="e.g. Production Sites" 
                        />
                        <p v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</p>
                    </div>

                    <div>
                        <InputLabel for="description" value="Description" class="text-sm font-medium text-slate-700" />
                        <TextInput 
                            id="description" 
                            type="text" 
                            class="mt-1.5 block w-full rounded-xl" 
                            v-model="form.description" 
                            placeholder="Optional description..." 
                        />
                        <p v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</p>
                    </div>

                    <div>
                        <InputLabel value="Select Sites" class="text-sm font-medium text-slate-700 mb-2" />
                        <div class="border border-slate-200 rounded-xl p-4 max-h-60 overflow-y-auto bg-slate-50/50">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div 
                                    v-for="site in $page.props.allSites" 
                                    :key="site.id" 
                                    class="flex items-center bg-white p-3 rounded-lg border border-slate-100 transition-all hover:border-indigo-300 hover:shadow-sm cursor-pointer"
                                    :class="{ 'border-indigo-400 bg-indigo-50/50': form.site_ids.includes(site.id) }"
                                    @click="form.site_ids.includes(site.id) 
                                        ? form.site_ids = form.site_ids.filter(id => id !== site.id)
                                        : form.site_ids.push(site.id)"
                                >
                                    <input 
                                        type="checkbox" 
                                        :id="`site-${site.id}`" 
                                        :value="site.id" 
                                        v-model="form.site_ids"
                                        class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-4 h-4 cursor-pointer"
                                        @click.stop
                                    >
                                    <label :for="`site-${site.id}`" class="ml-3 text-sm text-slate-700 cursor-pointer select-none truncate flex-1 font-medium">
                                        {{ site.site_name }}
                                    </label>
                                </div>
                            </div>
                            <div v-if="!$page.props.allSites || $page.props.allSites.length === 0" class="flex flex-col items-center justify-center py-8 text-center text-slate-500">
                                <svg class="h-10 w-10 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                                <span class="text-sm">No sites available to select.</span>
                            </div>
                        </div>
                        <p class="text-xs text-slate-500 mt-2">
                            Selected: {{ form.site_ids.length }} site(s)
                        </p>
                    </div>
                </div>
            </template>

            <template #footer>
                <div class="flex justify-end gap-3">
                    <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                    <PrimaryButton @click="submit" :disabled="form.processing">
                        {{ editingGroup ? 'Update Group' : 'Create Group' }}
                    </PrimaryButton>
                </div>
            </template>
        </DialogModal>
    </AuthenticatedLayout>
</template>
