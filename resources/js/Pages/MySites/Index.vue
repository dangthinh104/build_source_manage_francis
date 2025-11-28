<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import MySiteItems from '@/Pages/Utils/MySiteItems.vue';

const props = defineProps({
  sites: Object,
});
</script>

<template>
  <Head title="My Sites" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-slate-800 leading-tight">My Sites</h2>
    </template>

    <div class="py-6">
      <div class="max-w-10xl mx-auto">
        <MySiteItems :mySite="sites.data || []" />
        <div class="mt-4 flex items-center justify-end">
          <nav class="inline-flex -space-x-px" aria-label="Pagination">
            <Link v-if="sites.prev_page_url" :href="sites.prev_page_url" class="px-3 py-1 rounded-l-md border bg-white">Prev</Link>
            <span v-else class="px-3 py-1 rounded-l-md border bg-slate-100 text-slate-500">Prev</span>

            <template v-for="p in sites.last_page ? Array.from({ length: sites.last_page }, (_, i) => i + 1) : []" :key="p">
              <Link :href="`${sites.path}?page=${p}`" :class="['px-3 py-1 border', p === sites.current_page ? 'bg-primary-50 text-primary' : 'bg-white']">{{ p }}</Link>
            </template>

            <Link v-if="sites.next_page_url" :href="sites.next_page_url" class="px-3 py-1 rounded-r-md border bg-white">Next</Link>
            <span v-else class="px-3 py-1 rounded-r-md border bg-slate-100 text-slate-500">Next</span>
          </nav>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
