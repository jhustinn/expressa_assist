<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';

const props = defineProps({
    pending: {
        type: Array,
        default: () => [],
    },
    approved: {
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
    router.get('/pairing', { profile: pName }, { preserveState: false, preserveScroll: true });
};

const approving = ref({});
const revoking = ref({});

const handleApprove = async (user) => {
    approving.value[user.user_id] = true;
    try {
        const res = await fetch('/api/pairing/approve', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                platform: user.platform,
                request_id: user.request_id,
                profile: selectedProfile.value,
            })
        });
        if (res.ok) {
            router.reload();
        }
    } catch (e) {
        alert('Gagal menyetujui: ' + e.message);
    } finally {
        delete approving.value[user.user_id];
    }
};

const handleRevoke = async (user) => {
    if (!confirm(`Cabut akses untuk user ${user.user_id}?`)) return;
    revoking.value[user.user_id] = true;
    try {
        const res = await fetch('/api/pairing/revoke', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                platform: user.platform,
                user_id: user.user_id,
                profile: selectedProfile.value,
            })
        });
        if (res.ok) {
            router.reload();
        }
    } catch (e) {
        alert('Gagal mencabut: ' + e.message);
    } finally {
        delete revoking.value[user.user_id];
    }
};
</script>

<template>
    <AppLayout title="Pairing & Device Authorizations">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-500/10 dark:bg-teal-500/20 text-teal-500 flex items-center justify-center font-bold text-lg">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                            Pairing & Device Authorizations
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Otorisasi perangkat baru, kode QR Telegram/WhatsApp, dan persetujuan user bot.
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
                        :href="`http://127.0.0.1:9119/pairing?profile=${selectedProfile}`"
                        target="_blank"
                        rel="noreferrer"
                        class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 transition-colors inline-flex items-center gap-1.5"
                    >
                        <i class="fa-solid fa-up-right-from-square text-[10px]"></i>
                        Port 9119
                    </a>
                </div>
            </div>

            <!-- Pending Requests -->
            <div class="p-6 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-clock text-amber-500"></i>
                    Permintaan Pairing Menunggu Persetujuan ({{ pending.length }})
                </h3>

                <div v-if="pending.length === 0" class="text-center py-8 text-xs text-gray-400">
                    <i class="fa-solid fa-check-double text-3xl mb-2 text-emerald-500/50"></i>
                    <p>Tidak ada permintaan pairing baru yang tertunda.</p>
                </div>

                <div v-else class="space-y-3">
                    <div v-for="user in pending" :key="user.user_id" class="p-4 rounded-xl border border-amber-200 dark:border-amber-900/50 bg-amber-50/50 dark:bg-amber-950/20 flex items-center justify-between">
                        <div>
                            <div class="font-bold text-xs text-gray-900 dark:text-white">{{ user.user_name || user.user_id }}</div>
                            <div class="text-[10px] text-gray-500 font-mono mt-0.5">{{ user.platform }} | ID: {{ user.user_id }}</div>
                        </div>
                        <button
                            @click="handleApprove(user)"
                            :disabled="approving[user.user_id]"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white inline-flex items-center gap-1"
                        >
                            <i v-if="approving[user.user_id]" class="fa-solid fa-spinner animate-spin"></i>
                            <i v-else class="fa-solid fa-check"></i>
                            <span>Setujui Akses</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Approved Users -->
            <div class="p-6 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-user-check text-emerald-500"></i>
                    User Terverifikasi & Terpasang ({{ approved.length }})
                </h3>

                <div v-if="approved.length === 0" class="text-center py-6 text-xs text-gray-400">
                    <p>Belum ada user yang terpasang lewat pairing otomatis.</p>
                </div>

                <div v-else class="space-y-2">
                    <div v-for="u in approved" :key="u.user_id" class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-[#151521] flex items-center justify-between">
                        <div>
                            <span class="font-bold text-xs text-gray-900 dark:text-white">{{ u.user_name || u.user_id }}</span>
                            <span class="text-[10px] font-mono text-gray-400 ml-2">({{ u.platform }})</span>
                        </div>
                        <button
                            @click="handleRevoke(u)"
                            :disabled="revoking[u.user_id]"
                            class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-950/40 dark:text-red-400"
                        >
                            Cabut
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
