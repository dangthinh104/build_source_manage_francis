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
                <div class="btn-primary px-6 py-4">
                    <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add Environment Variable
                    </h2>
                </div>
                <form @submit.prevent="storeVariable" class="p-6">
                    <!-- Template Pattern Generator -->
                    <div class="mb-6 bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 shadow-sm">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h4 class="text-sm font-semibold text-blue-900">Template Pattern Generator</h4>
                        </div>
                        <p class="text-xs text-blue-700 mb-4">Generate the pattern to use in your .env source file (.env.example, .env.prod, etc.)</p>
                        
                        <!-- Reference Scope Radio Buttons -->
                        <div class="mb-4">
                            <label class="block text-xs font-medium text-slate-700 mb-2">Reference Scope:</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label class="flex items-center px-3 py-2 bg-white border border-slate-300 rounded-lg cursor-pointer hover:bg-slate-50 transition-colors" :class="{ 'border-indigo-500 bg-indigo-50': referenceScope === 'global' }">
                                    <input type="radio" v-model="referenceScope" value="global" class="form-radio text-indigo-600 mr-2" />
                                    <span class="text-sm font-medium">Global</span>
                                </label>
                                <label class="flex items-center px-3 py-2 bg-white border border-slate-300 rounded-lg cursor-pointer hover:bg-slate-50 transition-colors" :class="{ 'border-indigo-500 bg-indigo-50': referenceScope === 'dynamicSite' }">
                                    <input type="radio" v-model="referenceScope" value="dynamicSite" class="form-radio text-indigo-600 mr-2" />
                                    <span class="text-sm font-medium">Dynamic Site</span>
                                </label>
                                <label class="flex items-center px-3 py-2 bg-white border border-slate-300 rounded-lg cursor-pointer hover:bg-slate-50 transition-colors" :class="{ 'border-indigo-500 bg-indigo-50': referenceScope === 'explicitSite' }">
                                    <input type="radio" v-model="referenceScope" value="explicitSite" class="form-radio text-indigo-600 mr-2" />
                                    <span class="text-sm font-medium">Explicit Site</span>
                                </label>
                                <label class="flex items-center px-3 py-2 bg-white border border-slate-300 rounded-lg cursor-pointer hover:bg-slate-50 transition-colors" :class="{ 'border-indigo-500 bg-indigo-50': referenceScope === 'group' }">
                                    <input type="radio" v-model="referenceScope" value="group" class="form-radio text-indigo-600 mr-2" />
                                    <span class="text-sm font-medium">Build Group</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Conditional Input for Explicit Site -->
                        <div v-if="referenceScope === 'explicitSite'" class="mb-4">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Select Site:</label>
                            <select v-model="selectedExplicitSite" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                <option value="">-- Select a site --</option>
                                <option v-for="site in sites" :key="site.id" :value="site.site_name">{{ site.site_name }}</option>
                            </select>
                        </div>
                        
                        <!-- Conditional Input for Build Group -->
                        <div v-if="referenceScope === 'group'" class="mb-4">
                            <label class="block text-xs font-medium text-slate-700 mb-1">Group Name:</label>
                            <input 
                                v-model="selectedGroupName" 
                                type="text" 
                                class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm" 
                                placeholder="e.g., DEV_SERVER"
                            />
                        </div>
                        
                        <!-- Auto-Generated Pattern Preview -->
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-1">Generated Pattern:</label>
                            <div class="flex gap-2">
                                <input 
                                    type="text" 
                                    :value="generatedPattern" 
                                    readonly 
                                    class="flex-1 px-3 py-2 bg-white border border-slate-300 rounded-lg text-sm font-mono text-slate-800 cursor-text select-all" 
                                />
                                <button 
                                    @click="copyPattern" 
                                    type="button"
                                    class="px-4 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-700 transition-colors duration-200 flex items-center gap-1.5"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    Copy
                                </button>
                            </div>
                            <p class="text-xs text-slate-500 mt-2">Use this pattern in your .env source files to reference this variable</p>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Variable Name</label>
                            <input 
                                v-model="newVariable.variable_name" 
                                type="text" 
                                :class="['w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-indigo-400/50 transition-all duration-200 hover:border-slate-400', validationErrors.variable_name ? 'border-red-500' : 'border-slate-300 focus:border-indigo-400']"
                                placeholder="e.g., APP_DEBUG"
                                @blur="validateForm"
                            />
                            <p v-if="validationErrors.variable_name" class="mt-1 text-sm text-red-600">{{ validationErrors.variable_name }}</p>
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
                                :class="['border rounded-xl focus-within:ring-2 focus-within:ring-indigo-400/50 transition-all duration-200 hover:border-slate-400 overflow-hidden', validationErrors.variable_value ? 'border-red-500' : 'border-slate-300 focus-within:border-indigo-400']"
                            />
                            <p v-if="validationErrors.variable_value" class="mt-1 text-sm text-red-600">{{ validationErrors.variable_value }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Group Name (Optional)</label>
                            <input 
                                v-model="newVariable.group_name" 
                                type="text" 
                                :disabled="!!newVariable.my_site_id"
                                class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-400/50 focus:border-indigo-400 transition-all duration-200 hover:border-slate-400 disabled:bg-slate-100 disabled:cursor-not-allowed" 
                                placeholder="e.g., DEV_SERVER"
                            />
                            <p class="text-xs text-slate-500 mt-1">For group-scoped variables</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">My Site (Optional)</label>
                            <select 
                                v-model="newVariable.my_site_id" 
                                :disabled="!!newVariable.group_name"
                                class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-400/50 focus:border-indigo-400 transition-all duration-200 hover:border-slate-400 disabled:bg-slate-100 disabled:cursor-not-allowed"
                            >
                                <option :value="null">-- Select Site --</option>
                                <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.site_name }}</option>
                            </select>
                            <p class="text-xs text-slate-500 mt-1">For site-specific variables</p>
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
                                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Scope</th>
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
                                <td class="px-6 py-4">
                                    <span v-if="variable.group_name" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                        Group: {{ variable.group_name }}
                                    </span>
                                    <span v-else-if="variable.my_site_id" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                        </svg>
                                        Site: {{ variable.site_name }}
                                    </span>
                                    <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Global
                                    </span>
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
                                <td colspan="4" class="px-6 py-12 text-center">
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
                :sites="sites"
                @close="isEditModalOpen = false"
                @update="updateVariable"
            />
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import Pagination from '@/Components/Pagination.vue';
import { reactive, ref, watch, computed } from 'vue';
import debounce from 'lodash/debounce';
import axios from 'axios';
import {Head, usePage, router} from '@inertiajs/vue3';
import EditModal from './EditModal.vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Codemirror } from 'vue-codemirror';
import { json } from '@codemirror/lang-json';
import { useConfirm } from '@/Composables/useConfirm';
import { showToast } from '@/Utils/toastHelper';
import { wrapResponse } from '@/Utils/apiResponse';
import '@/Utils/envVariableValidation'; // Import validation ruleslidation'; // Import validation rules

const { confirm } = useConfirm();

const props = defineProps({
    envVariables: Object,
    filters: Object,
    sites: Array,
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
    group_name: '',
    my_site_id: null,
});

const validationErrors = ref({});

// Simple validation function using validation rules
const validateForm = () => {
    const errors = {};
    
    // Variable name validation
    if (!newVariable.variable_name || !newVariable.variable_name.trim()) {
        errors.variable_name = 'Variable name is required';
    } else if (newVariable.variable_name.length > 255) {
        errors.variable_name = 'Variable name must not exceed 255 characters';
    } else if (!/^[A-Z0-9_]+$/.test(newVariable.variable_name)) {
        errors.variable_name = 'Variable name must be uppercase letters, numbers, and underscores only';
    }
    
    // Variable value validation
    if (!newVariable.variable_value || newVariable.variable_value === '') {
        errors.variable_value = 'Variable value is required';
    }
    
    // Group name validation (optional)
    if (newVariable.group_name && newVariable.group_name.length > 255) {
        errors.group_name = 'Group name must not exceed 255 characters';
    }
    
    validationErrors.value = errors;
    return Object.keys(errors).length === 0;
};

// Template Pattern Generator State
const referenceScope = ref('global');
const selectedExplicitSite = ref('');
const selectedGroupName = ref('');

// Computed pattern based on selected scope and form data
const generatedPattern = computed(() => {
    const varName = newVariable.variable_name || 'VAR_NAME';
    
    switch (referenceScope.value) {
        case 'global':
            return `###${varName}`;
        case 'dynamicSite':
            return `###SITE_NAME###${varName}`;
        case 'explicitSite':
            const siteName = selectedExplicitSite.value || 'SITE_NAME_HERE';
            return `###${siteName}###${varName}`;
        case 'group':
            const groupName = selectedGroupName.value || 'GROUP_NAME_HERE';
            return `###${groupName}###${varName}`;
        default:
            return `###${varName}`;
    }
});

// Copy pattern to clipboard
const copyPattern = async () => {
    try {
        await navigator.clipboard.writeText(generatedPattern.value);
        showToast.success('Pattern copied to clipboard!');
    } catch (err) {
        showToast.error('Failed to copy pattern');
    }
};


const isEditModalOpen = ref(false);
const selectedVariable = ref(null);

const storeVariable = async () => {
    try {
        // Frontend validation
        const isValid = validateForm();
        if (!isValid) {
            showToast.error('Please fix validation errors');
            return;
        }
        
        // Validation: ensure group and site are mutually exclusive (though FormRequest will also check)
        if (newVariable.group_name && newVariable.my_site_id) {
            showToast.warning('A variable cannot be both group-scoped and site-specific. Please choose one or leave both empty.');
            return;
        }

        const response = await wrapResponse(
            axios.post('/envVariables', newVariable)
        );
        
        response.handleToast(showToast);
        
        if (response.isSuccess) {
            newVariable.variable_name = '';
            newVariable.variable_value = '';
            newVariable.group_name = '';
            newVariable.my_site_id = null;
            router.reload({ only: ['envVariables'] });
        }
    } catch (error) {
        console.error('Error when adding env:', error);
        showToast.error('Error when adding variable');
    }
};

const editVariable = (variable) => {
    selectedVariable.value = { ...variable };
    isEditModalOpen.value = true;
};

const updateVariable = async (updatedVariable) => {
    try {
        const response = await wrapResponse(
            axios.put(`/envVariables/${updatedVariable.id}`, updatedVariable)
        );
        
        response.handleToast(showToast);
        
        if (response.isSuccess) {
            isEditModalOpen.value = false;
            router.reload({ only: ['envVariables'] });
        }
    } catch (error) {
        console.error('Error when editing:', error);
        showToast.error('Error when updating variable');
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
        const response = await wrapResponse(
            axios.delete(`/envVariables/${id}`)
        );
        
        response.handleToast(showToast);
        
        if (response.isSuccess) {
            router.reload({ only: ['envVariables'] });
        }
    } catch (error) {
        console.error('Error when deleting env:', error);
        showToast.error('Error when deleting variable');
    }
};
</script>

<style scoped>
/* Các lớp Tailwind CSS đã được sử dụng inline */
</style>

