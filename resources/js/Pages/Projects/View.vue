<template>
    <AuthenticatedLayout :project="project">
        <Head :title="project.name" />

        <!-- Project Description -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">About Project</h3>
                <p v-if="project.description" class="text-sm text-gray-600 dark:text-gray-400">
                    {{ project.description }}
                </p>
                <p v-else class="text-sm text-gray-500 dark:text-gray-400 italic">
                    No description provided
                </p>
            </div>
        </div>

        <!-- Project Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <!-- Status Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</div>
                    <div class="mt-2">
                        <span v-if="project.status === 'Created'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            Created
                        </span>
                        <span v-else-if="project.status === 'Progressing'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                            Progressing
                        </span>
                        <span v-else-if="project.status === 'On Hold'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            On Hold
                        </span>
                        <span v-else-if="project.status === 'Completed'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            Completed
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tasks Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Tasks</div>
                    <div class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">0/0</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Completed</div>
                </div>
            </div>

            <!-- Team Members Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Team Members</div>
                    <div class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">1</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Active</div>
                </div>
            </div>

            <!-- Created Date Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</div>
                    <div class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                        {{ new Date(project.created_at).toLocaleDateString() }}
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        {{ new Date(project.created_at).toLocaleTimeString() }}
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Project Owner -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Project Owner</h2>
                    <div class="flex items-center space-x-4">
                        <div v-if="project.owner.avatar" class="flex-shrink-0">
                            <img :src="`/storage/${project.owner.avatar}`" :alt="project.owner.name" class="h-12 w-12 rounded-full object-cover">
                        </div>
                        <div v-else class="flex-shrink-0">
                            <div class="h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                <span class="text-xl font-medium text-indigo-600 dark:text-indigo-400">
                                    {{ project.owner.name.split(' ').map(n => n[0]).join('').toUpperCase() }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">{{ project.owner.name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">Owner</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Activity</h2>
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No activity yet</p>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    project: Object,
});
</script>
