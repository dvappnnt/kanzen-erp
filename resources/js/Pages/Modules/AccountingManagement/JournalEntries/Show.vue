<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import moment from "moment";
import HeaderInformation from "@/Components/Sections/HeaderInformation.vue";
import ProfileCard from "@/Components/Sections/ProfileCard.vue";
import DisplayInformation from "@/Components/Sections/DisplayInformation.vue";
import { singularizeAndFormat, formatDate, formatNumber } from "@/utils/global";

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
    { label: "Total Debit", value: (row) => formatNumber(row.total_debit, { style: 'currency', currency: 'PHP' }), class: "text-gray-500" },
    { label: "Total Credit", value: (row) => formatNumber(row.total_credit, { style: 'currency', currency: 'PHP' }), class: "text-gray-500" },
    { label: "Remarks", value: "remarks", class: "text-gray-500" },
];

const page = usePage();
const modelData = computed(() => page.props.modelData || {});

// Calculate totals
const totals = computed(() => {
    return modelData.value.details?.reduce((acc, detail) => {
        acc.debit += parseFloat(detail.debit || 0);
        acc.credit += parseFloat(detail.credit || 0);
        return acc;
    }, { debit: 0, credit: 0 }) || { debit: 0, credit: 0 };
});
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

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8">
                <!-- Header Information -->
                <div class="border-b border-gray-200 pb-8">
                    <div class="grid grid-cols-2 gap-8">
                        <div>
                            <h2 class="text-xl font-bold text-gray-800 mb-4">Journal Entry Information</h2>
                            <div class="space-y-2">
                                <p class="text-gray-600">Reference #: <span class="font-semibold">{{ modelData.reference_number }}</span></p>
                                <p class="text-gray-600">Date: <span class="font-semibold">{{ formatDate("M d Y", modelData.reference_date) }}</span></p>
                                <p class="text-gray-600">Company: <span class="font-semibold">{{ modelData.company?.name }}</span></p>
                            </div>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-800 mb-4">Additional Information</h2>
                            <div class="space-y-2">
                                <p class="text-gray-600">Created By: <span class="font-semibold">{{ modelData.created_by_user?.name }}</span></p>
                                <p class="text-gray-600">Created At: <span class="font-semibold">{{ formatDate("M d Y", modelData.created_at) }}</span></p>
                                <p class="text-gray-600">Remarks: <span class="font-semibold">{{ modelData.remarks }}</span></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Journal Entry Details Table -->
                <div class="mt-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Journal Entry Details</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="detail in modelData.details" :key="detail.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ detail.account?.code }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ detail.account?.name }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        {{ detail.name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right" :class="detail.debit > 0 ? 'text-green-600 font-medium' : 'text-gray-500'">
                                        {{ formatNumber(detail.debit, { style: 'currency', currency: 'PHP' }) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right" :class="detail.credit > 0 ? 'text-red-600 font-medium' : 'text-gray-500'">
                                        {{ formatNumber(detail.credit, { style: 'currency', currency: 'PHP' }) }}
                                    </td>
                                </tr>

                                <!-- Totals Row -->
                                <tr class="bg-gray-50 font-medium">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" colspan="3">
                                        Total
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-green-600 font-bold">
                                        {{ formatNumber(totals.debit, { style: 'currency', currency: 'PHP' }) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-red-600 font-bold">
                                        {{ formatNumber(totals.credit, { style: 'currency', currency: 'PHP' }) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Balance Check -->
                <div v-if="totals.debit !== totals.credit" class="mt-4 p-4 bg-yellow-50 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                Warning: Journal Entry is not balanced
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>
                                    The total debits ({{ formatNumber(totals.debit, { style: 'currency', currency: 'PHP' }) }})
                                    do not equal the total credits ({{ formatNumber(totals.credit, { style: 'currency', currency: 'PHP' }) }}).
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
