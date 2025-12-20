<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import Modal from './Modal.vue';
import SecondaryButton from './SecondaryButton.vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        default: 'Confirm Action',
    },
    message: {
        type: String,
        default: 'Are you sure you want to proceed?',
    },
    confirmText: {
        type: String,
        default: 'Confirm',
    },
    cancelText: {
        type: String,
        default: 'Cancel',
    },
    variant: {
        type: String,
        default: 'danger', // 'danger', 'warning', 'primary'
    },
    processing: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['confirm', 'cancel', 'close']);

const variantClasses = {
    danger: {
        icon: 'bg-red-100',
        iconColor: 'text-red-600',
        button: 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
    },
    warning: {
        icon: 'bg-amber-100',
        iconColor: 'text-amber-600',
        button: 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500',
    },
    primary: {
        icon: 'bg-primary-50',
        iconColor: 'text-primary',
        button: 'bg-primary hover:bg-primary/90 focus:ring-primary',
    },
};

const currentVariant = () => variantClasses[props.variant] || variantClasses.danger;

const confirm = () => {
    emit('confirm');
};

const cancel = () => {
    emit('cancel');
    emit('close');
};
</script>

<template>
    <Modal :show="show" max-width="md" @close="cancel">
        <div class="p-6">
            <!-- Icon -->
            <div class="flex justify-center mb-4">
                <div 
                    class="w-14 h-14 rounded-full flex items-center justify-center"
                    :class="currentVariant().icon"
                >
                    <!-- Warning/Danger icon -->
                    <svg 
                        v-if="variant === 'danger' || variant === 'warning'" 
                        class="w-7 h-7" 
                        :class="currentVariant().iconColor"
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <!-- Question/Primary icon -->
                    <svg 
                        v-else 
                        class="w-7 h-7" 
                        :class="currentVariant().iconColor"
                        fill="none" 
                        stroke="currentColor" 
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>

            <!-- Content -->
            <div class="text-center mb-6">
                <h3 class="text-xl font-semibold text-slate-900 mb-2">
                    {{ title }}
                </h3>
                <p class="text-sm text-slate-600">
                    {{ message }}
                </p>
            </div>

            <!-- Actions -->
            <div class="flex gap-3">
                <SecondaryButton
                    @click="cancel"
                    :disabled="processing"
                    class="flex-1 justify-center"
                >
                    {{ cancelText }}
                </SecondaryButton>
                <button
                    @click="confirm"
                    :disabled="processing"
                    class="flex-1 px-4 py-2.5 text-white rounded-xl font-semibold shadow-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-offset-2"
                    :class="currentVariant().button"
                >
                    <span v-if="processing" class="flex items-center justify-center gap-2">
                        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4l3.5-3.5L12 0v4a8 8 0 01-8 8z" />
                        </svg>
                        Processing...
                    </span>
                    <span v-else>{{ confirmText }}</span>
                </button>
            </div>
        </div>
    </Modal>
</template>
