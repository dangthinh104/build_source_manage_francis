<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const page = usePage();
const isAuthenticated = computed(() => !!page.props.auth?.user);
</script>

<template>
  <!-- Layout cho người dùng đã đăng nhập -->
  <AuthenticatedLayout v-if="isAuthenticated">
    <template #header>
      <h2 class="font-semibold text-xl text-slate-800 leading-tight">Not Found</h2>
    </template>

    <div class="py-12">
      <div class="max-w-3xl mx-auto text-center">
        <h1 class="text-6xl font-bold text-slate-800">404</h1>
        <p class="mt-4 text-slate-600">The page you are looking for could not be found.</p>
        <div class="mt-6">
          <a href="/" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">Go home</a>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>

  <!-- Layout đơn giản cho khách (chưa đăng nhập) -->
  <div v-else class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-slate-50 via-slate-100 to-slate-50">
    <div class="text-center px-6">
      <h1 class="text-8xl font-bold text-slate-800">404</h1>
      <p class="mt-4 text-xl text-slate-600">The page you are looking for could not be found.</p>
      <div class="mt-8">
        <a href="/" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-indigo-600 text-white font-medium hover:bg-indigo-700 transition-all shadow-lg shadow-indigo-500/30 hover:shadow-xl hover:shadow-indigo-500/40">
          Go to Login
        </a>
      </div>
    </div>
  </div>
</template>
