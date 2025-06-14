<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import moment from "moment";
import HeaderInformation from "@/Components/Sections/HeaderInformation.vue";
import ProfileCard from "@/Components/Sections/ProfileCard.vue";
import { singularizeAndFormat, formatNumber } from "@/utils/global";
import { useColors } from "@/Composables/useColors";
import NavigationTabs from "@/Components/Navigation/NavigationTabs.vue";
import { useToast } from "vue-toastification";
import Autocomplete from "@/Components/Data/Autocomplete.vue";

const modelName = "suppliers";
const toast = useToast();

// Get colors from composable
const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

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
        label: "Role",
        value: (row) => row.role,
        class: "text-gray-600 font-semibold",
    },
];

const page = usePage();
const modelData = computed(() => page.props.modelData || {});
const items = ref([]);
const availableProducts = ref([]);
const isLoading = ref(false);
const supplierProducts = ref([]);
const editMode = ref({});

// Modal state
const showEditModal = ref(false);
const editingProduct = ref(null);
const editingDetail = ref(null);

const navigationTabs = ref([
    {
        text: "Overview",
        url: `/${modelName}/${modelData.value.id}`,
        inertia: true,
        permission: "read suppliers",
    },
    {
        text: "Products Information",
        url: `/${modelName}/${modelData.value.id}/products`,
        inertia: true,
        permission: "read supplier products",
    },
]);

const getProductVariations = async (productId) => {
    try {
        const response = await axios.get(`/api/products/${productId}/variations`);
        console.log('API Response:', response.data);
        const variations = response.data.data || response.data || [];
        console.log('Variations:', variations);
        
        // Map the variations to include attribute information
        const mappedVariations = variations.map(variation => {
            const attributeText = variation.attributes?.length
                ? ` (${variation.attributes.map(attr => 
                    `${attr.attribute.name}: ${attr.attribute_value.value}`
                  ).join(', ')})`
                : '';
            
            return {
                id: variation.id,
                name: variation.name,
                is_default: variation.is_default,
                sku: variation.sku,
                barcode: variation.barcode,
                displayName: `${variation.name}${attributeText}`
            };
        });
        
        console.log('Mapped Variations:', mappedVariations);
        return mappedVariations;
    } catch (error) {
        console.error('Error loading variations:', error);
        toast.error('Failed to load product variations');
        return [];
    }
};

const handleProductSelect = async (responseData) => {
    const selectedProduct = responseData.data[0];
    console.log('Selected Product:', selectedProduct);
    
    if (!selectedProduct?.id) {
        item.variations = [];
        item.variation_id = '';
        return;
    }

    // Find the current item being edited
    const currentItem = items.value.find(item => item.id === selectedProduct.tempId);
    if (!currentItem) return;

    currentItem.product_id = selectedProduct.id;
    const variations = await getProductVariations(selectedProduct.id);
    console.log('Received Variations:', variations);
    currentItem.variations = variations;
    
    // If there's only one variation (default), select it automatically
    if (variations.length === 1) {
        currentItem.variation_id = variations[0].id;
        console.log('Auto-selected variation:', currentItem.variation_id);
    }
};

const addNewRow = () => {
    const tempId = Date.now();
    items.value.push({
        id: tempId,
        product_id: '',
        variation_id: '',
        currency: 'PHP',
        price: formatNumber(0),
        variations: [],
        tempId: tempId // Add tempId for tracking
    });
};

const removeRow = (index) => {
    items.value.splice(index, 1);
};

const loadSupplierProducts = async () => {
    try {
        const response = await axios.get(`/api/suppliers/${modelData.value.id}/products`);
        supplierProducts.value = response.data;
    } catch (error) {
        console.error('Error loading supplier products:', error);
        toast.error('Failed to load supplier products');
    }
};

const saveAllRows = async () => {
    if (items.value.length === 0) {
        return;
    }

    try {
        isLoading.value = true;
        
        // Prepare the products array for submission
        const products = items.value.map(item => ({
            product_id: parseInt(item.product_id),
            has_variation: 1,
            details: [{
                product_variation_id: parseInt(item.variation_id),
                currency: item.currency || 'PHP',
                price: parseFloat(item.price) || 0.00,
                cost: parseFloat(item.price) || 0.00,
                lead_time_days: 0,
                is_default: 1
            }]
        }));

        const response = await axios.post(`/api/suppliers/${modelData.value.id}/products`, {
            products: products
        });

        if (response.data.results.success.length > 0) {
            toast.success(response.data.message);
        }

        // Handle partial failures
        if (response.data.results.errors.length > 0) {
            response.data.results.errors.forEach(error => {
                toast.error(`Row ${error.index + 1}: ${error.message}`);
            });
        }

        // Clear successfully added items
        const successIndices = response.data.results.success.map(s => s.index);
        items.value = items.value.filter((_, index) => !successIndices.includes(index));

        // Reload the supplier products
        await loadSupplierProducts();
    } catch (error) {
        console.error('Error saving products:', error);
        toast.error(error.response?.data?.message || 'Failed to save products');
    } finally {
        isLoading.value = false;
    }
};

const handleKeyDown = async (event, item) => {
    if (event.key === 'Enter') {
        event.preventDefault();
        await saveAllRows();
    }
};

const deleteProduct = async (productId) => {
    try {
        isLoading.value = true;
        await axios.delete(`/api/suppliers/${modelData.value.id}/products/${productId}`);
        toast.success('Product deleted successfully');
        await loadSupplierProducts();
    } catch (error) {
        console.error('Error deleting product:', error);
        toast.error('Failed to delete product');
    } finally {
        isLoading.value = false;
    }
};

const handleValueChange = (event, item) => {
    console.log('Selected Variation:', item.variation_id);
    console.log('Current Item State:', item);
};

const startEdit = (product, detail) => {
    editingProduct.value = product;
    editingDetail.value = {
        ...detail,
        currency: detail.currency,
        price: detail.price,
        lead_time_days: detail.lead_time_days
    };
    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingProduct.value = null;
    editingDetail.value = null;
};

const saveEdit = async () => {
    try {
        isLoading.value = true;
        const response = await axios.put(
            `/api/suppliers/${modelData.value.id}/products/${editingProduct.value.id}/details/${editingDetail.value.id}`,
            {
                currency: editingDetail.value.currency,
                price: editingDetail.value.price,
                lead_time_days: editingDetail.value.lead_time_days
            }
        );

        toast.success('Product details updated successfully');
        await loadSupplierProducts();
        closeEditModal();
    } catch (error) {
        console.error('Error updating product details:', error);
        toast.error(error.response?.data?.message || 'Failed to update product details');
    } finally {
        isLoading.value = false;
    }
};

onMounted(async () => {
    await loadSupplierProducts();
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

        <div class="max-w-4xl mx-auto">
            <NavigationTabs :tabs="navigationTabs" />

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg pt-6">
                <HeaderInformation
                    :title="`${singularizeAndFormat(modelName)} Details`"
                    :modelData="modelData"
                />
                <ProfileCard :modelData="modelData" :columns="profileDetails" />

                <div class="border-t border-gray-200 py-6">
                    <div class="px-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Supplier Products</h3>
                        
                        <!-- Products Table -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variation</th>
                                        <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Currency</th>
                                        <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Price</th>
                                        <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="(item, index) in items" :key="item.id">
                                        <td class="px-2 py-2">
                                            <Autocomplete
                                                :searchUrl="'/api/autocomplete/products'"
                                                :placeholder="'Search product...'"
                                                :modelName="'products'"
                                                :mapCustomButtons="(data) => ({ ...data, tempId: item.tempId })"
                                                @select="handleProductSelect"
                                                class="w-full"
                                            />
                                        </td>
                                        <td class="px-2 py-2">
                                            <select 
                                                v-model="item.variation_id"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                @change="handleValueChange($event, item)"
                                                :disabled="!item.product_id"
                                            >
                                                <option value="">Select Variation</option>
                                                <option v-for="variation in item.variations" 
                                                        :key="variation.id" 
                                                        :value="variation.id"
                                                        :title="variation.displayName">
                                                    {{ variation.displayName }}
                                                </option>
                                            </select>
                                            <span v-if="item.variations && item.variations.length === 0" 
                                                  class="text-xs text-gray-500 mt-1 block">
                                                No variations available
                                            </span>
                                        </td>
                                        <td class="px-2 py-2">
                                            <select 
                                                v-model="item.currency"
                                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                @keydown="handleKeyDown($event, item)"
                                            >
                                                <option value="PHP">PHP</option>
                                                <option value="USD">USD</option>
                                            </select>
                                        </td>
                                        <td class="px-2 py-2">
                                            <input 
                                                type="number" 
                                                v-model="item.price"
                                                step="0.01"
                                                min="0"
                                                @keydown="handleKeyDown($event, item)"
                                                class="block w-24 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            >
                                        </td>
                                        <td class="px-2 py-2 text-right text-sm font-medium">
                                            <button 
                                                @click="removeRow(index)"
                                                class="text-red-600 hover:text-red-900 p-1 rounded-md hover:bg-red-50"
                                                :disabled="isLoading"
                                                title="Remove"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                    <!-- Add Row Button -->
                                    <tr>
                                        <td colspan="5" class="px-2 py-2">
                                            <button 
                                                @click="addNewRow"
                                                class="text-sm text-indigo-600 hover:text-indigo-900 flex items-center"
                                                :disabled="isLoading"
                                            >
                                                <span class="mr-1">+</span> Add Row
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Existing Supplier Products Table -->
                        <div class="mt-8">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Existing Supplier Products</h3>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                            <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Currency</th>
                                            <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                            <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lead Time</th>
                                            <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr v-for="product in supplierProducts" :key="product.id">
                                            <td class="px-2 py-2">
                                                <div class="font-medium text-gray-900">{{ product.name }}</div>
                                                <div class="text-sm text-gray-500">{{ product.unit_of_measure }}</div>
                                                <div class="text-sm text-gray-600" v-if="product.variations && product.variations.length > 0">
                                                    {{ product.variations[0].name }}
                                                </div>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    <div>SKU: {{ product.variations[0]?.sku || '-' }}</div>
                                                    <div>Barcode: {{ product.variations[0]?.barcode || '-' }}</div>
                                                </div>
                                            </td>
                                            <td class="px-2 py-2" v-for="detail in product.supplier_product_details" :key="detail.id">
                                                <div>{{ detail.currency }}</div>
                                            </td>
                                            <td class="px-2 py-2" v-for="detail in product.supplier_product_details" :key="detail.id">
                                                <div>{{ formatNumber(detail.price, { style: 'currency', currency: 'PHP' }) }}</div>
                                            </td>
                                            <td class="px-2 py-2" v-for="detail in product.supplier_product_details" :key="detail.id">
                                                <div>{{ detail.lead_time_days }} days</div>
                                            </td>
                                            <td class="px-2 py-2 text-right text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <button 
                                                        @click="startEdit(product, product.supplier_product_details[0])"
                                                        class="text-indigo-600 hover:text-indigo-900 p-1 rounded-md hover:bg-indigo-50"
                                                        :disabled="isLoading"
                                                        title="Edit"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                                        </svg>
                                                    </button>
                                                    <button 
                                                        @click="deleteProduct(product.id)"
                                                        class="text-red-600 hover:text-red-900 p-1 rounded-md hover:bg-red-50"
                                                        :disabled="isLoading"
                                                        title="Delete"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <tr v-if="supplierProducts.length === 0">
                                            <td colspan="5" class="px-2 py-4 text-center text-gray-500">
                                                No products found
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="showEditModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center">
            <div class="bg-white rounded-lg p-6 max-w-md w-full">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Product Details</h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Currency</label>
                        <select 
                            v-model="editingDetail.currency"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="PHP">PHP</option>
                            <option value="USD">USD</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Price</label>
                        <input 
                            type="number" 
                            v-model="editingDetail.price"
                            step="0.01"
                            min="0"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Lead Time (days)</label>
                        <input 
                            type="number" 
                            v-model="editingDetail.lead_time_days"
                            min="0"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                    </div>
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <button 
                        @click="closeEditModal"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                        :disabled="isLoading"
                    >
                        Cancel
                    </button>
                    <button 
                        @click="saveEdit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700"
                        :disabled="isLoading"
                    >
                        Save Changes
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
