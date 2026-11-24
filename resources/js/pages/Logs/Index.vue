<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';

const props = defineProps({
    logs: {
        type: Array,
        default: () => [],
    },
    currentFile: {
        type: String,
        default: 'agent',
    },
    currentLevel: {
        type: String,
        default: 'ALL',
    },
    currentComponent: {
        type: String,
        default: 'ALL',
    },
    currentLines: {
        type: Number,
        default: 100,
    },
    currentSearch: {
        type: String,
        default: '',
    },
    currentProfile: {
        type: String,
        default: 'default',
    },
    currentFilePath: {
        type: String,
        default: '',
    },
    profiles: {
        type: Array,
        default: () => [],
    },
    logFiles: {
        type: Array,
        default: () => ['agent', 'errors', 'gateway'],
    },
    levels: {
        type: Array,
        default: () => ['ALL', 'DEBUG', 'INFO', 'WARNING', 'ERROR'],
    },
    components: {
        type: Array,
        default: () => ['ALL', 'GATEWAY', 'AGENT', 'TOOLS', 'CLI', 'CRON'],
    },
    lineOptions: {
        type: Array,
        default: () => [50, 100, 200, 500],
    },
});

// State
const selectedFile = ref(props.currentFile);
const selectedLevel = ref(props.currentLevel);
const selectedComponent = ref(props.currentComponent);
const selectedLines = ref(props.currentLines);
const selectedProfile = ref(props.currentProfile);
const searchQuery = ref(props.currentSearch);

const logLines = ref([...props.logs]);
const filePath = ref(props.currentFilePath);
const isRefreshing = ref(false);
const autoRefresh = ref(true);
const autoScroll = ref(true);
const copied = ref(false);

const logsContainer = ref(null);
let refreshTimer = null;

// Fetch logs from API
const fetchLogs = async (silent = false) => {
    if (!silent) isRefreshing.value = true;
    try {
        const params = new URLSearchParams({
            file: selectedFile.value,
            level: selectedLevel.value,
            component: selectedComponent.value,
            lines: selectedLines.value.toString(),
            profile: selectedProfile.value,
        });
        if (searchQuery.value) {
            params.append('search', searchQuery.value);
        }

        const res = await fetch(`/api/logs?${params.toString()}`);
        if (res.ok) {
            const data = await res.json();
            logLines.value = data.lines || [];
            if (data.filePath) {
                filePath.value = data.filePath;
            }
            if (autoScroll.value) {
                scrollToBottom();
            }
        }
    } catch (e) {
        console.error('Failed to fetch logs:', e);
    } finally {
        if (!silent) isRefreshing.value = false;
    }
};

// URL synchronization
const applyFilters = () => {
    router.get(
        '/logs',
        {
            file: selectedFile.value,
            level: selectedLevel.value,
            component: selectedComponent.value,
            lines: selectedLines.value,
            profile: selectedProfile.value,
            search: searchQuery.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onSuccess: (page) => {
                logLines.value = page.props.logs || [];
                filePath.value = page.props.currentFilePath || '';
                if (autoScroll.value) {
                    scrollToBottom();
                }
            },
        }
    );
};

// Quick switches
const selectFile = (file) => {
    selectedFile.value = file;
    applyFilters();
};

const selectLevel = (level) => {
    selectedLevel.value = level;
    applyFilters();
};

const selectComponent = (comp) => {
    selectedComponent.value = comp;
    applyFilters();
};

const selectLinesCount = (count) => {
    selectedLines.value = Number(count);
    applyFilters();
};

const switchProfile = (profile) => {
    selectedProfile.value = profile;
    applyFilters();
};

let searchTimeout = null;
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
};

const clearSearch = () => {
    searchQuery.value = '';
    applyFilters();
};

// Auto scroll helper
const scrollToBottom = () => {
    nextTick(() => {
        if (logsContainer.value) {
            logsContainer.value.scrollTop = logsContainer.value.scrollHeight;
        }
    });
};

// Copy all logs
const copyLogs = async () => {
    try {
        await navigator.clipboard.writeText(logLines.value.join('\n'));
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (e) {
        console.error('Copy failed', e);
    }
};

// Log line styling parser
const getLineStyle = (line) => {
    if (/\b(ERROR|CRITICAL|FATAL)\b/i.test(line)) {
        return 'text-rose-400 bg-rose-950/20 font-medium';
    }
    if (/\b(WARNING|WARN)\b/i.test(line)) {
        return 'text-amber-300 bg-amber-950/15';
    }
    if (/\b(INFO)\b/i.test(line)) {
        return 'text-slate-200';
    }
    if (/\b(DEBUG)\b/i.test(line)) {
        return 'text-slate-400';
    }
    return 'text-slate-300';
};

// Polling setup
watch(autoRefresh, (enabled) => {
    if (enabled) {
        startPolling();
    } else {
        stopPolling();
    }
});

const startPolling = () => {
    stopPolling();
    refreshTimer = setInterval(() => {
        fetchLogs(true);
    }, 5000);
};

const stopPolling = () => {
    if (refreshTimer) {
        clearInterval(refreshTimer);
        refreshTimer = null;
    }
};

onMounted(() => {
    scrollToBottom();
    if (autoRefresh.value) {
        startPolling();
    }
});

onUnmounted(() => {
    stopPolling();
    clearTimeout(searchTimeout);
});
</script>

<template>
    <AppLayout title="Execution & System Logs">
        <Head title="Execution & System Logs - Expressa Assist" />

        <div class="space-y-4">
            <!-- Top Header & Primary Controls -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 p-5 rounded-2xl bg-white dark:bg-[#111726] border border-gray-200 dark:border-[#1a2337] shadow-xs">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-2xl bg-[#435ebe]/10 dark:bg-[#435ebe]/20 text-[#435ebe] dark:text-blue-400 flex items-center justify-center font-bold text-lg shadow-xs">
                        <i class="fa-solid fa-terminal"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h2 class="text-xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                                Execution Logs
                            </h2>
                            <span v-if="autoRefresh" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> LIVE
                            </span>
                            <span v-else class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-gray-500/10 text-gray-500 border border-gray-500/20">
                                PAUSED
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Real-time streaming event & activity log daemon Expressa Assist.
                        </p>
                    </div>
                </div>

                <!-- Top Right Controls (Profile, Port 9119, Live Switch, Refresh) -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Profile Switcher -->
                    <div class="flex items-center bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-[#1e2740] rounded-xl px-2.5 py-1.5 text-xs">
                        <i class="fa-solid fa-user-astronaut text-gray-400 mr-2"></i>
                        <span class="text-gray-500 font-medium mr-1.5">Profil:</span>
                        <select
                            :value="selectedProfile"
                            @change="switchProfile($event.target.value)"
                            aria-label="Pilih Profil Log"
                            class="bg-transparent font-semibold text-gray-900 dark:text-white focus:outline-none cursor-pointer text-xs"
                        >
                            <option v-for="p in profiles" :key="p.name" :value="p.name" class="bg-white dark:bg-[#111726] text-gray-900 dark:text-white">
                                {{ p.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Native Port 9119 Link -->
                    <a
                        :href="`http://127.0.0.1:9119/logs?profile=${selectedProfile}`"
                        target="_blank"
                        rel="noreferrer"
                        class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-[#151b2e] dark:hover:bg-[#1a233b] text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-[#1e2740] transition-colors inline-flex items-center gap-1.5"
                        title="Buka tampilan logs di antarmuka native Port 9119"
                    >
                        <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                        <span>Port 9119</span>
                    </a>

                    <!-- Live Auto-Refresh Toggle -->
                    <button
                        type="button"
                        @click="autoRefresh = !autoRefresh"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-semibold border transition-all inline-flex items-center gap-1.5 cursor-pointer',
                            autoRefresh
                                ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-600 dark:text-emerald-400 hover:bg-emerald-500/20'
                                : 'bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400'
                        ]"
                        title="Nyalakan/matikan auto refresh log setiap 5 detik"
                    >
                        <i :class="['fa-solid text-[10px]', autoRefresh ? 'fa-satellite-dish animate-pulse' : 'fa-pause']"></i>
                        <span>{{ autoRefresh ? 'Live Polling (5s)' : 'Polling Mati' }}</span>
                    </button>

                    <!-- Manual Refresh Button -->
                    <button
                        type="button"
                        @click="fetchLogs(false)"
                        :disabled="isRefreshing"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-[#435ebe] hover:bg-[#354bb4] text-white transition-all inline-flex items-center gap-1.5 shadow-sm disabled:opacity-50 cursor-pointer"
                        title="Segarkan data log sekarang"
                    >
                        <i :class="['fa-solid fa-rotate-right text-xs', isRefreshing ? 'fa-spin' : '']"></i>
                        <span>Refresh</span>
                    </button>
                </div>
            </div>

            <!-- Filter Toolbar (Matching 100% Native Hermes) -->
            <div class="p-4 rounded-2xl bg-white dark:bg-[#111726] border border-gray-200 dark:border-[#1a2337] space-y-3.5 shadow-xs">
                <!-- Row 1: File Switcher & Level Filter -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 text-xs">
                    <!-- Log File Selection Pills -->
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-gray-400 dark:text-gray-500 font-semibold uppercase tracking-wider text-[10px] mr-1">File:</span>
                        <button
                            v-for="file in logFiles"
                            :key="file"
                            type="button"
                            @click="selectFile(file)"
                            :class="[
                                'px-3 py-1.5 rounded-xl font-bold uppercase transition-all tracking-wide text-[11px] cursor-pointer',
                                selectedFile === file
                                    ? 'bg-[#435ebe] text-white shadow-xs'
                                    : 'bg-gray-100 dark:bg-[#151b2e] text-gray-600 dark:text-slate-300 hover:bg-gray-200 dark:hover:bg-white/10'
                            ]"
                        >
                            {{ file }}
                        </button>
                    </div>

                    <!-- Log Level Selection Pills -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-gray-400 dark:text-gray-500 font-semibold uppercase tracking-wider text-[10px] mr-1">Level:</span>
                        <button
                            v-for="lvl in levels"
                            :key="lvl"
                            type="button"
                            @click="selectLevel(lvl)"
                            :class="[
                                'px-2.5 py-1 rounded-lg font-bold text-[10px] uppercase transition-all cursor-pointer',
                                selectedLevel === lvl
                                    ? (lvl === 'ERROR' ? 'bg-rose-600 text-white shadow-xs'
                                      : (lvl === 'WARNING' ? 'bg-amber-500 text-slate-950 font-black shadow-xs'
                                      : 'bg-slate-700 text-white shadow-xs'))
                                    : 'bg-gray-100 dark:bg-[#151b2e] text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white'
                            ]"
                        >
                            {{ lvl }}
                        </button>
                    </div>
                </div>

                <!-- Row 2: Component Filter, Lines Limit, Search Box -->
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 pt-3 border-t border-gray-100 dark:border-[#1a2337] text-xs">
                    <!-- Component Filter Pills -->
                    <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="text-gray-400 dark:text-gray-500 font-semibold uppercase tracking-wider text-[10px] mr-1">Komponen:</span>
                        <button
                            v-for="comp in components"
                            :key="comp"
                            type="button"
                            @click="selectComponent(comp)"
                            :class="[
                                'px-2.5 py-1 rounded-lg font-bold text-[10px] uppercase transition-all cursor-pointer',
                                selectedComponent === comp
                                    ? 'bg-blue-600 text-white shadow-xs'
                                    : 'bg-gray-100 dark:bg-[#151b2e] text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white'
                            ]"
                        >
                            {{ comp }}
                        </button>
                    </div>

                    <!-- Right Sub-controls: Lines count, Search, Copy -->
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <!-- Lines Count Selector -->
                        <div class="flex items-center bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-[#1e2740] rounded-xl px-2 py-1 text-xs">
                            <span class="text-gray-400 text-[11px] mr-1.5 font-medium">Baris:</span>
                            <select
                                :value="selectedLines"
                                @change="selectLinesCount($event.target.value)"
                                aria-label="Batas Jumlah Baris Log"
                                class="bg-transparent font-bold text-gray-900 dark:text-white focus:outline-none cursor-pointer text-xs"
                            >
                                <option v-for="cnt in lineOptions" :key="cnt" :value="cnt" class="bg-white dark:bg-[#111726] text-gray-900 dark:text-white">
                                    {{ cnt }}
                                </option>
                            </select>
                        </div>

                        <!-- Free-text Search Input -->
                        <div class="relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[11px]"></i>
                            <input
                                v-model="searchQuery"
                                @input="onSearchInput"
                                type="text"
                                placeholder="Cari isi log..."
                                class="pl-8 pr-7 py-1.5 rounded-xl bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-[#1e2740] text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:border-[#435ebe] w-40 sm:w-52"
                            />
                            <button
                                v-if="searchQuery"
                                @click="clearSearch"
                                type="button"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-white"
                            >
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                        </div>

                        <!-- Copy Logs Button -->
                        <button
                            type="button"
                            @click="copyLogs"
                            class="px-2.5 py-1.5 rounded-xl bg-gray-100 hover:bg-gray-200 dark:bg-[#151b2e] dark:hover:bg-[#1a233b] text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-[#1e2740] transition text-xs font-semibold inline-flex items-center gap-1.5 cursor-pointer"
                            title="Salin seluruh isi log saat ini"
                        >
                            <i :class="['fa-solid', copied ? 'fa-check text-emerald-400' : 'fa-copy']"></i>
                            <span class="hidden sm:inline">{{ copied ? 'Tersalin' : 'Copy' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Terminal Log Viewer Window -->
            <div class="rounded-2xl border border-gray-300 dark:border-[#1a2337] bg-[#0c1017] shadow-xl font-mono text-xs overflow-hidden flex flex-col h-[68vh]">
                <!-- Terminal Header Bar -->
                <div class="h-10 px-4 bg-[#111726] border-b border-gray-200 dark:border-[#1a2337] flex items-center justify-between shrink-0 select-none">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-rose-500/80 inline-block"></span>
                        <span class="w-3 h-3 rounded-full bg-amber-500/80 inline-block"></span>
                        <span class="w-3 h-3 rounded-full bg-emerald-500/80 inline-block"></span>
                        <span class="ml-2 font-bold text-gray-300 text-[11px] truncate max-w-sm sm:max-w-md">
                            {{ filePath }}
                        </span>
                    </div>

                    <div class="flex items-center gap-3 text-[11px] text-gray-400">
                        <label class="flex items-center gap-1.5 cursor-pointer hover:text-gray-200">
                            <input
                                v-model="autoScroll"
                                type="checkbox"
                                class="w-3.5 h-3.5 rounded text-[#435ebe] focus:ring-0 bg-transparent border-gray-600"
                            />
                            <span>Auto Scroll</span>
                        </label>
                        <span class="text-gray-600">|</span>
                        <span class="font-bold text-gray-300">{{ logLines.length }} baris</span>
                    </div>
                </div>

                <!-- Logs Output Container -->
                <div
                    ref="logsContainer"
                    class="overflow-y-auto flex-1 p-4 font-mono text-[11.5px] leading-relaxed divide-y divide-white/[0.03] select-text"
                >
                    <template v-if="logLines.length > 0">
                        <div
                            v-for="(line, idx) in logLines"
                            :key="idx"
                            :class="[
                                'py-1 px-2 rounded-sm transition-colors whitespace-pre-wrap font-mono break-all flex items-start gap-3',
                                getLineStyle(line)
                            ]"
                        >
                            <!-- Line Number -->
                            <span class="text-gray-600 dark:text-gray-600 select-none shrink-0 text-[10px] w-8 text-right font-mono">
                                {{ idx + 1 }}
                            </span>
                            <!-- Line Text -->
                            <div class="flex-1 font-mono">
                                {{ line }}
                            </div>
                        </div>
                    </template>

                    <!-- Empty State -->
                    <div v-else class="h-full flex flex-col items-center justify-center text-center p-8 text-gray-500 space-y-2">
                        <i class="fa-solid fa-file-circle-xmark text-4xl text-gray-600 mb-1"></i>
                        <p class="text-sm font-semibold text-gray-400">
                            Tidak ada baris log yang sesuai dengan filter.
                        </p>
                        <p class="text-xs text-gray-500">
                            Coba ubah pilihan level, komponen, atau reset kata kunci pencarian.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>