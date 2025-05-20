<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import Form from "@/Components/Form/Form.vue";
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";

const { appSettings, accountTypes, account } = usePage().props;
const primaryColor = computed(() => appSettings?.primary_color || "#3B82F6");

const form = ref({
    name: account.name,
    code: account.code,
    account_type_id: account.account_type_id,
    is_active: account.is_active
});

const fields = ref([
    {
        id: "account_type_id",
        label: "Account Type",
        model: "account_type_id",
        type: "select",
        required: true,
        options: accountTypes.map((type) => ({
            value: type.id,
            text: `${type.name} (${type.code})`
        })),
    },
    {
        id: "code",
        label: "Account Code",
        model: "code",
        type: "text",
        required: true,
        placeholder: "Enter account code (e.g., 1001)",
    },
    {
        id: "name",
        label: "Account Name",
        model: "name",
        type: "text",
        required: true,
        placeholder: "Enter account name",
    },
    {
        id: "is_active",
        label: "Status",
        model: "is_active",
        type: "select",
        required: true,
        options: [
            { value: true, text: "Active" },
            { value: false, text: "Inactive" },
        ],
    },
]);

const submit = () => {
    router.put(`/api/accounts/${account.id}`, form.value, {
        onSuccess: () => {
            router.visit('/chart-of-accounts');
        },
    });
};
</script>

<template>
    <AppLayout title="Edit Account">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Edit Account
                </h2>
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <Form
                        :fields="fields"
                        v-model="form"
                        @submit="submit"
                        :submit-label="'Update Account'"
                        :submit-style="{ backgroundColor: primaryColor }"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template> 