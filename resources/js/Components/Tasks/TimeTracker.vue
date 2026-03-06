<template>
    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                Time Tracking
                <span class="ml-1 text-xs font-normal text-gray-500 dark:text-gray-400">
                    ({{ formatMinutes(totalLogged) }} logged)
                </span>
            </h4>
            <button
                type="button"
                @click="showForm = !showForm"
                class="inline-flex items-center gap-1 rounded-md bg-indigo-50 dark:bg-indigo-900/30 px-2 py-1 text-xs font-semibold text-indigo-700 dark:text-indigo-400 hover:bg-indigo-100 dark:hover:bg-indigo-900/50"
            >
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Log Time
            </button>
        </div>

        <!-- Log time form -->
        <Transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
        >
            <div v-if="showForm" class="mb-4 rounded-lg bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 p-3 space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Date <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.date"
                            type="date"
                            :max="today"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Time Spent <span class="text-red-500">*</span></label>
                        <input
                            v-model="form.timeInput"
                            type="text"
                            placeholder="e.g. 2h 30m, 1h, 45m"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm"
                        />
                        <p v-if="timeError" class="mt-0.5 text-xs text-red-500">{{ timeError }}</p>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Comment</label>
                    <textarea
                        v-model="form.comment"
                        rows="2"
                        placeholder="What did you work on?"
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm resize-none"
                    ></textarea>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button
                        type="button"
                        @click="cancelForm"
                        class="rounded-md bg-white dark:bg-gray-700 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="processing"
                        @click="submitLog"
                        class="rounded-md bg-indigo-600 px-2.5 py-1 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ processing ? 'Saving...' : (editingLog ? 'Update' : 'Save') }}
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Time logs list -->
        <div v-if="localLogs.length > 0" class="space-y-2">
            <div
                v-for="log in localLogs"
                :key="log.id"
                class="flex items-start gap-2 rounded-lg bg-white dark:bg-gray-700/30 border border-gray-100 dark:border-gray-600/50 px-3 py-2"
            >
                <div class="flex-shrink-0 mt-0.5">
                    <img
                        v-if="log.user?.avatar"
                        :src="log.user.avatar"
                        :alt="log.user?.name"
                        class="h-6 w-6 rounded-full object-cover"
                    />
                    <div
                        v-else
                        class="h-6 w-6 rounded-full bg-indigo-500 flex items-center justify-center text-white text-[10px] font-semibold"
                    >
                        {{ log.user?.name?.charAt(0).toUpperCase() }}
                    </div>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">{{ log.user?.name }}</span>
                        <span class="inline-flex items-center gap-1 rounded-full bg-green-100 dark:bg-green-900/30 px-2 py-0.5 text-xs font-semibold text-green-700 dark:text-green-400">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ formatMinutes(log.time_spent) }}
                        </span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ formatDate(log.date) }}</span>
                    </div>
                    <p v-if="log.comment" class="mt-0.5 text-xs text-gray-600 dark:text-gray-400 break-words">
                        {{ log.comment }}
                    </p>
                </div>

                <div v-if="canEditLog(log)" class="flex-shrink-0 flex items-center gap-1">
                    <button
                        type="button"
                        @click="startEdit(log)"
                        class="text-gray-400 hover:text-indigo-500 dark:hover:text-indigo-400 p-0.5"
                        title="Edit"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click="deleteLog(log)"
                        class="text-gray-400 hover:text-red-500 dark:hover:text-red-400 p-0.5"
                        title="Delete"
                    >
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <p v-else-if="!showForm" class="text-xs text-gray-400 dark:text-gray-500 italic">
            No time logged yet.
        </p>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    task: Object,
    project: Object,
    authUser: Object,
});

const showForm = ref(false);
const processing = ref(false);
const editingLog = ref(null);
const timeError = ref('');
const localLogs = ref([]);

const today = new Date().toISOString().split('T')[0];

const form = ref({
    date: today,
    timeInput: '',
    comment: '',
});

watch(() => props.task?.timeLogs, (val) => {
    localLogs.value = val ? [...val] : [];
}, { immediate: true, deep: true });

const totalLogged = computed(() => localLogs.value.reduce((sum, l) => sum + (l.time_spent ?? 0), 0));

const parseTimeInput = (input) => {
    if (!input || !input.trim()) return null;
    const str = input.trim().toLowerCase();
    if (/^\d+$/.test(str)) return parseInt(str, 10);
    let total = 0;
    const hMatch = str.match(/(\d+)\s*h/);
    const mMatch = str.match(/(\d+)\s*m/);
    if (hMatch) total += parseInt(hMatch[1], 10) * 60;
    if (mMatch) total += parseInt(mMatch[1], 10);
    return total > 0 ? total : null;
};

const formatMinutes = (mins) => {
    if (!mins) return '0m';
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    if (h > 0 && m > 0) return `${h}h ${m}m`;
    if (h > 0) return `${h}h`;
    return `${m}m`;
};

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
};

const canEditLog = (log) => {
    return log.user_id === props.authUser?.id || props.project?.user_id === props.authUser?.id;
};

const cancelForm = () => {
    showForm.value = false;
    editingLog.value = null;
    timeError.value = '';
    form.value = { date: today, timeInput: '', comment: '' };
};

const startEdit = (log) => {
    editingLog.value = log;
    form.value = {
        date: log.date,
        timeInput: formatMinutes(log.time_spent),
        comment: log.comment ?? '',
    };
    showForm.value = true;
};

const submitLog = () => {
    timeError.value = '';
    const minutes = parseTimeInput(form.value.timeInput);

    if (!form.value.date) { timeError.value = 'Date is required.'; return; }
    if (!minutes || minutes < 1) { timeError.value = 'Enter valid time (e.g. 2h 30m, 45m).'; return; }
    if (minutes > 1440) { timeError.value = 'Max 24h (1440m) per log.'; return; }

    processing.value = true;

    const payload = {
        date: form.value.date,
        time_spent: minutes,
        comment: form.value.comment || null,
    };

    if (editingLog.value) {
        const idx = localLogs.value.findIndex(l => l.id === editingLog.value.id);
        if (idx !== -1) {
            localLogs.value[idx] = { ...localLogs.value[idx], ...payload };
        }
        router.put(
            route('project.tasks.time-logs.update', {
                projectId: props.project.project_id,
                task: props.task.id,
                timeLog: editingLog.value.id,
            }),
            payload,
            { preserveScroll: true, onFinish: () => { processing.value = false; cancelForm(); } }
        );
    } else {
        const optimistic = {
            id: Date.now(),
            ...payload,
            user_id: props.authUser?.id,
            user: { id: props.authUser?.id, name: props.authUser?.name, avatar: props.authUser?.avatar },
            _optimistic: true,
        };
        localLogs.value.unshift(optimistic);
        router.post(
            route('project.tasks.time-logs.store', {
                projectId: props.project.project_id,
                task: props.task.id,
            }),
            payload,
            { preserveScroll: true, onFinish: () => { processing.value = false; cancelForm(); } }
        );
    }
};

const deleteLog = (log) => {
    if (!confirm('Delete this time log?')) return;
    localLogs.value = localLogs.value.filter(l => l.id !== log.id);
    router.delete(
        route('project.tasks.time-logs.destroy', {
            projectId: props.project.project_id,
            task: props.task.id,
            timeLog: log.id,
        }),
        { preserveScroll: true }
    );
};
</script>
