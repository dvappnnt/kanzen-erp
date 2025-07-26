<template>
  <AppLayout title="Purchase Requests">
    <div class="p-6 bg-white rounded shadow">
      <h1 class="text-xl font-bold mb-4">Purchase Requests</h1>
      <table class="w-full text-sm border">
        <thead class="bg-gray-100">
          <tr>
            <th class="border px-4 py-2 text-left">Reference No</th>
            <th class="border px-4 py-2 text-left">Warehouse</th>
            <th class="border px-4 py-2 text-left">Status</th>
            <th class="border px-4 py-2 text-left">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="request in props.requests" :key="request.id">
            <td class="border px-4 py-2">{{ request.reference_no }}</td>
            <td class="border px-4 py-2">{{ request.warehouse.name }}</td>
            <td class="border px-4 py-2 capitalize">{{ request.status }}</td>
            <td class="border px-4 py-2 space-x-2">
              <Link
                :href="route('purchase-requests.show', request.id)"
                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded"
              >
                View
              </Link>
              <Link
                :href="route('purchase-requests.edit', request.id)"
                class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded"
              >
                Edit
              </Link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, usePage } from '@inertiajs/vue3';
const props = defineProps({ requests: Array });
</script>
