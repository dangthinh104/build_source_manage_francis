/**
 * Env Variable Validation Rules
 * 
 * Mirrors backend Form Request validation rules for consistent validation.
 * Used with VeeValidate for client-side form validation.
 */

import { defineRule } from 'vee-validate';

// Custom rules for Env Variables
defineRule('env_var_name', (value) => {
    if (!value || !value.length) {
        return 'Variable name is required';
    }

    if (value.length > 255) {
        return 'Variable name must not exceed 255 characters';
    }

    // Uppercase letters, numbers, and underscores only
    if (!/^[A-Z0-9_]+$/.test(value)) {
        return 'Variable name must be uppercase letters, numbers, and underscores only';
    }

    return true;
});

defineRule('env_var_value', (value) => {
    if (value === null || value === undefined || value === '') {
        return 'Variable value is required';
    }

    return true;
});

defineRule('group_name', (value) => {
    if (!value) return true; // nullable

    if (value.length > 255) {
        return 'Group name must not exceed 255 characters';
    }

    return true;
});

/**
 * Validation schemas for Env Variable operations
 */
export const envVariableValidationSchemas = {
    // Create variable schema (matches StoreEnvVariableRequest)
    store: {
        variable_name: 'required|env_var_name',
        variable_value: 'required|env_var_value',
        group_name: 'group_name',
        my_site_id: '',
    },

    // Update variable schema (matches UpdateEnvVariableRequest)
    update: {
        variable_value: 'required|env_var_value',
        group_name: 'group_name',
        my_site_id: '',
    },
};
