<template>
    <div>
        <!-- Mobile Overlay -->
        <div
            v-if="isMobileOpen"
            class="fixed inset-0 z-30 bg-black bg-opacity-50 lg:hidden"
            @click="toggleMobile"
        ></div>

        <!-- Mobile Sidebar Overlay -->
        <div
            v-show="isSidebarOpen"
            class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden"
            @click="toggleSidebar"
        ></div>

        <!-- Sidebar -->
        <div
            :class="[
                'fixed top-0 left-0 z-40 h-screen transition-all duration-300 ease-in-out bg-white border-r border-gray-200 flex flex-col',
                'lg:translate-x-0',
                isMobileOpen ? 'translate-x-0' : '-translate-x-full',
                isMinimized ? 'w-16' : 'w-64',
            ]"
        >
            <!-- Sidebar Header -->
            <div class="flex items-center h-16 px-4 border-b border-gray-200">
                <Link
                    :href="route('dashboard')"
                    class="flex items-center space-x-3"
                >
                    <div class="debug-image-container w-32 h-32">
                        <img
                            :src="appIcon"
                            class="w-full h-full object-contain"
                            @error="handleImageError"
                            alt="App Icon"
                        />
                    </div>
                    <!-- <span v-show="!isMinimized" class="text-lg font-semibold">{{
                        appName
                    }}</span> -->
                </Link>
            </div>

            <!-- Profile Section -->
            <div class="px-4 py-4 border-b border-gray-200">
                <div class="flex items-center space-x-3" v-show="!isMinimized">
                    <div class="flex-shrink-0">
                        <div
                            v-if="auth.user?.profile_photo_url"
                            class="size-10 rounded-full bg-cover bg-center"
                            :style="{
                                backgroundImage: `url('${auth.user.profile_photo_url}')`,
                            }"
                        ></div>
                        <div
                            v-else
                            class="size-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-semibold"
                        >
                            {{ userInitials }}
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 truncate">
                            {{ auth.user?.name }}
                        </p>
                        <p class="text-xs text-gray-500 truncate">
                            {{ auth.user?.current_role?.name || "User" }}
                        </p>
                    </div>
                </div>
                <div v-show="isMinimized" class="flex justify-center">
                    <div
                        v-if="auth.user?.profile_photo_url"
                        class="size-10 rounded-full bg-cover bg-center"
                        :style="{
                            backgroundImage: `url('${auth.user.profile_photo_url}')`,
                        }"
                    ></div>
                    <div
                        v-else
                        class="size-10 rounded-full bg-primary-100 flex items-center justify-center text-primary-700 font-semibold"
                    >
                        {{ userInitials }}
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto">
                <!-- Navigation Links -->
                <nav class="flex-1 px-2 py-4 space-y-1">
                    <Link
                        :href="route('dashboard')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current('dashboard')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current('dashboard')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-view-dashboard-outline text-xl"
                            :style="
                                route().current('dashboard')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Dashboard</span
                        >
                    </Link>

                    <Link
                        :href="route('pos')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current('pos')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current('pos')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-network-pos text-xl"
                            :style="
                                route().current('pos')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3">POS</span>
                    </Link>

                    <div
                        v-if="
                            hasPermission('read banks') ||
                            hasPermission('read company accounts') ||
                            hasPermission('read invoices') ||
                            hasPermission('read supplier invoices') || 
                            hasPermission('read expenses') ||
                            hasPermission('read journal entries')
                        "
                        v-show="!isMinimized"
                        class="px-4 mt-4 mb-2 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider"
                    >
                        Accounting
                    </div>

                    <!-- <Link
                        :href="route('chart-of-accounts.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('chart-of-accounts.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('chart-of-accounts.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-bank-outline text-xl"
                            :style="
                                route()
                                    .current()
                                    ?.startsWith('chart-of-accounts.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3">Chart of Accounts</span>
                    </Link> -->

                    <Link
                        v-if="hasPermission('read invoices')"
                        :href="route('invoices.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('invoices.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('invoices.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-cash-check text-xl"
                            :style="
                                route().current()?.startsWith('invoices.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3">Invoices</span>
                    </Link>

                    <Link
                        v-if="hasPermission('read shipments')"
                        :href="route('shipments.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('shipments.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('shipments.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-truck-fast-outline text-xl"
                            :style="
                                route().current()?.startsWith('shipments.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3">Shipments</span>
                    </Link>

                    <Link
                        :href="route('supplier-invoices.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('supplier-invoices.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('supplier-invoices.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-account-credit-card-outline text-xl"
                            :style="
                                route()
                                    .current()
                                    ?.startsWith('supplier-invoices.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Supplier Invoices</span
                        >
                    </Link>

                    <Link
                        v-if="hasPermission('read expenses')"
                        :href="route('expenses.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('expenses.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('expenses.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-cash-multiple text-xl"
                            :style="
                                route().current()?.startsWith('expenses.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3">Expenses</span>
                    </Link>

                    <Link
                        v-if="hasPermission('read journal entries')"
                        :href="route('journal-entries.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('journal-entries.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('journal-entries.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-file-document-outline text-xl"
                            :style="
                                route()
                                    .current()
                                    ?.startsWith('journal-entries.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Journal Entries</span
                        >
                    </Link>

                    <Link
                        v-if="hasPermission('read banks')"
                        :href="route('banks.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('banks.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('banks.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-bank text-xl"
                            :style="
                                route().current()?.startsWith('banks.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3">Banks</span>
                    </Link>

                    <Link
                        v-if="hasPermission('read company accounts')"
                        :href="route('company-accounts.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('company-accounts.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('company-accounts.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-account-group text-xl"
                            :style="
                                route()
                                    .current()
                                    ?.startsWith('company-accounts.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Company Accounts</span
                        >
                    </Link>

                    <div
                        v-if="
                            hasPermission('read companies') ||
                            hasPermission('read purchase orders') ||
                            hasPermission('read goods receipts') ||
                            hasPermission('read warehouses') || 
                            hasPermission('read suppliers') || 
                            hasPermission('read products') ||
                            hasPermission('read shipments')
                        "
                        v-show="!isMinimized"
                        class="px-4 mt-4 mb-2 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider"
                    >
                        Warehouse
                    </div>

                    <Link
                        v-if="hasPermission('read purchase orders')"
                        :href="route('purchase-orders.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('purchase-orders.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('purchase-orders.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-check-decagram-outline text-xl"
                            :style="
                                route()
                                    .current()
                                    ?.startsWith('purchase-orders.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Purchase Orders</span
                        >
                    </Link>

                    <!-- <Link
                        :href="route('purchase-requisitions.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route()
                                .current()
                                ?.startsWith('purchase-requisitions.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route()
                                .current()
                                ?.startsWith('purchase-requisitions.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-file-document-outline text-xl"
                            :style="
                                route()
                                    .current()
                                    ?.startsWith('purchase-requisitions.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Purchase Requisitions</span
                        >
                    </Link> -->

                    <Link
                        v-if="hasPermission('read goods receipts')"
                        :href="route('goods-receipts.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('goods-receipts.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('goods-receipts.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-truck-fast-outline text-xl"
                            :style="
                                route().current()?.startsWith('goods-receipts.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Goods Receipts</span
                        >
                    </Link>

                    <Link
                        v-if="hasPermission('read warehouse stock transfers')"
                        :href="route('warehouse-stock-transfers.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('warehouse-stock-transfers.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('warehouse-stock-transfers.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-transfer text-xl"
                            :style="
                                route().current()?.startsWith('warehouse-stock-transfers.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Stock Transfers</span
                        >
                    </Link>

                    <Link
                        v-if="hasPermission('read warehouses')"
                        :href="route('warehouses.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('warehouses.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('warehouses.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-warehouse text-xl"
                            :style="
                                route().current()?.startsWith('warehouses.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Warehouses</span
                        >
                    </Link>

                    <Link
                        v-if="hasPermission('read couriers')"
                        :href="route('couriers.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('couriers.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('couriers.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-truck-outline text-xl"
                            :style="
                                route().current()?.startsWith('couriers.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Couriers</span
                        >
                    </Link>

                    <Link
                        v-if="hasPermission('read suppliers')"
                        :href="route('suppliers.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('suppliers.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('suppliers.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-account-card-outline text-xl"
                            :style="
                                route().current()?.startsWith('suppliers.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Suppliers</span
                        >
                    </Link>

                    <Link
                        v-if="hasPermission('read products')"
                        :href="route('products.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('products.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('products.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-package-variant-closed text-xl"
                            :style="
                                route().current()?.startsWith('products.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3">Products</span>
                    </Link>

                    <div
                        v-if="
                            hasPermission('read customers') ||
                            hasPermission('read agents')
                        "
                        v-show="!isMinimized"
                        class="px-4 mt-4 mb-2 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider"
                    >
                        CRM
                    </div>

                    <Link
                        v-if="hasPermission('read customers')"
                        :href="route('customers.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('customers.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('customers.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-account-star-outline text-xl"
                            :style="
                                route().current()?.startsWith('customers.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Customers</span
                        >
                    </Link>

                    <Link
                        v-if="hasPermission('read advertisements')"
                        :href="route('advertisements.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('advertisements.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('advertisements.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-bullhorn-outline text-xl"
                            :style="
                                route().current()?.startsWith('advertisements.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Advertisements</span
                        >
                    </Link>

                    <Link
                        v-if="hasPermission('read agents')"
                        :href="route('agents.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('agents.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('agents.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-account-group-outline text-xl"
                            :style="
                                route().current()?.startsWith('agents.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3">Agents</span>
                    </Link>

                    <div
                        v-if="
                            hasPermission('read projects') ||
                            hasPermission('read project tasks') ||
                            hasPermission('read project members')
                        "
                        v-show="!isMinimized"
                        class="px-4 mt-4 mb-2 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider"
                    >
                        Project Management
                    </div>

                    <Link
                        v-if="hasPermission('read projects')"
                        :href="route('projects.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('projects.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('projects.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-bulletin-board text-xl"
                            :style="
                                route().current()?.startsWith('projects.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3">Projects</span>
                    </Link>

                    <Link
                        v-if="hasPermission('read project tasks')"
                        :href="route('tasks.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('tasks.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('tasks.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-clipboard-list-outline text-xl"
                            :style="
                                route().current()?.startsWith('tasks.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3">Tasks</span>
                    </Link>

                    <div
                        v-if="
                            hasPermission('read employees') ||
                            hasPermission('read departments')
                        "
                        v-show="!isMinimized"
                        class="px-4 mt-4 mb-2 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider"
                    >
                        HR
                    </div>

                    <Link
                        v-if="hasPermission('read employees')"
                        :href="route('employees.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('employees.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('employees.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-account-group-outline text-xl"
                            :style="
                                route().current()?.startsWith('employees.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Employees</span
                        >
                    </Link>

                    <Link
                        v-if="hasPermission('read employee leaves')"
                        :href="route('employee-leaves.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('employee-leaves.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('employee-leaves.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-calendar-check-outline text-xl"
                            :style="
                                route()
                                    .current()
                                    ?.startsWith('employee-leaves.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3">Leaves</span>
                    </Link>

                    <Link
                        v-if="hasPermission('read employee overtimes')"
                        :href="route('employee-overtimes.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('employee-overtimes.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('employee-overtimes.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-clock-outline text-xl"
                            :style="
                                route()
                                    .current()
                                    ?.startsWith('employee-overtimes.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Overtimes</span
                        >
                    </Link>

                    <Link
                        v-if="hasPermission('read departments')"
                        :href="route('departments.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('departments.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('departments.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-office-building-outline text-xl"
                            :style="
                                route().current()?.startsWith('departments.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Departments</span
                        >
                    </Link>

                    <Link
                        v-if="hasPermission('read holidays')"
                        :href="route('holidays.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('holidays.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('holidays.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-calendar-outline text-xl"
                            :style="
                                route().current()?.startsWith('holidays.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3">Holidays</span>
                    </Link>

                    <Link
                        v-if="hasPermission('read offense types')"
                        :href="route('offense-types.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('offense-types.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('offense-types.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-alert-circle-outline text-xl"
                            :style="
                                route().current()?.startsWith('offense-types.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Offense Types</span
                        >
                    </Link>

                    <Link
                        v-if="hasPermission('read deductions')"
                        :href="route('deductions.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('deductions.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('deductions.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-cash-multiple text-xl"
                            :style="
                                route().current()?.startsWith('deductions.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Deductions</span
                        >
                    </Link>

                    <Link
                        v-if="hasPermission('read document types')"
                        :href="route('document-types.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('document-types.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('document-types.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-file-document-outline text-xl"
                            :style="
                                route().current()?.startsWith('document-types.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Document Types</span
                        >
                    </Link>

                    <div
                        v-if="
                            hasPermission('read users') ||
                            hasPermission('read companies')
                        "
                        v-show="!isMinimized"
                        class="px-4 mt-4 mb-2 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider"
                    >
                        Data
                    </div>

                    <Link
                        v-if="hasPermission('read users')"
                        :href="route('users.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('users.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('users.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-account-group-outline text-xl"
                            :style="
                                route().current()?.startsWith('users.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3">Users</span>
                    </Link>

                    <Link
                        v-if="hasPermission('read companies')"
                        :href="route('companies.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('companies.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('companies.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-domain text-xl"
                            :style="
                                route().current()?.startsWith('companies.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Companies</span
                        >
                    </Link>

                    <div
                        v-if="
                            hasPermission('read roles') ||
                            hasPermission('read activity logs') ||
                            hasPermission('read categories') ||
                            roles.includes('super-admin') ||
                            $page.props.jetstream.hasApiFeatures
                        "
                        v-show="!isMinimized"
                        class="px-4 mt-4 mb-2 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider"
                    >
                        Settings
                    </div>

                    <Link
                        v-if="hasPermission('read roles')"
                        :href="route('roles.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('roles.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('roles.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-shield-account-outline text-xl"
                            :style="
                                route().current()?.startsWith('roles.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Roles & Permissions</span
                        >
                    </Link>

                    <Link
                        v-if="hasPermission('read activity logs')"
                        :href="route('activity-logs.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('activity-logs.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('activity-logs.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-history text-xl"
                            :style="
                                route().current()?.startsWith('activity-logs.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Activity Logs</span
                        >
                    </Link>

                    <Link
                        v-if="$page.props.jetstream.hasApiFeatures"
                        :href="route('api-tokens.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('api-tokens.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('api-tokens.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-api text-xl"
                            :style="
                                route().current()?.startsWith('api-tokens.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >API Tokens</span
                        >
                    </Link>

                    <Link
                        v-if="hasPermission('read categories')"
                        :href="route('categories.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('categories.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('categories.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-shape-outline text-xl"
                            :style="
                                route().current()?.startsWith('categories.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >Categories</span
                        >
                    </Link>

                    <Link
                        v-if="roles.includes('super-admin')"
                        :href="route('app.settings.index')"
                        :class="[
                            'flex items-center px-4 py-2 rounded-lg transition-colors',
                            route().current()?.startsWith('app.settings.')
                                ? 'active-link'
                                : 'hover:bg-gray-100',
                        ]"
                        :style="
                            route().current()?.startsWith('app.settings.')
                                ? activeStyles
                                : sidebarTextStyle
                        "
                    >
                        <span
                            class="mdi mdi-cog-outline text-xl"
                            :style="
                                route().current()?.startsWith('app.settings.')
                                    ? { color: activeTextColor }
                                    : sidebarTextStyle
                            "
                        ></span>
                        <span v-show="!isMinimized" class="ml-3"
                            >App Settings</span
                        >
                    </Link>
                </nav>
            </div>

            <!-- Powered by text -->
            <div class="p-4 border-t border-gray-200">
                <div
                    v-if="!isMinimized"
                    class="text-center text-sm text-gray-500"
                >
                    Powered By {{ appName }}
                </div>
                <div v-else class="text-center">
                    <span class="mdi mdi-copyright text-gray-500"></span>
                </div>
            </div>
        </div>

        <!-- Mobile Toggle Button -->
        <!-- <button
            class="fixed bottom-4 left-4 z-50 p-3 bg-white rounded-full shadow-lg border border-gray-200 lg:hidden hover:bg-gray-50"
            @click="toggleSidebar"
        >
            <span 
                class="mdi"
                :class="isSidebarOpen ? 'mdi-close' : 'mdi-menu'"
            ></span>
        </button> -->
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import ApplicationMark from "@/Components/ApplicationMark.vue";
import { useColors } from "@/Composables/useColors";

const props = defineProps({
    isMobileOpen: {
        type: Boolean,
        default: false,
    },
    appIcon: {
        type: String,
        required: true,
    },
});

const isSidebarOpen = ref(false);
const isMinimized = ref(false);
const isMobileOpen = computed(() => props.isMobileOpen);

const { roles, permissions, appSettings, auth } = usePage().props;
const { sidebarActiveBgColor, sidebarActiveTextColor, sidebarTextColor } =
    useColors();

const emit = defineEmits(["update:sidebar", "update:mobile"]);

const hasPermission = (permission) => {
    return permissions.includes(permission);
};

const appName = computed(() => {
    return appSettings?.name ? stripQuotes(appSettings.name) : "The EO";
});

// Add computed styles for active state
const activeStyles = computed(() => ({
    backgroundColor: sidebarActiveBgColor.value,
    color: sidebarActiveTextColor.value,
}));

const sidebarTextStyle = computed(() => ({
    color: sidebarTextColor.value,
}));

const activeTextColor = computed(() => {
    return sidebarActiveTextColor.value;
});

// Add computed for user initials
const userInitials = computed(() => {
    const name = auth.user?.name || "";
    return name
        .split(" ")
        .map((word) => word[0])
        .join("")
        .toUpperCase();
});

function stripQuotes(value) {
    return value && value.startsWith('"') && value.endsWith('"')
        ? value.slice(1, -1)
        : value;
}

// Update to only handle minimize state
const emitSidebarState = () => {
    emit("update:sidebar", {
        isMinimized: isMinimized.value,
        isOpen: true, // Always true since we're removing the hide functionality
    });
};

const toggleMinimize = () => {
    isMinimized.value = !isMinimized.value;
    emitSidebarState();
};

// Add mobile toggle function
const toggleMobile = () => {
    emit("update:mobile", !isMobileOpen.value);
};

// Emit initial state
onMounted(() => {
    emitSidebarState();
});

const handleImageError = (e) => {
    console.error("Image failed to load:", e.target.src);
    e.target.src = "/app-settings/app-icon.png"; // Fallback image
};
</script>
<style scoped>
/* Add any additional styling here */
</style>
