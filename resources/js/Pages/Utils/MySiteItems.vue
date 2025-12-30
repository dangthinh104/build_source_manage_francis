<script setup xmlns="http://www.w3.org/1999/html">
import {useForm, usePage} from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { ref, watch, computed} from 'vue';
import Modal from "@/Components/Modal.vue";
import axios from "axios";
import Checkbox from "@/Components/Checkbox.vue";
import LogHistoryModal from '@/Pages/Utils/LogHistoryModal.vue';
import { toast } from 'vue3-toastify';
import { wrapResponse } from '@/Utils/apiResponse';

const page = usePage();

// defineProps
defineProps({
    mySite: {
        type: Array,
    }
});

// Check if current user is super admin
const isSuperAdmin = computed(() => {
    const role = page.props.auth?.user?.role;
    return role === 'super_admin';
});

// Check if user has admin privileges (admin or super_admin)
const hasAdminPrivileges = computed(() => {
    const role = page.props.auth?.user?.role;
    return role === 'admin' || role === 'super_admin';
});

// Check permissions from Inertia shared data
const can = computed(() => page.props.auth?.can || {});

const form = useForm({
    site_id: null,
    site_name: null,
    sh_content_dir: null,
    sh_content: null,
    port_pm2: null,
    folder_source_path: null,
    include_pm2: false,
})

// Init value
const confirmingViewLog = ref(false);
const detailViewConfirm = ref(false);
const maxWidth = ref('3xl');
const loadingIndices = ref([])
// Detail Log
const details = {
    site_name :'',
    path_log :'',
    log_content :'',
    sh_content :'',
    port_pm2 :'',
};

const detailSite = {
    'sh_content'         : '',
    'env_content'        : '',
    'site_name'          : '',
    'last_path_log'      : '',
    'sh_content_dir'     : '',
    'created_at'         : '',
    'last_user_build'    : '',
    'last_build_success' : '',
    'last_build_fail'    : '',
    'last_build'         : '',
    'port_pm2'          : '',
    'path_source_code'          : '',
    'api_endpoint_url'          : '',
    'id'          : '',
}
const editSite = {
    'site_name'          : '',
    'port_pm2'          : '',
    'api_endpoint_url'          : '',
    'id'          : '',
}
const historyModalShow = ref(false);
const historySiteId = ref(null);
const deleteConfirmShow = ref(false);
const deleteTarget = ref(null);
const deleting = ref(false);
const editModalShow = ref(false);
const updating = ref(false);
// Function onHandle
const onOpenLogDetails = async (siteID) => {
    try {
        const response = await wrapResponse(
            axios.post(route('my_site.get_content_log'), {'site_id': siteID})
        );
        
        if (response.isSuccess) {
            const data = response.data;
            details.log_content = data.log_content;
            details.site_name = data.site_name;
            details.path_log = data.path_log;
        } else {
            toast(response.message || 'Failed to load log', { type: 'error' });
        }
    } catch (error) {
        console.error("Error fetching log details:", error);
        toast('Failed to load log', { type: 'error' });
    } finally {
        confirmingViewLog.value = true;
    }
};
const openSiteDetailDialog = async (siteID) => {
    try {
        const response = await wrapResponse(
            axios.post(route('my_site.open_popup_detail'), {'site_id': siteID})
        );
        
        if (response.isSuccess) {
            Object.assign(detailSite, response.data);
        } else {
            toast(response.message || 'Failed to load site details', { type: 'error' });
        }
    } catch (error) {
        console.error("Error fetching site details:", error);
        toast('Failed to load site details', { type: 'error' });
    } finally {
        detailViewConfirm.value = true;
    }
};
const closeDetailSiteModal = () => {
    detailViewConfirm.value = false;
};
const closeConfirmViewLog = () => {
    confirmingViewLog.value = false;
};
//====== Function Handle
const addNewSite = () => {
    form.errors.site_name = '';
    if (!checkDomain(form.site_name)) {
        form.errors.site_name = 'Site name invalid';
        return;
    }
    form.post(route('my_site.store'), {
        onSuccess: () => form.reset(),
    });
};

const checkDomain = (domain) => {
    // Regular expression to match domain patterns like abc.com or subdomain.abc.com
    const domainPattern = /^(?!:\/\/)([a-zA-Z0-9-_]+\.)+[a-zA-Z]{2,6}$/;
    return domainPattern.test(domain);
}
const buildSite = async (siteID, index) => {
    try {
        loadingIndices.value.push(index);
        const response = await wrapResponse(
            axios.post(route('my_site.build_my_site'), {'site_id': siteID})
        );
        
        if (response.isSuccess) {
            const buildData = response.data;
            
            // Check if build was queued (async)
            if (buildData.status === 'queued') {
                toast('Build queued! Processing in background... ⏳', {
                    type: 'info',
                    position: 'top-right',
                    duration: 4000
                });
                
                // Poll for build completion
                pollBuildStatus(siteID, index);
                return; // Don't remove from loadingIndices yet
            }
            
            // Immediate success
            toast('Build completed successfully! 🎉', {
                type: 'success',
                position: 'top-right',
                duration: 4000
            });
            setTimeout(() => location.reload(), 1500);
        } else {
            response.handleToast(toast);
            loadingIndices.value = loadingIndices.value.filter(i => i !== index);
        }
    } catch (error) {
        console.error('Build error:', error);
        toast('Build failed: ' + (error.message || 'Something went wrong'), {
            type: 'error',
            position: 'top-right',
            duration: 5000
        });
        loadingIndices.value = loadingIndices.value.filter(i => i !== index);
    }
};

// Poll for build completion status
const pollBuildStatus = async (siteID, index) => {
    const maxPolls = 120; // Max 6 minutes (120 * 3 seconds)
    let polls = 0;
    
    const pollInterval = setInterval(async () => {
        polls++;
        
        try {
            const response = await wrapResponse(
                axios.post(route('my_site.build_status'), { site_id: siteID })
            );
            
            if (response.isSuccess) {
                const buildStatus = response.data.status;
                
                if (buildStatus === 'success') {
                    clearInterval(pollInterval);
                    toast('Build completed successfully! 🎉', {
                        type: 'success',
                        position: 'top-right',
                        duration: 4000
                    });
                    setTimeout(() => location.reload(), 1500);
                } else if (buildStatus === 'failed') {
                    clearInterval(pollInterval);
                    toast('Build failed. Check logs for details.', {
                        type: 'error',
                        position: 'top-right',
                        duration: 5000
                    });
                    loadingIndices.value = loadingIndices.value.filter(i => i !== index);
                } else if (polls >= maxPolls) {
                    clearInterval(pollInterval);
                    toast('Build is taking longer than expected. Check logs for status.', {
                        type: 'warning',
                        position: 'top-right',
                        duration: 5000
                    });
                    loadingIndices.value = loadingIndices.value.filter(i => i !== index);
                }
            }
            // If status is 'processing' or 'queued', continue polling
        } catch (error) {
            console.error('Error polling build status:', error);
            // Continue polling on error (network issues, etc.)
            if (polls >= maxPolls) {
                clearInterval(pollInterval);
                loadingIndices.value = loadingIndices.value.filter(i => i !== index);
            }
        }
    }, 3000); // Poll every 3 seconds
};

const openHistory = (siteID) => {
    historySiteId.value = siteID;
    historyModalShow.value = true;
};

const closeHistory = () => {
    historySiteId.value = null;
    historyModalShow.value = false;
};

const openEditModal = async (siteID) => {
    try {
        const response = await wrapResponse(
            axios.post(route('my_site.open_popup_detail'), {'site_id': siteID})
        );
        
        if (response.isSuccess) {
            const data = response.data;
            editSite.site_name = data.site_name;
            editSite.port_pm2 = data.port_pm2;
            editSite.api_endpoint_url = data.api_endpoint_url;
            editSite.id = data.id;
        } else {
            response.handleToast(toast);
        }
    } catch (error) {
        console.error('Error loading site data:', error);
        toast('Failed to load site data', { type: 'error' });
    } finally {
        editModalShow.value = true;
    }
};

const closeEditModal = () => {
    editModalShow.value = false;
};

const updateSite = async () => {
    try {
        updating.value = true;
        const response = await wrapResponse(
            axios.post(route('my_site.update'), editSite)
        );
        
        if (response.isSuccess) {
            response.handleToast(toast, 'Site updated successfully');
            setTimeout(() => location.reload(), 800);
        } else {
            response.handleToast(toast);
        }
    } catch (error) {
        console.error('Error updating site:', error);
        toast('Failed to update site', { type: 'error' });
    } finally {
        updating.value = false;
        editModalShow.value = false;
    }
};

const onOpenHistoryLog = (logData) => {
    // Handle both build history and filesystem logs
    details.log_content = logData.content || logData.output_log || '';
    details.site_name = logData.filename || 'Log Viewer';
    details.path_log = logData.path || '';
    confirmingViewLog.value = true;
    historyModalShow.value = false;
};

const confirmDeleteSite = (site) => {
    deleteTarget.value = site;
    deleteConfirmShow.value = true;
};

const performDeleteSite = async () => {
    try {
        deleting.value = true;
        const response = await wrapResponse(
            axios.post(route('my_site.delete'), { site_id: deleteTarget.value.id })
        );
        
        if (response.isSuccess) {
            response.handleToast(toast, 'Deletion queued — processing in background');
            setTimeout(() => location.reload(), 1200);
        } else {
            response.handleToast(toast);
        }
    } catch (e) {
        console.error('Error deleting site:', error);
        toast('Delete failed', { type: 'error' });
    } finally {
        deleting.value = false;
        deleteConfirmShow.value = false;
        deleteTarget.value = null;
    }
};


</script>

<template>
    <section class="space-y-10" style="max-width: 100rem;">
        <header class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-primary">Sites</p>
            <h2 class="text-2xl font-semibold text-slate-900">Stay in sync with every build</h2>
            <p class="text-sm text-slate-600">Register new sites, monitor PM2 ports, and trigger builds with live feedback.</p>
        </header>

        <!-- Only show Add Site form for users with manage_mysites permission -->
        <form v-if="isSuperAdmin" @submit.prevent="addNewSite()" class="rounded-3xl bg-white/95 p-6 shadow-xl ring-1 ring-slate-100 space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="site_name" class="block text-sm font-medium text-slate-700">Site name</label>
                    <input
                        type="text"
                        id="site_name"
                        v-model="form.site_name"
                        name="site_name"
                        class="mt-1 block w-full rounded-xl border-slate-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                        placeholder="example.francis.house"
                    >
                    <InputError :message="form.errors.site_name" class="mt-2" />
                </div>

                <div>
                    <label for="folder_source_path" class="block text-sm font-medium text-slate-700">Folder source path</label>
                    <input
                        type="text"
                        id="folder_source_path"
                        v-model="form.folder_source_path"
                        name="folder_source_path"
                        class="mt-1 block w-full rounded-xl border-slate-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                        placeholder="/var/www/html/sites/acme"
                    >
                    <InputError :message="form.errors.folder_source_path" class="mt-2" />
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                    <Checkbox name="include_pm2" v-model:checked="form.include_pm2" />
                    Include PM2 configuration
                </label>
                <div v-if="form.include_pm2" class="w-full md:w-auto md:flex-1">
                    <label for="port_pm2" class="block text-sm font-medium text-slate-700">PM2 port</label>
                    <input
                        type="text"
                        id="port_pm2"
                        v-model="form.port_pm2"
                        class="mt-1 block w-full rounded-xl border-slate-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                        placeholder="3001"
                    >
                    <InputError :message="form.errors.port_pm2" class="mt-2" />
                </div>
            </div>

            <div class="flex justify-end">
                <PrimaryButton class="px-6 py-3 text-sm" :loading="form.processing" loading-text="Saving site...">Add site</PrimaryButton>
            </div>
        </form>

        <div class="rounded-3xl bg-white shadow-2xl ring-1 ring-slate-100">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 px-6 py-4">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Current deployments</h3>
                    <p class="text-sm text-slate-500">{{ mySite.length }} site(s) tracked</p>
                </div>
                <span class="rounded-full bg-slate-100 px-4 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    Live builds
                </span>
            </div>
            <!-- Mobile Card View (visible only on small screens) -->
            <div class="block md:hidden space-y-4 p-4">
                <div
                    v-for="(site, index) in mySite"
                    :key="'mobile-' + site.id"
                    class="bg-white rounded-2xl shadow-md border border-slate-100 overflow-hidden"
                    :class="loadingIndices.includes(index) ? 'opacity-60' : ''"
                >
                    <!-- Card Header -->
                    <div class="bg-gradient-to-r from-slate-50 to-slate-100 px-4 py-3 border-b border-slate-200">
                        <Link :href="route('my_site.show', site.id)" class="font-semibold text-primary hover:underline">
                            #{{ site.id }} - {{ site.site_name }}
                        </Link>
                        <p class="text-xs text-slate-500 mt-0.5">Port {{ site.port_pm2 || '—' }}</p>
                    </div>

                    <!-- Card Body -->
                    <div class="p-4 space-y-3">
                        <!-- Status Row -->
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-500">Last Success</span>
                            <span class="text-xs px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600 font-medium">
                                {{ site.last_build_success_ago || '—' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-500">Last Fail</span>
                            <span class="text-xs px-2.5 py-1 rounded-full bg-rose-50 text-rose-600 font-medium">
                                {{ site.last_build_fail_ago || '—' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-xs text-slate-500">Last Builder</span>
                            <span class="text-sm text-slate-700">{{ site.last_builder?.name || '—' }}</span>
                        </div>
                    </div>

                    <!-- Card Actions (full-width buttons for better touch) -->
                    <div class="border-t border-slate-100 p-3 space-y-2">
                        <div class="flex gap-2">
                            <button
                                @click="onOpenLogDetails(site.id)"
                                class="flex-1 py-2.5 text-xs font-semibold text-primary border border-primary rounded-xl hover:bg-primary-50 transition-colors"
                            >
                                View Log
                            </button>
                            <SecondaryButton 
                                class="flex-1 text-xs py-2.5" 
                                @click="openSiteDetailDialog(site.id, index)"
                                :disabled="loadingIndices.includes(index)"
                            >Details</SecondaryButton>
                        </div>
                        <div class="flex gap-2">
                            <SecondaryButton 
                                v-if="can.manage_mysites || isSuperAdmin" 
                                class="flex-1 text-xs py-2.5" 
                                @click="openEditModal(site.id)"
                                :disabled="loadingIndices.includes(index)"
                            >Edit</SecondaryButton>
                            <Link
                                v-if="hasAdminPrivileges"
                                :href="route('logs.index', { subfolder: site.site_name })"
                                class="flex-1 py-2.5 text-center text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-xl hover:bg-amber-100 transition-colors"
                            >
                                PM2 Logs
                            </Link>
                        </div>
                        <PrimaryButton
                            v-if="can.build_mysites || hasAdminPrivileges"
                            class="w-full py-3 text-sm"
                            type="button"
                            :loading="loadingIndices.includes(index)"
                            :disabled="loadingIndices.includes(index)"
                            loading-text="Building..."
                            @click="buildSite(site.id, index)"
                        >
                            Build Site
                        </PrimaryButton>
                        <SecondaryButton 
                            v-if="isSuperAdmin" 
                            class="w-full text-xs py-2.5 text-rose-600 border-rose-200 hover:bg-rose-50" 
                            @click="confirmDeleteSite(site)"
                            :disabled="loadingIndices.includes(index)"
                        >Delete Site</SecondaryButton>
                    </div>
                </div>
            </div>

            <!-- Desktop Table View (hidden on mobile) -->
            <div class="hidden md:block overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-6 py-3">ID</th>
                            <th class="px-6 py-3">Site</th>
                            <th class="px-6 py-3">Last log</th>
                            <th class="px-6 py-3">Last success</th>
                            <th class="px-6 py-3">Last fail</th>
                            <th class="px-6 py-3">Last builder</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="(site, index) in mySite"
                            :key="site.id"
                            :class="[
                                index % 2 === 0 ? 'bg-white' : 'bg-slate-50/60',
                                loadingIndices.includes(index) ? 'opacity-60 pointer-events-none' : ''
                            ]"
                            class="transition hover:bg-primary-50"
                        >
                            <td class="px-6 py-4 font-semibold text-slate-800">
                                <Link :href="route('my_site.show', site.id)" class="text-primary hover:underline">#{{ site.id }}</Link>
                            </td>
                            <td class="px-6 py-4">
                                <a :href="`https://${site.site_name}`" target="_blank" class="font-medium text-primary hover:underline">
                                    {{ site.site_name }}
                                </a>
                                <p class="text-xs text-slate-500">Port {{ site.port_pm2 || '—' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <button
                                    @click="onOpenLogDetails(site.id)"
                                    class="inline-flex items-center gap-1 rounded-full border border-primary px-3 py-1 text-xs font-semibold text-primary hover:bg-primary-50"
                                >
                                    View log
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 12h16m0 0l-6-6m6 6-6 6" />
                                    </svg>
                                </button>
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-600">
                                    {{ site.last_build_success_ago || '—' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-600">
                                    {{ site.last_build_fail_ago || '—' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-500">
                                <p class="font-medium text-slate-700">{{ site.last_builder ? site.last_builder.name : '—' }}</p>
                                <p class="text-xs">{{ site.last_builder ? site.last_builder.email : '' }}</p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <Link
                                        v-if="hasAdminPrivileges"
                                        :href="route('logs.index', { subfolder: site.site_name })"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 text-amber-700 rounded-lg text-xs font-semibold hover:from-amber-100 hover:to-orange-100 hover:border-amber-300 transition-all duration-200"
                                        :disabled="loadingIndices.includes(index)"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        PM2 Logs
                                    </Link>
                                    <SecondaryButton 
                                        class="text-xs" 
                                        @click="openSiteDetailDialog(site.id, index)"
                                        :disabled="loadingIndices.includes(index)"
                                    >Details</SecondaryButton>
                                    <SecondaryButton 
                                        v-if="can.manage_mysites || isSuperAdmin" 
                                        class="text-xs" 
                                        @click="openEditModal(site.id)"
                                        :disabled="loadingIndices.includes(index)"
                                    >Edit</SecondaryButton>
                                    <PrimaryButton
                                        v-if="can.build_mysites || hasAdminPrivileges"
                                        class="text-xs"
                                        type="button"
                                        :loading="loadingIndices.includes(index)"
                                        :disabled="loadingIndices.includes(index)"
                                        loading-text="Building..."
                                        @click="buildSite(site.id, index)"
                                    >
                                        Build
                                    </PrimaryButton>
                                    <SecondaryButton 
                                        v-if="isSuperAdmin" 
                                        class="text-xs text-rose-600" 
                                        @click="confirmDeleteSite(site)"
                                        :disabled="loadingIndices.includes(index)"
                                    >Delete</SecondaryButton>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <Modal :show="confirmingViewLog" @close="closeConfirmViewLog" :maxWidth="maxWidth">
            <div class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-slate-900">Latest log · {{ details.path_log }}</h2>
                <div>
                    <h4 class="text-sm font-semibold text-slate-700 mb-2">Log Preview</h4>
                    <pre class="bg-gray-900 text-green-400 font-mono p-4 rounded-md overflow-x-auto whitespace-pre-wrap text-sm max-h-80">{{ details.log_content }}</pre>
                </div>
                <div class="flex justify-end">
                    <SecondaryButton @click="closeConfirmViewLog">Close</SecondaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="deleteConfirmShow" @close="() => { deleteConfirmShow = false }" :maxWidth="maxWidth">
            <div class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-slate-900">Confirm Deletion</h2>
                <p class="text-sm text-slate-700">You're about to delete site: <strong>{{ deleteTarget?.site_name }}</strong></p>
                <ul class="text-sm text-slate-600 list-disc ml-5">
                    <li>Stopping PM2 process: <strong>app-{{ deleteTarget?.site_name }}</strong></li>
                    <li>Removing Apache config for: <strong>{{ deleteTarget?.site_name }}</strong></li>
                    <li>Removing folder: <strong>{{ deleteTarget?.path_source_code }}</strong></li>
                    <li>Removing generated scripts & logs in storage</li>
                    <li>Deleting database record</li>
                </ul>
                <div class="flex justify-end gap-3">
                    <SecondaryButton @click="() => { deleteConfirmShow = false; deleteTarget = null }">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="deleting" @click="performDeleteSite" :loading="deleting" loading-text="Deleting...">Delete (irreversible)</PrimaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="detailViewConfirm" @close="closeDetailSiteModal" :maxWidth="maxWidth">
            <div class="p-6 space-y-4">
                <h2 class="text-lg font-semibold text-slate-900">Details · {{ detailSite.site_name }}</h2>
                <div class="grid gap-3 md:grid-cols-2 text-sm text-slate-600">
                    <p><span class="font-semibold text-slate-800">Last path log:</span> {{ detailSite.last_path_log }}</p>
                    <p><span class="font-semibold text-slate-800">SH directory:</span> {{ detailSite.sh_content_dir }}</p>
                    <p><span class="font-semibold text-slate-800">Last user build:</span> {{ detailSite.last_user_build }}</p>
                    <p><span class="font-semibold text-slate-800">Last build:</span> {{ detailSite.last_build }}</p>
                    <p><span class="font-semibold text-slate-800">Success:</span> {{ detailSite.last_build_success }}</p>
                    <p><span class="font-semibold text-slate-800">Fail:</span> {{ detailSite.last_build_fail }}</p>
                    <p><span class="font-semibold text-slate-800">Created:</span> {{ detailSite.created_at }}</p>
                    <p><span class="font-semibold text-slate-800">Source:</span> {{ detailSite.path_source_code }}</p>
                    <p><span class="font-semibold text-slate-800">PM2 port:</span> {{ detailSite.port_pm2 || '—' }}</p>
                    <p><span class="font-semibold text-slate-800">API endpoint:</span> {{ detailSite.api_endpoint_url || '—' }}</p>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-slate-700 mb-2">Shell Script Preview</h4>
                    <pre class="bg-gray-900 text-green-400 font-mono p-4 rounded-md overflow-x-auto whitespace-pre-wrap text-sm max-h-80">{{ detailSite.sh_content }}</pre>
                </div>

                <div>
                    <h4 class="text-sm font-semibold text-slate-700 mb-2">.env File Content</h4>
                    <pre class="bg-gray-900 text-amber-400 font-mono p-4 rounded-md overflow-x-auto whitespace-pre-wrap text-sm max-h-80">{{ detailSite.env_content || 'No .env file available' }}</pre>
                </div>

                <div class="flex justify-end">
                    <SecondaryButton @click="closeDetailSiteModal">Close</SecondaryButton>
                </div>
            </div>
        </Modal>

        <Modal :show="editModalShow" @close="closeEditModal" :maxWidth="maxWidth">
            <form @submit.prevent="updateSite">
                <div class="p-6 space-y-4">
                    <h2 class="text-lg font-semibold text-slate-900">Edit Site · {{ editSite.site_name }}</h2>
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Site Name</label>
                        <input
                            v-model="editSite.site_name"
                            type="text"
                            class="block w-full rounded-xl border-slate-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                            placeholder="example.com"
                            required
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">PM2 Port</label>
                        <input
                            v-model="editSite.port_pm2"
                            type="text"
                            class="block w-full rounded-xl border-slate-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                            placeholder="3001"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">API Endpoint URL</label>
                        <input
                            v-model="editSite.api_endpoint_url"
                            type="text"
                            class="block w-full rounded-xl border-slate-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary"
                            placeholder="https://api.example.com"
                        />
                    </div>

                    <div class="flex justify-end gap-3">
                        <SecondaryButton type="button" @click="closeEditModal">Cancel</SecondaryButton>
                        <PrimaryButton type="submit" :disabled="updating" :loading="updating" loading-text="Updating...">Update Site</PrimaryButton>
                    </div>
                </div>
            </form>
        </Modal>
    </section>
</template>

<LogHistoryModal :siteId="historySiteId" :show="historyModalShow" @close="closeHistory" @openLog="onOpenHistoryLog" />

