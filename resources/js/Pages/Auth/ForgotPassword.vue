<script setup>
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import { useColors } from '@/Composables/useColors';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { getAppLogo, getAppName } from '@/utils/global';

defineProps({
    status: String,
});

const { appSettings } = usePage().props;
const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Forgot Password" />

    <div class="min-h-screen grid grid-cols-1 md:grid-cols-2">
        <!-- Left side - Forgot Password Form -->
        <div class="flex flex-col justify-center px-4 sm:px-6 md:px-8 lg:px-12">
            <div class="w-full max-w-md mx-auto">
                <img
                    :src="getAppLogo(appSettings)"
                    alt="App Logo"
                    class="w-32 h-auto mb-8"
                />

                <h2 class="text-3xl font-bold text-gray-800 mb-1">
                    {{ getAppName(appSettings) }}
                </h2>
                <p class="text-gray-500 mb-6">
                    Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.
                </p>

                <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full"
                            required
                            autofocus
                            autocomplete="username"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <PrimaryButton
                        class="w-full py-3 justify-center"
                        :disabled="form.processing"
                        :style="{
                            backgroundColor: buttonPrimaryBgColor,
                            color: buttonPrimaryTextColor,
                        }"
                    >
                        Email Password Reset Link
                    </PrimaryButton>

                    <p class="text-center text-sm text-gray-500 mt-4">
                        Remembered your password?
                        <Link
                            :href="route('login')"
                            class="text-blue-600 hover:underline"
                        >
                            Click here to log in
                        </Link>
                    </p>
                </form>
            </div>
        </div>

        <!-- Right side - Background -->
        <div
            class="hidden md:block bg-cover bg-center"
            style="background-image: url('/static/auth/logo.jpg')"
        ></div>
    </div>
</template>
