<script setup>
import { usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

const props = defineProps({
    navbarTextStyle: {
        type: Object,
        required: true
    },
    onLogout: {
        type: Function,
        required: true
    }
});

const page = usePage();
const { roles, permissions } = page.props;

const hasPermission = (permission) => {
    return permissions.includes(permission);
};

const getInitials = (name) => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase();
};
</script>

<template>
    <div class="ms-3 relative">
        <Dropdown align="right" width="48">
            <template #trigger>
                <button
                    class="flex items-center text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition"
                >
                    <div
                        v-if="$page.props.auth.user.profile_photo_url"
                        class="w-8 h-8 rounded-full object-cover overflow-hidden"
                    >
                        <img
                            :src="$page.props.auth.user.profile_photo_url"
                            :alt="$page.props.auth.user.name"
                            class="w-full h-full"
                        />
                    </div>

                    <div
                        v-else
                        class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-white font-semibold"
                    >
                        {{ getInitials($page.props.auth.user.name) }}
                    </div>

                    <span
                        :style="navbarTextStyle"
                        class="ml-2 text-gray-700 text-sm font-medium"
                    >
                        {{ $page.props.auth.user.name }}
                    </span>
                </button>
            </template>

            <template #content>
                <!-- Account Management -->
                <div class="block px-4 py-2 text-xs text-gray-400">
                    Manage Account
                </div>

                <DropdownLink :href="route('profile.show')">Profile</DropdownLink>

                <DropdownLink
                    v-if="hasPermission('read roles')"
                    :href="route('roles.index')"
                >Roles & Permissions</DropdownLink>

                <DropdownLink
                    v-if="hasPermission('read activity logs')"
                    :href="route('activity-logs.index')"
                >Activity Logs</DropdownLink>

                <DropdownLink
                    v-if="$page.props.jetstream.hasApiFeatures"
                    :href="route('api-tokens.index')"
                >
                    API Tokens
                </DropdownLink>

                <DropdownLink
                    v-if="hasPermission('read categories')"
                    :href="route('categories.index')"
                    :active="route().current('categories.index')"
                >
                    Categories
                </DropdownLink>

                <DropdownLink
                    v-if="roles.includes('super-admin')"
                    :href="route('app.settings.index')"
                    :active="route().current('app.settings.index')"
                >
                    App Settings
                </DropdownLink>

                <div class="border-t border-gray-200" />

                <!-- Authentication -->
                <form @submit.prevent="onLogout">
                    <DropdownLink as="button">Log Out</DropdownLink>
                </form>
            </template>
        </Dropdown>
    </div>
</template> 