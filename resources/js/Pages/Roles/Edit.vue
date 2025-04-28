<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, computed } from "vue";
import axios from "@/axios";
import { router, usePage } from "@inertiajs/vue3";
import { useColors } from "@/Composables/useColors";

const modelName = "roles";
const isSubmitting = ref(false);

const props = defineProps({
    role: Object,
    rolePermissions: {
        type: Array,
        required: true,
    },
    resources: {
        type: Array,
        required: true,
    },
});

const { buttonPrimaryBgColor } = useColors();

const headerActions = ref([
    {
        text: "Go Back",
        url: `/${modelName}`,
        inertia: true,
        class: "hover:bg-opacity-90 text-white px-4 py-2 rounded",
        style: computed(() => ({
            backgroundColor: buttonPrimaryBgColor.value,
        })),
    },
]);

// Initialize form data
const formData = ref({
    roleName: props.role.name, // Initialize role name
    permissions: [...props.rolePermissions],
});

// Check if all permissions for a resource are selected
const isResourceFullySelected = (resource) =>
    ["create", "read", "update", "update", "delete", "restore"].every(
        (action) => formData.value.permissions.includes(`${action} ${resource}`)
    );

// Toggle All Permissions for Resource
const toggleAllPermissions = (resource, checked) => {
    const actions = ["create", "read", "update", "delete", "restore"];

    if (checked) {
        // Add all actions for the resource
        actions.forEach((action) => {
            const permission = `${action} ${resource}`;
            if (!formData.value.permissions.includes(permission)) {
                formData.value.permissions.push(permission);
            }
        });
    } else {
        // Remove all actions for the resource
        formData.value.permissions = formData.value.permissions.filter(
            (perm) =>
                !actions.some((action) => perm === `${action} ${resource}`)
        );
    }
};

// Submit the form
const submitForm = async () => {
    try {
        isSubmitting.value = true;

        // Send PUT request to update the role and permissions
        await axios.put(`/api/roles/${props.role.id}`, {
            roleName: formData.value.roleName,
            permissions: formData.value.permissions,
        });

        // Redirect back to roles listing
        router.get("/roles");
    } catch (error) {
        console.error(
            "Error submitting the form:",
            error.response?.data || error.message
        );
        alert("An error occurred while updating the permissions.");
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <AppLayout :title="`Edit Permissions for ${role.name}`">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Edit Permissions for Role: {{ role.name }}
                </h2>
                <HeaderActions :actions="headerActions" />
            </div>
        </template>

        <div class="max-w-12xl">
            <div class="bg-white shadow rounded-lg p-6">
                <form @submit.prevent="submitForm">
                    <!-- Role Name Section -->
                    <div class="mb-8">
                        <label
                            for="role-name"
                            class="block text-lg font-medium text-gray-900 mb-2"
                        >
                            Role Name
                        </label>
                        <input
                            type="text"
                            id="role-name"
                            v-model="formData.roleName"
                            class="mt-1 block w-full rounded-md shadow-sm border-gray-300 focus:ring-[var(--primary-color)] focus:border-[var(--primary-color)] sm:text-sm"
                            required
                        />
                    </div>

                    <!-- Divider Line -->
                    <hr class="my-6 border-t border-gray-300" />

                    <fieldset
                        v-for="resource in resources"
                        :key="resource"
                        class="mb-8"
                    >
                        <div class="flex items-center mb-4">
                            <input
                                type="checkbox"
                                :id="`select-all-${resource}`"
                                :checked="isResourceFullySelected(resource)"
                                @change="
                                    toggleAllPermissions(
                                        resource,
                                        $event.target.checked
                                    )
                                "
                                class="focus:ring-[var(--primary-color)] h-4 w-4 text-[var(--primary-color)] border-gray-300 rounded"
                                :style="{ '--primary-color': buttonPrimaryBgColor }"
                            />
                            <label
                                :for="`select-all-${resource}`"
                                class="ml-2 text-lg font-medium text-gray-900"
                            >
                                {{ resource.replace("-", " ").toUpperCase() }}
                            </label>
                        </div>

                        <div class="grid grid-cols-3 gap-4">
                            <div
                                v-for="action in [
                                    'create',
                                    'read',
                                    'update',
                                    'delete',
                                    'restore',
                                ]"
                                :key="action"
                                class="flex items-start"
                            >
                                <input
                                    type="checkbox"
                                    :id="`${resource} ${action}`"
                                    :value="`${action} ${resource}`"
                                    v-model="formData.permissions"
                                    class="focus:ring-[var(--primary-color)] h-4 w-4 text-[var(--primary-color)] border-gray-300 rounded"
                                    :style="{ '--primary-color': buttonPrimaryBgColor }"
                                />
                                <label
                                    :for="`${resource}-${action}`"
                                    class="ml-3 text-sm font-medium text-gray-700"
                                >
                                    {{
                                        action.charAt(0).toUpperCase() +
                                        action.slice(1)
                                    }}
                                </label>
                            </div>
                        </div>
                    </fieldset>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring disabled:opacity-25 transition"
                            :class="{
                                'bg-[var(--primary-color)] hover:bg-opacity-90 active:bg-opacity-80 focus:ring-[var(--primary-color)]': true,
                            }"
                            :style="{ '--primary-color': buttonPrimaryBgColor }"
                            :disabled="isSubmitting"
                        >
                            Update Permissions
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
