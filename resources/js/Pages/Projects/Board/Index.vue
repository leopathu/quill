<template>
    <AuthenticatedLayout :project="project">
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                    Board
                </h2>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-full px-4 sm:px-6 lg:px-8">
                <!-- Board Columns -->
                <div class="flex gap-4 overflow-x-auto pb-4">

                    <div
                        v-for="status in statusGroups"
                        :key="status"
                        class="flex-shrink-0 w-80"
                    >
                        <!-- Column Header -->
                        <div class="mb-3">
                            <div class="flex items-center justify-between px-3 py-2 bg-gray-100 dark:bg-gray-800 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-2 w-2 rounded-full"
                                        :class="getStatusColor(status)"
                                    ></div>
                                    <h3 class="font-semibold text-sm text-gray-900 dark:text-gray-100">
                                        {{ status }}
                                    </h3>
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400 font-medium">
                                    {{ tasksByStatus[status]?.length || 0 }}
                                </span>
                            </div>
                            <!-- Add Task button for Todo column -->
                            <button
                                v-if="status === 'Todo'"
                                @click="openNewTaskModal"
                                class="mt-2 w-full flex items-center justify-center gap-2 rounded bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 transition-colors shadow focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2"
                                type="button"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                Add Task
                            </button>
                        </div>

                        <!-- Task Cards -->
                        <div
                            class="space-y-3 min-h-[200px] bg-gray-50 dark:bg-gray-900/50 rounded-lg p-3"
                            @drop="onDrop($event, status)"
                            @dragover.prevent
                            @dragenter.prevent
                        >
                            <div
                                v-for="task in tasksByStatus[status]"
                                :key="task.id"
                                draggable="true"
                                @dragstart="onDragStart($event, task)"
                                @click="openTaskModal(task)"
                                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 cursor-pointer hover:shadow-md transition-shadow"
                            >
                                <!-- Task ID -->
                                <div class="flex items-start justify-between mb-2">
                                    <span class="text-xs font-mono text-gray-500 dark:text-gray-400">
                                        {{ task.task_id }}
                                    </span>
                                </div>

                                <!-- Task Title -->
                                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-3 line-clamp-2">
                                    {{ task.title }}
                                </h4>

                                <!-- Task Meta -->
                                <div class="flex items-center justify-between gap-2">
                                    <!-- Category -->
                                    <div v-if="task.category" class="flex items-center gap-1.5">
                                        <div
                                            class="h-2 w-2 rounded-full flex-shrink-0"
                                            :style="{ backgroundColor: task.category.color }"
                                        ></div>
                                        <span class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                            {{ task.category.name }}
                                        </span>
                                    </div>
                                    <div v-else class="flex-1"></div>

                                    <!-- Assignee -->
                                    <div v-if="task.assignee" class="flex-shrink-0">
                                        <img
                                            v-if="task.assignee.avatar"
                                            :src="task.assignee.avatar"
                                            :alt="task.assignee.name"
                                            :title="task.assignee.name"
                                            class="h-6 w-6 rounded-full object-cover"
                                        />
                                        <div
                                            v-else
                                            :title="task.assignee.name"
                                            class="h-6 w-6 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-semibold"
                                        >
                                            {{ task.assignee.name.charAt(0).toUpperCase() }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Empty State -->
                            <div
                                v-if="!tasksByStatus[status] || tasksByStatus[status].length === 0"
                                class="flex items-center justify-center h-32 text-gray-400 dark:text-gray-600 text-sm"
                            >
                                No tasks
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Modal -->
        <TaskModal
            :show="showTaskModal"
            :task="selectedTask"
            :project="project"
            :categories="categories"
            :tags="tags"
            :users="users"
            @close="showTaskModal = false"
            @saved="showTaskModal = false"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TaskModal from '../Tasks/TaskModal.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    project: Object,
    tasksByStatus: Object,
    statusGroups: Array,
    categories: Array,
    tags: Array,
    users: Array,
});

const draggedTask = ref(null);
const showTaskModal = ref(false);
const selectedTask = ref(null);

const getStatusColor = (status) => {
    const colors = {
        'Todo': 'bg-gray-400',
        'Analysis': 'bg-yellow-400',
        'Ready': 'bg-blue-400',
        'Progress': 'bg-purple-400',
        'Review': 'bg-orange-400',
        'QA': 'bg-pink-400',
        'Completed': 'bg-green-400',
    };
    return colors[status] || 'bg-gray-400';
};

const onDragStart = (event, task) => {
    draggedTask.value = task;
    event.dataTransfer.effectAllowed = 'move';
};


const openTaskModal = (task) => {
    selectedTask.value = task;
    showTaskModal.value = true;
};

const openNewTaskModal = () => {
    // Open modal in create mode, status pre-set to 'Todo'
    selectedTask.value = null;
    showTaskModal.value = true;
};

const onDrop = (event, newStatus) => {
    event.preventDefault();
    
    if (!draggedTask.value || draggedTask.value.status === newStatus) {
        draggedTask.value = null;
        return;
    }

    // Update task status
    router.put(
        route('project.board.updateStatus', [props.project.project_id, draggedTask.value.id]),
        { status: newStatus },
        {
            preserveScroll: true,
            onSuccess: () => {
                draggedTask.value = null;
            },
            onError: () => {
                draggedTask.value = null;
            },
        }
    );
};
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-clamp: 2;
}

/* Stylish scrollbars for board columns (horizontal and vertical) */
.flex.gap-4.overflow-x-auto.pb-4 {
    scrollbar-width: thin;
    scrollbar-color: #6366f1 #e5e7eb; /* indigo-500 on gray-100 */
}
.flex.gap-4.overflow-x-auto.pb-4::-webkit-scrollbar {
    height: 10px;
    width: 10px;
    background: #e5e7eb;
    border-radius: 8px;
}
.flex.gap-4.overflow-x-auto.pb-4::-webkit-scrollbar-thumb {
    background: #6366f1;
    border-radius: 8px;
}
.flex.gap-4.overflow-x-auto.pb-4::-webkit-scrollbar-thumb:hover {
    background: #4f46e5;
}
.flex.gap-4.overflow-x-auto.pb-4::-webkit-scrollbar-corner {
    background: #e5e7eb;
}

/* Vertical scroll for each column if needed */
.min-h-\[200px\] {
    scrollbar-width: thin;
    scrollbar-color: #6366f1 #f3f4f6;
}
.min-h-\[200px\]::-webkit-scrollbar {
    width: 8px;
    background: #f3f4f6;
    border-radius: 8px;
}
.min-h-\[200px\]::-webkit-scrollbar-thumb {
    background: #6366f1;
    border-radius: 8px;
}
.min-h-\[200px\]::-webkit-scrollbar-thumb:hover {
    background: #4f46e5;
}
.min-h-\[200px\]::-webkit-scrollbar-corner {
    background: #f3f4f6;
}
</style>
