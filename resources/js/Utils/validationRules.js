/**
 * MySite Validation Rules
 * 
 * Mirrors backend Form Request validation rules for consistent validation.
 * Used with VeeValidate for client-side form validation.
 */

import { defineRule } from 'vee-validate';
import { required, email, min, max, regex, url, integer, numeric } from '@vee-validate/rules';

// Register built-in VeeValidate rules
defineRule('required', required);
defineRule('email', email);
defineRule('min', min);
defineRule('max', max);
defineRule('regex', regex);
defineRule('url', url);
defineRule('integer', integer);
defineRule('numeric', numeric);

// Custom rules
defineRule('site_name', (value) => {
    if (!value || !value.length) {
        return 'Site name is required';
    }

    if (value.length > 255) {
        return 'Site name must not exceed 255 characters';
    }

    // Only alphanumeric, hyphens, underscores, and dots
    if (!/^[a-zA-Z0-9\-_.]+$/.test(value)) {
        return 'Site name can only contain letters, numbers, hyphens, underscores, and dots';
    }

    return true;
});

defineRule('port_range', (value) => {
    if (!value) return true; // nullable

    const port = parseInt(value);

    if (isNaN(port)) {
        return 'Port must be a number';
    }

    if (port < 1024) {
        return 'Port must be at least 1024';
    }

    if (port > 65535) {
        return 'Port must not exceed 65535';
    }

    return true;
});

defineRule('path', (value) => {
    if (!value || !value.length) {
        return 'Path is required';
    }

    if (value.length > 500) {
        return 'Path must not exceed 500 characters';
    }

    return true;
});

/**
 * Validation schemas for MySite operations
 */
export const mySiteValidationSchemas = {
    // Create site schema (matches StoreSiteRequest)
    store: {
        site_name: 'required|site_name',
        path_source_code: 'required|path',
        include_pm2: 'required',
        port_pm2: 'port_range',
        api_endpoint_url: 'url',
    },

    // Update site schema (matches UpdateSiteRequest)
    update: {
        id: 'required|integer',
        site_name: 'required|site_name',
        port_pm2: 'port_range',
        api_endpoint_url: 'url',
    },

    // Build site schema (matches BuildSiteRequest)
    build: {
        site_id: 'required|integer',
    },

    // Delete site schema (matches DeleteSiteRequest)
    delete: {
        site_id: 'required|integer',
    },

    // Get site data schema (matches GetSiteDataRequest)
    getSiteData: {
        site_id: 'required|integer',
    },

    // View log file schema (matches ViewLogFileRequest)
    viewLog: {
        site_id: 'required|integer',
        log_path: 'required',
    },
};

/**
 * Custom error messages
 */
export const validationMessages = {
    required: '{field} is required',
    email: '{field} must be a valid email',
    min: '{field} must be at least {min} characters',
    max: '{field} must not exceed {max} characters',
    integer: '{field} must be a number',
    url: '{field} must be a valid URL',
    regex: '{field} format is invalid',
};
