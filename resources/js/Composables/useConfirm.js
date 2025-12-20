import { ref, readonly, markRaw } from 'vue';

/**
 * Global state for confirm modal
 */
const isOpen = ref(false);
const modalProps = ref({
    title: 'Confirm Action',
    message: 'Are you sure?',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    variant: 'danger',
});

let resolvePromise = null;
let rejectPromise = null;

/**
 * Composable for programmatic confirm dialogs
 * 
 * Usage:
 * ```js
 * import { useConfirm } from '@/Composables/useConfirm';
 * 
 * const { confirm } = useConfirm();
 * 
 * const handleDelete = async () => {
 *     const confirmed = await confirm({
 *         title: 'Delete Item?',
 *         message: 'This action cannot be undone.',
 *         confirmText: 'Delete',
 *         variant: 'danger',
 *     });
 *     
 *     if (confirmed) {
 *         // proceed with deletion
 *     }
 * };
 * ```
 */
export function useConfirm() {
    /**
     * Open confirm modal and return a promise
     * @param {Object} options - Modal configuration
     * @param {string} options.title - Modal title
     * @param {string} options.message - Modal message
     * @param {string} options.confirmText - Confirm button text
     * @param {string} options.cancelText - Cancel button text
     * @param {string} options.variant - 'danger' | 'warning' | 'primary'
     * @returns {Promise<boolean>} - Resolves to true if confirmed, false if cancelled
     */
    const confirm = (options = {}) => {
        return new Promise((resolve, reject) => {
            modalProps.value = {
                title: options.title || 'Confirm Action',
                message: options.message || 'Are you sure you want to proceed?',
                confirmText: options.confirmText || 'Confirm',
                cancelText: options.cancelText || 'Cancel',
                variant: options.variant || 'danger',
            };

            resolvePromise = resolve;
            rejectPromise = reject;
            isOpen.value = true;
        });
    };

    /**
     * Handle confirm action
     */
    const handleConfirm = () => {
        isOpen.value = false;
        if (resolvePromise) {
            resolvePromise(true);
            resolvePromise = null;
            rejectPromise = null;
        }
    };

    /**
     * Handle cancel action
     */
    const handleCancel = () => {
        isOpen.value = false;
        if (resolvePromise) {
            resolvePromise(false);
            resolvePromise = null;
            rejectPromise = null;
        }
    };

    /**
     * Close modal (same as cancel)
     */
    const closeModal = () => {
        handleCancel();
    };

    return {
        // State
        isOpen: readonly(isOpen),
        modalProps: readonly(modalProps),

        // Methods
        confirm,
        handleConfirm,
        handleCancel,
        closeModal,
    };
}

// Export for direct import
export default useConfirm;
