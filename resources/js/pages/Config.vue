<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../layouts/AppLayout.vue';
import axios from 'axios';

const props = defineProps({
    config: {
        type: Object,
        default: () => ({}),
    },
    env: {
        type: Object,
        default: () => ({}),
    },
    configSections: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('quick');
const yamlViewStates = ref({});

const toggleYaml = (title) => {
    yamlViewStates.value[title] = !yamlViewStates.value[title];
};

const form = useForm({
    model_default: props.config?.model?.default || 'gemini-3.6-flash',
    model_provider: props.config?.model?.provider || 'gemini',
    model_base_url: props.config?.model?.base_url || '',
    terminal_cwd: props.config?.terminal?.cwd || '.',
    groq_api_key: props.env?.GROQ_API_KEY || '',
    google_api_key: props.env?.GOOGLE_API_KEY || props.env?.GEMINI_API_KEY || '',
    openrouter_api_key: props.env?.OPENROUTER_API_KEY || '',
    telegram_bot_token: props.env?.TELEGRAM_BOT_TOKEN || '',
});

const submit = () => {
    form.post('/config');
};

const testing = ref(false);
const testResult = ref(null);

const testApi = async (provider, apiKey) => {
    if (!apiKey) {
        alert('Masukkan API Key terlebih dahulu!');
        return;
    }
    testing.value = true;
    testResult.value = { status: 'loading', message: `Menguji koneksi ke ${provider}...` };

    try {
        const res = await axios.post('/monitoring/test-api', { provider, api_key: apiKey });
        testResult.value = res.data;
    } catch (err) {
        testResult.value = {
            status: 'error',
            message: 'Gagal menghubungi endpoint: ' + (err.response?.data?.message || err.message)
        };
    } finally {
        testing.value = false;
    }
};

const isBoolean = (val) => typeof val === 'boolean';
const isArray = (val) => Array.isArray(val);
const isObject = (val) => typeof val === 'object' && val !== null && !Array.isArray(val);
</script>

<template>
    <AppLayout title="Expressa Configuration">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                        Expressa Configuration
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Pengaturan engine, runtime limits, guardrails, dan parameter eksekusi di <code class="font-mono">config.yaml</code>.
                    </p>
                </div>

                <!-- Tab Selector -->
                <div class="flex items-center gap-2 p-1 rounded-xl bg-gray-100 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700/60 self-start sm:self-auto">
                    <button
                        @click="activeTab = 'quick'"
                        :class="[
                            'px-3.5 py-1.5 rounded-lg text-xs font-semibold transition',
                            activeTab === 'quick'
                                ? 'bg-white dark:bg-[#1e1e2d] text-gray-900 dark:text-white shadow-xs'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                        ]"
                    >
                        <i class="fa-solid fa-sliders mr-1.5"></i> Quick Settings
                    </button>
                    <button
                        @click="activeTab = 'sections'"
                        :class="[
                            'px-3.5 py-1.5 rounded-lg text-xs font-semibold transition',
                            activeTab === 'sections'
                                ? 'bg-white dark:bg-[#1e1e2d] text-gray-900 dark:text-white shadow-xs'
                                : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                        ]"
                    >
                        <i class="fa-solid fa-layer-group mr-1.5"></i> All Config Sections & YAML
                    </button>
                </div>
            </div>

            <!-- Tab 1: Quick Settings Form -->
            <div v-if="activeTab === 'quick'" class="space-y-6">
                <div class="bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-xs">
                    <div class="flex items-center justify-between pb-4 mb-6 border-b border-gray-200 dark:border-gray-800">
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Primary Model & Provider</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Mengatur model LLM default untuk sesi CLI dan Telegram long-polling.
                            </p>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-[#435ebe]/10 text-[#435ebe] dark:text-blue-400 border border-[#435ebe]/20 uppercase">
                            Provider: {{ form.model_provider }}
                        </span>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                                    Default Model
                                </label>
                                <input
                                    v-model="form.model_default"
                                    type="text"
                                    class="w-full bg-gray-50 dark:bg-[#151521] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm font-mono text-gray-900 dark:text-white focus:border-[#435ebe] outline-none transition"
                                    required
                                />
                                <p class="text-[11px] text-gray-400 mt-1.5">
                                    Rekomendasi: <code class="font-mono text-[#435ebe]">gemini-3.6-flash</code> atau <code class="font-mono text-[#435ebe]">gemini-flash-lite-latest</code>
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                                    Inference Provider
                                </label>
                                <select
                                    v-model="form.model_provider"
                                    class="w-full bg-gray-50 dark:bg-[#151521] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white focus:border-[#435ebe] outline-none transition"
                                >
                                    <option value="gemini">gemini (Google AI Studio)</option>
                                    <option value="groq">groq (Llama 3.3 Fast Inference)</option>
                                    <option value="openrouter">openrouter (Multi-Model Gateway)</option>
                                    <option value="anthropic">anthropic (Claude 3.5)</option>
                                </select>
                            </div>
                        </div>

                        <!-- API Keys Section -->
                        <div class="pt-4 border-t border-gray-200 dark:border-gray-800 space-y-4">
                            <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                Provider Credentials & API Keys
                            </h4>

                            <!-- Google Gemini Key -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        Google Gemini API Key
                                    </label>
                                    <button
                                        type="button"
                                        @click="testApi('gemini', form.google_api_key)"
                                        class="text-xs text-[#435ebe] dark:text-blue-400 hover:underline font-semibold"
                                    >
                                        <i class="fa-solid fa-bolt mr-1"></i> Test Koneksi
                                    </button>
                                </div>
                                <input
                                    v-model="form.google_api_key"
                                    type="password"
                                    placeholder="AIzaSy..."
                                    class="w-full bg-gray-50 dark:bg-[#151521] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2 text-sm font-mono text-gray-900 dark:text-white focus:border-[#435ebe] outline-none"
                                />
                            </div>

                            <!-- Groq Key -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                        Groq API Key
                                    </label>
                                    <button
                                        type="button"
                                        @click="testApi('groq', form.groq_api_key)"
                                        class="text-xs text-[#435ebe] dark:text-blue-400 hover:underline font-semibold"
                                    >
                                        <i class="fa-solid fa-bolt mr-1"></i> Test Koneksi
                                    </button>
                                </div>
                                <input
                                    v-model="form.groq_api_key"
                                    type="password"
                                    placeholder="gsk_..."
                                    class="w-full bg-gray-50 dark:bg-[#151521] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2 text-sm font-mono text-gray-900 dark:text-white focus:border-[#435ebe] outline-none"
                                />
                            </div>

                            <!-- Test Result Banner -->
                            <div
                                v-if="testResult"
                                :class="[
                                    'p-3.5 rounded-xl text-xs flex items-center justify-between border',
                                    testResult.status === 'success'
                                        ? 'bg-emerald-50 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-500/30'
                                        : 'bg-rose-50 text-rose-800 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-500/30'
                                ]"
                            >
                                <span class="font-medium">{{ testResult.message }}</span>
                                <span v-if="testResult.latency_ms" class="font-mono text-[11px] font-bold">
                                    {{ testResult.latency_ms }} ms
                                </span>
                            </div>
                        </div>

                        <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-800">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-5 py-2.5 rounded-xl text-xs font-semibold bg-[#435ebe] text-white hover:bg-[#3950a2] transition shadow-xs flex items-center gap-2"
                            >
                                <i class="fa-solid fa-floppy-disk"></i> Simpan Konfigurasi
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tab 2: Categorized Sections with YAML Previews (Reference Dashboard Feature) -->
            <div v-else class="space-y-5">
                <div
                    v-for="section in configSections"
                    :key="section.title"
                    class="rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 overflow-hidden shadow-xs"
                >
                    <div class="p-4 sm:p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-black/10">
                        <h3 class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fa-solid fa-code text-[#435ebe]"></i> {{ section.title }}
                        </h3>
                        <button
                            @click="toggleYaml(section.title)"
                            class="px-3 py-1 rounded-lg text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 transition flex items-center gap-1.5"
                        >
                            <i :class="yamlViewStates[section.title] ? 'fa-solid fa-table-list' : 'fa-solid fa-code'"></i>
                            {{ yamlViewStates[section.title] ? 'Tampilkan Tabel' : 'Tampilkan YAML' }}
                        </button>
                    </div>

                    <!-- YAML View -->
                    <div v-if="yamlViewStates[section.title]" class="p-5 bg-gray-50 dark:bg-[#151521] overflow-x-auto">
                        <pre class="font-mono text-xs text-gray-800 dark:text-gray-200 leading-relaxed">{{ section.yaml }}</pre>
                    </div>

                    <!-- Table / Key-Value View -->
                    <div v-else class="p-5 divide-y divide-gray-100 dark:divide-gray-800/80">
                        <div
                            v-for="(val, key) in section.data"
                            :key="key"
                            class="py-3 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs"
                        >
                            <span class="font-mono text-gray-500 dark:text-gray-400 min-w-44 font-semibold">
                                {{ key }}
                            </span>

                            <div>
                                <!-- Boolean Badge -->
                                <span
                                    v-if="isBoolean(val)"
                                    :class="[
                                        'px-2.5 py-0.5 rounded text-[11px] font-bold uppercase',
                                        val ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
                                    ]"
                                >
                                    {{ val ? 'enabled' : 'disabled' }}
                                </span>

                                <!-- Array Badges -->
                                <div v-else-if="isArray(val)" class="flex flex-wrap gap-1">
                                    <span
                                        v-for="(item, idx) in val"
                                        :key="idx"
                                        class="px-2 py-0.5 rounded text-[11px] font-mono bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700"
                                    >
                                        {{ String(item) }}
                                    </span>
                                </div>

                                <!-- Object / Nested -->
                                <div v-else-if="isObject(val)" class="p-2.5 rounded-lg bg-gray-50 dark:bg-[#151521] font-mono text-[11px] text-gray-600 dark:text-gray-300">
                                    <pre class="whitespace-pre-wrap">{{ JSON.stringify(val, null, 2) }}</pre>
                                </div>

                                <!-- Plain String/Number -->
                                <span v-else class="font-mono font-bold text-gray-800 dark:text-gray-200">
                                    {{ String(val) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
