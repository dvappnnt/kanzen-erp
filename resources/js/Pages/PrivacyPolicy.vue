<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { getAppLogo } from '@/utils/global';

const { appSettings } = usePage().props;

defineProps({
    policy: String,
});
</script>

<template>
    <Head title="Privacy Policy" />

    <div class="font-sans text-gray-900 antialiased">
        <div class="pt-12 bg-gray-100">
            <div class="min-h-screen flex flex-col items-center pt-6 sm:pt-0">
                <div>
                    <img :src="getAppLogo(appSettings)" alt="App Logo" class="w-32 h-auto mb-8" />
                </div>

                <div class="w-full sm:max-w-2xl mt-2 p-6 bg-white shadow-md overflow-hidden sm:rounded-lg prose" v-html="policy" />
            </div>
        </div>
    </div>
</template>
