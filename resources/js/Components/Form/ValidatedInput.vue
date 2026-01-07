<template>
    <div class="form-group">
        <label v-if="label" :for="name" class="form-label">
            {{ label }}
            <span v-if="required" class="text-danger">*</span>
        </label>
        
        <Field
            :id="name"
            :name="name"
            :type="type"
            :placeholder="placeholder"
            :class="inputClass"
            v-slot="{ field, errors, meta }"
        >
            <input
                v-bind="field"
                :type="type"
                :placeholder="placeholder"
                :class="[
                    inputClass,
                    {
                        'is-invalid': errors.length > 0 && meta.touched,
                        'is-valid': meta.valid && meta.touched && !errors.length
                    }
                ]"
            />
            
            <div v-if="errors.length > 0 && meta.touched" class="invalid-feedback d-block">
                {{ errors[0] }}
            </div>
            
            <small v-if="helper && !errors.length" class="form-text text-muted">
                {{ helper }}
            </small>
        </Field>
    </div>
</template>

<script setup>
import { Field } from 'vee-validate';

defineProps({
    name: {
        type: String,
        required: true,
    },
    label: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'text',
    },
    placeholder: {
        type: String,
        default: '',
    },
    helper: {
        type: String,
        default: '',
    },
    required: {
        type: Boolean,
        default: false,
    },
    inputClass: {
        type: String,
        default: 'form-control',
    },
});
</script>

<style scoped>
.is-invalid {
    border-color: #dc3545 !important;
}

.is-valid {
    border-color: #28a745 !important;
}

.invalid-feedback {
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.form-label {
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.text-danger {
    color: #dc3545;
}
</style>
