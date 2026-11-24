<script setup>
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import SystemSubnav from '../../components/SystemSubnav.vue';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
    gateway: {
        type: Object,
        default: () => ({}),
    },
    hermesHome: {
        type: String,
        default: 'E:\\hermes',
    },
    systemPaths: {
        type: Object,
        default: () => ({}),
    },
    profiles: {
        type: Array,
        default: () => [],
    },
    currentProfile: {
        type: String,
        default: 'default',
    },
});

const selectedProfile = ref(props.currentProfile);
const switchProfile = (pName) => {
    selectedProfile.value = pName;
    router.get('/system', { profile: pName }, { preserveState: false, preserveScroll: true });
};

// Form for Directory Paths stored in database
const pathForm = useForm({
    hermes_home_path: props.systemPaths?.hermes_home_path || 'E:\\hermes',
    hermes_bin_path: props.systemPaths?.hermes_bin_path || 'E:\\hermes\\bin\\hermes.exe',
    project_directory_path: props.systemPaths?.project_directory_path || 'E:\\App\\hermes-dashboard',
    backup_path: props.systemPaths?.backup_path || 'E:\\hermes\\backups',
    gateway_host: props.systemPaths?.gateway_host || '127.0.0.1',
    gateway_port: props.systemPaths?.gateway_port || 9119,
});

const submitPaths = () => {
    pathForm.post('/system/paths', {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="System & Diagnostics">
        <SystemSubnav />
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-500/10 dark:bg-slate-500/20 text-slate-500 flex items-center justify-center font-bold text-lg">
                        <i class="fa-solid fa-wrench"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                            System Diagnostics & Directory Config
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Status proses runtime, konfigurasi direktori sistem, dan parameter yang tersimpan di PostgreSQL.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <a
                        :href="`http://127.0.0.1:9119/system?profile=${selectedProfile}`"
                        target="_blank"
                        rel="noreferrer"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 transition-colors inline-flex items-center gap-1.5"
                    >
                        <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                        <span>Port 9119 Native</span>
                    </a>
                </div>
            </div>

            <!-- System Info Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <div class="text-xs text-gray-400 font-medium">Gateway Process</div>
                    <div class="text-base font-bold text-emerald-600 dark:text-emerald-400 mt-1 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                        ONLINE (PID: {{ gateway.pid || '9932' }})
                    </div>
                    <p class="text-[11px] text-gray-400 mt-2 font-mono truncate">{{ gateway.hermes_bin }}</p>
                </div>

                <div class="p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <div class="text-xs text-gray-400 font-medium">Direktori Data (Database)</div>
                    <div class="text-base font-bold text-gray-900 dark:text-white mt-1 font-mono truncate">
                        {{ hermesHome }}
                    </div>
                    <p class="text-[11px] text-gray-400 mt-2">Tersimpan di tabel global.system_settings</p>
                </div>

                <div class="p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <div class="text-xs text-gray-400 font-medium">Database Backend</div>
                    <div class="text-base font-bold text-[#435ebe] dark:text-blue-400 mt-1 font-mono">
                        PostgreSQL 17 (Connected)
                    </div>
                    <p class="text-[11px] text-gray-400 mt-2">Database: hermes_dashboard @ port 5432</p>
                </div>
            </div>

            <!-- Project Directory Paths Form (Stored in DB) -->
            <div class="bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-xs">
                <div class="flex items-center justify-between pb-4 mb-6 border-b border-gray-100 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fa-solid fa-folder-gear text-[#435ebe]"></i>
                            <span>Konfigurasi Direktori & Path Project (Tersimpan di Database)</span>
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Data path ini disimpan secara persisten di tabel <code class="font-mono text-[11px] bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded">global.system_settings</code> sehingga tidak ter-reset saat restart.
                        </p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                        DB Sync Active
                    </span>
                </div>

                <form @submit.prevent="submitPaths" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <!-- Hermes Home Path -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                                Hermes Home Directory Path
                            </label>
                            <div class="relative">
                                <i class="fa-solid fa-house-laptop absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                <input
                                    v-model="pathForm.hermes_home_path"
                                    type="text"
                                    required
                                    placeholder="E:\hermes"
                                    class="w-full pl-9 pr-3.5 py-2.5 rounded-xl text-xs font-mono bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none focus:border-[#435ebe]"
                                />
                            </div>
                            <p class="text-[11px] text-gray-400">Direktori tempat file config.yaml, .env, memory, dan folder profiles.</p>
                        </div>

                        <!-- Hermes Binary Path -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                                Hermes Executable Binary Path (hermes.exe)
                            </label>
                            <div class="relative">
                                <i class="fa-solid fa-terminal absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                <input
                                    v-model="pathForm.hermes_bin_path"
                                    type="text"
                                    required
                                    placeholder="E:\hermes\bin\hermes.exe"
                                    class="w-full pl-9 pr-3.5 py-2.5 rounded-xl text-xs font-mono bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none focus:border-[#435ebe]"
                                />
                            </div>
                            <p class="text-[11px] text-gray-400">File hermes.exe yang dipanggil untuk mengeksekusi chat, gateway, dan daemon.</p>
                        </div>

                        <!-- Project Directory Path -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                                Laravel Dashboard Project Directory Path
                            </label>
                            <div class="relative">
                                <i class="fa-solid fa-code absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                <input
                                    v-model="pathForm.project_directory_path"
                                    type="text"
                                    required
                                    placeholder="E:\App\hermes-dashboard"
                                    class="w-full pl-9 pr-3.5 py-2.5 rounded-xl text-xs font-mono bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none focus:border-[#435ebe]"
                                />
                            </div>
                            <p class="text-[11px] text-gray-400">Direktori root dari aplikasi Laravel Expressa Assist.</p>
                        </div>

                        <!-- Backup Path -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                                Backup & Export Storage Directory Path
                            </label>
                            <div class="relative">
                                <i class="fa-solid fa-box-archive absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                <input
                                    v-model="pathForm.backup_path"
                                    type="text"
                                    placeholder="E:\hermes\backups"
                                    class="w-full pl-9 pr-3.5 py-2.5 rounded-xl text-xs font-mono bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none focus:border-[#435ebe]"
                                />
                            </div>
                            <p class="text-[11px] text-gray-400">Lokasi penyimpanan file backup database dan snapshot konfigurasi.</p>
                        </div>

                        <!-- Gateway Host -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                                Gateway Host Bind IP
                            </label>
                            <input
                                v-model="pathForm.gateway_host"
                                type="text"
                                placeholder="127.0.0.1"
                                class="w-full px-3.5 py-2.5 rounded-xl text-xs font-mono bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none focus:border-[#435ebe]"
                            />
                        </div>

                        <!-- Gateway Port -->
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                                Gateway Port Daemon
                            </label>
                            <input
                                v-model="pathForm.gateway_port"
                                type="number"
                                placeholder="9119"
                                class="w-full px-3.5 py-2.5 rounded-xl text-xs font-mono bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none focus:border-[#435ebe]"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-end pt-4 border-t border-gray-100 dark:border-gray-800">
                        <button
                            type="submit"
                            :disabled="pathForm.processing"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#354bb4] text-white flex items-center gap-2 transition cursor-pointer disabled:opacity-50 shadow-sm"
                        >
                            <i v-if="pathForm.processing" class="fa-solid fa-circle-notch animate-spin"></i>
                            <i v-else class="fa-solid fa-floppy-disk"></i>
                            <span>{{ pathForm.processing ? 'Menyimpan ke DB...' : 'Simpan Path ke Database' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
