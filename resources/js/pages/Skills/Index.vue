<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';

const props = defineProps({
    skills: {
        type: Array,
        default: () => [],
    },
    toolsets: {
        type: Array,
        default: () => [],
    },
    categories: {
        type: Array,
        default: () => [],
    },
    totalCount: {
        type: Number,
        default: 0,
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

// View tabs: 'skills' | 'hub' | 'toolsets'
const activeTab = ref('skills');

// Profile selection
const selectedProfile = ref(props.currentProfile);
const switchProfile = (pName) => {
    selectedProfile.value = pName;
    router.get('/skills', { profile: pName }, { preserveState: false, preserveScroll: true });
};

// --- Tab 1: Installed Skills State ---
const selectedCategory = ref('all');
const selectedProvenance = ref('all');
const searchQuery = ref('');
const activeSkill = ref(null);
const isLoadingDetail = ref(false);
const togglingMap = ref({});
const lockingMap = ref({});

const filteredSkills = computed(() => {
    return props.skills.filter((skill) => {
        const matchesCategory = selectedCategory.value === 'all' || skill.category === selectedCategory.value;
        const matchesProvenance = selectedProvenance.value === 'all' || (skill.provenance || 'bundled') === selectedProvenance.value;
        const q = searchQuery.value.trim().toLowerCase();
        const matchesQuery = !q || (
            (skill.name || '').toLowerCase().includes(q) ||
            (skill.description || '').toLowerCase().includes(q) ||
            (skill.category || '').toLowerCase().includes(q)
        );
        return matchesCategory && matchesProvenance && matchesQuery;
    });
});

const openSkillModal = async (skill) => {
    activeSkill.value = { ...skill, content: 'Memuat isi SKILL.md...' };
    isLoadingDetail.value = true;
    try {
        const res = await fetch(`/api/skills/${skill.name}?profile=${selectedProfile.value}`);
        if (res.ok) {
            const data = await res.json();
            activeSkill.value = data;
        }
    } catch (e) {
        // keep fallback
    } finally {
        isLoadingDetail.value = false;
    }
};

const handleToggleSkill = async (skill) => {
    togglingMap.value[skill.name] = true;
    const targetState = !skill.enabled;
    try {
        const res = await fetch(`/api/skills/${skill.name}/toggle`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ enabled: targetState, profile: selectedProfile.value })
        });
        if (res.ok) {
            skill.enabled = targetState;
        }
    } catch (e) {
        console.error('Failed to toggle skill', e);
    } finally {
        delete togglingMap.value[skill.name];
    }
};

const handleToggleLock = async (skill) => {
    lockingMap.value[skill.name] = true;
    const targetState = !skill.locked;
    try {
        const res = await fetch(`/api/skills/${skill.name}/lock`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ locked: targetState, profile: selectedProfile.value })
        });
        if (res.ok) {
            skill.locked = targetState;
        }
    } catch (e) {
        console.error('Failed to lock skill', e);
    } finally {
        delete lockingMap.value[skill.name];
    }
};

// Skill Editing & Creation Modal
const editorModalOpen = ref(false);
const editingSkillName = ref('');
const editingSkillCategory = ref('general');
const editingSkillContent = ref('');
const isSavingSkill = ref(false);

const openCreateModal = () => {
    editingSkillName.value = '';
    editingSkillCategory.value = 'general';
    editingSkillContent.value = `---\nname: my-custom-skill\ndescription: "Deskripsi singkat kemampuan baru."\nauthor: Jhustyn\n---\n\n# Petunjuk Kerja Skill\n\nTuliskan instruksi langkah demi langkah di sini.\n`;
    editorModalOpen.value = true;
};

const openEditModal = (skill) => {
    editingSkillName.value = skill.name;
    editingSkillCategory.value = skill.category || 'general';
    editingSkillContent.value = skill.content || '';
    if (!skill.content) {
        openSkillModal(skill).then(() => {
            editingSkillContent.value = activeSkill.value?.content || '';
        });
    }
    editorModalOpen.value = true;
};

const saveSkillContent = async () => {
    if (!editingSkillName.value.trim()) {
        alert('Nama skill tidak boleh kosong.');
        return;
    }
    isSavingSkill.value = true;
    try {
        const res = await fetch(`/api/skills/${editingSkillName.value}/save`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                content: editingSkillContent.value,
                category: editingSkillCategory.value,
                profile: selectedProfile.value
            })
        });
        if (res.ok) {
            editorModalOpen.value = false;
            router.reload({ only: ['skills'] });
        } else {
            alert('Gagal menyimpan skill.');
        }
    } catch (e) {
        alert('Error: ' + e.message);
    } finally {
        isSavingSkill.value = false;
    }
};

// --- Tab 2: Skills Hub State ---
const hubSources = ref([]);
const hubFeatured = ref([]);
const hubOfficialSkills = ref([]);
const hubSearchResults = ref([]);
const isSearchingHub = ref(false);
const hubSearchQuery = ref('');
const hubSourceFilter = ref('all');
const hasSearchedHub = ref(false);
const hubInstalledMap = ref({});
const hubInstallingMap = ref({});

// Hub Preview & Security Scan Modal
const hubDetailModalOpen = ref(false);
const hubActiveDetail = ref(null);
const hubActiveTab = ref('readme'); // 'readme' | 'scan'
const hubPreviewData = ref(null);
const hubScanData = ref(null);
const isLoadingHubPreview = ref(false);
const isLoadingHubScan = ref(false);

// Update Check Modal / Alert
const updateModalOpen = ref(false);
const updateList = ref([]);
const isCheckingUpdates = ref(false);
const isUpdatingAll = ref(false);

const loadHubInitialData = async () => {
    try {
        const [srcRes, offRes] = await Promise.all([
            fetch(`/api/skills-hub/sources?profile=${selectedProfile.value}`),
            fetch(`/api/skills-hub/official?profile=${selectedProfile.value}`)
        ]);

        if (srcRes.ok) {
            const data = await srcRes.json();
            hubSources.value = data.sources || [];
            hubFeatured.value = data.featured || [];
            hubInstalledMap.value = data.installed || {};
        }

        if (offRes.ok) {
            const offData = await offRes.json();
            hubOfficialSkills.value = offData.skills || [];
        }
    } catch (e) {
        console.error('Failed to load skills hub sources', e);
    }
};

const executeHubSearch = async () => {
    const q = hubSearchQuery.value.trim();
    if (!q) {
        hasSearchedHub.value = false;
        hubSearchResults.value = [];
        return;
    }

    isSearchingHub.value = true;
    hasSearchedHub.value = true;
    try {
        const res = await fetch(`/api/skills-hub/search?q=${encodeURIComponent(q)}&source=${hubSourceFilter.value}&profile=${selectedProfile.value}`);
        if (res.ok) {
            const data = await res.json();
            hubSearchResults.value = data.results || [];
            if (data.installed) {
                hubInstalledMap.value = { ...hubInstalledMap.value, ...data.installed };
            }
        }
    } catch (e) {
        console.error('Hub search error', e);
    } finally {
        isSearchingHub.value = false;
    }
};

const openHubDetail = async (item) => {
    hubActiveDetail.value = item;
    hubActiveTab.value = 'readme';
    hubPreviewData.value = null;
    hubScanData.value = null;
    hubDetailModalOpen.value = true;

    // Load preview
    isLoadingHubPreview.value = true;
    try {
        const res = await fetch(`/api/skills-hub/preview?identifier=${encodeURIComponent(item.identifier)}&profile=${selectedProfile.value}`);
        if (res.ok) {
            hubPreviewData.value = await res.json();
        }
    } catch (e) {
        console.error('Failed to preview skill', e);
    } finally {
        isLoadingHubPreview.value = false;
    }

    // Load scan
    isLoadingHubScan.value = true;
    try {
        const res = await fetch(`/api/skills-hub/scan?identifier=${encodeURIComponent(item.identifier)}&profile=${selectedProfile.value}`);
        if (res.ok) {
            hubScanData.value = await res.json();
        }
    } catch (e) {
        console.error('Failed to scan skill', e);
    } finally {
        isLoadingHubScan.value = false;
    }
};

const handleHubInstall = async (item) => {
    const ident = item.identifier;
    hubInstallingMap.value[ident] = true;
    try {
        const res = await fetch('/api/skills-hub/install', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ identifier: ident, profile: selectedProfile.value })
        });
        if (res.ok) {
            item.installed = true;
            hubInstalledMap.value[ident] = true;
            router.reload({ only: ['skills'] });
        }
    } catch (e) {
        alert('Gagal memasang skill: ' + e.message);
    } finally {
        delete hubInstallingMap.value[ident];
    }
};

const handleHubUninstall = async (name) => {
    if (!confirm(`Copot skill "${name}"?`)) return;
    try {
        const res = await fetch('/api/skills-hub/uninstall', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name, profile: selectedProfile.value })
        });
        if (res.ok) {
            router.reload({ only: ['skills'] });
            if (hubDetailModalOpen.value) {
                hubDetailModalOpen.value = false;
            }
        }
    } catch (e) {
        alert('Gagal mencopot skill: ' + e.message);
    }
};

const checkAllUpdates = async () => {
    isCheckingUpdates.value = true;
    try {
        const res = await fetch(`/api/skills-hub/updates?profile=${selectedProfile.value}`);
        if (res.ok) {
            const data = await res.json();
            updateList.value = data.updates || [];
            updateModalOpen.value = true;
        }
    } catch (e) {
        alert('Gagal memeriksa pembaruan: ' + e.message);
    } finally {
        isCheckingUpdates.value = false;
    }
};

const triggerUpdateAll = async () => {
    isUpdatingAll.value = true;
    try {
        const res = await fetch('/api/skills-hub/update-all', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ profile: selectedProfile.value })
        });
        if (res.ok) {
            alert('Proses pembaruan berhasil dipicu!');
            updateModalOpen.value = false;
            router.reload({ only: ['skills'] });
        }
    } catch (e) {
        alert('Gagal memperbarui: ' + e.message);
    } finally {
        isUpdatingAll.value = false;
    }
};

// Check if a skill in hub is installed
const isSkillInstalled = (item) => {
    if (item.installed) return true;
    if (hubInstalledMap.value[item.identifier]) return true;
    const nameOnly = item.identifier.split('/').pop();
    return props.skills.some(s => s.name === nameOnly || s.name === item.name);
};

// Helpers for styling
const getCategoryColor = (category) => {
    switch (category) {
        case 'autonomous-ai-agents':
            return 'bg-blue-100 text-[#435ebe] dark:bg-[#435ebe]/20 dark:text-blue-300 border-blue-200 dark:border-blue-500/30';
        case 'software-development':
        case 'development':
            return 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300 border-indigo-200 dark:border-indigo-500/30';
        case 'research':
            return 'bg-purple-100 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300 border-purple-200 dark:border-purple-500/30';
        case 'productivity':
            return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200 dark:border-emerald-500/30';
        case 'creative':
            return 'bg-pink-100 text-pink-700 dark:bg-pink-950/60 dark:text-pink-300 border-pink-200 dark:border-pink-500/30';
        case 'email':
        case 'media':
            return 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950/60 dark:text-cyan-300 border-cyan-200 dark:border-cyan-500/30';
        case 'web':
            return 'bg-teal-100 text-teal-700 dark:bg-teal-950/60 dark:text-teal-300 border-teal-200 dark:border-teal-500/30';
        default:
            return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border-gray-200 dark:border-gray-700';
    }
};

const getProvenanceBadge = (prov) => {
    switch (prov) {
        case 'bundled':
            return { label: 'Bawaan (Bundled)', color: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 border-gray-200 dark:border-gray-700' };
        case 'agent':
            return { label: 'Custom / Agent', color: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border-emerald-200 dark:border-emerald-500/30' };
        case 'hub':
            return { label: 'Skills Hub', color: 'bg-purple-50 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300 border-purple-200 dark:border-purple-500/30' };
        case 'external':
            return { label: 'External Dir', color: 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 border-amber-200 dark:border-amber-500/30' };
        default:
            return { label: prov || 'Bawaan', color: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300 border-gray-200 dark:border-gray-700' };
    }
};

const getTrustBadge = (trust) => {
    switch (trust) {
        case 'builtin':
            return { label: 'Resmi (Built-in)', color: 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/50 border-blue-200 dark:border-blue-500/30', icon: 'fa-solid fa-shield-check' };
        case 'trusted':
            return { label: 'Terverifikasi (Trusted)', color: 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/50 border-emerald-200 dark:border-emerald-500/30', icon: 'fa-solid fa-badge-check' };
        default:
            return { label: 'Komunitas (Community)', color: 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/50 border-amber-200 dark:border-amber-500/30', icon: 'fa-solid fa-users' };
    }
};

const getToolsetIcon = (name) => {
    const n = (name || '').toLowerCase();
    if (n.includes('web') || n.includes('browser')) return 'fa-solid fa-globe';
    if (n.includes('terminal') || n.includes('code')) return 'fa-solid fa-terminal';
    if (n.includes('file')) return 'fa-solid fa-folder-open';
    if (n.includes('vision') || n.includes('video') || n.includes('image')) return 'fa-solid fa-eye';
    if (n.includes('tts') || n.includes('stt') || n.includes('speech')) return 'fa-solid fa-microphone';
    if (n.includes('memory') || n.includes('session')) return 'fa-solid fa-brain';
    if (n.includes('cron') || n.includes('todo') || n.includes('kanban')) return 'fa-solid fa-calendar-check';
    if (n.includes('computer')) return 'fa-solid fa-desktop';
    if (n.includes('discord')) return 'fa-brands fa-discord';
    if (n.includes('a2a')) return 'fa-solid fa-network-wired';
    return 'fa-solid fa-wrench';
};

onMounted(() => {
    loadHubInitialData();
});
</script>

<template>
    <AppLayout title="Skills & Tools Registry">
        <div class="space-y-6">
            <!-- Header Toolbar -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-[#435ebe]/10 dark:bg-[#435ebe]/20 text-[#435ebe] flex items-center justify-center font-bold text-lg">
                            <i class="fa-solid fa-puzzle-piece"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                                Skills & Tools Hub
                            </h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Manajemen kemampuan modular, tools CLI bawaan, dan katalog online Expressa Assist.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Profile Scope Switcher -->
                    <div class="flex items-center bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700/80 rounded-xl px-2.5 py-1.5 text-xs">
                        <i class="fa-solid fa-user-gear text-gray-400 mr-2"></i>
                        <span class="text-gray-500 dark:text-gray-400 font-medium mr-1.5">Profil:</span>
                        <select
                            :value="selectedProfile"
                            @change="switchProfile($event.target.value)"
                            aria-label="Pilih Profil Expressa"
                            class="bg-transparent font-semibold text-gray-900 dark:text-white focus:outline-none cursor-pointer"
                        >
                            <option
                                v-for="p in profiles"
                                :key="p.name"
                                :value="p.name"
                                class="bg-white dark:bg-[#1e1e2d] text-gray-900 dark:text-white"
                            >
                                {{ p.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Native Hermes Dashboard Link -->
                    <a
                        :href="`http://127.0.0.1:9119/skills?profile=${selectedProfile}`"
                        target="_blank"
                        rel="noreferrer"
                        class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 transition-colors inline-flex items-center gap-1.5"
                        title="Buka interface asli Expressa bawaan di port 9119"
                    >
                        <i class="fa-solid fa-up-right-from-square text-[10px]"></i>
                        Port 9119
                    </a>

                    <!-- Check Updates Button -->
                    <button
                        @click="checkAllUpdates"
                        :disabled="isCheckingUpdates"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 transition-colors inline-flex items-center gap-1.5"
                    >
                        <i class="fa-solid fa-arrows-rotate text-[11px]" :class="{ 'animate-spin': isCheckingUpdates }"></i>
                        <span>{{ isCheckingUpdates ? 'Memeriksa...' : 'Cek Pembaruan' }}</span>
                    </button>

                    <!-- Add Skill Button -->
                    <button
                        @click="openCreateModal"
                        class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-[#435ebe] hover:bg-[#354bb4] text-white transition-colors inline-flex items-center gap-1.5 shadow-sm"
                    >
                        <i class="fa-solid fa-plus text-[11px]"></i>
                        <span>Skill Baru</span>
                    </button>
                </div>
            </div>

            <!-- Main Tabs Header -->
            <div class="border-b border-gray-200 dark:border-gray-800">
                <nav class="flex space-x-6">
                    <button
                        @click="activeTab = 'skills'"
                        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors relative"
                        :class="activeTab === 'skills' ? 'border-[#435ebe] text-[#435ebe] dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                    >
                        <i class="fa-solid fa-puzzle-piece"></i>
                        <span>Skill Terpasang</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-[#435ebe] dark:bg-blue-950/60 dark:text-blue-300">
                            {{ filteredSkills.length }}
                        </span>
                    </button>

                    <button
                        @click="activeTab = 'hub'"
                        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors relative"
                        :class="activeTab === 'hub' ? 'border-[#435ebe] text-[#435ebe] dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                    >
                        <i class="fa-solid fa-store"></i>
                        <span>Skills Hub (Katalog Online)</span>
                        <span v-if="hubOfficialSkills.length > 0" class="px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300">
                            {{ hubOfficialSkills.length }}
                        </span>
                    </button>

                    <button
                        @click="activeTab = 'toolsets'"
                        class="py-3 px-1 border-b-2 font-medium text-sm flex items-center gap-2 transition-colors relative"
                        :class="activeTab === 'toolsets' ? 'border-[#435ebe] text-[#435ebe] dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                    >
                        <i class="fa-solid fa-toolbox"></i>
                        <span>Toolsets CLI Bawaan</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                            {{ toolsets.length }}
                        </span>
                    </button>
                </nav>
            </div>

            <!-- ========================================== -->
            <!-- TAB 1: INSTALLED SKILLS                    -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'skills'" class="space-y-6">
                <!-- Filter & Search Toolbar -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 p-4 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <!-- Search Input -->
                    <div class="relative w-full md:w-80">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari nama, modul, deskripsi..."
                            class="w-full pl-9 pr-4 py-2 text-xs rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700/80 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#435ebe] transition-all"
                        />
                    </div>

                    <!-- Provenance Filter Pills -->
                    <div class="flex flex-wrap items-center gap-1.5 text-xs">
                        <span class="text-gray-400 text-[11px] font-medium mr-1">Sumber:</span>
                        <button
                            @click="selectedProvenance = 'all'"
                            class="px-2.5 py-1 rounded-lg font-medium transition-colors"
                            :class="selectedProvenance === 'all' ? 'bg-[#435ebe] text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
                        >
                            Semua
                        </button>
                        <button
                            @click="selectedProvenance = 'bundled'"
                            class="px-2.5 py-1 rounded-lg font-medium transition-colors"
                            :class="selectedProvenance === 'bundled' ? 'bg-[#435ebe] text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
                        >
                            Bundled
                        </button>
                        <button
                            @click="selectedProvenance = 'agent'"
                            class="px-2.5 py-1 rounded-lg font-medium transition-colors"
                            :class="selectedProvenance === 'agent' ? 'bg-[#435ebe] text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
                        >
                            Custom/Agent
                        </button>
                        <button
                            @click="selectedProvenance = 'hub'"
                            class="px-2.5 py-1 rounded-lg font-medium transition-colors"
                            :class="selectedProvenance === 'hub' ? 'bg-[#435ebe] text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
                        >
                            Hub
                        </button>
                    </div>

                    <!-- Category Filter Dropdown -->
                    <div class="flex items-center gap-2">
                        <span class="text-gray-400 text-xs">Kategori:</span>
                        <select
                            v-model="selectedCategory"
                            aria-label="Filter berdasarkan kategori"
                            class="px-3 py-1.5 text-xs rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700/80 text-gray-900 dark:text-white focus:outline-none focus:ring-1 focus:ring-[#435ebe] cursor-pointer"
                        >
                            <option value="all">Semua Kategori ({{ totalCount }})</option>
                            <option v-for="cat in categories" :key="cat" :value="cat">
                                {{ cat }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="filteredSkills.length === 0" class="p-12 text-center rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800">
                    <i class="fa-solid fa-box-open text-gray-300 dark:text-gray-600 text-4xl mb-3"></i>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Tidak ada skill ditemukan</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Coba sesuaikan kata kunci pencarian atau filter kategori/sumber.</p>
                </div>

                <!-- Grid of Installed Skills -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="skill in filteredSkills"
                        :key="skill.name"
                        class="p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700 transition-all flex flex-col justify-between shadow-xs group"
                        :class="{ 'opacity-60': !skill.enabled }"
                    >
                        <div>
                            <!-- Header Card -->
                            <div class="flex items-start justify-between gap-3 mb-2.5">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                            {{ skill.name }}
                                        </h3>
                                        <!-- Provenance Badge -->
                                        <span
                                            class="px-2 py-0.5 text-[10px] font-semibold rounded-md border"
                                            :class="getProvenanceBadge(skill.provenance).color"
                                        >
                                            {{ getProvenanceBadge(skill.provenance).label }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span
                                            class="inline-block px-2 py-0.5 text-[10px] font-medium rounded-md border"
                                            :class="getCategoryColor(skill.category)"
                                        >
                                            {{ skill.category || 'general' }}
                                        </span>
                                        <span v-if="skill.locked" class="text-[10px] font-semibold text-amber-600 dark:text-amber-400 flex items-center gap-1" title="Terkunci dari modifikasi otomatis Expressa">
                                            <i class="fa-solid fa-lock text-[9px]"></i> Locked
                                        </span>
                                        <span v-if="skill.usage > 0" class="text-[10px] text-gray-400 font-mono">
                                            <i class="fa-solid fa-bolt text-[9px] text-[#435ebe]"></i> {{ skill.usage }}x
                                        </span>
                                    </div>
                                </div>

                                <!-- Action Quick Toggle -->
                                <button
                                    @click="handleToggleSkill(skill)"
                                    :disabled="togglingMap[skill.name]"
                                    class="w-10 h-6 rounded-full transition-colors relative flex items-center px-0.5 cursor-pointer"
                                    :class="skill.enabled ? 'bg-[#435ebe]' : 'bg-gray-300 dark:bg-gray-700'"
                                    :title="skill.enabled ? 'Nonaktifkan skill ini' : 'Aktifkan skill ini'"
                                >
                                    <div
                                        class="w-5 h-5 rounded-full bg-white transition-transform transform shadow-xs flex items-center justify-center text-[10px]"
                                        :class="skill.enabled ? 'translate-x-4 text-[#435ebe]' : 'translate-x-0 text-gray-400'"
                                    >
                                        <i v-if="togglingMap[skill.name]" class="fa-solid fa-spinner animate-spin text-[8px]"></i>
                                    </div>
                                </button>
                            </div>

                            <!-- Description -->
                            <p class="text-xs text-gray-600 dark:text-gray-300 line-clamp-2 mt-2 leading-relaxed">
                                {{ skill.description }}
                            </p>
                        </div>

                        <!-- Card Footer -->
                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800/80 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5">
                                <button
                                    @click="handleToggleLock(skill)"
                                    :disabled="lockingMap[skill.name]"
                                    class="p-1.5 rounded-lg text-xs hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                    :class="skill.locked ? 'text-amber-500' : 'text-gray-400'"
                                    :title="skill.locked ? 'Buka kunci edit agent' : 'Kunci dari perubahan otomatis agent'"
                                >
                                    <i class="fa-solid" :class="skill.locked ? 'fa-lock' : 'fa-lock-open'"></i>
                                </button>

                                <button
                                    v-if="skill.provenance === 'agent'"
                                    @click="openEditModal(skill)"
                                    class="p-1.5 rounded-lg text-xs text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                    title="Edit SKILL.md"
                                >
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                            </div>

                            <button
                                @click="openSkillModal(skill)"
                                class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-gray-50 hover:bg-gray-100 dark:bg-[#151521] dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 transition-colors inline-flex items-center gap-1"
                            >
                                <span>Lihat Doc</span>
                                <i class="fa-solid fa-arrow-right text-[10px]"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 2: SKILLS HUB (ONLINE STORE)           -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'hub'" class="space-y-6">
                <!-- Connected Hubs Strip -->
                <div class="p-4 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-network-wired text-purple-500 text-sm"></i>
                        <span class="text-xs font-bold text-gray-900 dark:text-white">Hub Sumber Online:</span>
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span
                                v-for="src in hubSources"
                                :key="src.id"
                                class="px-2.5 py-1 text-[11px] rounded-lg font-medium border"
                                :class="src.available === false ? 'bg-red-50 text-red-600 dark:bg-red-950/40 border-red-200 dark:border-red-900' : 'bg-gray-50 text-gray-700 dark:bg-[#151521] dark:text-gray-300 border-gray-200 dark:border-gray-700'"
                            >
                                <i class="fa-solid fa-circle text-[6px] mr-1" :class="src.available === false ? 'text-red-500' : 'text-emerald-500'"></i>
                                {{ src.label }}
                            </span>
                        </div>
                    </div>

                    <div class="text-xs text-gray-400">
                        Total {{ hubOfficialSkills.length }} skill resmi terdaftar
                    </div>
                </div>

                <!-- Search Hub Toolbar -->
                <div class="p-4 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs flex flex-col md:flex-row items-center gap-3">
                    <div class="relative flex-1 w-full">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input
                            v-model="hubSearchQuery"
                            @keyup.enter="executeHubSearch"
                            type="text"
                            placeholder="Cari ribuan skill di seluruh hub online (tekan Enter untuk mencari)..."
                            class="w-full pl-9 pr-24 py-2.5 text-xs rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700/80 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-[#435ebe]"
                        />
                        <button
                            @click="executeHubSearch"
                            :disabled="isSearchingHub"
                            class="absolute right-1.5 top-1/2 -translate-y-1/2 px-3 py-1.5 text-xs font-semibold bg-[#435ebe] text-white rounded-lg hover:bg-[#354bb4] transition-colors"
                        >
                            <i v-if="isSearchingHub" class="fa-solid fa-spinner animate-spin mr-1"></i>
                            <span>Cari</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-2 w-full md:w-auto">
                        <span class="text-xs text-gray-400">Sumber:</span>
                        <select
                            v-model="hubSourceFilter"
                            aria-label="Filter sumber hub"
                            class="px-3 py-2 text-xs rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700/80 text-gray-900 dark:text-white focus:outline-none"
                        >
                            <option value="all">Semua Sumber Hub</option>
                            <option v-for="s in hubSources" :key="s.id" :value="s.id">{{ s.label }}</option>
                        </select>
                    </div>
                </div>

                <!-- Search Results Section (if search executed) -->
                <div v-if="hasSearchedHub" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                            Hasil Pencarian: "{{ hubSearchQuery }}" ({{ hubSearchResults.length }})
                        </h3>
                        <button
                            @click="hasSearchedHub = false; hubSearchQuery = '';"
                            class="text-xs text-gray-400 hover:text-gray-200"
                        >
                            Tutup Hasil
                        </button>
                    </div>

                    <div v-if="hubSearchResults.length === 0" class="p-8 text-center rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800">
                        <p class="text-xs text-gray-400">Tidak ada skill yang cocok dengan pencarian di sumber yang terhubung.</p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="item in hubSearchResults"
                            :key="item.identifier"
                            class="p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 flex flex-col justify-between shadow-xs hover:border-gray-300 dark:hover:border-gray-700 transition-all"
                        >
                            <div>
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                            {{ item.name }}
                                        </h4>
                                        <p class="text-[10px] text-gray-400 font-mono truncate mt-0.5">
                                            {{ item.identifier }}
                                        </p>
                                    </div>
                                    <span
                                        class="px-2 py-0.5 text-[10px] font-semibold rounded-md border flex items-center gap-1"
                                        :class="getTrustBadge(item.trust_level).color"
                                    >
                                        <i :class="getTrustBadge(item.trust_level).icon"></i>
                                        {{ getTrustBadge(item.trust_level).label }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-300 line-clamp-2 mt-2 leading-relaxed">
                                    {{ item.description }}
                                </p>
                            </div>

                            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800/80 flex items-center justify-between gap-2">
                                <button
                                    @click="openHubDetail(item)"
                                    class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white inline-flex items-center gap-1"
                                >
                                    <i class="fa-solid fa-shield-halved text-[11px]"></i>
                                    <span>Preview & Scan</span>
                                </button>

                                <div>
                                    <span v-if="isSkillInstalled(item)" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900 inline-flex items-center gap-1">
                                        <i class="fa-solid fa-check text-[10px]"></i> Terpasang
                                    </span>
                                    <button
                                        v-else
                                        @click="handleHubInstall(item)"
                                        :disabled="hubInstallingMap[item.identifier]"
                                        class="px-3 py-1 text-xs font-semibold rounded-lg bg-[#435ebe] hover:bg-[#354bb4] text-white transition-colors inline-flex items-center gap-1"
                                    >
                                        <i v-if="hubInstallingMap[item.identifier]" class="fa-solid fa-spinner animate-spin text-[10px]"></i>
                                        <i v-else class="fa-solid fa-download text-[10px]"></i>
                                        <span>Pasang</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Official Skills Catalog Section -->
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-star text-amber-500"></i>
                                Katalog Resmi Nous Research & Hermes
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Kemampuan opsional terverifikasi siap pasang dari repositori resmi.
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="item in hubOfficialSkills.slice(0, 18)"
                            :key="item.identifier"
                            class="p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 flex flex-col justify-between shadow-xs hover:border-gray-300 dark:hover:border-gray-700 transition-all"
                        >
                            <div>
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                            {{ item.name }}
                                        </h4>
                                        <span class="inline-block mt-1 px-2 py-0.5 text-[10px] font-medium rounded-md border" :class="getCategoryColor(item.category)">
                                            {{ item.category }}
                                        </span>
                                    </div>
                                    <span
                                        class="px-2 py-0.5 text-[10px] font-semibold rounded-md border flex items-center gap-1"
                                        :class="getTrustBadge(item.trust_level).color"
                                    >
                                        <i :class="getTrustBadge(item.trust_level).icon"></i>
                                        {{ getTrustBadge(item.trust_level).label }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-300 line-clamp-2 mt-2 leading-relaxed">
                                    {{ item.description }}
                                </p>
                            </div>

                            <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800/80 flex items-center justify-between gap-2">
                                <button
                                    @click="openHubDetail(item)"
                                    class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white inline-flex items-center gap-1"
                                >
                                    <i class="fa-solid fa-shield-halved text-[11px]"></i>
                                    <span>Preview & Scan</span>
                                </button>

                                <div>
                                    <span v-if="isSkillInstalled(item)" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-900 inline-flex items-center gap-1">
                                        <i class="fa-solid fa-check text-[10px]"></i> Terpasang
                                    </span>
                                    <button
                                        v-else
                                        @click="handleHubInstall(item)"
                                        :disabled="hubInstallingMap[item.identifier]"
                                        class="px-3 py-1 text-xs font-semibold rounded-lg bg-[#435ebe] hover:bg-[#354bb4] text-white transition-colors inline-flex items-center gap-1"
                                    >
                                        <i v-if="hubInstallingMap[item.identifier]" class="fa-solid fa-spinner animate-spin text-[10px]"></i>
                                        <i v-else class="fa-solid fa-download text-[10px]"></i>
                                        <span>Pasang</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 3: CLI TOOLSETS                        -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'toolsets'" class="space-y-6">
                <div class="p-4 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Toolsets adalah kumpulan fungsi primitif bawaan yang diekspos langsung ke Expressa Assist saat mengeksekusi instruksi di terminal atau platform lainnya.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="ts in toolsets"
                        :key="ts.name"
                        class="p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 flex flex-col justify-between shadow-xs"
                    >
                        <div>
                            <div class="flex items-start justify-between gap-3 mb-2.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-lg bg-[#435ebe]/10 dark:bg-[#435ebe]/20 text-[#435ebe] flex items-center justify-center text-sm">
                                        <i :class="getToolsetIcon(ts.name)"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">
                                            {{ ts.label || ts.name }}
                                        </h4>
                                        <span class="text-[10px] text-gray-400 font-mono">{{ ts.platform_label || ts.platform || 'CLI' }}</span>
                                    </div>
                                </div>

                                <span
                                    class="px-2 py-0.5 text-[10px] font-semibold rounded-md border"
                                    :class="ts.enabled ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200' : 'bg-gray-100 text-gray-500 dark:bg-gray-800 border-gray-700'"
                                >
                                    {{ ts.enabled ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>

                            <p class="text-xs text-gray-600 dark:text-gray-300 mt-2 line-clamp-2">
                                {{ ts.description }}
                            </p>

                            <!-- Tool List Tags -->
                            <div v-if="ts.tools && ts.tools.length > 0" class="mt-3 flex flex-wrap gap-1">
                                <span
                                    v-for="t in ts.tools"
                                    :key="t"
                                    class="px-2 py-0.5 rounded text-[10px] font-mono bg-gray-100 dark:bg-[#151521] text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-800"
                                >
                                    {{ t }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- MODAL: VIEW SKILL.MD DETAIL                -->
            <!-- ========================================== -->
            <div
                v-if="activeSkill"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4"
                @click.self="activeSkill = null"
            >
                <div class="w-full max-w-3xl rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-2xl flex flex-col max-h-[85vh] overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-puzzle-piece text-[#435ebe]"></i>
                                {{ activeSkill.name }}
                            </h3>
                            <p class="text-[11px] text-gray-400 font-mono mt-0.5 truncate max-w-lg">
                                {{ activeSkill.file_path || activeSkill.path }}
                            </p>
                        </div>
                        <button
                            @click="activeSkill = null"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1.5"
                        >
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 overflow-y-auto flex-1 font-mono text-xs text-gray-800 dark:text-gray-200 bg-gray-50 dark:bg-[#151521]">
                        <pre class="whitespace-pre-wrap leading-relaxed">{{ activeSkill.content }}</pre>
                    </div>

                    <div class="px-6 py-3.5 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between bg-white dark:bg-[#1e1e2d]">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Status: <strong :class="activeSkill.enabled ? 'text-emerald-500' : 'text-gray-400'">{{ activeSkill.enabled ? 'Aktif' : 'Nonaktif' }}</strong>
                        </span>
                        <div class="flex items-center gap-2">
                            <button
                                v-if="activeSkill.provenance === 'agent'"
                                @click="openEditModal(activeSkill); activeSkill = null;"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-[#435ebe] text-white hover:bg-[#354bb4]"
                            >
                                Edit SKILL.md
                            </button>
                            <button
                                @click="activeSkill = null"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700"
                            >
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- MODAL: CREATE / EDIT SKILL MODAL           -->
            <!-- ========================================== -->
            <div
                v-if="editorModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4"
                @click.self="editorModalOpen = false"
            >
                <div class="w-full max-w-2xl rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-2xl flex flex-col max-h-[85vh] overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fa-solid fa-code text-[#435ebe]"></i>
                            {{ editingSkillName ? `Edit Skill: ${editingSkillName}` : 'Buat Custom Skill Baru' }}
                        </h3>
                        <button @click="editorModalOpen = false" class="text-gray-400 hover:text-gray-200 p-1">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <div class="p-6 overflow-y-auto space-y-4 flex-1">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nama Skill (Identifier)</label>
                            <input
                                v-model="editingSkillName"
                                type="text"
                                placeholder="misal: audit-mom-brd"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Kategori</label>
                            <input
                                v-model="editingSkillCategory"
                                type="text"
                                placeholder="misal: productivity, development, erp"
                                class="w-full px-3 py-2 text-xs rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Isi Dokumen SKILL.md (Markdown)</label>
                            <textarea
                                v-model="editingSkillContent"
                                rows="12"
                                class="w-full p-3 text-xs font-mono rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none leading-relaxed"
                            ></textarea>
                        </div>
                    </div>

                    <div class="px-6 py-3.5 border-t border-gray-200 dark:border-gray-800 flex items-center justify-end gap-2 bg-white dark:bg-[#1e1e2d]">
                        <button
                            @click="editorModalOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300"
                        >
                            Batal
                        </button>
                        <button
                            @click="saveSkillContent"
                            :disabled="isSavingSkill"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-[#435ebe] text-white hover:bg-[#354bb4] inline-flex items-center gap-1.5"
                        >
                            <i v-if="isSavingSkill" class="fa-solid fa-spinner animate-spin text-xs"></i>
                            <span>Simpan Skill</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- MODAL: HUB PREVIEW & SECURITY SCAN         -->
            <!-- ========================================== -->
            <div
                v-if="hubDetailModalOpen && hubActiveDetail"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4"
                @click.self="hubDetailModalOpen = false"
            >
                <div class="w-full max-w-3xl rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-2xl flex flex-col max-h-[85vh] overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-shield-halved text-purple-500"></i>
                                {{ hubActiveDetail.name }}
                            </h3>
                            <p class="text-[11px] text-gray-400 font-mono mt-0.5">
                                {{ hubActiveDetail.identifier }}
                            </p>
                        </div>
                        <button @click="hubDetailModalOpen = false" class="text-gray-400 hover:text-gray-200 p-1">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <!-- Inner Tabs: README vs Security Scan -->
                    <div class="px-6 border-b border-gray-200 dark:border-gray-800 flex space-x-4 bg-gray-50/50 dark:bg-[#151521]/50">
                        <button
                            @click="hubActiveTab = 'readme'"
                            class="py-2.5 px-1 border-b-2 font-medium text-xs transition-colors"
                            :class="hubActiveTab === 'readme' ? 'border-[#435ebe] text-[#435ebe] dark:text-blue-400 font-semibold' : 'border-transparent text-gray-400'"
                        >
                            <i class="fa-solid fa-file-lines mr-1.5"></i>
                            Dokumentasi SKILL.md
                        </button>
                        <button
                            @click="hubActiveTab = 'scan'"
                            class="py-2.5 px-1 border-b-2 font-medium text-xs transition-colors flex items-center gap-1.5"
                            :class="hubActiveTab === 'scan' ? 'border-[#435ebe] text-[#435ebe] dark:text-blue-400 font-semibold' : 'border-transparent text-gray-400'"
                        >
                            <i class="fa-solid fa-shield-virus"></i>
                            <span>Security Guard Scan</span>
                            <span v-if="hubScanData" class="px-1.5 py-0.2 text-[10px] rounded uppercase font-bold" :class="hubScanData.policy === 'allow' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">
                                {{ hubScanData.policy }}
                            </span>
                        </button>
                    </div>

                    <div class="p-6 overflow-y-auto flex-1 text-xs">
                        <!-- Readme View -->
                        <div v-if="hubActiveTab === 'readme'">
                            <div v-if="isLoadingHubPreview" class="py-12 text-center text-gray-400">
                                <i class="fa-solid fa-spinner animate-spin text-2xl mb-2 text-[#435ebe]"></i>
                                <p>Mengunduh preview SKILL.md dari hub...</p>
                            </div>
                            <div v-else-if="hubPreviewData" class="space-y-4">
                                <div v-if="hubPreviewData.files && hubPreviewData.files.length > 0" class="p-3 rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-800">
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">File Manifest:</span>
                                    <div class="flex flex-wrap gap-1.5 mt-1">
                                        <span v-for="f in hubPreviewData.files" :key="f" class="px-2 py-0.5 rounded text-[10px] font-mono bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300">
                                            {{ f }}
                                        </span>
                                    </div>
                                </div>
                                <div class="p-4 rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-800 font-mono">
                                    <pre class="whitespace-pre-wrap leading-relaxed">{{ hubPreviewData.skill_md || 'Tidak ada preview SKILL.md.' }}</pre>
                                </div>
                            </div>
                        </div>

                        <!-- Scan View -->
                        <div v-if="hubActiveTab === 'scan'">
                            <div v-if="isLoadingHubScan" class="py-12 text-center text-gray-400">
                                <i class="fa-solid fa-spinner animate-spin text-2xl mb-2 text-purple-500"></i>
                                <p>Menjalankan Security Guard Scan & AST Audit...</p>
                            </div>
                            <div v-else-if="hubScanData" class="space-y-4">
                                <div class="p-4 rounded-xl border flex items-center justify-between" :class="hubScanData.policy === 'allow' ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300' : 'bg-red-50 dark:bg-red-950/30 border-red-200 dark:border-red-800 text-red-800 dark:text-red-300'">
                                    <div class="flex items-center gap-3">
                                        <i class="fa-solid text-xl" :class="hubScanData.policy === 'allow' ? 'fa-circle-check text-emerald-500' : 'fa-triangle-exclamation text-red-500'"></i>
                                        <div>
                                            <h4 class="font-bold text-sm uppercase">Verdict: {{ hubScanData.policy }}</h4>
                                            <p class="text-[11px] opacity-90">{{ hubScanData.summary || hubScanData.policy_reason || 'Scan otomatis selesai.' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Severity Breakdown -->
                                <div v-if="hubScanData.severity_counts" class="grid grid-cols-4 gap-2 text-center">
                                    <div class="p-2 rounded-lg bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900/40">
                                        <div class="text-base font-bold text-red-600 dark:text-red-400">{{ hubScanData.severity_counts.critical || 0 }}</div>
                                        <div class="text-[10px] text-gray-500">Critical</div>
                                    </div>
                                    <div class="p-2 rounded-lg bg-orange-50 dark:bg-orange-950/30 border border-orange-200 dark:border-orange-900/40">
                                        <div class="text-base font-bold text-orange-600 dark:text-orange-400">{{ hubScanData.severity_counts.high || 0 }}</div>
                                        <div class="text-[10px] text-gray-500">High</div>
                                    </div>
                                    <div class="p-2 rounded-lg bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/40">
                                        <div class="text-base font-bold text-amber-600 dark:text-amber-400">{{ hubScanData.severity_counts.medium || 0 }}</div>
                                        <div class="text-[10px] text-gray-500">Medium</div>
                                    </div>
                                    <div class="p-2 rounded-lg bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900/40">
                                        <div class="text-base font-bold text-blue-600 dark:text-blue-400">{{ hubScanData.severity_counts.low || 0 }}</div>
                                        <div class="text-[10px] text-gray-500">Low</div>
                                    </div>
                                </div>

                                <!-- Findings List -->
                                <div v-if="hubScanData.findings && hubScanData.findings.length > 0" class="space-y-2">
                                    <h5 class="font-bold text-xs text-gray-700 dark:text-gray-300">Temuan Keamanan:</h5>
                                    <div v-for="(f, idx) in hubScanData.findings" :key="idx" class="p-3 rounded-lg bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-800 text-xs">
                                        <div class="flex items-center justify-between mb-1">
                                            <span class="font-bold uppercase text-[10px]" :class="f.severity === 'critical' || f.severity === 'high' ? 'text-red-500' : 'text-amber-500'">
                                                {{ f.severity }} - {{ f.category }}
                                            </span>
                                            <span class="text-[10px] font-mono text-gray-400">{{ f.file }}:{{ f.line }}</span>
                                        </div>
                                        <p class="text-gray-600 dark:text-gray-300">{{ f.description }}</p>
                                    </div>
                                </div>
                                <div v-else class="p-4 rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-800 text-center text-gray-400 text-xs">
                                    <i class="fa-solid fa-shield-check text-emerald-500 mr-1.5"></i>
                                    Tidak ditemukan ancaman atau injeksi berbahaya dalam bundle ini.
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="px-6 py-3.5 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between bg-white dark:bg-[#1e1e2d]">
                        <span class="text-xs text-gray-400">
                            Trust: <strong class="text-gray-700 dark:text-gray-200 uppercase">{{ hubActiveDetail.trust_level }}</strong>
                        </span>

                        <div class="flex items-center gap-2">
                            <button
                                v-if="isSkillInstalled(hubActiveDetail)"
                                @click="handleHubUninstall(hubActiveDetail.name)"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-red-50 text-red-600 hover:bg-red-100 dark:bg-red-950/50 dark:text-red-400 dark:hover:bg-red-900/50 transition-colors"
                            >
                                Copot Skill
                            </button>
                            <button
                                v-else
                                @click="handleHubInstall(hubActiveDetail)"
                                :disabled="hubInstallingMap[hubActiveDetail.identifier]"
                                class="px-4 py-1.5 rounded-xl text-xs font-semibold bg-[#435ebe] text-white hover:bg-[#354bb4] transition-colors inline-flex items-center gap-1.5"
                            >
                                <i v-if="hubInstallingMap[hubActiveDetail.identifier]" class="fa-solid fa-spinner animate-spin text-xs"></i>
                                <span>Pasang Skill Ini</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- MODAL: UPDATES MODAL                       -->
            <!-- ========================================== -->
            <div
                v-if="updateModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4"
                @click.self="updateModalOpen = false"
            >
                <div class="w-full max-w-md rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <i class="fa-solid fa-arrows-rotate text-[#435ebe]"></i>
                            Pembaruan Skill
                        </h3>
                        <button @click="updateModalOpen = false" class="text-gray-400 hover:text-gray-200">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <div v-if="updateList.length === 0" class="text-center py-6 text-gray-400 text-xs">
                        <i class="fa-solid fa-circle-check text-emerald-500 text-3xl mb-2"></i>
                        <p class="font-semibold text-gray-800 dark:text-gray-200">Semua skill sudah mutakhir!</p>
                        <p class="text-gray-400 mt-1">Tidak ada rilis baru yang tersedia di upstream hub.</p>
                    </div>

                    <div v-else class="space-y-3">
                        <p class="text-xs text-gray-400">Skill berikut memiliki versi baru di repositori sumber:</p>
                        <div class="space-y-2 max-h-60 overflow-y-auto">
                            <div v-for="u in updateList" :key="u.identifier" class="p-3 rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-800 flex items-center justify-between text-xs">
                                <div>
                                    <div class="font-bold text-gray-900 dark:text-white">{{ u.name }}</div>
                                    <div class="text-[10px] text-gray-400 font-mono">{{ u.source }}</div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-100 text-[#435ebe] dark:bg-blue-950 dark:text-blue-300">Update Ada</span>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-2">
                            <button
                                @click="updateModalOpen = false"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300"
                            >
                                Tutup
                            </button>
                            <button
                                @click="triggerUpdateAll"
                                :disabled="isUpdatingAll"
                                class="px-4 py-1.5 rounded-xl text-xs font-semibold bg-[#435ebe] text-white hover:bg-[#354bb4] inline-flex items-center gap-1.5"
                            >
                                <i v-if="isUpdatingAll" class="fa-solid fa-spinner animate-spin"></i>
                                <span>Perbarui Semua</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
