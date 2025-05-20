<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import Table from "@/Components/Data/Table.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, onMounted, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";
import axios from "@/axios";

// Access appSettings from Inertia.js page props
const { appSettings, accountTypes } = usePage().props;
const primaryColor = computed(() => appSettings?.primary_color || "#3B82F6");

// Define view mode
const viewMode = ref('tree'); // 'tree' or 'table'

// Define Header Actions
const headerActions = ref([
    {
        text: "View Mode",
        type: "dropdown",
        items: [
            {
                text: "Tree View",
                icon: "fas fa-sitemap",
                click: () => viewMode.value = 'tree'
            },
            {
                text: "Table View",
                icon: "fas fa-table",
                click: () => viewMode.value = 'table'
            }
        ]
    },
    {
        text: "Create",
        url: `/chart-of-accounts/create`,
        inertia: true,
        class: "hover:bg-opacity-90 text-white px-4 py-2 rounded",
        style: computed(() => ({
            backgroundColor: primaryColor.value,
        })),
    },
]);

// Table columns definition
const columns = [
    { key: 'code', label: 'Code', sortable: true },
    { key: 'name', label: 'Name', sortable: true },
    { key: 'type', label: 'Type', sortable: true },
    { key: 'balance', label: 'Balance', sortable: true },
];

// Function to format currency
const formatCurrency = (amount) => {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP'
    }).format(amount || 0);
};

// Flatten accounts for table view
const flattenedAccounts = computed(() => {
    const flattened = [];
    accountTypes.forEach(type => {
        type.accounts.forEach(account => {
            flattened.push({
                code: account.code,
                name: account.name,
                type: type.name,
                balance: formatCurrency(account.expenses_sum_amount || 0)
            });
        });
    });
    return flattened;
});

// Get type-specific CSS classes
const getTypeSpecificClasses = (typeCode) => {
    const classes = {
        'AS': 'bg-blue-100 text-blue-800', // Assets
        'LI': 'bg-red-100 text-red-800',   // Liabilities
        'EQ': 'bg-green-100 text-green-800', // Equity
        'RE': 'bg-purple-100 text-purple-800', // Revenue
        'EX': 'bg-orange-100 text-orange-800'  // Expenses
    };
    return classes[typeCode] || 'bg-gray-100 text-gray-800';
};

// Calculate type totals
const getTypeTotal = (type) => {
    return formatCurrency(type.total || 0);
};
</script>

<script>
// Tree view component
export const AccountTypeNode = {
    props: {
        type: {
            type: Object,
            required: true
        }
    }
};
</script>

<template>
    <AppLayout title="Chart of Accounts">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Chart of Accounts
                </h2>
                <HeaderActions :actions="headerActions" />
            </div>
        </template>

        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <!-- Tree View -->
                    <div v-if="viewMode === 'tree'">
                        <div v-for="type in accountTypes" :key="type.id" class="mb-4">
                            <div class="flex items-center justify-between p-3 rounded-lg" :class="getTypeSpecificClasses(type.code)">
                                <div class="flex items-center">
                                    <span class="font-semibold text-lg">{{ type.name }}</span>
                                    <span class="ml-2 opacity-75">({{ type.code }})</span>
                                </div>
                                <div class="font-semibold">
                                    {{ getTypeTotal(type) }}
                                </div>
                            </div>
                            <div class="ml-6 mt-2">
                                <div v-for="account in type.accounts" :key="account.id" 
                                     class="flex items-center justify-between p-2 hover:bg-gray-50 border-b border-gray-100">
                                    <div class="flex items-center">
                                        <span class="text-gray-600 font-medium">{{ account.code }}</span>
                                        <span class="mx-2">-</span>
                                        <span>{{ account.name }}</span>
                                    </div>
                                    <div class="font-medium text-gray-700">
                                        {{ formatCurrency(account.expenses_sum_amount) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table View -->
                    <div v-else>
                        <Table
                            :columns="columns"
                            :data="flattenedAccounts"
                            :sortable="true"
                            :searchable="true"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
