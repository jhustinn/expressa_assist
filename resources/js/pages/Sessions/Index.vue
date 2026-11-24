<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';

const props = defineProps({
    sessions: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
});

const searchQuery = ref('');
const platformFilter = ref('all');
const perPage = ref(10);
const currentPage = ref(1);

const filteredSessions = computed(() => {
    return props.sessions.filter((session) => {
        const q = searchQuery.value.trim().toLowerCase();
        const title = (session.title || '').toLowerCase();
        const id = (session.id || '').toLowerCase();
        const model = (session.model || '').toLowerCase();
        const platform = (session.platform || '').toLowerCase();

        const matchesQuery = !q || (
            title.includes(q) ||
            id.includes(q) ||
            model.includes(q) ||
            platform.includes(q)
        );

        const matchesPlatform = platformFilter.value === 'all' || platform === platformFilter.value;

        return matchesQuery && matchesPlatform;
    });
});

const totalPages = computed(() => Math.ceil(filteredSessions.value.length / perPage.value) || 1);

const paginatedSessions = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredSessions.value.slice(start, start + perPage.value);
});

const formatTokens = (tokens) => {
    if (!tokens) return '0';
    if (tokens >= 1000000) return (tokens / 1000000).toFixed(1) + 'M';
    if (tokens >= 1000) return (tokens / 1000).toFixed(1) + 'k';
    return String(tokens);
};

const getPlatformClass = (platform) => {
    switch (platform) {
        case 'telegram':
            return 'bg-cyan-50 text-cyan-700 border-cyan-200 dark:bg-cyan-950/40 dark:text-cyan-300 dark:border-cyan-500/30';
        case 'cli':
        case 'local':
            return 'bg-blue-50 text-[#435ebe] border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-500/30';
        case 'discord':
            return 'bg-indigo-50 text-indigo-700 border-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-300 dark:border-indigo-500/30';
        default:
            return 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700';
    }
};

const getPlatformIcon = (platform) => {
    switch (platform) {
        case 'telegram':
            return 'fa-brands fa-telegram';
        case 'cli':
        case 'local':
            return 'fa-solid fa-terminal';
        case 'discord':
            return 'fa-brands fa-discord';
        default:
            return 'fa-solid fa-laptop-code';
    }
};
</script>

<template>
    <AppLayout title="Sessions & Conversations">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                        Sessions & Conversations
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Daftar riwayat sesi interaksi dengan Expressa Assist di semua platform (Telegram, CLI, Local).
                    </p>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="p-4 rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Total Sessions</span>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.total || 0 }}</div>
                    <span class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 block">Tercatat di state.db / sessions.json</span>
                </div>
                <div class="p-4 rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Active Sessions</span>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ stats.active || 0 }}</div>
                    <span class="text-[11px] text-emerald-700/80 dark:text-emerald-400/80 mt-0.5 block">Siap menerima instruksi</span>
                </div>
                <div class="p-4 rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Total Tokens</span>
                    <div class="text-2xl font-bold text-[#435ebe] dark:text-blue-400 mt-1">{{ formatTokens(stats.totalTokens) }}</div>
                    <span class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 block">Input + Output prompt tokens</span>
                </div>
                <div class="p-4 rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Estimated Cost</span>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">${{ (stats.totalCost || 0).toFixed(4) }}</div>
                    <span class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 block">Estimasi penggunaan model</span>
                </div>
            </div>

            <!-- Table Card -->
            <div class="rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 overflow-hidden shadow-xs">
                <!-- Search & Filters -->
                <div class="p-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-black/10 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2.5 flex-1">
                        <div class="relative w-full sm:w-72">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari session ID, user, model..."
                                class="w-full pl-9 pr-8 py-2 rounded-xl text-xs bg-white dark:bg-[#151521] border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:border-[#435ebe] outline-none transition"
                            />
                        </div>

                        <select
                            v-model="platformFilter"
                            class="px-3 py-2 rounded-xl text-xs bg-white dark:bg-[#151521] border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 outline-none focus:border-[#435ebe]"
                        >
                            <option value="all">Semua Platform</option>
                            <option value="telegram">Telegram</option>
                            <option value="local">Local / CLI</option>
                            <option value="discord">Discord</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-2 self-end md:self-auto text-xs text-gray-500 dark:text-gray-400">
                        <span>Tampilkan:</span>
                        <select
                            v-model="perPage"
                            class="px-2.5 py-1.5 rounded-lg text-xs bg-white dark:bg-[#151521] border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 outline-none focus:border-[#435ebe]"
                        >
                            <option :value="5">5</option>
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                        </select>
                        <span>baris</span>
                    </div>
                </div>

                <!-- Sessions Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-black/20 text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <th class="px-6 py-3.5">SESSION / TITLE</th>
                                <th class="px-6 py-3.5">PLATFORM</th>
                                <th class="px-6 py-3.5">MODEL</th>
                                <th class="px-6 py-3.5">STARTED / UPDATED</th>
                                <th class="px-6 py-3.5 text-right">TOKENS</th>
                                <th class="px-6 py-3.5 text-right">COST</th>
                                <th class="px-6 py-3.5 text-right">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800/60">
                            <tr
                                v-for="sess in paginatedSessions"
                                :key="sess.id"
                                class="hover:bg-gray-50/70 dark:hover:bg-white/[0.02] transition"
                            >
                                <td class="px-6 py-4">
                                    <Link
                                        :href="`/sessions/${sess.id}`"
                                        class="font-semibold text-gray-900 dark:text-white hover:text-[#435ebe] dark:hover:text-blue-400 transition block truncate max-w-xs"
                                    >
                                        {{ sess.title || 'Untitled Session' }}
                                    </Link>
                                    <span class="text-xs font-mono text-gray-400 block mt-0.5 truncate max-w-xs">
                                        {{ sess.id }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium border capitalize',
                                            getPlatformClass(sess.platform)
                                        ]"
                                    >
                                        <i :class="getPlatformIcon(sess.platform)"></i>
                                        {{ sess.platform }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="font-mono text-xs text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded border border-gray-200 dark:border-gray-700">
                                        {{ sess.model }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                                        {{ sess.startTime ? sess.startTime.substring(0, 16).replace('T', ' ') : '-' }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <span class="font-mono text-xs text-gray-800 dark:text-gray-200">
                                        {{ formatTokens(sess.tokensInput) }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 text-right font-mono text-xs text-gray-700 dark:text-gray-300">
                                    ${{ (sess.cost || 0).toFixed(4) }}
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <Link
                                        :href="`/sessions/${sess.id}`"
                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-[#435ebe] hover:text-white dark:hover:bg-[#435ebe] transition inline-flex items-center gap-1.5"
                                    >
                                        <i class="fa-solid fa-eye"></i> Detail
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="filteredSessions.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <i class="fa-solid fa-comments text-3xl mb-3 text-gray-300 dark:text-gray-600 block"></i>
                                    Tidak ada session yang cocok dengan filter.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="p-4 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between text-xs text-gray-500">
                    <span>Halaman {{ currentPage }} dari {{ totalPages }}</span>
                    <div class="flex items-center gap-2">
                        <button
                            :disabled="currentPage === 1"
                            @click="currentPage--"
                            class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 disabled:opacity-40 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                        >
                            Sebelumnya
                        </button>
                        <button
                            :disabled="currentPage >= totalPages"
                            @click="currentPage++"
                            class="px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 disabled:opacity-40 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                        >
                            Selanjutnya
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
