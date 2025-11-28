
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { defineProps } from 'vue';
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";

const props = defineProps({
    user: Object, // This comes from the controller
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    role: props.user.role,
});
const submit = () => {
    form.errors.name = '';
    form.errors.email = '';
    form.errors.role = '';
    form.put(route('users.update', props.user.id)); // PUT request to update user
};
</script>

<template>
    <Head title="User Manager Edit" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Users Manager</h2>
        </template>


        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('users.index')" class="p-2 hover:bg-slate-100 rounded-lg transition-colors duration-200">
                    <svg class="h-6 w-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Edit User</h1>
                    <p class="text-slate-600 mt-1">Update user information</p>
                </div>
            </div>

            <!-- Form Card -->
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="bg-gradient-to-r from-indigo-500 to-purple-500 px-6 py-4">
                    <h2 class="text-lg font-semibold text-white">User Information</h2>
                </div>
                
                <form @submit.prevent="submit" class="p-6 space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Full Name</label>
                        <input 
                            v-model="form.name" 
                            type="text" 
                            class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-400/50 focus:border-indigo-400 transition-all duration-200 hover:border-slate-400" 
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Email Address</label>
                        <input 
                            v-model="form.email" 
                            type="email" 
                            class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-400/50 focus:border-indigo-400 transition-all duration-200 hover:border-slate-400" 
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">User Role</label>
                        <select 
                            v-model="form.role" 
                            class="w-full px-4 py-2.5 border border-slate-300 rounded-xl focus:ring-2 focus:ring-indigo-400/50 focus:border-indigo-400 transition-all duration-200 hover:border-slate-400"
                        >
                            <option value="Default">Default User</option>
                            <option value="Admin">Administrator</option>
                        </select>
                        <InputError :message="form.errors.role" />
                    </div>

                    <div class="flex items-center gap-3 pt-4">
                        <PrimaryButton type="submit" class="flex-1">
                            <svg class="h-5 w-5 shrink-0 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Update User
                        </PrimaryButton>
                        <Link :href="route('users.index')" class="px-6 py-2.5 bg-slate-100 text-slate-700 rounded-xl font-semibold hover:bg-slate-200 transition-all duration-200">
                            Cancel
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
