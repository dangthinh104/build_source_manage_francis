<script setup xmlns="http://www.w3.org/1999/html">
import {useForm} from '@inertiajs/vue3';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { ref, watch} from 'vue';
import Modal from "@/Components/Modal.vue";
import axios from "axios";
import Checkbox from "@/Components/Checkbox.vue";
import { toast } from 'vue3-toastify'
// defineProps
defineProps({
    mySite: {
        type: Array,
    }
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

const updateMySetting = async () => {
    try {
        const response = await axios.post(route('my_site.update'), detailSite);
        console.warn(response)
    } catch (error) {
        console.error("Error fetching suggestions:", error);
    } finally {
        detailViewConfirm.value = false;
    }
}
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


</script>

<template>
    <section class="space-y-10" style="max-width: 100rem;">
        <header class="space-y-2">
            <p class="text-xs font-semibold uppercase tracking-[0.35em] text-indigo-500">Sites</p>
            <h2 class="text-2xl font-semibold text-slate-900">Stay in sync with every build</h2>
            <p class="text-sm text-slate-600">Register new sites, monitor PM2 ports, and trigger builds with live feedback.</p>
        </header>

        <form @submit.prevent="addNewSite()" class="rounded-3xl bg-white/95 p-6 shadow-xl ring-1 ring-slate-100 space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="site_name" class="block text-sm font-medium text-slate-700">Site name</label>
                    <input
                        type="text"
                        id="site_name"
                        v-model="form.site_name"
                        name="site_name"
                        class="mt-1 block w-full rounded-xl border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                        class="mt-1 block w-full rounded-xl border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                        class="mt-1 block w-full rounded-xl border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
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
                            class="transition hover:bg-indigo-50/40"
                        >
                            <td class="px-6 py-4 font-semibold text-slate-800">#{{ site.id }}</td>
                            <td class="px-6 py-4">
                                <a :href="`https://${site.site_name}`" target="_blank" class="font-medium text-indigo-600 hover:underline">
                                    {{ site.site_name }}
                                </a>
                                <p class="text-xs text-slate-500">Port {{ site.port_pm2 || '—' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <button
                                    @click="onOpenLogDetails(site.id)"
                                    class="inline-flex items-center gap-1 rounded-full border border-indigo-200 px-3 py-1 text-xs font-semibold text-indigo-600 hover:bg-indigo-50"
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
                                <p class="font-medium text-slate-700">{{ site.name || '—' }}</p>
                                <p class="text-xs">{{ site.last_build || 'No history' }}</p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <SecondaryButton class="text-xs" @click="openSiteDetailDialog(site.id, index)">Details</SecondaryButton>
                                    <PrimaryButton
                                        class="text-xs"
                                        type="button"
                                        :loading="loadingIndices.includes(index)"
                                        loading-text="Building..."
                                        @click="buildSite(site.id, index)"
                                    >
                                        Build
                                    </PrimaryButton>
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

        <Modal :show="detailViewConfirm" @close="closeDetailSiteModal" :maxWidth="maxWidth">
            <form @submit.prevent="updateMySetting">
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
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="text-sm font-medium text-slate-700">PM2 port</label>
                            <input
                                v-model="detailSite.port_pm2"
                                type="text"
                                class="mt-1 block w-full rounded-xl border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label class="text-sm font-medium text-slate-700">API endpoint URL</label>
                            <input
                                v-model="detailSite.api_endpoint_url"
                                type="text"
                                class="mt-1 block w-full rounded-xl border-slate-200 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <div>
                        <h4 class="text-sm font-semibold text-slate-700 mb-2">Shell Script Preview</h4>
                        <pre class="bg-gray-900 text-green-400 font-mono p-4 rounded-md overflow-x-auto whitespace-pre-wrap text-sm max-h-80">{{ detailSite.sh_content }}</pre>
                    </div>

                    <div class="flex justify-end gap-3">
                        <PrimaryButton type="submit" class="px-5 py-2 text-sm">Update</PrimaryButton>
                        <SecondaryButton @click="closeDetailSiteModal">Close</SecondaryButton>
                    </div>
                </div>
            </form>
        </Modal>
    </section>
</template>

