<template>
    <Form @submit="handleSubmit" v-slot="{ meta, errors, values }" :validation-schema="validationSchema" :initial-values="formData">
        <div class="space-y-4">
            <h2 class="text-lg font-semibold text-slate-900">
                {{ isEdit ? `Edit Site · ${formData.site_name}` : 'Create New Site' }}
            </h2>
            
            <!-- Site Name -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Site Name <span class="text-red-500">*</span>
                </label>
                <Field
                    name="site_name"
                    v-slot="{ field, errors: fieldErrors }"
                >
                    <input
                        v-bind="field"
                        type="text"
                        placeholder="example.com"
                        :class="[
                            'block w-full rounded-xl border-slate-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary',
                            fieldErrors.length > 0 ? 'border-red-300' : ''
                        ]"
                    />
                    <span v-if="fieldErrors.length > 0" class="text-red-500 text-xs mt-1 block">
                        {{ fieldErrors[0] }}
                    </span>
                </Field>
                <small class="text-slate-500 text-xs">Only letters, numbers, hyphens, underscores, and dots</small>
            </div>
            
            <!-- Path Source Code -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Source Code Path <span class="text-red-500">*</span>
                </label>
                <Field
                    name="path_source_code"
                    v-slot="{ field, errors: fieldErrors }"
                >
                    <input
                        v-bind="field"
                        type="text"
                        placeholder="/var/www/html/myproject"
                        :class="[
                            'block w-full rounded-xl border-slate-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary',
                            fieldErrors.length > 0 ? 'border-red-300' : ''
                        ]"
                    />
                    <span v-if="fieldErrors.length > 0" class="text-red-500 text-xs mt-1 block">
                        {{ fieldErrors[0] }}
                    </span>
                </Field>
                <small class="text-slate-500 text-xs">Absolute path to your project directory</small>
            </div>
            
            <!-- Include PM2 -->
            <div class="flex items-center gap-2">
                <Field
                    name="include_pm2"
                    type="checkbox"
                    v-slot="{ field }"
                >
                    <input
                        v-bind="field"
                        type="checkbox"
                        :value="true"
                        class="rounded border-slate-300 text-primary focus:ring-primary"
                    />
                </Field>
                <label class="text-sm text-slate-700">Enable PM2 Process Manager</label>
            </div>
            
            <!-- Port PM2 (conditional) -->
            <div v-if="values.include_pm2">
                <label class="block text-sm font-medium text-slate-700 mb-1">PM2 Port</label>
                <Field
                    name="port_pm2"
                    v-slot="{ field, errors: fieldErrors }"
                >
                    <input
                        v-bind="field"
                        type="number"
                        placeholder="3000"
                        :class="[
                            'block w-full rounded-xl border-slate-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary',
                            fieldErrors.length > 0 ? 'border-red-300' : ''
                        ]"
                    />
                    <span v-if="fieldErrors.length > 0" class="text-red-500 text-xs mt-1 block">
                        {{ fieldErrors[0] }}
                    </span>
                </Field>
                <small class="text-slate-500 text-xs">Port number between 1024-65535</small>
            </div>
            
            <!-- API Endpoint URL -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">API Endpoint URL (Optional)</label>
                <Field
                    name="api_endpoint_url"
                    v-slot="{ field, errors: fieldErrors }"
                >
                    <input
                        v-bind="field"
                        type="url"
                        placeholder="https://api.example.com"
                        :class="[
                            'block w-full rounded-xl border-slate-200 px-3 py-2 text-sm focus:border-primary focus:ring-primary',
                            fieldErrors.length > 0 ? 'border-red-300' : ''
                        ]"
                    />
                    <span v-if="fieldErrors.length > 0" class="text-red-500 text-xs mt-1 block">
                        {{ fieldErrors[0] }}
                    </span>
                </Field>
            </div>
            
            <!-- Form Actions -->
            <div class="flex justify-end gap-3 pt-2">
                <button
                    type="button"
                    @click="$emit('cancel')"
                    class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                >
                    Cancel
                </button>
                
                <button
                    type="submit"
                    :disabled="!meta.valid || isSubmitting"
                    :class="[
                        'px-4 py-2 text-sm font-medium text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary',
                        (!meta.valid || isSubmitting) ? 'bg-slate-400 cursor-not-allowed' : 'bg-primary hover:bg-primary-dark'
                    ]"
                >
                    <span v-if="isSubmitting">
                        <i class="fa fa-spinner fa-spin"></i> Submitting...
                    </span>
                    <span v-else>
                        {{ isEdit ? 'Update Site' : 'Create Site' }}
                    </span>
                </button>
            </div>
        </div>
    </Form>
</template>

<script setup>
import { ref, watch } from 'vue';
import { Form, Field } from 'vee-validate';
import { router } from '@inertiajs/vue3';
import { showToast } from '@/Utils/toastHelper';
import { mySiteValidationSchemas } from '@/Utils/validationRules';

const props = defineProps({
    site: {
        type: Object,
        default: null,
    },
    isEdit: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['cancel', 'success']);

const isSubmitting = ref(false);

// Form data
const formData = ref({
    site_name: props.site?.site_name || '',
    path_source_code: props.site?.path_source_code || '',
    include_pm2: props.site?.port_pm2 ? true : false,
    port_pm2: props.site?.port_pm2 || null,
    api_endpoint_url: props.site?.api_endpoint_url || '',
});

// Watch for site prop changes (edit mode)
watch(() => props.site, (newSite) => {
    if (newSite) {
        formData.value = {
            id: newSite.id,
            site_name: newSite.site_name,
            path_source_code: newSite.path_source_code,
            include_pm2: !!newSite.port_pm2,
            port_pm2: newSite.port_pm2,
            api_endpoint_url: newSite.api_endpoint_url || '',
        };
    }
}, { immediate: true });

// Get validation schema based on mode
const validationSchema = props.isEdit 
    ? mySiteValidationSchemas.update 
    : mySiteValidationSchemas.store;

// Handle form submission
const handleSubmit = (values) => {
    isSubmitting.value = true;
    
    const routeName = props.isEdit ? 'my_site.update' : 'my_site.store';
    
    router.post(route(routeName), values, {
        preserveScroll: true,
        onSuccess: () => {
            emit('success');
            isSubmitting.value = false;
        },
        onError: (errors) => {
            console.error('Validation errors:', errors);
            showToast.error('Please check the form for errors');
            isSubmitting.value = false;
        },
    });
};
</script>

<style scoped>
/* Styles match existing app theme */
</style>
