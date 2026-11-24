<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import SystemSubnav from '../../components/SystemSubnav.vue';

const props = defineProps({
    logs: {
        type: Array,
        default: () => [],
    },
    currentProfile: {
        type: String,
        default: 'default',
    },
});

const isRefreshing = ref(false);
const liveLogs = ref([...props.logs]);

const refreshLogs = async () => {
    isRefreshing.value = true;
    try {
        const res = await fetch('/api/gateway/logs');
        if (res.ok) {
            const data = await res.json();
            liveLogs.value = data.logs || [];
        }
    } catch (e) {
        console.error('Error refreshing logs', e);
    } finally {
        isRefreshing.value = false;
    }
};
</script>

<template>
    <AppLayout title="Gateway & System Logs">
        <SystemSubnav />
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gray-500/10 dark:bg-gray-500/20 text-gray-400 flex items-center justify-center font-bold text-lg">
                        <i class="fa-solid fa-terminal"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                            Live Gateway & System Logs
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Streaming log event gateway.log secara real-time.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        @click="refreshLogs"
                        :disabled="isRefreshing"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-[#435ebe] hover:bg-[#354bb4] text-white transition-colors inline-flex items-center gap-1.5 shadow-sm"
                    >
                        <i class="fa-solid fa-arrows-rotate" :class="{ 'animate-spin': isRefreshing }"></i>
                        <span>Refresh Log</span>
                    </button>
                </div>
            </div>

            <!-- Terminal Log Window -->
            <div class="rounded-2xl border border-gray-800 bg-[#0d1117] p-5 shadow-2xl font-mono text-xs overflow-hidden flex flex-col h-[70vh]">
                <div class="flex items-center justify-between pb-3 border-b border-gray-800 mb-3 text-gray-400 text-[11px]">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-red-500/80 inline-block"></span>
                        <span class="w-3 h-3 rounded-full bg-amber-500/80 inline-block"></span>
                        <span class="w-3 h-3 rounded-full bg-emerald-500/80 inline-block"></span>
                        <span class="ml-2 font-bold text-gray-300">E:\hermes\gateway.log</span>
                    </div>
                    <span>{{ liveLogs.length }} baris</span>
                </div>

                <div class="overflow-y-auto flex-1 space-y-1 text-gray-300 font-mono text-xs leading-relaxed">
                    <div v-for="(line, idx) in liveLogs" :key="idx" class="whitespace-pre-wrap hover:bg-white/5 py-0.5 px-1 rounded">
                        {{ line }}
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
