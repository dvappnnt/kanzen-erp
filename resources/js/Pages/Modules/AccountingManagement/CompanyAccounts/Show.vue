<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import moment from "moment";
import HeaderInformation from "@/Components/Sections/HeaderInformation.vue";
import ProfileCard from "@/Components/Sections/ProfileCard.vue";
import DisplayInformation from "@/Components/Sections/DisplayInformation.vue";
import { singularizeAndFormat, formatNumber } from "@/utils/global";

const modelName = "company-accounts";

// Access appSettings from Inertia.js page props
const { appSettings } = usePage().props;
const primaryColor = computed(() => appSettings?.primary_color || "#3B82F6");

const headerActions = ref([
    {
        text: "Go Back",
        url: `/${modelName}`,
        inertia: true,
        class: "border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded text-gray-600",
    },
]);

const profileDetails = [
    { label: "Name", value: "name", class: "text-xl font-bold" },
    { label: "Number", value: "number", class: "text-gray-500" },
    { label: "Type", value: "type", class: "text-gray-500" },
];

const accountDetails = ref([
    { label: "Bank", value: (row) => row.name || "-" },
    { label: "Account Number", value: (row) => row.number || "-" },
    { label: "Account Type", value: (row) => row.type || "-" },
    { label: "Status", value: (row) => row.status || "-" },
    { label: "Balance", value: (row) => formatNumber(row.balance, { style: 'currency', currency: 'PHP' }) || "-" },
    { label: "Currency", value: (row) => row.currency || "-" },
]);

const bankDetails = ref([
    { label: "Bank Code", value: (row) => row.bank?.code || "-" },
    { label: "Bank Name", value: (row) => row.bank?.name || "-" },
]);

const companyDetails = ref([
    { label: "Company", value: (row) => row.company?.name || "-" },
    { label: "Address", value: (row) => row.company?.address || "-" },
    { label: "Mobile", value: (row) => row.company?.mobile || "-" },
    { label: "Landline", value: (row) => row.company?.landline || "-" },
    { label: "Description", value: (row) => row.company?.description || "-" },
    { label: "Website", value: (row) => row.company?.website || "-" },
]);

const page = usePage();
const modelData = computed(() => page.props.modelData || {});
</script>

<template>
    <AppLayout :title="`${singularizeAndFormat(modelName)} Details`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ singularizeAndFormat(modelName) }} Details
                </h2>
                <HeaderActions :actions="headerActions" />
            </div>
        </template>

        <div class="max-w-12xl mx-auto">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg pt-6">
                <HeaderInformation
                    :title="`${singularizeAndFormat(modelName)} Details`"
                    :modelData="modelData"
                />
                <ProfileCard :modelData="modelData" :columns="profileDetails" />

                <div class="border-t border-gray-200" />
                <DisplayInformation
                    title="Account Information"
                    :modelData="modelData"
                    :rowDetails="accountDetails"
                />
                <div class="border-t border-gray-200" />
                <DisplayInformation
                    title="Bank Information"
                    :modelData="modelData"
                    :rowDetails="bankDetails"
                />
                <div class="border-t border-gray-200" />
                <DisplayInformation
                    title="Company Information"
                    :modelData="modelData"
                    :rowDetails="companyDetails"
                />
            </div>
        </div>
    </AppLayout>
</template>
