<script setup xmlns="http://www.w3.org/1999/html">
import {useForm, usePage} from '@inertiajs/vue3';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { ref, watch, computed} from 'vue';
import Modal from "@/Components/Modal.vue";
import axios from "axios";
import Checkbox from "@/Components/Checkbox.vue";
import LogHistoryModal from '@/Pages/Utils/LogHistoryModal.vue';
import { toast } from 'vue3-toastify'

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
        const response = await axios.post(route('my_site.get_content_log'), {'site_id': siteID});
        details.log_content = response.data.log_content
        details.site_name = response.data.site_name
        details.path_log = response.data.path_log
    } catch (error) {
        console.error("Error fetching suggestions:", error);
    } finally {
        confirmingViewLog.value = true;
    }
};
const openSiteDetailDialog = async (siteID) => {
    try {
        const response = await axios.post(route('my_site.open_popup_detail'), {'site_id': siteID});
        detailSite.sh_content = response.data.sh_content
        detailSite.site_name = response.data.site_name
        detailSite.last_path_log = response.data.last_path_log
        detailSite.sh_content_dir = response.data.sh_content_dir
        detailSite.created_at = response.data.created_at
        detailSite.last_user_build = response.data.last_user_build
        detailSite.last_build_success = response.data.last_build_success
        detailSite.last_build_fail = response.data.last_build_fail
        detailSite.port_pm2 = response.data.port_pm2
        detailSite.last_build = response.data.last_build
        detailSite.path_source_code = response.data.path_source_code
        detailSite.api_endpoint_url = response.data.api_endpoint_url
        detailSite.id = response.data.id
    } catch (error) {
        console.error("Error fetching suggestions:", error);
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
const buildSite = async (siteID,index) => {
    try {
        loadingIndices.value.push(index)
        const response = await axios.post(route('my_site.build_my_site'), {'site_id': siteID});
        if (response.data.status === 1) {
            location.reload(); // Reloads the current page
        } else {
            toast(response.data.message, {
                type: 'error',
                position: 'top-right',
                duration: 5000
            })
        }
    } catch (error) {
        toast('Something went wrong', {
            type: 'error',
            position: 'top-right',
            duration: 5000
        })
    } finally {
        loadingIndices.value = loadingIndices.value.filter(i => i !== index)
    }
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
        const response = await axios.post(route('my_site.open_popup_detail'), {'site_id': siteID});
        editSite.site_name = response.data.site_name;
        editSite.port_pm2 = response.data.port_pm2;
        editSite.api_endpoint_url = response.data.api_endpoint_url;
        editSite.id = response.data.id;
    } catch (error) {
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
        const response = await axios.post(route('my_site.update'), editSite);
        toast('Site updated successfully', { type: 'success' });
        setTimeout(() => location.reload(), 800);
    } catch (error) {
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
        const response = await axios.post(route('my_site.delete'), { site_id: deleteTarget.value.id });
        if (response.data.status) {
            toast('Deletion queued — processing in background', { type: 'success' });
            // Optionally remove row from UI instead of reload
            setTimeout(() => location.reload(), 1200);
        } else {
            toast('Delete failed: ' + (response.data.message || response.data.messages?.join(', ')), { type: 'error' });
        }
    } catch (e) {
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

        <!-- Only show Add Site form for super_admin -->
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

            <div class="overflow-x-auto">
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
                            :class="index % 2 === 0 ? 'bg-white' : 'bg-slate-50/60'"
                            class="transition hover:bg-primary-50"
                        >
                            <td class="px-6 py-4 font-semibold text-slate-800">
                                <button @click="openHistory(site.id)" class="text-primary hover:underline">#{{ site.id }}</button>
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
                                    {{ site.last_build_success || '—' }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-600">
                                    {{ site.last_build_fail || '—' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-500">
                                <p class="font-medium text-slate-700">{{ site.lastBuilder ? site.lastBuilder.name : '—' }}</p>
                                <p class="text-xs">{{ site.last_build || 'No history' }}</p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <SecondaryButton class="text-xs" @click="openSiteDetailDialog(site.id, index)">Details</SecondaryButton>
                                    <SecondaryButton class="text-xs" @click="openHistory(site.id)">History</SecondaryButton>
                                    <SecondaryButton v-if="isSuperAdmin" class="text-xs" @click="openEditModal(site.id)">Edit</SecondaryButton>
                                    <PrimaryButton
                                        v-if="hasAdminPrivileges"
                                        class="text-xs"
                                        type="button"
                                        :loading="loadingIndices.includes(index)"
                                        loading-text="Building..."
                                        @click="buildSite(site.id, index)"
                                    >
                                        Build
                                    </PrimaryButton>
                                    <SecondaryButton v-if="isSuperAdmin" class="text-xs text-rose-600" @click="confirmDeleteSite(site)">Delete</SecondaryButton>
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

