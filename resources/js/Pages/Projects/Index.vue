<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({
    projects: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const canManageProjects = computed(() => {
    const user = page.props.auth.user;
    return user.role?.name === 'Admin';
});

const showModal = ref(false);
const editMode = ref(false);
const currentProject = ref(null);

const form = useForm({
    name: '',
    project_id: '',
    description: '',
    status: 'Created',
    logo: null,
});

const logoPreview = ref(null);
const fileInput = ref(null);

const openCreateModal = () => {
    editMode.value = false;
    currentProject.value = null;
    form.reset();
    logoPreview.value = null;
    showModal.value = true;
};

const openEditModal = (project) => {
    editMode.value = true;
    currentProject.value = project;
    form.name = project.name;
    form.project_id = project.project_id;
    form.description = project.description || '';
    form.status = project.status;
    form.logo = null;
    logoPreview.value = project.logo ? `/storage/${project.logo}` : null;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
    logoPreview.value = null;
};

const handleLogoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.logo = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            logoPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const removeLogo = () => {
    form.logo = null;
    logoPreview.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submitForm = () => {
    if (editMode.value) {
        // For file uploads, we need to use post with _method spoofing
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(route('projects.update', currentProject.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            forceFormData: true,
        });
    } else {
        form.post(route('projects.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const deleteProject = (project) => {
    if (confirm(`Are you sure you want to delete "${project.name}"?`)) {
        useForm({}).delete(route('projects.destroy', project.id), {
            preserveScroll: true,
        });
    }
};

const modalTitle = computed(() => editMode.value ? 'Edit Project' : 'Create Project');

// Auto-generate project ID from project name in create mode
watch(() => form.name, (newName) => {
    if (!editMode.value && newName) {
        const cleaned = newName.replace(/[^a-zA-Z0-9\s]/g, '');
        const words = cleaned.split(' ').filter(word => word.length > 0);
        
        let prefix = '';
        if (words.length === 1) {
            // Single word: take first 3 letters
            prefix = words[0].slice(0, 3).toUpperCase();
        } else {
            // Multiple words: take first letter of each word (up to 3)
            prefix = words
                .map(word => word.charAt(0))
                .join('')
                .toUpperCase()
                .slice(0, 3);
        }
        
        form.project_id = prefix || '';
    }
});
</script>

<template>
    <Head title="Projects" />

    <AuthenticatedLayout>
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <!-- Header -->
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                        <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Projects</h2>
                        <button
                            v-if="canManageProjects"
                            @click="openCreateModal"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Create Project
                        </button>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Project ID</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Owner</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Created</th>
                                    <th v-if="canManageProjects" scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-if="projects.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        No projects found. Create your first project to get started.
                                    </td>
                                </tr>
                                <tr v-for="project in projects" :key="project.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <img
                                                v-if="project.logo"
                                                :src="`/storage/${project.logo}`"
                                                :alt="project.name"
                                                class="h-10 w-10 rounded-full object-cover mr-3"
                                            />
                                            <div v-else class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center mr-3">
                                                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ project.name }}</div>
                                                <div v-if="project.description" class="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ project.description }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                            {{ project.project_id }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            :class="[
                                                'px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full',
                                                project.status === 'Created' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200' : '',
                                                project.status === 'Progressing' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '',
                                                project.status === 'On Hold' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : '',
                                                project.status === 'Completed' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ''
                                            ]"
                                        >
                                            {{ project.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ project.owner }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ project.created_at }}
                                    </td>
                                    <td v-if="canManageProjects" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button
                                            @click="openEditModal(project)"
                                            class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-4"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="deleteProject(project)"
                                            class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

        <!-- Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="submitForm">
                        <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="mb-4">
                                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100" id="modal-title">
                                    {{ modalTitle }}
                                </h3>
                            </div>

                            <div class="space-y-4">
                                <!-- Project Name -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Project Name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        required
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    />
                                    <p v-if="form.errors.name" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.name }}</p>
                                </div>

                                <!-- Project ID -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Project ID <span v-if="!editMode" class="text-red-500">*</span>
                                    </label>
                                    
                                    <!-- Edit Mode: Display as text -->
                                    <div v-if="editMode" class="mt-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md">
                                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ form.project_id }}</span>
                                    </div>
                                    
                                    <!-- Create Mode: Editable input -->
                                    <input
                                        v-else
                                        id="project_id"
                                        v-model="form.project_id"
                                        type="text"
                                        required
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        placeholder="e.g., PRO"
                                    />
                                    
                                    <p v-if="!editMode" class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Auto-generated from project name. You can edit it.
                                    </p>
                                    <p v-if="form.errors.project_id" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.project_id }}</p>
                                </div>

                                <!-- Description -->
                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Description
                                    </label>
                                    <textarea
                                        id="description"
                                        v-model="form.description"
                                        rows="3"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        placeholder="Brief description of the project..."
                                    ></textarea>
                                    <p v-if="form.errors.description" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.description }}</p>
                                </div>

                                <!-- Status (only in edit mode) -->
                                <div v-if="editMode">
                                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Status <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="status"
                                        v-model="form.status"
                                        required
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    >
                                        <option value="Created">Created</option>
                                        <option value="Progressing">Progressing</option>
                                        <option value="On Hold">On Hold</option>
                                        <option value="Completed">Completed</option>
                                    </select>
                                    <p v-if="form.errors.status" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.status }}</p>
                                </div>

                                <!-- Logo -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Project Logo
                                    </label>
                                    <div class="mt-2 flex items-start space-x-4">
                                        <!-- Logo Preview -->
                                        <div v-if="logoPreview" class="flex-shrink-0">
                                            <div class="relative">
                                                <img
                                                    :src="logoPreview"
                                                    alt="Project logo"
                                                    class="h-20 w-20 object-cover rounded border-2 border-gray-300 dark:border-gray-600"
                                                />
                                                <button
                                                    type="button"
                                                    @click="removeLogo"
                                                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition"
                                                >
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Upload Button -->
                                        <div class="flex-1">
                                            <input
                                                ref="fileInput"
                                                type="file"
                                                accept="image/*"
                                                @change="handleLogoChange"
                                                class="hidden"
                                            />
                                            <label
                                                @click="() => fileInput.click()"
                                                class="cursor-pointer inline-flex items-center px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 transition"
                                            >
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                                {{ logoPreview ? 'Change' : 'Upload' }}
                                            </label>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                PNG, JPG, GIF up to 2MB
                                            </p>
                                            <p v-if="form.errors.logo" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.logo }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Modal Footer -->
                        <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50"
                            >
                                {{ editMode ? 'Update' : 'Create' }}
                            </button>
                            <button
                                type="button"
                                @click="closeModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
