<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
                <div class="flex min-h-screen items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div
                        class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75"
                        @click="close"
                    ></div>

                    <!-- Modal -->
                    <div class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-lg shadow-xl">
                        <form @submit.prevent="submit">
                            <!-- Header -->
                            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    Edit Category
                                </h3>
                                <button
                                    type="button"
                                    @click="close"
                                    class="rounded-md text-gray-400 hover:text-gray-500 dark:hover:text-gray-300"
                                >
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Body -->
                            <div class="px-6 py-4 space-y-4">
                                <!-- Category Name -->
                                <div>
                                    <label for="category-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Category Name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        id="category-name"
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    />
                                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.name }}
                                    </p>
                                </div>

                                <!-- Status -->
                                <div>
                                    <label for="category-status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Status <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.status"
                                        id="category-status"
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                        <option value="open">Open</option>
                                        <option value="closed" :disabled="hasOpenTasks">Closed</option>
                                    </select>
                                    <p v-if="hasOpenTasks && form.status === 'open'" class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                        <svg class="inline h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                        </svg>
                                        Cannot close category with {{ openTasksCount }} open task(s). Complete all tasks first.
                                    </p>
                                    <p v-if="form.errors.status" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.status }}
                                    </p>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="flex items-center justify-end gap-3 border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                                <button
                                    type="button"
                                    @click="close"
                                    class="rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-gray-100 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{ form.processing ? 'Saving...' : 'Save' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { watch, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    category: Object,
    project: Object,
});

const emit = defineEmits(['close', 'saved']);

const form = useForm({
    name: '',
    status: 'open',
});

// Calculate open tasks count
const openTasksCount = computed(() => {
    if (!props.category) return 0;
    return (props.category.total_tasks || 0) - (props.category.completed_tasks || 0);
});

const hasOpenTasks = computed(() => openTasksCount.value > 0);

// Initialize form when category prop changes
watch(() => props.category, (newCategory) => {
    if (newCategory) {
        form.name = newCategory.name;
        form.status = newCategory.status || 'open';
    }
}, { immediate: true });

const submit = () => {
    form.put(route('project.categories.update', { 
        projectId: props.project.project_id, 
        category: props.category.id 
    }), {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
        },
    });
};

const close = () => {
    emit('close');
};
</script>
