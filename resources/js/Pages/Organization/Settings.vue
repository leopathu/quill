<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    organization: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    name: props.organization.name || '',
    description: props.organization.description || '',
    logo: null,
});

const logoPreview = ref(props.organization.logo ? `/storage/${props.organization.logo}` : null);
const fileInput = ref(null);

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

const submit = () => {
    form.post(route('organization.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Organization Settings" />

    <AuthenticatedLayout>
        <div class="space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Organization Information
                            </h2>

                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Update your organization's profile information and logo.
                            </p>
                        </header>

                        <form @submit.prevent="submit" class="mt-6 space-y-6">
                            <!-- Organization Name -->
                            <div>
                                <InputLabel for="name" value="Organization Name" />

                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    required
                                    autofocus
                                />

                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <!-- Organization Description -->
                            <div>
                                <InputLabel for="description" value="Description" />

                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                    rows="4"
                                    placeholder="Describe your organization..."
                                ></textarea>

                                <InputError class="mt-2" :message="form.errors.description" />
                            </div>

                            <!-- Organization Logo -->
                            <div>
                                <InputLabel for="logo" value="Organization Logo" />

                                <div class="mt-2 flex items-start space-x-4">
                                    <!-- Logo Preview -->
                                    <div v-if="logoPreview" class="flex-shrink-0">
                                        <div class="relative">
                                            <img
                                                :src="logoPreview"
                                                alt="Organization logo"
                                                class="h-24 w-24 object-cover rounded-lg border-2 border-gray-300 dark:border-gray-600"
                                            />
                                            <button
                                                type="button"
                                                @click="removeLogo"
                                                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition"
                                            >
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Upload Button -->
                                    <div class="flex-1">
                                        <input
                                            ref="fileInput"
                                            id="logo"
                                            type="file"
                                            accept="image/*"
                                            @change="handleLogoChange"
                                            class="hidden"
                                        />

                                        <label
                                            for="logo"
                                            class="cursor-pointer inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150"
                                        >
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ logoPreview ? 'Change Logo' : 'Upload Logo' }}
                                        </label>

                                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                            PNG, JPG, GIF up to 2MB
                                        </p>

                                        <InputError class="mt-2" :message="form.errors.logo" />
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="form.processing">
                                    Save Changes
                                </PrimaryButton>

                                <Transition
                                    enter-active-class="transition ease-in-out"
                                    enter-from-class="opacity-0"
                                    leave-active-class="transition ease-in-out"
                                    leave-to-class="opacity-0"
                                >
                                    <p
                                        v-if="form.recentlySuccessful"
                                        class="text-sm text-gray-600 dark:text-gray-400"
                                    >
                                        Saved.
                                    </p>
                                </Transition>
                            </div>
                        </form>
                    </section>
                </div>
        </div>
    </AuthenticatedLayout>
</template>
