<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    organization: { type: Object, required: true },
    smtp:         { type: Object, default: () => ({}) },
});

const page = usePage();
const activeTab = ref('general');

// ── General form ──────────────────────────────────────────────────────────────
const form = useForm({
    name:        props.organization.name || '',
    description: props.organization.description || '',
    logo:        null,
});

const logoPreview = ref(props.organization.logo ? `/storage/${props.organization.logo}` : null);
const fileInput = ref(null);

const handleLogoChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.logo = file;
        const reader = new FileReader();
        reader.onload = (e) => { logoPreview.value = e.target.result; };
        reader.readAsDataURL(file);
    }
};

const removeLogo = () => {
    form.logo = null;
    logoPreview.value = null;
    if (fileInput.value) fileInput.value.value = '';
};

const submitGeneral = () => {
    form.post(route('organization.update'), { preserveScroll: true });
};

// ── SMTP form ─────────────────────────────────────────────────────────────────
const smtpForm = useForm({
    host:         props.smtp.host || '',
    port:         props.smtp.port || '587',
    username:     props.smtp.username || '',
    password:     '',          // never pre-fill password
    encryption:   props.smtp.encryption || 'tls',
    from_address: props.smtp.from_address || '',
    from_name:    props.smtp.from_name || '',
});

const showPassword = ref(false);

const submitSmtp = () => {
    smtpForm.post(route('organization.smtp.update'), { preserveScroll: true });
};

const smtpSuccess = computed(() => page.props.flash?.smtp_success);
const generalSuccess = computed(() => page.props.flash?.success);

const tabs = [
    { key: 'general',  label: 'General',  icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
    { key: 'smtp',     label: 'SMTP / Email', icon: 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z' },
];
</script>

<template>
    <Head title="Organization Settings" />

    <AuthenticatedLayout>
        <div class="max-w-4xl mx-auto">

            <!-- Page header -->
            <div class="mb-6">
                <h1 class="text-xl font-bold text-gray-900 dark:text-white">Organization Settings</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Manage your organization profile and configuration.</p>
            </div>

            <!-- Tab bar -->
            <div class="flex gap-1 bg-gray-100 dark:bg-gray-800 p-1 rounded-xl mb-6 w-fit">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    @click="activeTab = tab.key"
                    :class="[
                        'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all',
                        activeTab === tab.key
                            ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-sm'
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200',
                    ]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="tab.icon"/>
                    </svg>
                    {{ tab.label }}
                </button>
            </div>

            <!-- ── General tab ──────────────────────────────────────────────── -->
            <div v-show="activeTab === 'general'" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-8">
                <div class="mb-6">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">Organization Information</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Update your organization's name, description and logo.</p>
                </div>

                <form @submit.prevent="submitGeneral" class="space-y-6">
                    <!-- Name -->
                    <div>
                        <InputLabel for="name" value="Organization Name" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required autofocus />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <!-- Description -->
                    <div>
                        <InputLabel for="description" value="Description" />
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            placeholder="Describe your organization…"
                            class="mt-1 block w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                        ></textarea>
                        <InputError class="mt-2" :message="form.errors.description" />
                    </div>

                    <!-- Logo -->
                    <div>
                        <InputLabel for="logo" value="Organization Logo" />
                        <div class="mt-2 flex items-start gap-5">
                            <div v-if="logoPreview" class="relative flex-shrink-0">
                                <img :src="logoPreview" class="h-24 w-24 object-cover rounded-xl border-2 border-gray-200 dark:border-gray-600" />
                                <button type="button" @click="removeLogo" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                            <div>
                                <input ref="fileInput" id="logo" type="file" accept="image/*" @change="handleLogoChange" class="hidden" />
                                <label for="logo" class="cursor-pointer inline-flex items-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    {{ logoPreview ? 'Change Logo' : 'Upload Logo' }}
                                </label>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">PNG, JPG, GIF up to 2MB</p>
                                <InputError class="mt-1" :message="form.errors.logo" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-4 pt-2">
                        <PrimaryButton :disabled="form.processing">Save Changes</PrimaryButton>
                        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                            <p v-if="form.recentlySuccessful || generalSuccess" class="text-sm text-green-600 dark:text-green-400">Saved.</p>
                        </Transition>
                    </div>
                </form>
            </div>

            <!-- ── SMTP / Email tab ─────────────────────────────────────────── -->
            <div v-show="activeTab === 'smtp'" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-8">
                <div class="mb-6">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">SMTP / Email Configuration</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Configure outgoing email settings for notifications and alerts.</p>
                </div>

                <!-- Info banner -->
                <div class="flex gap-3 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800 mb-6">
                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                        These settings override the application's default mail configuration for this organization.
                        Leave blank to use system defaults.
                    </p>
                </div>

                <form @submit.prevent="submitSmtp" class="space-y-6">

                    <!-- Server section -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Host -->
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">SMTP Host</label>
                            <input
                                v-model="smtpForm.host"
                                type="text"
                                placeholder="smtp.example.com"
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition placeholder-gray-400"
                            />
                            <p v-if="smtpForm.errors.host" class="mt-1 text-xs text-red-500">{{ smtpForm.errors.host }}</p>
                        </div>
                        <!-- Port -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Port</label>
                            <input
                                v-model="smtpForm.port"
                                type="number"
                                min="1" max="65535"
                                placeholder="587"
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                            />
                            <p v-if="smtpForm.errors.port" class="mt-1 text-xs text-red-500">{{ smtpForm.errors.port }}</p>
                        </div>
                    </div>

                    <!-- Encryption -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Encryption</label>
                        <div class="flex gap-3">
                            <label
                                v-for="opt in [{ value: 'tls', label: 'TLS' }, { value: 'ssl', label: 'SSL' }, { value: 'none', label: 'None' }]"
                                :key="opt.value"
                                :class="[
                                    'flex-1 flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border-2 cursor-pointer text-sm font-medium transition-all',
                                    smtpForm.encryption === opt.value
                                        ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300'
                                        : 'border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-gray-300 dark:hover:border-gray-500',
                                ]"
                            >
                                <input type="radio" v-model="smtpForm.encryption" :value="opt.value" class="sr-only" />
                                {{ opt.label }}
                            </label>
                        </div>
                        <p v-if="smtpForm.errors.encryption" class="mt-1 text-xs text-red-500">{{ smtpForm.errors.encryption }}</p>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-2">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">Authentication</p>
                    </div>

                    <!-- Username -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username / Email</label>
                        <input
                            v-model="smtpForm.username"
                            type="text"
                            placeholder="your@email.com"
                            class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition placeholder-gray-400"
                        />
                        <p v-if="smtpForm.errors.username" class="mt-1 text-xs text-red-500">{{ smtpForm.errors.username }}</p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password</label>
                        <div class="relative">
                            <input
                                v-model="smtpForm.password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="Leave blank to keep existing password"
                                class="w-full px-4 py-2.5 pr-10 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition placeholder-gray-400"
                            />
                            <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                                <svg v-if="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-400">Leave blank to keep the existing password unchanged.</p>
                        <p v-if="smtpForm.errors.password" class="mt-1 text-xs text-red-500">{{ smtpForm.errors.password }}</p>
                    </div>

                    <!-- Divider -->
                    <div class="border-t border-gray-100 dark:border-gray-700 pt-2">
                        <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">Sender Identity</p>
                    </div>

                    <!-- From address + name -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Email Address</label>
                            <input
                                v-model="smtpForm.from_address"
                                type="email"
                                placeholder="noreply@yourorg.com"
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition placeholder-gray-400"
                            />
                            <p v-if="smtpForm.errors.from_address" class="mt-1 text-xs text-red-500">{{ smtpForm.errors.from_address }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">From Name</label>
                            <input
                                v-model="smtpForm.from_name"
                                type="text"
                                placeholder="Your Organization"
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition placeholder-gray-400"
                            />
                            <p v-if="smtpForm.errors.from_name" class="mt-1 text-xs text-red-500">{{ smtpForm.errors.from_name }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-4 pt-2">
                        <button
                            type="submit"
                            :disabled="smtpForm.processing"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-sm font-medium rounded-lg transition-colors"
                        >
                            <svg v-if="smtpForm.processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>
                            Save SMTP Settings
                        </button>
                        <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                            <p v-if="smtpSuccess" class="text-sm text-green-600 dark:text-green-400 flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                Settings saved.
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
