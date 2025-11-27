
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {Head, Link, useForm} from '@inertiajs/vue3';
import PrimaryButton from "@/Components/PrimaryButton.vue";
import InputError from "@/Components/InputError.vue";

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    role: 'Default',
});
const submit = () => {
    form.errors.name = '';
    form.errors.email = '';
    form.errors.password = '';
    form.errors.password_confirmation = '';
    form.post(route('users.store'));
};
</script>

<template>
    <Head title="User Manager Add" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Users Manager</h2>
        </template>


        <div class="py-12">
            <div class="max-w-10xl mx-auto sm:px-12 lg:px-12 space-y-12">
                <div class="p-6  min-h-screen bg-white">
                    <div class="max-w-8xl mx-auto bg-white p-4 shadow rounded" style="min-height: 20rem;">

                        <div class="container mx-auto px-4 py-8">
                            <h1 class="text-3xl font-bold mb-6">Add User</h1>

                            <form @submit.prevent="submit" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Name</label>
                                    <input v-model="form.name" type="text" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Enter Name" />
                                    <InputError :message="form.errors.name" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"/>

                                </div>

                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                                    <input v-model="form.email" type="email" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Enter Email" />
                                    <InputError :message="form.errors.email" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"/>

                                </div>

                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                                    <input v-model="form.password" type="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Enter Password" />
                                    <InputError :message="form.errors.password" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"/>

                                </div>

                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Confirm Password</label>
                                    <input v-model="form.password_confirmation" type="password" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" placeholder="Confirm Password" />
                                    <InputError :message="form.errors.password_confirmation" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"/>

                                </div>

                                <div class="mb-4">
                                    <label class="block text-gray-700 text-sm font-bold mb-2">Role</label>
                                    <select v-model="form.role" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                        <option value="Default">Default</option>
                                        <option value="Admin">Admin</option>
                                    </select>
                                </div>

                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">Create User</button>
                                <PrimaryButton><Link :href="route('users.index')" class="text-blue-500 hover:underline">Back</Link></PrimaryButton>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
