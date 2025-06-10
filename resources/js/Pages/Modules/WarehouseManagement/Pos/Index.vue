<template>
    <AppLayout title="Point of Sale">
        <!-- Step 1: Customer Selection -->
        <CustomerSelection
            v-if="currentStep === 1"
            @proceed="handleCustomerSelection"
        />

        <!-- Step 2: Product Selection and Cart -->
        <div
            v-else-if="currentStep === 2"
            class="flex h-[calc(100vh-4rem)] bg-gray-50"
        >
            <!-- Left Side - Products Section -->
            <div class="w-2/3 p-6 overflow-hidden flex flex-col">
                <!-- Search and Categories -->
                <div class="mb-6 flex gap-4">
                    <div class="flex-1">
                        <input
                            type="text"
                            v-model="searchQuery"
                            @input="handleSearch"
                            placeholder="Search products..."
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>
                    <!-- <div class="flex-1">
                        <select
                            v-model="selectedCategory"
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                            <option value="">All Categories</option>
                            <option
                                v-for="category in categories"
                                :key="category.id"
                                :value="category.id"
                            >
                                {{ category.name }}
                            </option>
                        </select>
                    </div> -->
                </div>

                <!-- Products Grid -->
                <div class="flex-1 overflow-y-auto">
                    <div class="grid grid-cols-3 gap-4">
                        <div
                            v-for="product in filteredProducts"
                            :key="product.id"
                            class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow cursor-pointer p-4"
                            @click="addToCart(product)"
                        >
                            <div
                                class="aspect-square bg-gray-100 rounded-lg mb-3 flex items-center justify-center"
                            >
                                <img
                                    v-if="product.supplier_product_detail.product.avatar"
                                    :src="`/storage/${product.supplier_product_detail.product.avatar}`"
                                    :alt="product.supplier_product_detail.product.name"
                                    class="h-10 w-10 rounded-full object-cover"
                                />
                                <span v-else class="text-4xl text-gray-400">📦</span>
                            </div>
                            <h3 class="font-medium text-gray-900">
                                {{
                                    product.supplier_product_detail.product.name
                                }}
                            </h3>
                            <p class="text-gray-500 text-sm">
                                SKU:
                                {{
                                    product.supplier_product_detail.product.slug
                                }}
                            </p>
                            <p class="text-blue-600 font-semibold mt-2">
                                {{
                                    formatNumber(parseFloat(product.price), {
                                        style: "currency",
                                        currency: "PHP",
                                    })
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Cart Section -->
            <div class="w-1/3 bg-white border-l border-gray-200 flex flex-col">
                <!-- Cart Header -->
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Current Cart
                    </h2>
                    <!-- <p class="text-sm text-gray-500">
                        Transaction #TRX-{{
                            new Date().getTime().toString().slice(-6)
                        }}
                    </p> -->
                </div>

                <!-- Cart Items -->
                <div class="flex-1 overflow-y-auto p-6">
                    <div class="space-y-4">
                        <div
                            v-for="item in cartItems"
                            :key="item.id"
                            class="flex items-center justify-between bg-gray-50 p-4 rounded-lg"
                        >
                            <div class="flex items-center space-x-4">
                                <div
                                    class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center"
                                >
                                    <img
                                        v-if="item.avatar"
                                        :src="`/storage/${item.avatar}`"
                                        :alt="item.name"
                                        class="h-10 w-10 rounded-full object-cover"
                                    />
                                    <span v-else class="text-4xl text-gray-400">📦</span>
                                </div>
                                <div>
                                    <h4 class="font-medium">{{ item.name }}</h4>
                                    <p class="text-sm text-gray-500">
                                        {{
                                            formatNumber(item.price, {
                                                style: "currency",
                                                currency: "PHP",
                                            })
                                        }}
                                    </p>
                                    <p
                                        v-if="
                                            item.serials &&
                                            item.serials.length > 0
                                        "
                                        @click="showSerialListModal(item)"
                                        class="text-sm text-blue-600 cursor-pointer hover:text-blue-800"
                                    >
                                        {{ item.serials.length }} serial/batch
                                        number(s)
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <template v-if="!item.serials || item.serials.length === 0">
                                    <button
                                        @click="updateQuantity(item, -1)"
                                        class="text-gray-500 hover:text-gray-700"
                                    >
                                        <span class="text-xl">-</span>
                                    </button>
                                    <span class="w-8 text-center">{{
                                        item.quantity
                                    }}</span>
                                    <button
                                        @click="updateQuantity(item, 1)"
                                        class="text-gray-500 hover:text-gray-700"
                                    >
                                        <span class="text-xl">+</span>
                                    </button>
                                </template>
                                <template v-else>
                                    <span class="w-8 text-center">{{ item.serials.length }}</span>
                                </template>
                                <button
                                    @click="removeFromCart(item)"
                                    class="text-red-500 hover:text-red-700 ml-2"
                                >
                                    <svg
                                        class="w-5 h-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        ></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cart Summary -->
                <div class="border-t border-gray-200 p-6 space-y-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">Subtotal</span>
                        <span class="font-medium">{{
                            formatNumber(subtotal, {
                                style: "currency",
                                currency: "PHP",
                            })
                        }}</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <div class="flex items-center gap-2">
                            <span class="text-gray-600">Tax Rate</span>
                            <button
                                @click="showTaxModal = true"
                                class="text-gray-400 hover:text-gray-600"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                    />
                                </svg>
                            </button>
                        </div>
                        <span class="font-medium">{{ taxRate }}%</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <div class="flex items-center gap-2">
                            <span class="text-gray-600">Tax Amount</span>
                            <button
                                @click="showTaxModal = true"
                                class="text-gray-400 hover:text-gray-600"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                    />
                                </svg>
                            </button>
                        </div>
                        <span class="font-medium">{{
                            formatNumber(taxAmount, {
                                style: "currency",
                                currency: "PHP",
                            })
                        }}</span>
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <div class="flex items-center gap-2">
                            <span class="text-gray-600">Discount</span>
                            <button
                                @click="showDiscountModal = true"
                                class="text-gray-400 hover:text-gray-600"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                    />
                                </svg>
                            </button>
                        </div>
                        <span class="font-medium text-green-600"
                            >-{{
                                formatNumber(discountAmount, {
                                    style: "currency",
                                    currency: "PHP",
                                })
                            }}</span
                        >
                    </div>
                    <div class="flex justify-between text-sm items-center">
                        <div class="flex items-center gap-2">
                            <span class="text-gray-600">Shipping</span>
                            <button
                                @click="showShippingModal = true"
                                class="text-gray-400 hover:text-gray-600"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                    />
                                </svg>
                            </button>
                        </div>
                        <span class="font-medium">{{
                            formatNumber(shippingAmount, {
                                style: "currency",
                                currency: "PHP",
                            })
                        }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-semibold">
                        <span>Total</span>
                        <span>{{
                            formatNumber(total, {
                                style: "currency",
                                currency: "PHP",
                            })
                        }}</span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="grid grid-cols-2 gap-4 pt-4">
                        <button
                            @click="clearCart"
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-medium"
                        >
                            Clear Cart
                        </button>
                        <button
                            @click="showCheckoutModal = true"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium"
                        >
                            Checkout
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Review Order -->
        <CheckoutReview
            v-else-if="currentStep === 3"
            :customer-info="customerInfo"
            :cart-items="cartItems"
            :payment-details="reviewData"
            :subtotal="subtotal"
            :tax-rate="taxRate"
            :tax-amount="taxAmount"
            :discount-type="discountType"
            :discount-value="discountValue"
            :discount-amount="discountAmount"
            :shipping-amount="shippingAmount"
            :shipping-method="shippingMethod || 'pickup'"
            :total="total"
            @back="currentStep = 2"
            @proceed="handleProceedToReceipt"
        />

        <!-- Step 4: Receipt -->
        <Receipt
            v-else-if="currentStep === 4"
            :invoice="invoice"
            :payment-details="{
                method: paymentMethod,
                account_number: paymentDetails.account_number,
                account_name: paymentDetails.account_name,
                bank: selectedBank,
                company_account: selectedCompanyAccount,
            }"
            @newTransaction="startNewTransaction"
        />

        <!-- Checkout Modal -->
        <TransitionRoot appear :show="showCheckoutModal" as="template">
            <Dialog
                as="div"
                @close="showCheckoutModal = false"
                class="relative z-10"
            >
                <TransitionChild
                    enter="duration-300 ease-out"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="duration-200 ease-in"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-black bg-opacity-25" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div
                        class="flex min-h-full items-center justify-center p-4"
                    >
                        <TransitionChild
                            enter="duration-300 ease-out"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="duration-200 ease-in"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel
                                class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all"
                            >
                                <DialogTitle
                                    as="h3"
                                    class="text-lg font-medium leading-6 text-gray-900 mb-4"
                                >
                                    Checkout Details
                                </DialogTitle>

                                <div class="space-y-4">
                                    <!-- Payment Method -->
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-1"
                                        >
                                            Payment Method
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            v-model="paymentMethod"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        >
                                            <option value="">
                                                Select payment method
                                            </option>
                                            <option value="cash">Cash</option>
                                            <option value="gcash">GCash</option>
                                            <!-- <option value="credit-card">Card</option>
                                            <option value="bank-transfer">
                                                Bank Transfer
                                            </option> -->
                                        </select>
                                    </div>

                                    <!-- GCash Fields -->
                                    <div
                                        v-if="paymentMethod === 'gcash'"
                                        class="space-y-4"
                                    >
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Mobile Number
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                v-model="paymentDetails.account_number"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Enter GCash mobile number"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Reference Number
                                                <span class="text-red-500">*</span>
                                            </label>
                                            <input
                                                type="text"
                                                v-model="paymentDetails.reference_number"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Enter GCash reference number"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Receipt Attachment</label>
                                            <Dropzone
                                                id="receipt-attachment"
                                                label="Upload Receipt (Optional)"
                                                v-model="paymentDetails.receipt_attachment"
                                            />
                                        </div>
                                    </div>

                                    <!-- Card Fields -->
                                    <div
                                        v-if="paymentMethod === 'credit-card'"
                                        class="space-y-4"
                                    >
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-1"
                                            >
                                                Account Number
                                                <span class="text-red-500"
                                                    >*</span
                                                >
                                            </label>
                                            <input
                                                type="text"
                                                v-model="
                                                    paymentDetails.account_number
                                                "
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Enter card number"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-1"
                                            >
                                                Account Name
                                                <span class="text-red-500"
                                                    >*</span
                                                >
                                            </label>
                                            <input
                                                type="text"
                                                v-model="
                                                    paymentDetails.account_name
                                                "
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Enter account name"
                                            />
                                        </div>
                                    </div>

                                    <!-- Bank Transfer Fields -->
                                    <div
                                        v-if="paymentMethod === 'bank-transfer'"
                                        class="space-y-4"
                                    >
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-1"
                                            >
                                                Account Number
                                                <span class="text-red-500"
                                                    >*</span
                                                >
                                            </label>
                                            <input
                                                type="text"
                                                v-model="
                                                    paymentDetails.account_number
                                                "
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Enter account number"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-1"
                                            >
                                                Account Name
                                                <span class="text-red-500"
                                                    >*</span
                                                >
                                            </label>
                                            <input
                                                type="text"
                                                v-model="
                                                    paymentDetails.account_name
                                                "
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Enter account name"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-1"
                                            >
                                                Bank
                                                <span class="text-red-500"
                                                    >*</span
                                                >
                                            </label>
                                            <div class="relative">
                                                <input
                                                    type="text"
                                                    v-model="bankSearch"
                                                    @input="searchBanks"
                                                    @focus="
                                                        showBankResults = true
                                                    "
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="Search bank..."
                                                />
                                                <!-- Bank Search Results -->
                                                <div
                                                    v-if="
                                                        showBankResults &&
                                                        bankResults.length > 0
                                                    "
                                                    class="absolute z-10 w-full mt-1 bg-white rounded-md shadow-lg border border-gray-200"
                                                >
                                                    <ul
                                                        class="max-h-60 overflow-auto py-1"
                                                    >
                                                        <li
                                                            v-for="bank in bankResults"
                                                            :key="bank.id"
                                                            @click="
                                                                selectBank(bank)
                                                            "
                                                            class="px-4 py-2 hover:bg-gray-100 cursor-pointer"
                                                        >
                                                            {{ bank.name }}
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-sm font-medium text-gray-700 mb-1"
                                            >
                                                Company Account
                                                <span class="text-red-500"
                                                    >*</span
                                                >
                                            </label>
                                            <div class="relative">
                                                <input
                                                    type="text"
                                                    v-model="
                                                        companyAccountSearch
                                                    "
                                                    @input="
                                                        searchCompanyAccounts
                                                    "
                                                    @focus="
                                                        showCompanyAccountResults = true
                                                    "
                                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                    placeholder="Search company account..."
                                                />
                                                <!-- Company Account Search Results -->
                                                <div
                                                    v-if="
                                                        showCompanyAccountResults &&
                                                        companyAccountResults.length >
                                                            0
                                                    "
                                                    class="absolute z-10 w-full mt-1 bg-white rounded-md shadow-lg border border-gray-200"
                                                >
                                                    <ul
                                                        class="max-h-60 overflow-auto py-1"
                                                    >
                                                        <li
                                                            v-for="account in companyAccountResults"
                                                            :key="account.id"
                                                            @click="
                                                                selectCompanyAccount(
                                                                    account
                                                                )
                                                            "
                                                            class="px-4 py-2 hover:bg-gray-100 cursor-pointer"
                                                        >
                                                            {{ account.name }}
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Shipping Method -->
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-1"
                                        >
                                            Shipping Method
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            v-model="shippingMethod"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        >
                                            <option value="">
                                                Select shipping method
                                            </option>
                                            <option value="pickup">Pickup</option>
                                            <option value="delivery">Delivery</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-end gap-3">
                                    <button
                                        @click="showCheckoutModal = false"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        @click="proceedToReview"
                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md"
                                    >
                                        Proceed to Review
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Tax Modal -->
        <TransitionRoot appear :show="showTaxModal" as="template">
            <Dialog
                as="div"
                @close="showTaxModal = false"
                class="relative z-10"
            >
                <TransitionChild
                    enter="duration-300 ease-out"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="duration-200 ease-in"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-black bg-opacity-25" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div
                        class="flex min-h-full items-center justify-center p-4"
                    >
                        <TransitionChild
                            enter="duration-300 ease-out"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="duration-200 ease-in"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel
                                class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all"
                            >
                                <DialogTitle
                                    as="h3"
                                    class="text-lg font-medium leading-6 text-gray-900 mb-4"
                                >
                                    Edit Tax
                                </DialogTitle>

                                <div class="space-y-4">
                                    <div v-if="discountType === 'senior' || discountType === 'pwd'" class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-sm text-yellow-700">
                                                    Tax is automatically set to 0% for {{ discountType === 'senior' ? 'Senior' : 'PWD' }} discounts. To edit tax settings, please select either Percentage or Fixed Amount discount type.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-1"
                                        >
                                            Tax Rate (%)
                                        </label>
                                        <input
                                            type="number"
                                            v-model="tempTaxRate"
                                            @input="updateTaxAmount"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                            :disabled="discountType === 'senior' || discountType === 'pwd'"
                                            :class="{ 'bg-gray-100 cursor-not-allowed': discountType === 'senior' || discountType === 'pwd' }"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-1"
                                        >
                                            Tax Amount
                                        </label>
                                        <input
                                            type="number"
                                            v-model="tempTaxAmount"
                                            @input="updateTaxRate"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                            :disabled="discountType === 'senior' || discountType === 'pwd'"
                                            :class="{ 'bg-gray-100 cursor-not-allowed': discountType === 'senior' || discountType === 'pwd' }"
                                        />
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-end gap-3">
                                    <button
                                        @click="showTaxModal = false"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        @click="saveTaxChanges"
                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md"
                                    >
                                        Save Changes
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Discount Modal -->
        <TransitionRoot appear :show="showDiscountModal" as="template">
            <Dialog
                as="div"
                @close="showDiscountModal = false"
                class="relative z-10"
            >
                <TransitionChild
                    enter="duration-300 ease-out"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="duration-200 ease-in"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-black bg-opacity-25" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div
                        class="flex min-h-full items-center justify-center p-4"
                    >
                        <TransitionChild
                            enter="duration-300 ease-out"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="duration-200 ease-in"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel
                                class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all"
                            >
                                <DialogTitle
                                    as="h3"
                                    class="text-lg font-medium leading-6 text-gray-900 mb-4"
                                >
                                    Edit Discount
                                </DialogTitle>

                                <div class="space-y-4">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-1"
                                        >
                                            Discount Type
                                        </label>
                                        <select
                                            v-model="discountType"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        >
                                            <option value="percentage">
                                                Percentage
                                            </option>
                                            <option value="fixed">
                                                Fixed Amount
                                            </option>
                                            <option value="senior">
                                                Senior Discount
                                            </option>
                                            <option value="pwd">
                                                PWD Discount
                                            </option>
                                        </select>
                                    </div>
                                    <div v-if="discountType === 'percentage' || discountType === 'fixed'">
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-1"
                                        >
                                            {{
                                                discountType === "percentage"
                                                    ? "Discount Percentage"
                                                    : "Discount Amount"
                                            }}
                                        </label>
                                        <input
                                            type="number"
                                            v-model="discountValue"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                        />
                                    </div>
                                    <div v-if="discountType === 'senior' || discountType === 'pwd'" class="text-sm text-gray-600">
                                        <p v-if="discountType === 'senior'">
                                            Senior citizens are entitled to a 20% discount and are exempt from VAT.
                                        </p>
                                        <p v-if="discountType === 'pwd'">
                                            PWDs are entitled to a 20% discount and are exempt from VAT.
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-end gap-3">
                                    <button
                                        @click="showDiscountModal = false"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        @click="saveDiscountChanges"
                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md"
                                    >
                                        Save Changes
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Shipping Modal -->
        <TransitionRoot appear :show="showShippingModal" as="template">
            <Dialog
                as="div"
                @close="showShippingModal = false"
                class="relative z-10"
            >
                <TransitionChild
                    enter="duration-300 ease-out"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="duration-200 ease-in"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-black bg-opacity-25" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div
                        class="flex min-h-full items-center justify-center p-4"
                    >
                        <TransitionChild
                            enter="duration-300 ease-out"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="duration-200 ease-in"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel
                                class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all"
                            >
                                <DialogTitle
                                    as="h3"
                                    class="text-lg font-medium leading-6 text-gray-900 mb-4"
                                >
                                    Edit Shipping
                                </DialogTitle>

                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-1"
                                    >
                                        Shipping Amount
                                    </label>
                                    <input
                                        type="number"
                                        v-model="tempShippingAmount"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                    />
                                </div>

                                <div class="mt-6 flex justify-end gap-3">
                                    <button
                                        @click="showShippingModal = false"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        @click="saveShippingChanges"
                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md"
                                    >
                                        Save Changes
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Serial Number Input Modal -->
        <TransitionRoot appear :show="showSerialModal" as="template">
            <Dialog
                as="div"
                @close="showSerialModal = false"
                class="relative z-10"
            >
                <TransitionChild
                    enter="duration-300 ease-out"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="duration-200 ease-in"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-black bg-opacity-25" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div
                        class="flex min-h-full items-center justify-center p-4"
                    >
                        <TransitionChild
                            enter="duration-300 ease-out"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="duration-200 ease-in"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel
                                class="w-full max-w-3xl transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all"
                            >
                                <DialogTitle
                                    as="h3"
                                    class="text-lg font-medium leading-6 text-gray-900 mb-4"
                                >
                                    Enter Serial/Batch Number
                                </DialogTitle>

                                <div class="space-y-4">
                                    <div>
                                        <label
                                            class="block text-sm font-medium text-gray-700 mb-1"
                                        >
                                            Serial/Batch Number
                                            <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            v-model="serialNumber"
                                            @keyup.enter="checkAndAddSerial"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Enter or scan serial/batch number"
                                            ref="serialInput"
                                            autofocus
                                        />
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <p>
                                            Product:
                                            {{
                                                selectedProduct
                                                    ?.supplier_product_detail
                                                    .product.name
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-end gap-3">
                                    <button
                                        @click="showSerialModal = false"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        @click="checkAndAddSerial"
                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md"
                                    >
                                        Add to Cart
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Serial List Modal -->
        <TransitionRoot appear :show="showSerialListModalFlag" as="template">
            <Dialog
                as="div"
                @close="showSerialListModalFlag = false"
                class="relative z-10"
            >
                <TransitionChild
                    enter="duration-300 ease-out"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="duration-200 ease-in"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-black bg-opacity-25" />
                </TransitionChild>

                <div class="fixed inset-0 overflow-y-auto">
                    <div
                        class="flex min-h-full items-center justify-center p-4"
                    >
                        <TransitionChild
                            enter="duration-300 ease-out"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="duration-200 ease-in"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <DialogPanel
                                class="w-full max-w-3xl transform overflow-hidden rounded-2xl bg-white p-6 shadow-xl transition-all"
                            >
                                <DialogTitle
                                    as="h3"
                                    class="text-lg font-medium leading-6 text-gray-900 mb-4"
                                >
                                    Serial/Batch Numbers for
                                    {{ selectedItem?.name }}
                                </DialogTitle>

                                <div class="space-y-2 max-h-96 overflow-y-auto">
                                    <div
                                        v-for="serial in selectedItem?.serials"
                                        :key="serial"
                                        class="flex justify-between items-center py-4 px-6 bg-gray-50 rounded hover:bg-gray-100"
                                    >
                                        <span class="font-medium text-base">{{
                                            serial
                                        }}</span>
                                        <button
                                            @click="
                                                removeSerial(
                                                    selectedItem,
                                                    serial
                                                )
                                            "
                                            class="text-red-500 hover:text-red-700"
                                        >
                                            <svg
                                                class="w-6 h-6"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M6 18L18 6M6 6l12 12"
                                                ></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-6 flex justify-end">
                                    <button
                                        @click="showSerialListModalFlag = false"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded-md"
                                    >
                                        Close
                                    </button>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </Dialog>
        </TransitionRoot>
    </AppLayout>
</template>

<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import CustomerSelection from "./Components/CustomerSelection.vue";
import CheckoutReview from "./Components/CheckoutReview.vue";
import Receipt from "./Components/Receipt.vue";
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from "vue";
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionRoot,
    TransitionChild,
} from "@headlessui/vue";
import debounce from "lodash/debounce";
import { formatNumber } from "@/utils/global";
import Dropzone from "@/Components/Form/Dropzone.vue";

// Step Management
const currentStep = ref(1);

// Customer State
const customerInfo = ref(null);
const selectedWarehouseId = ref(null);

// Product Search and Filter
const searchQuery = ref("");
const selectedCategory = ref("");
const products = ref([]);
const categories = ref([]);

// Cart State
const cartItems = ref([]);
const taxRate = ref(12);
const tempTaxRate = ref(12);
const tempTaxAmount = ref(0);
const discountType = ref("percentage");
const discountValue = ref(0);
const shippingAmount = ref(0);
const tempShippingAmount = ref(0);
const shippingMethod = ref("");

// Modals
const showCheckoutModal = ref(false);
const showTaxModal = ref(false);
const showDiscountModal = ref(false);
const showShippingModal = ref(false);

// Payment Method
const paymentMethod = ref("");
const paymentDetails = ref({
    account_number: "",
    account_name: "",
    bank_id: null,
    company_account_id: null,
    reference_number: null,
    receipt_attachment: null
});

// Bank search
const bankSearch = ref("");
const bankResults = ref([]);
const showBankResults = ref(false);
const selectedBank = ref(null);

// Company account search
const companyAccountSearch = ref("");
const companyAccountResults = ref([]);
const showCompanyAccountResults = ref(false);
const selectedCompanyAccount = ref(null);

// Serial handling
const showSerialModal = ref(false);
const showSerialListModalFlag = ref(false);
const serialNumber = ref("");
const selectedProduct = ref(null);
const selectedItem = ref(null);

// Add to the script section
const invoice = ref(null);

// Add this to your reactive state declarations
const reviewData = ref(null);

// Computed Properties
const subtotal = computed(() => {
    return cartItems.value.reduce(
        (total, item) => total + item.price * item.quantity,
        0
    );
});

const taxAmount = computed(() => {
    if (discountType.value === "senior" || discountType.value === "pwd") {
        return 0;
    }
    return (subtotal.value * taxRate.value) / 100;
});

const discountAmount = computed(() => {
    if (discountType.value === "percentage") {
        return (subtotal.value * discountValue.value) / 100;
    } else if (discountType.value === "fixed") {
        return discountValue.value;
    } else if (discountType.value === "senior" || discountType.value === "pwd") {
        // 20% discount for Senior/PWD
        return (subtotal.value * 20) / 100;
    }
    return 0;
});

const total = computed(() => {
    return (
        subtotal.value +
        taxAmount.value -
        discountAmount.value +
        shippingAmount.value
    );
});

const filteredProducts = computed(() => {
    return products.value;
});

// Methods
const handleCustomerSelection = (data) => {
    customerInfo.value = data.customer;
    selectedWarehouseId.value = data.warehouse_id;
    currentStep.value = 2;
    fetchProducts();
};

const fetchProducts = async () => {
    try {
        const response = await axios.get("/api/warehouse-products", {
            params: { warehouse_id: selectedWarehouseId.value },
        });
        products.value = response.data.data;
    } catch (error) {
        console.error("Error fetching products:", error);
    }
};

const handleSearch = debounce(async () => {
    if (!searchQuery.value.trim()) {
        fetchProducts();
        return;
    }

    try {
        const response = await axios.get("/api/search/warehouse-products", {
            params: {
                warehouse_id: selectedWarehouseId.value,
                search: searchQuery.value,
                category: selectedCategory.value,
            },
        });
        products.value = response.data.data;
    } catch (error) {
        console.error("Error searching products:", error);
    }
}, 300);

const addToCart = (product) => {
    if (product.has_serials) {
        selectedProduct.value = product;
        showSerialModal.value = true;
        return;
    }

    const existingItem = cartItems.value.find((item) => item.id === product.id);
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cartItems.value.push({
            id: product.id,
            name: product.supplier_product_detail.product.name,
            price: parseFloat(product.price),
            quantity: 1,
            serials: [],
            warehouse_id: selectedWarehouseId.value,
            avatar: product.supplier_product_detail.product.avatar
        });
    }
};

const updateQuantity = (item, change) => {
    if (item.serials && item.serials.length > 0) {
        return; // Don't allow quantity changes for serialized items
    }

    const newQuantity = item.quantity + change;
    if (newQuantity > 0) {
        item.quantity = newQuantity;
    } else {
        removeFromCart(item);
    }
};

const removeFromCart = (item) => {
    const index = cartItems.value.indexOf(item);
    if (index > -1) {
        cartItems.value.splice(index, 1);
    }
};

const clearCart = () => {
    cartItems.value = [];
};

const updateTaxAmount = () => {
    if (discountType.value === "senior" || discountType.value === "pwd") {
        tempTaxAmount.value = 0;
        tempTaxRate.value = 0;
        return;
    }
    tempTaxAmount.value = (subtotal.value * tempTaxRate.value) / 100;
};

const updateTaxRate = () => {
    if (discountType.value === "senior" || discountType.value === "pwd") {
        tempTaxRate.value = 0;
        tempTaxAmount.value = 0;
        return;
    }
    tempTaxRate.value = (tempTaxAmount.value / subtotal.value) * 100;
};

const saveTaxChanges = () => {
    if (discountType.value === "senior" || discountType.value === "pwd") {
        taxRate.value = 0;
        tempTaxRate.value = 0;
    } else {
        taxRate.value = tempTaxRate.value;
    }
    showTaxModal.value = false;
};

const saveDiscountChanges = () => {
    showDiscountModal.value = false;
};

const saveShippingChanges = () => {
    shippingAmount.value = tempShippingAmount.value;
    showShippingModal.value = false;
};

watch(showTaxModal, (newVal) => {
    if (newVal) {
        if (discountType.value === "senior" || discountType.value === "pwd") {
            tempTaxRate.value = 0;
            tempTaxAmount.value = 0;
        } else {
            tempTaxRate.value = taxRate.value;
            tempTaxAmount.value = taxAmount.value;
        }
    }
});

watch(showShippingModal, (newVal) => {
    if (newVal) {
        tempShippingAmount.value = shippingAmount.value;
    }
});

const startNewTransaction = () => {
    customerInfo.value = null;
    selectedWarehouseId.value = null;
    currentStep.value = 1;
    cartItems.value = [];
    taxRate.value = 12;
    discountType.value = "percentage";
    discountValue.value = 0;
    shippingAmount.value = 0;
};

// Debounced search functions
const searchBanks = debounce(async () => {
    if (!bankSearch.value) {
        bankResults.value = [];
        return;
    }

    try {
        const response = await axios.get("/api/autocomplete/banks", {
            params: { search: bankSearch.value },
        });
        bankResults.value = response.data.data || [];
    } catch (error) {
        console.error("Error searching banks:", error);
        bankResults.value = [];
    }
}, 300);

const searchCompanyAccounts = debounce(async () => {
    if (!companyAccountSearch.value) {
        companyAccountResults.value = [];
        return;
    }

    try {
        const response = await axios.get("/api/autocomplete/company-accounts", {
            params: { search: companyAccountSearch.value },
        });
        companyAccountResults.value = response.data.data || [];
    } catch (error) {
        console.error("Error searching company accounts:", error);
        companyAccountResults.value = [];
    }
}, 300);

const selectBank = (bank) => {
    selectedBank.value = bank;
    bankSearch.value = bank.name;
    paymentDetails.value.bank_id = bank.id;
    showBankResults.value = false;
};

const selectCompanyAccount = (account) => {
    selectedCompanyAccount.value = account;
    companyAccountSearch.value = account.name;
    paymentDetails.value.company_account_id = account.id;
    showCompanyAccountResults.value = false;
};

// Reset payment details when payment method changes
watch(paymentMethod, () => {
    paymentDetails.value = {
        account_number: "",
        account_name: "",
        bank_id: null,
        company_account_id: null,
        reference_number: null,
        receipt_attachment: null
    };
    bankSearch.value = "";
    companyAccountSearch.value = "";
    selectedBank.value = null;
    selectedCompanyAccount.value = null;
});

// Add click outside handlers to close dropdowns
onMounted(() => {
    document.addEventListener("click", (e) => {
        const bankSearchContainer = document.querySelector(
            "#bank-search-container"
        );
        const companyAccountSearchContainer = document.querySelector(
            "#company-account-search-container"
        );

        if (bankSearchContainer && !bankSearchContainer.contains(e.target)) {
            showBankResults.value = false;
        }

        if (
            companyAccountSearchContainer &&
            !companyAccountSearchContainer.contains(e.target)
        ) {
            showCompanyAccountResults.value = false;
        }
    });
});

onUnmounted(() => {
    document.removeEventListener("click", () => {});
});

const proceedToReview = () => {
    // Validate required fields based on payment method
    if (!paymentMethod.value) {
        alert("Please select a payment method");
        return;
    }

    // Generate reference number for cash payments if not provided
    let referenceNumber = null;
    if (paymentMethod.value === 'cash') {
        referenceNumber = `SI-CASH-${Date.now()}`;
    } else if (paymentMethod.value === 'gcash') {
        if (!paymentDetails.value.account_number) {
            alert("Please enter the GCash mobile number");
            return;
        }
        referenceNumber = paymentDetails.value.reference_number;
        if (!referenceNumber) {
            alert("Please enter the GCash reference number");
            return;
        }
    }

    // Prepare payment details
    const paymentMethodDetails = {
        payment_method: paymentMethod.value,
        payment_details: {
            account_number: paymentDetails.value.account_number || null,
            account_name: paymentDetails.value.account_name || null,
            bank_id: selectedBank.value?.id || null,
            company_account_id: selectedCompanyAccount.value?.id || null,
            reference_number: referenceNumber,
            status: 'approved',
            payment_date: new Date().toISOString().split('T')[0],
            amount: total.value
        }
    };

    // Prepare invoice data
    const invoiceData = {
        company_id: customerInfo.value?.company_id,
        customer_id: customerInfo.value?.id,
        warehouse_id: selectedWarehouseId.value,
        type: 'pos-invoice',
        invoice_date: new Date().toISOString().split('T')[0],
        discount_rate: discountType.value === 'percentage' ? discountValue.value : 0,
        discount_amount: discountAmount.value,
        tax_rate: taxRate.value,
        tax_amount: taxAmount.value,
        shipping_cost: shippingAmount.value,
        subtotal: subtotal.value,
        total_amount: total.value,
        currency: 'PHP',
        status: 'fully-paid',
        shipping_method: shippingMethod.value,
        payment_method: paymentMethod.value,
        payment_details: paymentMethodDetails.payment_details,
        items: cartItems.value.map(item => ({
            warehouse_product_id: item.id,
            qty: item.quantity,
            price: item.price,
            total: item.price * item.quantity,
            serials: item.serials || []
        }))
    };

    // If there's a receipt attachment, prepare FormData
    if (paymentDetails.value.receipt_attachment) {
        const formData = new FormData();
        
        // Add all invoice data
        Object.entries(invoiceData).forEach(([key, value]) => {
            if (typeof value === 'object') {
                formData.append(key, JSON.stringify(value));
            } else {
                formData.append(key, value);
            }
        });

        // Add the file
        formData.append('receipt_attachment', paymentDetails.value.receipt_attachment);
        
        // Store both the FormData and the original file for the review component
        reviewData.value = {
            formData,
            originalFile: paymentDetails.value.receipt_attachment,
            invoiceData
        };
    } else {
        reviewData.value = invoiceData;
    }

    showCheckoutModal.value = false;
    currentStep.value = 3;
};

const checkAndAddSerial = async () => {
    if (!serialNumber.value) {
        alert("Please enter a serial/batch number");
        return;
    }

    try {
        const response = await axios.get(
            "/api/serial-check/warehouse-products",
            {
                params: {
                    warehouse_id: selectedWarehouseId.value,
                    product_id: selectedProduct.value.id,
                    serial_number: serialNumber.value,
                },
            }
        );

        if (!response.data.data) {
            alert(response.data.message);
            return;
        }

        const existingItem = cartItems.value.find(
            (item) => item.id === selectedProduct.value.id
        );
        if (existingItem) {
            if (existingItem.serials.includes(serialNumber.value)) {
                alert("This serial/batch number is already in your cart");
                return;
            }
            existingItem.quantity += 1;
            existingItem.serials.push(serialNumber.value);
        } else {
            cartItems.value.push({
                id: selectedProduct.value.id,
                name: selectedProduct.value.supplier_product_detail.product.name,
                price: parseFloat(selectedProduct.value.price),
                quantity: 1,
                serials: [serialNumber.value],
                warehouse_id: selectedWarehouseId.value,
                avatar: selectedProduct.value.supplier_product_detail.product.avatar
            });
        }

        serialNumber.value = "";
        // Keep the modal open for multiple serial entries
        if (selectedProduct.value) {
            nextTick(() => {
                if (serialInput.value) {
                    serialInput.value.focus();
                }
            });
        }
    } catch (error) {
        console.error("Error checking serial:", error);
        alert("Error checking serial/batch number");
    }
};

const showSerialListModal = (item) => {
    selectedItem.value = item;
    showSerialListModalFlag.value = true;
};

const removeSerial = (item, serial) => {
    const index = item.serials.indexOf(serial);
    if (index > -1) {
        item.serials.splice(index, 1);
        item.quantity -= 1;

        if (item.quantity === 0) {
            removeFromCart(item);
        }
    }
};

// Add ref for serial input
const serialInput = ref(null);

const handleProceedToReceipt = (data) => {
    invoice.value = data;
    currentStep.value = 4;
};

// Update the watch for discountType
watch(discountType, (newType) => {
    if (newType === "senior" || newType === "pwd") {
        // Reset discount value when switching to special discount types
        discountValue.value = 0;
        // Set tax rate to 0 for Senior/PWD
        taxRate.value = 0;
        tempTaxRate.value = 0;
    } else {
        // Reset to default tax rate when switching back to regular discounts
        taxRate.value = 12;
        tempTaxRate.value = 12;
    }
});
</script>

<style scoped>
/* Custom scrollbar for Webkit browsers */
::-webkit-scrollbar {
    width: 6px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>
