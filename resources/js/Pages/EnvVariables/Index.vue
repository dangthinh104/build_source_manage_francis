<template>
    <Head title="Env Manager" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Env Manager</h2>
        </template>


        <div class="py-12">
            <div class="max-w-10xl mx-auto sm:px-12 lg:px-12 space-y-12">
                <div class="p-6  min-h-screen dark:bg-gray-800">
                    <div class="max-w-8xl mx-auto bg-white p-4 shadow rounded" style="min-height: 20rem;">

                    <!-- Thông báo thành công -->
                    <div v-if="flash.success" class="bg-green-500 text-white p-4 rounded mb-4">
                        {{ flash.success }}
                    </div>

                    <!-- Thêm biến mới -->
                    <div class="bg-white p-4 rounded shadow mb-6">
                        <h2 class="text-xl font-semibold mb-4">Add Env</h2>
                        <form @submit.prevent="storeVariable">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Name</label>
                                <input v-model="newVariable.variable_name" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">Value</label>
                                <input v-model="newVariable.variable_value" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" />
                            </div>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Add Env</button>
                        </form>
                    </div>

                    <!-- Danh sách các biến -->
                    <div class="bg-white p-4 rounded shadow">
                        <h2 class="text-xl font-semibold mb-4">List Env</h2>
                        <table class="min-w-full bg-white">
                            <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name Env</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                                <th class="px-6 py-3"></th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="variable in envVariables" :key="variable.id">
                                <td class="px-6 py-4">{{ variable.variable_name }}</td>
                                <td class="px-6 py-4">{{ variable.variable_value }}</td>
                                <td class="px-6 py-4">
                                    <button @click="editVariable(variable)" class="bg-yellow-500 text-white px-4 py-2 rounded mr-2">Edit</button>
                                    <button @click="deleteVariable(variable.id)" class="bg-red-500 text-white px-4 py-2 rounded">Delete</button>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Modal chỉnh sửa biến -->
                    <EditModal
                        v-if="isEditModalOpen"
                        :variable="selectedVariable"
                        @close="isEditModalOpen = false"
                        @update="updateVariable"
                    />
                    </div>
                </div>
            </div>

    </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { reactive, ref } from 'vue';
import axios from 'axios';
import {Head, usePage} from '@inertiajs/vue3';
import EditModal from './EditModal.vue';
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";

const props = defineProps({
    envVariables: Array,
});

// Safely access the flash messages
const page = usePage();
const flash = page.props?.flash || {}; // Set a default empty object if flash is undefined


const newVariable = reactive({
    variable_name: '',
    variable_value: '',
});

const isEditModalOpen = ref(false);
const selectedVariable = ref(null);

const storeVariable = async () => {
    try {
        const response = await axios.post('/envVariables', newVariable);
        if (response.status === 200) {
            newVariable.variable_name = '';
            newVariable.variable_value = '';
            location.reload();
        }
    } catch (error) {
        console.error('Error when add env:', error);
    }
};

const editVariable = (variable) => {
    selectedVariable.value = { ...variable };
    isEditModalOpen.value = true;
};

const updateVariable = async (updatedVariable) => {
    try {
        const response = await axios.put(`/envVariables/${updatedVariable.id}`, updatedVariable);
        if (response.status === 200) {
            isEditModalOpen.value = false;
            location.reload();
        }
    } catch (error) {
        console.error('Error when edit:', error);
    }
};

const deleteVariable = async (id) => {
    if (confirm('Confirm delete env?')) {
        try {
            const response = await axios.delete(`/envVariables/${id}`);
            if (response.status === 200) {
                location.reload();
            }
        } catch (error) {
            console.error('Error when delete env:', error);
        }
    }
};
</script>

<style scoped>
/* Các lớp Tailwind CSS đã được sử dụng inline */
</style>

