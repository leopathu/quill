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
                    <div class="relative w-full max-w-4xl bg-white dark:bg-gray-800 rounded-lg shadow-xl">
                        <form @submit.prevent="submit">
                            <!-- Header -->
                            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {{ task ? 'Edit Task' : 'Create Task' }}
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
                            <div class="max-h-[calc(100vh-250px)] overflow-y-auto px-6 py-4 space-y-4">
                                <!-- Title -->
                                <div>
                                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Title <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.title"
                                        type="text"
                                        id="title"
                                        required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    />
                                    <p v-if="form.errors.title" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.title }}
                                    </p>
                                </div>

                                <!-- Description -->
                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Description
                                    </label>
                                    <div class="mt-1">
                                        <Ckeditor
                                            v-model="form.description"
                                            :editor="editor"
                                            :config="editorConfig"
                                        ></Ckeditor>
                                    </div>
                                    <p v-if="form.errors.description" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.description }}
                                    </p>
                                </div>

                                <!-- Status and Category Row -->
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Status -->
                                    <div>
                                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Status <span class="text-red-500">*</span>
                                        </label>
                                        <select
                                            v-model="form.status"
                                            id="status"
                                            required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                        >
                                            <option value="Todo">Todo</option>
                                            <option value="Analysis">Analysis</option>
                                            <option value="Ready">Ready</option>
                                            <option value="Progress">Progress</option>
                                            <option value="Review">Review</option>
                                            <option value="QA">QA</option>
                                            <option value="Completed">Completed</option>
                                        </select>
                                        <p v-if="form.errors.status" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                            {{ form.errors.status }}
                                        </p>
                                    </div>

                                    <!-- Category -->
                                    <div>
                                        <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Category <span class="text-red-500">*</span>
                                        </label>
                                        <input
                                            v-model="form.category"
                                            type="text"
                                            id="category"
                                            list="categories-list"
                                            required
                                            placeholder="Select or type to create new"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                        />
                                        <datalist id="categories-list">
                                            <option v-for="category in categories" :key="category.id" :value="category.name"></option>
                                        </datalist>
                                        <p v-if="form.errors.category" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                            {{ form.errors.category }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Tags -->
                                <div>
                                    <label for="tags" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Tags
                                    </label>
                                    <input
                                        v-model="tagsInput"
                                        type="text"
                                        id="tags"
                                        list="tags-list"
                                        placeholder="Type and press Enter to add tags"
                                        @keydown.enter.prevent="addTag"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    />
                                    <datalist id="tags-list">
                                        <option v-for="tag in tags" :key="tag.id" :value="tag.name"></option>
                                    </datalist>
                                    <div v-if="form.tags.length > 0" class="mt-2 flex flex-wrap gap-2">
                                        <span
                                            v-for="(tag, index) in form.tags"
                                            :key="index"
                                            class="inline-flex items-center gap-1 rounded-full bg-indigo-100 dark:bg-indigo-900/30 px-3 py-1 text-sm text-indigo-700 dark:text-indigo-400"
                                        >
                                            {{ tag }}
                                            <button
                                                type="button"
                                                @click="removeTag(index)"
                                                class="hover:text-indigo-900 dark:hover:text-indigo-200"
                                            >
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </span>
                                    </div>
                                    <p v-if="form.errors.tags" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.tags }}
                                    </p>
                                </div>

                                <!-- Assignee and Estimation Row -->
                                <div class="grid grid-cols-2 gap-4">
                                    <!-- Assignee -->
                                    <div>
                                        <label for="assignee" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Assignee
                                        </label>
                                        <select
                                            v-model="form.assignee_id"
                                            id="assignee"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                        >
                                            <option :value="null">Unassigned</option>
                                            <option v-for="user in users" :key="user.id" :value="user.id">
                                                {{ user.name }}
                                            </option>
                                        </select>
                                        <p v-if="form.errors.assignee_id" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                            {{ form.errors.assignee_id }}
                                        </p>
                                    </div>

                                    <!-- Estimation -->
                                    <div>
                                        <label for="estimation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Estimation
                                        </label>
                                        <input
                                            v-model="form.estimation"
                                            type="text"
                                            id="estimation"
                                            placeholder="e.g., 2h, 30m, 2h 30m"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                        />
                                        <p v-if="form.errors.estimation" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                            {{ form.errors.estimation }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Attachments -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Attachments
                                    </label>
                                    <div class="mt-1">
                                        <input
                                            ref="fileInput"
                                            type="file"
                                            multiple
                                            @change="handleFileChange"
                                            class="block w-full text-sm text-gray-500 dark:text-gray-400
                                                file:mr-4 file:py-2 file:px-4
                                                file:rounded-md file:border-0
                                                file:text-sm file:font-semibold
                                                file:bg-indigo-50 file:text-indigo-700
                                                hover:file:bg-indigo-100
                                                dark:file:bg-indigo-900/30 dark:file:text-indigo-400
                                                dark:hover:file:bg-indigo-900/50"
                                        />
                                    </div>
                                    
                                    <!-- Existing attachments -->
                                    <div v-if="task && task.attachments && task.attachments.length > 0" class="mt-2">
                                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Existing files:</p>
                                        <div class="space-y-1">
                                            <div
                                                v-for="(attachment, index) in task.attachments"
                                                :key="index"
                                                class="flex items-center justify-between rounded bg-gray-50 dark:bg-gray-700 px-2 py-1 text-xs"
                                            >
                                                <div class="flex items-center gap-2">
                                                    <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                                                    </svg>
                                                    <span class="text-gray-700 dark:text-gray-300">{{ attachment.name }}</span>
                                                    <span class="text-gray-500 dark:text-gray-400">({{ formatFileSize(attachment.size) }})</span>
                                                </div>
                                                <button
                                                    type="button"
                                                    @click="removeExistingAttachment(index)"
                                                    class="text-red-500 hover:text-red-700 dark:hover:text-red-400"
                                                >
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- New attachments preview -->
                                    <div v-if="form.attachments.length > 0" class="mt-2">
                                        <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">New files:</p>
                                        <div class="space-y-1">
                                            <div
                                                v-for="(file, index) in form.attachments"
                                                :key="index"
                                                class="flex items-center justify-between rounded bg-indigo-50 dark:bg-indigo-900/30 px-2 py-1 text-xs"
                                            >
                                                <div class="flex items-center gap-2">
                                                    <svg class="h-4 w-4 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                                                    </svg>
                                                    <span class="text-gray-700 dark:text-gray-300">{{ file.name }}</span>
                                                    <span class="text-gray-500 dark:text-gray-400">({{ formatFileSize(file.size) }})</span>
                                                </div>
                                                <button
                                                    type="button"
                                                    @click="removeNewAttachment(index)"
                                                    class="text-red-500 hover:text-red-700 dark:hover:text-red-400"
                                                >
                                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <p v-if="form.errors.attachments" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                        {{ form.errors.attachments }}
                                    </p>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                                <button
                                    v-if="task"
                                    type="button"
                                    @click="deleteTask"
                                    :disabled="form.processing"
                                    class="rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Delete
                                </button>
                                <div v-else></div>
                                
                                <div class="flex gap-3">
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
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { Ckeditor } from '@ckeditor/ckeditor5-vue';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';

const props = defineProps({
    show: Boolean,
    task: Object,
    project: Object,
    categories: Array,
    tags: Array,
    users: Array,
});

const emit = defineEmits(['close', 'saved']);

const editor = ClassicEditor;
const editorConfig = {
    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo'],
};

const fileInput = ref(null);
const tagsInput = ref('');

const form = useForm({
    title: '',
    description: '',
    status: 'Todo',
    category: '',
    tags: [],
    assignee_id: null,
    estimation: '',
    attachments: [],
    existing_attachments: [],
});

// Initialize form when task prop changes
watch(() => props.task, (newTask) => {
    if (newTask) {
        form.title = newTask.title;
        form.description = newTask.description || '';
        form.status = newTask.status;
        form.category = newTask.category?.name || '';
        form.tags = newTask.tags?.map(t => t.name) || [];
        form.assignee_id = newTask.assignee_id;
        form.estimation = formatEstimationForInput(newTask.estimation);
        form.attachments = [];
        form.existing_attachments = newTask.attachments || [];
    } else {
        form.reset();
        form.existing_attachments = [];
    }
}, { immediate: true });

const formatEstimationForInput = (hours) => {
    if (!hours) return '';
    
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

const addTag = () => {
    const tag = tagsInput.value.trim();
    if (tag && !form.tags.includes(tag)) {
        form.tags.push(tag);
        tagsInput.value = '';
    }
};

const removeTag = (index) => {
    form.tags.splice(index, 1);
};

const handleFileChange = (event) => {
    const files = Array.from(event.target.files);
    form.attachments.push(...files);
};

const removeNewAttachment = (index) => {
    form.attachments.splice(index, 1);
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const removeExistingAttachment = (index) => {
    form.existing_attachments.splice(index, 1);
};

const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const submit = () => {
    const url = props.task
        ? route('project.tasks.update', { projectId: props.project.project_id, task: props.task.id })
        : route('project.tasks.store', { projectId: props.project.project_id });

    const method = props.task ? 'put' : 'post';

    form.transform((data) => {
        const formData = new FormData();
        
        formData.append('title', data.title);
        formData.append('description', data.description || '');
        formData.append('status', data.status);
        formData.append('category', data.category);
        formData.append('assignee_id', data.assignee_id || '');
        formData.append('estimation', data.estimation || '');
        
        data.tags.forEach((tag, index) => {
            formData.append(`tags[${index}]`, tag);
        });
        
        data.attachments.forEach((file, index) => {
            formData.append(`attachments[${index}]`, file);
        });
        
        if (props.task) {
            data.existing_attachments.forEach((attachment, index) => {
                formData.append(`existing_attachments[${index}][name]`, attachment.name);
                formData.append(`existing_attachments[${index}][path]`, attachment.path);
                formData.append(`existing_attachments[${index}][size]`, attachment.size);
                formData.append(`existing_attachments[${index}][type]`, attachment.type);
            });
        }
        
        if (method === 'put') {
            formData.append('_method', 'PUT');
        }
        
        return formData;
    })[method](url, {
        preserveScroll: true,
        onSuccess: () => {
            emit('saved');
        },
    });
};

const deleteTask = () => {
    if (!confirm('Are you sure you want to delete this task?')) {
        return;
    }

    router.delete(route('project.tasks.destroy', { 
        projectId: props.project.project_id, 
        task: props.task.id 
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

<style>
.ck-editor__editable {
    min-height: 200px;
}

.dark .ck-editor__editable {
    background-color: rgb(55 65 81);
    color: rgb(243 244 246);
}
</style>
