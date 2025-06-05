<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed } from "vue";
import { usePage, Link } from "@inertiajs/vue3";
import moment from "moment";
import HeaderInformation from "@/Components/Sections/HeaderInformation.vue";
import ProfileCard from "@/Components/Sections/ProfileCard.vue";
import DisplayInformation from "@/Components/Sections/DisplayInformation.vue";
import {
    singularizeAndFormat,
    humanReadable,
    formatDate,
    getStatusPillClass,
} from "@/utils/global";
import { useColors } from "@/Composables/useColors";
import axios from "axios";
import { useToast } from "vue-toastification";

const page = usePage();
const modelName = "employee-leaves";
const modelData = computed(() => page.props.modelData || {});
const toast = useToast();

const getQrUrl = (id) => {
    return route("qr.employees", {
        employee: usePage().props.modelData.employee_id,
    });
};

const { buttonPrimaryBgColor, buttonPrimaryTextColor } = useColors();

const profileDetails = [
    {
        label: "Name",
        value: (row) => row.employee?.full_name,
        class: "text-xl font-bold",
    },
    {
        label: "Number",
        value: (row) => row.employee?.number,
        class: "text-gray-600 font-semibold",
    },
    {
        has_qr: true,
        qr_data: (row) => getQrUrl(row.id),
        created_at: (row) => moment(row.created_at).fromNow(),
    },
];

const employeeLeaveDetails = ref([
    {
        label: "Status",
        value: (row) => humanReadable(row.status),
        render: (row) => ({
            text: humanReadable(row.status),
            class: getStatusPillClass(row.status),
        }),
    },
    { label: "Leave Type", value: (row) => humanReadable(row.leave_type) },
    {
        label: "Start Date",
        value: (row) => formatDate("M d Y", row.start_date),
    },
    { label: "End Date", value: (row) => formatDate("M d Y", row.end_date) },
    { label: "Reason", value: (row) => row.reason || "-" },
    { label: "Remarks", value: (row) => row.remarks || "-" },
]);

// Modal state for approval/rejection
const showActionModal = ref(false);
const actionType = ref(""); // 'approve' or 'reject'
const remarks = ref("");
const isSubmitting = ref(false);

function openActionModal(type) {
    actionType.value = type;
    remarks.value = "";
    showActionModal.value = true;
}

async function submitAction() {
    if (!actionType.value) return;
    isSubmitting.value = true;
    try {
        const url = `/api/employee-leaves/${modelData.value.id}/${actionType.value}`;
        await axios.post(url, { remarks: remarks.value });
        showActionModal.value = false;
        toast.success("Leave " + actionType.value + "d successfully");
        window.location.reload();
    } catch (e) {
        alert("Failed to " + actionType.value + " leave.");
    } finally {
        isSubmitting.value = false;
    }
}
</script>

<template>
    <AppLayout :title="`${singularizeAndFormat(modelName)} Details`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ singularizeAndFormat(modelName) }} Details
                </h2>
                <div class="flex gap-2">
                    <Link
                        :href="`/${modelName}`"
                        class="border border-gray-400 hover:bg-gray-100 px-4 py-2 rounded text-gray-600"
                    >
                        Go Back
                    </Link>
                </div>
            </div>
        </template>

        <div class="max-w-4xl mx-auto">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg pt-6">
                <HeaderInformation
                    :title="`${singularizeAndFormat(modelName)} Details`"
                    :modelData="modelData"
                />
                <ProfileCard :modelData="modelData" :columns="profileDetails" />

                <div class="border-t border-gray-200" />
                <DisplayInformation
                    title="Employee Leave Information"
                    :modelData="modelData"
                    :rowDetails="employeeLeaveDetails"
                />
                <!-- Approve/Reject Buttons (left-aligned, directly below data) -->
                <div
                    class="flex gap-2 justify-start px-9 pb-9"
                    v-if="modelData.status === 'pending'"
                >
                    <button
                        @click="openActionModal('approve')"
                        class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                    >
                        Approve
                    </button>
                    <button
                        @click="openActionModal('reject')"
                        class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
                    >
                        Reject
                    </button>
                </div>
            </div>
        </div>

        <!-- Custom Action Modal -->
        <div
            v-if="showActionModal"
            class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50"
        >
            <div class="bg-white p-6 rounded shadow-lg w-full max-w-md">
                <h2 class="text-lg font-bold mb-4 text-center">
                    {{
                        actionType === "approve"
                            ? "Approve Leave"
                            : "Reject Leave"
                    }}
                </h2>
                <label class="block mb-2 font-medium"
                    >Remarks (optional):</label
                >
                <textarea
                    v-model="remarks"
                    rows="3"
                    class="w-full border rounded p-2 mb-4"
                    placeholder="Enter remarks (optional)"
                ></textarea>
                <div class="flex justify-end mt-4">
                    <button
                        @click="showActionModal = false"
                        :disabled="isSubmitting"
                        class="mr-2 px-4 py-2 bg-gray-200 rounded"
                    >
                        Cancel
                    </button>
                    <button
                        @click="submitAction"
                        :disabled="isSubmitting"
                        :class="[
                            actionType === 'approve'
                                ? 'bg-green-600 hover:bg-green-700'
                                : 'bg-red-600 hover:bg-red-700',
                            'px-4 py-2 text-white rounded',
                        ]"
                    >
                        <span v-if="isSubmitting">Processing...</span>
                        <span v-else>{{
                            actionType === "approve" ? "Approve" : "Reject"
                        }}</span>
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
