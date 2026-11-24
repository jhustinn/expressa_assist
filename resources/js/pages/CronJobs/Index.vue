<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';

const props = defineProps({
    jobs: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
});

const searchQuery = ref('');

const filteredJobs = computed(() => {
    return props.jobs.filter((j) => {
        const q = searchQuery.value.trim().toLowerCase();
        return !q || (j.name || '').toLowerCase().includes(q) || (j.schedule || '').toLowerCase().includes(q);
    });
});

const triggerJob = (job) => {
    router.post(`/cron-jobs/${job.id}/run`, {}, {
        preserveScroll: true,
    });
};

const toggleJob = (job) => {
    router.post(`/cron-jobs/${job.id}/toggle`, {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout title="Cron Jobs & Scheduler">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                        Cron Jobs & Scheduled Tasks
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Jadwal eksekusi berkala Expressa Assist (daily sweep, health checks, Telegram digest).
                    </p>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div class="p-4 rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Total Cron Jobs</span>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.total || 0 }}</div>
                </div>
                <div class="p-4 rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Scheduled (Active)</span>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ stats.scheduled || 0 }}</div>
                </div>
                <div class="p-4 rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Paused</span>
                    <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ stats.paused || 0 }}</div>
                </div>
            </div>

            <!-- Jobs Table -->
            <div class="rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 overflow-hidden shadow-xs">
                <div class="p-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-black/10 flex items-center justify-between">
                    <div class="relative w-72">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari job nama atau cron..."
                            class="w-full pl-9 pr-8 py-2 rounded-xl text-xs bg-white dark:bg-[#151521] border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:border-[#435ebe] outline-none transition"
                        />
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-black/20 text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <th class="px-6 py-3.5">JOB NAME</th>
                                <th class="px-6 py-3.5">SCHEDULE</th>
                                <th class="px-6 py-3.5">NEXT RUN</th>
                                <th class="px-6 py-3.5">TARGET</th>
                                <th class="px-6 py-3.5">STATUS</th>
                                <th class="px-6 py-3.5">BOUND SKILLS</th>
                                <th class="px-6 py-3.5 text-right">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800/60">
                            <tr
                                v-for="job in filteredJobs"
                                :key="job.id"
                                class="hover:bg-gray-50/70 dark:hover:bg-white/[0.02] transition"
                            >
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-900 dark:text-white text-sm">
                                        {{ job.name }}
                                    </div>
                                    <span class="text-xs font-mono text-gray-400 block mt-0.5">{{ job.id }}</span>
                                </td>

                                <td class="px-6 py-4">
                                    <code class="px-2.5 py-1 rounded-md text-xs font-mono bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-[#435ebe] dark:text-blue-300">
                                        {{ job.schedule }}
                                    </code>
                                </td>

                                <td class="px-6 py-4 text-xs text-gray-500 dark:text-gray-400">
                                    {{ job.nextRun }}
                                </td>

                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 capitalize">
                                        <i :class="job.deliveryTarget === 'telegram' ? 'fa-brands fa-telegram text-cyan-500' : 'fa-solid fa-server text-gray-400'"></i>
                                        {{ job.deliveryTarget }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <span
                                        :class="[
                                            'px-2.5 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-1.5 border',
                                            job.state === 'scheduled'
                                                ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-500/30'
                                                : 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-500/30'
                                        ]"
                                    >
                                        <i :class="job.state === 'scheduled' ? 'fa-solid fa-circle-check text-[10px]' : 'fa-solid fa-circle-pause text-[10px]'"></i>
                                        {{ job.state }}
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="sk in job.skills"
                                            :key="sk"
                                            class="px-2 py-0.5 rounded text-[11px] font-mono bg-blue-50 text-[#435ebe] dark:bg-blue-950/50 dark:text-blue-300 border border-blue-200 dark:border-blue-500/30"
                                        >
                                            {{ sk }}
                                        </span>
                                        <span v-if="!job.skills || job.skills.length === 0" class="text-xs text-gray-400">
                                            -
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="triggerJob(job)"
                                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-[#435ebe] text-white hover:bg-[#3950a2] transition inline-flex items-center gap-1.5 shadow-xs"
                                            title="Jalankan di tick berikutnya"
                                        >
                                            <i class="fa-solid fa-play text-[10px]"></i> Trigger
                                        </button>
                                        <button
                                            @click="toggleJob(job)"
                                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition"
                                        >
                                            <i :class="job.state === 'scheduled' ? 'fa-solid fa-pause' : 'fa-solid fa-play'"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
