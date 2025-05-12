<template>
    <div
        class="min-h-screen bg-white flex flex-col items-center justify-center px-6 py-10 space-y-6"
    >
        <!-- Avatar -->
        <img
            src="/static/the-original-creator/avatar.jpg"
            alt="Avatar"
            class="w-32 h-32 rounded-full shadow-md"
        />

        <!-- Name & Title -->
        <div class="text-center">
            <h1 class="text-2xl font-bold text-gray-800">Edeeson I. Opina</h1>
            <p class="text-gray-600 text-base">Full Stack Web Developer</p>
        </div>

        <!-- Tech Stack Logos -->
        <div class="flex items-center gap-6">
            <img
                src="/static/the-original-creator/laravel.png"
                alt="Laravel"
                class="h-12 w-auto"
            />
            <img
                src="/static/the-original-creator/vue.png"
                alt="Vue"
                class="h-12 w-auto"
            />
            <img
                src="/static/the-original-creator/nuxt.png"
                alt="Nuxt"
                class="h-12 w-auto"
            />
        </div>

        <!-- App Name -->
        <div class="pt-2 text-4xl text-gray-800 font-medium">
            {{ appName }}
        </div>
        <!-- Login Button -->
        <Link
            :href="route('login')"
            class="rounded-md px-4 py-2 transition focus:outline-none focus-visible:ring-2 ring-offset-2"
            :style="{
                backgroundColor: buttonPrimaryBgColor,
                color: buttonPrimaryTextColor,
                borderColor: buttonPrimaryBgColor,
            }"
        >
            Click here to start
        </Link>
    </div>
</template>

<script setup>
import { useColors } from "@/Composables/useColors";
import { Link } from "@inertiajs/vue3";

const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();
const appName = import.meta.env.VITE_APP_NAME;
</script>
