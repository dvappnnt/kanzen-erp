<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import Stats from "@/Components/Stats.vue";
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const stats = computed(() => page.props.stats || {});
</script>

<template>
    <AppLayout title="Dashboard">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Dashboard
            </h2>
        </template>

        <div class="max-w-12xl">
            <Stats :stats="stats" />
        </div>
    </AppLayout>
</template>
