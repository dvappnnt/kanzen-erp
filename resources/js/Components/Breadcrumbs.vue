<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const currentRoute = computed(() => {
    const page = usePage();
    const path = page.url.split('/').filter(segment => segment);
    
    let breadcrumbs = [];
    let currentPath = '';
    
    // Always add dashboard as first item
    breadcrumbs.push({
        name: 'Dashboard',
        path: '/dashboard',
        current: path.length === 0 || (path.length === 1 && path[0] === 'dashboard')
    });

    // Build the rest of the breadcrumbs
    path.forEach((segment, index) => {
        if (segment === 'dashboard') return; // Skip dashboard as it's already added
        
        currentPath += `/${segment}`;
        const name = segment.split('-')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
            
        breadcrumbs.push({
            name,
            path: currentPath,
            current: index === path.length - 1
        });
    });

    return breadcrumbs;
});

const linkStyle = computed(() => ({
    color: '#6B7280', // Default gray color for inactive links
}));

const linkHoverStyle = computed(() => ({
    color: '#374151', // Darker gray on hover
}));

const currentItemStyle = computed(() => ({
    color: '#111827', // Almost black for current item
}));
</script>

<template>
    <nav 
        class="flex px-5 py-3 text-gray-700 shadow-sm rounded-lg bg-white mb-4" 
        aria-label="Breadcrumb"
    >
        <ol class="inline-flex items-center space-x-1 md:space-x-3">
            <li v-for="(item, index) in currentRoute" :key="index" class="inline-flex items-center">
                <!-- Separator for all items except first -->
                <div v-if="index > 0" class="flex items-center">
                    <svg class="w-3 h-3 mx-1 text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
                    </svg>
                </div>
                
                <!-- Link or text depending on whether it's the current item -->
                <Link
                    v-if="!item.current"
                    :href="item.path"
                    class="inline-flex items-center text-sm font-medium transition-colors duration-150 ease-in-out px-2 py-1 rounded"
                    :style="linkStyle"
                    @mouseover="$event.currentTarget.style.color = linkHoverStyle.color"
                    @mouseleave="$event.currentTarget.style.color = linkStyle.color"
                >
                    <span v-if="index === 0" class="mr-2">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                        </svg>
                    </span>
                    {{ item.name }}
                </Link>
                <span
                    v-else
                    class="inline-flex items-center text-sm font-medium px-2 py-1 rounded"
                    :style="currentItemStyle"
                >
                    <span v-if="index === 0" class="mr-2">
                        <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                        </svg>
                    </span>
                    {{ item.name }}
                </span>
            </li>
        </ol>
    </nav>
</template> 