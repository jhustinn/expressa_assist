<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../layouts/AppLayout.vue';

const props = defineProps({
    gatewayStatus: {
        type: Object,
        default: () => ({ running: false, status: 'STOPPED' }),
    },
    config: {
        type: Object,
        default: () => ({}),
    },
    env: {
        type: Object,
        default: () => ({}),
    },
    profiles: {
        type: Array,
        default: () => [],
    },
    commands: {
        type: Array,
        default: () => [],
    },
    recentLogs: {
        type: Array,
        default: () => [],
    },
    activityLogs: {
        type: Array,
        default: () => [],
    },
    overviewData: {
        type: Object,
        default: () => ({}),
    },
    recentSessions: {
        type: Array,
        default: () => [],
    },
});

const memoryPercent = computed(() => {
    if (!props.overviewData?.memory?.limit) return 0;
    return Math.min(100, Math.round((props.overviewData.memory.used / props.overviewData.memory.limit) * 100));
});

const userProfilePercent = computed(() => {
    if (!props.overviewData?.userProfile?.limit) return 0;
    return Math.min(100, Math.round((props.overviewData.userProfile.used / props.overviewData.userProfile.limit) * 100));
});

const restartGateway = () => {
    router.post('/monitoring/action', { action: 'restart' }, { preserveScroll: true });
};
</script>

<template>
    <AppLayout title="Expressa Assist Overview & Dashboard">
        <div class="space-y-6">
            <!-- Top Header & Quick Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                        Expressa Assist Control Center
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Sistem manajemen, observabilitas runtime, dan orkestrasi bot Telegram otonom.
                    </p>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        @click="restartGateway"
                        class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-[#435ebe] text-white hover:bg-[#3950a2] transition flex items-center gap-2 shadow-xs"
                    >
                        <i class="fa-solid fa-arrows-rotate"></i> Restart Gateway
                    </button>
                    <Link
                        href="/monitoring"
                        class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700 transition flex items-center gap-2"
                    >
                        <i class="fa-solid fa-chart-line text-[#435ebe]"></i> Live Monitor
                    </Link>
                </div>
            </div>

            <!-- Safe Mode & Architecture Banner -->
            <div class="p-4 rounded-2xl bg-gradient-to-r from-emerald-500/10 via-sky-500/10 to-indigo-500/10 border border-emerald-500/20 flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-500 flex items-center justify-center text-lg shrink-0">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">
                                Safe Direct API Engine Aktif
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                0% Shell Command Risk
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Eksekusi binary mentah <code class="font-mono text-rose-400">hermes.exe</code> telah dimatikan total. Inferensi AI berjalan langsung secara aman via Groq &amp; Gemini Native HTTP API.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <Link
                        href="/agent-jhustyn"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold bg-[#435ebe] text-white hover:bg-[#354bb4] transition flex items-center gap-1.5"
                    >
                        <i class="fa-solid fa-user-astronaut"></i>
                        <span>Playground Jhustyn</span>
                    </Link>
                    <Link
                        href="/files"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition flex items-center gap-1.5"
                    >
                        <i class="fa-regular fa-folder-open"></i>
                        <span>File Editor</span>
                    </Link>
                    <Link
                        href="/telegram"
                        class="px-3 py-1.5 rounded-xl text-xs font-bold bg-sky-500/10 text-sky-500 hover:bg-sky-500/20 transition flex items-center gap-1.5"
                    >
                        <i class="fa-brands fa-telegram"></i>
                        <span>Bot Simulator</span>
                    </Link>
                </div>
            </div>

            <!-- Top Stat Cards (Reference Dashboard Overview Metrics) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- 1. Model Engine Card -->
                <div class="p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Active Model</span>
                        <i class="fa-solid fa-microchip text-[#435ebe]"></i>
                    </div>
                    <div class="my-2">
                        <div class="font-mono text-sm font-bold text-gray-900 dark:text-white truncate" :title="overviewData?.model?.name">
                            {{ overviewData?.model?.name || 'gemini-3.6-flash' }}
                        </div>
                        <span class="mt-1 inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-blue-100 text-[#435ebe] dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-500/30">
                            {{ overviewData?.model?.provider || 'gemini' }}
                        </span>
                    </div>
                    <Link href="/config" class="text-[11px] text-[#435ebe] dark:text-blue-400 hover:underline font-semibold flex items-center gap-1">
                        Konfigurasi Model <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </Link>
                </div>

                <!-- 2. Sessions Card -->
                <div class="p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Conversations</span>
                        <i class="fa-solid fa-comments text-cyan-600"></i>
                    </div>
                    <div class="my-2">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ overviewData?.sessions?.total || 0 }}
                        </div>
                        <span class="text-xs text-emerald-600 dark:text-emerald-400 font-medium">
                            {{ overviewData?.sessions?.active || 1 }} active session
                        </span>
                    </div>
                    <Link href="/sessions" class="text-[11px] text-[#435ebe] dark:text-blue-400 hover:underline font-semibold flex items-center gap-1">
                        Lihat Semua Riwayat <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </Link>
                </div>

                <!-- 3. Skills Installed -->
                <div class="p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Skills Registry</span>
                        <i class="fa-solid fa-puzzle-piece text-purple-600"></i>
                    </div>
                    <div class="my-2">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ overviewData?.skills?.count || 45 }}
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ overviewData?.skills?.categories || 12 }} categories terpasang
                        </span>
                    </div>
                    <Link href="/skills" class="text-[11px] text-[#435ebe] dark:text-blue-400 hover:underline font-semibold flex items-center gap-1">
                        Jelajahi Kemampuan <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </Link>
                </div>

                <!-- 4. Cron Jobs -->
                <div class="p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Cron Tasks</span>
                        <i class="fa-solid fa-clock text-amber-600"></i>
                    </div>
                    <div class="my-2">
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            {{ overviewData?.cronJobs?.scheduled || 0 }} scheduled
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ overviewData?.cronJobs?.paused || 0 }} paused
                        </span>
                    </div>
                    <Link href="/cron-jobs" class="text-[11px] text-[#435ebe] dark:text-blue-400 hover:underline font-semibold flex items-center gap-1">
                        Kelola Jadwal <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </Link>
                </div>
            </div>

            <!-- Middle Row: Memory Usage & System Data Files -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                <!-- Agent Memory Progress -->
                <div class="p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-brain text-[#435ebe]"></i>
                            <h3 class="font-bold text-sm text-gray-900 dark:text-white">Agent Memory</h3>
                        </div>
                        <span class="text-xs font-mono font-bold text-gray-700 dark:text-gray-300">
                            {{ memoryPercent }}%
                        </span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-800 h-2 rounded-full overflow-hidden mb-2">
                        <div
                            class="bg-[#435ebe] h-full rounded-full transition-all duration-500"
                            :style="{ width: memoryPercent + '%' }"
                        ></div>
                    </div>
                    <div class="flex items-center justify-between text-xs text-gray-500 font-mono">
                        <span>{{ overviewData?.memory?.used || 0 }} chars</span>
                        <span>limit {{ overviewData?.memory?.limit || 2200 }}</span>
                    </div>
                    <Link href="/memory" class="mt-3 text-[11px] text-[#435ebe] dark:text-blue-400 hover:underline font-semibold block">
                        Buka MEMORY.md &rarr;
                    </Link>
                </div>

                <!-- User Profile Progress -->
                <div class="p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-user-tag text-purple-600"></i>
                            <h3 class="font-bold text-sm text-gray-900 dark:text-white">User Profile</h3>
                        </div>
                        <span class="text-xs font-mono font-bold text-gray-700 dark:text-gray-300">
                            {{ userProfilePercent }}%
                        </span>
                    </div>
                    <div class="w-full bg-gray-100 dark:bg-gray-800 h-2 rounded-full overflow-hidden mb-2">
                        <div
                            class="bg-purple-600 h-full rounded-full transition-all duration-500"
                            :style="{ width: userProfilePercent + '%' }"
                        ></div>
                    </div>
                    <div class="flex items-center justify-between text-xs text-gray-500 font-mono">
                        <span>{{ overviewData?.userProfile?.used || 0 }} chars</span>
                        <span>limit {{ overviewData?.userProfile?.limit || 1375 }}</span>
                    </div>
                    <Link href="/memory" class="mt-3 text-[11px] text-[#435ebe] dark:text-blue-400 hover:underline font-semibold block">
                        Buka USER.md &rarr;
                    </Link>
                </div>

                <!-- Data Files Status Pill Grid (Reference Dashboard Feature) -->
                <div class="p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-folder-tree text-amber-500"></i>
                            <h3 class="font-bold text-sm text-gray-900 dark:text-white">System Data Files</h3>
                        </div>
                        <span class="text-[11px] font-mono text-gray-400">E:\hermes</span>
                    </div>

                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div
                            v-for="(exists, file) in overviewData?.dataFiles || {}"
                            :key="file"
                            class="flex items-center gap-1.5 p-2 rounded-lg bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-800 font-mono text-[11px]"
                        >
                            <i :class="exists ? 'fa-solid fa-circle-check text-emerald-500 text-xs' : 'fa-solid fa-circle-xmark text-gray-400 text-xs'"></i>
                            <span :class="exists ? 'text-gray-800 dark:text-gray-200 font-semibold' : 'text-gray-400'">{{ file }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Sessions List (from Reference Dashboard) -->
            <div class="rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 overflow-hidden shadow-xs">
                <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-base text-gray-900 dark:text-white">Recent Sessions</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Percakapan dan instruksi terakhir dengan Expressa Assist</p>
                    </div>
                    <Link
                        href="/sessions"
                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-[#435ebe] hover:text-white transition"
                    >
                        Lihat Semua
                    </Link>
                </div>

                <div class="divide-y divide-gray-200 dark:divide-gray-800/70">
                    <Link
                        v-for="sess in recentSessions"
                        :key="sess.id"
                        :href="`/sessions/${sess.id}`"
                        class="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-gray-50/70 dark:hover:bg-white/[0.02] transition block"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-[#435ebe]/20 text-[#435ebe] dark:text-blue-400 flex items-center justify-center shrink-0">
                                <i :class="sess.platform === 'telegram' ? 'fa-brands fa-telegram text-base' : 'fa-solid fa-terminal text-sm'"></i>
                            </div>
                            <div>
                                <div class="font-semibold text-sm text-gray-900 dark:text-white">
                                    {{ sess.title || 'Untitled Session' }}
                                </div>
                                <span class="font-mono text-xs text-gray-400">
                                    {{ sess.model }} &bull; {{ sess.id }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-4 text-xs self-end sm:self-auto">
                            <span class="font-mono px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 uppercase">
                                {{ sess.platform }}
                            </span>
                            <span class="text-gray-500 font-mono">{{ sess.messageCount || 0 }} msgs</span>
                            <span class="text-gray-400 font-mono">{{ sess.startTime ? sess.startTime.substring(0, 16).replace('T', ' ') : '-' }}</span>
                        </div>
                    </Link>

                    <div v-if="!recentSessions || recentSessions.length === 0" class="p-8 text-center text-xs text-gray-400">
                        Belum ada riwayat session aktif.
                    </div>
                </div>
            </div>

            <!-- Gateway Service Status & Recent Activity Log -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <!-- Gateway Status Box -->
                <div class="p-6 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-800">
                            <h3 class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-satellite-dish text-[#435ebe]"></i> Expressa Gateway Daemon
                            </h3>
                            <span
                                :class="[
                                    'px-2.5 py-0.5 rounded-full text-xs font-bold inline-flex items-center gap-1.5 border',
                                    gatewayStatus.running
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300'
                                        : 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300'
                                ]"
                            >
                                <span :class="['w-1.5 h-1.5 rounded-full', gatewayStatus.running ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500']"></span>
                                {{ gatewayStatus.status }}
                            </span>
                        </div>
                        <div class="mt-4 space-y-2 text-xs text-gray-600 dark:text-gray-300">
                            <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                                <span>Binary Executable:</span>
                                <span class="font-mono text-gray-800 dark:text-gray-200">E:\hermes\bin\hermes.exe</span>
                            </div>
                            <div class="flex justify-between py-1 border-b border-gray-100 dark:border-gray-800">
                                <span>Process PID:</span>
                                <span class="font-mono font-bold">{{ gatewayStatus.pid || 'Inactive' }}</span>
                            </div>
                            <div class="flex justify-between py-1">
                                <span>Telegram Whitelist Active:</span>
                                <span class="font-mono font-bold text-sky-600">{{ env?.TELEGRAM_ALLOWED_USERS || 'Semua Whitelisted' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Logs -->
                <div class="p-6 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-800 mb-3">
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fa-solid fa-clock-rotate-left text-[#435ebe]"></i> Recent Activity
                        </h3>
                        <Link href="/monitoring" class="text-xs text-[#435ebe] dark:text-blue-400 hover:underline">
                            Log Lengkap &rarr;
                        </Link>
                    </div>

                    <div class="space-y-2.5 max-h-48 overflow-y-auto">
                        <div
                            v-for="log in activityLogs"
                            :key="log.id"
                            class="p-2.5 rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-800 text-xs flex items-center justify-between"
                        >
                            <span class="text-gray-800 dark:text-gray-200 truncate mr-2">{{ log.message || log.global_activity_description }}</span>
                            <span class="text-[10px] font-mono text-gray-400 shrink-0">
                                {{ log.created_at ? log.created_at.substring(11, 16) : '' }}
                            </span>
                        </div>
                        <div v-if="!activityLogs || activityLogs.length === 0" class="text-xs text-gray-400 text-center py-4">
                            Belum ada log aktivitas.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
