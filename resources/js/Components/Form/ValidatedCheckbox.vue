<template>
    <div class="form-check">
        <Field
            :id="name"
            :name="name"
            :type="type"
            :value="value"
            v-slot="{ field, errors }"
        >
            <input
                v-bind="field"
                :type="type"
                :value="value"
                :class="['form-check-input', { 'is-invalid': errors.length > 0 }]"
            />
            
            <label :for="name" class="form-check-label">
                {{ label }}
            </label>
            
            <div v-if="errors.length > 0" class="invalid-feedback d-block">
                {{ errors[0] }}
            </div>
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
        required: true,
    },
    type: {
        type: String,
        default: 'checkbox',
        validator: (value) => ['checkbox', 'radio'].includes(value),
    },
    value: {
        type: [String, Number, Boolean],
        default: true,
    },
});
</script>

<style scoped>
.is-invalid {
    border-color: #dc3545 !important;
}

.invalid-feedback {
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}
</style>
