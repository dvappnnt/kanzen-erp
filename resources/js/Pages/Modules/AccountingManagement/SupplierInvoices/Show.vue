<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, computed, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import moment from "moment";
import { formatNumber } from "@/utils/global";
import { Link } from "@inertiajs/vue3";
import axios from "@/axios";
import { useToast } from "vue-toastification";
import Dropzone from "@/Components/Form/Dropzone.vue";

const page = usePage();
const modelData = computed(() => page.props.modelData || {});
const toast = useToast();

const showAddPaymentModal = ref(false);
const showEditPaymentModal = ref(false);
const showDeletePaymentModal = ref(false);
const showApproveModal = ref(false);
const showRejectModal = ref(false);
const showRemarksModal = ref(false);
const selectedPayment = ref(null);
const isSubmitting = ref(false);
const remarks = ref('');

const paymentForm = ref({
    supplier_invoice_id: modelData.value.id,
    company_account_id: null,
    payment_method: 'cash',
    reference_number: '',
    account_name: '',
    account_number: '',
    status: 'pending',
    payment_date: moment().format('YYYY-MM-DD'),
    amount: 0,
    file: null
});

const formatDate = (date) => {
    return date ? moment(date).format('MMMM D, YYYY') : '-';
};

const handlePrint = () => {
    const printWindow = window.open(`${window.location.origin}/supplier-invoices/${modelData.value.id}/print`, '_blank');
    
    printWindow.onload = function() {
        printWindow.print();
    };
};

const openAddPaymentModal = () => {
    paymentForm.value = {
        supplier_invoice_id: modelData.value.id,
        company_account_id: null,
        payment_method: 'cash',
        reference_number: '',
        account_name: '',
        account_number: '',
        status: 'pending',
        payment_date: moment().format('YYYY-MM-DD'),
        amount: remainingBalance.value,
        file: null
    };
    showAddPaymentModal.value = true;
};

const openEditPaymentModal = (payment) => {
    selectedPayment.value = payment;
    paymentForm.value = { ...payment };
    showEditPaymentModal.value = true;
};

const openDeletePaymentModal = (payment) => {
    selectedPayment.value = payment;
    showDeletePaymentModal.value = true;
};

const openApproveModal = (payment) => {
    selectedPayment.value = payment;
    remarks.value = '';
    showApproveModal.value = true;
};

const openRejectModal = (payment) => {
    selectedPayment.value = payment;
    remarks.value = '';
    showRejectModal.value = true;
};

const openRemarksModal = (payment) => {
    selectedPayment.value = payment;
    showRemarksModal.value = true;
};

const approvedPayments = computed(() => (modelData.value.payments || []).filter(p => p.status === 'approved'));

const totalPaid = computed(() => {
    const sum = approvedPayments.value.reduce((sum, p) => sum + parseFloat(p.amount), 0);
    return isNaN(sum) ? 0 : sum;
});

const remainingBalance = computed(() => {
    const total = parseFloat(modelData.value.total_amount) - totalPaid.value;
    return isNaN(total) ? 0 : total;
});

const validatePaymentAmount = (amount) => {
    if (amount > remainingBalance.value) {
        toast.error(`Payment amount cannot exceed the remaining balance of ${formatNumber(remainingBalance.value, { style: 'currency', currency: 'PHP' })}`);
        return false;
    }
    return true;
};

const submitPayment = async () => {
    try {
        if (!validatePaymentAmount(paymentForm.value.amount)) {
            return;
        }

        isSubmitting.value = true;
        const formData = new FormData();
        Object.keys(paymentForm.value).forEach(key => {
            if (paymentForm.value[key] !== null) {
                formData.append(key, paymentForm.value[key]);
            }
        });
        
        const response = await axios.post('/api/supplier-invoice-payments', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        toast.success('Payment added successfully');
        showAddPaymentModal.value = false;
        window.location.reload();
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to add payment');
    } finally {
        isSubmitting.value = false;
    }
};

const updatePayment = async () => {
    try {
        if (!validatePaymentAmount(paymentForm.value.amount)) {
            return;
        }

        isSubmitting.value = true;
        const formData = new FormData();
        Object.keys(paymentForm.value).forEach(key => {
            if (paymentForm.value[key] !== null) {
                formData.append(key, paymentForm.value[key]);
            }
        });
        formData.append('_method', 'PUT');
        
        const response = await axios.post(`/api/supplier-invoice-payments/${selectedPayment.value.id}`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        toast.success('Payment updated successfully');
        showEditPaymentModal.value = false;
        window.location.reload();
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to update payment');
    } finally {
        isSubmitting.value = false;
    }
};

const deletePayment = async () => {
    try {
        isSubmitting.value = true;
        await axios.delete(`/api/supplier-invoice-payments/${selectedPayment.value.id}`);
        toast.success('Payment deleted successfully');
        showDeletePaymentModal.value = false;
        window.location.reload();
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to delete payment');
    } finally {
        isSubmitting.value = false;
    }
};

const approvePayment = async () => {
    try {
        isSubmitting.value = true;
        await axios.post(`/api/supplier-invoice-payments/${selectedPayment.value.id}/approve`, {
            remarks: remarks.value
        });
        toast.success('Payment approved successfully');
        showApproveModal.value = false;
        window.location.reload();
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to approve payment');
    } finally {
        isSubmitting.value = false;
    }
};

const rejectPayment = async () => {
    try {
        isSubmitting.value = true;
        await axios.post(`/api/supplier-invoice-payments/${selectedPayment.value.id}/reject`, {
            remarks: remarks.value
        });
        toast.success('Payment rejected successfully');
        showRejectModal.value = false;
        window.location.reload();
    } catch (error) {
        toast.error(error.response?.data?.message || 'Failed to reject payment');
    } finally {
        isSubmitting.value = false;
    }
};

const downloadFile = (filePath) => {
    window.open(`/storage/${filePath}`, '_blank');
};

// Watch total payments to update invoice status
watch(() => modelData.value.payments, (newPayments) => {
    if (!newPayments) return;
    
    const totalPaid = newPayments.reduce((sum, payment) => sum + payment.amount, 0);
    const totalAmount = modelData.value.total_amount;
    
    let newStatus = 'unpaid';
    if (totalPaid >= totalAmount) {
        newStatus = 'fully-paid';
    } else if (totalPaid > 0) {
        newStatus = 'paid';
    }
    
    if (newStatus !== modelData.value.status) {
        axios.put(`/api/supplier-invoices/${modelData.value.id}`, {
            status: newStatus
        });
    }
}, { deep: true });

console.log(modelData.value);
</script>

<template>
    <AppLayout title="Supplier Invoice">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Supplier Invoice
                </h2>
                <div class="flex gap-2">
                    <Link
                        href="/supplier-invoices"
                        class="border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded text-gray-600"
                    >
                        Go Back
                    </Link>
                    <button
                        @click="handlePrint"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd" />
                        </svg>
                        Print
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div id="invoice" class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-8">
                    <!-- Header Section -->
                    <div class="border-b border-gray-200 pb-8">
                        <div class="flex justify-between">
                            <!-- Supplier Info -->
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800">{{ modelData.supplier?.name }}</h1>
                                <p class="text-gray-600">{{ modelData.supplier?.address }}</p>
                                <p class="text-gray-600">{{ modelData.supplier?.phone }}</p>
                                <p class="text-gray-600">{{ modelData.supplier?.email }}</p>
                            </div>
                            <!-- Invoice Info -->
                            <div class="text-right">
                                <h2 class="text-xl font-bold text-gray-800 mb-2">SUPPLIER INVOICE</h2>
                                <p class="text-gray-600">Invoice #: <span class="font-semibold">{{ modelData.invoice_number }}</span></p>
                                <p class="text-gray-600">Date: <span class="font-semibold">{{ formatDate(modelData.invoice_date) }}</span></p>
                                <p class="text-gray-600">Due Date: <span class="font-semibold">{{ formatDate(modelData.due_date) }}</span></p>
                                <p class="text-gray-600">Status: <span class="font-semibold capitalize">{{ modelData.status }}</span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Company & References Section -->
                    <div class="py-6 border-b border-gray-200">
                        <div class="grid grid-cols-2 gap-8">
                            <!-- Company Info -->
                            <div>
                                <h3 class="font-semibold text-gray-800 mb-2">Billed To</h3>
                                <p class="text-gray-600">Company: <span class="font-semibold">{{ modelData.company?.name }}</span></p>
                                <p class="text-gray-600">Account: <span class="font-semibold">{{ modelData.company_account?.name || 'Default Account' }}</span></p>
                            </div>
                            <!-- References -->
                            <div>
                                <h3 class="font-semibold text-gray-800 mb-2">References</h3>
                                <p class="text-gray-600">
                                    Purchase Order: 
                                    <Link 
                                        :href="`/purchase-orders/${modelData.purchase_order_id}`"
                                        class="font-semibold text-blue-600 hover:text-blue-800"
                                    >
                                        {{ modelData.purchase_order?.number }}
                                    </Link>
                                </p>
                                <p class="text-gray-600">
                                    Goods Receipt: 
                                    <Link 
                                        :href="`/goods-receipts/${modelData.goods_receipt_id}`"
                                        class="font-semibold text-blue-600 hover:text-blue-800"
                                    >
                                        {{ modelData.goods_receipt?.number }}
                                    </Link>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="py-8 border-b border-gray-200">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Details</h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                        <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SKU</th>
                                        <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variation</th>
                                        <th class="px-2 py-2 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Quantity</th>
                                        <th class="px-2 py-2 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Unit Price</th>
                                        <th class="px-2 py-2 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="detail in modelData.details" :key="detail.id">
                                        <td class="px-2 py-2">
                                            <div class="font-medium text-gray-900">
                                                {{ detail.supplier_product?.product?.name }}
                                            </div>
                                        </td>
                                        <td class="px-2 py-2">
                                            <div class="text-sm text-gray-600">
                                                {{ detail.supplier_product?.product?.sku }}
                                            </div>
                                        </td>
                                        <td class="px-2 py-2">
                                            <div class="text-sm text-gray-600">
                                                Default
                                            </div>
                                        </td>
                                        <td class="px-2 py-2 text-right">{{ formatNumber(detail.quantity) }}</td>
                                        <td class="px-2 py-2 text-right">{{ formatNumber(detail.unit_price, { style: 'currency', currency: 'PHP' }) }}</td>
                                        <td class="px-2 py-2 text-right">{{ formatNumber(detail.total, { style: 'currency', currency: 'PHP' }) }}</td>
                                    </tr>
                                    <tr v-if="!modelData.details?.length">
                                        <td colspan="6" class="px-2 py-4 text-center text-gray-500">
                                            No items found
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Payments Section -->
                    <div class="py-8 border-b border-gray-200">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Payments</h3>
                            <button
                                v-if="modelData.status !== 'fully-paid'"
                                @click="openAddPaymentModal"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded"
                            >
                                Add Payment
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                                        <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                        <th class="px-2 py-2 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                        <th class="px-2 py-2 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-2 py-2 bg-gray-50 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-2 py-2 bg-gray-50 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="payment in modelData.payments" :key="payment.id">
                                        <td class="px-2 py-2">
                                            <div class="font-medium text-gray-900 capitalize">
                                                {{ payment.payment_method }}
                                            </div>
                                        </td>
                                        <td class="px-2 py-2">
                                            <div class="text-sm text-gray-600">
                                                {{ payment.reference_number || '-' }}
                                            </div>
                                        </td>
                                        <td class="px-2 py-2">
                                            <div class="text-sm text-gray-600">
                                                {{ formatDate(payment.payment_date) }}
                                            </div>
                                        </td>
                                        <td class="px-2 py-2 text-right">
                                            {{ formatNumber(payment.amount, { style: 'currency', currency: 'PHP' }) }}
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <span :class="{
                                                'px-2 py-1 text-xs rounded-full': true,
                                                'bg-yellow-100 text-yellow-800': payment.status === 'pending',
                                                'bg-green-100 text-green-800': payment.status === 'approved',
                                                'bg-red-100 text-red-800': payment.status === 'rejected'
                                            }">
                                                {{ payment.status }}
                                            </span>
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <div class="flex justify-center space-x-2">
                                                <!-- File actions -->
                                                <button
                                                    v-if="payment.file_path"
                                                    @click="downloadFile(payment.file_path)"
                                                    class="text-blue-600 hover:text-blue-800"
                                                    title="View/Download File"
                                                >
                                                    <i class="fas fa-file-download"></i>
                                                </button>

                                                <!-- Edit/Delete actions (only for pending status) -->
                                                <template v-if="payment.status === 'pending'">
                                                    <button
                                                        @click="openEditPaymentModal(payment)"
                                                        class="text-blue-600 hover:text-blue-800"
                                                        title="Edit"
                                                    >
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button
                                                        @click="openDeletePaymentModal(payment)"
                                                        class="text-red-600 hover:text-red-800"
                                                        title="Delete"
                                                    >
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <button
                                                        @click="openApproveModal(payment)"
                                                        class="text-green-600 hover:text-green-800"
                                                        title="Approve"
                                                    >
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button
                                                        @click="openRejectModal(payment)"
                                                        class="text-red-600 hover:text-red-800"
                                                        title="Reject"
                                                    >
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </template>

                                                <!-- Show remarks if any -->
                                                <button
                                                    v-if="payment.remarks"
                                                    class="text-gray-600 hover:text-gray-800"
                                                    title="View Remarks"
                                                    @click="openRemarksModal(payment)"
                                                >
                                                    <i class="fas fa-comment-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr v-if="!modelData.payments?.length">
                                        <td colspan="6" class="px-2 py-4 text-center text-gray-500">
                                            No payments found
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Summary Section -->
                    <div class="py-8">
                        <div class="flex justify-end">
                            <div class="w-80 space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Subtotal:</span>
                                    <span class="font-medium">{{ formatNumber(modelData.subtotal, { style: 'currency', currency: 'PHP' }) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Tax ({{ modelData.tax_rate }}%):</span>
                                    <span class="font-medium">{{ formatNumber(modelData.tax_amount, { style: 'currency', currency: 'PHP' }) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Shipping Cost:</span>
                                    <span class="font-medium">{{ formatNumber(modelData.shipping_cost, { style: 'currency', currency: 'PHP' }) }}</span>
                                </div>
                                <div class="flex justify-between pt-3 border-t border-gray-200">
                                    <span class="font-semibold text-gray-800">Total Amount:</span>
                                    <span class="font-bold text-gray-800">{{ formatNumber(modelData.total_amount, { style: 'currency', currency: 'PHP' }) }}</span>
                                </div>
                                <div class="flex justify-between pt-3 border-t border-gray-200">
                                    <span class="font-semibold text-gray-800">Total Paid:</span>
                                    <span class="font-bold text-green-600">{{ formatNumber(totalPaid, { style: 'currency', currency: 'PHP' }) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="font-semibold text-gray-800">Balance:</span>
                                    <span class="font-bold text-red-600">{{ formatNumber(remainingBalance, { style: 'currency', currency: 'PHP' }) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Remarks -->
                    <div v-if="modelData.remarks" class="pt-8 border-t border-gray-200">
                        <h3 class="font-semibold text-gray-800 mb-2">Remarks</h3>
                        <p class="text-gray-600">{{ modelData.remarks }}</p>
                    </div>

                    <!-- Footer -->
                    <div class="pt-8 border-t border-gray-200 text-center text-gray-500 text-sm">
                        <p>This is a computer-generated document. No signature is required.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Payment Modal -->
        <div v-if="showAddPaymentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Add Payment</h3>
                    <button @click="showAddPaymentModal = false" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form @submit.prevent="submitPayment">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Payment Method <span class="text-red-500">*</span></label>
                            <select v-model="paymentForm.payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="cash">Cash</option>
                                <option value="bank-transfer">Bank Transfer</option>
                                <option value="credit-card">Credit Card</option>
                                <option value="gcash">GCash</option>
                                <option value="check">Check</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Reference Number <span class="text-gray-500">(Optional)</span></label>
                            <input type="text" v-model="paymentForm.reference_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Check/Payment Date <span class="text-red-500">*</span></label>
                            <input type="date" v-model="paymentForm.payment_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Amount <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" v-model="paymentForm.amount" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Attachment <span class="text-gray-500">(Optional)</span></label>
                            <Dropzone
                                id="payment-file"
                                label="Upload Payment File"
                                v-model="paymentForm.file"
                            />
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" @click="showAddPaymentModal = false" class="px-4 py-2 border rounded-md text-gray-600 hover:bg-gray-100">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSubmitting" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <span v-if="isSubmitting">Saving...</span>
                            <span v-else>Save</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Payment Modal -->
        <div v-if="showEditPaymentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Edit Payment</h3>
                    <button @click="showEditPaymentModal = false" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form @submit.prevent="updatePayment">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Payment Method <span class="text-red-500">*</span></label>
                            <select v-model="paymentForm.payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="cash">Cash</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="check">Check</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Reference Number <span class="text-gray-500">(Optional)</span></label>
                            <input type="text" v-model="paymentForm.reference_number" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Check/Payment Date <span class="text-red-500">*</span></label>
                            <input type="date" v-model="paymentForm.payment_date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Amount <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" v-model="paymentForm.amount" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Attachment <span class="text-gray-500">(Optional)</span></label>
                            <Dropzone
                                id="payment-file-edit"
                                label="Upload Payment File"
                                v-model="paymentForm.file"
                            />
                            <div v-if="selectedPayment.file_path" class="mt-2 text-sm text-gray-500">
                                Current file: 
                                <a 
                                    :href="`/storage/${selectedPayment.file_path}`" 
                                    target="_blank"
                                    class="text-blue-600 hover:text-blue-800"
                                >
                                    View current file
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" @click="showEditPaymentModal = false" class="px-4 py-2 border rounded-md text-gray-600 hover:bg-gray-100">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSubmitting" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <span v-if="isSubmitting">Saving...</span>
                            <span v-else>Save</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Payment Modal -->
        <div v-if="showDeletePaymentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3 text-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Delete Payment</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">
                            Are you sure you want to delete this payment? This action cannot be undone.
                        </p>
                    </div>
                    <div class="mt-4 flex justify-center space-x-3">
                        <button type="button" @click="showDeletePaymentModal = false" class="px-4 py-2 border rounded-md text-gray-600 hover:bg-gray-100">
                            Cancel
                        </button>
                        <button @click="deletePayment" :disabled="isSubmitting" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            <span v-if="isSubmitting">Deleting...</span>
                            <span v-else>Delete</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Approve Payment Modal -->
        <div v-if="showApproveModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Approve Payment</h3>
                    <button @click="showApproveModal = false" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form @submit.prevent="approvePayment">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Remarks</label>
                            <textarea v-model="remarks" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" @click="showApproveModal = false" class="px-4 py-2 border rounded-md text-gray-600 hover:bg-gray-100">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSubmitting" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <span v-if="isSubmitting">Approving...</span>
                            <span v-else>Approve</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Reject Payment Modal -->
        <div v-if="showRejectModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Reject Payment</h3>
                    <button @click="showRejectModal = false" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form @submit.prevent="rejectPayment">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Remarks</label>
                            <textarea v-model="remarks" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" @click="showRejectModal = false" class="px-4 py-2 border rounded-md text-gray-600 hover:bg-gray-100">
                            Cancel
                        </button>
                        <button type="submit" :disabled="isSubmitting" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            <span v-if="isSubmitting">Rejecting...</span>
                            <span v-else>Reject</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- View Remarks Modal -->
        <div v-if="showRemarksModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Payment Remarks</h3>
                    <button @click="showRemarksModal = false" class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="mt-2 text-sm text-gray-600">
                    {{ selectedPayment?.remarks }}
                </div>
                <div class="mt-6 flex justify-end">
                    <button 
                        @click="showRemarksModal = false" 
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@media print {
    @page {
        margin: 1cm;
    }
    .print\\:hidden {
        display: none !important;
    }
    #invoice {
        box-shadow: none;
        padding: 0;
    }
}
</style>
