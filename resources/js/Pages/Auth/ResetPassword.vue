<script setup>
import { Head, useForm, usePage, Link } from "@inertiajs/vue3";
import { useColors } from "@/Composables/useColors";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { getAppLogo, getAppName } from "@/utils/global";

const props = defineProps({
    email: String,
    token: String,
});

const { appSettings } = usePage().props;
const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const form = useForm({
    token: props.token,
    email: props.email,
    password: "",
    password_confirmation: "",
});

const submit = () => {
    form.post(route("password.update"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <Head title="Reset Password" />

    <div class="min-h-screen grid grid-cols-1 md:grid-cols-2">
        <!-- Left side - Reset Form -->
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
                <p class="text-gray-500 mb-6">Choose a new password</p>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full bg-gray-100 cursor-not-allowed"
                            readonly
                            required
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
                            autocomplete="new-password"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.password"
                        />
                    </div>

                    <div>
                        <InputLabel
                            for="password_confirmation"
                            value="Confirm Password"
                        />
                        <TextInput
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            required
                            autocomplete="new-password"
                        />
                        <InputError
                            class="mt-2"
                            :message="form.errors.password_confirmation"
                        />
                    </div>

                    <PrimaryButton
                        class="w-full py-3 justify-center"
                        :disabled="form.processing"
                        :style="{
                            backgroundColor: buttonPrimaryBgColor,
                            color: buttonPrimaryTextColor,
                        }"
                    >
                        Reset Password
                    </PrimaryButton>
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
