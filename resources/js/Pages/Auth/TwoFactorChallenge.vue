<script setup>
import { nextTick, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { useColors } from '@/Composables/useColors';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { getAppLogo, getAppName } from '@/utils/global';

const { appSettings } = usePage().props;
const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const recovery = ref(false);
const form = useForm({
    code: '',
    recovery_code: '',
});

const recoveryCodeInput = ref(null);
const codeInput = ref(null);

const toggleRecovery = async () => {
    recovery.value ^= true;

    await nextTick();

    if (recovery.value) {
        recoveryCodeInput.value.focus();
        form.code = '';
    } else {
        codeInput.value.focus();
        form.recovery_code = '';
    }
};

const submit = () => {
    form.post(route('two-factor.login'));
};
</script>

<template>
    <Head title="Two-factor Confirmation" />

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
                    <template v-if="!recovery">
                        Please confirm access to your account by entering the authentication code provided by your authenticator application.
                    </template>
                    <template v-else>
                        Please confirm access to your account by entering one of your emergency recovery codes.
                    </template>
                </p>

                <form @submit.prevent="submit" class="space-y-4">
                    <div v-if="!recovery">
                        <InputLabel for="code" value="Code" />
                        <TextInput
                            id="code"
                            ref="codeInput"
                            v-model="form.code"
                            type="text"
                            inputmode="numeric"
                            class="mt-1 block w-full"
                            autofocus
                            autocomplete="one-time-code"
                        />
                        <InputError class="mt-2" :message="form.errors.code" />
                    </div>

                    <div v-else>
                        <InputLabel for="recovery_code" value="Recovery Code" />
                        <TextInput
                            id="recovery_code"
                            ref="recoveryCodeInput"
                            v-model="form.recovery_code"
                            type="text"
                            class="mt-1 block w-full"
                            autocomplete="one-time-code"
                        />
                        <InputError class="mt-2" :message="form.errors.recovery_code" />
                    </div>

                    <div class="flex items-center justify-between">
                        <button
                            type="button"
                            class="text-sm text-gray-600 hover:text-gray-900 underline cursor-pointer"
                            @click.prevent="toggleRecovery"
                        >
                            <template v-if="!recovery">
                                Use a recovery code
                            </template>
                            <template v-else>
                                Use an authentication code
                            </template>
                        </button>

                        <PrimaryButton
                            class="ms-4"
                            :disabled="form.processing"
                            :style="{
                                backgroundColor: buttonPrimaryBgColor,
                                color: buttonPrimaryTextColor,
                            }"
                        >
                            Log in
                        </PrimaryButton>
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
