<script setup>
import { ref, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ConfirmModal from '@/Components/ConfirmModal.vue';
import DangerButton from '@/Components/DangerButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import axios from 'axios';

const tokens = ref([]);
const displayingToken = ref(false);
const plainTextToken = ref('');
const justCreatedTokenName = ref('');
const confirmingTokenRevocation = ref(false);
const tokenBeingRevoked = ref(null);
const copySuccess = ref(false);

const createForm = useForm({
    name: '',
    expires_days: 90,
});

const getTokens = async () => {
    try {
        const response = await axios.get(route('api-tokens.index'));
        tokens.value = response.data.tokens;
    } catch (error) {
        console.error('Failed to fetch tokens', error);
    }
};

const createTokenAxios = () => {
    createForm.processing = true;
    axios.post(route('api-tokens.store'), {
        name: createForm.name,
        expires_days: createForm.expires_days
    }).then(response => {
        plainTextToken.value = response.data.token;
        justCreatedTokenName.value = createForm.name;
        displayingToken.value = true;
        createForm.reset();
        getTokens();
    }).catch(error => {
        if (error.response?.data?.errors) {
            createForm.errors = error.response.data.errors;
        } else {
             console.error('An error occurred:', error);
        }
    }).finally(() => {
        createForm.processing = false;
    });
};

const confirmRevokeToken = (token) => {
    tokenBeingRevoked.value = token;
    confirmingTokenRevocation.value = true;
};

const revokeToken = () => {
    if (!tokenBeingRevoked.value) return;

    axios.delete(route('api-tokens.destroy', tokenBeingRevoked.value.id))
        .then(() => {
            getTokens();
            confirmingTokenRevocation.value = false;
            tokenBeingRevoked.value = null;
        });
};

const closeTokenModal = () => {
    displayingToken.value = false;
    plainTextToken.value = '';
    justCreatedTokenName.value = '';
    copySuccess.value = false;
};

const copyToClipboard = () => {
    navigator.clipboard.writeText(plainTextToken.value).then(() => {
        copySuccess.value = true;
        setTimeout(() => copySuccess.value = false, 2000);
    });
};

onMounted(() => {
    getTokens();
});
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-slate-900">API Tokens</h2>
            <p class="mt-1 text-sm text-slate-600">
                Manage API tokens that allow third-party services to access the application on your behalf.
            </p>
        </header>

        <!-- Create Token Form -->
        <div class="space-y-6">
            <div class="grid grid-cols-6 gap-6">
                <!-- Token Name -->
                <div class="col-span-6 sm:col-span-4">
                    <InputLabel for="token_name" value="Token Name" />
                    <TextInput
                        id="token_name"
                        v-model="createForm.name"
                        type="text"
                        class="mt-1 block w-full"
                        autofocus
                        placeholder="e.g. My CI/CD Runner"
                    />
                    <InputError :message="createForm.errors.name" class="mt-2" />
                </div>
                
                <!-- Expiration Dropdown -->
                <div class="col-span-6 sm:col-span-2">
                    <InputLabel for="expires_days" value="Expiration" />
                    <select
                        id="expires_days"
                        v-model="createForm.expires_days"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    >
                        <option :value="30">30 days</option>
                        <option :value="60">60 days</option>
                        <option :value="90">90 days</option>
                        <option :value="null">Never</option>
                    </select>
                    <InputError :message="createForm.errors.expires_days" class="mt-2" />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :class="{ 'opacity-25': createForm.processing }" :disabled="createForm.processing" @click="createTokenAxios">
                    Create
                </PrimaryButton>
            </div>
        </div>

        <div class="border-t border-slate-200 my-8"></div>

        <!-- Manage Tokens -->
        <div>
            <h3 class="text-lg font-medium text-slate-900 pb-4">Active Tokens</h3>
            
            <div v-if="tokens.length > 0" class="space-y-4">
                <div v-for="token in tokens" :key="token.id" class="flex items-center justify-between border-b border-slate-100 pb-2 last:border-0">
                    <div>
                        <div class="font-medium text-slate-800">{{ token.name }}</div>
                        <div class="text-xs text-slate-500">
                            Created: {{ token.created_at }} | Expires: {{ token.expires_at }}
                        </div>
                        <div class="text-xs text-slate-500">
                            Last used: {{ token.last_used_at }}
                        </div>
                    </div>
                    <DangerButton @click="confirmRevokeToken(token)" class="ml-4">
                        Revoke
                    </DangerButton>
                </div>
            </div>

            <div v-else class="text-sm text-slate-500">
                No active API tokens found.
            </div>
        </div>

        <!-- Token Value Modal -->
        <DialogModal :show="displayingToken" @close="closeTokenModal">
            <template #title>
                API Token Created
            </template>

            <template #content>
                <div class="space-y-4">
                    <p class="text-sm text-slate-600">
                        Please copy your new API token. For your security, it will not be shown again.
                    </p>

                    <div class="mt-4 p-4 bg-slate-100 rounded-lg break-all font-mono text-sm border border-slate-200 text-slate-800 relative group">
                        {{ plainTextToken }}
                        
                        <button 
                            type="button" 
                            @click="copyToClipboard"
                            class="absolute top-2 right-2 p-1.5 bg-white rounded-md shadow-sm border border-gray-200 hover:bg-gray-50 text-gray-500 hover:text-gray-700 transition"
                            title="Copy to clipboard"
                        >
                            <span v-if="copySuccess" class="text-green-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                            <span v-else>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>
            </template>

            <template #footer>
                <SecondaryButton @click="closeTokenModal">
                    Close
                </SecondaryButton>
            </template>
        </DialogModal>

        <!-- Revoke Confirmation Modal -->
        <ConfirmModal
            :show="confirmingTokenRevocation"
            title="Revoke API Token"
            message="Are you sure you want to revoke this token? Any services using it will no longer be able to access the API."
            confirm-text="Revoke"
            @confirm="revokeToken"
            @cancel="confirmingTokenRevocation = false"
            @close="confirmingTokenRevocation = false"
        />
    </section>
</template>
