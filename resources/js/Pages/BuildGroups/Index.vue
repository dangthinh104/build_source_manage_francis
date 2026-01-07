<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
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
            // Toast will be shown by AuthenticatedLayout from flash message
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
        router.post(route('build_groups.build', id), {}, {
            preserveScroll: true,
            // Toast will be shown by AuthenticatedLayout from flash message
        });
    }
};
</script>

<template>
    <Head title="Build Groups" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Build Groups</h2>
        </template>

        <div class="py-6">
            <div class="max-w-10xl mx-auto space-y-10" style="max-width: 100rem;">
                
                <header class="space-y-2">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-primary">Groups</p>
                            <h2 class="text-2xl font-semibold text-slate-900">Manage site collections</h2>
                            <p class="text-sm text-slate-600">Group sites together to trigger batch builds and manage dependencies.</p>
                        </div>
                        <PrimaryButton @click="openModal()" class="w-full md:w-auto self-start md:self-center">
                            <span class="mr-2">+</span> Create Group
                        </PrimaryButton>
                    </div>
                </header>

                <div v-if="groups.data.length === 0" class="rounded-3xl bg-white p-12 text-center border border-slate-100 shadow-sm">
                    <div class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-slate-50 mb-4">
                        <svg class="h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-slate-900">No build groups yet</h3>
                    <p class="mt-2 text-sm text-slate-500">Create a group to start managing multiple sites together.</p>
                </div>
                
                <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <div 
                        v-for="group in groups.data" 
                        :key="group.id" 
                        class="group relative flex flex-col rounded-3xl bg-white shadow-xl shadow-slate-200/50 ring-1 ring-slate-100 transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl hover:shadow-slate-200/80"
                    >
                        <!-- Card Header -->
                        <div class="border-b border-slate-100 px-6 py-5 bg-gradient-to-r from-slate-50/50 to-white rounded-t-3xl">
                             <div class="flex justify-between items-start">
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 group-hover:text-primary transition-colors duration-300">{{ group.name }}</h3>
                                    <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                        {{ group.sites_count }} sites linked
                                    </p>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">
                                    {{ group.sites_count > 0 ? 'Active' : 'Empty' }}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Card Body -->
                        <div class="flex-1 px-6 py-5">
                            <p class="text-sm text-slate-600 line-clamp-3 leading-relaxed">
                                {{ group.description || 'No description provided.' }}
                            </p>
                            
                            <div class="mt-6 flex items-center justify-between text-xs text-slate-400">
                                <span class="flex items-center gap-1">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    {{ group.user?.name || 'Unknown' }}
                                </span>
                            </div>
                        </div>

                        <!-- Card Actions -->
                        <div class="border-t border-slate-100 bg-slate-50/50 px-6 py-4 rounded-b-3xl flex items-center justify-between gap-3">
                            <div class="flex gap-2">
                                <button 
                                    @click="openModal(group)" 
                                    class="rounded-xl p-2 text-slate-500 hover:bg-white hover:text-indigo-600 hover:shadow-md transition-all duration-200"
                                    title="Edit Group"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>
                                <button 
                                    @click="deleteGroup(group.id)" 
                                    class="rounded-xl p-2 text-slate-500 hover:bg-white hover:text-rose-600 hover:shadow-md transition-all duration-200"
                                    title="Delete Group"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </div>
                            
                            <PrimaryButton 
                                @click="triggerBuild(group.id, group.name)" 
                                class="!py-2 !px-4 !text-xs shadow-none"
                                :class="{'opacity-50 cursor-not-allowed': group.sites_count === 0}"
                                :disabled="group.sites_count === 0"
                            >
                                <svg class="mr-1.5 h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Build All
                            </PrimaryButton>
                        </div>
                    </div>
                </div>

                 <div class="mt-6 flex items-center justify-end" v-if="groups.links">
                    <!-- Pagination (simplified for brevity, can match system one) -->
                     <nav class="inline-flex -space-x-px rounded-md bg-white shadow-sm" aria-label="Pagination">
                         <template v-for="(link, key) in groups.links" :key="key">
                            <div v-if="link.url === null" class="relative inline-flex items-center border border-slate-200 px-4 py-2 text-sm font-medium text-slate-500" v-html="link.label" />
                            <Link v-else :href="link.url" class="relative inline-flex items-center border border-slate-200 px-4 py-2 text-sm font-medium hover:bg-slate-50 focus:z-20" :class="{ 'z-10 bg-indigo-50 border-indigo-500 text-indigo-600': link.active, 'text-slate-500': !link.active }" v-html="link.label" />
                         </template>
                    </nav>
                </div>
            </div>
        </div>

        <DialogModal :show="isModalOpen" @close="closeModal">
            <template #title>
                {{ editingGroup ? 'Edit Build Group' : 'Create Build Group' }}
            </template>

            <template #content>
                <div class="mb-4">
                    <InputLabel for="name" value="Group Name" />
                    <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus placeholder="e.g. Production Sites" />
                    <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
                </div>

                <div class="mb-6">
                    <InputLabel for="description" value="Description" />
                    <TextInput id="description" type="text" class="mt-1 block w-full" v-model="form.description" placeholder="Optional description..." />
                    <div v-if="form.errors.description" class="text-red-500 text-sm mt-1">{{ form.errors.description }}</div>
                </div>

                <div class="mb-6">
                    <InputLabel value="Select Sites" class="mb-2" />
                    <div class="border border-slate-200 rounded-xl p-4 max-h-60 overflow-y-auto bg-slate-50 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div v-for="site in $page.props.allSites" :key="site.id" class="flex items-center bg-white p-2 rounded-lg border border-slate-100 shadow-sm transition-all hover:border-indigo-200">
                            <input 
                                type="checkbox" 
                                :id="`site-${site.id}`" 
                                :value="site.id" 
                                v-model="form.site_ids"
                                class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500 w-4 h-4 cursor-pointer"
                            >
                            <label :for="`site-${site.id}`" class="ml-2 text-sm text-slate-700 cursor-pointer select-none truncate flex-1 font-medium">
                                {{ site.site_name }}
                            </label>
                        </div>
                        <div v-if="!$page.props.allSites || $page.props.allSites.length === 0" class="col-span-2 flex flex-col items-center justify-center py-6 text-center text-slate-500">
                            <svg class="h-8 w-8 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <span class="text-sm">No sites available to select.</span>
                        </div>
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeModal">Cancel</SecondaryButton>
                <PrimaryButton class="ml-3" @click="submit" :disabled="form.processing">
                    {{ editingGroup ? 'Update Group' : 'Create Group' }}
                </PrimaryButton>
            </template>
        </DialogModal>
    </AuthenticatedLayout>
</template>
