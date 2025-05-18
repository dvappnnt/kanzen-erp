<script setup>
import { computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useColors } from '@/Composables/useColors';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { getAppLogo, getAppName } from '@/utils/global';

const props = defineProps({
    status: String,
});

const { appSettings } = usePage().props;
const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(() => props.status === 'verification-link-sent');
</script>

<template>
    <Head title="Email Verification" />

    <div class="min-h-screen grid grid-cols-1 md:grid-cols-2">
        <!-- Left side - Form -->
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
                    Before continuing, please verify your email address by clicking the link we just sent you. If you didn’t receive the email, we will gladly send another.
                </p>

                <div
                    v-if="verificationLinkSent"
                    class="mb-4 font-medium text-sm text-green-600"
                >
                    A new verification link has been sent to the email address you provided in your profile settings.
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <PrimaryButton
                        class="w-full py-3 justify-center"
                        :disabled="form.processing"
                        :style="{
                            backgroundColor: buttonPrimaryBgColor,
                            color: buttonPrimaryTextColor,
                        }"
                    >
                        Resend Verification Email
                    </PrimaryButton>

                    <div class="flex items-center justify-center gap-4 text-sm text-gray-600">
                        <Link
                            :href="route('profile.show')"
                            class="hover:underline"
                        >
                            Edit Profile
                        </Link>

                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="hover:underline"
                        >
                            Log Out
                        </Link>
                    </div>
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
