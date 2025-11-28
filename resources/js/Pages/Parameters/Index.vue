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

      <!-- Create Parameter Card -->
      <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-500 to-purple-500 px-6 py-4">
          <h2 class="text-lg font-semibold text-white flex items-center gap-2">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
            Add Parameter
          </h2>
        </div>
        <form @submit.prevent="storeParameter" class="p-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">Key</label>
              <TextInput v-model="newParam.key" placeholder="parameter_key" required />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">Value</label>
              <TextInput v-model="newParam.value" placeholder="value" required />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">Type</label>
              <TextInput v-model="newParam.type" placeholder="string/integer/boolean/path" required />
            </div>
            <div>
              <label class="block text-sm font-semibold text-slate-700 mb-2">Description</label>
              <TextInput v-model="newParam.description" placeholder="Optional description" />
            </div>
          </div>
          <div class="mt-4">
            <PrimaryButton type="submit">Create Parameter</PrimaryButton>
          </div>
        </form>
      </div>

      <!-- Parameters List Card -->
      <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-slate-50 to-slate-100 px-6 py-4 border-b border-slate-200">
          <h2 class="text-lg font-semibold text-slate-900 flex items-center gap-2">
            <svg class="h-5 w-5 shrink-0 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Parameters
            <span class="ml-auto text-sm font-normal text-slate-600">{{ parameters.length }} items</span>
          </h2>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
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
                    <div class="h-8 w-8 shrink-0 rounded-lg bg-indigo-100 flex items-center justify-center mr-3">
                      <svg class="h-4 w-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                      </svg>
                    </div>
                    <span class="text-sm font-semibold text-slate-900">{{ param.key }}</span>
                  </div>
                </td>
                <td class="px-6 py-4">
                  <TextInput v-model="param.value" />
                </td>
                <td class="px-6 py-4">{{ param.type }}</td>
                <td class="px-6 py-4"><TextInput v-model="param.description" /></td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div class="flex items-center justify-end gap-2">
                      <button @click="openEdit(param)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg font-medium hover:bg-indigo-100 transition-all duration-200">Edit</button>
                      <button @click="remove(param)" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 text-red-600 rounded-lg font-medium hover:bg-red-100 transition-all duration-200">Delete</button>
                    </div>
                </td>
              </tr>
              <tr v-if="parameters.length === 0">
                <td colspan="5" class="px-6 py-12 text-center">
                  <div class="flex flex-col items-center justify-center text-slate-500">
                    <svg class="h-12 w-12 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <p class="text-sm font-medium">No parameters</p>
                    <p class="text-xs mt-1">Add your first parameter above</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <EditModal v-if="isEditOpen" :parameter="editing" @close="closeEdit" @update="handleUpdate" />

  </AuthenticatedLayout>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue';
import axios from 'axios';
import { Head, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import EditModal from './EditModal.vue';

const page = usePage();
const flash = page.props?.flash || {};

const parameters = ref([]);
const newParam = reactive({ key: '', value: '', type: '', description: '' });

const load = async () => {
  try {
    const res = await axios.get('/parameters');
    parameters.value = res.data;
  } catch (e) {
    console.error('Failed to load parameters', e);
  }
};

const storeParameter = async () => {
  try {
    const res = await axios.post('/parameters', newParam);
    parameters.value.push(res.data);
    newParam.key = '';
    newParam.value = '';
    newParam.type = '';
    newParam.description = '';
  } catch (e) {
    console.error('Create failed', e);
    alert(e.response?.data?.message || 'Create failed');
  }
};

const save = async (param) => {
  try {
    await axios.put(`/parameters/${param.id}`, param);
    alert('Saved');
  } catch (e) {
    console.error('Save failed', e);
    alert(e.response?.data?.message || 'Save failed');
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

onMounted(load);

const isEditOpen = ref(false);
const editing = ref(null);

const openEdit = (param) => {
  editing.value = { ...param };
  isEditOpen.value = true;
};

const closeEdit = () => {
  isEditOpen.value = false;
  editing.value = null;
};

const handleUpdate = async (updated) => {
  try {
    await axios.put(`/parameters/${updated.id}`, updated);
    const idx = parameters.value.findIndex(p => p.id === updated.id);
    if (idx !== -1) {
      parameters.value[idx] = { ...parameters.value[idx], ...updated };
    }
    closeEdit();
    alert('Updated');
  } catch (e) {
    console.error('Update failed', e);
    alert(e.response?.data?.message || 'Update failed');
  }
};
</script>
