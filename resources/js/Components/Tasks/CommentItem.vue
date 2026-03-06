<template>
    <div class="flex gap-3">
        <!-- Avatar -->
        <div class="flex-shrink-0">
            <img
                v-if="comment.user?.avatar"
                :src="comment.user.avatar"
                :alt="comment.user.name"
                class="h-7 w-7 rounded-full object-cover"
            />
            <div
                v-else
                class="h-7 w-7 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-semibold"
            >
                {{ comment.user?.name?.charAt(0).toUpperCase() }}
            </div>
        </div>

        <!-- Content -->
        <div class="flex-1 min-w-0">
            <!-- Bubble -->
            <div class="rounded-lg bg-gray-100 dark:bg-gray-700/60 px-3 py-2">
                <div class="flex items-center justify-between gap-2 mb-0.5">
                    <span class="text-xs font-semibold text-gray-900 dark:text-gray-100">
                        {{ comment.user?.name }}
                    </span>
                    <span class="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap">
                        {{ formatDate(comment.created_at) }}
                    </span>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap break-words">{{ comment.body }}</p>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 mt-1 px-1">
                <button
                    type="button"
                    class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-medium"
                    @click="toggleReply"
                >
                    Reply
                </button>
                <button
                    v-if="canDelete"
                    type="button"
                    class="text-xs text-red-500 hover:underline"
                    @click="deleteComment"
                >
                    Delete
                </button>
            </div>

            <!-- Reply form -->
            <div v-if="showReplyForm" class="mt-2 flex gap-2">
                <div class="flex-shrink-0">
                    <img
                        v-if="authUser?.avatar"
                        :src="authUser.avatar"
                        :alt="authUser.name"
                        class="h-6 w-6 rounded-full object-cover"
                    />
                    <div
                        v-else
                        class="h-6 w-6 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-semibold"
                    >
                        {{ authUser?.name?.charAt(0).toUpperCase() }}
                    </div>
                </div>
                <div class="flex-1">
                    <textarea
                        v-model="replyText"
                        rows="2"
                        :placeholder="`Reply to ${comment.user?.name}...`"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm resize-none"
                        @keydown.ctrl.enter.prevent="submitReply"
                        ref="replyInput"
                    ></textarea>
                    <div class="mt-1 flex items-center justify-end gap-2">
                        <button
                            type="button"
                            @click="showReplyForm = false; replyText = ''"
                            class="text-xs text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            :disabled="!replyText.trim() || replyProcessing"
                            @click="submitReply"
                            class="rounded-md bg-indigo-600 px-2 py-0.5 text-xs font-semibold text-white hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ replyProcessing ? 'Posting...' : 'Reply' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Nested replies -->
            <div v-if="localReplies.length" class="mt-3 space-y-3 pl-2 border-l-2 border-gray-200 dark:border-gray-600">
                <CommentItem
                    v-for="reply in localReplies"
                    :key="reply.id"
                    :comment="reply"
                    :task="task"
                    :project="project"
                    :auth-user="authUser"
                    @reply-posted="$emit('reply-posted')"
                    @deleted="(id) => { localReplies = localReplies.filter(r => r.id !== id); $emit('deleted', id); }"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, nextTick, reactive } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    comment: Object,
    task: Object,
    project: Object,
    authUser: Object,
});

const emit = defineEmits(['reply-posted', 'deleted']);

const showReplyForm = ref(false);
const replyText = ref('');
const replyProcessing = ref(false);
const replyInput = ref(null);

// Local copy of replies for optimistic updates
const localReplies = ref(props.comment.replies ? [...props.comment.replies] : []);

const canDelete = computed(() => {
    return props.authUser && (
        props.authUser.id === props.comment.user_id ||
        props.authUser.id === props.comment.user?.id ||
        props.authUser.id === props.project?.owner_id
    );
});

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    const now = new Date();
    const diff = Math.floor((now - d) / 1000);
    if (diff < 60) return 'just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    if (diff < 604800) return `${Math.floor(diff / 86400)}d ago`;
    return d.toLocaleDateString();
};

const toggleReply = () => {
    showReplyForm.value = !showReplyForm.value;
    if (showReplyForm.value) {
        nextTick(() => replyInput.value?.focus());
    }
};

const submitReply = () => {
    if (!replyText.value.trim()) return;
    replyProcessing.value = true;

    // Optimistically add reply immediately
    const optimistic = {
        id: Date.now(),
        body: replyText.value.trim(),
        parent_id: props.comment.id,
        created_at: new Date().toISOString(),
        user: { id: props.authUser?.id, name: props.authUser?.name, avatar: props.authUser?.avatar },
        replies: [],
        _optimistic: true,
    };
    localReplies.value.push(optimistic);

    const body = replyText.value.trim();
    replyText.value = '';
    showReplyForm.value = false;

    router.post(
        route('project.tasks.comments.store', {
            projectId: props.project.project_id,
            task: props.task.id,
        }),
        { body, parent_id: props.comment.id },
        {
            preserveScroll: true,
            onSuccess: () => {
                emit('reply-posted');
            },
            onFinish: () => { replyProcessing.value = false; },
        }
    );
};

const deleteComment = () => {
    if (!confirm('Delete this comment?')) return;
    router.delete(
        route('project.tasks.comments.destroy', {
            projectId: props.project.project_id,
            task: props.task.id,
            comment: props.comment.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => emit('deleted', props.comment.id),
        }
    );
};
</script>
