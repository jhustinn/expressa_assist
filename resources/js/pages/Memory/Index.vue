<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';

const props = defineProps({
    memory: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    type: 'agent',
    entry: '',
});

const submitMemory = () => {
    form.post('/memory', {
        onSuccess: () => {
            form.reset('entry');
        },
    });
};

const getUsagePercent = (used, limit) => {
    if (!limit) return 0;
    return Math.min(100, Math.round((used / limit) * 100));
};
</script>

<template>
    <AppLayout title="Memory & Knowledge">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                        Memory & Context Retention
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Memori persisten Expressa Assist (kebiasaan user, preferensi teknis, fakta permanen antar sesi).
                    </p>
                </div>
            </div>

            <!-- Memory Dual Grid: Agent Memory vs User Profile -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Agent Memory Panel -->
                <div class="rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-800">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-[#435ebe]/20 text-[#435ebe] dark:text-blue-400 flex items-center justify-center text-lg">
                                    <i class="fa-solid fa-brain"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-gray-900 dark:text-white">Agent Memory</h3>
                                    <span class="text-xs font-mono text-gray-400">MEMORY.md</span>
                                </div>
                            </div>
                            <span class="text-xs font-mono font-semibold text-gray-700 dark:text-gray-300">
                                {{ memory.agentMemory.used }} / {{ memory.agentMemory.limit }} chars
                            </span>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mt-4">
                            <div class="w-full bg-gray-100 dark:bg-gray-800 h-2 rounded-full overflow-hidden">
                                <div
                                    class="bg-[#435ebe] h-full rounded-full transition-all duration-500"
                                    :style="{ width: getUsagePercent(memory.agentMemory.used, memory.agentMemory.limit) + '%' }"
                                ></div>
                            </div>
                            <div class="flex justify-between items-center text-[11px] text-gray-500 mt-1">
                                <span>Kapasitas Tersedia</span>
                                <span>{{ getUsagePercent(memory.agentMemory.used, memory.agentMemory.limit) }}% terpakai</span>
                            </div>
                        </div>

                        <!-- Memory Entries List -->
                        <div class="mt-5 space-y-2.5">
                            <div
                                v-for="(entry, idx) in memory.agentMemory.content"
                                :key="idx"
                                class="p-3 rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-800 text-xs text-gray-800 dark:text-gray-200 flex items-start gap-2.5"
                            >
                                <i class="fa-solid fa-circle-check text-emerald-500 text-xs mt-0.5 shrink-0"></i>
                                <span class="leading-relaxed">{{ entry }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Profile Memory Panel -->
                <div class="rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 p-6 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-800">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-950/40 text-purple-600 dark:text-purple-400 flex items-center justify-center text-lg">
                                    <i class="fa-solid fa-user-tag"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-base text-gray-900 dark:text-white">User Profile</h3>
                                    <span class="text-xs font-mono text-gray-400">USER.md</span>
                                </div>
                            </div>
                            <span class="text-xs font-mono font-semibold text-gray-700 dark:text-gray-300">
                                {{ memory.userProfile.used }} / {{ memory.userProfile.limit }} chars
                            </span>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mt-4">
                            <div class="w-full bg-gray-100 dark:bg-gray-800 h-2 rounded-full overflow-hidden">
                                <div
                                    class="bg-purple-600 h-full rounded-full transition-all duration-500"
                                    :style="{ width: getUsagePercent(memory.userProfile.used, memory.userProfile.limit) + '%' }"
                                ></div>
                            </div>
                            <div class="flex justify-between items-center text-[11px] text-gray-500 mt-1">
                                <span>Kapasitas Tersedia</span>
                                <span>{{ getUsagePercent(memory.userProfile.used, memory.userProfile.limit) }}% terpakai</span>
                            </div>
                        </div>

                        <!-- Memory Entries List -->
                        <div class="mt-5 space-y-2.5">
                            <div
                                v-for="(entry, idx) in memory.userProfile.content"
                                :key="idx"
                                class="p-3 rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-800 text-xs text-gray-800 dark:text-gray-200 flex items-start gap-2.5"
                            >
                                <i class="fa-solid fa-user text-purple-500 text-xs mt-0.5 shrink-0"></i>
                                <span class="leading-relaxed">{{ entry }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Append Memory Form -->
            <div class="rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 p-6 shadow-xs">
                <h3 class="font-bold text-base text-gray-900 dark:text-white mb-1">
                    Tambah Fakta / Memori Baru Secara Manual
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                    Tulis fakta deklaratif permanen (misal: "User selalu ingin format laporan dalam bahasa Indonesia").
                </p>

                <form @submit.prevent="submitMemory" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                        <div class="sm:col-span-1">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                Target File
                            </label>
                            <select
                                v-model="form.type"
                                class="w-full px-3 py-2 rounded-xl text-xs bg-gray-50 dark:bg-[#151521] border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white outline-none focus:border-[#435ebe]"
                            >
                                <option value="agent">Agent Memory (MEMORY.md)</option>
                                <option value="user">User Profile (USER.md)</option>
                            </select>
                        </div>

                        <div class="sm:col-span-3">
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                                Fakta / Entri Memori
                            </label>
                            <input
                                v-model="form.entry"
                                type="text"
                                placeholder="Contoh: User menyukai ringkasan poin-poin singkat tanpa prolog AI basa-basi"
                                class="w-full px-3.5 py-2 rounded-xl text-xs bg-gray-50 dark:bg-[#151521] border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 outline-none focus:border-[#435ebe]"
                                required
                            />
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button
                            type="submit"
                            :disabled="form.processing || !form.entry.trim()"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-[#435ebe] text-white hover:bg-[#3950a2] disabled:opacity-50 transition shadow-xs flex items-center gap-2"
                        >
                            <i class="fa-solid fa-floppy-disk"></i> Simpan ke Memori
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
