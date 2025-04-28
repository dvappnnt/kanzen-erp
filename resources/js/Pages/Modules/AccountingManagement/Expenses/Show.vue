<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import moment from "moment";
import HeaderInformation from "@/Components/Sections/HeaderInformation.vue";
import ProfileCard from "@/Components/Sections/ProfileCard.vue";
import DisplayInformation from "@/Components/Sections/DisplayInformation.vue";
import { singularizeAndFormat } from "@/utils/global";

const modelName = "expenses";
const formatModelName = (name) => {
    if (name.endsWith("ies")) {
        return (
            name.slice(0, -3).charAt(0).toUpperCase() + name.slice(1, -3) + "y"
        );
    }
    return name.slice(0, -1).charAt(0).toUpperCase() + name.slice(1, -1);
};
const formattedTitle = formatModelName(modelName);

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
    { label: "Email", value: "email", class: "text-gray-500" },
    {
        label: "Created By",
        value: (row) => row.created_by_user?.name || "-",
        class: "text-gray-600 font-semibold",
    },
];

const expenseDetails = ref([
    {
        label: "Company",
        value: (row) => row.company?.name || "-",
        class: "text-gray-600 font-semibold",
    },
    { label: "Supplier", value: (row) => row.supplier?.name || "-" },
    { label: "Category", value: (row) => row.category?.name || "-" },
    { label: "Payment Method", value: "payment_method" },
    { label: "Currency", value: "currency" },
    { label: "Amount", value: "amount" },
    { label: "Expense Date", value: "expense_date" },
    { label: "Description", value: "description" },
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
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <DisplayInformation
                    title="Expense Information"
                    :modelData="modelData"
                    :rowDetails="expenseDetails"
                />
            </div>
        </div>
    </AppLayout>
</template>
