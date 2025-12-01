<template>
    <div class="fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-2xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Edit Env</h3>
                    <form @submit.prevent="updateVariable">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input v-model="form.variable_name" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" disabled />
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Value</label>
                            <Codemirror
                                v-model="form.variable_value"
                                placeholder="Enter value or JSON format"
                                :style="{ height: '250px' }"
                                :autofocus="false"
                                :indent-with-tab="true"
                                :tab-size="2"
                                :extensions="[json()]"
                                class="mt-1 border border-gray-300 rounded-md shadow-sm focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500 overflow-hidden"
                            />
                        </div>
                        <div class="flex justify-end">
                            <button @click="$emit('close')" type="button" class="bg-gray-500 text-white px-4 py-2 rounded mr-2">Cancel</button>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { Codemirror } from 'vue-codemirror';
import { json } from '@codemirror/lang-json';

const props = defineProps({
    variable: Object,
});

const emit = defineEmits(['close', 'update']);

const form = reactive({
    id: null,
    variable_name: '',
    variable_value: '',
});

watch(
    () => props.variable,
    (newVal) => {
        if (newVal) {
            form.id = newVal.id;
            form.variable_name = newVal.variable_name;
            form.variable_value = newVal.variable_value;
        }
    },
    { immediate: true }
);

const updateVariable = () => {
    emit('update', { ...form });
};
</script>
