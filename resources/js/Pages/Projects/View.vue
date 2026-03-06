<template>
    <AuthenticatedLayout :project="project">
        <Head :title="project.name + ' – Overview'" />

        <!-- ── Header banner ─────────────────────────────────────────────── -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6 flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex items-center gap-4 flex-1">
                <div v-if="project.logo" class="w-14 h-14 rounded-xl overflow-hidden flex-shrink-0">
                    <img :src="`/storage/${project.logo}`" class="w-full h-full object-cover" />
                </div>
                <div v-else class="w-14 h-14 rounded-xl bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center flex-shrink-0">
                    <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ project.name.charAt(0) }}</span>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">{{ project.name }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ project.description || 'No description provided' }}</p>
                </div>
            </div>
            <div class="flex items-center gap-3 flex-shrink-0">
                <span :class="statusClass(project.status)" class="px-3 py-1 rounded-full text-xs font-semibold">
                    {{ project.status }}
                </span>
                <span class="text-xs text-gray-400 dark:text-gray-500">Since {{ project.created_at }}</span>
            </div>
        </div>

        <!-- ── KPI cards ─────────────────────────────────────────────────── -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/40 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total Tasks</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.total_tasks }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/40 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Completed</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.completed_tasks }}</p>
                    <p class="text-xs text-green-600 dark:text-green-400 font-medium">{{ stats.completion_rate }}%</p>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Team Members</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ stats.team_count }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/40 flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Time Logged</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ formatMinutes(stats.total_minutes) }}</p>
                </div>
            </div>
        </div>

        <!-- ── Overall completion progress bar ───────────────────────────── -->
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-6">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Overall Progress</span>
                <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ stats.completion_rate }}%</span>
            </div>
            <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-3">
                <div
                    class="h-3 rounded-full bg-gradient-to-r from-indigo-500 to-indigo-600 transition-all duration-700"
                    :style="{ width: stats.completion_rate + '%' }"
                />
            </div>
            <div class="flex gap-6 mt-3 text-xs text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-gray-300 dark:bg-gray-600 inline-block"></span> Todo: {{ stats.todo_tasks }}</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-yellow-400 inline-block"></span> In Progress: {{ stats.in_progress_tasks }}</span>
                <span class="flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span> Completed: {{ stats.completed_tasks }}</span>
            </div>
        </div>

        <!-- ── Charts row ─────────────────────────────────────────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

            <!-- Task Status Doughnut -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4">Task Status Distribution</h2>
                <div v-if="stats.total_tasks > 0" class="flex items-center gap-6">
                    <div class="w-44 h-44 flex-shrink-0">
                        <Doughnut :data="statusChartData" :options="doughnutOptions" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <div v-for="(item, i) in statusLegend" :key="i" class="flex items-center gap-2 text-sm">
                            <span class="w-3 h-3 rounded-sm flex-shrink-0" :style="{ backgroundColor: item.color }"></span>
                            <span class="text-gray-600 dark:text-gray-400">{{ item.label }}</span>
                            <span class="font-semibold text-gray-900 dark:text-white ml-auto pl-4">{{ item.value }}</span>
                        </div>
                    </div>
                </div>
                <div v-else class="py-10 text-center text-sm text-gray-400 dark:text-gray-500">No tasks yet</div>
            </div>

            <!-- Category Progress Bar Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-4">Progress by Category</h2>
                <div v-if="tasksByCategory.length > 0" class="h-52">
                    <Bar :data="categoryChartData" :options="barOptions" />
                </div>
                <div v-else class="py-10 text-center text-sm text-gray-400 dark:text-gray-500">No categories yet</div>
            </div>
        </div>

        <!-- ── Category breakdown table ────────────────────────────────────── -->
        <div v-if="tasksByCategory.length > 0" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 mb-6 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Category Breakdown</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-900/40">
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Todo</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">In Progress</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Completed</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Progress</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-for="cat in tasksByCategory" :key="cat.name" class="hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors">
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full flex-shrink-0" :style="{ backgroundColor: cat.color || '#6366f1' }"></span>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ cat.name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-3 text-center text-sm text-gray-700 dark:text-gray-300 font-semibold">{{ cat.total }}</td>
                            <td class="px-6 py-3 text-center text-sm text-gray-500 dark:text-gray-400">{{ cat.todo }}</td>
                            <td class="px-6 py-3 text-center">
                                <span class="text-sm text-yellow-600 dark:text-yellow-400 font-medium">{{ cat.in_progress }}</span>
                            </td>
                            <td class="px-6 py-3 text-center">
                                <span class="text-sm text-green-600 dark:text-green-400 font-medium">{{ cat.completed }}</span>
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-2 min-w-24">
                                        <div
                                            class="h-2 rounded-full transition-all"
                                            :style="{ width: catProgress(cat) + '%', backgroundColor: cat.color || '#6366f1' }"
                                        />
                                    </div>
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 w-8 text-right">{{ catProgress(cat) }}%</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Bottom row: Team + Recent Tasks ─────────────────────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Team Members -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Team</h2>
                    <span class="text-xs text-gray-400">{{ teamMembers.length }} members</span>
                </div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    <li v-for="member in teamMembers" :key="member.id" class="px-6 py-3 flex items-center gap-3">
                        <div v-if="member.avatar" class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0">
                            <img :src="`/storage/${member.avatar}`" class="w-full h-full object-cover" />
                        </div>
                        <div v-else class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center flex-shrink-0">
                            <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ initials(member.name) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ member.name }}</p>
                            <p class="text-xs text-gray-400 capitalize">{{ member.role }}</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ member.tasks_assigned }} tasks</p>
                            <p class="text-xs text-green-600 dark:text-green-400">{{ member.tasks_completed }} done</p>
                        </div>
                    </li>
                    <li v-if="!teamMembers.length" class="px-6 py-8 text-center text-sm text-gray-400">No team members yet</li>
                </ul>
                <!-- Owner row -->
                <div class="px-6 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900/30 flex items-center gap-3">
                    <div v-if="project.owner.avatar" class="w-8 h-8 rounded-full overflow-hidden flex-shrink-0">
                        <img :src="`/storage/${project.owner.avatar}`" class="w-full h-full object-cover" />
                    </div>
                    <div v-else class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center flex-shrink-0">
                        <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">{{ initials(project.owner.name) }}</span>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ project.owner.name }}</p>
                    </div>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 font-medium">Owner</span>
                </div>
            </div>

            <!-- Recent Tasks -->
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide">Recent Tasks</h2>
                </div>
                <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                    <li v-for="task in recentTasks" :key="task.id" class="px-6 py-3 flex items-start gap-3">
                        <span :class="taskStatusDot(task.status)" class="mt-1.5 w-2 h-2 rounded-full flex-shrink-0"></span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ task.title }}</p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span v-if="task.category" class="text-xs px-1.5 py-0.5 rounded" :style="{ backgroundColor: (task.category.color || '#6366f1') + '22', color: task.category.color || '#6366f1' }">
                                    {{ task.category.name }}
                                </span>
                                <span class="text-xs text-gray-400">{{ task.created_at }}</span>
                            </div>
                        </div>
                        <div v-if="task.assignee" class="flex-shrink-0">
                            <div v-if="task.assignee.avatar" class="w-6 h-6 rounded-full overflow-hidden">
                                <img :src="`/storage/${task.assignee.avatar}`" class="w-full h-full object-cover" />
                            </div>
                            <div v-else class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                <span class="text-gray-600 dark:text-gray-300" style="font-size:9px">{{ initials(task.assignee.name) }}</span>
                            </div>
                        </div>
                    </li>
                    <li v-if="!recentTasks.length" class="px-6 py-8 text-center text-sm text-gray-400">No tasks yet</li>
                </ul>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Doughnut, Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    ArcElement, Tooltip, Legend,
    CategoryScale, LinearScale, BarElement, Title,
} from 'chart.js';

ChartJS.register(ArcElement, Tooltip, Legend, CategoryScale, LinearScale, BarElement, Title);

const props = defineProps({
    project:         Object,
    stats:           Object,
    tasksByStatus:   Object,
    tasksByCategory: Array,
    teamMembers:     Array,
    recentTasks:     Array,
});

// ── Helpers ──────────────────────────────────────────────────────────────────
function initials(name) {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
}

function formatMinutes(mins) {
    if (!mins) return '0h';
    const h = Math.floor(mins / 60);
    const m = mins % 60;
    return h > 0 ? `${h}h ${m > 0 ? m + 'm' : ''}`.trim() : `${m}m`;
}

function catProgress(cat) {
    if (!cat.total) return 0;
    return Math.round((cat.completed / cat.total) * 100);
}

function statusClass(status) {
    const map = {
        'Created':     'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
        'Progressing': 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300',
        'On Hold':     'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300',
        'Completed':   'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
    };
    return map[status] || 'bg-gray-100 text-gray-700';
}

function taskStatusDot(status) {
    const map = {
        'Todo':        'bg-gray-300 dark:bg-gray-600',
        'In Progress': 'bg-yellow-400',
        'Completed':   'bg-green-500',
    };
    return map[status] || 'bg-gray-300';
}

// ── Status doughnut chart ─────────────────────────────────────────────────────
const STATUS_COLORS = {
    'Todo':        '#d1d5db',
    'In Progress': '#f59e0b',
    'Completed':   '#22c55e',
    'On Hold':     '#ef4444',
};

const statusLegend = computed(() =>
    Object.entries(props.tasksByStatus || {}).map(([label, value]) => ({
        label,
        value,
        color: STATUS_COLORS[label] || '#6366f1',
    }))
);

const statusChartData = computed(() => ({
    labels: statusLegend.value.map(s => s.label),
    datasets: [{
        data:            statusLegend.value.map(s => s.value),
        backgroundColor: statusLegend.value.map(s => s.color),
        borderWidth: 0,
        hoverOffset: 4,
    }],
}));

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '70%',
    plugins: { legend: { display: false }, tooltip: { callbacks: {
        label: ctx => ` ${ctx.label}: ${ctx.parsed}`,
    }}},
};

// ── Category bar chart ────────────────────────────────────────────────────────
const categoryChartData = computed(() => ({
    labels: props.tasksByCategory.map(c => c.name),
    datasets: [
        {
            label: 'Completed',
            data: props.tasksByCategory.map(c => c.completed),
            backgroundColor: '#22c55e',
            borderRadius: 4,
        },
        {
            label: 'In Progress',
            data: props.tasksByCategory.map(c => c.in_progress),
            backgroundColor: '#f59e0b',
            borderRadius: 4,
        },
        {
            label: 'Todo',
            data: props.tasksByCategory.map(c => c.todo),
            backgroundColor: '#d1d5db',
            borderRadius: 4,
        },
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
        x: { stacked: true, grid: { display: false }, ticks: { font: { size: 11 } } },
        y: { stacked: true, beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: 'rgba(156,163,175,0.15)' } },
    },
};
</script>
