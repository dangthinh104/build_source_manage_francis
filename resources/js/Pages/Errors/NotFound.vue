<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-slate-800 leading-tight">Not Found</h2>
    </template>

    <div class="py-12">
      <div class="max-w-3xl mx-auto text-center">
        <h1 class="text-6xl font-bold">404</h1>
        <p class="mt-4 text-slate-600">The page you are looking for could not be found.</p>
        <div class="mt-6">
          <a href="/" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white">Go home</a>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
</script>
