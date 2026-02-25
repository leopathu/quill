<template>
    <AuthenticatedLayout :project="project">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Tasks
                </h2>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                >
                    <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    New Task
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-full sm:px-6 lg:px-8">
                <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <!-- Tasks grouped by category -->
                    <div v-if="Object.keys(tasksGrouped).length > 0">
                        <div v-for="(tasks, categoryName) in tasksGrouped" :key="categoryName" class="border-b border-gray-200 dark:border-gray-700">
                            <div class="bg-gray-50 dark:bg-gray-900 px-6 py-3 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <div
                                        v-if="getCategoryColor(categoryName)"
                                        class="h-3 w-3 rounded-full"
                                        :style="{ backgroundColor: getCategoryColor(categoryName) }"
                                    ></div>
                                    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        {{ categoryName }}
                                    </h3>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        ({{ tasks.length }})
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Tasks Table -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-900">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                ID
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Title
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Tags
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Assignee
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr
                                            v-for="task in tasks"
                                            :key="task.id"
                                            class="hover:bg-gray-50 dark:hover:bg-gray-900 cursor-pointer"
                                            @click="openEditModal(task)"
                                        >
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                #{{ task.id }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ task.title }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div v-if="task.tags && task.tags.length > 0" class="flex flex-wrap gap-1">
                                                    <span
                                                        v-for="tag in task.tags"
                                                        :key="tag.id"
                                                        class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                                                        :style="{ backgroundColor: tag.color + '20', color: tag.color }"
                                                    >
                                                        {{ tag.name }}
                                                    </span>
                                                </div>
                                                <span v-else class="text-sm text-gray-400 dark:text-gray-500">—</span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div v-if="task.assignee" class="flex items-center">
                                                    <img 
                                                        v-if="task.assignee.avatar" 
                                                        :src="task.assignee.avatar" 
                                                        :alt="task.assignee.name" 
                                                        class="h-8 w-8 rounded-full"
                                                    >
                                                    <div 
                                                        v-else 
                                                        class="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-sm font-medium text-indigo-600 dark:text-indigo-400"
                                                    >
                                                        {{ task.assignee.name.charAt(0).toUpperCase() }}
                                                    </div>
                                                    <span class="ml-3 text-sm text-gray-900 dark:text-gray-100">
                                                        {{ task.assignee.name }}
                                                    </span>
                                                </div>
                                                <span v-else class="text-sm text-gray-400 dark:text-gray-500">Unassigned</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Empty state -->
                    <div v-else class="px-6 py-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">No tasks</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Get started by creating a new task.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Create/Edit Modal -->
        <TaskModal
            v-if="showModal"
            :show="showModal"
            :task="selectedTask"
            :project="project"
            :categories="categories"
            :tags="tags"
            :users="users"
            @close="closeModal"
            @saved="handleTaskSaved"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TaskModal from './TaskModal.vue';

const props = defineProps({
    project: Object,
    tasksGrouped: Object,
    categories: Array,
    tags: Array,
    users: Array,
});

const showModal = ref(false);
const selectedTask = ref(null);

const openCreateModal = () => {
    selectedTask.value = null;
    showModal.value = true;
};

const openEditModal = (task) => {
    selectedTask.value = task;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedTask.value = null;
};

const handleTaskSaved = () => {
    closeModal();
    // Reload page to get updated data
    window.location.reload();
};

const getCategoryColor = (categoryName) => {
    const category = props.categories.find(c => c.name === categoryName);
    return category?.color || null;
};

const getStatusClass = (status) => {
    const classes = {
        'Todo': 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-500/20',
        'Analysis': 'bg-blue-50 text-blue-700 ring-blue-700/10 dark:bg-blue-900/30 dark:text-blue-400 dark:ring-blue-400/30',
        'Ready': 'bg-purple-50 text-purple-700 ring-purple-700/10 dark:bg-purple-900/30 dark:text-purple-400 dark:ring-purple-400/30',
        'Progress': 'bg-yellow-50 text-yellow-800 ring-yellow-600/20 dark:bg-yellow-900/30 dark:text-yellow-400 dark:ring-yellow-400/30',
        'Review': 'bg-orange-50 text-orange-700 ring-orange-700/10 dark:bg-orange-900/30 dark:text-orange-400 dark:ring-orange-400/30',
        'QA': 'bg-indigo-50 text-indigo-700 ring-indigo-700/10 dark:bg-indigo-900/30 dark:text-indigo-400 dark:ring-indigo-400/30',
        'Completed': 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-400/30',
    };
    return classes[status] || classes['Todo'];
};

const formatEstimation = (hours) => {
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);
    
    if (h > 0 && m > 0) {
        return `${h}h ${m}m`;
    } else if (h > 0) {
        return `${h}h`;
    } else if (m > 0) {
        return `${m}m`;
    }
    return '';
};
</script>
