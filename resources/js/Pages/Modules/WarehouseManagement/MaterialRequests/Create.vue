<template>
  <AppLayout title="Create Material Request">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Create Material Request
        </h2>
      </div>
    </template>

    <div class="max-w-6xl mx-auto mt-6">
      <div class="bg-white shadow-sm rounded-lg p-6">
        <form @submit.prevent="submit">
          <!-- Warehouse Selection -->
          <div class="mb-4">
            <label class="block mb-1 font-medium">Warehouse</label>
            <select v-model="form.warehouse_id" class="input w-full">
              <option value="" disabled>Select Warehouse</option>
              <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                {{ warehouse.name }}
              </option>
            </select>
            <p v-if="form.errors.warehouse_id" class="text-red-500 text-sm mt-1">{{ form.errors.warehouse_id }}</p>
          </div>

          <!-- Remarks -->
          <div class="mb-4">
            <label class="block mb-1 font-medium">Remarks</label>
            <textarea v-model="form.remarks" rows="2" class="input w-full" placeholder="Enter remarks (optional)" />
            <p v-if="form.errors.remarks" class="text-red-500 text-sm mt-1">{{ form.errors.remarks }}</p>
          </div>

          <!-- Items Section -->
          <div class="mb-4">
            <label class="block font-medium mb-2">Items</label>
            <div v-for="(item, index) in form.items" :key="index" class="flex gap-2 mb-2 items-center">
              <select v-model="item.product_id" class="input w-2/3">
                <option value="" disabled>Select Product</option>
                <option v-for="product in products" :key="product.id" :value="product.id">
                  {{ product.name }}
                </option>
              </select>

              <input
                type="number"
                min="1"
                v-model="item.requested_qty"
                class="input w-1/3"
                placeholder="Qty"
              />

              <button
                v-if="form.items.length > 1"
                type="button"
                @click="removeItem(index)"
                class="text-red-600 text-xl font-bold"
              >
                &times;
              </button>
            </div>

            <button type="button" @click="addItem" class="text-blue-600 font-medium mt-2">+ Add Item</button>

            <p v-if="form.errors.items" class="text-red-500 text-sm mt-1">{{ form.errors.items }}</p>
          </div>

          <!-- Submit Button -->
          <div class="mt-6">
            <button
              type="submit"
              class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow font-medium"
              :disabled="form.processing"
            >
              Submit Request
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({
  products: Array,
  warehouses: Array
})

const form = useForm({
  warehouse_id: '',
  remarks: '',
  items: [{ product_id: '', requested_qty: 1 }]
})

const addItem = () => {
  form.items.push({ product_id: '', requested_qty: 1 })
}

const removeItem = (index) => {
  form.items.splice(index, 1)
}

const submit = () => {
  form.post('/material-requests')
}
</script>
