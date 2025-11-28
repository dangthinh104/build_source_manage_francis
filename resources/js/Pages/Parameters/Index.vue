<template>
  <Head title="Parameters" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-slate-800 leading-tight">Parameters</h2>
    </template>

    <div class="space-y-6">
      <div v-if="flash.success" class="bg-gradient-to-r from-green-500 to-emerald-500 text-white px-6 py-4 rounded-2xl shadow-lg shadow-green-500/30 flex items-center gap-3 fade-in">
        <svg class="h-6 w-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <span class="font-medium">{{ flash.success }}</span>
      </div>

      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-slate-900">Parameters</h1>
          <p class="text-slate-600 mt-1">Manage application parameters and feature flags</p>
        </div>
        <button @click="openCreate" class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-indigo-600 to-indigo-500 text-white rounded-xl font-semibold shadow-lg shadow-indigo-500/30 transition-all duration-300 hover:shadow-xl hover:shadow-indigo-500/40 hover:-translate-y-0.5">
          <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
          </svg>
          <span>Add New</span>
        </button>
      </div>

      <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-gradient-to-r from-slate-50 to-slate-100">
              <tr>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Key</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Value</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Type</th>
                <th class="px-6 py-4 text-left text-xs font-semibold text-slate-700 uppercase tracking-wider">Description</th>
                <th class="px-6 py-4 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100">
              <tr v-for="param in parameters" :key="param.id" class="hover:bg-slate-50 transition-colors duration-150">
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="flex items-center">
                    <div class="h-10 w-10 shrink-0 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-white font-semibold">
                      {{ param.key.charAt(0).toUpperCase() }}
                    </div>
                    <div class="ml-4">
                      <div class="text-sm font-semibold text-slate-900">{{ param.key }}</div>
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-slate-600 truncate max-w-xl">{{ param.value }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">{{ param.type }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">{{ param.description }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex items-center justify-end gap-2">
                    <button @click="openEdit(param)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg font-medium hover:bg-indigo-100 transition-all duration-200">
                      <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                      </svg>
                      <span>Edit</span>
                    </button>
                    <button @click="remove(param)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 text-red-600 rounded-lg font-medium hover:bg-red-100 transition-all duration-200">
                      <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                      </svg>
                      <span>Delete</span>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="parameters.length === 0">
                <td colspan="5" class="px-6 py-12 text-center">
                  <div class="flex flex-col items-center justify-center text-slate-500">
                    <svg class="h-12 w-12 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-sm font-medium">No parameters found</p>
                    <p class="text-xs mt-1">Use the Add New button to create one</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <EditModal v-if="isModalOpen" :parameter="editing" :mode="modalMode" @close="closeModal" @update="handleUpdate" @create="handleCreate" />

  </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import EditModal from './EditModal.vue';

const props = defineProps({
  parameters: Array,
});

const page = usePage();
const flash = page.props?.flash || {};

const parameters = ref(props.parameters || []);

const isModalOpen = ref(false);
const modalMode = ref('edit'); // 'edit' or 'create'
const editing = ref(null);

const openEdit = (param) => {
  editing.value = { ...param };
  modalMode.value = 'edit';
  isModalOpen.value = true;
};

const openCreate = () => {
  editing.value = { key: '', value: '', type: '', description: '' };
  modalMode.value = 'create';
  isModalOpen.value = true;
};

const closeModal = () => {
  isModalOpen.value = false;
  editing.value = null;
};

const handleUpdate = async (updated) => {
  try {
    await axios.put(`/parameters/${updated.id}`, updated);
    const idx = parameters.value.findIndex(p => p.id === updated.id);
    if (idx !== -1) parameters.value[idx] = { ...parameters.value[idx], ...updated };
    closeModal();
  } catch (e) {
    console.error('Update failed', e);
    alert(e.response?.data?.message || 'Update failed');
  }
};

const handleCreate = async (payload) => {
  try {
    const res = await axios.post('/parameters', payload);
    parameters.value.unshift(res.data);
    closeModal();
  } catch (e) {
    console.error('Create failed', e);
    alert(e.response?.data?.message || 'Create failed');
  }
};

const remove = async (param) => {
  if (!confirm('Delete parameter?')) return;
  try {
    await axios.delete(`/parameters/${param.id}`);
    parameters.value = parameters.value.filter(p => p.id !== param.id);
  } catch (e) {
    console.error('Delete failed', e);
    alert(e.response?.data?.message || 'Delete failed');
  }
};
</script>

