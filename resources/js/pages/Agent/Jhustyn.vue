<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';

const props = defineProps({
    agent: {
        type: Object,
        required: true,
    },
    systemUsers: {
        type: Array,
        default: () => [],
    },
    fileInfo: {
        type: Object,
        default: () => ({ exists: false, size: 0, last_modified: null }),
    },
    workspaceInfo: {
        type: Object,
        default: () => ({ exists: false, path: '', subdirs: [] }),
    },
});

// Playground state
const playgroundMessages = ref([
    {
        role: 'assistant',
        content: 'Halo! Saya adalah **Agent Jhustyn**, Autonomous AI Engineer untuk sistem **JPP-ERP** (PT Petrokimia Gresik).\n\nSaya menguasai arsitektur Express.js + Prisma ORM, mutasi data transaksi atomik, dan seluruh alur modul bisnis Sales/EBAP, Purchasing, Inventory, Finance, dan Approval Matrix. Apa yang ingin Anda diskusikan atau uji hari ini?',
        time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    }
]);
const playgroundInput = ref('');
const playgroundLoading = ref(false);
const playgroundScrollRef = ref(null);

const sendPlaygroundMessage = async (customPrompt = null) => {
    const text = customPrompt || playgroundInput.value.trim();
    if (!text || playgroundLoading.value) return;

    playgroundMessages.value.push({
        role: 'user',
        content: text,
        time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    });

    if (!customPrompt) {
        playgroundInput.value = '';
    }
    playgroundLoading.value = true;

    try {
        const res = await fetch('/api/chat/send', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                message: text,
                profile: 'jhustyn',
            })
        });

        const data = await res.json();
        playgroundMessages.value.push({
            role: 'assistant',
            content: data.reply || 'Jhustyn tidak mengembalikan respon.',
            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        });
    } catch (e) {
        playgroundMessages.value.push({
            role: 'assistant',
            content: 'Terjadi kesalahan sistem saat menghubungi Jhustyn: ' + e.message,
            time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        });
    } finally {
        playgroundLoading.value = false;
    }
};

const activeTab = ref('knowledge'); // 'knowledge', 'projects', 'users', 'markdown', 'playground'

const form = useForm({
    role_title: props.agent.role_title || 'Lead Enterprise Architect & Senior AI Engineer',
    project_name: props.agent.project_name || 'ERP JPP (Jasa Pelayanan Pabrik - PT Petrokimia Gresik)',
    principal_partner: props.agent.principal_partner || 'Jesa',
    workspace_path: props.agent.workspace_path || 'E:\\App\\SWA\\JPP-ERP',
    repository_url: props.agent.repository_url || 'https://github.com/expressa-repo/petrokimia-gersik.git',
    branch: props.agent.branch || 'main',
    model_default: props.agent.model_default || 'gemini-flash-lite-latest',
    knowledge_skills: Array.isArray(props.agent.knowledge_skills) ? [...props.agent.knowledge_skills] : [],
    business_modules: Array.isArray(props.agent.business_modules) ? [...props.agent.business_modules] : [],
    responsibility_paths: Array.isArray(props.agent.responsibility_paths) ? [...props.agent.responsibility_paths] : [],
    allowed_users: Array.isArray(props.agent.allowed_users) ? [...props.agent.allowed_users] : [],
    allowed_telegram_ids: Array.isArray(props.agent.allowed_telegram_ids) ? [...props.agent.allowed_telegram_ids] : [],
    system_prompt: props.agent.system_prompt || '',
    markdown_content: props.agent.markdown_content || '',
});

// New input states
const newSkill = ref('');
const newPath = ref('');
const newTelegramId = ref('');
const newModule = ref({ name: '', scope: '' });
const isMarkdownPreview = ref(false);

const addSkill = () => {
    if (!newSkill.value.trim()) return;
    if (!form.knowledge_skills.includes(newSkill.value.trim())) {
        form.knowledge_skills.push(newSkill.value.trim());
    }
    newSkill.value = '';
};

const removeSkill = (index) => {
    form.knowledge_skills.splice(index, 1);
};

const addPath = () => {
    if (!newPath.value.trim()) return;
    if (!form.responsibility_paths.includes(newPath.value.trim())) {
        form.responsibility_paths.push(newPath.value.trim());
    }
    newPath.value = '';
};

const removePath = (index) => {
    form.responsibility_paths.splice(index, 1);
};

const addModule = () => {
    if (!newModule.value.name.trim()) return;
    form.business_modules.push({
        name: newModule.value.name.trim(),
        scope: newModule.value.scope.trim() || 'Modul operasional ERP JPP'
    });
    newModule.value = { name: '', scope: '' };
};

const removeModule = (index) => {
    form.business_modules.splice(index, 1);
};

const toggleUserAccess = (email) => {
    const idx = form.allowed_users.indexOf(email);
    if (idx > -1) {
        form.allowed_users.splice(idx, 1);
    } else {
        form.allowed_users.push(email);
    }
};

const isUserAllowed = (email) => {
    return form.allowed_users.includes(email);
};

const addTelegramId = () => {
    if (!newTelegramId.value.trim()) return;
    if (!form.allowed_telegram_ids.includes(newTelegramId.value.trim())) {
        form.allowed_telegram_ids.push(newTelegramId.value.trim());
    }
    newTelegramId.value = '';
};

const removeTelegramId = (id) => {
    const idx = form.allowed_telegram_ids.indexOf(id);
    if (idx > -1) {
        form.allowed_telegram_ids.splice(idx, 1);
    }
};

const saveAll = () => {
    form.put('/agent-jhustyn', {
        preserveScroll: true,
    });
};

const syncingFromDisk = ref(false);
const syncFromDisk = () => {
    if (!confirm('Apakah Anda yakin ingin memuat ulang spesifikasi dari file Jhustyn.md di disk? Perubahan yang belum disimpan akan tertimpa.')) {
        return;
    }
    syncingFromDisk.value = true;
    router.post('/agent-jhustyn/sync', {}, {
        onFinish: () => {
            syncingFromDisk.value = false;
        }
    });
};
</script>

<template>
    <AppLayout title="Manajemen Agent Jhustyn (Jhustyn.md)">
        <Head title="Manajemen Agent Jhustyn - Expressa Assist" />

        <div class="space-y-6">
            <!-- Hero Agent Profile Header -->
            <div class="rounded-3xl bg-linear-to-r from-[#1e2746] to-[#161c32] border border-gray-200 dark:border-gray-800 p-6 sm:p-8 shadow-sm">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <!-- Left: Identity -->
                    <div class="flex items-start gap-4 sm:gap-5">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl bg-linear-to-tr from-[#435ebe] to-sky-400 text-white flex items-center justify-center shrink-0 shadow-lg shadow-[#435ebe]/30 text-2xl sm:text-3xl relative">
                            <i class="fa-solid fa-user-astronaut"></i>
                            <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-full bg-emerald-500 border-2 border-[#161c32] flex items-center justify-center" title="Agent Online & Siap Digunakan">
                                <span class="w-2 h-2 rounded-full bg-white animate-pulse"></span>
                            </span>
                        </div>

                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                                    {{ agent.name }}
                                </h1>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#435ebe]/20 text-sky-400 border border-[#435ebe]/30 uppercase tracking-wider">
                                    ERP JPP LEAD
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    Autonomous Agent
                                </span>
                            </div>

                            <p class="text-xs sm:text-sm font-semibold text-gray-700 dark:text-slate-300">
                                {{ agent.role_title }}
                            </p>

                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-gray-500 dark:text-slate-400 pt-1">
                                <span class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-building text-[#435ebe]"></i>
                                    {{ agent.project_name }}
                                </span>
                                <span class="flex items-center gap-1.5">
                                    <i class="fa-solid fa-user-tie text-emerald-400"></i>
                                    Partner: <b>{{ agent.principal_partner }}</b>
                                </span>
                                <span class="flex items-center gap-1.5 font-mono">
                                    <i class="fa-solid fa-folder-tree text-amber-400"></i>
                                    {{ agent.workspace_path }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Quick Actions -->
                    <div class="flex flex-wrap items-center gap-2.5 self-start lg:self-center">
                        <Link
                            href="/chat?profile=jhustyn"
                            class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-sky-500/10 hover:bg-sky-500/20 text-sky-400 border border-sky-500/30 transition flex items-center gap-2 cursor-pointer"
                            title="Buka ruang chat langsung dengan Jhustyn"
                        >
                            <i class="fa-solid fa-terminal text-xs"></i>
                            <span>Chat Live</span>
                        </Link>

                        <button
                            @click="syncFromDisk"
                            :disabled="syncingFromDisk"
                            class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 border border-gray-300 dark:border-slate-700 transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
                            title="Muat ulang konten terbaru dari file Jhustyn.md di disk"
                        >
                            <i :class="['fa-solid fa-rotate', syncingFromDisk ? 'fa-spin' : '']"></i>
                            <span>Reload Disk</span>
                        </button>

                        <button
                            @click="saveAll"
                            :disabled="form.processing"
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#354bb4] text-white shadow-md shadow-[#435ebe]/20 transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
                        >
                            <i v-if="form.processing" class="fa-solid fa-circle-notch animate-spin"></i>
                            <i v-else class="fa-solid fa-floppy-disk"></i>
                            <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
                        </button>
                    </div>
                </div>

                <!-- File & Workspace Status Subbar -->
                <div class="mt-6 pt-4 border-t border-gray-200/50 dark:border-gray-800/80 flex flex-wrap items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center gap-1.5 text-gray-400">
                            <i class="fa-solid fa-file-code text-blue-400"></i>
                            File: <code class="font-mono text-gray-300">{{ fileInfo.path }}</code>
                        </span>
                        <span v-if="fileInfo.exists" class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            {{ (fileInfo.size / 1024).toFixed(1) }} KB
                        </span>
                        <span v-else class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                            File Belum Ditemukan
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center gap-1.5 text-gray-400">
                            <i class="fa-solid fa-microchip text-purple-400"></i>
                            Model Default:
                        </span>
                        <select
                            v-model="form.model_default"
                            class="bg-gray-50 dark:bg-[#111726] border border-gray-200 dark:border-gray-700 rounded-lg px-2 py-1 text-xs text-gray-900 dark:text-white font-mono focus:outline-none cursor-pointer"
                        >
                            <option value="gemini-flash-lite-latest">gemini-flash-lite-latest (Recommended)</option>
                            <option value="gemini-2.5-flash">gemini-2.5-flash</option>
                            <option value="gemini-2.5-pro">gemini-2.5-pro (Deep Architecture)</option>
                            <option value="llama-3.3-70b-versatile">llama-3.3-70b-versatile (Groq)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="flex items-center gap-2 border-b border-gray-200 dark:border-gray-800 pb-2">
                <button
                    @click="activeTab = 'knowledge'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2',
                        activeTab === 'knowledge'
                            ? 'bg-[#435ebe] text-white shadow-sm'
                            : 'text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-800/60 hover:text-gray-900 dark:hover:text-white'
                    ]"
                >
                    <i class="fa-solid fa-brain"></i>
                    <span>Ilmu & Kemampuan</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-white/20 text-white font-mono">
                        {{ form.knowledge_skills.length }}
                    </span>
                </button>

                <button
                    @click="activeTab = 'projects'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2',
                        activeTab === 'projects'
                            ? 'bg-[#435ebe] text-white shadow-sm'
                            : 'text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-800/60 hover:text-gray-900 dark:hover:text-white'
                    ]"
                >
                    <i class="fa-solid fa-diagram-project"></i>
                    <span>Project & Tanggung Jawab (Path)</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-white/20 text-white font-mono">
                        {{ form.responsibility_paths.length }}
                    </span>
                </button>

                <button
                    @click="activeTab = 'users'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2',
                        activeTab === 'users'
                            ? 'bg-[#435ebe] text-white shadow-sm'
                            : 'text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-800/60 hover:text-gray-900 dark:hover:text-white'
                    ]"
                >
                    <i class="fa-solid fa-users-gear"></i>
                    <span>Hak Akses User</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-white/20 text-white font-mono">
                        {{ form.allowed_users.length }}
                    </span>
                </button>

                <button
                    @click="activeTab = 'markdown'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer',
                        activeTab === 'markdown'
                            ? 'bg-[#435ebe] text-white shadow-sm'
                            : 'text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-800/60 hover:text-gray-900 dark:hover:text-white'
                    ]"
                >
                    <i class="fa-solid fa-file-pen"></i>
                    <span>Editor Jhustyn.md</span>
                </button>

                <button
                    @click="activeTab = 'playground'"
                    :class="[
                        'px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer',
                        activeTab === 'playground'
                            ? 'bg-[#435ebe] text-white shadow-sm'
                            : 'text-gray-600 dark:text-slate-400 hover:bg-gray-100 dark:hover:bg-slate-800/60 hover:text-gray-900 dark:hover:text-white'
                    ]"
                >
                    <i class="fa-solid fa-flask-vial"></i>
                    <span>Test & Playground Jhustyn</span>
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                </button>
            </div>

            <!-- ============================================================ -->
            <!-- TAB 1: ILMU & KEMAMPUAN (KNOWLEDGE & SKILLS)                -->
            <!-- ============================================================ -->
            <div v-if="activeTab === 'knowledge'" class="space-y-6">
                <!-- Core Competencies / Knowledge Skills Tags -->
                <div class="rounded-3xl bg-white dark:bg-[#111726] border border-gray-200 dark:border-[#1a2337] p-6 shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-lightbulb text-amber-400"></i>
                                Ilmu & Keahlian yang Dikuasai
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                                Daftar kompetensi teknis, framework, dan metodologi yang menjadi keahlian utama Jhustyn.
                            </p>
                        </div>

                        <!-- Add Tag Input -->
                        <div class="flex items-center gap-2">
                            <input
                                v-model="newSkill"
                                @keydown.enter.prevent="addSkill"
                                type="text"
                                placeholder="Tambah ilmu baru (Enter)..."
                                class="px-3 py-1.5 rounded-xl text-xs bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:border-[#435ebe]"
                            />
                            <button
                                @click="addSkill"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold bg-[#435ebe] text-white hover:bg-[#354bb4] transition shrink-0"
                            >
                                <i class="fa-solid fa-plus"></i> Tambah
                            </button>
                        </div>
                    </div>

                    <!-- Tags Cloud -->
                    <div class="flex flex-wrap gap-2.5 pt-2">
                        <div
                            v-for="(skill, idx) in form.knowledge_skills"
                            :key="idx"
                            class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-gray-50 dark:bg-[#161d31] text-gray-800 dark:text-slate-200 border border-gray-200 dark:border-gray-700/80 shadow-xs hover:border-[#435ebe]/50 transition group"
                        >
                            <i class="fa-solid fa-check text-emerald-400 text-[10px]"></i>
                            <span>{{ skill }}</span>
                            <button
                                @click="removeSkill(idx)"
                                class="opacity-40 group-hover:opacity-100 hover:text-rose-400 text-xs transition"
                                title="Hapus keahlian"
                            >
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Business Modules Breakdown -->
                <div class="rounded-3xl bg-white dark:bg-[#111726] border border-gray-200 dark:border-[#1a2337] p-6 shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-cubes text-sky-400"></i>
                                Modul Bisnis Enterprise ERP JPP
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                                Seluruh rantai proses bisnis yang dikuasai secara mendalam oleh Jhustyn.
                            </p>
                        </div>
                    </div>

                    <!-- Module Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="(mod, mIdx) in form.business_modules"
                            :key="mIdx"
                            class="p-4 rounded-2xl bg-gray-50/50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-800 flex flex-col justify-between"
                        >
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <div class="font-bold text-xs text-gray-900 dark:text-white flex items-center gap-2">
                                        <span class="w-6 h-6 rounded-lg bg-[#435ebe]/10 text-[#435ebe] text-[10px] font-mono flex items-center justify-center font-bold">
                                            0{{ mIdx + 1 }}
                                        </span>
                                        <span>{{ mod.name }}</span>
                                    </div>
                                    <button
                                        @click="removeModule(mIdx)"
                                        class="text-gray-400 hover:text-rose-400 text-xs transition"
                                        title="Hapus modul"
                                    >
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-slate-400 leading-relaxed font-sans pl-8">
                                    {{ mod.scope }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Add New Module Form -->
                    <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-800/80 grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <input
                            v-model="newModule.name"
                            type="text"
                            placeholder="Nama Modul Bisnis Baru..."
                            class="px-3.5 py-2 rounded-xl text-xs bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:border-[#435ebe]"
                        />
                        <input
                            v-model="newModule.scope"
                            type="text"
                            placeholder="Deskripsi / Alur Kerja Modul..."
                            class="px-3.5 py-2 rounded-xl text-xs bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:border-[#435ebe]"
                        />
                        <button
                            @click="addModule"
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-[#435ebe] text-white hover:bg-[#354bb4] transition flex items-center justify-center gap-1.5"
                        >
                            <i class="fa-solid fa-plus"></i> Tambah Modul
                        </button>
                    </div>
                </div>

                <!-- Philosophy & Directives -->
                <div class="rounded-3xl bg-white dark:bg-[#111726] border border-gray-200 dark:border-[#1a2337] p-6 shadow-xs">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-2">
                        <i class="fa-solid fa-gavel text-purple-400"></i>
                        Prinsip Eksekusi & Zero Basa-Basi
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-gray-600 dark:text-slate-300">
                        <div class="p-3.5 rounded-2xl bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-800">
                            <div class="font-bold text-gray-900 dark:text-white mb-1 flex items-center gap-1.5">
                                <i class="fa-solid fa-bolt text-amber-400"></i> Bias for Action
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-slate-400 leading-relaxed">
                                Tidak sekadar memberi saran teori. Begitu instruksi jelas dari Jesa, langsung edit file, compile, dan kirim commit.
                            </p>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-800">
                            <div class="font-bold text-gray-900 dark:text-white mb-1 flex items-center gap-1.5">
                                <i class="fa-solid fa-shield-halved text-emerald-400"></i> Atomic Transaction
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-slate-400 leading-relaxed">
                                Operasi mutasi data multi-tabel (order, approval, stok, faktur) wajib selalu dibungkus prisma.$transaction.
                            </p>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-800">
                            <div class="font-bold text-gray-900 dark:text-white mb-1 flex items-center gap-1.5">
                                <i class="fa-solid fa-vial-circle-check text-blue-400"></i> Strict Verification
                            </div>
                            <p class="text-[11px] text-gray-500 dark:text-slate-400 leading-relaxed">
                                Wajib menjalankan build check lokal (vite build / prisma validate) sebelum melapor pekerjaan selesai.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- TAB 2: PROJECT & TANGGUNG JAWAB (PATHS)                     -->
            <!-- ============================================================ -->
            <div v-if="activeTab === 'projects'" class="space-y-6">
                <!-- Project Metadata -->
                <div class="rounded-3xl bg-white dark:bg-[#111726] border border-gray-200 dark:border-[#1a2337] p-6 shadow-xs space-y-4">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <i class="fa-solid fa-code-branch text-[#435ebe]"></i>
                        Konfigurasi Target Repository & Proyek
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <div class="space-y-1.5">
                            <label class="font-bold text-gray-700 dark:text-slate-300">
                                Target Local Workspace Directory Path
                            </label>
                            <input
                                v-model="form.workspace_path"
                                type="text"
                                class="w-full px-3.5 py-2.5 rounded-xl font-mono bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none focus:border-[#435ebe]"
                            />
                            <div class="flex items-center gap-2 pt-0.5">
                                <span v-if="workspaceInfo.exists" class="text-[10px] text-emerald-400 font-semibold flex items-center gap-1">
                                    <i class="fa-solid fa-circle-check"></i> Direktori valid di disk
                                </span>
                                <span v-else class="text-[10px] text-amber-400 font-semibold flex items-center gap-1">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Path direktori belum ditemukan di komputer
                                </span>
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label class="font-bold text-gray-700 dark:text-slate-300">
                                Remote Git Repository URL
                            </label>
                            <input
                                v-model="form.repository_url"
                                type="text"
                                class="w-full px-3.5 py-2.5 rounded-xl font-mono bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none focus:border-[#435ebe]"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <label class="font-bold text-gray-700 dark:text-slate-300">
                                Branch Aktif
                            </label>
                            <input
                                v-model="form.branch"
                                type="text"
                                class="w-full px-3.5 py-2.5 rounded-xl font-mono bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none focus:border-[#435ebe]"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <label class="font-bold text-gray-700 dark:text-slate-300">
                                Principal Stakeholder / Owner
                            </label>
                            <input
                                v-model="form.principal_partner"
                                type="text"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none focus:border-[#435ebe]"
                            />
                        </div>
                    </div>
                </div>

                <!-- Responsibility Paths List -->
                <div class="rounded-3xl bg-white dark:bg-[#111726] border border-gray-200 dark:border-[#1a2337] p-6 shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-folder-tree text-amber-400"></i>
                                Sub-Direktori & File Tanggung Jawab Jhustyn
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                                Path yang menjadi cakupan tugas langsung Jhustyn saat menerima instruksi coding.
                            </p>
                        </div>

                        <!-- Add Path Input -->
                        <div class="flex items-center gap-2">
                            <input
                                v-model="newPath"
                                @keydown.enter.prevent="addPath"
                                type="text"
                                placeholder="Contoh: BE\src\controllers"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-mono bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:border-[#435ebe]"
                            />
                            <button
                                @click="addPath"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold bg-[#435ebe] text-white hover:bg-[#354bb4] transition shrink-0"
                            >
                                <i class="fa-solid fa-plus"></i> Tambah Path
                            </button>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-100 dark:divide-gray-800/80">
                        <div
                            v-for="(p, pIdx) in form.responsibility_paths"
                            :key="pIdx"
                            class="py-3 flex items-center justify-between gap-3 group"
                        >
                            <div class="flex items-center gap-3">
                                <span class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-[#151b2e] text-gray-500 dark:text-slate-400 text-xs font-mono flex items-center justify-center">
                                    <i class="fa-solid fa-folder text-[11px] text-[#435ebe]"></i>
                                </span>
                                <code class="text-xs font-mono text-gray-800 dark:text-slate-200">{{ p }}</code>
                            </div>

                            <button
                                @click="removePath(pIdx)"
                                class="p-1.5 text-gray-400 hover:text-rose-400 transition"
                                title="Hapus dari daftar tanggung jawab"
                            >
                                <i class="fa-solid fa-trash-can text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- TAB 3: HAK AKSES USER (ALLOWED USERS & TELEGRAM)            -->
            <!-- ============================================================ -->
            <div v-if="activeTab === 'users'" class="space-y-6">
                <!-- Info Banner -->
                <div class="p-4 rounded-2xl bg-blue-500/10 border border-blue-500/20 text-xs text-blue-700 dark:text-blue-300 flex items-start gap-3">
                    <i class="fa-solid fa-shield-halved text-base mt-0.5 shrink-0"></i>
                    <div>
                        <div class="font-bold">Proteksi Akses Agent Otonom</div>
                        <p class="mt-0.5 text-[11px] text-blue-600 dark:text-blue-200">
                            Hanya pengguna yang diizinkan di bawah ini yang dapat berinteraksi, memberi instruksi ngoding, dan memicu eksekusi alat oleh Agent Jhustyn (baik lewat Dashboard maupun Telegram Bot).
                        </p>
                    </div>
                </div>

                <!-- Registered Dashboard Users Access Matrix -->
                <div class="rounded-3xl bg-white dark:bg-[#111726] border border-gray-200 dark:border-[#1a2337] p-6 shadow-xs">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2 mb-1">
                        <i class="fa-solid fa-user-lock text-emerald-400"></i>
                        Daftar User Sistem yang Diizinkan Menggunakan Jhustyn
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-slate-400 mb-4">
                        Centang user yang berhak mengirim prompt dan perintah eksekusi ke Agent Jhustyn.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        <div
                            v-for="u in systemUsers"
                            :key="u.global_user_id"
                            @click="toggleUserAccess(u.email)"
                            :class="[
                                'p-4 rounded-2xl border transition-all cursor-pointer flex items-center justify-between gap-3 select-none',
                                isUserAllowed(u.email)
                                    ? 'bg-[#435ebe]/10 border-[#435ebe] text-gray-900 dark:text-white shadow-xs'
                                    : 'bg-gray-50 dark:bg-[#151b2e] border-gray-200 dark:border-gray-800 text-gray-500 dark:text-slate-400 opacity-60 hover:opacity-100'
                            ]"
                        >
                            <div class="flex items-center gap-3 truncate">
                                <div class="w-9 h-9 rounded-xl bg-[#435ebe] text-white flex items-center justify-center font-bold text-xs shrink-0">
                                    {{ u.name.substring(0, 2).toUpperCase() }}
                                </div>
                                <div class="truncate">
                                    <div class="font-bold text-xs truncate">{{ u.name }}</div>
                                    <div class="text-[10px] text-gray-400 truncate">{{ u.email }}</div>
                                    <span class="inline-block mt-0.5 text-[9px] uppercase font-bold px-1.5 py-0.2 rounded bg-gray-200 dark:bg-slate-700 text-gray-700 dark:text-slate-300">
                                        {{ u.role }}
                                    </span>
                                </div>
                            </div>

                            <div class="shrink-0">
                                <div
                                    :class="[
                                        'w-5 h-5 rounded-lg flex items-center justify-center text-xs transition',
                                        isUserAllowed(u.email)
                                            ? 'bg-[#435ebe] text-white'
                                            : 'border border-gray-400 text-transparent'
                                    ]"
                                >
                                    <i class="fa-solid fa-check"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Telegram Bot Whitelist IDs -->
                <div class="rounded-3xl bg-white dark:bg-[#111726] border border-gray-200 dark:border-[#1a2337] p-6 shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="fa-brands fa-telegram text-sky-400"></i>
                                Telegram Allowed User IDs (@jhustyn_hermes_bot)
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                                User ID Telegram yang diotorisasi untuk mengontrol bot Jhustyn secara langsung dari chat Telegram.
                            </p>
                        </div>

                        <!-- Add ID Input -->
                        <div class="flex items-center gap-2">
                            <input
                                v-model="newTelegramId"
                                @keydown.enter.prevent="addTelegramId"
                                type="text"
                                placeholder="Contoh: 8831937787"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-mono bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:border-[#435ebe]"
                            />
                            <button
                                @click="addTelegramId"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold bg-[#435ebe] text-white hover:bg-[#354bb4] transition shrink-0"
                            >
                                <i class="fa-solid fa-plus"></i> Tambah ID
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2.5">
                        <div
                            v-for="tgId in form.allowed_telegram_ids"
                            :key="tgId"
                            class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-mono font-bold bg-sky-500/10 text-sky-400 border border-sky-500/30 group"
                        >
                            <i class="fa-brands fa-telegram text-sky-400"></i>
                            <span>{{ tgId }}</span>
                            <button
                                @click="removeTelegramId(tgId)"
                                class="opacity-50 group-hover:opacity-100 hover:text-rose-400 transition"
                                title="Hapus ID Telegram"
                            >
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <div v-if="!form.allowed_telegram_ids || form.allowed_telegram_ids.length === 0" class="text-xs text-gray-400 italic">
                            Belum ada Telegram ID yang diizinkan.
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- TAB 4: EDITOR SPESIFIKASI "JHUSTYN.MD"                       -->
            <!-- ============================================================ -->
            <div v-if="activeTab === 'markdown'" class="space-y-4">
                <div class="rounded-3xl bg-white dark:bg-[#111726] border border-gray-200 dark:border-[#1a2337] p-6 shadow-xs">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-file-pen text-[#435ebe]"></i>
                                Spesifikasi Asli Dokumen: Jhustyn.md
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                                Edit langsung berkas spesifikasi sistem otonom. Saat disimpan, berkas <code class="font-mono text-gray-300">E:\hermes\profiles\jhustyn\Jhustyn.md</code> akan diperbarui otomatis.
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="isMarkdownPreview = false"
                                :class="[
                                    'px-3 py-1.5 rounded-lg text-xs font-bold transition',
                                    !isMarkdownPreview
                                        ? 'bg-[#435ebe] text-white'
                                        : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-300'
                                ]"
                            >
                                <i class="fa-solid fa-code"></i> Editor
                            </button>
                            <button
                                type="button"
                                @click="isMarkdownPreview = true"
                                :class="[
                                    'px-3 py-1.5 rounded-lg text-xs font-bold transition',
                                    isMarkdownPreview
                                        ? 'bg-[#435ebe] text-white'
                                        : 'bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-slate-300'
                                ]"
                            >
                                <i class="fa-solid fa-eye"></i> Pratinjau
                            </button>
                        </div>
                    </div>

                    <!-- Editor Textarea -->
                    <div v-if="!isMarkdownPreview">
                        <textarea
                            v-model="form.markdown_content"
                            rows="24"
                            class="w-full p-4 rounded-2xl font-mono text-xs leading-relaxed bg-gray-50 dark:bg-[#0c101b] border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-slate-200 focus:outline-none focus:border-[#435ebe]"
                            placeholder="# JHUSTYN.MD — AUTONOMOUS LEAD ARCHITECT..."
                        ></textarea>
                    </div>

                    <!-- Markdown Rendered Preview -->
                    <div
                        v-else
                        class="p-6 rounded-2xl bg-gray-50 dark:bg-[#0c101b] border border-gray-200 dark:border-gray-800 text-xs text-gray-800 dark:text-slate-200 space-y-4 max-h-[600px] overflow-y-auto whitespace-pre-wrap font-sans leading-relaxed select-text"
                    >
                        {{ form.markdown_content }}
                    </div>

                    <!-- Bottom Bar -->
                    <div class="flex items-center justify-between pt-4 mt-2 border-t border-gray-100 dark:border-gray-800/80 text-xs">
                        <span class="text-gray-400 font-mono text-[11px]">
                            Total Karakter: {{ form.markdown_content.length }}
                        </span>

                        <button
                            @click="saveAll"
                            :disabled="form.processing"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#354bb4] text-white shadow-md shadow-[#435ebe]/20 transition flex items-center gap-2 cursor-pointer disabled:opacity-50"
                        >
                            <i v-if="form.processing" class="fa-solid fa-circle-notch animate-spin"></i>
                            <i v-else class="fa-solid fa-floppy-disk"></i>
                            <span>Simpan ke File & Database</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- TAB 5: TEST & PLAYGROUND JHUSTYN (LIVE DRY-RUN)             -->
            <!-- ============================================================ -->
            <div v-if="activeTab === 'playground'" class="space-y-4">
                <div class="rounded-3xl bg-white dark:bg-[#111726] border border-gray-200 dark:border-[#1a2337] p-6 shadow-xs space-y-5">
                    <!-- Playground Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 border-b border-gray-100 dark:border-gray-800/80">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center font-bold text-lg">
                                <i class="fa-solid fa-user-astronaut"></i>
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-extrabold text-sm text-gray-900 dark:text-white">
                                        Interactive Jhustyn Playground
                                    </h3>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 flex items-center gap-1">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Direct Safe API
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Uji respon, kepatuhan spesifikasi, dan pengetahuan Jhustyn terhadap ERP JPP secara langsung.
                                </p>
                            </div>
                        </div>

                        <!-- Context Chips -->
                        <div class="flex items-center gap-2 flex-wrap text-[11px] font-mono">
                            <span class="px-2.5 py-1 rounded-xl bg-gray-100 dark:bg-[#151b2e] text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-gray-800">
                                <i class="fa-solid fa-folder text-amber-400 mr-1"></i> E:\App\SWA\JPP-ERP
                            </span>
                            <span class="px-2.5 py-1 rounded-xl bg-gray-100 dark:bg-[#151b2e] text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-gray-800">
                                <i class="fa-solid fa-shield text-emerald-400 mr-1"></i> ACID Transactions
                            </span>
                        </div>
                    </div>

                    <!-- Quick Prompt Suggestions -->
                    <div class="space-y-1.5">
                        <div class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                            Pintasan Pengujian Cepat:
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <button
                                type="button"
                                @click="sendPlaygroundMessage('Jelaskan arsitektur backend ERP JPP dan aturan prisma.$transaction.')"
                                :disabled="playgroundLoading"
                                class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-[#151b2e] dark:hover:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-gray-800 transition cursor-pointer"
                            >
                                ⚡ Arsitektur Prisma & Transaksi
                            </button>
                            <button
                                type="button"
                                @click="sendPlaygroundMessage('Bagaimana alur dan validasi modul Sales & Distribution (Tender / EBAP)?')"
                                :disabled="playgroundLoading"
                                class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-[#151b2e] dark:hover:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-gray-800 transition cursor-pointer"
                            >
                                📋 Alur Sales & EBAP
                            </button>
                            <button
                                type="button"
                                @click="sendPlaygroundMessage('Sebutkan seluruh tanggung jawab path direktori proyek yang kamu kuasai.')"
                                :disabled="playgroundLoading"
                                class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-[#151b2e] dark:hover:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-gray-800 transition cursor-pointer"
                            >
                                📂 Tanggung Jawab Path
                            </button>
                            <button
                                type="button"
                                @click="sendPlaygroundMessage('Buatkan contoh kode safe transaction saat approval faktur dan mutasi stok.')"
                                :disabled="playgroundLoading"
                                class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-[#151b2e] dark:hover:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-gray-800 transition cursor-pointer"
                            >
                                🛡️ Contoh Safe Mutation
                            </button>
                        </div>
                    </div>

                    <!-- Conversation Window -->
                    <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-[#0c101b] p-4 flex flex-col h-[450px]">
                        <div class="flex-1 overflow-y-auto space-y-3.5 pr-2 select-text" ref="playgroundScrollRef">
                            <div
                                v-for="(msg, idx) in playgroundMessages"
                                :key="idx"
                                :class="[
                                    'flex gap-3 text-xs',
                                    msg.role === 'user' ? 'justify-end' : 'justify-start'
                                ]"
                            >
                                <!-- Jhustyn Avatar -->
                                <div
                                    v-if="msg.role !== 'user'"
                                    class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-xs shrink-0 shadow-sm"
                                >
                                    <i class="fa-solid fa-user-astronaut"></i>
                                </div>

                                <!-- Bubble Content -->
                                <div
                                    :class="[
                                        'max-w-[85%] rounded-2xl p-4 leading-relaxed whitespace-pre-wrap select-text shadow-xs',
                                        msg.role === 'user'
                                            ? 'bg-[#435ebe] text-white rounded-tr-none'
                                            : 'bg-white dark:bg-[#151b2e] border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-slate-100 rounded-tl-none font-sans'
                                    ]"
                                >
                                    <div class="flex items-center justify-between gap-4 mb-1 border-b pb-1 text-[10px]" :class="msg.role === 'user' ? 'border-white/20 text-white/80' : 'border-gray-100 dark:border-gray-800 text-gray-400'">
                                        <span class="font-bold">{{ msg.role === 'user' ? 'Anda (Tester)' : 'Agent Jhustyn' }}</span>
                                        <span>{{ msg.time }}</span>
                                    </div>
                                    <div class="text-xs leading-relaxed">
                                        {{ msg.content }}
                                    </div>
                                </div>
                            </div>

                            <!-- Loading indicator -->
                            <div v-if="playgroundLoading" class="flex gap-3 justify-start text-xs items-center">
                                <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center text-xs shadow-xs animate-pulse">
                                    <i class="fa-solid fa-user-astronaut"></i>
                                </div>
                                <div class="bg-white dark:bg-[#151b2e] border border-gray-200 dark:border-gray-800 rounded-2xl px-4 py-2.5 text-gray-500 dark:text-gray-400 flex items-center gap-2 shadow-xs">
                                    <i class="fa-solid fa-spinner animate-spin text-[#435ebe] text-xs"></i>
                                    <span>Jhustyn sedang menganalisis konteks JPP-ERP...</span>
                                </div>
                            </div>
                        </div>

                        <!-- Playground Input -->
                        <div class="pt-3 border-t border-gray-200 dark:border-gray-800 flex items-center gap-2">
                            <input
                                v-model="playgroundInput"
                                @keydown.enter.prevent="sendPlaygroundMessage()"
                                :disabled="playgroundLoading"
                                type="text"
                                placeholder="Ketik pertanyaan atau instruksi untuk Jhustyn... (tekan Enter)"
                                class="flex-1 px-4 py-2.5 rounded-xl text-xs bg-white dark:bg-[#151b2e] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:border-[#435ebe]"
                            />
                            <button
                                @click="sendPlaygroundMessage()"
                                :disabled="playgroundLoading || !playgroundInput.trim()"
                                class="px-4 py-2.5 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#354bb4] text-white transition disabled:opacity-50 flex items-center gap-1.5 cursor-pointer"
                            >
                                <i :class="['fa-solid text-xs', playgroundLoading ? 'fa-spinner fa-spin' : 'fa-paper-plane']"></i>
                                <span>Kirim</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
