import { toast as vueToast } from 'vue3-toastify';

/**
 * Toast Helper Utility
 * 
 * Centralized toast notification utility for consistent toast handling across the application.
 * Wraps vue3-toastify with standardized configuration.
 * 
 * Usage:
 *   import { showToast } from '@/Utils/toastHelper';
 *   showToast.success('Operation successful!');
 *   showToast.error('Something went wrong');
 */

// Default toast configuration
const defaultConfig = {
    position: 'top-right',
    transition: 'slide',
    hideProgressBar: false,
    closeOnClick: true,
    pauseOnHover: true,
    draggable: true,
};

/**
 * Show a toast notification
 * @param {string} message - The message to display
 * @param {Object} options - Toast options (type, autoClose, etc.)
 */
export const showToast = (message, options = {}) => {
    const config = { ...defaultConfig, ...options };
    return vueToast(message, config);
};

/**
 * Toast helper with type-specific methods
 */
showToast.success = (message, options = {}) => {
    return vueToast(message, {
        ...defaultConfig,
        type: 'success',
        autoClose: 3000,
        ...options,
    });
};

showToast.error = (message, options = {}) => {
    return vueToast(message, {
        ...defaultConfig,
        type: 'error',
        autoClose: 5000,
        ...options,
    });
};

showToast.warning = (message, options = {}) => {
    return vueToast(message, {
        ...defaultConfig,
        type: 'warning',
        autoClose: 4000,
        ...options,
    });
};

showToast.info = (message, options = {}) => {
    return vueToast(message, {
        ...defaultConfig,
        type: 'info',
        autoClose: 3000,
        ...options,
    });
};

/**
 * Show a loading toast
 * Returns the toast ID that can be used to update or dismiss it
 */
showToast.loading = (message = 'Loading...', options = {}) => {
    return vueToast(message, {
        ...defaultConfig,
        type: 'info',
        autoClose: false,
        closeButton: false,
        ...options,
    });
};

/**
 * Update an existing toast
 * @param {number|string} toastId - The ID of the toast to update
 * @param {Object} options - New toast options
 */
showToast.update = (toastId, options = {}) => {
    return vueToast.update(toastId, options);
};

/**
 * Dismiss a specific toast or all toasts
 * @param {number|string} toastId - Optional toast ID. If not provided, dismisses all
 */
showToast.dismiss = (toastId) => {
    return vueToast.dismiss(toastId);
};

// Export the main toast function as default
export default showToast;
