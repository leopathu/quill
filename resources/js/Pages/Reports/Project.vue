<template>
    <AuthenticatedLayout :project="project">
        <Head :title="`${project.name} — Time Reports`" />

        <div class="space-y-6">
            <!-- Page header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Time Reports</h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ project.name }}</p>
                </div>
                <button
                    type="button"
                    @click="showLogModal = true"
                    class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Log Time
                </button>
            </div>

            <!-- Filters -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <form @submit.prevent="applyFilters" class="flex flex-wrap gap-3 items-end">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">From</label>
                        <input v-model="filterForm.date_from" type="date" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">To</label>
                        <input v-model="filterForm.date_to" type="date" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Member</label>
                        <select v-model="filterForm.user_id" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm">
                            <option value="">All Members</option>
                            <option v-for="u in members" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>
                    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Apply</button>
                    <button type="button" @click="resetFilters" class="rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50">Reset</button>
                </form>
            </div>

            <!-- Summary cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Total Time</div>
                    <div class="mt-2 text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ formatMinutes(computedTotal) }}</div>
                    <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">{{ filteredLogs.length }} log entries</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Tasks Tracked</div>
                    <div class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">{{ byTask.length }}</div>
                    <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">with logged time</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                    <div class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Contributors</div>
                    <div class="mt-2 text-3xl font-bold text-purple-600 dark:text-purple-400">{{ byUser.length }}</div>
                    <div class="mt-1 text-xs text-gray-400 dark:text-gray-500">team members</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- By Task -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Time by Task</h2>
                    </div>
                    <div class="p-5 space-y-3">
                        <div v-if="byTask.length === 0" class="text-sm text-gray-400 dark:text-gray-500 italic text-center py-4">No data for selected period.</div>
                        <div v-for="item in byTask" :key="item.task_id">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm text-gray-700 dark:text-gray-300 truncate max-w-[70%]">{{ item.task_title }}</span>
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap ml-2">{{ formatMinutes(item.total_minutes) }}</span>
                            </div>
                            <div class="h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                <div
                                    class="h-full bg-indigo-500 rounded-full"
                                    :style="{ width: maxTaskMinutes ? (item.total_minutes / maxTaskMinutes * 100) + '%' : '0%' }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- By User -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-white">Time by Member</h2>
                    </div>
                    <div class="p-5 space-y-3">
                        <div v-if="byUser.length === 0" class="text-sm text-gray-400 dark:text-gray-500 italic text-center py-4">No data for selected period.</div>
                        <div v-for="item in byUser" :key="item.user_id" class="flex items-center gap-3">
                            <img v-if="item.user_avatar" :src="item.user_avatar" :alt="item.user_name" class="h-7 w-7 rounded-full object-cover flex-shrink-0" />
                            <div v-else class="h-7 w-7 rounded-full bg-indigo-500 flex items-center justify-center text-white text-xs font-semibold flex-shrink-0">
                                {{ item.user_name?.charAt(0).toUpperCase() }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ item.user_name }}</span>
                                    <span class="ml-2 text-sm font-semibold text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ formatMinutes(item.total_minutes) }}</span>
                                </div>
                                <div class="h-1.5 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                                    <div
                                        class="h-full bg-purple-500 rounded-full"
                                        :style="{ width: maxUserMinutes ? (item.total_minutes / maxUserMinutes * 100) + '%' : '0%' }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed log table -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">All Time Logs</h2>
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ filteredLogs.length }} entries</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-700/50">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Member</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Task</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Time</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Comment</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-if="filteredLogs.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500 italic">No time logs found.</td>
                            </tr>
                            <tr v-for="log in filteredLogs" :key="log.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ formatDate(log.date) }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <img v-if="log.user_avatar" :src="log.user_avatar" :alt="log.user_name" class="h-5 w-5 rounded-full object-cover" />
                                        <div v-else class="h-5 w-5 rounded-full bg-indigo-500 flex items-center justify-center text-white text-[9px] font-semibold">
                                            {{ log.user_name?.charAt(0).toUpperCase() }}
                                        </div>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ log.user_name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300 max-w-[200px] truncate">{{ log.task_title }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-green-100 dark:bg-green-900/30 px-2 py-0.5 text-xs font-semibold text-green-700 dark:text-green-400">
                                        {{ formatMinutes(log.time_spent) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 max-w-[220px] truncate">{{ log.comment ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Log Time Modal -->
        <Teleport to="body">
            <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showLogModal" class="fixed inset-0 z-50 overflow-y-auto">
                    <div class="flex min-h-screen items-center justify-center p-4">
                        <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/75" @click="showLogModal = false"></div>
                        <div class="relative w-full max-w-md bg-white dark:bg-gray-800 rounded-lg shadow-xl p-6 space-y-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Log Time</h3>

                            <!-- Task -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Task <span class="text-red-500">*</span></label>
                                <select v-model="logForm.task_id" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm">
                                    <option value="">Choose a task…</option>
                                    <option v-for="t in tasks" :key="t.id" :value="t.id">{{ t.title }}</option>
                                </select>
                                <p v-if="logErrors.task_id" class="mt-1 text-xs text-red-500">{{ logErrors.task_id }}</p>
                            </div>

                            <!-- Date -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Date <span class="text-red-500">*</span></label>
                                <input v-model="logForm.date" type="date" :max="todayStr" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm" />
                                <p v-if="logErrors.date" class="mt-1 text-xs text-red-500">{{ logErrors.date }}</p>
                            </div>

                            <!-- Time spent -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Time Spent <span class="text-red-500">*</span></label>
                                <input v-model="logForm.timeInput" type="text" placeholder="e.g. 2h 30m, 1h, 45m" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm" />
                                <p v-if="logErrors.time_spent" class="mt-1 text-xs text-red-500">{{ logErrors.time_spent }}</p>
                            </div>

                            <!-- Comment -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Comment</label>
                                <textarea v-model="logForm.comment" rows="3" placeholder="What did you work on?" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 text-sm resize-none"></textarea>
                            </div>

                            <div class="flex justify-end gap-3 pt-2">
                                <button type="button" @click="showLogModal = false" class="rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50">Cancel</button>
                                <button type="button" :disabled="logProcessing" @click="submitLog" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50">
                                    {{ logProcessing ? 'Saving…' : 'Save' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    project: Object,
    logs: Array,
    byTask: Array,
    byUser: Array,
    tasks: Array,
    members: Array,
    filters: Object,
    totalMinutes: Number,
});

const todayStr = new Date().toISOString().split('T')[0];

const filterForm = ref({
    date_from: props.filters?.date_from ?? todayStr.slice(0, 7) + '-01',
    date_to:   props.filters?.date_to   ?? todayStr,
    user_id: '',
});

const filteredLogs = computed(() => {
    let list = [...props.logs];
    if (filterForm.value.user_id) {
        list = list.filter(l => l.user_id == filterForm.value.user_id);
    }
    return list;
});

const computedTotal = computed(() => filteredLogs.value.reduce((s, l) => s + l.time_spent, 0));
const maxTaskMinutes = computed(() => Math.max(...props.byTask.map(t => t.total_minutes), 1));
const maxUserMinutes = computed(() => Math.max(...props.byUser.map(u => u.total_minutes), 1));

const applyFilters = () => {
    router.get(
        route('project.reports.project', props.project.project_id),
        { date_from: filterForm.value.date_from, date_to: filterForm.value.date_to },
        { preserveState: true, preserveScroll: true }
    );
};

const resetFilters = () => {
    filterForm.value = { date_from: todayStr.slice(0, 7) + '-01', date_to: todayStr, user_id: '' };
    applyFilters();
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
    return new Date(dateStr).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
};

// --- Log time modal ---
const showLogModal = ref(false);
const logProcessing = ref(false);
const logErrors = ref({});
const logForm = ref({ task_id: '', date: todayStr, timeInput: '', comment: '' });

const parseTimeInput = (input) => {
    if (!input?.trim()) return null;
    const str = input.trim().toLowerCase();
    if (/^\d+$/.test(str)) return parseInt(str, 10);
    let total = 0;
    const hMatch = str.match(/(\d+)\s*h/);
    const mMatch = str.match(/(\d+)\s*m/);
    if (hMatch) total += parseInt(hMatch[1], 10) * 60;
    if (mMatch) total += parseInt(mMatch[1], 10);
    return total > 0 ? total : null;
};

const submitLog = () => {
    logErrors.value = {};
    const minutes = parseTimeInput(logForm.value.timeInput);

    if (!logForm.value.task_id)  { logErrors.value.task_id = 'Please select a task.'; return; }
    if (!logForm.value.date)     { logErrors.value.date = 'Date is required.'; return; }
    if (!minutes || minutes < 1) { logErrors.value.time_spent = 'Enter valid time (e.g. 2h 30m, 45m).'; return; }
    if (minutes > 1440)          { logErrors.value.time_spent = 'Max 24h per entry.'; return; }

    logProcessing.value = true;
    router.post(
        route('project.tasks.time-logs.store', { projectId: props.project.project_id, task: logForm.value.task_id }),
        { date: logForm.value.date, time_spent: minutes, comment: logForm.value.comment || null },
        {
            preserveScroll: true,
            onSuccess: () => {
                showLogModal.value = false;
                logForm.value = { task_id: '', date: todayStr, timeInput: '', comment: '' };
            },
            onFinish: () => { logProcessing.value = false; },
        }
    );
};
</script>
