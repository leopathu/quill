<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Doughnut, Bar, Line } from 'vue-chartjs';
import {
    Chart as ChartJS,
    ArcElement, Tooltip, Legend,
    CategoryScale, LinearScale, BarElement,
    PointElement, LineElement, Filler, Title,
} from 'chart.js';

ChartJS.register(
    ArcElement, Tooltip, Legend,
    CategoryScale, LinearScale, BarElement,
    PointElement, LineElement, Filler, Title,
);

const props = defineProps({
    period:       String,
    dateRange:    Object,
    stats:        Object,
    tasksByStatus: Object,
    projectStats: Array,
    teamStats:    Array,
    trend:        Object,
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

// ── Period filter ─────────────────────────────────────────────────────────────
const periods = [
    { value: 'this_week',     label: 'This Week' },
    { value: 'last_week',     label: 'Last Week' },
    { value: 'this_month',    label: 'This Month' },
    { value: 'last_month',    label: 'Last Month' },
    { value: 'this_quarter',  label: 'This Quarter' },
    { value: 'last_quarter',  label: 'Last Quarter' },
    { value: 'this_year',     label: 'This Year' },
    { value: 'last_year',     label: 'Last Year' },
];

function setPeriod(p) {
    router.get(route('dashboard'), { period: p }, { preserveScroll: true, replace: true });
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function initials(name) {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
}

function formatMinutes(mins) {
    if (!mins) return '0h';
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    return h > 0 ? `${h}h${m > 0 ? ' ' + m + 'm' : ''}` : `${m}m`;
}

function statusClass(status) {
    const map = {
        'Created':     'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
        'Progressing': 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
        'On Hold':     'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
        'Completed':   'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
    };
    return map[status] || 'bg-gray-100 text-gray-700';
}

// ── Status doughnut ───────────────────────────────────────────────────────────
const STATUS_COLORS = {
    'Todo':        '#d1d5db',
    'In Progress': '#f59e0b',
    'Completed':   '#22c55e',
    'On Hold':     '#ef4444',
};

const statusLegend = computed(() =>
    Object.entries(props.tasksByStatus || {}).map(([label, value]) => ({
        label, value, color: STATUS_COLORS[label] || '#6366f1',
    }))
);

const statusChartData = computed(() => ({
    labels: statusLegend.value.map(s => s.label),
    datasets: [{
        data: statusLegend.value.map(s => s.value),
        backgroundColor: statusLegend.value.map(s => s.color),
        borderWidth: 0,
        hoverOffset: 5,
    }],
}));

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '72%',
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` } } },
};

// ── Project bar chart ─────────────────────────────────────────────────────────
const projectBarData = computed(() => ({
    labels: props.projectStats.slice(0, 8).map(p => p.name),
    datasets: [
        { label: 'Completed', data: props.projectStats.slice(0, 8).map(p => p.completed),   backgroundColor: '#22c55e', borderRadius: 4 },
        { label: 'In Progress', data: props.projectStats.slice(0, 8).map(p => p.in_progress), backgroundColor: '#f59e0b', borderRadius: 4 },
        { label: 'Todo',      data: props.projectStats.slice(0, 8).map(p => p.todo),        backgroundColor: '#d1d5db', borderRadius: 4 },
    ],
}));

const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
        tooltip: { mode: 'index', intersect: false },
    },
    scales: {
        x: { stacked: true, grid: { display: false }, ticks: { font: { size: 10 }, maxRotation: 30 } },
        y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: 'rgba(156,163,175,0.15)' } },
    },
};

// ── Activity trend line ───────────────────────────────────────────────────────
const trendData = computed(() => ({
    labels: props.trend.labels,
    datasets: [
        {
            label: 'Tasks Created',
            data: props.trend.created,
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99,102,241,0.08)',
            fill: true,
            tension: 0.4,
            pointRadius: 3,
            pointHoverRadius: 5,
        },
        {
            label: 'Tasks Completed',
            data: props.trend.completed,
            borderColor: '#22c55e',
            backgroundColor: 'rgba(34,197,94,0.08)',
            fill: true,
            tension: 0.4,
            pointRadius: 3,
            pointHoverRadius: 5,
        },
    ],
}));

const lineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { mode: 'index', intersect: false },
    plugins: {
        legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
    },
    scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10 }, maxTicksLimit: 12 } },
        y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: 'rgba(156,163,175,0.15)' } },
    },
};
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>

        <!-- ── Page header ────────────────────────────────────────────────── -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                    Welcome back, {{ authUser?.name }}! 👋
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ dateRange.from }} – {{ dateRange.to }}
                </p>
            </div>

            <!-- Period filter pills -->
            <div class="flex flex-wrap gap-1.5">
                <button
                    v-for="p in periods"
                    :key="p.value"
                    @click="setPeriod(p.value)"
                    :class="[
                        'px-3 py-1.5 rounded-lg text-xs font-medium transition-colors',
                        period === p.value
                            ? 'bg-indigo-600 text-white shadow-sm'
                            : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700',
                    ]"
                >
                    {{ p.label }}
                </button>
            </div>
        </div>

        <!-- ── KPI cards ──────────────────────────────────────────────────── -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <!-- Total projects -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Projects</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_projects }}</p>
                    <p class="text-xs text-yellow-600 dark:text-yellow-400">{{ stats.active_projects }} active</p>
                </div>
            </div>

            <!-- Tasks -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Tasks</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_tasks }}</p>
                    <p class="text-xs text-indigo-500 dark:text-indigo-400">+{{ stats.tasks_in_period }} this period</p>
                </div>
            </div>

            <!-- Completed -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/40 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Completed</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.completed_all }}</p>
                    <p class="text-xs text-green-600 dark:text-green-400">{{ stats.completion_rate }}% rate</p>
                </div>
            </div>

            <!-- Time logged -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/40 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Time Logged</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatMinutes(stats.time_in_period) }}</p>
                    <p class="text-xs text-gray-400">this period</p>
                </div>
            </div>
        </div>

        <!-- ── Overall progress bar ───────────────────────────────────────── -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Overall Completion</span>
                <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ stats.completion_rate }}%</span>
            </div>
            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-3">
                <div
                    class="h-3 rounded-full bg-gradient-to-r from-indigo-500 to-indigo-600 transition-all duration-700"
                    :style="{ width: stats.completion_rate + '%' }"
                />
            </div>
            <div class="flex flex-wrap gap-6 mt-3 text-xs text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-gray-300 dark:bg-gray-600"></span>Todo: {{ stats.todo }}</span>
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-yellow-400"></span>In Progress: {{ stats.in_progress }}</span>
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-green-500"></span>Completed: {{ stats.completed_all }}</span>
            </div>
        </div>

        <!-- ── Charts row ─────────────────────────────────────────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

            <!-- Status doughnut (1/3) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4">Task Status</h2>
                <div v-if="stats.total_tasks > 0" class="flex flex-col items-center gap-4">
                    <div class="w-36 h-36">
                        <Doughnut :data="statusChartData" :options="doughnutOptions" />
                    </div>
                    <div class="w-full flex flex-col gap-1.5">
                        <div v-for="item in statusLegend" :key="item.label" class="flex items-center gap-2 text-xs">
                            <span class="w-2.5 h-2.5 rounded-sm flex-shrink-0" :style="{ backgroundColor: item.color }"></span>
                            <span class="text-gray-600 dark:text-gray-400 flex-1">{{ item.label }}</span>
                            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ item.value }}</span>
                        </div>
                    </div>
                </div>
                <div v-else class="py-10 text-center text-sm text-gray-400">No tasks yet</div>
            </div>

            <!-- Project tasks bar (2/3) -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4">Tasks by Project</h2>
                <div v-if="projectStats.length > 0" class="h-52">
                    <Bar :data="projectBarData" :options="barOptions" />
                </div>
                <div v-else class="py-10 text-center text-sm text-gray-400">No projects yet</div>
            </div>
        </div>

        <!-- ── Activity trend (full width) ───────────────────────────────── -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4">Activity Trend</h2>
            <div v-if="trend.labels.length > 0" class="h-52">
                <Line :data="trendData" :options="lineOptions" />
            </div>
            <div v-else class="py-10 text-center text-sm text-gray-400">No activity data for this period</div>
        </div>

        <!-- ── Bottom row: Project table + Team ───────────────────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Project stats table (2/3) -->
            <div class="lg:col-span-2 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Project Overview</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-900/40 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <th class="px-5 py-3 text-left">Project</th>
                                <th class="px-5 py-3 text-center">Tasks</th>
                                <th class="px-5 py-3 text-center">Done</th>
                                <th class="px-5 py-3 text-center">Time</th>
                                <th class="px-5 py-3 text-left">Progress</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr
                                v-for="p in projectStats"
                                :key="p.id"
                                class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors cursor-pointer"
                                @click="router.get(route('project.view', p.project_id))"
                            >
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <div v-if="p.logo" class="w-7 h-7 rounded-lg overflow-hidden flex-shrink-0">
                                            <img :src="`/storage/${p.logo}`" class="w-full h-full object-cover" />
                                        </div>
                                        <div v-else class="w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center flex-shrink-0">
                                            <span class="text-indigo-600 dark:text-indigo-400 font-bold" style="font-size:10px">{{ p.name.charAt(0) }}</span>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate max-w-36">{{ p.name }}</p>
                                            <span :class="statusClass(p.status)" class="text-[10px] px-1.5 py-0.5 rounded-full font-medium">{{ p.status }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-center text-sm font-semibold text-gray-700 dark:text-gray-300">{{ p.total_tasks }}</td>
                                <td class="px-5 py-3 text-center text-sm text-green-600 dark:text-green-400 font-medium">{{ p.completed }}</td>
                                <td class="px-5 py-3 text-center text-xs text-gray-500 dark:text-gray-400">{{ formatMinutes(p.time_logged) }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-2 min-w-16">
                                            <div
                                                class="h-2 rounded-full bg-indigo-500 transition-all"
                                                :style="{ width: p.completion_rate + '%' }"
                                            />
                                        </div>
                                        <span class="text-xs text-gray-500 w-7 text-right">{{ p.completion_rate }}%</span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!projectStats.length">
                                <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-400">No projects found</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Team leaderboard (1/3) -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Team Activity</h2>
                </div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    <li
                        v-for="(member, idx) in teamStats"
                        :key="member.id"
                        class="px-5 py-3 flex items-center gap-3"
                    >
                        <!-- Rank -->
                        <span class="w-5 text-xs font-bold text-gray-400 flex-shrink-0">{{ idx + 1 }}</span>
                        <!-- Avatar -->
                        <div v-if="member.avatar" class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0">
                            <img :src="`/storage/${member.avatar}`" class="w-full h-full object-cover" />
                        </div>
                        <div v-else class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center flex-shrink-0">
                            <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ initials(member.name) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-gray-900 dark:text-white truncate">{{ member.name }}</p>
                            <p class="text-xs text-gray-400">{{ member.assigned }} tasks · {{ formatMinutes(member.time) }}</p>
                        </div>
                        <span class="text-xs font-bold text-green-600 dark:text-green-400 flex-shrink-0">{{ member.completed }}✓</span>
                    </li>
                    <li v-if="!teamStats.length" class="px-5 py-8 text-center text-xs text-gray-400">No activity this period</li>
                </ul>
            </div>
        </div>

    </AuthenticatedLayout>
</template>
