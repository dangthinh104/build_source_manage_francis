<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import TickerWatchList from "@/Pages/Utils/MySiteItems.vue";

defineProps({
    mySite: {
        type: Array,
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Dashboard</h2>
        </template>


        <div class="py-12">
            <div class="max-w-10xl mx-auto sm:px-12 lg:px-12 space-y-12">
                <div class="p-8 sm:p-12 bg-white shadow sm:rounded-lg">
                    <TickerWatchList
                        :mySite="mySite"
                        class="max-w-3xl"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
