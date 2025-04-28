<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import moment from "moment";
import HeaderInformation from "@/Components/Sections/HeaderInformation.vue";
import ProfileCard from "@/Components/Sections/ProfileCard.vue";
import DisplayInformation from "@/Components/Sections/DisplayInformation.vue";
import { singularizeAndFormat, formatDate } from "@/utils/global";

const modelName = "journal-entries";

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
    { label: "Reference Number", value: "reference_number", class: "text-xl font-bold" },
    { label: "Reference Date", value: (row) => formatDate("M d Y", row.reference_date), class: "text-gray-500" },
];

const journalEntryDetails = [
    { label: "Total Debit", value: "total_debit", class: "text-gray-500" },
    { label: "Total Credit", value: "total_credit", class: "text-gray-500" },
    { label: "Remarks", value: "remarks", class: "text-gray-500" },
];

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
                    title="Journal Entry Information"
                    :modelData="modelData"
                    :rowDetails="journalEntryDetails"
                />
            </div>
        </div>
    </AppLayout>
</template>
