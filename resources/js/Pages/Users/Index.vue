
<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import axios from 'axios';
import InputError from "@/Components/InputError.vue";
const props = defineProps({
    users: Array,
});
const successMessage = ref('');
// Delete user with confirmation
const confirmDelete = (userId) => {
    if (confirm('Are you sure you want to delete this user?')) {
        deleteUser(userId);
    }
};
// Delete user with axios
const deleteUser = async (userId) => {
    try {
        const response = await axios.post(route('users.destroy', userId));

        if (response.status === 200) {

            if (response.data.status === true) {
                window.location.reload(); // Optionally reload page to reflect changes or remove user from list
            } else {
                // On success, display message and reload users list
                successMessage.value = response.data.message;
            }
        }
    } catch (error) {
        console.error('Failed to delete user:', error);
        alert('Error: Could not delete user.');
    }
};
</script>

<template>
    <Head title="User Manager" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Users Manager</h2>
        </template>


        <div class="py-12">
            <div class="max-w-10xl mx-auto sm:px-12 lg:px-12 space-y-12">
                <div class="p-6  min-h-screen dark:bg-gray-800">
                    <div class="max-w-8xl mx-auto bg-white p-4 shadow rounded" style="min-height: 20rem;">

                        <div class="container mx-auto px-4 py-8">
                            <h1 class="text-3xl font-bold mb-6">User Management</h1>
                            <InputError :message="successMessage"/>
                            <div class="overflow-x-auto">
                                <table class="min-w-full bg-white border rounded-lg">
                                    <thead>
                                    <tr class="bg-gray-100 text-gray-600 uppercase text-sm leading-normal">
                                        <th class="py-3 px-6 text-left">Name</th>
                                        <th class="py-3 px-6 text-left">Email</th>
                                        <th class="py-3 px-6 text-left">Role</th>
                                        <th class="py-3 px-6 text-left">Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody class="text-gray-600 text-sm font-light">
                                    <tr v-for="user in users" :key="user.id" class="border-b border-gray-200 hover:bg-gray-100">
                                        <td class="py-3 px-6 text-left">{{ user.name }}</td>
                                        <td class="py-3 px-6 text-left">{{ user.email }}</td>
                                        <td class="py-3 px-6 text-left">{{ user.role }}</td>
                                        <td class="py-3 px-6 text-left">
                                            <!-- Link to edit the user -->
                                            <Link :href="route('users.edit', user.id)" class="text-blue-500 hover:underline">Edit</Link>
                                            <!-- Delete Button -->
                                            <button @click="confirmDelete(user.id)" class="ml-4 text-red-500 hover:underline">
                                                Delete
                                            </button>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>

                            <Link
                                :href="route('users.create')"
                                class="mt-6 inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                            >
                                Add User
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
