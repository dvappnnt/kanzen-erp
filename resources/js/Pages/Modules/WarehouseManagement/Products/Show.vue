<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import moment from "moment";
import HeaderInformation from "@/Components/Sections/HeaderInformation.vue";
import ProfileCard from "@/Components/Sections/ProfileCard.vue";
import DisplayInformation from "@/Components/Sections/DisplayInformation.vue";
import { singularizeAndFormat } from "@/utils/global";
import { useColors } from "@/Composables/useColors";
import NavigationTabs from "@/Components/Navigation/NavigationTabs.vue";

const modelName = "products";

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

const getQrUrl = (id) => {
    return route("qr.products", { product: usePage().props.modelData.id });
};

const profileDetails = [
    { label: "Name", value: "name", class: "text-xl font-bold" },
    {
        label: "Category",
        value: (row) => row.category.name,
        class: "text-gray-600 font-semibold",
    },
    {
        has_qr: true,
        qr_data: (row) => getQrUrl(row.token),
        created_at: (row) => moment(row.created_at).fromNow(),
    },
];

const productDetails = ref([
    { label: "Slug", value: "slug" },
    { label: "Product Name", value: "name" },
    { label: "Category", value: (row) => row.category?.name },
    { label: "Description", value: "description" },
]);

const navigationTabs = ref([
    {
        text: "Overview",
        url: `/${modelName}/${usePage().props.modelData.id}`,
        inertia: true,
        permission: "read suppliers",
    },
    {
        text: "Images Information",
        url: `/${modelName}/${usePage().props.modelData.id}/images`,
        inertia: true,
        permission: "read product images",
    },
    {
        text: "Specifications Information",
        url: `/${modelName}/${usePage().props.modelData.id}/specifications`,
        inertia: true,
        permission: "read product specifications",
    },
    {
        text: "Variations Information",
        url: `/${modelName}/${usePage().props.modelData.id}/variations`,
        inertia: true,
        permission: "read product variations",
    },
]);

const page = usePage();
const modelData = computed(() => page.props.modelData || {});
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

                <div class="border-t border-gray-200" />
                <DisplayInformation
                    title="Contact Information"
                    :modelData="modelData"
                    :rowDetails="productDetails"
                />
            </div>
        </div>
    </AppLayout>
</template>
