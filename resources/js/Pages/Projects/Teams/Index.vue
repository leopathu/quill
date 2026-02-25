<template>
    <AuthenticatedLayout :project="project">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Team Members
                </h2>
                <button
                    v-if="userRole === 'manager'"
                    @click="showAddModal = true"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                >
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM3 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 9.374 21c-2.331 0-4.512-.645-6.374-1.766Z" />
                    </svg>
                    Add Member
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <!-- Flash Messages -->
                    <div v-if="$page.props.flash?.success" class="bg-green-50 border-l-4 border-green-400 p-4 m-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700">{{ $page.props.flash.success }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="$page.props.flash?.error" class="bg-red-50 border-l-4 border-red-400 p-4 m-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700">{{ $page.props.flash.error }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Team Members List -->
                    <div class="p-6">
                        <div class="space-y-4">
                            <div
                                v-for="member in teamMembers"
                                :key="member.id"
                                class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                            >
                                <div class="flex items-center space-x-4">
                                    <!-- Avatar -->
                                    <div class="flex-shrink-0">
                                        <img
                                            v-if="member.avatar"
                                            :src="member.avatar"
                                            :alt="member.name"
                                            class="h-12 w-12 rounded-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="h-12 w-12 rounded-full bg-indigo-600 flex items-center justify-center text-white font-semibold text-lg"
                                        >
                                            {{ member.name.charAt(0).toUpperCase() }}
                                        </div>
                                    </div>

                                    <!-- Member Info -->
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                {{ member.name }}
                                            </h3>
                                            <span
                                                v-if="member.is_owner"
                                                class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20"
                                            >
                                                Owner
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ member.email }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <!-- Role Badge -->
                                    <span
                                        :class="{
                                            'bg-purple-50 text-purple-700 ring-purple-700/10': member.role === 'manager',
                                            'bg-blue-50 text-blue-700 ring-blue-700/10': member.role === 'developer'
                                        }"
                                        class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                    >
                                        {{ member.role === 'manager' ? 'Manager' : 'Developer' }}
                                    </span>

                                    <!-- Actions -->
                                    <div v-if="userRole === 'manager' && !member.is_owner" class="flex items-center gap-2">
                                        <button
                                            @click="openEditModal(member)"
                                            class="text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400"
                                            title="Change role"
                                        >
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                            </svg>
                                        </button>
                                        <button
                                            @click="confirmRemove(member)"
                                            class="text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                                            title="Remove member"
                                        >
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div v-if="teamMembers.length === 0" class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">No team members</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by adding a team member.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Member Modal -->
        <AddMemberModal
            :show="showAddModal"
            :project="project"
            :available-users="availableUsers"
            @close="showAddModal = false"
        />

        <!-- Edit Role Modal -->
        <EditRoleModal
            :show="showEditModal"
            :project="project"
            :member="selectedMember"
            @close="showEditModal = false"
        />

        <!-- Remove Confirmation Modal -->
        <RemoveMemberModal
            :show="showRemoveModal"
            :project="project"
            :member="selectedMember"
            @close="showRemoveModal = false"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import AddMemberModal from './AddMemberModal.vue';
import EditRoleModal from './EditRoleModal.vue';
import RemoveMemberModal from './RemoveMemberModal.vue';

const props = defineProps({
    project: Object,
    teamMembers: Array,
    availableUsers: Array,
    userRole: String,
});

const showAddModal = ref(false);
const showEditModal = ref(false);
const showRemoveModal = ref(false);
const selectedMember = ref(null);

const openEditModal = (member) => {
    selectedMember.value = member;
    showEditModal.value = true;
};

const confirmRemove = (member) => {
    selectedMember.value = member;
    showRemoveModal.value = true;
};
</script>
