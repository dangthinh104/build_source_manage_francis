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

const viewMode = ref('history'); // 'history' or 'logs'
const histories = ref([]);
const logs = ref([]);
const loading = ref(false);

const fetchHistories = async () => {
  if (!props.siteId) return;
  loading.value = true;
  try {
    const res = await axios.post(route('my_site.history'), { site_id: props.siteId });
    histories.value = res.data.histories || [];
  } catch (e) {
    console.error('Failed to fetch histories', e);
    toast('Failed to load build history', { type: 'error' });
  } finally {
    loading.value = false;
  }
};

const fetchLogs = async () => {
  if (!props.siteId) return;
  loading.value = true;
  try {
    const res = await axios.post(route('my_site.logs'), { site_id: props.siteId });
    logs.value = res.data.logs || [];
  } catch (e) {
    console.error('Failed to fetch logs', e);
    toast('Failed to load log files', { type: 'error' });
  } finally {
    loading.value = false;
  }
};

const viewLogFile = async (log) => {
  try {
    const res = await axios.post(route('my_site.view_log'), {
      site_id: props.siteId,
      log_path: log.path,
    });
    
    emit('openLog', {
      filename: res.data.filename,
      content: res.data.content,
    });
  } catch (e) {
    console.error('Failed to view log', e);
    toast('Failed to load log file', { type: 'error' });
  }
};

const openLog = (history) => {
  emit('openLog', {
    filename: `Build #${history.id}`,
    content: history.output_log,
  });
};

// Watch show to fetch on open
watch(() => props.show, (v) => {
  if (v) {
    if (viewMode.value === 'history') {
      fetchHistories();
    } else {
      fetchLogs();
    }
  }
}, { immediate: false });

// Watch view mode changes
watch(viewMode, (newMode) => {
  if (props.show) {
    if (newMode === 'history') {
      fetchHistories();
    } else {
      fetchLogs();
    }
  }
});

const close = () => {
  histories.value = [];
  logs.value = [];
  viewMode.value = 'history';
  emit('close');
};
</script>

<template>
  <Modal :show="show" @close="close" :maxWidth="'3xl'">
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold">Site Logs</h3>
        <div class="flex gap-2">
          <button
            @click="viewMode = 'history'"
            :class="[
              'px-3 py-1 text-sm rounded',
              viewMode === 'history'
                ? 'bg-primary text-white'
                : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
            ]"
          >
            Build History
          </button>
          <button
            @click="viewMode = 'logs'"
            :class="[
              'px-3 py-1 text-sm rounded',
              viewMode === 'logs'
                ? 'bg-primary text-white'
                : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
            ]"
          >
            Log Files
          </button>
        </div>
      </div>

      <div v-if="loading" class="py-6 text-center text-slate-500">
        <svg class="animate-spin h-5 w-5 mx-auto mb-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Loading...
      </div>

      <!-- Build History View -->
      <div v-else-if="viewMode === 'history'">
        <ul v-if="histories.length" class="space-y-3 max-h-96 overflow-y-auto">
          <li v-for="h in histories" :key="h.id" class="p-3 bg-slate-50 rounded-md flex items-start justify-between hover:bg-slate-100">
            <div class="flex-1">
              <div class="text-sm font-medium">
                {{ h.user_name }} · 
                <span :class="['text-xs px-2 py-0.5 rounded', h.status === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700']">
                  {{ h.status }}
                </span>
              </div>
              <div class="text-xs text-slate-500 mt-1">{{ h.created_at }}</div>
              <div class="text-xs text-slate-700 mt-1 font-mono">{{ h.output_excerpt }}...</div>
            </div>
            <button class="ml-3 px-3 py-1 text-xs text-white bg-primary rounded hover:bg-primary-600" @click="openLog(h)">
              View Full Log
            </button>
          </li>
        </ul>
        <div v-else class="py-8 text-center text-slate-500">
          No build history found
        </div>
      </div>

      <!-- Log Files View -->
      <div v-else-if="viewMode === 'logs'">
        <ul v-if="logs.length" class="space-y-3 max-h-96 overflow-y-auto">
          <li v-for="log in logs" :key="log.path" class="p-3 bg-slate-50 rounded-md flex items-center justify-between hover:bg-slate-100">
            <div class="flex-1">
              <div class="text-sm font-medium font-mono">{{ log.filename }}</div>
              <div class="text-xs text-slate-500 mt-1">{{ log.date }}</div>
            </div>
            <button class="ml-3 px-3 py-1 text-xs text-white bg-primary rounded hover:bg-primary-600" @click="viewLogFile(log)">
              View Log
            </button>
          </li>
        </ul>
        <div v-else class="py-8 text-center text-slate-500">
          No log files found
        </div>
      </div>

      <div class="flex justify-end mt-4 pt-4 border-t">
        <button class="px-4 py-2 bg-slate-100 rounded hover:bg-slate-200" @click="close">Close</button>
      </div>
    </div>
  </Modal>
</template>
