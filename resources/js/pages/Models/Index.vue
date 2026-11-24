<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfigSubnav from '../../components/ConfigSubnav.vue';

const props = defineProps({
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

const selectedProfile = ref(props.currentProfile);
const switchProfile = (pName) => {
    selectedProfile.value = pName;
    router.get('/models', { profile: pName }, { preserveState: false, preserveScroll: true });
};
</script>

<template>
    <AppLayout title="Model & Inference Routing">
        <ConfigSubnav />
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 dark:bg-purple-500/20 text-purple-500 flex items-center justify-center font-bold text-lg">
                        <i class="fa-solid fa-microchip"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                            Models & Inference Routing
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Konfigurasi model default, parameter LLM, Mixture-of-Agents (MoA), dan routing subtask.
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
                        :href="`http://127.0.0.1:9119/models?profile=${selectedProfile}`"
                        target="_blank"
                        rel="noreferrer"
                        class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 transition-colors inline-flex items-center gap-1.5"
                    >
                        <i class="fa-solid fa-up-right-from-square text-[10px]"></i>
                        Port 9119
                    </a>
                </div>
            </div>

            <!-- Active Model Card -->
            <div class="p-6 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-star text-amber-500"></i>
                    Model Default Aktif
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-800">
                        <div class="text-xs text-gray-400">Model Name</div>
                        <div class="text-base font-bold text-gray-900 dark:text-white mt-1 font-mono">
                            {{ modelInfo.model || 'gemini-flash-lite-latest' }}
                        </div>
                    </div>
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-800">
                        <div class="text-xs text-gray-400">Provider</div>
                        <div class="text-base font-bold text-[#435ebe] dark:text-blue-400 mt-1 uppercase">
                            {{ modelInfo.provider || 'gemini' }}
                        </div>
                    </div>
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-800">
                        <div class="text-xs text-gray-400">Context Window</div>
                        <div class="text-base font-bold text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                            {{ modelInfo.context_window ? `${modelInfo.context_window.toLocaleString()} tokens` : '1,048,576 tokens' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Model Options / Catalog -->
            <div class="p-6 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-2">
                    Katalog Model & Dukungan Provider
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                    Expressa Assist mendukung lebih dari 20 provider LLM (Google Gemini, Groq, OpenRouter, Anthropic, OpenAI, DeepSeek, xAI, Local Ollama, dll).
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                    <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-[#151521]/50">
                        <div class="font-bold text-xs text-gray-900 dark:text-white">Google Gemini</div>
                        <div class="text-[11px] text-gray-400 mt-0.5">gemini-flash-lite, gemini-2.5-pro</div>
                        <span class="inline-block mt-2 px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Aktif & Teruji</span>
                    </div>
                    <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-[#151521]/50">
                        <div class="font-bold text-xs text-gray-900 dark:text-white">Groq</div>
                        <div class="text-[11px] text-gray-400 mt-0.5">llama-3.3-70b-versatile</div>
                        <span class="inline-block mt-2 px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">Tersedia</span>
                    </div>
                    <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-[#151521]/50">
                        <div class="font-bold text-xs text-gray-900 dark:text-white">OpenRouter</div>
                        <div class="text-[11px] text-gray-400 mt-0.5">deepseek/deepseek-r1, qwen</div>
                        <span class="inline-block mt-2 px-2 py-0.5 rounded text-[10px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">Tersedia</span>
                    </div>
                    <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-[#151521]/50">
                        <div class="font-bold text-xs text-gray-900 dark:text-white">Anthropic Claude</div>
                        <div class="text-[11px] text-gray-400 mt-0.5">claude-3-7-sonnet</div>
                        <span class="inline-block mt-2 px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">Tersedia</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
