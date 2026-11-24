<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfigSubnav from '../../components/ConfigSubnav.vue';

const props = defineProps({
    webhooks: {
        type: Object,
        default: () => ({ enabled: false, routes: [] }),
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
    router.get('/webhooks', { profile: pName }, { preserveState: false, preserveScroll: true });
};
</script>

<template>
    <AppLayout title="Webhooks & Event Triggers">
        <ConfigSubnav />
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 dark:bg-indigo-500/20 text-indigo-500 flex items-center justify-center font-bold text-lg">
                        <i class="fa-solid fa-webhook"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                            Webhooks & Inbound Triggers
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Terima payload HTTP POST dari GitHub, Stripe, Jira, atau bot eksternal untuk memicu tugas otomatis agent.
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
                        :href="`http://127.0.0.1:9119/webhooks?profile=${selectedProfile}`"
                        target="_blank"
                        rel="noreferrer"
                        class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 transition-colors inline-flex items-center gap-1.5"
                    >
                        <i class="fa-solid fa-up-right-from-square text-[10px]"></i>
                        Port 9119
                    </a>
                </div>
            </div>

            <!-- Webhook Routes Card -->
            <div class="p-6 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-satellite-dish text-indigo-500"></i>
                        Endpoint Webhook Masuk
                    </h3>
                    <span class="px-2.5 py-1 text-xs font-semibold rounded-lg" :class="webhooks.enabled ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-500 dark:bg-gray-800'">
                        {{ webhooks.enabled ? 'Listener Aktif' : 'Listener Nonaktif' }}
                    </span>
                </div>

                <div v-if="!webhooks.routes || webhooks.routes.length === 0" class="text-center py-8 text-xs text-gray-400">
                    <i class="fa-solid fa-bell-slash text-3xl mb-2 text-gray-300 dark:text-gray-600"></i>
                    <p>Belum ada rute webhook khusus yang didaftarkan.</p>
                </div>

                <div v-else class="space-y-3">
                    <div v-for="r in webhooks.routes" :key="r.path" class="p-4 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-[#151521] flex items-center justify-between">
                        <div>
                            <span class="font-bold text-xs font-mono text-indigo-600 dark:text-indigo-400">{{ r.path }}</span>
                            <p class="text-xs text-gray-500 mt-1">{{ r.action }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
