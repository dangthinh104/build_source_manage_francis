<script setup>
import { ref, watch } from 'vue';
import axios from 'axios';
import { toast } from 'vue3-toastify';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
  siteId: { type: [Number, String], required: false },
  show: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'openLog']);

const histories = ref([]);
const loading = ref(false);

const fetchHistories = async () => {
  if (!props.siteId) return;
  loading.value = true;
  try {
    console.log('Fetching histories for', props.siteId);
    const res = await axios.post(route('my_site.history'), { site_id: props.siteId });
    histories.value = res.data.histories || [];
  } catch (e) {
    console.error('Failed to fetch histories', e);
    toast('Failed to load history', { type: 'error' });
  } finally {
    loading.value = false;
  }
};

// Watch show to fetch on open
watch(() => props.show, (v) => {
  if (v) fetchHistories();
}, { immediate: false });

const openLog = (history) => {
  emit('openLog', history);
};

const close = () => {
  histories.value = [];
  emit('close');
};
</script>

<template>
  <Modal :show="show" @close="close" :maxWidth="'3xl'">
    <div class="p-6">
      <h3 class="text-lg font-semibold">Build History</h3>
      <div v-if="loading" class="py-6">Loading...</div>
      <ul v-else class="mt-4 space-y-3">
        <li v-for="h in histories" :key="h.id" class="p-3 bg-slate-50 rounded-md flex items-start justify-between">
          <div>
            <div class="text-sm font-medium">{{ h.user_name }} · <span class="text-xs text-slate-500">{{ h.created_at }}</span></div>
            <div class="text-xs text-slate-700 mt-1">{{ h.output_excerpt }}</div>
          </div>
          <div class="flex items-center gap-2">
            <button class="text-xs text-primary" @click="openLog(h)">View</button>
          </div>
        </li>
      </ul>
      <div class="flex justify-end mt-4">
        <button class="px-4 py-2 bg-slate-100 rounded" @click="close">Close</button>
      </div>
    </div>
  </Modal>
</template>
