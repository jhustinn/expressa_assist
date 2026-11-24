<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfigSubnav from '../../components/ConfigSubnav.vue';

const props = defineProps({
    platforms: {
        type: Array,
        default: () => [],
    },
    envPath: {
        type: String,
        default: '',
    },
    startCommand: {
        type: String,
        default: '',
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
    router.get('/channels', { profile: pName }, { preserveState: false, preserveScroll: true });
};

const searchQuery = ref('');
const statusFilter = ref('all');
const togglingMap = ref({});
const testingMap = ref({});
const testResults = ref({});

// Edit Modal
const activeModalPlatform = ref(null);
const editingEnv = ref({});
const isSaving = ref(false);

const filteredPlatforms = computed(() => {
    return props.platforms.filter((p) => {
        const matchesStatus = statusFilter.value === 'all' ||
            (statusFilter.value === 'connected' && p.state === 'connected') ||
            (statusFilter.value === 'enabled' && p.enabled) ||
            (statusFilter.value === 'disabled' && !p.enabled);

        const q = searchQuery.value.trim().toLowerCase();
        const matchesQuery = !q ||
            (p.name || '').toLowerCase().includes(q) ||
            (p.id || '').toLowerCase().includes(q);

        return matchesStatus && matchesQuery;
    });
});

const connectedCount = computed(() => props.platforms.filter(p => p.state === 'connected').length);
const enabledCount = computed(() => props.platforms.filter(p => p.enabled).length);

const handleTogglePlatform = async (platform) => {
    togglingMap.value[platform.id] = true;
    const targetState = !platform.enabled;
    try {
        const res = await fetch(`/api/channels/${platform.id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ enabled: targetState, profile: selectedProfile.value })
        });
        if (res.ok) {
            platform.enabled = targetState;
            platform.state = targetState ? (platform.state === 'disabled' ? 'connected' : platform.state) : 'disabled';
        }
    } catch (e) {
        console.error('Error toggling channel', e);
    } finally {
        delete togglingMap.value[platform.id];
    }
};

const handleTestConnection = async (platform) => {
    testingMap.value[platform.id] = true;
    testResults.value[platform.id] = null;
    try {
        const res = await fetch(`/api/channels/${platform.id}/test`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ profile: selectedProfile.value })
        });
        if (res.ok) {
            const data = await res.json();
            testResults.value[platform.id] = data;
        }
    } catch (e) {
        testResults.value[platform.id] = { ok: false, message: 'Koneksi gagal: ' + e.message };
    } finally {
        delete testingMap.value[platform.id];
    }
};

const openConfigModal = (platform) => {
    activeModalPlatform.value = platform;
    const envObj = {};
    if (platform.env_vars) {
        platform.env_vars.forEach(v => {
            envObj[v.key] = v.value || '';
        });
    }
    editingEnv.value = envObj;
};

const savePlatformConfig = async () => {
    if (!activeModalPlatform.value) return;
    isSaving.value = true;
    try {
        const res = await fetch(`/api/channels/${activeModalPlatform.value.id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                env: editingEnv.value,
                profile: selectedProfile.value,
            })
        });
        if (res.ok) {
            activeModalPlatform.value = null;
            router.reload({ only: ['platforms'] });
        } else {
            alert('Gagal menyimpan konfigurasi.');
        }
    } catch (e) {
        alert('Error: ' + e.message);
    } finally {
        isSaving.value = false;
    }
};

const getPlatformIcon = (id) => {
    switch (id) {
        case 'telegram': return 'fa-brands fa-telegram text-sky-500';
        case 'discord': return 'fa-brands fa-discord text-indigo-500';
        case 'slack': return 'fa-brands fa-slack text-amber-500';
        case 'whatsapp':
        case 'whatsapp_cloud': return 'fa-brands fa-whatsapp text-emerald-500';
        case 'signal': return 'fa-solid fa-comment-dots text-blue-500';
        case 'matrix': return 'fa-solid fa-hashtag text-teal-500';
        case 'teams': return 'fa-brands fa-microsoft text-indigo-600';
        case 'email': return 'fa-solid fa-envelope text-red-500';
        case 'sms': return 'fa-solid fa-phone text-emerald-600';
        case 'google_chat': return 'fa-brands fa-google text-green-500';
        case 'webhook': return 'fa-solid fa-webhook text-purple-500';
        case 'a2a': return 'fa-solid fa-network-wired text-cyan-500';
        default: return 'fa-solid fa-comments text-gray-400';
    }
};

const getStateBadge = (state) => {
    switch (state) {
        case 'connected':
            return { label: 'Terhubung', color: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' };
        case 'disabled':
            return { label: 'Nonaktif', color: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 border-gray-200 dark:border-gray-700' };
        case 'not_configured':
            return { label: 'Belum Dikonfigurasi', color: 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200 dark:border-amber-800' };
        default:
            return { label: state, color: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border-gray-200 dark:border-gray-700' };
    }
};
</script>

<template>
    <AppLayout title="Channels & Messaging Platforms">
        <ConfigSubnav />
        <div class="space-y-6">
            <!-- Header Card -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-sky-500/10 dark:bg-sky-500/20 text-sky-500 flex items-center justify-center font-bold text-lg">
                        <i class="fa-solid fa-tower-broadcast"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                            Channels & Messaging Platforms
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Konfigurasi bot dan gateway perpesanan multi-platform (Telegram, Discord, Slack, WhatsApp, dll).
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Profile Switcher -->
                    <div class="flex items-center bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700/80 rounded-xl px-2.5 py-1.5 text-xs">
                        <i class="fa-solid fa-user-gear text-gray-400 mr-2"></i>
                        <span class="text-gray-500 dark:text-gray-400 font-medium mr-1.5">Profil:</span>
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

                    <!-- Native Link -->
                    <a
                        :href="`http://127.0.0.1:9119/channels?profile=${selectedProfile}`"
                        target="_blank"
                        rel="noreferrer"
                        class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 transition-colors inline-flex items-center gap-1.5"
                    >
                        <i class="fa-solid fa-up-right-from-square text-[10px]"></i>
                        Port 9119
                    </a>

                    <!-- Telegram RBAC shortcut -->
                    <a
                        href="/telegram"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-[#435ebe] hover:bg-[#354bb4] text-white transition-colors inline-flex items-center gap-1.5 shadow-sm"
                    >
                        <i class="fa-brands fa-telegram text-xs"></i>
                        <span>Telegram RBAC & Users</span>
                    </a>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="p-4 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Total Channel</div>
                    <div class="text-xl font-bold text-gray-900 dark:text-white mt-1">{{ platforms.length }}</div>
                </div>
                <div class="p-4 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Terhubung (Live)</div>
                    <div class="text-xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ connectedCount }}</div>
                </div>
                <div class="p-4 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Channel Aktif</div>
                    <div class="text-xl font-bold text-[#435ebe] dark:text-blue-400 mt-1">{{ enabledCount }}</div>
                </div>
                <div class="p-4 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Gateway Status</div>
                    <div class="text-sm font-bold text-emerald-600 dark:text-emerald-400 mt-1.5 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        ONLINE
                    </div>
                </div>
            </div>

            <!-- Filter Toolbar -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 p-4 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                <div class="relative w-full md:w-80">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari platform perpesanan..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700/80 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#435ebe]"
                    />
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-xs text-gray-400">Status:</span>
                    <select
                        v-model="statusFilter"
                        aria-label="Filter status platform"
                        class="px-3 py-1.5 text-xs rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none"
                    >
                        <option value="all">Semua Status</option>
                        <option value="connected">Terhubung Saja</option>
                        <option value="enabled">Aktif Saja</option>
                        <option value="disabled">Nonaktif</option>
                    </select>
                </div>
            </div>

            <!-- Platform Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="platform in filteredPlatforms"
                    :key="platform.id"
                    class="p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 flex flex-col justify-between shadow-xs hover:border-gray-300 dark:hover:border-gray-700 transition-all"
                    :class="{ 'border-emerald-500/40 dark:border-emerald-500/30': platform.state === 'connected' }"
                >
                    <div>
                        <!-- Header -->
                        <div class="flex items-start justify-between gap-3 mb-2">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-800 flex items-center justify-center text-lg">
                                    <i :class="getPlatformIcon(platform.id)"></i>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                        {{ platform.name }}
                                        <span v-if="platform.id === 'telegram'" class="px-1.5 py-0.2 rounded text-[10px] bg-sky-50 text-sky-700 font-semibold border border-sky-200">
                                            Utama
                                        </span>
                                    </h3>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold border inline-block mt-0.5" :class="getStateBadge(platform.state).color">
                                        {{ getStateBadge(platform.state).label }}
                                    </span>
                                </div>
                            </div>

                            <!-- Quick Toggle -->
                            <button
                                @click="handleTogglePlatform(platform)"
                                :disabled="togglingMap[platform.id]"
                                class="w-10 h-6 rounded-full transition-colors relative flex items-center px-0.5 cursor-pointer"
                                :class="platform.enabled ? 'bg-[#435ebe]' : 'bg-gray-300 dark:bg-gray-700'"
                                :title="platform.enabled ? 'Nonaktifkan' : 'Aktifkan'"
                            >
                                <div
                                    class="w-5 h-5 rounded-full bg-white transition-transform transform shadow-xs flex items-center justify-center text-[10px]"
                                    :class="platform.enabled ? 'translate-x-4 text-[#435ebe]' : 'translate-x-0 text-gray-400'"
                                >
                                    <i v-if="togglingMap[platform.id]" class="fa-solid fa-spinner animate-spin text-[8px]"></i>
                                </div>
                            </button>
                        </div>

                        <!-- Configured Credentials info -->
                        <div class="mt-3 space-y-1.5 text-xs text-gray-500 dark:text-gray-400">
                            <div v-if="platform.env_vars && platform.env_vars.length > 0" class="flex items-center gap-1.5 flex-wrap">
                                <span v-for="env in platform.env_vars" :key="env.key" class="px-2 py-0.5 rounded text-[10px] font-mono border" :class="env.is_set ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' : 'bg-gray-50 dark:bg-[#151521] text-gray-400 border-gray-200 dark:border-gray-800'">
                                    <i class="fa-solid fa-circle text-[5px] mr-1" :class="env.is_set ? 'text-emerald-500' : 'text-gray-400'"></i>
                                    {{ env.key }}
                                </span>
                            </div>
                            <div v-else class="text-[11px] text-gray-400 italic">
                                Belum ada variabel lingkungan terdaftar.
                            </div>
                        </div>

                        <!-- Test Connection Feedback (if tested) -->
                        <div v-if="testResults[platform.id]" class="mt-3 p-2.5 rounded-xl text-xs border" :class="testResults[platform.id].ok ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200' : 'bg-red-50 dark:bg-red-950/40 text-red-700 dark:text-red-300 border-red-200'">
                            <div class="font-semibold flex items-center gap-1.5">
                                <i :class="testResults[platform.id].ok ? 'fa-solid fa-circle-check text-emerald-500' : 'fa-solid fa-triangle-exclamation text-red-500'"></i>
                                <span>{{ testResults[platform.id].ok ? 'Koneksi Sukses' : 'Koneksi Gagal' }}</span>
                            </div>
                            <p class="text-[11px] mt-0.5 opacity-90">{{ testResults[platform.id].message }}</p>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between gap-2">
                        <button
                            @click="handleTestConnection(platform)"
                            :disabled="testingMap[platform.id]"
                            class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors inline-flex items-center gap-1.5"
                        >
                            <i v-if="testingMap[platform.id]" class="fa-solid fa-spinner animate-spin text-[10px]"></i>
                            <i v-else class="fa-solid fa-plug-circle-check text-[10px]"></i>
                            <span>Tes</span>
                        </button>

                        <button
                            @click="openConfigModal(platform)"
                            class="px-3 py-1 text-xs font-semibold rounded-lg bg-gray-50 hover:bg-gray-100 dark:bg-[#151521] dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 transition-colors inline-flex items-center gap-1.5"
                        >
                            <i class="fa-solid fa-sliders text-[10px]"></i>
                            <span>Konfigurasi</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- MODAL: CONFIGURE PLATFORM CREDENTIALS -->
            <div
                v-if="activeModalPlatform"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4"
                @click.self="activeModalPlatform = null"
            >
                <div class="w-full max-w-xl rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-2xl p-6">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-800 mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-lg">
                                <i :class="getPlatformIcon(activeModalPlatform.id)"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                    Konfigurasi {{ activeModalPlatform.name }}
                                </h3>
                                <p class="text-xs text-gray-500">Edit credential dan parameter bot di profil {{ selectedProfile }}</p>
                            </div>
                        </div>
                        <button @click="activeModalPlatform = null" class="text-gray-400 hover:text-gray-200">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <div class="space-y-4 max-h-[60vh] overflow-y-auto pr-1">
                        <div v-for="v in activeModalPlatform.env_vars" :key="v.key">
                            <label class="block text-xs font-bold font-mono text-gray-700 dark:text-gray-300 mb-1">
                                {{ v.key }}
                                <span v-if="v.is_secret" class="text-[10px] text-amber-500 font-sans font-normal ml-1">
                                    (Secret / Key)
                                </span>
                            </label>
                            <input
                                v-model="editingEnv[v.key]"
                                :type="v.is_secret ? 'password' : 'text'"
                                :placeholder="v.placeholder || `Masukkan ${v.key}...`"
                                class="w-full px-3 py-2 text-xs font-mono rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none"
                            />
                            <p v-if="v.description" class="text-[11px] text-gray-400 mt-1">{{ v.description }}</p>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-2 mt-4">
                        <button
                            @click="activeModalPlatform = null"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300"
                        >
                            Batal
                        </button>
                        <button
                            @click="savePlatformConfig"
                            :disabled="isSaving"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-[#435ebe] text-white hover:bg-[#354bb4] inline-flex items-center gap-1.5"
                        >
                            <i v-if="isSaving" class="fa-solid fa-spinner animate-spin"></i>
                            <span>Simpan Perubahan</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
