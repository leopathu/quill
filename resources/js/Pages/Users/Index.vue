<template>
    <Head title="Users" />

    <AuthenticatedLayout>
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <!-- Header -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Users</h2>
                <button
                    v-if="canManageUsers"
                    @click="openCreateModal"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add User
                </button>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">User</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Role</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Joined</th>
                            <th v-if="canManageUsers" scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr v-if="users.length === 0">
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                No users found. Add your first user to get started.
                            </td>
                        </tr>
                        <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <img
                                        v-if="user.avatar"
                                        :src="`/storage/${user.avatar}`"
                                        :alt="user.name"
                                        class="h-10 w-10 rounded-full object-cover mr-3"
                                    />
                                    <div v-else class="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center mr-3">
                                        <span class="text-indigo-600 dark:text-indigo-300 font-semibold text-sm">
                                            {{ user.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ user.name }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ user.email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span :class="getRoleBadgeClass(user.role)" class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full">
                                    {{ user.role }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ user.created_at }}
                            </td>
                            <td v-if="canManageUsers" class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <button
                                    @click="openEditModal(user)"
                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-4"
                                >
                                    Edit
                                </button>
                                <button
                                    v-if="user.id !== $page.props.auth.user.id"
                                    @click="confirmDelete(user)"
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

        <!-- Create/Edit Modal -->
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
                                    {{ isEditing ? 'Edit User' : 'Add User' }}
                                </h3>
                            </div>

                            <div class="space-y-4">
                                <!-- Avatar -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Avatar</label>
                            <div class="flex items-center space-x-4">
                                <div class="flex-shrink-0">
                                    <img
                                        v-if="avatarPreview"
                                        :src="avatarPreview"
                                        alt="Avatar preview"
                                        class="h-20 w-20 rounded-full object-cover"
                                    />
                                    <div v-else class="h-20 w-20 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                        <svg class="h-10 w-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        accept="image/*"
                                        @change="handleAvatarChange"
                                        class="block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 dark:file:bg-indigo-900 file:text-indigo-700 dark:file:text-indigo-300 hover:file:bg-indigo-100 dark:hover:file:bg-indigo-800"
                                    />
                                    <button
                                        v-if="avatarPreview"
                                        type="button"
                                        @click="removeAvatar"
                                        class="mt-2 text-sm text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                    >
                                        Remove Avatar
                                    </button>
                                </div>
                            </div>
                                    <p v-if="form.errors.avatar" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.avatar }}</p>
                                </div>

                                <!-- Name -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Name <span class="text-red-500">*</span>
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

                                <!-- Email -->
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Email <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        required
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    />
                                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.email }}</p>
                                </div>

                                <!-- Role -->
                                <div>
                                    <label for="role_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Role <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="role_id"
                                        v-model="form.role_id"
                                        required
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    >
                                        <option value="">Select a role</option>
                                        <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                                    </select>
                                    <p v-if="form.errors.role_id" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.role_id }}</p>
                                </div>

                                <!-- Password -->
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Password <span v-if="!isEditing" class="text-red-500">*</span>
                                        <span v-else class="text-sm text-gray-500 dark:text-gray-400">(leave blank to keep current)</span>
                                    </label>
                                    <input
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        :required="!isEditing"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    />
                                    <p v-if="form.errors.password" class="mt-1 text-sm text-red-600 dark:text-red-400">{{ form.errors.password }}</p>
                                </div>

                                <!-- Confirm Password -->
                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Confirm Password <span v-if="!isEditing || form.password" class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        :required="!isEditing || form.password"
                                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    />
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
                                {{ isEditing ? 'Update' : 'Create' }}
                            </button>
                            <button
                                type="button"
                                @click="closeModal"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm"
                            >
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" @click.self="showDeleteModal = false">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
                <div class="mt-3 text-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">Delete User</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Are you sure you want to delete {{ userToDelete?.name }}? This action cannot be undone.
                        </p>
                    </div>
                    <div class="flex justify-center gap-4 px-4 py-3">
                        <button
                            @click="showDeleteModal = false"
                            class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500"
                        >
                            Cancel
                        </button>
                        <button
                            @click="deleteUser"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
                        >
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    users: Array,
    roles: Array,
});

const page = usePage();
const canManageUsers = computed(() => {
    const user = page.props.auth.user;
    return user.role?.name === 'Admin';
});

const showModal = ref(false);
const showDeleteModal = ref(false);
const isEditing = ref(false);
const userToDelete = ref(null);
const editingUserId = ref(null);

const form = useForm({
    name: '',
    email: '',
    role_id: '',
    password: '',
    password_confirmation: '',
    avatar: null,
});

const avatarPreview = ref(null);
const fileInput = ref(null);

const openCreateModal = () => {
    isEditing.value = false;
    form.reset();
    form.clearErrors();
    avatarPreview.value = null;
    showModal.value = true;
};

const openEditModal = (user) => {
    isEditing.value = true;
    editingUserId.value = user.id;
    const role = props.roles.find(r => r.name === user.role);
    form.name = user.name;
    form.email = user.email;
    form.role_id = role?.id || '';
    form.password = '';
    form.password_confirmation = '';
    form.avatar = null;
    avatarPreview.value = user.avatar ? `/storage/${user.avatar}` : null;
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
    avatarPreview.value = null;
};

const handleAvatarChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.avatar = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            avatarPreview.value = e.target.result;
        };
        reader.readAsDataURL(file);
    }
};

const removeAvatar = () => {
    form.avatar = null;
    avatarPreview.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submitForm = () => {
    if (isEditing.value) {
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
        })).post(route('users.update', editingUserId.value), {
            onSuccess: () => closeModal(),
            forceFormData: true,
        });
    } else {
        form.post(route('users.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const confirmDelete = (user) => {
    userToDelete.value = user;
    showDeleteModal.value = true;
};

const deleteUser = () => {
    form.delete(route('users.destroy', userToDelete.value.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
            userToDelete.value = null;
        },
    });
};

const getRoleBadgeClass = (roleName) => {
    const classes = {
        'Admin': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        'Team': 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
    };
    return classes[roleName] || 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
};
</script>
