<script setup>
import { computed, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import moment from "moment";
import { humanReadable } from "@/utils/global";

const page = usePage();
const modelData = computed(() => page.props.modelData || {});
const educationalAttainments = computed(() => page.props.educationalAttainments || []);
const workExperiences = computed(() => page.props.workExperiences || []);
const dependents = computed(() => page.props.dependents || []);
const contactDetails = computed(() => page.props.contactDetails || []);
const documents = computed(() => page.props.documents || []);
const certificates = computed(() => page.props.certificates || []);
const disciplinaryActions = computed(() => page.props.disciplinaryActions || []);
const payrollDetails = computed(() => page.props.payrollDetails || []);
const employmentDetails = computed(() => {
    const details = page.props.employmentDetails || [];
    // Return the most recent employment detail (first item in array)
    return details.length > 0 ? details[0] : null;
});
const skills = computed(() => page.props.skills || []);
const awards = computed(() => page.props.awards || []);
const company = computed(() => {
    const companies = page.props.companies || [];
    return companies.length > 0 ? companies[0] : null;
});

// Compute the initial for avatar fallback
const avatarInitial = computed(() => {
    if (modelData.value.firstname) {
        return modelData.value.firstname.charAt(0).toUpperCase();
    }
    return '?';
});

// Get sections from query param
function getSectionsFromQuery() {
    const params = new URLSearchParams(window.location.search);
    const sections = params.get('sections');
    if (!sections) return [];
    return sections.split(',');
}
const sectionsToShow = computed(() => getSectionsFromQuery());

// Print the page when component mounts
onMounted(() => {
    window.print();
});
</script>

<template>
    <div class="print-container max-w-4xl mx-auto p-8 bg-white">
        <!-- Header with Avatar and Employee Name -->
        <div class="flex items-center justify-center mb-8 border-b pb-6">
            <div class="text-center">
                <div class="mb-4 flex justify-center">
                    <template v-if="modelData.avatar">
                        <img 
                            :src="`/storage/${modelData.avatar}`" 
                            :alt="modelData.firstname + ' ' + modelData.lastname"
                            class="w-32 h-32 rounded-full object-cover border-4 border-gray-200"
                        />
                    </template>
                    <template v-else>
                        <div class="w-32 h-32 rounded-full border-4 border-gray-200 bg-gray-100 flex items-center justify-center">
                            <span class="text-4xl font-bold text-gray-600">{{ avatarInitial }}</span>
                        </div>
                    </template>
                </div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    {{ modelData.firstname }} {{ modelData.middlename }} {{ modelData.lastname }}
                    <span v-if="modelData.suffix" class="text-gray-600">{{ modelData.suffix }}</span>
                </h1>
                <p class="text-gray-600">{{ modelData.number }}</p>
                <div class="mt-2 text-gray-600">
                    <p>{{ modelData.company?.name || 'No Company Assigned' }} - {{ modelData.department?.name || 'No Department Assigned' }}</p>
                </div>
            </div>
        </div>

        <!-- Personal Information -->
        <div v-if="sectionsToShow.includes('personal')" class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 border-b pb-2">Personal Information</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p><span class="font-medium">Gender:</span> {{ humanReadable(modelData.gender) || '-' }}</p>
                    <p><span class="font-medium">Birthdate:</span> {{ modelData.birthdate ? moment(modelData.birthdate).format('MMMM D, YYYY') : 'No details yet' }}</p>
                    <p><span class="font-medium">Birthplace:</span> {{ modelData.birthplace || '-' }}</p>
                    <p><span class="font-medium">Civil Status:</span> {{ humanReadable(modelData.civil_status) || '-' }}</p>
                </div>
                <div>
                    <p><span class="font-medium">Citizenship:</span> {{ modelData.citizenship || '-' }}</p>
                    <p><span class="font-medium">Religion:</span> {{ modelData.religion || '-' }}</p>
                    <p><span class="font-medium">Blood Type:</span> {{ modelData.blood_type || '-' }}</p>
                    <p><span class="font-medium">Height/Weight:</span> {{ (modelData.height || '-') }} cm/{{ (modelData.weight || '-') }} kg</p>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div v-if="sectionsToShow.includes('contact')" class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 border-b pb-2">Contact Information</h2>
            <div v-if="contactDetails.length > 0" class="grid grid-cols-2 gap-4">
                <div v-for="contact in contactDetails" :key="contact.id">
                    <p><span class="font-medium">Address:</span> {{ contact.address }}</p>
                    <p><span class="font-medium">Email:</span> {{ contact.email }}</p>
                    <p><span class="font-medium">Mobile:</span> {{ contact.mobile }}</p>
                    <p><span class="font-medium">Landline:</span> {{ contact.landline }}</p>
                </div>
            </div>
            <p v-else class="text-gray-500 italic">No contact details available yet</p>
        </div>

        <!-- Employment Details -->
        <div v-if="sectionsToShow.includes('employment')" class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 border-b pb-2">Employment Details</h2>
            <div v-if="employmentDetails" class="grid grid-cols-2 gap-4">
                <div>
                    <p><span class="font-medium">Position:</span> {{ employmentDetails.position?.name || '-' }}</p>
                    <p><span class="font-medium">Employment Status:</span> {{ humanReadable(employmentDetails.employment_status) || '-' }}</p>
                    <p><span class="font-medium">From Date:</span> {{ employmentDetails.from_date ? moment(employmentDetails.from_date).format('MMMM D, YYYY') : '-' }}</p>
                    <p><span class="font-medium">Salary Type:</span> {{ humanReadable(employmentDetails.salary_type) || '-' }}</p>
                </div>
                <div>
                    <p><span class="font-medium">Basic Salary:</span> {{ employmentDetails.basic_salary ? '₱' + parseFloat(employmentDetails.basic_salary).toLocaleString() : '-' }}</p>
                    <p><span class="font-medium">Tax Status:</span> {{ humanReadable(employmentDetails.tax_status) || '-' }}</p>
                    <p><span class="font-medium">To Date:</span> {{ employmentDetails.to_date ? moment(employmentDetails.to_date).format('MMMM D, YYYY') : 'Present' }}</p>
                    <p v-if="employmentDetails.remarks"><span class="font-medium">Remarks:</span> {{ employmentDetails.remarks }}</p>
                </div>
            </div>
            <p v-else class="text-gray-500 italic">No employment details available yet</p>
        </div>

        <!-- Educational Attainments -->
        <div v-if="sectionsToShow.includes('education')" class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 border-b pb-2">Educational Background</h2>
            <div v-if="educationalAttainments.length > 0" class="space-y-4">
                <div v-for="education in educationalAttainments" :key="education.id" class="border-l-4 border-gray-200 pl-4">
                    <h3 class="font-medium text-lg">{{ humanReadable(education.level) }}</h3>
                    <p class="font-bold text-gray-900">{{ education.school_name }}</p>
                    <p v-if="education.course" class="text-gray-600">{{ education.course }}</p>
                    <p class="text-sm text-gray-500">
                        {{ moment(education.from_date).format('MMMM YYYY') }} - 
                        {{ education.to_date ? moment(education.to_date).format('MMMM YYYY') : 'Present' }}
                    </p>
                    <p v-if="education.honors_received" class="text-sm text-gray-600 mt-1">
                        Honors/Awards: {{ education.honors_received }}
                    </p>
                </div>
            </div>
            <p v-else class="text-gray-500 italic">No educational background available yet</p>
        </div>

        <!-- Work Experience -->
        <div v-if="sectionsToShow.includes('work')" class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 border-b pb-2">Work Experience</h2>
            <div v-if="workExperiences.length > 0" class="space-y-4">
                <div v-for="experience in workExperiences" :key="experience.id" class="border-l-4 border-gray-200 pl-4">
                    <h3 class="font-medium text-lg">{{ experience.position }}</h3>
                    <p class="text-gray-600">{{ experience.company_name }}</p>
                    <p class="text-sm text-gray-500">
                        {{ moment(experience.start_date).format('MMMM YYYY') }} - 
                        {{ experience.end_date ? moment(experience.end_date).format('MMMM YYYY') : 'Present' }}
                    </p>
                    <p v-if="experience.responsibilities" class="text-sm text-gray-600 mt-2">
                        {{ experience.responsibilities }}
                    </p>
                    <p v-if="experience.reason_for_leaving" class="text-sm text-gray-600 mt-1">
                        Reason for leaving: {{ experience.reason_for_leaving }}
                    </p>
                    <p v-if="experience.last_salary" class="text-sm text-gray-600 mt-1">
                        Last Salary: ₱{{ parseFloat(experience.last_salary).toLocaleString() }}
                    </p>
                </div>
            </div>
            <p v-else class="text-gray-500 italic">No work experience available yet</p>
        </div>

        <!-- Skills -->
        <div v-if="sectionsToShow.includes('skills')" class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 border-b pb-2">Skills</h2>
            <div v-if="skills.length > 0">
                <!-- Advanced Skills -->
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-green-800 mb-2 flex items-center">
                        <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                        Advanced Skills
                    </h3>
                    <div class="pl-4">
                        <div v-if="skills.some(s => s.proficiency_level === 'advanced')" class="grid grid-cols-3 gap-2">
                            <div v-for="skill in skills.filter(s => s.proficiency_level === 'advanced')" :key="skill.id" class="flex items-start gap-2">
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">
                                    {{ skill.name }}
                                </span>
                                <span v-if="skill.description" class="text-sm text-gray-600">
                                    - {{ skill.description }}
                                </span>
                            </div>
                        </div>
                        <p v-else class="text-gray-500 italic text-sm">No advanced skills listed</p>
                    </div>
                </div>

                <!-- Intermediate Skills -->
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-yellow-800 mb-2 flex items-center">
                        <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></span>
                        Intermediate Skills
                    </h3>
                    <div class="pl-4">
                        <div v-if="skills.some(s => s.proficiency_level === 'intermediate')" class="grid grid-cols-3 gap-2">
                            <div v-for="skill in skills.filter(s => s.proficiency_level === 'intermediate')" :key="skill.id" class="flex items-start gap-2">
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">
                                    {{ skill.name }}
                                </span>
                                <span v-if="skill.description" class="text-sm text-gray-600">
                                    - {{ skill.description }}
                                </span>
                            </div>
                        </div>
                        <p v-else class="text-gray-500 italic text-sm">No intermediate skills listed</p>
                    </div>
                </div>

                <!-- Beginner Skills -->
                <div class="mb-4">
                    <h3 class="text-lg font-medium text-blue-800 mb-2 flex items-center">
                        <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>
                        Beginner Skills
                    </h3>
                    <div class="pl-4">
                        <div v-if="skills.some(s => s.proficiency_level === 'beginner')" class="grid grid-cols-3 gap-2">
                            <div v-for="skill in skills.filter(s => s.proficiency_level === 'beginner')" :key="skill.id" class="flex items-start gap-2">
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">
                                    {{ skill.name }}
                                </span>
                                <span v-if="skill.description" class="text-sm text-gray-600">
                                    - {{ skill.description }}
                                </span>
                            </div>
                        </div>
                        <p v-else class="text-gray-500 italic text-sm">No beginner skills listed</p>
                    </div>
                </div>
            </div>
            <p v-else class="text-gray-500 italic">No skills listed yet</p>
        </div>

        <!-- Certificates & Training -->
        <div v-if="sectionsToShow.includes('certificates')" class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 border-b pb-2">Certificates & Training</h2>
            <div v-if="certificates.length > 0" class="space-y-4">
                <div v-for="cert in certificates" :key="cert.id" class="border-l-4 border-gray-200 pl-4">
                    <h3 class="font-medium text-lg">{{ cert.title }}</h3>
                    <p class="text-gray-600">{{ cert.location }}</p>
                    <p class="text-gray-600">{{ cert.organizer }}</p>
                    <p class="text-sm text-gray-500">
                        {{ moment(cert.date_from).format('MMMM YYYY') }}
                        <span v-if="cert.date_to"> - {{ moment(cert.date_to).format('MMMM YYYY') }}</span>
                    </p>
                </div>
            </div>
            <p v-else class="text-gray-500 italic">No certificates or training records available yet</p>
        </div>

        <!-- Awards -->
        <div v-if="sectionsToShow.includes('awards')" class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 border-b pb-2">Awards & Recognition</h2>
            <div v-if="awards.length > 0" class="space-y-4">
                <div v-for="award in awards" :key="award.id" class="border-l-4 border-gray-200 pl-4">
                    <h3 class="font-medium text-lg">{{ award.title }}</h3>
                    <p class="text-gray-600">{{ award.awarded_by }}</p>
                    <p class="text-sm text-gray-500">
                        {{ moment(award.date).format('MMMM YYYY') }}
                    </p>
                    <p v-if="award.description" class="text-sm text-gray-600 mt-2">
                        {{ award.description }}
                    </p>
                </div>
            </div>
            <p v-else class="text-gray-500 italic">No awards or recognition records available yet</p>
        </div>

        <!-- Government IDs -->
        <div v-if="sectionsToShow.includes('government')" class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 border-b pb-2">Government IDs</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p><span class="font-medium">SSS:</span> {{ modelData.sss || '-' }}</p>
                    <p><span class="font-medium">PhilHealth:</span> {{ modelData.philhealth || '-' }}</p>
                </div>
                <div>
                    <p><span class="font-medium">Pag-IBIG:</span> {{ modelData.pagibig || '-' }}</p>
                    <p><span class="font-medium">TIN:</span> {{ modelData.tin || '-' }}</p>
                    <p><span class="font-medium">UMID:</span> {{ modelData.umid || '-' }}</p>
                </div>
            </div>
        </div>

        <!-- Dependents -->
        <div v-if="sectionsToShow.includes('dependents')" class="mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4 border-b pb-2">Dependents</h2>
            <div v-if="dependents.length > 0" class="grid grid-cols-2 gap-4">
                <div v-for="dependent in dependents" :key="dependent.id" class="border-l-4 border-gray-200 pl-4">
                    <h3 class="font-medium">{{ dependent.name }}</h3>
                    <p class="text-sm text-gray-600" v-if="dependent.relationship">Relationship: {{ humanReadable(dependent.relationship) || '-' }}</p>
                    <p class="text-sm text-gray-600" v-if="dependent.birthplace">Birthplace: {{ dependent.birthplace || '-' }}</p>
                    <p class="text-sm text-gray-600" v-if="dependent.birthdate">Birthdate: {{ dependent.birthdate ? moment(dependent.birthdate).format('MMMM D, YYYY') : '-' }}</p>
                    <p class="text-sm text-gray-600" v-if="dependent.email">Email: {{ dependent.email || '-' }}</p>
                    <p class="text-sm text-gray-600" v-if="dependent.mobile">Mobile: {{ dependent.mobile || '-' }}</p>
                    <p class="text-sm text-gray-600" v-if="dependent.landline">Landline: {{ dependent.landline || '-' }}</p>
                    <p class="text-sm text-gray-600" v-if="dependent.address">Address: {{ dependent.address || '-' }}</p>
                </div>
            </div>
            <p v-else class="text-gray-500 italic">No dependents listed yet</p>
        </div>

        <!-- Company Information Footer -->
        <div class="mt-12 pt-8 border-t border-gray-200">
            <div class="text-center">
                <h2 class="text-xl font-bold text-gray-900 mb-4">{{ modelData.company?.name }}</h2>
                <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
                    <div class="text-left">
                        <p v-if="modelData.company?.address" class="mb-1">{{ modelData.company.address }}</p>
                        <p v-if="modelData.company?.email" class="mb-1">{{ modelData.company.email }}</p>
                    </div>
                    <div class="text-right">
                        <p v-if="modelData.company?.mobile" class="mb-1">{{ modelData.company.mobile }}</p>
                        <p v-if="modelData.company?.landline" class="mb-1">{{ modelData.company.landline }}</p>
                        <p v-if="modelData.company?.website" class="mb-1">
                            <a :href="modelData.company.website" class="text-blue-600 hover:underline" target="_blank">
                                {{ modelData.company.website }}
                            </a>
                        </p>
                    </div>
                </div>
                <p v-if="modelData.company?.description" class="mt-4 text-sm text-gray-500 italic">
                    {{ modelData.company.description }}
                </p>
            </div>
        </div>
    </div>
</template>

<style>
@media print {
    @page {
        margin: 2cm;
    }
    
    body {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    .print-container {
        max-width: none;
        padding: 0;
    }

    /* Hide any elements that shouldn't be printed */
    .no-print {
        display: none !important;
    }

    /* Ensure images print properly */
    img {
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }

    /* Ensure links are visible in print */
    a {
        text-decoration: none;
        color: inherit;
    }
}
</style>
