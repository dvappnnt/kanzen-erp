<script setup>
import { defineProps, defineEmits, ref, watch, computed } from "vue";
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import DialogModal from "@/Components/DialogModal.vue";
import axios from "@/axios";
import { useToast } from "vue-toastification";

// Define emit for parent component communication
const emit = defineEmits(["dateRangeChanged"]);

// Props to receive data from the parent component
const props = defineProps({
    modelData: {
        type: Array,
        required: true,
    },
    primaryColor: {
        type: String,
        default: "#3B82F6",
    },
});

// Modal state
const showModal = ref(false);
const selectedBooking = ref(null);
const toast = useToast();

// Function to open the modal and set selected booking
const openModal = (event) => {
    selectedBooking.value = {
        ...event.event.extendedProps,
        id: event.event.extendedProps.id,
        title: event.event.title,
        description: event.event.extendedProps.description || "No description",
        company: event.event.extendedProps.company || "N/A",
        status: event.event.extendedProps.status,
        participants: event.event.extendedProps.participants || [],
    };
    showModal.value = true;
};

// Calendar options ref
const calendarOptions = ref({
    plugins: [dayGridPlugin],
    initialView: "dayGridMonth",
    events: [],
    eventClick: openModal,
    datesSet: (info) => {
        emit("dateRangeChanged", {
            start: info.startStr,
            end: info.endStr,
        });
    },
});

// Watch for changes in modelData and update calendar events
watch(
    () => props.modelData,
    (newData) => {
        calendarOptions.value.events =
            newData.length > 0
                ? newData.map((booking) => ({
                      id: booking.id,
                      title: booking.title,
                      start: booking.start_time,
                      end: booking.end_time,
                      backgroundColor: getStatusColor(booking.status), // Set color dynamically
                      borderColor: getStatusColor(booking.status), // Border color
                      extendedProps: {
                          bookable: booking.bookable || null,
                          interviewer: booking.interviewer || null,
                          id: booking.id,
                          description: booking.description || "No description",
                          company: booking.company?.name || "N/A",
                          status: booking.status,
                          participants: booking.participants || [],
                      },
                  }))
                : []; // Provide an empty array if no bookings
    },
    { immediate: true }
);

// Function to update booking status
const updateBookingStatus = async (status) => {
    try {
        await axios.put(`/api/bookings/${selectedBooking.value.id}/status`, { status });
        
        toast.success(`Booking status updated to ${status}.`);
        selectedBooking.value.status = status; // Update UI state
        showModal.value = false; // Close modal after update
        emit("dateRangeChanged", {
            start: calendarOptions.value.datesSet.start,
            end: calendarOptions.value.datesSet.end,
        });
    } catch (error) {
        toast.error("Failed to update booking status.");
        console.error("Error updating booking status:", error.response?.data || error.message);
    }
};

// Function to take over as interviewer
const takeOverInterviewer = async () => {
    try {
        await axios.put(`/api/bookings/${selectedBooking.value.id}/takeover`);

        toast.success("You have taken over as the interviewer.");
        selectedBooking.value.interviewer.name = "You"; // Update UI
        showModal.value = false; // Close modal after update
        emit("dateRangeChanged", {
            start: calendarOptions.value.datesSet.start,
            end: calendarOptions.value.datesSet.end,
        });
    } catch (error) {
        toast.error("Failed to take over as interviewer.");
        console.error("Error taking over:", error.response?.data || error.message);
    }
};

// Function to map status to appropriate colors
const getStatusClass = (status) => {
    switch (status) {
        case "pending":
            return "text-orange-500 font-semibold";
        case "confirmed":
            return "text-green-500 font-semibold";
        case "canceled":
            return "text-red-500 font-semibold";
        case "done":
            return "text-green-700 font-semibold";
        default:
            return "text-gray-500";
    }
};

// Function to get color based on booking status
const getStatusColor = (status) => {
    switch (status) {
        case "pending":
            return "#FFA500"; // Orange for pending
        case "confirmed":
            return "#28A745"; // Green for confirmed
        case "canceled":
            return "#DC3545"; // Red for canceled
        case "done":
            return "#4CAF50"; // Dark green for done
        default:
            return "#6C757D"; // Gray for unknown status
    }
};

// Function to parse participants (assume JSON string format in API)
const parsedParticipants = computed(() => {
    try {
        return JSON.parse(selectedBooking.value?.participants || "[]");
    } catch (error) {
        console.error("Error parsing participants:", error);
        return [];
    }
});
</script>

<template>
    <div class="data-calendar">
        <FullCalendar :options="calendarOptions" />
        <p v-if="calendarOptions.events.length === 0" class="text-center text-gray-500 mt-4">No bookings available</p>

        <!-- Dialog Modal -->
        <DialogModal :show="showModal" @close="showModal = false">
            <template #title>
                Booking Details - {{ selectedBooking?.title }}
            </template>
            <template #content>
                <p class="text-lg font-semibold mt-4">
                    Booked By:
                    <span class="font-normal">
                        {{
                            selectedBooking?.bookable?.name
                                ? selectedBooking.bookable.name
                                : "N/A"
                        }}
                    </span>
                </p>

                <p class="text-lg font-semibold mt-2">
                    Interviewer:
                    <span class="font-normal">
                        {{
                            selectedBooking?.interviewer?.name
                                ? selectedBooking.interviewer.name
                                : "N/A"
                        }}
                    </span>
                </p>

                <button
                    @click="takeOverInterviewer"
                    class="mt-4 text-sm text-white bg-blue-500 hover:bg-blue-600 px-3 py-1 rounded"
                >
                    Takeover as an interviewer
                </button>

                <div class="border-t border-gray-200 mb-4 mt-4" />

                <p><strong>Description:</strong> {{ selectedBooking?.description }}</p>
                <p><strong>Company:</strong> {{ selectedBooking?.company }}</p>
                <p>
                    <strong>Status: </strong>
                    <span :class="getStatusClass(selectedBooking?.status)">
                        {{ selectedBooking?.status }}
                    </span>
                </p>

                <div class="border-t border-gray-200 mb-4 mt-4" />

                <p><strong>Participants:</strong></p>
                <ul v-if="parsedParticipants.length">
                    <li v-for="(participant, index) in parsedParticipants" :key="index" class="flex items-center gap-2">
                        <span class="mdi mdi-email-outline text-gray-600"></span>
                        <span class="font-medium">{{ participant }}</span>
                    </li>
                </ul>
                <p v-else class="text-gray-500">No participants added.</p>
            </template>
            <template #footer>
                <button @click="updateBookingStatus('pending')" class="bg-yellow-500 text-white px-4 py-2 rounded mr-2">
                    Pending
                </button>
                <button @click="updateBookingStatus('confirmed')" class="bg-green-500 text-white px-4 py-2 rounded mr-2">
                    Confirmed
                </button>
                <button @click="updateBookingStatus('canceled')" class="bg-red-500 text-white px-4 py-2 rounded">
                    Canceled
                </button>
            </template>
        </DialogModal>
    </div>
</template>
