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
                    <div class="relative w-full max-w-6xl bg-white dark:bg-gray-800 rounded-lg shadow-xl">
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

                            <!-- Body: two-column 70/30 layout -->
                            <div class="flex max-h-[calc(100vh-220px)] overflow-hidden">

                                <!-- Left column: 70% — Title + Description + Comments -->
                                <div class="flex-[7] min-w-0 overflow-y-auto px-6 py-5 space-y-4 border-r border-gray-200 dark:border-gray-700">
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
                                        <p v-if="errors.title" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                            {{ errors.title }}
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
                                        <p v-if="errors.description" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                            {{ errors.description }}
                                        </p>
                                    </div>

                                    <!-- Comments (only in edit mode) -->
                                    <div v-if="task">
                                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">
                                                Comments
                                                <span v-if="localComments.length" class="ml-1 text-xs font-normal text-gray-500 dark:text-gray-400">
                                                    ({{ localComments.length }})
                                                </span>
                                            </h4>

                                            <!-- Add comment form -->
                                            <div class="flex gap-3 mb-4">
                                                <div class="flex-shrink-0">
                                                    <img
                                                        v-if="authUser?.avatar"
                                                        :src="authUser.avatar"
                                                        :alt="authUser.name"
                                                        class="h-8 w-8 rounded-full object-cover"
                                                    />
                                                    <div
                                                        v-else
                                                        class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-semibold"
                                                    >
                                                        {{ authUser?.name?.charAt(0).toUpperCase() }}
                                                    </div>
                                                </div>
                                                <div class="flex-1">
                                                    <textarea
                                                        v-model="newComment"
                                                        rows="2"
                                                        placeholder="Write a comment..."
                                                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm resize-none"
                                                        @keydown.ctrl.enter.prevent="submitComment(null)"
                                                    ></textarea>
                                                    <div class="mt-1.5 flex items-center justify-between">
                                                        <span class="text-xs text-gray-400 dark:text-gray-500">Ctrl+Enter to submit</span>
                                                        <button
                                                            type="button"
                                                            :disabled="!newComment.trim() || commentProcessing"
                                                            @click="submitComment(null)"
                                                            class="rounded-md bg-indigo-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                                        >
                                                            {{ commentProcessing ? 'Posting...' : 'Comment' }}
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Comments list -->
                                            <div class="space-y-4">
                                                <div
                                                    v-for="comment in localComments"
                                                    :key="comment.id"
                                                >
                                                    <CommentItem
                                                        :comment="comment"
                                                        :task="task"
                                                        :project="project"
                                                        :auth-user="authUser"
                                                        @reply-posted="onCommentAction"
                                                        @deleted="onCommentAction"
                                                    />
                                                </div>
                                                <p v-if="localComments.length === 0" class="text-sm text-gray-400 dark:text-gray-500 italic">
                                                    No comments yet.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Right column: 30% — all other fields -->
                                <div class="flex-[3] min-w-0 overflow-y-auto px-5 py-5 space-y-4 bg-gray-50 dark:bg-gray-800/60 rounded-br-lg">

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
                                        <p v-if="errors.status" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                            {{ errors.status }}
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
                                        <p v-if="errors.category" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                            {{ errors.category }}
                                        </p>
                                    </div>

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
                                        <p v-if="errors.assignee_id" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                            {{ errors.assignee_id }}
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
                                        <p v-if="errors.estimation" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                            {{ errors.estimation }}
                                        </p>
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
                                            placeholder="Type and press Enter"
                                            @keydown.enter.prevent="addTag"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                        />
                                        <datalist id="tags-list">
                                            <option v-for="tag in tags" :key="tag.id" :value="tag.name"></option>
                                        </datalist>
                                        <div v-if="form.tags.length > 0" class="mt-2 flex flex-wrap gap-1.5">
                                            <span
                                                v-for="(tag, index) in form.tags"
                                                :key="index"
                                                class="inline-flex items-center gap-1 rounded-full bg-indigo-100 dark:bg-indigo-900/30 px-2.5 py-0.5 text-xs text-indigo-700 dark:text-indigo-400"
                                            >
                                                {{ tag }}
                                                <button
                                                    type="button"
                                                    @click="removeTag(index)"
                                                    class="hover:text-indigo-900 dark:hover:text-indigo-200"
                                                >
                                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </span>
                                        </div>
                                        <p v-if="errors.tags" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                            {{ errors.tags }}
                                        </p>
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
                                                    file:mr-3 file:py-1.5 file:px-3
                                                    file:rounded-md file:border-0
                                                    file:text-xs file:font-semibold
                                                    file:bg-indigo-50 file:text-indigo-700
                                                    hover:file:bg-indigo-100
                                                    dark:file:bg-indigo-900/30 dark:file:text-indigo-400
                                                    dark:hover:file:bg-indigo-900/50"
                                            />
                                        </div>

                                        <!-- Existing attachments -->
                                        <div v-if="task && task.attachments && task.attachments.length > 0" class="mt-2">
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Existing files:</p>
                                            <div class="space-y-1">
                                                <div
                                                    v-for="(attachment, index) in task.attachments"
                                                    :key="index"
                                                    class="flex items-center justify-between rounded bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 px-2 py-1 text-xs"
                                                >
                                                    <div class="flex items-center gap-1.5 min-w-0">
                                                        <svg class="h-3.5 w-3.5 shrink-0 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                                                        </svg>
                                                        <span class="truncate text-gray-700 dark:text-gray-300">{{ attachment.name }}</span>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        @click="removeExistingAttachment(index)"
                                                        class="ml-1 shrink-0 text-red-500 hover:text-red-700 dark:hover:text-red-400"
                                                    >
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- New attachments preview -->
                                        <div v-if="form.attachments.length > 0" class="mt-2">
                                            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">New files:</p>
                                            <div class="space-y-1">
                                                <div
                                                    v-for="(file, index) in form.attachments"
                                                    :key="index"
                                                    class="flex items-center justify-between rounded bg-indigo-50 dark:bg-indigo-900/30 border border-indigo-100 dark:border-indigo-800 px-2 py-1 text-xs"
                                                >
                                                    <div class="flex items-center gap-1.5 min-w-0">
                                                        <svg class="h-3.5 w-3.5 shrink-0 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13" />
                                                        </svg>
                                                        <span class="truncate text-gray-700 dark:text-gray-300">{{ file.name }}</span>
                                                    </div>
                                                    <button
                                                        type="button"
                                                        @click="removeNewAttachment(index)"
                                                        class="ml-1 shrink-0 text-red-500 hover:text-red-700 dark:hover:text-red-400"
                                                    >
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <p v-if="errors.attachments" class="mt-1 text-sm text-red-600 dark:text-red-400">
                                            {{ errors.attachments }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                                <button
                                    v-if="task"
                                    type="button"
                                    @click="deleteTask"
                                    :disabled="processing"
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
                                        :disabled="processing"
                                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        {{ processing ? 'Saving...' : 'Save' }}
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
import { ref, reactive, watch, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { Ckeditor } from '@ckeditor/ckeditor5-vue';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
import CommentItem from '@/Components/Tasks/CommentItem.vue';

const props = defineProps({
    show: Boolean,
    task: Object,
    project: Object,
    categories: Array,
    tags: Array,
    users: Array,
    defaultStatus: {
        type: String,
        default: 'Todo',
    },
});

const emit = defineEmits(['close', 'saved']);

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

const editor = ClassicEditor;
const editorConfig = {
    toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'undo', 'redo'],
};

const fileInput = ref(null);
const tagsInput = ref('');
const processing = ref(false);
const errors = ref({});
const newComment = ref('');
const commentProcessing = ref(false);
const localComments = ref([]);

// Plain reactive form state — no Inertia proxy
const form = reactive({
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

const formatEstimationForInput = (hours) => {
    if (!hours) return '';
    const h = Math.floor(hours);
    const m = Math.round((hours - h) * 60);
    if (h > 0 && m > 0) return `${h}h ${m}m`;
    if (h > 0) return `${h}h`;
    if (m > 0) return `${m}m`;
    return '';
};

const resetForm = (task) => {
    errors.value = {};
    tagsInput.value = '';

    if (task) {
        form.title         = task.title ?? '';
        form.description   = task.description ?? '';
        form.status        = task.status ?? 'Todo';
        form.category      = task.category?.name ?? '';
        form.assignee_id   = task.assignee_id ?? null;
        form.estimation    = formatEstimationForInput(task.estimation);
        form.attachments   = [];
        form.existing_attachments = Array.isArray(task.attachments) ? task.attachments.map(a => ({ ...a })) : [];

        // tags may arrive as a Proxy-wrapped array from Inertia page props
        // Convert to a plain array of name strings
        const rawTags = Array.isArray(task.tags) ? [...task.tags] : [];
        form.tags = rawTags.map(t => (typeof t === 'string' ? t : String(t.name ?? ''))).filter(Boolean);
    } else {
        form.title         = '';
        form.description   = '';
        form.status        = props.defaultStatus ?? 'Todo';
        form.category      = '';
        form.assignee_id   = null;
        form.estimation    = '';
        form.attachments   = [];
        form.existing_attachments = [];
        form.tags          = [];
    }
};

// Re-populate whenever the modal opens or the task changes
watch(() => props.show, (val) => {
    if (val) {
        resetForm(props.task);
        localComments.value = props.task?.comments ? [...props.task.comments] : [];
    }
}, { immediate: true });

watch(() => props.task, (val) => {
    if (props.show) {
        resetForm(val);
        localComments.value = val?.comments ? [...val.comments] : [];
    }
});

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
    form.attachments.push(...Array.from(event.target.files));
};

const removeNewAttachment = (index) => {
    form.attachments.splice(index, 1);
    if (fileInput.value) fileInput.value.value = '';
};

const removeExistingAttachment = (index) => {
    form.existing_attachments.splice(index, 1);
};

const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const submit = async () => {
    const isEdit = !!props.task;
    const url = isEdit
        ? route('project.tasks.update', { projectId: props.project.project_id, task: props.task.id })
        : route('project.tasks.store', { projectId: props.project.project_id });

    const fd = new FormData();
    fd.append('title',       form.title);
    fd.append('description', form.description ?? '');
    fd.append('status',      form.status);
    fd.append('category',    form.category ?? '');
    fd.append('assignee_id', form.assignee_id ?? '');
    fd.append('estimation',  form.estimation ?? '');

    form.tags.forEach((tag, i)         => fd.append(`tags[${i}]`, tag));
    form.attachments.forEach((file, i) => fd.append(`attachments[${i}]`, file));

    if (isEdit) {
        form.existing_attachments.forEach((a, i) => {
            fd.append(`existing_attachments[${i}][name]`, a.name);
            fd.append(`existing_attachments[${i}][path]`, a.path);
            fd.append(`existing_attachments[${i}][size]`, a.size);
            fd.append(`existing_attachments[${i}][type]`, a.type);
        });
        fd.append('_method', 'PUT');
    }

    processing.value = true;
    errors.value = {};

    try {
        await window.axios.post(url, fd);
        router.reload({ preserveScroll: true });
        emit('saved');
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors ?? {};
        } else {
            console.error('Submit error:', e);
        }
    } finally {
        processing.value = false;
    }
};

const deleteTask = () => {
    if (!confirm('Are you sure you want to delete this task?')) return;
    router.delete(route('project.tasks.destroy', {
        projectId: props.project.project_id,
        task: props.task.id,
    }), {
        preserveScroll: true,
        onSuccess: () => emit('saved'),
    });
};

const submitComment = (parentId) => {
    if (!newComment.value.trim()) return;
    commentProcessing.value = true;

    // Optimistically add comment to local list immediately
    const optimistic = {
        id: Date.now(), // temp id
        body: newComment.value.trim(),
        parent_id: parentId ?? null,
        created_at: new Date().toISOString(),
        user: { id: authUser.value?.id, name: authUser.value?.name, avatar: authUser.value?.avatar },
        replies: [],
        _optimistic: true,
    };

    if (!parentId) {
        localComments.value.unshift(optimistic);
    } else {
        // inject into the right parent's replies
        const addToParent = (list) => {
            for (const c of list) {
                if (c.id === parentId) { c.replies = c.replies ?? []; c.replies.push(optimistic); return true; }
                if (c.replies?.length && addToParent(c.replies)) return true;
            }
            return false;
        };
        addToParent(localComments.value);
    }

    const body = newComment.value.trim();
    newComment.value = '';

    router.post(
        route('project.tasks.comments.store', { projectId: props.project.project_id, task: props.task.id }),
        { body, parent_id: parentId ?? null },
        {
            preserveScroll: true,
            onFinish: () => { commentProcessing.value = false; },
        }
    );
};

const onCommentAction = (deletedId) => {
    if (deletedId) {
        localComments.value = localComments.value.filter(c => c.id !== deletedId);
    }
};

const close = () => emit('close');
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
