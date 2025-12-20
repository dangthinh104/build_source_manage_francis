<script setup>
/**
 * ConfirmProvider - Global provider component that renders the ConfirmModal
 * 
 * Usage: Include this component once at the app root level (in AuthenticatedLayout.vue)
 * 
 * Then in any child component:
 * ```js
 * import { useConfirm } from '@/Composables/useConfirm';
 * const { confirm } = useConfirm();
 * 
 * const handleDelete = async () => {
 *     if (await confirm({ title: 'Delete?', message: 'This cannot be undone' })) {
 *         // proceed
 *     }
 * };
 * ```
 */
import ConfirmModal from './ConfirmModal.vue';
import { useConfirm } from '@/Composables/useConfirm';

const { isOpen, modalProps, handleConfirm, handleCancel, closeModal } = useConfirm();
</script>

<template>
    <ConfirmModal
        :show="isOpen"
        :title="modalProps.title"
        :message="modalProps.message"
        :confirm-text="modalProps.confirmText"
        :cancel-text="modalProps.cancelText"
        :variant="modalProps.variant"
        @confirm="handleConfirm"
        @cancel="handleCancel"
        @close="closeModal"
    />
</template>
