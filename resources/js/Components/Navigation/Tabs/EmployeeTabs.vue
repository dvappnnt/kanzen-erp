<script setup>
import { ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import NavigationTabs from '@/Components/Navigation/NavigationTabs.vue';

const navigationTabs = ref([
    {
        text: "Overview",
        url: `/employees/${usePage().props.modelData.id}`,
        inertia: true,
        permission: "read employees",
    },
    {
        text: "Employment Details",
        url: `/employees/${usePage().props.modelData.id}/employment-details`,
        inertia: true,
        permission: "read employee employment details",
    },
    {
        text: "Payroll Details",
        url: `/employees/${usePage().props.modelData.id}/payroll-details`,
        inertia: true,
        permission: "read employee payroll details",
    },
    {
        text: "Work Experiences",
        url: `/employees/${
            usePage().props.modelData.id
        }/work-experiences`,
        inertia: true,
        permission: "read employee work experiences",
    },
    {
        text: "Educational Attainments",
        url: `/employees/${
            usePage().props.modelData.id
        }/educational-attainments`,
        inertia: true,
        permission: "read employee educational attainments",
    },
    {
        text: "Work Histories",
        url: `/employees/${usePage().props.modelData.id}/work-histories`,
        inertia: true,
        permission: "read employee work histories",
    },
    {
        text: "Contact Details",
        url: `/employees/${usePage().props.modelData.id}/contact-details`,
        inertia: true,
        permission: "read employee contact details",
    },
    {
        text: "Dependents",
        url: `/employees/${usePage().props.modelData.id}/dependents`,
        inertia: true,
        permission: "read employee dependents",
    },
    {
        text: "Documents",
        url: `/employees/${usePage().props.modelData.id}/documents`,
        inertia: true,
        permission: "read employee documents",
    },
    {
        text: "Certificates & Training",
        url: `/employees/${usePage().props.modelData.id}/certificates`,
        inertia: true,
        permission: "read employee certificates",
    },
    {
        text: "Awards",
        url: `/employees/${usePage().props.modelData.id}/awards`,
        inertia: true,
        permission: "read employee awards",
    },
    {
        text: "Skills",
        url: `/employees/${usePage().props.modelData.id}/skills`,
        inertia: true,
        permission: "read employee skills",
    },
    {
        text: "Performance Reviews",
        url: `/employees/${usePage().props.modelData.id}/performance-reviews`,
        inertia: true,
        permission: "read employee performance reviews",
    },
    {
        text: "Disciplinary Actions",
        url: `/employees/${usePage().props.modelData.id}/disciplinary-actions`,
        inertia: true,
        permission: "read employee disciplinary actions",
    },
]);
</script>

<template>
  <NavigationTabs :tabs="navigationTabs" />
</template>
