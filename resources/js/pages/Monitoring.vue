<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../layouts/AppLayout.vue';
import axios from 'axios';

const props = defineProps({
    status: Object,
    logs: Array,
    activityLogs: Array,
});

const currentStatus = ref(props.status);
const currentLogs = ref(props.logs || []);
const loading = ref(false);
const autoScroll = ref(true);
let pollTimer = null;

const scrollToBottom = () => {
    const el = document.getElementById('log-terminal');
    if (el) el.scrollTop = el.scrollHeight;
};

const fetchStatusAndLogs = async () => {
    try {
        const [statusRes, logsRes] = await Promise.all([
            axios.get('/api/gateway/status'),
            axios.get('/api/gateway/logs'),
        ]);
        currentStatus.value = statusRes.data;
        currentLogs.value = logsRes.data.logs || [];
        if (autoScroll.value) {
            nextTick(() => scrollToBottom());
        }
    } catch (e) {}
};

const triggerAction = async (action) => {
    loading.value = true;
    try {
        await axios.post('/monitoring/action', { action });
        await fetchStatusAndLogs();
    } catch (err) {
        alert('Gagal mengeksekusi aksi: ' + (err.response?.data?.message || err.message));
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    scrollToBottom();
    pollTimer = setInterval(fetchStatusAndLogs, 3000);
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});
</script>

<template>
    <AppLayout>
        <Head title="Live Gateway Console & Monitoring" />

        <div class="space-y-6">
            
            <!-- Gateway Control Hero Card -->
            <div class="bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        <div
                            :class="[
                                'w-14 h-14 rounded-2xl flex items-center justify-center text-2xl transition shadow-lg shrink-0',
                                currentStatus.running
                                    ? 'bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 shadow-emerald-500/10'
                                    : 'bg-rose-500/10 text-rose-500 border border-rose-500/20'
                            ]"
                        >
                            <i class="fa-solid fa-satellite-dish"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2.5">
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">Expressa Gateway Process</h3>
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold',
                                        currentStatus.running
                                            ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20'
                                            : 'bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20'
                                    ]"
                                >
                                    <span :class="['w-1.5 h-1.5 rounded-full', currentStatus.running ? 'bg-emerald-500 animate-pulse' : 'bg-rose-500']"></span>
                                    <span>{{ currentStatus.status }}</span>
                                </span>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                PID: <strong class="font-mono text-gray-800 dark:text-gray-200">{{ currentStatus.pid || 'N/A' }}</strong> &bull; 
                                Binary: <code class="font-mono text-gray-500">E:\hermes\bin\hermes.exe gateway run</code>
                            </p>
                        </div>
                    </div>

                    <!-- Action Controls -->
                    <div class="flex items-center gap-3">
                        <button
                            @click="triggerAction('start')"
                            :disabled="currentStatus.running || loading"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-500 disabled:opacity-40 disabled:pointer-events-none text-white transition flex items-center gap-2 shadow-sm"
                        >
                            <i class="fa-solid fa-play"></i>
                            <span>Start Gateway</span>
                        </button>

                        <button
                            @click="triggerAction('restart')"
                            :disabled="loading"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#364b98] disabled:opacity-50 text-white transition flex items-center gap-2 shadow-sm"
                        >
                            <i :class="['fa-solid fa-rotate-right', loading ? 'fa-spin' : '']"></i>
                            <span>Restart</span>
                        </button>

                        <button
                            @click="triggerAction('stop')"
                            :disabled="!currentStatus.running || loading"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold bg-rose-500/10 hover:bg-rose-500/20 text-rose-500 border border-rose-500/20 disabled:opacity-40 disabled:pointer-events-none transition flex items-center gap-2"
                        >
                            <i class="fa-solid fa-stop"></i>
                            <span>Stop</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Terminal Live Console -->
            <div class="bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-xs flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-terminal text-emerald-500 text-base"></i>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">Live Gateway Log Console (E:\hermes\gateway.log)</h4>
                    </div>

                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 cursor-pointer select-none">
                            <input v-model="autoScroll" type="checkbox" class="rounded text-[#435ebe] focus:ring-0">
                            <span>Auto-scroll</span>
                        </label>
                        <button
                            @click="fetchStatusAndLogs"
                            class="p-1.5 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:text-[#435ebe] transition"
                            title="Refresh Logs"
                        >
                            <i class="fa-solid fa-arrows-rotate text-xs"></i>
                        </button>
                    </div>
                </div>

                <div
                    id="log-terminal"
                    class="bg-gray-900 dark:bg-black/90 border border-gray-800 rounded-xl p-4 font-mono text-xs text-gray-200 overflow-y-auto h-[480px] space-y-1.5 leading-relaxed"
                >
                    <div v-for="(line, idx) in currentLogs" :key="idx" class="hover:bg-white/5 px-1 rounded break-all">
                        <span class="text-emerald-400 font-bold select-none mr-2">&gt;</span>
                        <span>{{ line }}</span>
                    </div>
                    <div v-if="currentLogs.length === 0" class="text-gray-500 py-10 text-center">
                        Belum ada aktivitas di log. Jalankan gateway untuk mulai memantau aktivitas bot.
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
