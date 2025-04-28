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

const page = usePage();
const modelName = "users";
const modelData = computed(() => page.props.modelData || {});

const formatModelName = (name) => {
    if (name.endsWith("ies")) {
        return name.slice(0, -3).charAt(0).toUpperCase() + name.slice(1, -3) + "y";
    }
    return name.slice(0, -1).charAt(0).toUpperCase() + name.slice(1, -1);
};
const formattedTitle = formatModelName(modelName);

const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const headerActions = ref([
    {
        text: "Go Back",
        url: `/${modelName}`,
        inertia: true,
        class: "border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded text-gray-600",
    },
    {
        text: "Edit",
        url: `/${modelName}/${modelData.value.id}/edit`,
        inertia: true,
        class: "px-4 py-2 rounded",
        style: computed(() => ({
            backgroundColor: buttonPrimaryBgColor.value,
            color: buttonPrimaryTextColor.value
        })),
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

const contactDetails = ref([
    { label: "Located At", value: "address" },
    { label: "Mobile", value: "mobile" },
    { label: "Landline", value: "landline" },
]);
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
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg pt-6">
                <HeaderInformation
                    :title="`${formattedTitle} Details`"
                    :modelData="modelData"
                />
                <ProfileCard :modelData="modelData" :columns="profileDetails" />

                <div class="border-t border-gray-200" />
                <DisplayInformation
                    title="Contact Information"
                    :modelData="modelData"
                    :rowDetails="contactDetails"
                />
            </div>
        </div>
    </AppLayout>
</template>
