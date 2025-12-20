<template>
    <Head title="Env Manager" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Env Manager</h2>
        </template>


        <div class="space-y-6">
            <!-- Success Message -->
            <div v-if="flash.success" class="bg-gradient-to-r from-green-500 to-emerald-500 text-white px-6 py-4 rounded-2xl shadow-lg shadow-green-500/30 flex items-center gap-3 fade-in">
                <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium">{{ flash.success }}</span>
            </div>

            <!-- Add New Variable Card -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-500 to-purple-500 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Environment Variable
                    </h2>
                </div>
                <form @submit.prevent="storeVariable" class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Variable Name</label>
                            <input 
                                v-model="newVariable.variable_name" 
                                type="text" 
                                class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-400/50 focus:border-indigo-400 transition-all duration-200 hover:border-slate-400" 
                                placeholder="e.g., APP_DEBUG"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Variable Value</label>
                            <Codemirror
                                v-model="newVariable.variable_value"
                                placeholder="e.g., true or JSON format"
                                :style="{ height: '250px' }"
                                :autofocus="false"
                                :indent-with-tab="true"
                                :tab-size="2"
                                :extensions="[json()]"
                                class="border border-slate-300 rounded-xl focus-within:ring-2 focus-within:ring-indigo-400/50 focus-within:border-indigo-400 transition-all duration-200 hover:border-slate-400 overflow-hidden"
                            />
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 btn-primary text-white rounded-xl font-semibold shadow-lg transition-all duration-300 hover:shadow-xl hover:-translate-y-0.5">
                            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            <span>Add Variable</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Variables List Card -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-slate-50 to-slate-100 px-6 py-4 border-b border-slate-200">
                    <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
                        <svg class="h-5 w-5 shrink-0 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Environment Variables
                        <span class="ml-auto text-sm font-normal text-slate-600">{{ envVariables.data.length }} variables</span>
                    </h2>
                </div>
                <!-- Mobile Card View -->
                <div class="block md:hidden divide-y divide-slate-100">
                    <div 
                        v-for="variable in envVariables.data" 
                        :key="'mobile-' + variable.id"
                        class="p-4 space-y-3"
                    >
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 shrink-0 rounded-lg bg-indigo-100 flex items-center justify-center">
                                <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-semibold text-slate-900 font-mono text-sm">{{ variable.variable_name }}</p>
                            </div>
                        </div>
                        <div class="bg-slate-50 p-3 rounded-xl">
                            <p class="text-xs text-slate-500 mb-1">Value</p>
                            <p class="text-sm text-slate-700 font-mono break-all">{{ variable.variable_value }}</p>
                        </div>
                        <div class="flex gap-2">
                            <button
                                @click="editVariable(variable)"
                                class="flex-1 py-2.5 text-center text-xs font-semibold text-indigo-600 bg-indigo-50 rounded-xl hover:bg-indigo-100 transition-colors"
                            >
                                Edit
                            </button>
                            <button
                                @click="deleteVariable(variable.id)"
                                class="flex-1 py-2.5 text-center text-xs font-semibold text-rose-600 bg-rose-50 rounded-xl hover:bg-rose-100 transition-colors"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                    <div v-if="envVariables.data.length === 0" class="p-8 text-center">
                        <svg class="h-12 w-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <p class="text-sm text-slate-500">No environment variables</p>
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">
                                    Variable Name
                                    <input
                                        v-model="searchParams.variable_name"
                                        type="text"
                                        placeholder="Filter Name..."
                                        class="mt-2 w-full px-2 py-1 text-xs border border-slate-200 rounded-md focus:border-primary focus:ring-1 focus:ring-primary font-normal"
                                    />
                                </th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Value</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <tr v-for="variable in envVariables.data" :key="variable.id" class="hover:bg-slate-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 shrink-0 rounded-lg bg-indigo-100 flex items-center justify-center mr-3">
                                            <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                            </svg>
                                        </div>
                                        <span class="text-sm font-semibold text-slate-900 font-mono">{{ variable.variable_name }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-slate-600 font-mono bg-slate-100 px-3 py-1 rounded-lg">{{ variable.variable_value }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="editVariable(variable)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg font-medium hover:bg-indigo-100 transition-all duration-200"
                                        >
                                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            <span>Edit</span>
                                        </button>
                                        <button
                                            @click="deleteVariable(variable.id)"
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
                            <tr v-if="envVariables.data.length === 0">
                                <td colspan="3" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-500">
                                        <svg class="h-12 w-12 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        <p class="text-sm font-medium">No environment variables</p>
                                        <p class="text-xs mt-1">Add your first variable above</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!-- Pagination -->
                <div class="px-6 py-4 border-t border-slate-100 bg-slate-50" v-if="envVariables.links && envVariables.links.length > 0">
                    <Pagination :links="envVariables.links" />
                </div>
            </div>

            <!-- Edit Modal -->
            <EditModal
                v-if="isEditModalOpen"
                :variable="selectedVariable"
                @close="isEditModalOpen = false"
                @update="updateVariable"
            />
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import Pagination from '@/Components/Pagination.vue';
import { reactive, ref, watch } from 'vue';
import debounce from 'lodash/debounce';
import axios from 'axios';
import {Head, usePage, router} from '@inertiajs/vue3';
import EditModal from './EditModal.vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Codemirror } from 'vue-codemirror';
import { json } from '@codemirror/lang-json';
import { useConfirm } from '@/Composables/useConfirm';
import { toast } from 'vue3-toastify';

const { confirm } = useConfirm();

const props = defineProps({
    envVariables: Object,
    filters: Object,
});

const searchParams = reactive({
    variable_name: props.filters?.variable_name || '',
});

watch(searchParams, debounce((value) => {
    router.get(route('envVariables.index'), value, {
        preserveState: true,
        preserveScroll: true,
        replace: true
    });
}, 300));

// Safely access the flash messages
const page = usePage();
const flash = page.props?.flash || {}; // Set a default empty object if flash is undefined


const newVariable = reactive({
    variable_name: '',
    variable_value: '',
});

const isEditModalOpen = ref(false);
const selectedVariable = ref(null);

const storeVariable = async () => {
    try {
        const response = await axios.post('/envVariables', newVariable);
        if (response.data.success) {
            newVariable.variable_name = '';
            newVariable.variable_value = '';
            router.reload({ only: ['envVariables'] });
        } else {
            alert('Failed to add variable: ' + (response.data.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error when add env:', error);
        alert('Error when adding variable: ' + (error.response?.data?.message || error.message));
    }
};

const editVariable = (variable) => {
    selectedVariable.value = { ...variable };
    isEditModalOpen.value = true;
};

const updateVariable = async (updatedVariable) => {
    try {
        const response = await axios.put(`/envVariables/${updatedVariable.id}`, updatedVariable);
        if (response.data.success) {
            isEditModalOpen.value = false;
            router.reload({ only: ['envVariables'] });
        } else {
            alert('Failed to update variable: ' + (response.data.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error when edit:', error);
        alert('Error when updating variable: ' + (error.response?.data?.message || error.message));
    }
};

const deleteVariable = async (id) => {
    const confirmed = await confirm({
        title: 'Delete Environment Variable?',
        message: 'Are you sure you want to delete this variable?',
        confirmText: 'Delete',
        variant: 'danger',
    });
    if (!confirmed) return;
    
    try {
        const response = await axios.delete(`/envVariables/${id}`);
        if (response.data.success) {
            toast('Variable deleted', { type: 'success' });
            router.reload({ only: ['envVariables'] });
        } else {
            toast('Failed to delete variable: ' + (response.data.message || 'Unknown error'), { type: 'error' });
        }
    } catch (error) {
        console.error('Error when delete env:', error);
        toast('Error when deleting variable: ' + (error.response?.data?.message || error.message), { type: 'error' });
    }
};
</script>

<style scoped>
/* Các lớp Tailwind CSS đã được sử dụng inline */
</style>

