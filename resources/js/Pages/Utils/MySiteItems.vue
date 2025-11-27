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
    <section class="space-y-9" style="max-width: 100rem;">
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">List our site</h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                You can update or create your site
            </p>
        </header>

        <form @submit.prevent="addNewSite()">
            <div class="mb-4">
                <label for="site_name" class="block text-sm font-medium text-white">Name</label>
                <input type="text" id="site_name" v-model="form.site_name" name="site_name" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                <InputError :message="form.errors.site_name" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"/>
            </div>

            <div class="mb-4">
                <label for="folder_source_path" class="block text-sm font-medium text-white">Folder Source Path</label>
                <input type="text" id="folder_source_path" v-model="form.folder_source_path" name="folder_source_path" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                <InputError :message="form.errors.folder_source_path" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"/>
            </div>

            <div class="block mt-4">
                <label class="flex items-center">
                    <Checkbox name="include_pm2" v-model:checked="form.include_pm2" />
                    <span class="ms-2 text-sm text-gray-600 text-white">Include PM2</span>
                </label>
            </div>
            <div class="mb-4" v-show="form.include_pm2">
                <label for="folder_source_path" class="block text-sm font-medium text-white">Port PM2</label>
                <input type="text" id="port_pm2" v-model="form.port_pm2" name="folder_source_path" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                <InputError :message="form.errors.port_pm2" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"/>
            </div>
            <div>
                <PrimaryButton type="submit" >Add Site </PrimaryButton>
            </div>

        </form>
        <div class="overflow-x-auto">
        <table class="table-auto border-collapse border border-gray-400 w-full">
            <thead>
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Site Name</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Log</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Success</th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Fail</th>
                <th scope="col" class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last User Build</th>
<!--                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Build</th>-->
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(site, index) in mySite" :key="site.id">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    {{ site.id }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <a :href="`https://${site.site_name}`" target="_blank" >{{ site.site_name }} </a>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    View Log
                    <button @click="onOpenLogDetails(site.id)"  class="bg-300  rounded inline-flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                    </button>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-700">{{ site.last_build_success }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">{{ site.last_build_fail }}</td>
                <td class="px-1 py-1 whitespace-nowrap text-sm text-gray-500">{{ site.name }}</td>
<!--                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ site.last_build }}</td>-->

                <td class="px-2 py-2 whitespace-nowrap text-right text-sm font-medium">
                    <SecondaryButton @click="openSiteDetailDialog(site.id, index)">Detail</SecondaryButton>
                    <PrimaryButton
                        @click="buildSite(site.id, index)"
                        :disabled="loadingIndices.includes(index)"
                        class="relative transition-all duration-200"
                    >
                        <span :class="{ 'opacity-0': loadingIndices.includes(index) }">Build</span>
                        <div
                            v-if="loadingIndices.includes(index)"
                            class="absolute inset-0 flex items-center justify-center bg-opacity-20 bg-blue-100 transition-opacity duration-200"
                        >
                            <div class="h-6 w-6 border-4 border-primary border-t-transparent rounded-full animate-spin">
                                <span class="sr-only">Wait...</span>
                            </div>
                            <span class="ml-2 text-primary font-medium">Wait...</span>
                        </div>
                    </PrimaryButton>

                </td>
            </tr>
            </tbody>
        </table>
        </div>
        <Modal :show="confirmingViewLog" @close="closeConfirmViewLog" :maxWidth="maxWidth">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Show details log file at: {{details.path_log }}
                </h2>
                <div class="editable-content" contenteditable="false">
                    {{ details.log_content }}
                </div>
                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeConfirmViewLog"> Ok </SecondaryButton>
                </div>
            </div>
        </Modal>
        <Modal :show="detailViewConfirm" @close="closeDetailSiteModal" :maxWidth="maxWidth">
            <form @submit.prevent="updateMySetting">
            <div class="p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Detail for site {{detailSite.site_name }}
                </h2>
                <ul class="text-white">
                    <li>Last path log: {{detailSite.last_path_log}}</li>
                    <li>SH Content Dir: {{detailSite.sh_content_dir}}</li>
                    <li>Last User Build: {{detailSite.last_user_build}}</li>
                    <li>Build: {{detailSite.last_build}}</li>
                    <li>Last Success: {{detailSite.last_build_success}}</li>
                    <li>Last Fail: {{detailSite.last_build_fail}}</li>
                    <li>Site Created Day: {{detailSite.created_at}}</li>
                    <li>Path source code: {{detailSite.path_source_code}}</li>
                    <li>Port PM2:
                        <input v-model="detailSite.port_pm2" type="text" style="width: 50%;" class="mt-5 text-black block border-gray-300 rounded-md shadow-sm"  />
                    </li>
                    <li>Api Endpoint URL
                        <input v-model="detailSite.api_endpoint_url" type="text"  style="width: 50%;" class="mt-1 text-black block border-gray-300 rounded-md shadow-sm"  />
                    </li>

                </ul>

                <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    <h4 style="color:white">Content SH</h4>
                    <div class="editable-content" contenteditable="false">
                        {{ detailSite.sh_content }}
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Update</button>
                    <SecondaryButton @click="closeDetailSiteModal"> Ok </SecondaryButton>
                </div>
            </div>
            </form>
        </Modal>
    </section>
</template>

<style scoped>
.editable-content {
    font-size: 0.7rem;
    font-family: "Courier New", Courier, "Lucida Console", Monaco, monospace;
    width: 100%; /* Full width */
    border: 1px solid #ccc;
    padding: 10px;
    background-color: #171717;
    word-wrap: break-word;
    white-space: pre-wrap; /* Preserve white spaces */
    color:white;
}
</style>
