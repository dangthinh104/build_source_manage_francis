<script setup>
import { useForm, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';

const page = usePage();
const preferences = computed(() => page.props.preferences || {
    theme_color: 'indigo',
    content_width: 'wide',
    sidebar_style: 'gradient',
    compact_mode: false
});

const form = useForm({
    theme_color: preferences.value.theme_color,
    content_width: preferences.value.content_width,
    sidebar_style: preferences.value.sidebar_style,
    compact_mode: preferences.value.compact_mode || false,
});

const submit = () => {
    form.post(route('preferences.update'), {
        preserveScroll: true,
        onSuccess: () => {
            // Reload page to apply new theme using Inertia router
            router.reload({ only: ['preferences', 'auth'] });
        },
    });
};

const themeOptions = [
    { value: 'indigo', label: 'Indigo', color: 'bg-indigo-500' },
    { value: 'purple', label: 'Purple', color: 'bg-purple-500' },
    { value: 'blue', label: 'Blue', color: 'bg-blue-500' },
    { value: 'emerald', label: 'Emerald', color: 'bg-emerald-500' },
    { value: 'rose', label: 'Rose', color: 'bg-rose-500' },
    { value: 'orange', label: 'Orange', color: 'bg-orange-500' },
];
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-semibold text-slate-900">Display Preferences</h2>
            <p class="mt-1 text-sm text-slate-600">
                Customize your interface appearance and layout.
            </p>
        </header>

        <!-- Success/Error Messages -->
        <div v-if="page.props.flash?.success" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-xl flex items-start gap-3">
            <svg class="h-5 w-5 text-green-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <p class="text-sm text-green-800">{{ page.props.flash.success }}</p>
        </div>
        
        <div v-if="page.props.flash?.error" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-xl flex items-start gap-3">
            <svg class="h-5 w-5 text-red-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <p class="text-sm text-red-800">{{ page.props.flash.error }}</p>
        </div>

        <form @submit.prevent="submit" class="mt-6 space-y-6">
            <!-- Theme Color -->
            <div>
                <InputLabel for="theme_color" value="Theme Color" />
                <div class="mt-3 grid grid-cols-3 md:grid-cols-6 gap-3">
                    <button
                        v-for="theme in themeOptions"
                        :key="theme.value"
                        type="button"
                        @click="form.theme_color = theme.value"
                        class="relative flex flex-col items-center gap-2 p-3 rounded-xl border-2 transition-all duration-200 hover:shadow-md"
                        :class="form.theme_color === theme.value 
                            ? 'border-slate-900 bg-slate-50' 
                            : 'border-slate-200 hover:border-slate-300'"
                    >
                        <div class="h-8 w-8 rounded-lg shadow-sm" :class="theme.color"></div>
                        <span class="text-xs font-medium text-slate-700">{{ theme.label }}</span>
                        <div v-if="form.theme_color === theme.value" class="absolute -top-1 -right-1">
                            <svg class="h-5 w-5 text-green-600 bg-white rounded-full" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Content Width -->
            <div>
                <InputLabel for="content_width" value="Content Width" />
                <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <button
                        type="button"
                        @click="form.content_width = 'default'"
                        class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all duration-200 hover:shadow-md"
                        :class="form.content_width === 'default' 
                            ? 'border-slate-900 bg-slate-50' 
                            : 'border-slate-200 hover:border-slate-300'"
                    >
                        <div class="w-full h-12 bg-slate-200 rounded flex items-center justify-center">
                            <div class="w-3/5 h-8 bg-slate-400 rounded"></div>
                        </div>
                        <span class="text-sm font-medium text-slate-700">Default</span>
                        <span class="text-xs text-slate-500">Standard width</span>
                    </button>
                    <button
                        type="button"
                        @click="form.content_width = 'wide'"
                        class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all duration-200 hover:shadow-md"
                        :class="form.content_width === 'wide' 
                            ? 'border-slate-900 bg-slate-50' 
                            : 'border-slate-200 hover:border-slate-300'"
                    >
                        <div class="w-full h-12 bg-slate-200 rounded flex items-center justify-center">
                            <div class="w-4/5 h-8 bg-slate-400 rounded"></div>
                        </div>
                        <span class="text-sm font-medium text-slate-700">Wide</span>
                        <span class="text-xs text-slate-500">More space</span>
                    </button>
                    <button
                        type="button"
                        @click="form.content_width = 'full'"
                        class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all duration-200 hover:shadow-md"
                        :class="form.content_width === 'full' 
                            ? 'border-slate-900 bg-slate-50' 
                            : 'border-slate-200 hover:border-slate-300'"
                    >
                        <div class="w-full h-12 bg-slate-200 rounded flex items-center justify-center">
                            <div class="w-full h-8 bg-slate-400 rounded"></div>
                        </div>
                        <span class="text-sm font-medium text-slate-700">Full Width</span>
                        <span class="text-xs text-slate-500">Maximum space</span>
                    </button>
                </div>
            </div>

            <!-- Sidebar Style -->
            <div>
                <InputLabel for="sidebar_style" value="Sidebar Style" />
                <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3">
                    <button
                        type="button"
                        @click="form.sidebar_style = 'gradient'"
                        class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all duration-200 hover:shadow-md"
                        :class="form.sidebar_style === 'gradient' 
                            ? 'border-slate-900 bg-slate-50' 
                            : 'border-slate-200 hover:border-slate-300'"
                    >
                        <div class="w-full h-12 bg-gradient-to-br from-slate-700 to-slate-900 rounded"></div>
                        <span class="text-sm font-medium text-slate-700">Gradient</span>
                    </button>
                    <button
                        type="button"
                        @click="form.sidebar_style = 'solid'"
                        class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all duration-200 hover:shadow-md"
                        :class="form.sidebar_style === 'solid' 
                            ? 'border-slate-900 bg-slate-50' 
                            : 'border-slate-200 hover:border-slate-300'"
                    >
                        <div class="w-full h-12 bg-slate-900 rounded"></div>
                        <span class="text-sm font-medium text-slate-700">Solid</span>
                    </button>
                    <button
                        type="button"
                        @click="form.sidebar_style = 'glass'"
                        class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 transition-all duration-200 hover:shadow-md"
                        :class="form.sidebar_style === 'glass' 
                            ? 'border-slate-900 bg-slate-50' 
                            : 'border-slate-200 hover:border-slate-300'"
                    >
                        <div class="w-full h-12 bg-slate-900/80 backdrop-blur rounded"></div>
                        <span class="text-sm font-medium text-slate-700">Glass</span>
                    </button>
                </div>
            </div>

            <!-- Compact Mode -->
            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl">
                <div>
                    <InputLabel for="compact_mode" value="Compact Mode" class="!mb-0" />
                    <p class="text-sm text-slate-600 mt-1">Reduce spacing for more content</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input 
                        type="checkbox" 
                        v-model="form.compact_mode" 
                        class="sr-only peer"
                    >
                    <div class="w-11 h-6 bg-slate-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
                </label>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">
                    <svg class="h-5 w-5 shrink-0 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Save Preferences
                </PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p v-if="form.recentlySuccessful" class="text-sm text-slate-600">Saved.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>
