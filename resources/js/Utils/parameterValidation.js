/**
 * Parameter Validation Rules
 * 
 * Mirrors backend Form Request validation rules for consistent validation.
 * Used with VeeValidate for client-side form validation.
 */

import { defineRule } from 'vee-validate';

// Custom rules for Parameters
defineRule('parameter_key', (value) => {
    if (!value || !value.length) {
        return 'Key is required';
    }

    if (value.length > 255) {
        return 'Key must not exceed 255 characters';
    }

    return true;
});

defineRule('parameter_value', (value) => {
    if (value === null || value === undefined || value === '') {
        return 'Value is required';
    }

    return true;
});

defineRule('parameter_type', (value) => {
    if (!value || !value.length) {
        return 'Type is required';
    }

    if (value.length > 100) {
        return 'Type must not exceed 100 characters';
    }

    return true;
});

/**
 * Validation schemas for Parameter operations
 */
export const parameterValidationSchemas = {
    // Create parameter schema (matches StoreParameterRequest)
    store: {
        key: 'required|parameter_key',
        value: 'required|parameter_value',
        type: 'required|parameter_type',
        description: '',
    },

    // Update parameter schema (matches UpdateParameterRequest)
    update: {
        key: 'required|parameter_key',
        value: 'required|parameter_value',
        type: 'required|parameter_type',
        description: '',
    },
};
