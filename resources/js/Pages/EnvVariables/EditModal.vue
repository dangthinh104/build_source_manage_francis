<template>
    <div class="fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-2xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Edit Env</h3>
                    
                    <!-- Template Pattern Generator -->
                    <div class="mb-6 bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4 shadow-sm">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <h4 class="text-sm font-semibold text-blue-900">Template Pattern Generator</h4>
                        </div>
                        <p class="text-xs text-blue-700 mb-4">Generate the pattern to use in your .env source file (.env.example, .env.prod, etc.)</p>
                        
                        <!-- Reference Scope Radio Buttons -->
                        <div class="mb-4">
                            <label class="block text-xs font-medium text-gray-700 mb-2">Reference Scope:</label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" :class="{ 'border-indigo-500 bg-indigo-50': referenceScope === 'global' }">
                                    <input type="radio" v-model="referenceScope" value="global" class="form-radio text-indigo-600 mr-2" />
                                    <span class="text-sm font-medium">Global</span>
                                </label>
                                <label class="flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" :class="{ 'border-indigo-500 bg-indigo-50': referenceScope === 'dynamicSite' }">
                                    <input type="radio" v-model="referenceScope" value="dynamicSite" class="form-radio text-indigo-600 mr-2" />
                                    <span class="text-sm font-medium">Dynamic Site</span>
                                </label>
                                <label class="flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" :class="{ 'border-indigo-500 bg-indigo-50': referenceScope === 'explicitSite' }">
                                    <input type="radio" v-model="referenceScope" value="explicitSite" class="form-radio text-indigo-600 mr-2" />
                                    <span class="text-sm font-medium">Explicit Site</span>
                                </label>
                                <label class="flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors" :class="{ 'border-indigo-500 bg-indigo-50': referenceScope === 'group' }">
                                    <input type="radio" v-model="referenceScope" value="group" class="form-radio text-indigo-600 mr-2" />
                                    <span class="text-sm font-medium">Build Group</span>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Conditional Input for Explicit Site -->
                        <div v-if="referenceScope === 'explicitSite'" class="mb-4">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Select Site:</label>
                            <select v-model="selectedExplicitSite" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                <option value="">-- Select a site --</option>
                                <option v-for="site in sites" :key="site.id" :value="site.site_name">{{ site.site_name }}</option>
                            </select>
                        </div>
                        
                        <!-- Conditional Input for Build Group -->
                        <div v-if="referenceScope === 'group'" class="mb-4">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Group Name:</label>
                            <input 
                                v-model="selectedGroupName" 
                                type="text" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm" 
                                placeholder="e.g., DEV_SERVER"
                            />
                        </div>
                        
                        <!-- Auto-Generated Pattern Preview -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Generated Pattern:</label>
                            <div class="flex gap-2">
                                <input 
                                    type="text" 
                                    :value="generatedPattern" 
                                    readonly 
                                    class="flex-1 px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-mono text-gray-800 cursor-text select-all" 
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
                            <p class="text-xs text-gray-500 mt-2">Use this pattern in your .env source files to reference this variable</p>
                        </div>
                    </div>
                    
                    <form @submit.prevent="updateVariable">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input v-model="form.variable_name" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" disabled />
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Value</label>
                            <Codemirror
                                v-model="form.variable_value"
                                placeholder="Enter value or JSON format"
                                :style="{ height: '250px' }"
                                :autofocus="false"
                                :indent-with-tab="true"
                                :tab-size="2"
                                :extensions="[json()]"
                                class="mt-1 border border-gray-300 rounded-md shadow-sm focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500 overflow-hidden"
                            />
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Group Name (Optional)</label>
                            <input 
                                v-model="form.group_name" 
                                type="text" 
                                :disabled="!!form.my_site_id"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm disabled:bg-gray-100 disabled:cursor-not-allowed" 
                                placeholder="e.g., DEV_SERVER"
                            />
                            <p class="text-xs text-gray-500 mt-1">For group-scoped variables</p>
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">My Site (Optional)</label>
                            <select 
                                v-model="form.my_site_id" 
                                :disabled="!!form.group_name"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm disabled:bg-gray-100 disabled:cursor-not-allowed"
                            >
                                <option :value="null">-- Select Site --</option>
                                <option v-for="site in sites" :key="site.id" :value="site.id">{{ site.site_name }}</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">For site-specific variables</p>
                        </div>
                        <div class="flex justify-end">
                            <button @click="$emit('close')" type="button" class="bg-gray-500 text-white px-4 py-2 rounded mr-2">Cancel</button>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, watch, ref, computed } from 'vue';
import { Codemirror } from 'vue-codemirror';
import { json } from '@codemirror/lang-json';
import { toast } from 'vue3-toastify';

const props = defineProps({
    variable: Object,
    sites: Array,
});

const emit = defineEmits(['close', 'update']);

const form = reactive({
    id: null,
    variable_name: '',
    variable_value: '',
    group_name: '',
    my_site_id: null,
});

// Template Pattern Generator State
const referenceScope = ref('global');
const selectedExplicitSite = ref('');
const selectedGroupName = ref('');

// Computed pattern based on selected scope and form data
const generatedPattern = computed(() => {
    const varName = form.variable_name || 'VAR_NAME';
    
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
        toast('Pattern copied to clipboard!', { type: 'success' });
    } catch (err) {
        toast('Failed to copy pattern', { type: 'error' });
    }
};

watch(
    () => props.variable,
    (newVal) => {
        if (newVal) {
            form.id = newVal.id;
            form.variable_name = newVal.variable_name;
            form.variable_value = newVal.variable_value;
            form.group_name = newVal.group_name || '';
            form.my_site_id = newVal.my_site_id || null;
        }
    },
    { immediate: true }
);

const updateVariable = () => {
    // Validation: ensure group and site are mutually exclusive
    if (form.group_name && form.my_site_id) {
        toast('A variable cannot be both group-scoped and site-specific. Please choose one or leave both empty.', { type: 'warning' });
        return;
    }
    emit('update', { ...form });
};
</script>
