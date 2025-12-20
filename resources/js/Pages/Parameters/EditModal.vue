<template>
    <Modal :show="true" @close="$emit('close')" max-width="lg">
        <form @submit.prevent="submit">
            <div class="p-6 space-y-5">
                <!-- Header -->
                <div class="flex items-center gap-3 pb-4 border-b border-slate-100">
                    <div class="w-10 h-10 rounded-full bg-primary-50 flex items-center justify-center">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">
                            {{ mode === 'create' ? 'Create Parameter' : 'Edit Parameter' }}
                        </h2>
                        <p class="text-sm text-slate-500">
                            {{ mode === 'create' ? 'Add a new application parameter' : `Editing "${parameter?.key}"` }}
                        </p>
                    </div>
                </div>

                <!-- Form Fields -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Key</label>
                        <input 
                            v-model="form.key" 
                            type="text" 
                            class="block w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-primary focus:ring-primary disabled:bg-slate-50 disabled:text-slate-500"
                            :disabled="mode === 'edit'"
                            placeholder="Enter parameter key"
                            required 
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Value</label>
                        <input 
                            v-model="form.value" 
                            type="text" 
                            class="block w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-primary focus:ring-primary"
                            placeholder="Enter parameter value"
                            required 
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Type</label>
                            <select 
                                v-model="form.type" 
                                class="block w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-primary focus:ring-primary"
                            >
                                <option value="">Select type</option>
                                <option value="string">String</option>
                                <option value="integer">Integer</option>
                                <option value="boolean">Boolean</option>
                                <option value="path">Path</option>
                                <option value="json">JSON</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                            <input 
                                v-model="form.description" 
                                type="text" 
                                class="block w-full rounded-xl border-slate-200 px-4 py-2.5 text-sm focus:border-primary focus:ring-primary"
                                placeholder="Optional description"
                            />
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 pt-4 border-t border-slate-100">
                    <SecondaryButton type="button" @click="$emit('close')">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton type="submit">
                        {{ mode === 'create' ? 'Create Parameter' : 'Update Parameter' }}
                    </PrimaryButton>
                </div>
            </div>
        </form>
    </Modal>
</template>

<script setup>
import { reactive, watch } from 'vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    parameter: Object,
    mode: {
        type: String,
        default: 'edit',
    },
});

const emit = defineEmits(['close', 'update', 'create']);

const form = reactive({
    id: null,
    key: '',
    value: '',
    type: '',
    description: '',
});

watch(() => props.parameter, (newVal) => {
    if (newVal) {
        form.id = newVal.id || null;
        form.key = newVal.key || '';
        form.value = newVal.value || '';
        form.type = newVal.type || '';
        form.description = newVal.description || '';
    }
}, { immediate: true });

const submit = () => {
    if (props.mode === 'create') {
        emit('create', { key: form.key, value: form.value, type: form.type, description: form.description });
    } else {
        emit('update', { id: form.id, key: form.key, value: form.value, type: form.type, description: form.description });
    }
};
</script>
