<template>
    <AuthenticatedLayout :project="project">
        <Head :title="`${project.name} – Documents`" />

        <div class="flex h-[calc(100vh-4rem)] -m-6 overflow-hidden">

            <!-- ─── Left Sidebar: Document Tree ────────────────────────────── -->
            <aside class="w-72 flex-shrink-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col">
                <!-- Header -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200 uppercase tracking-wide">Documents</h2>
                    <button
                        @click="openCreate(null)"
                        class="flex items-center gap-1 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 transition-colors"
                        title="New document"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        New
                    </button>
                </div>

                <!-- Flat list -->
                <nav class="flex-1 overflow-y-auto py-2 custom-scrollbar">
                    <template v-if="flatTree.length">
                        <button
                            v-for="flat in flatTree"
                            :key="flat.id"
                            @click="navigateTo(flat)"
                            class="group w-full flex items-center gap-2 px-3 py-2 text-left transition-colors rounded-lg mx-1 my-0.5"
                            :class="[
                                activeDoc?.id === flat.id
                                    ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300'
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/60',
                            ]"
                            :style="{ paddingLeft: (flat.depth * 16 + 12) + 'px' }"
                            :title="flat.title"
                        >
                            <svg class="w-3.5 h-3.5 flex-shrink-0 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="flex-1 text-xs font-medium truncate">{{ flat.title }}</span>
                            <button
                                @click.stop="openCreate(flat.id)"
                                class="opacity-0 group-hover:opacity-100 w-4 h-4 flex-shrink-0 text-gray-400 hover:text-indigo-500 transition-opacity"
                                title="Add child document"
                            >
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </button>
                        </button>
                    </template>
                    <p v-else class="px-4 py-6 text-xs text-gray-400 dark:text-gray-500 text-center">
                        No documents yet.<br>Click <strong>New</strong> to create one.
                    </p>
                </nav>
            </aside>

            <!-- ─── Right Panel: View / Edit ───────────────────────────────── -->
            <main class="flex-1 overflow-y-auto bg-gray-50 dark:bg-gray-900 custom-scrollbar">

                <!-- ── Edit / Create Form ── -->
                <div v-if="editing" class="max-w-4xl mx-auto p-8">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8">
                        <!-- Form header -->
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                                {{ form.id ? 'Edit Document' : 'New Document' }}
                            </h2>
                            <button @click="cancelEdit" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Title -->
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title <span class="text-red-500">*</span></label>
                            <input
                                v-model="form.title"
                                type="text"
                                placeholder="Document title…"
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-sm"
                            />
                            <p v-if="errors.title" class="mt-1 text-xs text-red-500">{{ errors.title }}</p>
                        </div>

                        <!-- Parent document -->
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Parent Document</label>
                            <select
                                v-model="form.parent_id"
                                class="w-full px-4 py-2.5 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition text-sm"
                            >
                                <option :value="null">— None (top level) —</option>
                                <option
                                    v-for="flat in flatTree"
                                    :key="flat.id"
                                    :value="flat.id"
                                    :disabled="form.id && flat.id === form.id"
                                >
                                    {{ '&nbsp;&nbsp;&nbsp;&nbsp;'.repeat(flat.depth) + flat.title }}
                                </option>
                            </select>
                        </div>

                        <!-- Tags -->
                        <div class="mb-5">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tags</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="tag in tags"
                                    :key="tag.id"
                                    type="button"
                                    @click="toggleTag(tag.id)"
                                    :class="[
                                        'px-3 py-1 rounded-full text-xs font-medium border-2 transition-all',
                                        form.tag_ids.includes(tag.id)
                                            ? 'opacity-100 shadow-sm scale-105'
                                            : 'opacity-50 hover:opacity-75',
                                    ]"
                                    :style="{
                                        backgroundColor: form.tag_ids.includes(tag.id) ? (tag.color || '#6366f1') + '22' : 'transparent',
                                        borderColor: tag.color || '#6366f1',
                                        color: tag.color || '#6366f1',
                                    }"
                                >
                                    {{ tag.name }}
                                </button>
                                <span v-if="!tags.length" class="text-xs text-gray-400 dark:text-gray-500">No tags available</span>
                            </div>
                        </div>

                        <!-- CKEditor Content -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Content</label>
                            <div class="rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden ck-editor-wrapper">
                                <Ckeditor
                                    :editor="editor"
                                    v-model="form.content"
                                    :config="editorConfig"
                                    class="min-h-[400px]"
                                />
                            </div>
                            <p v-if="errors.content" class="mt-1 text-xs text-red-500">{{ errors.content }}</p>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-3">
                            <button
                                @click="submitForm"
                                :disabled="processing"
                                class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-sm font-medium rounded-lg transition-colors"
                            >
                                <svg v-if="processing" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                                </svg>
                                {{ form.id ? 'Save Changes' : 'Create Document' }}
                            </button>
                            <button @click="cancelEdit" class="px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ── View Mode ── -->
                <div v-else-if="activeDoc" class="max-w-4xl mx-auto p-8">
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8">
                        <!-- Document header -->
                        <div class="flex items-start justify-between gap-4 mb-6">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white leading-tight flex-1">
                                {{ activeDoc.title }}
                            </h1>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <button
                                    @click="openEdit(activeDoc)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 rounded-lg transition-colors"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                    Edit
                                </button>
                                <button
                                    @click="openCreate(activeDoc.id)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Add Child
                                </button>
                                <button
                                    @click="confirmDelete(activeDoc)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 hover:bg-red-100 dark:hover:bg-red-900/60 rounded-lg transition-colors"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M8 7V5a1 1 0 011-1h6a1 1 0 011 1v2"/>
                                    </svg>
                                    Delete
                                </button>
                            </div>
                        </div>

                        <!-- Meta row -->
                        <div class="flex flex-wrap items-center gap-4 mb-5 text-xs text-gray-500 dark:text-gray-400">
                            <!-- Author -->
                            <div class="flex items-center gap-1.5">
                                <div v-if="activeDoc.author?.avatar" class="w-5 h-5 rounded-full overflow-hidden">
                                    <img :src="`/storage/${activeDoc.author.avatar}`" class="w-full h-full object-cover" />
                                </div>
                                <div v-else class="w-5 h-5 rounded-full bg-indigo-100 dark:bg-indigo-900 flex items-center justify-center">
                                    <span class="text-indigo-600 dark:text-indigo-400 font-bold" style="font-size:9px">
                                        {{ initials(activeDoc.author?.name) }}
                                    </span>
                                </div>
                                <span>{{ activeDoc.author?.name }}</span>
                            </div>
                            <span class="text-gray-300 dark:text-gray-600">·</span>
                            <span>Updated {{ formatDate(activeDoc.updated_at) }}</span>
                        </div>

                        <!-- Tags -->
                        <div v-if="activeDoc.tags?.length" class="flex flex-wrap gap-1.5 mb-6">
                            <span
                                v-for="tag in activeDoc.tags"
                                :key="tag.id"
                                class="px-2.5 py-0.5 rounded-full text-xs font-medium"
                                :style="{ backgroundColor: (tag.color || '#6366f1') + '22', color: tag.color || '#6366f1' }"
                            >
                                {{ tag.name }}
                            </span>
                        </div>

                        <!-- Divider -->
                        <hr class="border-gray-200 dark:border-gray-700 mb-6" />

                        <!-- Document content (rendered HTML) -->
                        <div
                            v-if="activeDoc.content"
                            class="prose prose-sm dark:prose-invert max-w-none doc-view ck-content"
                            v-html="activeDoc.content"
                        />
                        <div v-else class="py-12 text-center">
                            <svg class="mx-auto mb-3 w-10 h-10 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="text-sm text-gray-400 dark:text-gray-500">This document has no content yet.</p>
                            <button @click="openEdit(activeDoc)" class="mt-3 text-xs text-indigo-500 hover:text-indigo-700 dark:hover:text-indigo-300 font-medium">
                                + Add content
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ── Empty State (no doc selected) ── -->
                <div v-else class="flex flex-col items-center justify-center h-full text-center px-8">
                    <svg class="w-16 h-16 text-gray-200 dark:text-gray-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10l6 6v10a2 2 0 01-2 2zM9 12h6M9 16h4"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-400 dark:text-gray-500 mb-1">No document selected</h3>
                    <p class="text-sm text-gray-400 dark:text-gray-500 mb-4">Select a document from the sidebar or create a new one.</p>
                    <button
                        @click="openCreate(null)"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Create First Document
                    </button>
                </div>
            </main>
        </div>

        <!-- ─── Delete Confirmation Modal ──────────────────────────────────── -->
        <Teleport to="body">
            <div v-if="deleteTarget" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm">
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-md mx-4 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Delete Document</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">This action cannot be undone.</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-5">
                        Are you sure you want to delete <strong>"{{ deleteTarget.title }}"</strong>?
                        Child documents will be moved up to the parent level.
                    </p>
                    <div class="flex gap-3 justify-end">
                        <button @click="deleteTarget = null" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors">
                            Cancel
                        </button>
                        <button @click="doDelete" :disabled="processing" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:opacity-60 rounded-lg transition-colors">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Ckeditor } from '@ckeditor/ckeditor5-vue';
import ClassicEditor from '@ckeditor/ckeditor5-build-classic';

// ─── Sub-component: recursive tree item ─────────────────────────────────────
const DocTreeItem = {
    name: 'DocTreeItem',
    props: {
        node: Object,
        activeId: Number,
        project: Object,
        depth: { type: Number, default: 0 },
    },
    emits: ['select', 'add-child'],
    components: {},
    setup(props, { emit }) {
        const open = ref(true);
        const hasChildren = computed(() => props.node.children?.length > 0);
        return { open, hasChildren };
    },
    template: `
        <div>
            <div
                class="group flex items-center gap-1 px-2 py-1.5 cursor-pointer rounded-lg mx-1 transition-colors"
                :class="[
                    activeId === node.id
                        ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300'
                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/60',
                ]"
                :style="{ paddingLeft: (depth * 16 + 8) + 'px' }"
                @click="$emit('select', node)"
            >
                <!-- Chevron toggle -->
                <button
                    v-if="hasChildren"
                    @click.stop="open = !open"
                    class="w-4 h-4 flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-transform"
                    :class="{ 'rotate-90': open }"
                >
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <span v-else class="w-4 flex-shrink-0"></span>

                <!-- Doc icon -->
                <svg class="w-3.5 h-3.5 flex-shrink-0 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>

                <span class="flex-1 text-xs font-medium truncate">{{ node.title }}</span>

                <!-- Add child button -->
                <button
                    @click.stop="$emit('add-child', node.id)"
                    class="opacity-0 group-hover:opacity-100 w-4 h-4 flex-shrink-0 text-gray-400 hover:text-indigo-500 transition-opacity"
                    title="Add child document"
                >
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                </button>
            </div>

            <!-- Recursive children -->
            <div v-if="hasChildren && open">
                <doc-tree-item
                    v-for="child in node.children"
                    :key="child.id"
                    :node="child"
                    :active-id="activeId"
                    :project="project"
                    :depth="depth + 1"
                    @select="$emit('select', $event)"
                    @add-child="$emit('add-child', $event)"
                />
            </div>
        </div>
    `,
};

// ─── Props ───────────────────────────────────────────────────────────────────
const props = defineProps({
    project: Object,
    tree:    Array,
    tags:    Array,
    activeDoc: Object,
});

// ─── Editor setup ────────────────────────────────────────────────────────────
const editor = ClassicEditor;
const editorConfig = {
    toolbar: [
        'heading', '|',
        'bold', 'italic', 'underline', 'strikethrough', '|',
        'link', 'blockQuote', 'code', '|',
        'bulletedList', 'numberedList', 'todoList', '|',
        'outdent', 'indent', '|',
        'insertTable', 'mediaEmbed', '|',
        'undo', 'redo',
    ],
};

// ─── State ───────────────────────────────────────────────────────────────────
const editing    = ref(false);
const processing = ref(false);
const deleteTarget = ref(null);
const errors     = ref({});

const form = ref({
    id:        null,
    title:     '',
    content:   '',
    parent_id: null,
    tag_ids:   [],
});

// ─── Flatten tree for parent selector ────────────────────────────────────────
function flattenTree(nodes, depth = 0) {
    const result = [];
    for (const node of nodes) {
        result.push({ id: node.id, title: node.title, depth });
        if (node.children?.length) {
            result.push(...flattenTree(node.children, depth + 1));
        }
    }
    return result;
}
const flatTree = computed(() => flattenTree(props.tree));

// ─── Helpers ─────────────────────────────────────────────────────────────────
function initials(name) {
    if (!name) return '?';
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function toggleTag(id) {
    const idx = form.value.tag_ids.indexOf(id);
    if (idx === -1) form.value.tag_ids.push(id);
    else form.value.tag_ids.splice(idx, 1);
}

// ─── Navigation ──────────────────────────────────────────────────────────────
function navigateTo(node) {
    if (editing.value) {
        // Confirm discard if dirty
        if (!confirm('Discard unsaved changes?')) return;
        editing.value = false;
    }
    router.get(route('project.documents.show', { projectId: props.project.project_id, document: node.id }), {}, {
        preserveScroll: true,
    });
}

// ─── Open create / edit ──────────────────────────────────────────────────────
function openCreate(parentId) {
    errors.value = {};
    form.value = {
        id:        null,
        title:     '',
        content:   '',
        parent_id: parentId ?? null,
        tag_ids:   [],
    };
    editing.value = true;
}

function openEdit(doc) {
    errors.value = {};
    form.value = {
        id:        doc.id,
        title:     doc.title,
        content:   doc.content ?? '',
        parent_id: doc.parent_id ?? null,
        tag_ids:   doc.tags?.map(t => t.id) ?? [],
    };
    editing.value = true;
}

function cancelEdit() {
    editing.value = false;
    errors.value = {};
}

// ─── Submit ──────────────────────────────────────────────────────────────────
function submitForm() {
    errors.value = {};
    processing.value = true;

    const payload = {
        title:     form.value.title,
        content:   form.value.content,
        parent_id: form.value.parent_id,
        tag_ids:   form.value.tag_ids,
    };

    if (form.value.id) {
        // Update
        router.put(
            route('project.documents.update', { projectId: props.project.project_id, document: form.value.id }),
            payload,
            {
                onSuccess: () => { editing.value = false; },
                onError:   (e) => { errors.value = e; },
                onFinish:  () => { processing.value = false; },
                preserveScroll: true,
            }
        );
    } else {
        // Create
        router.post(
            route('project.documents.store', { projectId: props.project.project_id }),
            payload,
            {
                onSuccess: () => { editing.value = false; },
                onError:   (e) => { errors.value = e; },
                onFinish:  () => { processing.value = false; },
                preserveScroll: true,
            }
        );
    }
}

// ─── Delete ──────────────────────────────────────────────────────────────────
function confirmDelete(doc) {
    deleteTarget.value = doc;
}

function doDelete() {
    processing.value = true;
    router.delete(
        route('project.documents.destroy', { projectId: props.project.project_id, document: deleteTarget.value.id }),
        {
            onSuccess: () => { deleteTarget.value = null; },
            onFinish:  () => { processing.value = false; },
            preserveScroll: true,
        }
    );
}

// Reset editing state when activeDoc changes (Inertia navigation)
watch(() => props.activeDoc, () => {
    editing.value = false;
    errors.value = {};
});
</script>

<style>
/* CKEditor content styles */
.ck-editor-wrapper .ck-editor__editable {
    min-height: 400px;
    max-height: 600px;
}

/* Render CKEditor output correctly */
.doc-view .ck-content { color: #ffffff; }
.doc-view .ck-content h2 { font-size: 1.5rem; font-weight: 700; margin: 1.25rem 0 0.5rem; color: #ffffff; }
.doc-view .ck-content h3 { font-size: 1.25rem; font-weight: 600; margin: 1rem 0 0.4rem; color: #ffffff; }
.doc-view .ck-content h4 { font-size: 1.1rem;  font-weight: 600; margin: 0.75rem 0 0.3rem; color: #ffffff; }
.doc-view .ck-content p   { margin: 0.5rem 0; line-height: 1.7; color: #ffffff; }
.doc-view .ck-content li  { color: #ffffff; }
.doc-view .ck-content a   { color: #a5b4fc; text-decoration: underline; }
/* Keep editor (form) text dark */
.ck-editor-wrapper .ck-content { color: #111827 !important; }
.ck-editor-wrapper .ck-content * { color: inherit; }
.ck-content ul, .ck-content ol { padding-left: 1.5rem; margin: 0.5rem 0; }
.ck-content ul  { list-style-type: disc; }
.ck-content ol  { list-style-type: decimal; }
.ck-content li  { margin: 0.25rem 0; }
.ck-content blockquote {
    border-left: 4px solid #6366f1;
    padding: 0.5rem 1rem;
    margin: 0.75rem 0;
    color: #6b7280;
    background: #f9fafb;
    border-radius: 0 4px 4px 0;
}
.dark .ck-content blockquote { background: #1f2937; color: #9ca3af; }
.ck-content pre, .ck-content code {
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
    background: #f3f4f6;
    border-radius: 4px;
    font-size: 0.875em;
}
.dark .ck-content pre, .dark .ck-content code { background: #1f2937; color: #e5e7eb; }
.ck-content pre  { padding: 1rem; overflow-x: auto; margin: 0.75rem 0; }
.ck-content code { padding: 0.1em 0.3em; }
.ck-content a    { color: #6366f1; text-decoration: underline; }
.ck-content table { border-collapse: collapse; width: 100%; margin: 0.75rem 0; }
.ck-content table td,
.ck-content table th { border: 1px solid #e5e7eb; padding: 0.5rem 0.75rem; text-align: left; }
.dark .ck-content table td,
.dark .ck-content table th { border-color: #374151; }
.ck-content table th { background: #f9fafb; font-weight: 600; }
.dark .ck-content table th { background: #1f2937; }
.ck-content figure.table { overflow-x: auto; }

/* Custom scrollbar */
.custom-scrollbar { scrollbar-width: thin; scrollbar-color: #d1d5db transparent; }
.custom-scrollbar::-webkit-scrollbar { width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background-color: #d1d5db; border-radius: 3px; }
.dark .custom-scrollbar { scrollbar-color: #4b5563 transparent; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #4b5563; }
</style>
