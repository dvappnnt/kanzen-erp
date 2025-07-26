<template>
    <AppLayout title="Internal Transfers">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-bold">Internal Transfers</h2>
                <!-- <Link
                    :href="route('internal-transfers.create')"
                    class="bg-black text-white px-4 py-2 rounded hover:bg-opacity-90"
                >
                    + Create Internal Transfer
                </Link> -->
            </div>

            <!-- Table for transfers -->
            <div v-if="transfers.length" class="overflow-x-auto">
                <table
                    class="min-w-full divide-y divide-gray-200 border border-gray-300 rounded-lg"
                >
                    <thead class="bg-gray-100">
                        <tr>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase"
                            >
                                Reference No
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase"
                            >
                                From
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase"
                            >
                                To
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase"
                            >
                                Status
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr
                            v-for="transfer in transfers"
                            :key="transfer.id"
                            class="hover:bg-gray-50"
                        >
                            <td
                                class="px-6 py-3 font-medium text-sm text-gray-800"
                            >
                                {{ transfer.reference_no }}
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-700">
                                {{ transfer.from_warehouse.name }}
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-700">
                                {{ transfer.to_warehouse.name }}
                            </td>
                            <td class="px-6 py-3 text-sm">
                                <span
                                    :class="[
                                        'inline-block px-2 py-1 text-xs font-semibold rounded',
                                        transfer.status === 'pending'
                                            ? 'bg-yellow-100 text-yellow-800'
                                            : transfer.status === 'approved'
                                            ? 'bg-green-100 text-green-800'
                                            : 'bg-gray-100 text-gray-800',
                                    ]"
                                >
                                    {{ transfer.status }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm flex gap-2">
                                <Link
                                    :href="
                                        route(
                                            'internal-transfers.show',
                                            transfer.id
                                        )
                                    "
                                    class="bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition"
                                >
                                    View
                                </Link>
                                <!-- <Link
                                    :href="
                                        route(
                                            'internal-transfers.edit',
                                            transfer.id
                                        )
                                    "
                                    class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 transition"
                                >
                                    Edit
                                </Link> -->
                                <Link
                                    :href="
                                        transfer.status !== 'transferred'
                                            ? route(
                                                  'internal-transfers.edit',
                                                  transfer.id
                                              )
                                            : '#'
                                    "
                                    class="px-3 py-1 rounded transition"
                                    :class="[
                                        transfer.status === 'transferred'
                                            ? 'bg-gray-400 text-white cursor-not-allowed'
                                            : 'bg-green-600 text-white hover:bg-green-700',
                                    ]"
                                    :disabled="
                                        transfer.status === 'transferred'
                                    "
                                >
                                    Edit
                                </Link>

                                <Link
                                    v-if="transfer.status === 'approved'"
                                    :href="`/warehouses/${transfer.to_warehouse.id}?transfer_id=${transfer.id}`"
                                    class="bg-purple-600 text-white px-3 py-1 rounded hover:bg-purple-700 transition"
                                >
                                    Transfer
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <p v-else class="text-gray-600 mt-4">
                No internal transfers found.
            </p>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { Link, usePage } from "@inertiajs/vue3";

const { props } = usePage();
const transfers = props.transfers || [];
</script>
