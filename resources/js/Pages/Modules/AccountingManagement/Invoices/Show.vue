<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import { ref, computed } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import moment from "moment";
import { formatNumber, humanReadable } from "@/utils/global";
import Dropzone from "@/Components/Form/Dropzone.vue";
import axios from "axios";
import { useToast } from "vue-toastification";

const page = usePage();
const modelData = computed(() => page.props.modelData || {});
const toast = useToast();

const formatDate = (date) => {
    return moment(date).format('MMMM D, YYYY');
};

const formatPaymentMethod = (method) => {
    const methods = {
        'cash': 'Cash',
        'gcash': 'GCash',
        'credit-card': 'Credit Card',
        'bank-transfer': 'Bank Transfer'
    };
    return methods[method] || method;
};

const printInvoice = () => {
    // Open the print window
    const printWindow = window.open(`${window.location.origin}/invoices/${modelData.value.id}/print`, '_blank');
    
    // Wait for the window to load and trigger print
    printWindow.onload = function() {
        printWindow.print();
    };
};

const downloadReceipt = (path) => {
    window.open(`/storage/${path}`, '_blank');
};

// Helper function to chunk array into groups
const chunkArray = (array, size) => {
    const chunked = [];
    for (let i = 0; i < array.length; i += size) {
        chunked.push(array.slice(i, i + size));
    }
    return chunked;
};

// Format serial numbers in a more compact way
const formatSerialNumbers = (serials) => {
    return serials.map(serial => {
        const productSerial = serial.warehouse_product_serial;
        const parts = [];
        
        // Add serial/batch number
        parts.push(productSerial.serial_number);
        if (productSerial.batch_number) {
            parts.push(`Batch: ${productSerial.batch_number}`);
        }
        
        // Add dates if present
        const dates = [];
        if (productSerial.manufactured_at) {
            dates.push(`Mfg: ${formatDate(productSerial.manufactured_at)}`);
        }
        if (productSerial.expired_at) {
            dates.push(`Exp: ${formatDate(productSerial.expired_at)}`);
        }
        
        return {
            main: parts.join(' | '),
            dates: dates.join(' | ')
        };
    });
};

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

const approvedPayments = computed(() => (modelData.value.payment_method_details || []).filter(p => p.status === 'approved'));

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

const openAddPaymentModal = () => {
    paymentForm.value = {
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
        
        const response = await axios.post(`/api/invoices/${modelData.value.id}/payments`, formData, {
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
        
        const response = await axios.post(`/api/invoices/${modelData.value.id}/payments/${selectedPayment.value.id}`, formData, {
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
        await axios.delete(`/api/invoices/${modelData.value.id}/payments/${selectedPayment.value.id}`);
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
        await axios.post(`/api/invoices/${modelData.value.id}/payments/${selectedPayment.value.id}/approve`, {
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
        await axios.post(`/api/invoices/${modelData.value.id}/payments/${selectedPayment.value.id}/reject`, {
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
</script>

<template>
    <AppLayout title="Sales Invoice">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Invoice
                </h2>
                <div class="flex gap-2">
                    <Link
                        href="/invoices"
                        class="border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded text-gray-600"
                    >
                        Go Back
                    </Link>
                    <button
                        @click="printInvoice"
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
                            <!-- Company Info -->
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800">{{ modelData.company?.name }}</h1>
                                <p class="text-gray-600">{{ modelData.company?.address }}</p>
                                <p class="text-gray-600">{{ modelData.company?.mobile }}</p>
                                <p class="text-gray-600">{{ modelData.company?.email }}</p>
                            </div>
                            <!-- Invoice Info -->
                            <div class="text-right">
                                <h2 class="text-xl font-bold text-gray-800 mb-2">SALES INVOICE</h2>
                                <p class="text-gray-600">Invoice #: <span class="font-semibold">{{ modelData.number }}</span></p>
                                <p class="text-gray-600">Date: <span class="font-semibold">{{ formatDate(modelData.invoice_date) }}</span></p>
                                <p class="text-gray-600">Due Date: <span class="font-semibold">{{ modelData.due_date ? formatDate(modelData.due_date) : '-' }}</span></p>
                                <p class="text-gray-600">Shipping Method: <span class="font-semibold">{{ humanReadable(modelData.shipping_method) }}</span></p>
                                <p class="text-gray-600">Shipment Status: <span class="font-semibold capitalize">{{ humanReadable(modelData.shipment_status) }}</span></p>
                                <p class="text-gray-600">Status: <span class="font-semibold capitalize">{{ humanReadable(modelData.status) }}</span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Bill To & Ship To Section -->
                    <div class="grid grid-cols-2 gap-8 py-8 border-b border-gray-200">
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-3">Bill To:</h3>
                            <p class="font-medium text-gray-800">{{ modelData.customer?.name }}</p>
                            <p class="text-gray-600">{{ modelData.customer?.address }}</p>
                            <p class="text-gray-600">{{ modelData.customer?.phone }}</p>
                            <p class="text-gray-600">{{ modelData.customer?.email }}</p>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-800 mb-3">Ship From:</h3>
                            <p class="font-medium text-gray-800">{{ modelData.warehouse?.name }}</p>
                            <p class="text-gray-600">{{ modelData.warehouse?.address }}</p>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="py-8 border-b border-gray-200">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left">
                                    <th class="pb-4 text-gray-600 text-sm font-semibold">Item Description</th>
                                    <th class="pb-4 text-gray-600 text-sm font-semibold text-right">Unit</th>
                                    <th class="pb-4 text-gray-600 text-sm font-semibold text-right">Qty</th>
                                    <th class="pb-4 text-gray-600 text-sm font-semibold text-right">Price</th>
                                    <th class="pb-4 text-gray-600 text-sm font-semibold text-right">Amount</th>
                                </tr>
                            </thead>
                            <tbody class="border-t border-gray-100">
                                <tr v-for="detail in modelData.details" :key="detail.id" class="border-b border-gray-50">
                                    <td class="py-4">
                                        <p class="font-medium text-gray-800">
                                            {{ detail.warehouse_product?.supplier_product_detail?.product?.name }}
                                        </p>
                                        <div v-if="detail.invoice_serials?.length" class="text-sm text-gray-500 mt-2 space-y-1">
                                            <div v-for="(serial, index) in formatSerialNumbers(detail.invoice_serials)" :key="index" class="pl-4 border-l-2 border-gray-200">
                                                <p class="font-medium">{{ serial.main }}</p>
                                                <p class="text-xs text-gray-400">{{ serial.dates }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 text-right">{{ detail.warehouse_product?.supplier_product_detail?.product?.unit_of_measure }}</td>
                                    <td class="py-4 text-right">{{ detail.qty }}</td>
                                    <td class="py-4 text-right">{{ formatNumber(detail.price, { style: 'currency', currency: modelData.currency }) }}</td>
                                    <td class="py-4 text-right">{{ formatNumber(detail.total, { style: 'currency', currency: modelData.currency }) }}</td>
                                </tr>
                            </tbody>
                        </table>
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

                    <!-- Payments Section -->
                    <div v-if="modelData.is_credit" class="py-8 border-b border-gray-200">
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
                                    <tr v-for="payment in modelData.payment_method_details" :key="payment.id">
                                        <td class="px-2 py-2">
                                            <div class="font-medium text-gray-900 capitalize">
                                                {{ humanReadable(payment.payment_method) }}
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
                                                    v-if="payment.receipt_attachment"
                                                    @click="downloadFile(payment.receipt_attachment)"
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
                                    <tr v-if="!modelData.payment_method_details?.length">
                                        <td colspan="6" class="px-2 py-4 text-center text-gray-500">
                                            No payments found
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="pt-8 border-t border-gray-200 text-center text-gray-500 text-sm">
                        <p>Thank you for your business!</p>
                        <p class="mt-1">For questions about this invoice, please contact {{ modelData.company?.name }}</p>
                        <p class="mt-1">{{ modelData.company?.email }} | {{ modelData.company?.mobile }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

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
                            id="payment-file-edit"
                            label="Upload Payment File"
                            v-model="paymentForm.file"
                        />
                        <div v-if="selectedPayment.receipt_attachment" class="mt-2 text-sm text-gray-500">
                            Current file: 
                            <a 
                                :href="`/storage/${selectedPayment.receipt_attachment}`" 
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
