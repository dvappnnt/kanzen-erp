<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

const props = defineProps({
    showingNavigationDropdown: {
        type: Boolean,
        required: true
    },
    onLogout: {
        type: Function,
        required: true
    },
    onSwitchTeam: {
        type: Function,
        required: true
    }
});

const page = usePage();
const { roles, permissions } = page.props;

const hasPermission = (permission) => {
    return permissions.includes(permission);
};
</script>

<template>
    <div
        :class="{
            block: showingNavigationDropdown,
            hidden: !showingNavigationDropdown,
        }"
        class="sm:hidden"
    >
        <div class="pt-2 pb-3 space-y-1">
            <ResponsiveNavLink
                :href="route('dashboard')"
                :active="route().current('dashboard')"
            >
                Dashboard
            </ResponsiveNavLink>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="flex items-center px-4">
                <div
                    v-if="$page.props.jetstream.managesProfilePhotos"
                    class="shrink-0 me-3"
                >
                    <img
                        class="size-10 rounded-full object-cover"
                        :src="$page.props.auth.user.profile_photo_url"
                        :alt="$page.props.auth.user.name"
                    />
                </div>

                <div>
                    <div class="font-medium text-base text-gray-800">
                        {{ $page.props.auth.user.name }}
                    </div>
                    <div class="font-medium text-sm text-gray-500">
                        {{ $page.props.auth.user.email }}
                    </div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <ResponsiveNavLink
                    :href="route('profile.show')"
                    :active="route().current('profile.show')"
                >
                    Profile
                </ResponsiveNavLink>

                <ResponsiveNavLink
                    v-if="roles.includes('super-admin')"
                    :href="route('app.settings.index')"
                    :active="route().current('app.settings.index')"
                >
                    App Settings
                </ResponsiveNavLink>

                <ResponsiveNavLink
                    v-if="$page.props.jetstream.hasApiFeatures"
                    :href="route('api-tokens.index')"
                    :active="route().current('api-tokens.index')"
                >
                    API Tokens
                </ResponsiveNavLink>

                <!-- Authentication -->
                <form method="POST" @submit.prevent="onLogout">
                    <ResponsiveNavLink as="button">
                        Log Out
                    </ResponsiveNavLink>
                </form>

                <!-- Team Management -->
                <template v-if="$page.props.jetstream.hasTeamFeatures">
                    <div class="border-t border-gray-200" />

                    <div class="block px-4 py-2 text-xs text-gray-400">
                        Manage Team
                    </div>

                    <!-- Team Settings -->
                    <ResponsiveNavLink
                        :href="route('teams.show', $page.props.auth.user.current_team)"
                        :active="route().current('teams.show')"
                    >
                        Team Settings
                    </ResponsiveNavLink>

                    <ResponsiveNavLink
                        v-if="$page.props.jetstream.canCreateTeams"
                        :href="route('teams.create')"
                        :active="route().current('teams.create')"
                    >
                        Create New Team
                    </ResponsiveNavLink>

                    <!-- Team Switcher -->
                    <template v-if="$page.props.auth.user.all_teams.length > 1">
                        <div class="border-t border-gray-200" />

                        <div class="block px-4 py-2 text-xs text-gray-400">
                            Switch Teams
                        </div>

                        <template
                            v-for="team in $page.props.auth.user.all_teams"
                            :key="team.id"
                        >
                            <form @submit.prevent="onSwitchTeam(team)">
                                <ResponsiveNavLink as="button">
                                    <div class="flex items-center">
                                        <svg
                                            v-if="team.id == $page.props.auth.user.current_team_id"
                                            class="me-2 size-5 text-green-400"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke-width="1.5"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                        <div>{{ team.name }}</div>
                                    </div>
                                </ResponsiveNavLink>
                            </form>
                        </template>
                    </template>
                </template>
            </div>
        </div>
    </div>
</template> 