<script setup>
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    project: Object,
});

const showingSidebar = ref(false);
const page = usePage();

const navigation = [
    { name: 'Dashboard', href: 'dashboard', icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' },
    { name: 'Projects', href: 'projects.index', icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10' },
    { name: 'Users', href: 'users.index', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
    { name: 'Organization', href: 'organization.edit', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
];

const projectNavigation = computed(() => {
    if (!props.project) return [];
    return [
        { name: 'Overview', href: route('project.view', props.project.project_id), icon: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6', current: true },
        { name: 'Tasks', href: '#', icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' },
        { name: 'Team', href: '#', icon: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' },
        { name: 'Discussions', href: '#', icon: 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z' },
        { name: 'Documents', href: '#', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
        { name: 'Activity', href: '#', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
        { name: 'Settings', href: '#', icon: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' },
    ];
});

const currentNavigation = computed(() => props.project ? projectNavigation.value : navigation);

const isActive = (item) => {
    if (typeof item === 'string') {
        return route().current(item);
    }
    return route().current() === item;
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Sidebar for desktop -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:flex lg:w-64 lg:flex-col">
            <div class="flex flex-col flex-grow border-r border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-y-auto">
                <!-- Organization Logo -->
                <div class="flex items-center flex-shrink-0 px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                    <Link :href="route('dashboard')" class="flex items-center">
                        <img
                            v-if="page.props.auth.organization?.logo"
                            :src="`/storage/${page.props.auth.organization.logo}`"
                            :alt="page.props.auth.organization.name"
                            class="h-9 w-9 object-cover rounded"
                        />
                        <ApplicationLogo v-else class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                        <span class="ml-3 text-xl font-semibold text-gray-800 dark:text-gray-200">
                            {{ page.props.auth.organization?.name || 'Quill' }}
                        </span>
                    </Link>
                </div>

                <!-- Project Info (if in project context) -->
                <div v-if="project" class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <Link :href="route('projects.index')" class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-3">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Back to Projects
                    </Link>
                    <div class="flex items-center space-x-3">
                        <div v-if="project.logo" class="flex-shrink-0">
                            <img :src="`/storage/${project.logo}`" :alt="project.name" class="h-10 w-10 rounded-lg object-cover">
                        </div>
                        <div v-else class="flex-shrink-0">
                            <div class="h-10 w-10 rounded-lg bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                                    {{ project.name.charAt(0).toUpperCase() }}
                                </span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ project.name }}</h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ project.project_id }}</p>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 py-6 space-y-1">
                    <template v-for="item in currentNavigation" :key="item.name">
                        <Link
                            v-if="item.href.startsWith('http') || item.href.startsWith('/')"
                            :href="item.href"
                            :class="[
                                route().current() === item.href
                                    ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-200'
                                    : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white',
                                'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors'
                            ]"
                        >
                            <svg
                                :class="[
                                    route().current() === item.href
                                        ? 'text-indigo-700 dark:text-indigo-200'
                                        : 'text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white',
                                    'mr-3 flex-shrink-0 h-5 w-5'
                                ]"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            {{ item.name }}
                        </Link>
                        <a
                            v-else-if="item.href === '#'"
                            href="#"
                            :class="[
                                'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white',
                                'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors'
                            ]"
                        >
                            <svg
                                :class="[
                                    'text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white',
                                    'mr-3 flex-shrink-0 h-5 w-5'
                                ]"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            {{ item.name }}
                        </a>
                        <Link
                            v-else
                            :href="route(item.href)"
                            :class="[
                                isActive(item.href)
                                    ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white'
                                    : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white',
                                'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors'
                            ]"
                        >
                            <svg
                                :class="[
                                    isActive(item.href)
                                        ? 'text-gray-900 dark:text-white'
                                        : 'text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white',
                                    'mr-3 flex-shrink-0 h-5 w-5'
                                ]"
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                            </svg>
                            {{ item.name }}
                        </Link>
                    </template>
                </nav>
            </div>
        </div>

        <!-- Mobile sidebar -->
        <div v-show="showingSidebar" class="relative z-50 lg:hidden">
            <div class="fixed inset-0 bg-gray-900/80" @click="showingSidebar = false"></div>
            <div class="fixed inset-0 flex">
                <div class="relative mr-16 flex w-full max-w-xs flex-1">
                    <div class="absolute left-full top-0 flex w-16 justify-center pt-5">
                        <button type="button" class="-m-2.5 p-2.5" @click="showingSidebar = false">
                            <span class="sr-only">Close sidebar</span>
                            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex flex-col flex-grow bg-white dark:bg-gray-800 overflow-y-auto">
                        <!-- Organization Logo -->
                        <div class="flex items-center flex-shrink-0 px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                            <Link :href="route('dashboard')" class="flex items-center" @click="showingSidebar = false">
                                <img
                                    v-if="page.props.auth.organization?.logo"
                                    :src="`/storage/${page.props.auth.organization.logo}`"
                                    :alt="page.props.auth.organization.name"
                                    class="h-9 w-9 object-cover rounded"
                                />
                                <ApplicationLogo v-else class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                                <span class="ml-3 text-xl font-semibold text-gray-800 dark:text-gray-200">
                                    {{ page.props.auth.organization?.name || 'Quill' }}
                                </span>
                            </Link>
                        </div>

                        <!-- Project Info (if in project context) -->
                        <div v-if="project" class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <Link :href="route('projects.index')" class="inline-flex items-center text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white mb-3" @click="showingSidebar = false">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Back to Projects
                            </Link>
                            <div class="flex items-center space-x-3">
                                <div v-if="project.logo" class="flex-shrink-0">
                                    <img :src="`/storage/${project.logo}`" :alt="project.name" class="h-10 w-10 rounded-lg object-cover">
                                </div>
                                <div v-else class="flex-shrink-0">
                                    <div class="h-10 w-10 rounded-lg bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                        <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">
                                            {{ project.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ project.name }}</h2>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ project.project_id }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation -->
                        <nav class="flex-1 px-4 py-6 space-y-1">
                            <template v-for="item in currentNavigation" :key="item.name">
                                <Link
                                    v-if="item.href.startsWith('http') || item.href.startsWith('/')"
                                    :href="item.href"
                                    :class="[
                                        route().current() === item.href
                                            ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-200'
                                            : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white',
                                        'group flex items-center px-3 py-2 text-sm font-medium rounded-md'
                                    ]"
                                    @click="showingSidebar = false"
                                >
                                    <svg
                                        :class="[
                                            route().current() === item.href
                                                ? 'text-indigo-700 dark:text-indigo-200'
                                                : 'text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white',
                                            'mr-3 flex-shrink-0 h-5 w-5'
                                        ]"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                                    </svg>
                                    {{ item.name }}
                                </Link>
                                <a
                                    v-else-if="item.href === '#'"
                                    href="#"
                                    :class="[
                                        'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white',
                                        'group flex items-center px-3 py-2 text-sm font-medium rounded-md'
                                    ]"
                                    @click="showingSidebar = false"
                                >
                                    <svg
                                        :class="[
                                            'text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white',
                                            'mr-3 flex-shrink-0 h-5 w-5'
                                        ]"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                                    </svg>
                                    {{ item.name }}
                                </a>
                                <Link
                                    v-else
                                    :href="route(item.href)"
                                    :class="[
                                        isActive(item.href)
                                            ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white'
                                            : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white',
                                        'group flex items-center px-3 py-2 text-sm font-medium rounded-md'
                                    ]"
                                    @click="showingSidebar = false"
                                >
                                    <svg
                                        :class="[
                                            isActive(item.href)
                                                ? 'text-gray-900 dark:text-white'
                                                : 'text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white',
                                            'mr-3 flex-shrink-0 h-5 w-5'
                                        ]"
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="item.icon" />
                                    </svg>
                                    {{ item.name }}
                                </Link>
                            </template>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content area -->
        <div class="lg:pl-64 flex flex-col h-screen">
            <!-- Top bar -->
            <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                <!-- Mobile menu button -->
                <button
                    type="button"
                    class="-m-2.5 p-2.5 text-gray-700 dark:text-gray-300 lg:hidden"
                    @click="showingSidebar = true"
                >
                    <span class="sr-only">Open sidebar</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <!-- Separator -->
                <div class="h-6 w-px bg-gray-200 dark:bg-gray-700 lg:hidden"></div>

                <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6 items-center justify-end">
                    <!-- Profile dropdown -->
                    <div class="flex items-center gap-x-4 lg:gap-x-6">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button type="button" class="flex items-center gap-x-3">
                                    <div class="hidden lg:block text-right">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $page.props.auth.user.name }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $page.props.auth.user.email }}
                                        </div>
                                    </div>
                                    <img
                                        v-if="$page.props.auth.user.avatar"
                                        :src="`/storage/${$page.props.auth.user.avatar}`"
                                        :alt="$page.props.auth.user.name"
                                        class="h-9 w-9 rounded-full object-cover"
                                    />
                                    <div v-else class="h-9 w-9 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                        <span class="text-sm font-semibold text-indigo-600 dark:text-indigo-300">
                                            {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                    <svg class="hidden lg:block h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('profile.edit')">
                                    Profile
                                </DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">
                                    Log Out
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </div>

            <!-- Page content -->
            <main class="flex-1 overflow-y-auto">
                <div class="py-6">
                    <!-- Page heading -->
                    <div v-if="$slots.header" class="px-4 sm:px-6 lg:px-8 mb-6">
                        <slot name="header" />
                    </div>

                    <!-- Page content -->
                    <div class="px-4 sm:px-6 lg:px-8">
                        <slot />
                    </div>
                </div>
            </main>
        </div>
    </div>
</template>
