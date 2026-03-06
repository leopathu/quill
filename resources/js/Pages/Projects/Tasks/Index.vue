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
                                    <button
                                        @click.stop="openCategoryEditModal(categoryName)"
                                        class="ml-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                        title="Edit category"
                                    >
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                        </svg>
                                    </button>
                                </div>
                                
                                <!-- Progress Bar -->
                                <div class="flex items-center gap-2 max-w-md">
                                    <div class="w-64 bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                        <div
                                            class="bg-indigo-600 dark:bg-indigo-500 h-2 rounded-full transition-all duration-300"
                                            :style="{ width: getCategoryProgress(categoryName) + '%' }"
                                        ></div>
                                    </div>
                                    <span class="text-xs text-gray-600 dark:text-gray-400 whitespace-nowrap min-w-[80px]">
                                        {{ getCategoryProgress(categoryName) }}% ({{ getCategoryCompletedCount(categoryName) }}/{{ getCategoryTotalCount(categoryName) }})
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Tasks Table -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr
                                            v-for="task in tasks"
                                            :key="task.id"
                                            class="hover:bg-gray-50 dark:hover:bg-gray-900 cursor-pointer"
                                            @click="openEditModal(task)"
                                        >
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" style="width: 5%">
                                                #{{ task.id }}
                                            </td>
                                            <td class="px-6 py-4" style="width: 60%">
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ task.title }}
                                                </div>
                                            </td>
                                            <td class="px-6 py-4" style="width: 15%">
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
                                            <td class="px-6 py-4 whitespace-nowrap" style="width: 15%">
                                                <span
                                                    class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset"
                                                    :class="getStatusClass(task.status)"
                                                >
                                                    {{ task.status }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap" style="width: 5%">
                                                <div v-if="task.assignee" class="flex items-center justify-center">
                                                    <img 
                                                        v-if="task.assignee.avatar" 
                                                        :src="task.assignee.avatar" 
                                                        :alt="task.assignee.name" 
                                                        :title="task.assignee.name"
                                                        class="h-8 w-8 rounded-full"
                                                    >
                                                    <div 
                                                        v-else 
                                                        :title="task.assignee.name"
                                                        class="h-8 w-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center text-sm font-medium text-indigo-600 dark:text-indigo-400"
                                                    >
                                                        {{ task.assignee.name.charAt(0).toUpperCase() }}
                                                    </div>
                                                </div>
                                                <div v-else class="flex items-center justify-center">
                                                    <div class="h-8 w-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                                        </svg>
                                                    </div>
                                                </div>
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

        <!-- Category Edit Modal -->
        <CategoryEditModal
            v-if="showCategoryModal"
            :show="showCategoryModal"
            :category="selectedCategory"
            :project="project"
            @close="closeCategoryModal"
            @saved="handleCategorySaved"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TaskModal from '@/Components/Tasks/TaskModal.vue';
import CategoryEditModal from './CategoryEditModal.vue';

const props = defineProps({
    project: Object,
    tasksGrouped: Object,
    categories: Array,
    tags: Array,
    users: Array,
});

const showModal = ref(false);
const selectedTask = ref(null);
const showCategoryModal = ref(false);
const selectedCategory = ref(null);

const openCreateModal = () => {
    selectedTask.value = null;
    showModal.value = true;
};

const openEditModal = (task) => {
    selectedTask.value = task;
    showModal.value = true;
};

const openCategoryEditModal = (categoryName) => {
    const category = props.categories.find(c => c.name === categoryName);
    if (category) {
        selectedCategory.value = category;
        showCategoryModal.value = true;
    }
};

const closeModal = () => {
    showModal.value = false;
    selectedTask.value = null;
};

const closeCategoryModal = () => {
    showCategoryModal.value = false;
    selectedCategory.value = null;
};

const handleTaskSaved = () => {
    closeModal();
    // Reload page to get updated data
    window.location.reload();
};

const handleCategorySaved = () => {
    closeCategoryModal();
    // Reload page to get updated data
    window.location.reload();
};

const getCategoryColor = (categoryName) => {
    const category = props.categories.find(c => c.name === categoryName);
    return category?.color || null;
};

const getCategoryProgress = (categoryName) => {
    const category = props.categories.find(c => c.name === categoryName);
    return category?.completion_percentage || 0;
};

const getCategoryCompletedCount = (categoryName) => {
    const category = props.categories.find(c => c.name === categoryName);
    return category?.completed_tasks || 0;
};

const getCategoryTotalCount = (categoryName) => {
    const category = props.categories.find(c => c.name === categoryName);
    return category?.total_tasks || 0;
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
