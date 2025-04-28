<script setup>
import { ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Echo from 'laravel-echo';
import Dropdown from '@/Components/Dropdown.vue';

const props = defineProps({
    navbarTextColor: {
        type: String,
        required: true
    },
    onHover: {
        type: Function,
        required: true
    },
    onLeave: {
        type: Function,
        required: true
    },
    primaryColor: {
        type: String,
        required: true
    }
});

// Notification state
const notifications = ref([]);
const unreadCount = ref(0);
const loadingNotifications = ref(true);
const page = usePage();

const fetchNotifications = async () => {
    try {
        const { data } = await axios.get("/api/notifications");
        notifications.value = data.notifications;
        unreadCount.value = data.unread_count;
    } catch (error) {
        console.error("Error fetching notifications:", error);
    } finally {
        loadingNotifications.value = false;
    }
};

const markAsRead = async (id) => {
    await axios.post(`/api/notifications/${id}/read`);
    notifications.value = notifications.value.filter((n) => n.id !== id);
    unreadCount.value -= 1;
};

const markAllAsRead = async () => {
    try {
        await axios.post('/api/notifications/mark-all-read');
        unreadCount.value = 0;
        notifications.value = notifications.value.map(notification => ({
            ...notification,
            read_at: new Date()
        }));
    } catch (error) {
        console.error('Error marking notifications as read:', error);
    }
};

onMounted(() => {
    fetchNotifications();

    if (window.Echo) {
        window.Echo.private(
            `App.Models.User.${page.props.auth.user.id}`
        ).notification((notification) => {
            if (notification?.data) {
                notifications.value.unshift(notification);
                unreadCount.value += 1;
                notifications.value = [...notifications.value];
            }
        });
    } else {
        console.error("Laravel Echo is not initialized.");
    }
});
</script>

<template>
    <div class="relative">
        <Dropdown align="right" class="z-50">
            <template #trigger>
                <button
                    :style="{
                        backgroundColor: 'transparent',
                        color: navbarTextColor,
                    }"
                    class="flex items-center justify-center w-10 h-10 rounded-full transition focus:outline-none active:bg-gray-200"
                    @mouseover="onHover($event)"
                    @mouseleave="onLeave($event)"
                >
                    <i class="mdi mdi-bell text-xl"></i>
                    <span
                        v-if="unreadCount > 0"
                        class="absolute top-0 right-0 w-4 h-4 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"
                    >
                        {{ unreadCount }}
                    </span>
                </button>
            </template>

            <template #content>
                <div class="w-[320px] bg-white rounded-lg shadow-lg">
                    <div class="p-4 border-b border-gray-200">
                        <h4 class="font-medium text-gray-700">
                            Notifications
                        </h4>
                    </div>
                    <div class="max-h-[400px] overflow-y-auto">
                        <div
                            v-if="loadingNotifications"
                            class="p-4 text-center text-gray-500"
                        >
                            Loading notifications...
                        </div>
                        <template v-else>
                            <template
                                v-for="notification in notifications"
                                :key="notification.id"
                            >
                                <div
                                    class="p-4 hover:bg-gray-50 flex items-start space-x-3"
                                >
                                    <div class="flex-grow">
                                        <p
                                            v-if="notification.data?.title"
                                            class="text-gray-700 font-medium"
                                        >
                                            {{ notification.data.title }}
                                        </p>
                                        <p
                                            v-if="notification.data?.message"
                                            class="text-sm text-gray-500"
                                        >
                                            {{ notification.data.message }}
                                        </p>
                                        <a
                                            v-if="notification.data?.url"
                                            :href="notification.data.url"
                                            class="text-sm text-blue-500 hover:underline"
                                            :style="{ color: primaryColor }"
                                            @click="markAsRead(notification.id)"
                                        >
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </template>
                            <div
                                v-if="notifications.length === 0"
                                class="p-4 text-center text-gray-500"
                            >
                                No notifications available.
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </Dropdown>
    </div>
</template> 