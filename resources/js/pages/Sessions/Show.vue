<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';

const props = defineProps({
    session: {
        type: Object,
        required: true,
    },
});

const expandedTools = ref({});

const toggleTool = (idx) => {
    expandedTools.value[idx] = !expandedTools.value[idx];
};

const getRoleClass = (role) => {
    switch (role) {
        case 'system':
            return 'border-l-4 border-l-purple-500 bg-purple-50/40 dark:bg-purple-950/20';
        case 'user':
            return 'border-l-4 border-l-[#435ebe] bg-blue-50/40 dark:bg-blue-950/20';
        case 'assistant':
            return 'border-l-4 border-l-emerald-500 bg-emerald-50/30 dark:bg-emerald-950/20';
        case 'tool':
            return 'border-l-4 border-l-amber-500 bg-amber-50/30 dark:bg-amber-950/20';
        default:
            return 'border-l-4 border-l-gray-400 bg-gray-50 dark:bg-gray-800/40';
    }
};

const getRoleBadge = (role) => {
    switch (role) {
        case 'system':
            return 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300';
        case 'user':
            return 'bg-blue-100 text-[#435ebe] dark:bg-blue-900/40 dark:text-blue-300';
        case 'assistant':
            return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300';
        case 'tool':
            return 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
    }
};
</script>

<template>
    <AppLayout :title="`Session: ${session.title}`">
        <div class="space-y-6">
            <!-- Top Navigation & Title -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center gap-3">
                    <Link
                        href="/sessions"
                        class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 flex items-center justify-center transition border border-gray-200 dark:border-gray-700"
                        title="Kembali ke Daftar Session"
                    >
                        <i class="fa-solid fa-arrow-left text-xs"></i>
                    </Link>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                                {{ session.title }}
                            </h2>
                            <span class="px-2 py-0.5 rounded text-[11px] font-mono uppercase bg-blue-100 text-[#435ebe] dark:bg-blue-950/60 dark:text-blue-300 border border-blue-200 dark:border-blue-500/30">
                                {{ session.platform }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 font-mono mt-0.5">
                            ID: {{ session.id }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <span class="px-3 py-1.5 rounded-xl text-xs font-mono bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700">
                        <i class="fa-solid fa-microchip text-[#435ebe] mr-1"></i> {{ session.model }}
                    </span>
                </div>
            </div>

            <!-- Metadata Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="p-4 rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider block">Messages</span>
                    <div class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ session.totalMessages }}</div>
                </div>
                <div class="p-4 rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider block">Tokens Used</span>
                    <div class="text-xl font-bold text-[#435ebe] dark:text-blue-400 mt-1">{{ (session.totalTokens || 0).toLocaleString() }}</div>
                </div>
                <div class="p-4 rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider block">Estimated Cost</span>
                    <div class="text-xl font-bold text-gray-900 dark:text-white mt-1">${{ (session.cost || 0).toFixed(4) }}</div>
                </div>
                <div class="p-4 rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider block">Started At</span>
                    <div class="text-xs font-mono font-bold text-gray-900 dark:text-white mt-2 truncate">
                        {{ session.startedAt ? session.startedAt.substring(0, 19).replace('T', ' ') : '-' }}
                    </div>
                </div>
            </div>

            <!-- Conversation Inspector Thread -->
            <div class="rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 overflow-hidden shadow-xs">
                <div class="p-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-black/10 flex items-center justify-between">
                    <span class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-list-check text-[#435ebe]"></i> Conversation Turns & Tool Calls
                    </span>
                    <span class="text-xs text-gray-400 font-mono">{{ session.messages.length }} turn(s)</span>
                </div>

                <div class="p-5 space-y-4">
                    <div
                        v-for="(msg, idx) in session.messages"
                        :key="msg.id || idx"
                        :class="[
                            'p-4 rounded-xl transition border border-gray-200 dark:border-gray-800',
                            getRoleClass(msg.role)
                        ]"
                    >
                        <div class="flex items-center justify-between pb-2 mb-2 border-b border-gray-200/60 dark:border-gray-700/60 text-xs">
                            <div class="flex items-center gap-2">
                                <span :class="['px-2 py-0.5 rounded font-bold uppercase text-[10px] tracking-wider', getRoleBadge(msg.role)]">
                                    {{ msg.role }}
                                </span>
                                <span v-if="msg.toolName" class="font-mono text-[11px] text-amber-600 dark:text-amber-400 font-bold">
                                    <i class="fa-solid fa-wrench mr-1"></i> {{ msg.toolName }}
                                </span>
                            </div>
                            <span class="font-mono text-[11px] text-gray-400">
                                {{ msg.timestamp }}
                            </span>
                        </div>

                        <!-- Message Content -->
                        <div class="text-xs sm:text-sm text-gray-800 dark:text-gray-200 whitespace-pre-wrap font-sans leading-relaxed">
                            {{ msg.content }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
