<script setup>
import { Link } from "@inertiajs/vue3";
import { formatNumber } from "@/utils/global";
import { useColors } from "@/Composables/useColors";
import { computed } from "vue";
import { Chart } from "vue-chartjs";
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    BarElement,
    BarController,
} from "chart.js";

ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    BarController,
    Title,
    Tooltip,
    Legend
);

const props = defineProps({
    stats: {
        type: Object,
        required: true,
        default: () => ({
            receivables: 0,
            payables: 0,
            customers_count: 0,
            warehouses_count: 0,
            cashflow: [],
        }),
    },
});

const safeStats = computed(() => ({
    receivables: props.stats?.receivables || 0,
    payables: props.stats?.payables || 0,
    customers_count: props.stats?.customers_count || 0,
    warehouses_count: props.stats?.warehouses_count || 0,
    cashflow: props.stats?.cashflow || [],
}));

const chartData = computed(() => ({
    labels: safeStats.value.cashflow.map((item) => item.month),
    datasets: [
        {
            type: "bar",
            label: "Inflow",
            data: safeStats.value.cashflow.map((item) => item.inflow),
            backgroundColor: "rgba(99, 102, 241, 0.5)",
            borderColor: "rgb(99, 102, 241)",
            borderWidth: 1,
        },
        {
            type: "bar",
            label: "Outflow",
            data: safeStats.value.cashflow
                .map((item) => item.outflow)
                .map((val) => -val),
            backgroundColor: "rgba(99, 102, 241, 0.2)",
            borderColor: "rgb(99, 102, 241)",
            borderWidth: 1,
        },
        {
            type: "line",
            label: "Net Cashflow",
            data: safeStats.value.cashflow.map((item) => item.net),
            borderColor: "rgb(0, 0, 0)",
            tension: 0.1,
            fill: false,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                callback: function (value) {
                    return formatNumber(Math.abs(value), {
                        style: "currency",
                        currency: "PHP",
                    });
                },
            },
        },
    },
    plugins: {
        legend: {
            display: true,
            position: "bottom",
        },
    },
};
</script>

<template>
    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Receivables Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-gray-500 text-sm">You will receive</h3>
                <Link
                    :href="route('pos')"
                    class="bg-gray-100 hover:bg-gray-200 text-sm px-4 py-2 rounded-lg"
                >
                    + New Invoice
                </Link>
            </div>
            <p class="text-3xl font-semibold">
                {{
                    formatNumber(safeStats.receivables, {
                        style: "currency",
                        currency: "PHP",
                    })
                }}
            </p>
        </div>

        <!-- Payables Card -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-gray-500 text-sm">You will pay</h3>
                <Link
                    :href="route('expenses.create')"
                    class="bg-gray-100 hover:bg-gray-200 text-sm px-4 py-2 rounded-lg"
                >
                    + New Bill
                </Link>
            </div>
            <p class="text-3xl font-semibold">
                {{
                    formatNumber(safeStats.payables, {
                        style: "currency",
                        currency: "PHP",
                    })
                }}
            </p>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Cashflow Chart -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-semibold">Cashflow</h3>
                <select class="bg-gray-100 rounded-lg px-3 py-1 text-sm">
                    <option>2024</option>
                </select>
            </div>
            <Chart 
                type="bar"
                :data="chartData" 
                :options="chartOptions" 
                class="h-[300px]" 
            />
        </div>

        <!-- Right Sidebar -->
        <div class="space-y-6">
            <!-- Chart of Accounts -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold">Chart of Accounts</h3>
                    <button class="text-gray-400 hover:text-gray-600">
                        More
                    </button>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center justify-between py-2">
                        <span class="text-gray-600">Customers</span>
                        <span class="font-semibold">{{
                            safeStats.customers_count
                        }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <span class="text-gray-600">Warehouses</span>
                        <span class="font-semibold">{{
                            safeStats.warehouses_count
                        }}</span>
                    </div>
                </div>
            </div>

            <!-- Reports -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-semibold">Reports</h3>
                    <button class="text-gray-400 hover:text-gray-600">
                        More
                    </button>
                </div>
                <div class="space-y-3">
                    <!-- <button
                        class="w-full text-left px-4 py-2 hover:bg-gray-50 rounded-lg flex items-center space-x-3"
                    >
                        <span class="text-gray-600">Balance Sheet</span>
                    </button>
                    <button
                        class="w-full text-left px-4 py-2 hover:bg-gray-50 rounded-lg flex items-center space-x-3"
                    >
                        <span class="text-gray-600">Profit & Loss</span>
                    </button>
                    <button
                        class="w-full text-left px-4 py-2 hover:bg-gray-50 rounded-lg flex items-center space-x-3"
                    >
                        <span class="text-gray-600">Cash Flow</span>
                    </button> -->

                    <Link
                        :href="route('expenses.index')"
                        class="w-full text-left px-4 py-2 hover:bg-gray-50 rounded-lg flex items-center space-x-3"
                    >
                        <span class="text-gray-600">Expenses</span>
                    </Link>

                    <Link
                        :href="route('invoices.index')"
                        class="w-full text-left px-4 py-2 hover:bg-gray-50 rounded-lg flex items-center space-x-3"
                    >
                        <span class="text-gray-600">Invoices</span>
                    </Link>

                    <Link
                        :href="route('journal-entries.index')"
                        class="w-full text-left px-4 py-2 hover:bg-gray-50 rounded-lg flex items-center space-x-3"
                    >
                        <span class="text-gray-600">Journal Entries</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
