<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '../layouts/AppLayout.vue';

const props = defineProps({
    variables: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
});

const searchQuery = ref('');
const selectedCategory = ref('all');
const showRaw = ref({});
const editModalOpen = ref(false);
const activeVar = ref(null);

const form = useForm({
    key: '',
    value: '',
});

const isSyncing = ref(false);
const syncEnvToDb = () => {
    isSyncing.value = true;
    router.post('/environment/sync', {}, {
        preserveScroll: true,
        onFinish: () => {
            isSyncing.value = false;
        }
    });
};

const filteredVars = computed(() => {
    return props.variables.filter((v) => {
        const matchesCategory = selectedCategory.value === 'all' || v.category === selectedCategory.value;
        const q = searchQuery.value.trim().toLowerCase();
        const matchesQuery = !q || (
            v.key.toLowerCase().includes(q) ||
            (v.description || '').toLowerCase().includes(q)
        );
        return matchesCategory && matchesQuery;
    });
});

const toggleShow = (key) => {
    showRaw.value[key] = !showRaw.value[key];
};

const copyValue = (val) => {
    if (!val) return;
    navigator.clipboard.writeText(val);
    alert('Disalin ke clipboard!');
};

const openEdit = (v) => {
    activeVar.value = v;
    form.key = v.key;
    form.value = v.value || '';
    editModalOpen.value = true;
};

const submitEdit = () => {
    form.post('/environment', {
        onSuccess: () => {
            editModalOpen.value = false;
        },
    });
};
</script>

<template>
    <AppLayout title="Environment Variables">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                        Environment Variables
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Daftar API keys, konfigurasi runtime, dan variabel lingkungan sistem dari <code class="font-mono">E:\hermes\.env</code>.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        @click="syncEnvToDb"
                        :disabled="isSyncing"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-[#435ebe] text-white hover:bg-[#354bb4] transition inline-flex items-center gap-1.5 cursor-pointer disabled:opacity-50 shadow-xs"
                        title="Sinkronkan semua nilai .env ke tabel global.system_settings di PostgreSQL"
                    >
                        <i :class="['fa-solid text-[10px]', isSyncing ? 'fa-spinner fa-spin' : 'fa-database']"></i>
                        <span>{{ isSyncing ? 'Menyinkronkan...' : 'Sync .env ke DB' }}</span>
                    </button>
                    <span class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-500/30">
                        <i class="fa-solid fa-key mr-1.5"></i> {{ stats.configured }} / {{ stats.total }} Terkonfigurasi
                    </span>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="p-4 rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider block">Total Variabel</span>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.total }}</div>
                </div>
                <div class="p-4 rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider block">Sudah Diset (Active)</span>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ stats.configured }}</div>
                </div>
                <div class="p-4 rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <span class="text-[11px] font-semibold text-gray-500 uppercase tracking-wider block">Belum Diset (Missing)</span>
                    <div class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{{ stats.missing }}</div>
                </div>
            </div>

            <!-- Toolbar & Filter -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                <div class="relative w-full sm:w-80">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari variabel..."
                        class="w-full pl-9 pr-8 py-2 rounded-xl text-xs bg-gray-50 dark:bg-[#151521] border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:border-[#435ebe] outline-none transition"
                    />
                </div>

                <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
                    <button
                        @click="selectedCategory = 'all'"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition',
                            selectedCategory === 'all'
                                ? 'bg-[#435ebe] text-white shadow-xs'
                                : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200'
                        ]"
                    >
                        Semua
                    </button>
                    <button
                        v-for="cat in categories"
                        :key="cat"
                        @click="selectedCategory = cat"
                        :class="[
                            'px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition',
                            selectedCategory === cat
                                ? 'bg-[#435ebe] text-white shadow-xs'
                                : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 hover:bg-gray-200'
                        ]"
                    >
                        {{ cat }}
                    </button>
                </div>
            </div>

            <!-- Environment List Card -->
            <div class="rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 overflow-hidden shadow-xs">
                <div class="divide-y divide-gray-200 dark:divide-gray-800/70">
                    <div
                        v-for="v in filteredVars"
                        :key="v.key"
                        class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-gray-50/60 dark:hover:bg-white/[0.015] transition"
                    >
                        <div class="flex items-start gap-3.5">
                            <div
                                :class="[
                                    'w-2.5 h-2.5 rounded-full mt-1.5 shrink-0',
                                    v.isSet ? 'bg-emerald-500 ring-4 ring-emerald-500/20' : 'bg-gray-300 dark:bg-gray-700'
                                ]"
                            />
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-mono text-sm font-bold text-gray-900 dark:text-white">
                                        {{ v.key }}
                                    </span>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                                        {{ v.category }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    {{ v.description }}
                                </p>
                            </div>
                        </div>

                        <!-- Right: Value & Actions -->
                        <div class="flex items-center gap-3 self-end sm:self-auto">
                            <div v-if="v.isSet" class="flex items-center gap-2">
                                <span class="font-mono text-xs bg-gray-100 dark:bg-gray-800 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 max-w-xs truncate">
                                    {{ showRaw[v.key] ? v.value : v.redacted }}
                                </span>
                                <button
                                    v-if="v.isSecret"
                                    @click="toggleShow(v.key)"
                                    class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition"
                                    :title="showRaw[v.key] ? 'Sembunyikan' : 'Lihat nilai asli'"
                                >
                                    <i :class="['fa-solid text-xs', showRaw[v.key] ? 'fa-eye-slash' : 'fa-eye']"></i>
                                </button>
                                <button
                                    @click="copyValue(v.value)"
                                    class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition"
                                    title="Salin nilai"
                                >
                                    <i class="fa-solid fa-copy text-xs"></i>
                                </button>
                            </div>
                            <div v-else>
                                <span class="px-2.5 py-1 rounded text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-500 border border-gray-200 dark:border-gray-700">
                                    not set
                                </span>
                            </div>

                            <button
                                @click="openEdit(v)"
                                class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-[#435ebe] hover:text-white transition inline-flex items-center gap-1.5"
                            >
                                <i class="fa-solid fa-pen text-[10px]"></i> Edit
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Modal -->
            <div
                v-if="editModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
                @click.self="editModalOpen = false"
            >
                <div class="bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-800">
                        <div>
                            <h3 class="font-bold text-base text-gray-900 dark:text-white">
                                Edit Variabel Environment
                            </h3>
                            <span class="font-mono text-xs text-[#435ebe] font-bold">{{ form.key }}</span>
                        </div>
                        <button
                            @click="editModalOpen = false"
                            class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                        >
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitEdit" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                                Nilai Variabel
                            </label>
                            <textarea
                                v-model="form.value"
                                rows="3"
                                class="w-full px-3.5 py-2.5 rounded-xl text-xs font-mono bg-gray-50 dark:bg-[#151521] border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white outline-none focus:border-[#435ebe] leading-relaxed"
                                placeholder="Masukkan nilai variabel..."
                            ></textarea>
                            <p class="text-[11px] text-gray-400 mt-1">
                                Perubahan akan disimpan langsung ke <code class="font-mono">E:\hermes\.env</code>.
                            </p>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button
                                type="button"
                                @click="editModalOpen = false"
                                class="px-4 py-2 rounded-xl text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-4 py-2 rounded-xl text-xs font-semibold bg-[#435ebe] text-white hover:bg-[#3950a2] transition shadow-xs flex items-center gap-2"
                            >
                                <i class="fa-solid fa-floppy-disk"></i> Simpan ke .env
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
