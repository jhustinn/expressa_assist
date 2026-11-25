<script setup>
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import axios from 'axios';

const props = defineProps({
    models: {
        type: Array,
        default: () => [],
    },
    agentProfiles: {
        type: Array,
        default: () => [],
    },
    providers: {
        type: Array,
        default: () => [],
    },
    modelInfo: {
        type: Object,
        default: () => ({}),
    },
    modelOptions: {
        type: Object,
        default: () => ({}),
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

// Profile switcher
const selectedProfile = ref(props.currentProfile);
const switchProfile = (pName) => {
    selectedProfile.value = pName;
    router.get('/models', { profile: pName }, { preserveState: false, preserveScroll: true });
};

// Filter provider
const selectedProviderFilter = ref('all');
const filteredModels = computed(() => {
    if (selectedProviderFilter.value === 'all') {
        return props.models;
    }
    return props.models.filter(m => m.provider === selectedProviderFilter.value);
});

// Agent assignment state
const assigningSlug = ref(null);
const assignmentSuccess = ref({});

const changeAgentModel = async (agentSlug, newModelKey) => {
    assigningSlug.value = agentSlug;
    try {
        const res = await axios.post('/api/models/assign-agent', {
            agent_slug: agentSlug,
            model_key: newModelKey,
        });

        // Update local agent model_default
        const targetAgent = props.agentProfiles.find(a => a.slug === agentSlug);
        if (targetAgent) {
            targetAgent.model_default = newModelKey;
        }

        assignmentSuccess.value[agentSlug] = res.data.message || 'Model berhasil diubah!';
        setTimeout(() => {
            delete assignmentSuccess.value[agentSlug];
        }, 4000);
    } catch (err) {
        alert('Gagal mengubah model: ' + (err.response?.data?.message || err.message));
    } finally {
        assigningSlug.value = null;
    }
};

// Modal: Tambah Model Baru
const showAddModal = ref(false);
const addForm = useForm({
    name: '',
    model_key: '',
    provider: 'groq',
    base_url: 'https://api.groq.com/openai/v1',
    api_key_env: 'GROQ_API_KEY',
    context_window: 131072,
    max_output_tokens: 8192,
    description: '',
    is_active: true,
    is_default: false,
});

const onProviderChange = () => {
    const prov = props.providers.find(p => p.id === addForm.provider);
    if (prov) {
        addForm.base_url = prov.default_base;
        addForm.api_key_env = prov.env;
    }
};

const submitAddModel = () => {
    addForm.post('/api/models/store', {
        onSuccess: () => {
            showAddModal.value = false;
            addForm.reset();
        },
    });
};

// Modal: Edit Model
const showEditModal = ref(false);
const editingModelId = ref(null);
const editForm = useForm({
    name: '',
    model_key: '',
    provider: 'groq',
    base_url: '',
    api_key_env: '',
    context_window: 128000,
    max_output_tokens: 8192,
    description: '',
    is_active: true,
    is_default: false,
});

const openEditModal = (model) => {
    editingModelId.value = model.ai_model_id;
    editForm.name = model.name;
    editForm.model_key = model.model_key;
    editForm.provider = model.provider;
    editForm.base_url = model.base_url || '';
    editForm.api_key_env = model.api_key_env || '';
    editForm.context_window = model.context_window;
    editForm.max_output_tokens = model.max_output_tokens;
    editForm.description = model.description || '';
    editForm.is_active = Boolean(model.is_active);
    editForm.is_default = Boolean(model.is_default);
    showEditModal.value = true;
};

const submitEditModel = () => {
    editForm.put(`/api/models/${editingModelId.value}`, {
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
};

const deleteModel = (model) => {
    if (confirm(`Yakin ingin menghapus model '${model.name}' (${model.model_key}) dari database?`)) {
        router.delete(`/api/models/${model.ai_model_id}`);
    }
};

// Modal: Test Model Ping
const showTestModal = ref(false);
const testingModel = ref(null);
const testPrompt = ref('Halo! Jelaskan secara singkat siapa kamu dan apa spesialisasi arsitekturmu.');
const testLoading = ref(false);
const testResponse = ref(null);

const openTestModal = (model) => {
    testingModel.value = model;
    testResponse.value = null;
    showTestModal.value = true;
};

const runModelTest = async () => {
    if (!testingModel.value) return;
    testLoading.value = true;
    testResponse.value = null;
    try {
        const res = await axios.post('/api/models/test', {
            model_key: testingModel.value.model_key,
            prompt: testPrompt.value,
        });
        testResponse.value = res.data;
    } catch (err) {
        testResponse.value = {
            status: 'error',
            message: err.response?.data?.message || err.message,
            latency_ms: err.response?.data?.latency_ms || 0,
        };
    } finally {
        testLoading.value = false;
    }
};

// Helper provider styling
const getProviderBadge = (provider) => {
    switch (provider) {
        case 'gemini':
            return 'bg-blue-500/10 text-blue-500 border-blue-500/20';
        case 'groq':
            return 'bg-amber-500/10 text-amber-500 border-amber-500/20';
        case 'openrouter':
            return 'bg-purple-500/10 text-purple-500 border-purple-500/20';
        case 'anthropic':
            return 'bg-orange-500/10 text-orange-500 border-orange-500/20';
        case 'ollama':
            return 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20';
        default:
            return 'bg-gray-500/10 text-gray-400 border-gray-500/20';
    }
};

const getAgentsUsingModel = (modelKey) => {
    return props.agentProfiles.filter(a => a.model_default === modelKey);
};
</script>

<template>
    <AppLayout title="Model & Inference Routing">
        <Head title="AI Models & Agent Routing" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 dark:bg-purple-500/20 text-purple-500 flex items-center justify-center font-bold text-lg">
                        <i class="fa-solid fa-microchip"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                                AI Models & Agent Routing
                            </h2>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/10 text-purple-500 border border-purple-500/20">
                                Database Driven
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Kelola katalog model AI, simpan ke database PostgreSQL, dan assign model berbeda untuk tiap agent (Jhustyn, Default, dll).
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <button
                        @click="showAddModal = true"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#354bb4] text-white flex items-center gap-2 shadow-xs transition"
                    >
                        <i class="fa-solid fa-plus"></i>
                        <span>Tambah Model Baru</span>
                    </button>

                    <a
                        :href="`http://127.0.0.1:9119/models?profile=${selectedProfile}`"
                        target="_blank"
                        rel="noreferrer"
                        class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 transition inline-flex items-center gap-1.5"
                    >
                        <i class="fa-solid fa-up-right-from-square text-[10px]"></i>
                        <span>Port 9119</span>
                    </a>
                </div>
            </div>

            <!-- SECTION 1: PER-AGENT MODEL ASSIGNMENT (REALTIME SWITCH) -->
            <div class="bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-5 border-b border-gray-200 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-[#435ebe]/10 text-[#435ebe] flex items-center justify-center text-base">
                            <i class="fa-solid fa-user-astronaut"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                                Model Assignment Per Agent (Ganti Model Kapan Saja)
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Tentukan model LLM mana yang mengotaki masing-masing agent secara dinamis dan tersimpan langsung di database.
                            </p>
                        </div>
                    </div>
                    <span class="text-[11px] text-gray-400 font-mono">
                        {{ agentProfiles.length }} Agent Terdaftar
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="agent in agentProfiles"
                        :key="agent.agent_profile_id"
                        class="p-5 rounded-xl bg-gray-50 dark:bg-[#141928] border border-gray-200 dark:border-gray-800/80 flex flex-col justify-between space-y-4 hover:border-[#435ebe]/50 transition"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-sm font-bold shadow-xs">
                                    <i :class="agent.slug === 'jhustyn' ? 'fa-solid fa-robot' : 'fa-solid fa-brain'"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-bold text-sm text-gray-900 dark:text-white">{{ agent.name }}</h4>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-[#435ebe]/10 text-[#435ebe]">
                                            {{ agent.slug }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ agent.role_title }}</p>
                                </div>
                            </div>

                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                {{ agent.status || 'Active' }}
                            </span>
                        </div>

                        <!-- Workspace & Project Info -->
                        <div class="text-[11px] text-gray-400 space-y-1 bg-white dark:bg-[#0f131f] p-3 rounded-lg border border-gray-100 dark:border-gray-800">
                            <div class="flex justify-between">
                                <span class="text-gray-500">Project Target:</span>
                                <span class="font-semibold text-gray-700 dark:text-gray-300 truncate max-w-[200px]">{{ agent.project_name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-500">Workspace CWD:</span>
                                <span class="font-mono text-gray-700 dark:text-gray-300 truncate max-w-[200px]">{{ agent.workspace_path }}</span>
                            </div>
                        </div>

                        <!-- Assigned Model Selector -->
                        <div class="pt-2 border-t border-gray-200 dark:border-gray-800 space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <label class="font-bold text-gray-700 dark:text-gray-300">
                                    Model yang Mengotaki Agent:
                                </label>
                                <span v-if="assigningSlug === agent.slug" class="text-xs text-[#435ebe] flex items-center gap-1 font-semibold">
                                    <i class="fa-solid fa-spinner animate-spin"></i> Menyimpan...
                                </span>
                            </div>

                            <div class="flex gap-2">
                                <select
                                    :value="agent.model_default"
                                    @change="changeAgentModel(agent.slug, $event.target.value)"
                                    :disabled="assigningSlug === agent.slug"
                                    class="flex-1 bg-white dark:bg-[#1c2236] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs font-mono font-semibold text-gray-900 dark:text-white outline-none focus:border-[#435ebe]"
                                >
                                    <option v-for="m in models" :key="m.model_key" :value="m.model_key">
                                        [{{ m.provider.toUpperCase() }}] {{ m.name }} ({{ m.model_key }})
                                    </option>
                                </select>

                                <button
                                    type="button"
                                    @click="openTestModal({ model_key: agent.model_default, name: agent.name + ' Model' })"
                                    class="px-3 py-2 rounded-xl text-xs font-bold bg-purple-500/10 hover:bg-purple-500/20 text-purple-400 border border-purple-500/20 transition flex items-center gap-1.5"
                                    title="Uji Inferensi Cepat Model Ini"
                                >
                                    <i class="fa-solid fa-bolt"></i>
                                    <span>Tes</span>
                                </button>
                            </div>

                            <div v-if="assignmentSuccess[agent.slug]" class="p-2 rounded-lg bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[11px] flex items-center gap-1.5">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>{{ assignmentSuccess[agent.slug] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION 2: DAFTAR KATALOG MODEL AI (DATABASE STORED) -->
            <div class="bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 mb-5 border-b border-gray-200 dark:border-gray-800">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                Master Katalog Model AI (Database PostgreSQL)
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-xs font-mono font-bold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
                                {{ models.length }} Model
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Daftar model yang tersedia untuk routing tugas, inferensi chat, dan eksekusi coding agent.
                        </p>
                    </div>

                    <!-- Provider Filter Buttons -->
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button
                            type="button"
                            @click="selectedProviderFilter = 'all'"
                            :class="[
                                'px-3 py-1 rounded-lg text-xs font-semibold transition',
                                selectedProviderFilter === 'all'
                                    ? 'bg-[#435ebe] text-white'
                                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-white'
                            ]"
                        >
                            Semua
                        </button>
                        <button
                            v-for="prov in providers"
                            :key="prov.id"
                            type="button"
                            @click="selectedProviderFilter = prov.id"
                            :class="[
                                'px-3 py-1 rounded-lg text-xs font-semibold transition capitalize',
                                selectedProviderFilter === prov.id
                                    ? 'bg-[#435ebe] text-white'
                                    : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:text-white'
                            ]"
                        >
                            {{ prov.name }}
                        </button>
                    </div>
                </div>

                <!-- Models Table -->
                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 dark:bg-[#161622] text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="px-4 py-3.5">Nama Model & Key</th>
                                <th class="px-4 py-3.5">Provider</th>
                                <th class="px-4 py-3.5">Context Window</th>
                                <th class="px-4 py-3.5">Max Output</th>
                                <th class="px-4 py-3.5">Digunakan Oleh</th>
                                <th class="px-4 py-3.5">Status</th>
                                <th class="px-4 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800 font-sans">
                            <tr
                                v-for="m in filteredModels"
                                :key="m.ai_model_id"
                                class="hover:bg-gray-50 dark:hover:bg-white/5 transition"
                            >
                                <td class="px-4 py-3.5">
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                                            <span>{{ m.name }}</span>
                                            <span v-if="m.is_default" class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-500/10 text-amber-500 border border-amber-500/20">
                                                Default
                                            </span>
                                        </div>
                                        <div class="font-mono text-[11px] text-gray-400 mt-0.5">
                                            {{ m.model_key }}
                                        </div>
                                        <div v-if="m.description" class="text-[10px] text-gray-500 line-clamp-1 mt-0.5">
                                            {{ m.description }}
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3.5">
                                    <span :class="['px-2.5 py-1 rounded-md text-[11px] font-bold uppercase border', getProviderBadge(m.provider)]">
                                        {{ m.provider }}
                                    </span>
                                </td>

                                <td class="px-4 py-3.5 font-mono text-gray-700 dark:text-gray-300">
                                    {{ m.context_window?.toLocaleString() }} tokens
                                </td>

                                <td class="px-4 py-3.5 font-mono text-gray-700 dark:text-gray-300">
                                    {{ m.max_output_tokens?.toLocaleString() }} tokens
                                </td>

                                <td class="px-4 py-3.5">
                                    <div class="flex flex-wrap gap-1">
                                        <template v-if="getAgentsUsingModel(m.model_key).length > 0">
                                            <span
                                                v-for="ag in getAgentsUsingModel(m.model_key)"
                                                :key="ag.agent_profile_id"
                                                class="px-2 py-0.5 rounded text-[10px] font-bold bg-[#435ebe]/10 text-[#435ebe] border border-[#435ebe]/20"
                                            >
                                                {{ ag.name }}
                                            </span>
                                        </template>
                                        <span v-else class="text-gray-500 text-[11px] italic">
                                            -
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-3.5">
                                    <span v-if="m.is_active" class="inline-flex items-center gap-1.5 text-emerald-500 text-[11px] font-bold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                    </span>
                                    <span v-else class="inline-flex items-center gap-1.5 text-gray-500 text-[11px]">
                                        <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span> Non-aktif
                                    </span>
                                </td>

                                <td class="px-4 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Test Ping Button -->
                                        <button
                                            type="button"
                                            @click="openTestModal(m)"
                                            class="p-1.5 rounded-lg text-purple-400 hover:bg-purple-500/10 transition"
                                            title="Uji Respons / Ping Model"
                                        >
                                            <i class="fa-solid fa-bolt text-xs"></i>
                                        </button>

                                        <!-- Edit Button -->
                                        <button
                                            type="button"
                                            @click="openEditModal(m)"
                                            class="p-1.5 rounded-lg text-sky-400 hover:bg-sky-500/10 transition"
                                            title="Edit Model"
                                        >
                                            <i class="fa-solid fa-pen text-xs"></i>
                                        </button>

                                        <!-- Delete Button -->
                                        <button
                                            type="button"
                                            @click="deleteModel(m)"
                                            :disabled="m.is_default"
                                            class="p-1.5 rounded-lg text-rose-400 hover:bg-rose-500/10 transition disabled:opacity-30 disabled:cursor-not-allowed"
                                            title="Hapus Model"
                                        >
                                            <i class="fa-solid fa-trash-can text-xs"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- MODAL: TAMBAH MODEL BARU -->
            <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
                <div class="bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 rounded-2xl w-full max-w-lg p-6 shadow-xl max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-[#435ebe]/10 text-[#435ebe] flex items-center justify-center">
                                <i class="fa-solid fa-microchip text-sm"></i>
                            </div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">Tambah Model AI Baru ke Database</h4>
                        </div>
                        <button @click="showAddModal = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitAddModel" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Nama Model</label>
                                <input
                                    v-model="addForm.name"
                                    type="text"
                                    placeholder="Contoh: Groq DeepSeek R1"
                                    class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white outline-none focus:border-[#435ebe]"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Provider</label>
                                <select
                                    v-model="addForm.provider"
                                    @change="onProviderChange"
                                    class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white outline-none focus:border-[#435ebe]"
                                >
                                    <option v-for="p in providers" :key="p.id" :value="p.id">
                                        {{ p.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Model Key / ID Endpoint</label>
                            <input
                                v-model="addForm.model_key"
                                type="text"
                                placeholder="Contoh: qwen/qwen3.8-27b atau gemini-2.5-pro"
                                class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs font-mono text-gray-900 dark:text-white outline-none focus:border-[#435ebe]"
                                required
                            />
                            <p class="text-[10px] text-gray-400 mt-0.5">String model yang dikirimkan ke payload API provider.</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Base URL Endpoint (Opsional)</label>
                                <input
                                    v-model="addForm.base_url"
                                    type="text"
                                    placeholder="https://api..."
                                    class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs font-mono text-gray-900 dark:text-white outline-none focus:border-[#435ebe]"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Nama Variabel ENV API Key</label>
                                <input
                                    v-model="addForm.api_key_env"
                                    type="text"
                                    placeholder="Contoh: GROQ_API_KEY"
                                    class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs font-mono text-gray-900 dark:text-white outline-none focus:border-[#435ebe]"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Context Window (Tokens)</label>
                                <input
                                    v-model="addForm.context_window"
                                    type="number"
                                    class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs font-mono text-gray-900 dark:text-white outline-none focus:border-[#435ebe]"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Max Output Tokens</label>
                                <input
                                    v-model="addForm.max_output_tokens"
                                    type="number"
                                    class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs font-mono text-gray-900 dark:text-white outline-none focus:border-[#435ebe]"
                                    required
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Deskripsi Singkat</label>
                            <textarea
                                v-model="addForm.description"
                                rows="2"
                                placeholder="Kemampuan atau spesifikasi model..."
                                class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white outline-none focus:border-[#435ebe]"
                            ></textarea>
                        </div>

                        <div class="flex items-center gap-6 pt-1">
                            <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-700 dark:text-gray-300">
                                <input type="checkbox" v-model="addForm.is_active" class="rounded text-[#435ebe]" />
                                <span>Status Aktif</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-700 dark:text-gray-300">
                                <input type="checkbox" v-model="addForm.is_default" class="rounded text-[#435ebe]" />
                                <span>Set Sebagai Default Sistem</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-200 dark:border-gray-800">
                            <button
                                type="button"
                                @click="showAddModal = false"
                                class="px-4 py-2 rounded-xl text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="addForm.processing"
                                class="px-5 py-2 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#354bb4] text-white flex items-center gap-1.5"
                            >
                                <i :class="['fa-solid fa-floppy-disk', addForm.processing ? 'fa-spin' : '']"></i>
                                <span>Simpan ke Database</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: EDIT MODEL -->
            <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
                <div class="bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 rounded-2xl w-full max-w-lg p-6 shadow-xl max-h-[90vh] overflow-y-auto">
                    <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-sky-500/10 text-sky-500 flex items-center justify-center">
                                <i class="fa-solid fa-pen text-sm"></i>
                            </div>
                            <h4 class="text-sm font-bold text-gray-900 dark:text-white">Edit Konfigurasi Model AI</h4>
                        </div>
                        <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitEditModel" class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Nama Model</label>
                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white outline-none focus:border-[#435ebe]"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Provider</label>
                                <select
                                    v-model="editForm.provider"
                                    class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white outline-none focus:border-[#435ebe]"
                                >
                                    <option v-for="p in providers" :key="p.id" :value="p.id">
                                        {{ p.name }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Model Key / ID Endpoint</label>
                            <input
                                v-model="editForm.model_key"
                                type="text"
                                class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs font-mono text-gray-900 dark:text-white outline-none focus:border-[#435ebe]"
                                required
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Base URL Endpoint</label>
                                <input
                                    v-model="editForm.base_url"
                                    type="text"
                                    class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs font-mono text-gray-900 dark:text-white outline-none focus:border-[#435ebe]"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Nama Variabel ENV</label>
                                <input
                                    v-model="editForm.api_key_env"
                                    type="text"
                                    class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs font-mono text-gray-900 dark:text-white outline-none focus:border-[#435ebe]"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Context Window</label>
                                <input
                                    v-model="editForm.context_window"
                                    type="number"
                                    class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs font-mono text-gray-900 dark:text-white outline-none focus:border-[#435ebe]"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Max Output Tokens</label>
                                <input
                                    v-model="editForm.max_output_tokens"
                                    type="number"
                                    class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs font-mono text-gray-900 dark:text-white outline-none focus:border-[#435ebe]"
                                    required
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Deskripsi Singkat</label>
                            <textarea
                                v-model="editForm.description"
                                rows="2"
                                class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white outline-none focus:border-[#435ebe]"
                            ></textarea>
                        </div>

                        <div class="flex items-center gap-6 pt-1">
                            <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-700 dark:text-gray-300">
                                <input type="checkbox" v-model="editForm.is_active" class="rounded text-[#435ebe]" />
                                <span>Status Aktif</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-xs text-gray-700 dark:text-gray-300">
                                <input type="checkbox" v-model="editForm.is_default" class="rounded text-[#435ebe]" />
                                <span>Set Sebagai Default Sistem</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-200 dark:border-gray-800">
                            <button
                                type="button"
                                @click="showEditModal = false"
                                class="px-4 py-2 rounded-xl text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="px-5 py-2 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#354bb4] text-white flex items-center gap-1.5"
                            >
                                <i :class="['fa-solid fa-floppy-disk', editForm.processing ? 'fa-spin' : '']"></i>
                                <span>Simpan Perubahan</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- MODAL: TEST MODEL PING -->
            <div v-if="showTestModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
                <div class="bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 rounded-2xl w-full max-w-lg p-6 shadow-xl">
                    <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-200 dark:border-gray-800">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-lg bg-purple-500/10 text-purple-500 flex items-center justify-center">
                                <i class="fa-solid fa-bolt text-sm"></i>
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white">Uji Respon Inferensi Model</h4>
                                <p class="text-[11px] font-mono text-purple-400">{{ testingModel?.model_key }}</p>
                            </div>
                        </div>
                        <button @click="showTestModal = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Prompt Uji</label>
                            <textarea
                                v-model="testPrompt"
                                rows="2"
                                class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3 py-2 text-xs text-gray-900 dark:text-white outline-none focus:border-[#435ebe]"
                            ></textarea>
                        </div>

                        <!-- Response Area -->
                        <div v-if="testResponse" class="p-3.5 rounded-xl border space-y-2" :class="testResponse.status === 'success' ? 'bg-emerald-500/5 border-emerald-500/20' : 'bg-rose-500/5 border-rose-500/20'">
                            <div class="flex items-center justify-between text-[11px]">
                                <span class="font-bold" :class="testResponse.status === 'success' ? 'text-emerald-400' : 'text-rose-400'">
                                    {{ testResponse.status === 'success' ? 'Berhasil Terhubung' : 'Gagal' }}
                                </span>
                                <span class="font-mono text-gray-400">{{ testResponse.latency_ms }} ms latency</span>
                            </div>
                            <div class="text-xs whitespace-pre-wrap leading-relaxed" :class="testResponse.status === 'success' ? 'text-gray-200' : 'text-rose-300'">
                                {{ testResponse.reply || testResponse.message }}
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button
                                type="button"
                                @click="showTestModal = false"
                                class="px-4 py-2 rounded-xl text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300"
                            >
                                Tutup
                            </button>
                            <button
                                type="button"
                                @click="runModelTest"
                                :disabled="testLoading"
                                class="px-5 py-2 rounded-xl text-xs font-bold bg-purple-600 hover:bg-purple-700 text-white flex items-center gap-2"
                            >
                                <i :class="['fa-solid fa-play', testLoading ? 'animate-spin' : '']"></i>
                                <span>{{ testLoading ? 'Menghubungi LLM...' : 'Kirim Uji Coba' }}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
