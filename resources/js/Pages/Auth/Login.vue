<script setup>
import { Head, Link, useForm, usePage } from "@inertiajs/vue3";
import { useColors } from "@/Composables/useColors";
import Checkbox from "@/Components/Checkbox.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { getAppLogo, getAppName } from "@/utils/global";

const { appSettings } = usePage().props;
const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        remember: form.remember ? "on" : "",
    })).post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head title="Log in" />

    <div class="min-h-screen grid grid-cols-1 md:grid-cols-2">
        <!-- Left side - Login Form -->
        <div class="flex flex-col justify-center px-4 sm:px-6 md:px-8 lg:px-12">
            <div class="w-full max-w-md mx-auto">
                <img
                    :src="getAppLogo(appSettings)"
                    alt="App Logo"
                    class="w-48 h-auto mb-8"
                />
                <!-- <img src="/static/auth/kanzen.png" alt="App Logo" class="w-48 h-auto mb-8" /> -->

                <h2 class="text-3xl font-bold text-gray-800 mb-1">
                    {{ getAppName(appSettings) }}
                </h2>
                <p class="text-gray-500 mb-6">Please enter your details</p>

                <div
                    v-if="status"
                    class="mb-4 font-medium text-sm text-green-600"
                >
                    {{ status }}
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <InputLabel for="email" value="Email address" />
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

                    <div>
                        <InputLabel for="password" value="Password" />
                        <TextInput
                            id="password"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-full"
                            required
                            autocomplete="current-password"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.password"
                        />
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <Checkbox
                                v-model:checked="form.remember"
                                name="remember"
                            />
                            <span class="ms-2 text-sm text-gray-600"
                                >Remember for 30 days</span
                            >
                        </label>
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm text-gray-500 hover:underline"
                        >
                            Forgot password?
                        </Link>
                    </div>

                    <PrimaryButton
                        class="w-full py-3 justify-center"
                        :disabled="form.processing"
                        :style="{
                            backgroundColor: buttonPrimaryBgColor,
                            color: buttonPrimaryTextColor,
                        }"
                    >
                        Sign in
                    </PrimaryButton>

                    <!-- Or Divider -->
                    <div class="flex items-center gap-4 my-4">
                        <hr class="flex-grow border-t border-gray-300" />
                        <span class="text-gray-500 text-sm">or</span>
                        <hr class="flex-grow border-t border-gray-300" />
                    </div>

                    <!-- Google Sign In -->
                    <button
                        type="button"
                        class="w-full border border-gray-300 rounded-md py-3 flex items-center justify-center gap-2 text-sm hover:bg-gray-50 transition"
                    >
                        <img
                            src="/static/auth/google.png"
                            class="h-5 w-5"
                            alt="Google"
                        />
                        Sign in with Google
                    </button>

                    <p class="text-center text-sm text-gray-500">
                        Don’t have an account?
                        <Link
                            :href="route('register')"
                            class="text-blue-600 hover:underline"
                        >
                            Sign up
                        </Link>
                    </p>
                </form>
            </div>
        </div>

        <!-- Right side - Illustration -->
        <div
            class="hidden md:block bg-cover bg-center"
            style="background-image: url('/static/auth/logo.jpg')"
        ></div>
    </div>
</template>
