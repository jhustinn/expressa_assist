<script setup>
import { ref, computed } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';

const props = defineProps({
    currentPath: {
        type: String,
        required: true,
    },
    parentPath: {
        type: String,
        default: null,
    },
    breadcrumbs: {
        type: Array,
        default: () => [],
    },
    directories: {
        type: Array,
        default: () => [],
    },
    files: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({ total_directories: 0, total_files: 0, total_size_formatted: '0 B' }),
    },
    quickRoots: {
        type: Array,
        default: () => [],
    },
});

const searchQuery = ref('');
const isPathInputOpen = ref(false);
const manualPathInput = ref(props.currentPath);

// File Preview Modal State
const previewModalOpen = ref(false);
const previewLoading = ref(false);
const previewData = ref(null);
const copySuccess = ref(false);

const navigateTo = (path) => {
    if (!path) return;
    router.get('/files', { path }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const submitManualPath = () => {
    if (!manualPathInput.value.trim()) return;
    navigateTo(manualPathInput.value.trim());
    isPathInputOpen.value = false;
};

// Filtered directories & files by search query
const filteredDirectories = computed(() => {
    if (!searchQuery.value.trim()) return props.directories;
    const q = searchQuery.value.toLowerCase();
    return props.directories.filter(d => d.name.toLowerCase().includes(q));
});

const filteredFiles = computed(() => {
    if (!searchQuery.value.trim()) return props.files;
    const q = searchQuery.value.toLowerCase();
    return props.files.filter(f => f.name.toLowerCase().includes(q) || (f.extension && f.extension.toLowerCase().includes(q)));
});

// Open File Preview
const openFilePreview = async (file) => {
    previewModalOpen.value = true;
    previewLoading.value = true;
    previewData.value = null;
    copySuccess.value = false;

    try {
        const res = await fetch(`/api/files/read?path=${encodeURIComponent(file.path)}`);
        const data = await res.json();
        if (res.ok && data.success) {
            previewData.value = data;
        } else {
            previewData.value = {
                name: file.name,
                path: file.path,
                content: data.message || 'Gagal membaca berkas.',
                is_error: true,
            };
        }
    } catch (e) {
        previewData.value = {
            name: file.name,
            path: file.path,
            content: 'Terjadi kesalahan saat memuat berkas: ' + e.message,
            is_error: true,
        };
    } finally {
        previewLoading.value = false;
    }
};

const closeFilePreview = () => {
    previewModalOpen.value = false;
    previewData.value = null;
};

const copyFileContent = () => {
    if (!previewData.value || !previewData.value.content) return;
    navigator.clipboard.writeText(previewData.value.content);
    copySuccess.value = true;
    setTimeout(() => {
        copySuccess.value = false;
    }, 2000);
};

// Icon resolver for files
const getFileIcon = (ext) => {
    switch (ext) {
        case 'md':
            return { icon: 'fa-brands fa-markdown', color: 'text-sky-400' };
        case 'json':
            return { icon: 'fa-solid fa-brackets-curly', color: 'text-amber-400' };
        case 'yaml':
        case 'yml':
            return { icon: 'fa-solid fa-sliders', color: 'text-purple-400' };
        case 'vue':
            return { icon: 'fa-brands fa-vuejs', color: 'text-emerald-400' };
        case 'js':
        case 'ts':
            return { icon: 'fa-brands fa-js', color: 'text-yellow-400' };
        case 'php':
            return { icon: 'fa-brands fa-php', color: 'text-indigo-400' };
        case 'env':
            return { icon: 'fa-solid fa-key', color: 'text-rose-400' };
        case 'db':
        case 'sqlite':
        case 'sql':
            return { icon: 'fa-solid fa-database', color: 'text-violet-400' };
        case 'log':
        case 'txt':
            return { icon: 'fa-regular fa-file-lines', color: 'text-gray-400' };
        case 'lock':
            return { icon: 'fa-solid fa-lock', color: 'text-amber-500' };
        case 'exe':
        case 'bat':
        case 'ps1':
        case 'sh':
            return { icon: 'fa-solid fa-terminal', color: 'text-emerald-400' };
        default:
            return { icon: 'fa-regular fa-file', color: 'text-gray-400' };
    }
};
</script>

<template>
    <AppLayout title="Workspace & Directory Browser">
        <Head title="File Manager & Directory Browser - Expressa Assist" />

        <div class="space-y-5">
            <!-- Header Banner -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-3xl bg-white dark:bg-[#111726] border border-gray-200 dark:border-[#1a2337] shadow-xs">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-[#435ebe]/10 dark:bg-[#435ebe]/20 text-[#435ebe] dark:text-blue-400 flex items-center justify-center font-bold text-xl shrink-0">
                        <i class="fa-solid fa-folder-tree"></i>
                    </div>
                    <div>
                        <h2 class="text-lg sm:text-xl font-extrabold text-gray-900 dark:text-white tracking-tight flex items-center gap-2">
                            Workspace & Directory Browser
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                            Navigasi direktori, inspeksi file sesi, memory, kode sumber, dan artifak proyek.
                        </p>
                    </div>
                </div>

                <!-- Stats summary badge -->
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="px-3 py-1 rounded-xl bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-800 text-gray-600 dark:text-slate-300 font-mono flex items-center gap-1.5">
                        <i class="fa-solid fa-folder text-amber-400"></i>
                        <span>{{ stats.total_directories }} Folder</span>
                    </span>
                    <span class="px-3 py-1 rounded-xl bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-800 text-gray-600 dark:text-slate-300 font-mono flex items-center gap-1.5">
                        <i class="fa-solid fa-file text-blue-400"></i>
                        <span>{{ stats.total_files }} Berkas</span>
                    </span>
                    <span class="px-3 py-1 rounded-xl bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-800 text-gray-600 dark:text-slate-300 font-mono flex items-center gap-1.5">
                        <i class="fa-solid fa-hard-drive text-emerald-400"></i>
                        <span>{{ stats.total_size_formatted }}</span>
                    </span>
                </div>
            </div>

            <!-- Quick Root Presets Bar -->
            <div class="flex flex-wrap items-center gap-2.5">
                <span class="text-xs font-bold text-gray-500 dark:text-slate-400 mr-1 flex items-center gap-1.5">
                    <i class="fa-solid fa-compass text-[#435ebe]"></i> Scope Cepat:
                </span>
                <button
                    v-for="root in quickRoots"
                    :key="root.path"
                    @click="navigateTo(root.path)"
                    :disabled="!root.exists"
                    :class="[
                        'px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all flex items-center gap-2 border',
                        currentPath.toLowerCase().startsWith(root.path.toLowerCase())
                            ? 'bg-[#435ebe] text-white border-[#435ebe] shadow-xs'
                            : 'bg-white dark:bg-[#111726] border-gray-200 dark:border-[#1a2337] text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-800/60'
                    ]"
                    :title="root.path"
                >
                    <i :class="[root.icon, 'text-xs']"></i>
                    <span>{{ root.name }}</span>
                </button>
            </div>

            <!-- Explorer Navigation Bar -->
            <div class="p-3 rounded-2xl bg-white dark:bg-[#111726] border border-gray-200 dark:border-[#1a2337] shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
                <!-- Left: Up Button & Breadcrumbs / Path Input -->
                <div class="flex-1 flex items-center gap-2 min-w-0">
                    <!-- Up / Parent Directory Button -->
                    <button
                        @click="navigateTo(parentPath)"
                        :disabled="!parentPath"
                        class="p-2 rounded-xl text-xs font-bold bg-gray-100 hover:bg-gray-200 dark:bg-[#151b2e] dark:hover:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-gray-700 transition disabled:opacity-40 disabled:cursor-not-allowed shrink-0"
                        title="Naik ke direktori atas (Parent)"
                    >
                        <i class="fa-solid fa-arrow-up"></i>
                    </button>

                    <!-- Breadcrumbs View -->
                    <div v-if="!isPathInputOpen" class="flex-1 flex items-center gap-1.5 overflow-x-auto scrollbar-thin py-1 text-xs font-mono min-w-0">
                        <template v-for="(crumb, idx) in breadcrumbs" :key="crumb.path">
                            <button
                                @click="navigateTo(crumb.path)"
                                :class="[
                                    'px-2.5 py-1 rounded-lg transition-colors truncate shrink-0',
                                    idx === breadcrumbs.length - 1
                                        ? 'bg-[#435ebe]/10 text-[#435ebe] dark:text-blue-400 font-bold border border-[#435ebe]/30'
                                        : 'text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-slate-800'
                                ]"
                                :title="crumb.path"
                            >
                                {{ crumb.name }}
                            </button>
                            <span v-if="idx < breadcrumbs.length - 1" class="text-gray-400 shrink-0">
                                <i class="fa-solid fa-chevron-right text-[10px]"></i>
                            </span>
                        </template>
                    </div>

                    <!-- Manual Path Input Form -->
                    <form v-else @submit.prevent="submitManualPath" class="flex-1 flex items-center gap-2">
                        <input
                            v-model="manualPathInput"
                            type="text"
                            placeholder="Ketik path direktori (misal: E:\hermes\profiles\jhustyn)..."
                            class="flex-1 px-3 py-1.5 rounded-xl text-xs font-mono bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none focus:border-[#435ebe]"
                            autofocus
                        />
                        <button
                            type="submit"
                            class="px-3 py-1.5 rounded-xl text-xs font-bold bg-[#435ebe] text-white hover:bg-[#354bb4] transition shrink-0"
                        >
                            Buka
                        </button>
                        <button
                            type="button"
                            @click="isPathInputOpen = false"
                            class="px-2.5 py-1.5 rounded-xl text-xs text-gray-400 hover:text-white transition shrink-0"
                        >
                            Batal
                        </button>
                    </form>

                    <!-- Toggle Path Input Button -->
                    <button
                        v-if="!isPathInputOpen"
                        @click="manualPathInput = currentPath; isPathInputOpen = true"
                        class="p-2 rounded-xl text-xs text-gray-400 hover:text-gray-700 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition shrink-0"
                        title="Edit / Salin alamat path"
                    >
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                </div>

                <!-- Right: Search Filter Input & Refresh -->
                <div class="flex items-center gap-2 shrink-0">
                    <div class="relative w-full sm:w-56">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari file / folder..."
                            class="w-full pl-8 pr-3 py-1.5 rounded-xl text-xs bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:border-[#435ebe]"
                        />
                    </div>

                    <button
                        @click="navigateTo(currentPath)"
                        class="p-2 rounded-xl text-xs font-bold bg-gray-100 hover:bg-gray-200 dark:bg-[#151b2e] dark:hover:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-gray-700 transition"
                        title="Muat ulang direktori saat ini"
                    >
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                </div>
            </div>

            <!-- Directory & Files Table Container -->
            <div class="rounded-3xl bg-white dark:bg-[#111726] border border-gray-200 dark:border-[#1a2337] overflow-hidden shadow-xs">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 dark:bg-[#151b2e] border-b border-gray-200 dark:border-gray-800 text-[11px] font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                            <tr>
                                <th class="py-3 px-4 w-1/2">Nama</th>
                                <th class="py-3 px-4 w-28">Tipe / Item</th>
                                <th class="py-3 px-4 w-28">Ukuran</th>
                                <th class="py-3 px-4 w-36">Terakhir Diubah</th>
                                <th class="py-3 px-4 w-28 text-right">Aksi</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800/70 text-gray-700 dark:text-slate-300">
                            <!-- Parent Directory Row (..) -->
                            <tr
                                v-if="parentPath"
                                @click="navigateTo(parentPath)"
                                class="hover:bg-gray-50/70 dark:hover:bg-slate-800/40 cursor-pointer transition select-none"
                            >
                                <td class="py-2.5 px-4 flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center text-sm font-bold shrink-0">
                                        <i class="fa-solid fa-folder-open"></i>
                                    </span>
                                    <span class="font-bold font-mono text-gray-900 dark:text-white">.. (Direktori Atas)</span>
                                </td>
                                <td class="py-2.5 px-4 text-gray-400 font-mono">Folder</td>
                                <td class="py-2.5 px-4 text-gray-400 font-mono">-</td>
                                <td class="py-2.5 px-4 text-gray-400 font-mono">-</td>
                                <td class="py-2.5 px-4 text-right"></td>
                            </tr>

                            <!-- Directories Rows -->
                            <tr
                                v-for="dir in filteredDirectories"
                                :key="dir.path"
                                @click="navigateTo(dir.path)"
                                class="hover:bg-gray-50/70 dark:hover:bg-slate-800/40 cursor-pointer transition select-none group"
                            >
                                <td class="py-2.5 px-4 flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-xl bg-amber-500/10 text-amber-500 group-hover:bg-amber-500 group-hover:text-white transition flex items-center justify-center text-sm font-bold shrink-0">
                                        <i class="fa-solid fa-folder"></i>
                                    </span>
                                    <div class="truncate">
                                        <span class="font-semibold text-gray-900 dark:text-white group-hover:text-[#435ebe] dark:group-hover:text-blue-400 transition font-mono">
                                            {{ dir.name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-4">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-gray-100 dark:bg-[#151b2e] text-gray-600 dark:text-slate-400 border border-gray-200 dark:border-gray-800">
                                        {{ dir.items_count }} item
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-gray-400 font-mono">-</td>
                                <td class="py-2.5 px-4 text-gray-400 font-mono text-[11px]">{{ dir.last_modified }}</td>
                                <td class="py-2.5 px-4 text-right">
                                    <button
                                        @click.stop="navigateTo(dir.path)"
                                        class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-gray-100 hover:bg-[#435ebe] hover:text-white dark:bg-slate-800 dark:hover:bg-[#435ebe] transition"
                                    >
                                        Buka
                                    </button>
                                </td>
                            </tr>

                            <!-- Files Rows -->
                            <tr
                                v-for="file in filteredFiles"
                                :key="file.path"
                                class="hover:bg-gray-50/70 dark:hover:bg-slate-800/40 transition select-none group"
                            >
                                <td class="py-2.5 px-4 flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-xl bg-gray-100 dark:bg-[#151b2e] flex items-center justify-center text-sm font-bold shrink-0">
                                        <i :class="[getFileIcon(file.extension).icon, getFileIcon(file.extension).color]"></i>
                                    </span>
                                    <div class="truncate">
                                        <span class="font-medium text-gray-900 dark:text-slate-200 font-mono text-xs">
                                            {{ file.name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-2.5 px-4">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-mono uppercase bg-gray-100 dark:bg-[#151b2e] text-gray-600 dark:text-slate-400 border border-gray-200 dark:border-gray-800">
                                        {{ file.extension || 'file' }}
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-gray-600 dark:text-slate-300 font-mono">{{ file.size_formatted }}</td>
                                <td class="py-2.5 px-4 text-gray-400 font-mono text-[11px]">{{ file.last_modified }}</td>
                                <td class="py-2.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Preview Button for readable files -->
                                        <button
                                            v-if="file.is_editable"
                                            @click="openFilePreview(file)"
                                            class="px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-[#435ebe]/10 hover:bg-[#435ebe] text-[#435ebe] hover:text-white dark:text-blue-400 dark:hover:text-white transition flex items-center gap-1 cursor-pointer"
                                            title="Pratinjau konten berkas"
                                        >
                                            <i class="fa-solid fa-eye text-[10px]"></i>
                                            <span>Lihat</span>
                                        </button>

                                        <!-- Download Button -->
                                        <a
                                            :href="`/api/files/download?path=${encodeURIComponent(file.path)}`"
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition"
                                            title="Download berkas"
                                        >
                                            <i class="fa-solid fa-download text-xs"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty Directory Notice -->
                            <tr v-if="filteredDirectories.length === 0 && filteredFiles.length === 0">
                                <td colspan="5" class="py-12 text-center text-xs text-gray-400">
                                    <div class="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-[#151b2e] text-gray-400 flex items-center justify-center mx-auto mb-2 text-xl">
                                        <i class="fa-regular fa-folder-open"></i>
                                    </div>
                                    <div class="font-bold text-gray-600 dark:text-slate-400">Direktori Kosong</div>
                                    <p class="text-[11px] text-gray-400 mt-0.5">Tidak ada file atau sub-folder yang ditemukan pada path ini.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- FILE PREVIEW MODAL                                           -->
        <!-- ============================================================ -->
        <div
            v-if="previewModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
        >
            <div class="w-full max-w-4xl max-h-[85vh] rounded-3xl bg-white dark:bg-[#111726] border border-gray-200 dark:border-[#1a2337] shadow-2xl flex flex-col overflow-hidden">
                <!-- Modal Header -->
                <div class="p-4 sm:p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-[#435ebe]/10 text-[#435ebe] flex items-center justify-center font-bold shrink-0">
                            <i :class="[getFileIcon(previewData?.extension || '').icon, 'text-base']"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-bold text-sm text-gray-900 dark:text-white truncate font-mono">
                                {{ previewData?.name || 'Memuat berkas...' }}
                            </h3>
                            <div class="text-[11px] text-gray-400 font-mono truncate">
                                {{ previewData?.path }}
                            </div>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center gap-2 shrink-0">
                        <button
                            v-if="previewData && !previewData.is_error"
                            @click="copyFileContent"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 transition flex items-center gap-1.5"
                            title="Salin isi berkas"
                        >
                            <i :class="copySuccess ? 'fa-solid fa-check text-emerald-400' : 'fa-regular fa-copy'"></i>
                            <span>{{ copySuccess ? 'Disalin' : 'Salin' }}</span>
                        </button>

                        <a
                            v-if="previewData?.path"
                            :href="`/api/files/download?path=${encodeURIComponent(previewData.path)}`"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-[#435ebe] text-white hover:bg-[#354bb4] transition flex items-center gap-1.5"
                        >
                            <i class="fa-solid fa-download"></i>
                            <span class="hidden sm:inline">Download</span>
                        </a>

                        <button
                            @click="closeFilePreview"
                            class="p-2 rounded-xl text-gray-400 hover:text-gray-700 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-800 transition"
                        >
                            <i class="fa-solid fa-xmark text-base"></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Content / Code Viewer -->
                <div class="flex-1 p-4 overflow-y-auto bg-gray-50 dark:bg-[#0c101b] font-mono text-xs leading-relaxed select-text">
                    <div v-if="previewLoading" class="py-16 text-center text-gray-400">
                        <i class="fa-solid fa-spinner animate-spin text-2xl text-[#435ebe] mb-2"></i>
                        <p>Membaca konten berkas...</p>
                    </div>

                    <div v-else-if="previewData?.is_error" class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs">
                        {{ previewData.content }}
                    </div>

                    <div v-else class="whitespace-pre overflow-x-auto text-gray-800 dark:text-slate-200">
                        {{ previewData?.content }}
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="p-3 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-[#111726] flex items-center justify-between text-[11px] text-gray-400 font-mono">
                    <span>Ukuran: {{ previewData?.size || '-' }}</span>
                    <span>Terakhir diubah: {{ previewData?.last_modified || '-' }}</span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
