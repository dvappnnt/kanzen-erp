<script setup>
import AppLayout from "@/Layouts/AppLayout.vue";
import HeaderActions from "@/Components/HeaderActions.vue";
import { ref, onMounted, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { router } from "@inertiajs/vue3";
import axios from "@/axios";
import moment from "moment";
import draggable from 'vuedraggable';

const modelName = "projects";
const columns = ref([]);
const projects = ref([]);
const isLoading = ref(false);

// Access appSettings from Inertia.js page props
const { appSettings } = usePage().props;
const primaryColor = computed(() => appSettings?.primary_color || "#3B82F6");

// Define Header Actions
const headerActions = ref([
    {
        text: "Create",
        url: `/${modelName}/create`,
        inertia: true,
        class: "hover:bg-opacity-90 text-white px-4 py-2 rounded",
        style: computed(() => ({
            backgroundColor: primaryColor.value,
        })),
    },
]);

// Computed property to sort columns by position
const sortedColumns = computed(() => {
    return [...columns.value].sort((a, b) => a.position - b.position);
});

// Computed property to get projects for a column
const getColumnProjects = (columnId) => {
    if (!projects.value) return [];
    return projects.value
        .filter(p => p.project_column_id === columnId)
        .sort((a, b) => a.position - b.position);
};

// Fetch Columns and Projects
const fetchData = async () => {
    isLoading.value = true;
    try {
        const [columnsResponse, projectsResponse] = await Promise.all([
            axios.get('/api/projects-columns'),
            axios.get('/api/projects')
        ]);
        
        columns.value = columnsResponse.data.data || [];
        projects.value = Array.isArray(projectsResponse.data) ? projectsResponse.data : (projectsResponse.data.data || []);
    } catch (error) {
        console.error("Error fetching data:", error.response?.data || error.message);
        columns.value = [];
        projects.value = [];
    } finally {
        isLoading.value = false;
    }
};

// Handle project drag end
const onDragEnd = async (evt) => {
    const { newIndex, oldIndex, from, to } = evt;
    const project = evt.item.__draggable_context?.element;
    const newColumnId = parseInt(to.dataset.columnId);
    const oldColumnId = parseInt(from.dataset.columnId);
    
    if (!project) return;
    
    try {
        // Calculate new position based on surrounding projects
        const columnProjects = getColumnProjects(newColumnId);
        let newPosition;
        
        if (columnProjects.length === 0) {
            newPosition = 0;
        } else if (newIndex === 0) {
            newPosition = columnProjects[0].position - 1;
        } else if (newIndex >= columnProjects.length) {
            newPosition = columnProjects[columnProjects.length - 1].position + 1;
        } else {
            // Position between two projects
            const prevPosition = columnProjects[newIndex - 1].position;
            const nextPosition = columnProjects[newIndex].position;
            newPosition = prevPosition + Math.floor((nextPosition - prevPosition) / 2);
        }

        // Update local state first for immediate feedback
        const projectIndex = projects.value.findIndex(p => p.id === project.id);
        if (projectIndex !== -1) {
            projects.value[projectIndex] = {
                ...projects.value[projectIndex],
                project_column_id: newColumnId,
                position: newPosition
            };
        }
        
        // Send update to server
        await axios.put(`/api/projects/${project.id}`, {
            ...project,
            project_column_id: newColumnId,
            position: newPosition
        });
    } catch (error) {
        console.error("Error updating project:", error.response?.data || error.message);
        // Only fetch data if the update failed
        await fetchData();
    }
};

// Initialize Data
onMounted(() => fetchData());
</script>

<template>
    <AppLayout :title="modelName.charAt(0).toUpperCase() + modelName.slice(1)">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ modelName.charAt(0).toUpperCase() + modelName.slice(1) }}
                </h2>
                <HeaderActions :actions="headerActions" />
            </div>
        </template>

        <div class="max-w-12xl">
            <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                <div class="p-6">
                    <div v-if="isLoading" class="flex justify-center items-center h-64">
                        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900"></div>
                    </div>
                    
                    <div v-else class="flex space-x-4 overflow-x-auto pb-4">
                        <div v-for="column in sortedColumns" :key="column.id" class="flex-shrink-0 w-80">
                            <div class="bg-gray-100 rounded-lg p-4">
                                <h3 class="font-semibold text-lg mb-4">
                                    {{ column.name }}
                                    <span class="text-sm text-gray-500">({{ getColumnProjects(column.id).length }})</span>
                                </h3>
                                <draggable
                                    :list="getColumnProjects(column.id)"
                                    :group="{ name: 'projects' }"
                                    item-key="id"
                                    class="space-y-3 min-h-[200px]"
                                    :data-column-id="column.id"
                                    @end="onDragEnd"
                                >
                                    <template #item="{ element }">
                                        <div 
                                            class="bg-white rounded-lg shadow p-4 cursor-move hover:shadow-md transition-shadow"
                                            :style="{ borderLeft: `4px solid ${primaryColor}` }"
                                        >
                                            <h4 class="font-medium text-gray-900">{{ element.name }}</h4>
                                            <p class="text-sm text-gray-600 mt-2">{{ element.description }}</p>
                                            <div class="mt-4 flex justify-between items-center">
                                                <span class="text-xs text-gray-500">
                                                    {{ element.start_date ? moment(element.start_date).format('MMM D, YYYY') : 'No start date' }}
                                                </span>
                                                <span class="text-xs text-gray-500">
                                                    {{ element.end_date ? moment(element.end_date).format('MMM D, YYYY') : 'No end date' }}
                                                </span>
                                            </div>
                                            <div class="mt-2 flex items-center">
                                                <span class="px-2 py-1 text-xs rounded" 
                                                    :style="{ 
                                                        backgroundColor: primaryColor + '20',
                                                        color: primaryColor
                                                    }"
                                                >
                                                    {{ element.status }}
                                                </span>
                                            </div>
                                        </div>
                                    </template>
                                </draggable>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.draggable-ghost {
    opacity: 0.5;
    background: #c8ebfb;
}

.draggable-drag {
    opacity: 0.9;
    background: #ffffff;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
</style>
