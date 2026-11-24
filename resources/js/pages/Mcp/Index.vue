<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';

const props = defineProps({
    servers: {
        type: Array,
        default: () => [],
    },
    catalog: {
        type: Array,
        default: () => [],
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
    router.get('/mcp', { profile: pName }, { preserveState: false, preserveScroll: true });
};
</script>

<template>
    <AppLayout title="Model Context Protocol (MCP)">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 dark:bg-amber-500/20 text-amber-500 flex items-center justify-center font-bold text-lg">
                        <i class="fa-solid fa-plug"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                            MCP Servers & Connectors
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Integrasi Model Context Protocol (MCP) untuk menghubungkan tools eksternal, basis data, dan API.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <div class="flex items-center bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700/80 rounded-xl px-2.5 py-1.5 text-xs">
                        <i class="fa-solid fa-user-gear text-gray-400 mr-2"></i>
                        <span class="text-gray-500 font-medium mr-1.5">Profil:</span>
                        <select
                            :value="selectedProfile"
                            @change="switchProfile($event.target.value)"
                            aria-label="Pilih Profil Expressa"
                            class="bg-transparent font-semibold text-gray-900 dark:text-white focus:outline-none cursor-pointer"
                        >
                            <option v-for="p in profiles" :key="p.name" :value="p.name" class="bg-white dark:bg-[#1e1e2d] text-gray-900 dark:text-white">
                                {{ p.name }}
                            </option>
                        </select>
                    </div>

                    <a
                        :href="`http://127.0.0.1:9119/mcp?profile=${selectedProfile}`"
                        target="_blank"
                        rel="noreferrer"
                        class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 transition-colors inline-flex items-center gap-1.5"
                    >
                        <i class="fa-solid fa-up-right-from-square text-[10px]"></i>
                        Port 9119
                    </a>
                </div>
            </div>

            <!-- Installed MCP Servers -->
            <div class="p-6 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-server text-emerald-500"></i>
                    Server MCP Terpasang ({{ servers.length }})
                </h3>

                <div v-if="servers.length === 0" class="text-center py-8 text-xs text-gray-400">
                    <i class="fa-solid fa-plug-circle-xmark text-3xl mb-2 text-gray-300 dark:text-gray-600"></i>
                    <p>Belum ada MCP Server yang dikonfigurasi di profil ini.</p>
                </div>

                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="s in servers"
                        :key="s.name"
                        class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-[#151521]"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-sm text-gray-900 dark:text-white">{{ s.name }}</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                {{ s.transport || 'stdio' }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 line-clamp-2">{{ s.description || s.command }}</p>
                    </div>
                </div>
            </div>

            <!-- Official Catalog -->
            <div class="p-6 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-2">
                    Katalog MCP Resmi
                </h3>
                <p class="text-xs text-gray-400 mb-4">
                    Konektor MCP populer yang dapat dipasang ke Expressa Assist.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                    <div v-for="entry in catalog.slice(0, 9)" :key="entry.name" class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-[#151521]/50">
                        <div class="font-bold text-xs text-gray-900 dark:text-white">{{ entry.name }}</div>
                        <div class="text-[11px] text-gray-400 mt-1 line-clamp-2">{{ entry.description }}</div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
