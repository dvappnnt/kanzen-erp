<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { useColors } from '@/Composables/useColors';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { getAppLogo, getAppName } from '@/utils/global';

const { appSettings } = usePage().props;
const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Register" />

    <div class="min-h-screen grid grid-cols-1 md:grid-cols-2">
        <!-- Left side - Register Form -->
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
                <p class="text-gray-500 mb-6">Create your account</p>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                            autofocus
                            autocomplete="name"
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <InputLabel for="email" value="Email" />
                        <TextInput
                            id="email"
                            v-model="form.email"
                            type="email"
                            class="mt-1 block w-full"
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
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div>
                        <InputLabel for="password_confirmation" value="Confirm Password" />
                        <TextInput
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            class="mt-1 block w-full"
                            required
                            autocomplete="new-password"
                        />
                        <InputError class="mt-2" :message="form.errors.password_confirmation" />
                    </div>

                    <div v-if="$page.props.jetstream.hasTermsAndPrivacyPolicyFeature">
                        <InputLabel for="terms">
                            <div class="flex items-center">
                                <Checkbox
                                    id="terms"
                                    v-model:checked="form.terms"
                                    name="terms"
                                    required
                                />
                                <div class="ms-2 text-sm text-gray-600">
                                    I agree to the
                                    <a
                                        target="_blank"
                                        :href="route('terms.show')"
                                        class="underline hover:text-gray-900"
                                    >
                                        Terms of Service
                                    </a>
                                    and
                                    <a
                                        target="_blank"
                                        :href="route('policy.show')"
                                        class="underline hover:text-gray-900"
                                    >
                                        Privacy Policy
                                    </a>
                                </div>
                            </div>
                            <InputError class="mt-2" :message="form.errors.terms" />
                        </InputLabel>
                    </div>

                    <PrimaryButton
                        class="w-full py-3 justify-center"
                        :disabled="form.processing"
                        :style="{
                            backgroundColor: buttonPrimaryBgColor,
                            color: buttonPrimaryTextColor,
                        }"
                    >
                        Register
                    </PrimaryButton>

                    <p class="text-center text-sm text-gray-500 mt-4">
                        Already registered?
                        <Link
                            :href="route('login')"
                            class="text-blue-600 hover:underline"
                        >
                            Log in
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
