<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, onMounted, computed } from 'vue';
import { toast } from 'vue3-toastify';

const props = defineProps({
    qrCode: {
        type: String,
        required: true,
    },
    secret: {
        type: String,
        required: true,
    },
});

// Format secret with spaces for better readability (groups of 4)
const formattedSecret = computed(() => {
    return props.secret.match(/.{1,4}/g)?.join(' ') || props.secret;
});

const form = useForm({
    code: '',
});

const codeInputs = ref([]);

onMounted(() => {
    // Autofocus first input on mount
    if (codeInputs.value[0]) {
        codeInputs.value[0].focus();
    }
});

const copySecret = async () => {
    try {
        await navigator.clipboard.writeText(props.secret);
        toast.success('Secret key copied to clipboard!', {
            position: 'top-right',
            autoClose: 2000,
        });
    } catch (err) {
        toast.error('Failed to copy to clipboard', {
            position: 'top-right',
            autoClose: 2000,
        });
    }
};

const submit = () => {
    form.post(route('2fa.confirm'), {
        preserveScroll: true,
        onError: () => {
            form.reset('code');
            if (codeInputs.value[0]) {
                codeInputs.value[0].focus();
            }
        },
    });
};

// Autofocus next input when typing
const handleInput = (index, event) => {
    const value = event.target.value;
    if (value.length === 1 && index < 5) {
        codeInputs.value[index + 1]?.focus();
    }
};

// Handle backspace to go to previous input
const handleKeydown = (index, event) => {
    if (event.key === 'Backspace' && !event.target.value && index > 0) {
        codeInputs.value[index - 1]?.focus();
    }
};

// Handle paste to fill all inputs
const handlePaste = (event) => {
    event.preventDefault();
    const pastedData = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);

    if (pastedData.length === 6) {
        form.code = pastedData;
        codeInputs.value[5]?.focus();
    }
};
</script>

<template>
    <Head title="Set Up Two-Factor Authentication" />

    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-50 to-slate-100 px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-xl">
            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <!-- Icon -->
                <div class="flex justify-center mb-6">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                </div>

                <!-- Header -->
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold text-slate-900 mb-2">Set Up Two-Factor Authentication</h2>
                    <p class="text-sm text-slate-600">Scan the QR code with your authenticator app</p>
                </div>

                <!-- QR Code Section -->
                <div class="mb-8">
                    <!-- QR Code Container -->
                    <div class="flex justify-center mb-6">
                        <div class="p-6 bg-white rounded-2xl border-2 border-slate-200 shadow-sm">
                            <div 
                                class="flex items-center justify-center w-[200px] h-[200px] overflow-hidden"
                                v-html="qrCode"
                            ></div>
                        </div>
                    </div>

                    <!-- Manual Entry -->
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <p class="text-xs font-medium text-slate-600 mb-3 text-center uppercase tracking-wide">
                            Or enter this code manually in your app
                        </p>
                        <div class="flex flex-col items-center gap-3">
                            <div class="flex items-center gap-2 w-full justify-center">
                                <code class="text-sm font-mono font-bold text-slate-900 bg-white px-4 py-2.5 rounded-lg border-2 border-slate-300 tracking-wider">
                                    {{ formattedSecret }}
                                </code>
                                <button
                                    type="button"
                                    @click="copySecret"
                                    class="p-2.5 text-slate-600 hover:text-indigo-600 hover:bg-white rounded-lg transition-colors border border-transparent hover:border-slate-300 flex-shrink-0"
                                    title="Copy to clipboard"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                            <p class="text-xs text-slate-500 text-center">
                                Account: {{ $page.props.auth?.user?.email || 'your-email' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- 6-digit Code Input -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-3 text-center">
                            Enter the 6-digit code to confirm
                        </label>
                        <div class="flex gap-2 justify-center mb-2">
                            <input
                                v-for="i in 6"
                                :key="i"
                                :ref="el => codeInputs[i-1] = el"
                                type="text"
                                inputmode="numeric"
                                maxlength="1"
                                :value="form.code[i-1] || ''"
                                @input="(e) => { form.code = form.code.substring(0, i-1) + e.target.value + form.code.substring(i); handleInput(i-1, e); }"
                                @keydown="(e) => handleKeydown(i-1, e)"
                                @paste="handlePaste"
                                class="w-12 h-14 text-center text-2xl font-bold border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                :class="form.errors.code ? 'border-red-300 bg-red-50' : 'border-slate-300 bg-white hover:border-slate-400'"
                            />
                        </div>
                        <p v-if="form.errors.code" class="mt-2 text-sm text-red-600 text-center flex items-center justify-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ form.errors.code }}
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="form.processing || form.code.length !== 6"
                        class="w-full flex justify-center items-center px-4 py-3 border border-transparent rounded-xl text-base font-medium text-white bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200"
                    >
                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span v-if="form.processing">Confirming...</span>
                        <span v-else>Confirm & Enable 2FA</span>
                    </button>
                </form>

                <!-- Back to Login -->
                <div class="mt-6 text-center">
                    <a
                        href="/login"
                        class="text-sm font-medium text-indigo-600 hover:text-indigo-500 transition inline-flex items-center gap-1"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to login
                    </a>
                </div>
            </div>

            <!-- Help Text -->
            <div class="mt-6 text-center">
                <p class="text-sm text-slate-500">
                    Use apps like Google Authenticator, Authy, or 1Password
                </p>
            </div>
        </div>
    </div>
</template>
