<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <input
        class="border-slate-300 bg-white text-slate-900 focus:border-primary focus:ring-primary/30 rounded-xl shadow-sm transition-all duration-200 hover:border-slate-400"
        v-model="model"
        ref="input"
    />
</template>
