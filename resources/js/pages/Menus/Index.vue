<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';

const props = defineProps({
    menus: {
        type: Array,
        default: () => [],
    },
    roles: {
        type: Array,
        default: () => [],
    },
    sections: {
        type: Array,
        default: () => [],
    },
});

const activeTab = ref('matrix'); // 'matrix' | 'builder' | 'roles'
const searchQuery = ref('');
const sectionFilter = ref('all');

// --- TAB 1: RBAC Matrix State ---
// Initialize matrix map: { [menuId]: [ 'admin', 'developer', ... ] }
const matrixMap = ref({});
props.menus.forEach((m) => {
    matrixMap.value[m.id] = Array.isArray(m.roles) ? [...m.roles] : ['admin'];
});

const isRoleAllowed = (menuId, roleName) => {
    const list = matrixMap.value[menuId] || [];
    return list.includes('*') || list.includes(roleName);
};

const toggleRolePermission = (menuId, roleName) => {
    // Admin is always protected
    if (roleName === 'admin') return;

    if (!matrixMap.value[menuId]) {
        matrixMap.value[menuId] = [];
    }

    const idx = matrixMap.value[menuId].indexOf(roleName);
    if (idx > -1) {
        matrixMap.value[menuId].splice(idx, 1);
    } else {
        matrixMap.value[menuId].push(roleName);
    }
};

const grantAllForMenu = (menuId) => {
    matrixMap.value[menuId] = props.roles.map(r => r.name);
};

const revokeNonAdminForMenu = (menuId) => {
    matrixMap.value[menuId] = ['admin'];
};

const isSavingMatrix = ref(false);
const saveRbacMatrix = () => {
    isSavingMatrix.value = true;
    router.post('/menus/matrix', { matrix: matrixMap.value }, {
        preserveScroll: true,
        onFinish: () => {
            isSavingMatrix.value = false;
        }
    });
};

// --- TAB 2: Menu Builder / Management ---
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const activeMenu = ref(null);

const addForm = useForm({
    section_key: 'core_chat',
    section_title: 'Core & Chat',
    section_order: 1,
    name: '',
    url: '',
    icon: 'fa-solid fa-cube',
    badge: '',
    order: 1,
    is_active: true,
    roles: ['admin', 'developer'],
    feature_code: '',
});

const editForm = useForm({
    section_key: 'core_chat',
    section_title: 'Core & Chat',
    section_order: 1,
    name: '',
    url: '',
    icon: 'fa-solid fa-cube',
    badge: '',
    order: 1,
    is_active: true,
    roles: [],
    feature_code: '',
});

const onSectionSelect = (formObj, sectionKey) => {
    const sec = props.sections.find(s => s.key === sectionKey);
    if (sec) {
        formObj.section_key = sec.key;
        formObj.section_title = sec.title;
        formObj.section_order = sec.order;
    }
};

const openAddModal = () => {
    addForm.reset();
    addForm.section_key = 'core_chat';
    addForm.section_title = 'Core & Chat';
    addForm.section_order = 1;
    addForm.roles = ['admin', 'developer'];
    isAddModalOpen.value = true;
};

const submitAddMenu = () => {
    addForm.post('/menus', {
        preserveScroll: true,
        onSuccess: () => {
            isAddModalOpen.value = false;
            addForm.reset();
        }
    });
};

const openEditModal = (menu) => {
    activeMenu.value = menu;
    editForm.section_key = menu.section_key;
    editForm.section_title = menu.section_title;
    editForm.section_order = menu.section_order;
    editForm.name = menu.name;
    editForm.url = menu.url;
    editForm.icon = menu.icon;
    editForm.badge = menu.badge || '';
    editForm.order = menu.order;
    editForm.is_active = Boolean(menu.is_active);
    editForm.roles = Array.isArray(menu.roles) ? [...menu.roles] : ['admin'];
    editForm.feature_code = menu.feature_code || '';
    isEditModalOpen.value = true;
};

const submitEditMenu = () => {
    if (!activeMenu.value) return;
    editForm.put(`/menus/${activeMenu.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            isEditModalOpen.value = false;
            activeMenu.value = null;
        }
    });
};

const toggleMenuStatus = (menu) => {
    router.post(`/menus/${menu.id}/toggle`, {}, {
        preserveScroll: true,
    });
};

const deleteMenu = (menu) => {
    if (confirm(`Yakin ingin menghapus menu '${menu.name}'?`)) {
        router.delete(`/menus/${menu.id}`, {
            preserveScroll: true,
        });
    }
};

// Filtered menus
const filteredMenus = computed(() => {
    return props.menus.filter((m) => {
        const matchesSection = sectionFilter.value === 'all' || m.section_key === sectionFilter.value;
        const q = searchQuery.value.trim().toLowerCase();
        const matchesQuery = !q ||
            m.name.toLowerCase().includes(q) ||
            m.url.toLowerCase().includes(q) ||
            m.section_title.toLowerCase().includes(q);
        return matchesSection && matchesQuery;
    });
});
</script>

<template>
    <AppLayout title="Menu & RBAC Manager">
        <div class="space-y-6">
            <!-- Header Toolbar -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                <div class="flex items-center gap-3.5">
                    <div class="w-11 h-11 rounded-2xl bg-[#435ebe]/10 dark:bg-[#435ebe]/20 text-[#435ebe] dark:text-blue-400 flex items-center justify-center font-bold text-xl shadow-xs">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                                Menu & RBAC Manager
                            </h2>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                Live Database
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Atur struktur menu sidebar, urutan, ikon, badge, dan kontrol hak akses per role (RBAC).
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <button
                        v-if="activeTab === 'matrix'"
                        @click="saveRbacMatrix"
                        :disabled="isSavingMatrix"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#354bb4] text-white flex items-center gap-2 transition cursor-pointer disabled:opacity-50 shadow-sm"
                    >
                        <i :class="['fa-solid text-xs', isSavingMatrix ? 'fa-spinner fa-spin' : 'fa-floppy-disk']"></i>
                        <span>{{ isSavingMatrix ? 'Menyimpan Matriks...' : 'Simpan Matriks RBAC' }}</span>
                    </button>

                    <button
                        @click="openAddModal"
                        class="px-4 py-2 rounded-xl text-xs font-bold bg-gray-900 hover:bg-black dark:bg-white dark:hover:bg-gray-100 text-white dark:text-gray-900 flex items-center gap-2 transition cursor-pointer shadow-sm"
                    >
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Tambah Menu Baru</span>
                    </button>
                </div>
            </div>

            <!-- Tab Selector -->
            <div class="flex items-center gap-2 p-1 bg-gray-100 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700/60 rounded-2xl w-fit select-none">
                <button
                    @click="activeTab = 'matrix'"
                    :class="[
                        'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold transition cursor-pointer',
                        activeTab === 'matrix'
                            ? 'bg-white dark:bg-[#1e1e2d] text-[#435ebe] dark:text-blue-400 shadow-xs'
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                    ]"
                >
                    <i class="fa-solid fa-table-cells text-xs"></i>
                    <span>Matriks Akses RBAC</span>
                </button>

                <button
                    @click="activeTab = 'builder'"
                    :class="[
                        'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold transition cursor-pointer',
                        activeTab === 'builder'
                            ? 'bg-white dark:bg-[#1e1e2d] text-[#435ebe] dark:text-blue-400 shadow-xs'
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                    ]"
                >
                    <i class="fa-solid fa-bars-staggered text-xs"></i>
                    <span>Daftar & Konfigurasi Menu ({{ menus.length }})</span>
                </button>

                <button
                    @click="activeTab = 'roles'"
                    :class="[
                        'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold transition cursor-pointer',
                        activeTab === 'roles'
                            ? 'bg-white dark:bg-[#1e1e2d] text-[#435ebe] dark:text-blue-400 shadow-xs'
                            : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                    ]"
                >
                    <i class="fa-solid fa-shield-halved text-xs"></i>
                    <span>Daftar Peran (Roles & Permissions)</span>
                </button>
            </div>

            <!-- ========================================== -->
            <!-- TAB 1: RBAC PERMISSIONS MATRIX             -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'matrix'" class="space-y-4">
                <div class="bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-xs">
                    <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-gray-50/50 dark:bg-black/10">
                        <div>
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <i class="fa-solid fa-sliders text-[#435ebe]"></i>
                                <span>Matriks Kontrol Hak Akses Sidebar & Fitur</span>
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Centang role yang diizinkan untuk melihat dan mengakses setiap menu di sidebar dashboard.
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] text-gray-400">
                                <i class="fa-solid fa-circle-info mr-1 text-[#435ebe]"></i>
                                Role <b>Admin</b> memiliki izin penuh untuk semua menu.
                            </span>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-gray-50/80 dark:bg-black/20 border-b border-gray-200 dark:border-gray-800 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <th class="py-3 px-4 min-w-[200px]">Menu & Rute URL</th>
                                    <th class="py-3 px-3">Kategori Section</th>
                                    <th
                                        v-for="r in roles"
                                        :key="r.name"
                                        class="py-3 px-3 text-center min-w-[110px]"
                                    >
                                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg" :class="r.name === 'admin' ? 'bg-[#435ebe]/10 text-[#435ebe] font-extrabold' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'">
                                            <span>{{ r.label }}</span>
                                        </div>
                                    </th>
                                    <th class="py-3 px-3 text-right">Quick Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                                <tr
                                    v-for="m in menus"
                                    :key="m.id"
                                    class="hover:bg-gray-50/70 dark:hover:bg-white/[0.02] transition"
                                >
                                    <!-- Menu Name & Icon -->
                                    <td class="py-3 px-4">
                                        <div class="flex items-center gap-2.5">
                                            <div class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-600 dark:text-gray-300 shrink-0">
                                                <i :class="[m.icon, 'text-xs text-[#435ebe] dark:text-blue-400']"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <div class="font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                                                    <span>{{ m.name }}</span>
                                                    <span v-if="m.badge" class="text-[9px] px-1.5 py-0.2 rounded-full bg-[#435ebe]/10 text-[#435ebe] font-semibold">{{ m.badge }}</span>
                                                </div>
                                                <div class="text-[10px] text-gray-400 font-mono">{{ m.url }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Section -->
                                    <td class="py-3 px-3 whitespace-nowrap">
                                        <span class="text-[10px] px-2 py-0.5 rounded-md font-semibold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                                            {{ m.section_title }}
                                        </span>
                                    </td>

                                    <!-- Role Checkboxes -->
                                    <td
                                        v-for="r in roles"
                                        :key="r.name"
                                        class="py-3 px-3 text-center"
                                    >
                                        <label class="inline-flex items-center justify-center cursor-pointer">
                                            <input
                                                type="checkbox"
                                                :checked="isRoleAllowed(m.id, r.name)"
                                                :disabled="r.name === 'admin'"
                                                @change="toggleRolePermission(m.id, r.name)"
                                                class="w-4 h-4 rounded text-[#435ebe] focus:ring-[#435ebe] cursor-pointer disabled:opacity-50"
                                            />
                                        </label>
                                    </td>

                                    <!-- Quick Grant / Revoke Actions -->
                                    <td class="py-3 px-3 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1">
                                            <button
                                                @click="grantAllForMenu(m.id)"
                                                class="px-2 py-1 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400 hover:bg-emerald-100 transition"
                                                title="Izinkan semua role"
                                            >
                                                Semua
                                            </button>
                                            <button
                                                @click="revokeNonAdminForMenu(m.id)"
                                                class="px-2 py-1 rounded text-[10px] font-semibold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 hover:bg-gray-200 transition"
                                                title="Hanya Admin"
                                            >
                                                Admin Only
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-black/10 flex items-center justify-between">
                        <span class="text-xs text-gray-500">
                            Perubahan matriks akan aktif di sidebar pengguna sesuai role akun mereka.
                        </span>
                        <button
                            @click="saveRbacMatrix"
                            :disabled="isSavingMatrix"
                            class="px-4 py-2 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#354bb4] text-white flex items-center gap-2 transition cursor-pointer disabled:opacity-50 shadow-sm"
                        >
                            <i :class="['fa-solid text-xs', isSavingMatrix ? 'fa-spinner fa-spin' : 'fa-floppy-disk']"></i>
                            <span>{{ isSavingMatrix ? 'Menyimpan...' : 'Simpan Matriks RBAC' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 2: MENU BUILDER & CONFIG               -->
            <!-- ========================================== -->
            <div v-else-if="activeTab === 'builder'" class="space-y-4">
                <!-- Filter Bar -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <div class="relative w-full sm:w-80">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari menu, rute URL, atau section..."
                            class="w-full pl-9 pr-3.5 py-2 rounded-xl text-xs bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:border-[#435ebe]"
                        />
                    </div>

                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400">Section:</span>
                        <select
                            v-model="sectionFilter"
                            class="px-3 py-2 rounded-xl text-xs font-semibold bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none cursor-pointer"
                        >
                            <option value="all">Semua Section ({{ menus.length }})</option>
                            <option v-for="sec in sections" :key="sec.key" :value="sec.key">
                                {{ sec.title }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Menus Table -->
                <div class="bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 rounded-2xl overflow-hidden shadow-xs">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="bg-gray-50/80 dark:bg-black/20 border-b border-gray-200 dark:border-gray-800 text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                    <th class="py-3 px-4">Menu & Ikon</th>
                                    <th class="py-3 px-4">Rute URL</th>
                                    <th class="py-3 px-4">Section Kategori</th>
                                    <th class="py-3 px-4">Urutan</th>
                                    <th class="py-3 px-4">Hak Akses Role</th>
                                    <th class="py-3 px-4">Status</th>
                                    <th class="py-3 px-4 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60">
                                <tr
                                    v-for="m in filteredMenus"
                                    :key="m.id"
                                    class="hover:bg-gray-50/70 dark:hover:bg-white/[0.02] transition"
                                >
                                    <!-- Name & Icon -->
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-xl bg-[#435ebe]/10 dark:bg-[#435ebe]/20 text-[#435ebe] dark:text-blue-400 flex items-center justify-center shrink-0">
                                                <i :class="[m.icon, 'text-sm']"></i>
                                            </div>
                                            <div>
                                                <div class="font-bold text-gray-900 dark:text-white text-xs flex items-center gap-2">
                                                    <span>{{ m.name }}</span>
                                                    <span v-if="m.badge" class="text-[9px] px-1.5 py-0.2 rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30 font-bold">
                                                        {{ m.badge }}
                                                    </span>
                                                </div>
                                                <div class="text-[10px] text-gray-400 font-mono">{{ m.icon }}</div>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- URL -->
                                    <td class="py-3.5 px-4 font-mono text-gray-700 dark:text-gray-300">
                                        {{ m.url }}
                                    </td>

                                    <!-- Section -->
                                    <td class="py-3.5 px-4">
                                        <span class="text-[11px] font-semibold text-gray-700 dark:text-gray-300">
                                            {{ m.section_title }}
                                        </span>
                                    </td>

                                    <!-- Order -->
                                    <td class="py-3.5 px-4 font-mono font-bold text-gray-600 dark:text-gray-400">
                                        {{ m.order }}
                                    </td>

                                    <!-- Roles Badges -->
                                    <td class="py-3.5 px-4">
                                        <div class="flex items-center gap-1 flex-wrap">
                                            <span
                                                v-for="role in (m.roles || ['admin'])"
                                                :key="role"
                                                class="px-2 py-0.5 rounded text-[10px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 uppercase"
                                            >
                                                {{ role }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Status Toggle -->
                                    <td class="py-3.5 px-4">
                                        <button
                                            @click="toggleMenuStatus(m)"
                                            :class="[
                                                'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold cursor-pointer transition select-none',
                                                m.is_active
                                                    ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-500/30'
                                                    : 'bg-gray-100 text-gray-500 border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700'
                                            ]"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full" :class="m.is_active ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                                            <span>{{ m.is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                        </button>
                                    </td>

                                    <!-- Actions -->
                                    <td class="py-3.5 px-4 text-right">
                                        <div class="flex items-center justify-end gap-1.5">
                                            <button
                                                @click="openEditModal(m)"
                                                class="p-1.5 rounded-lg text-gray-400 hover:text-[#435ebe] hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                                title="Edit Konfigurasi Menu"
                                            >
                                                <i class="fa-solid fa-pen-to-square text-xs"></i>
                                            </button>
                                            <button
                                                @click="deleteMenu(m)"
                                                class="p-1.5 rounded-lg text-gray-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10 transition"
                                                title="Hapus Menu"
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
            </div>

            <!-- ========================================== -->
            <!-- TAB 3: ROLES & PERMISSIONS OVERVIEW        -->
            <!-- ========================================== -->
            <div v-else-if="activeTab === 'roles'" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="r in roles"
                        :key="r.name"
                        class="p-5 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs space-y-3"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <div class="w-9 h-9 rounded-xl bg-[#435ebe]/10 dark:bg-[#435ebe]/20 text-[#435ebe] dark:text-blue-400 flex items-center justify-center font-bold text-sm">
                                    <i :class="r.name === 'admin' ? 'fa-solid fa-crown' : (r.name === 'developer' ? 'fa-solid fa-code' : (r.name === 'reviewer' ? 'fa-solid fa-glasses' : 'fa-solid fa-user'))"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-gray-900 dark:text-white text-sm">
                                        {{ r.label }} ({{ r.name }})
                                    </h3>
                                    <span v-if="r.is_system" class="text-[9px] font-mono text-gray-400 uppercase">System Role</span>
                                </div>
                            </div>
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-blue-50 text-[#435ebe] dark:bg-[#435ebe]/20 dark:text-blue-300">
                                {{ menus.filter(m => (m.roles || []).includes(r.name) || r.name === 'admin').length }} Menu Diakses
                            </span>
                        </div>

                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ r.description }}
                        </p>

                        <!-- Permissions Badges -->
                        <div class="pt-3 border-t border-gray-100 dark:border-gray-800 space-y-1.5">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-gray-400 block">Izin Fitur:</span>
                            <div class="flex flex-wrap gap-1.5">
                                <span
                                    v-for="(val, perm) in (r.feature_permissions || {})"
                                    :key="perm"
                                    :class="[
                                        'px-2 py-0.5 rounded text-[10px] font-semibold border',
                                        val
                                            ? 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-500/30'
                                            : 'bg-gray-100 text-gray-400 border-gray-200 dark:bg-gray-800 dark:text-gray-500 dark:border-gray-700 line-through'
                                    ]"
                                >
                                    {{ perm }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- MODAL: ADD MENU                            -->
            <!-- ========================================== -->
            <div
                v-if="isAddModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            >
                <div class="w-full max-w-lg rounded-3xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                        <h3 class="font-bold text-gray-900 dark:text-white text-base flex items-center gap-2">
                            <i class="fa-solid fa-plus-circle text-[#435ebe]"></i>
                            <span>Tambah Item Menu Baru</span>
                        </h3>
                        <button @click="isAddModalOpen = false" class="text-gray-400 hover:text-gray-700 dark:hover:text-white transition">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitAddMenu" class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    Section Kategori
                                </label>
                                <select
                                    :value="addForm.section_key"
                                    @change="onSectionSelect(addForm, $event.target.value)"
                                    class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-xs focus:border-[#435ebe] outline-none"
                                >
                                    <option v-for="sec in sections" :key="sec.key" :value="sec.key">
                                        {{ sec.title }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    Urutan (Order)
                                </label>
                                <input
                                    v-model="addForm.order"
                                    type="number"
                                    required
                                    class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-xs focus:border-[#435ebe] outline-none"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    Nama Menu <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="addForm.name"
                                    type="text"
                                    required
                                    placeholder="Contoh: Analytics Hub"
                                    class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-xs focus:border-[#435ebe] outline-none"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    Rute URL <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="addForm.url"
                                    type="text"
                                    required
                                    placeholder="/analytics"
                                    class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-xs font-mono focus:border-[#435ebe] outline-none"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    FontAwesome Icon
                                </label>
                                <input
                                    v-model="addForm.icon"
                                    type="text"
                                    required
                                    placeholder="fa-solid fa-chart-pie"
                                    class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-xs font-mono focus:border-[#435ebe] outline-none"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    Badge (Opsional)
                                </label>
                                <input
                                    v-model="addForm.badge"
                                    type="text"
                                    placeholder="Contoh: Beta / New"
                                    class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-xs focus:border-[#435ebe] outline-none"
                                />
                            </div>
                        </div>

                        <!-- Roles allowed -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                Role yang Diizinkan Melihat Menu Ini:
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <label
                                    v-for="r in roles"
                                    :key="r.name"
                                    class="flex items-center gap-2 p-2 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer text-xs"
                                >
                                    <input
                                        type="checkbox"
                                        :value="r.name"
                                        v-model="addForm.roles"
                                        class="w-4 h-4 rounded text-[#435ebe]"
                                    />
                                    <span>{{ r.label }}</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                            <button
                                type="button"
                                @click="isAddModalOpen = false"
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="addForm.processing"
                                class="px-4 py-2 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#354bb4] text-white transition disabled:opacity-50"
                            >
                                Simpan Menu
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- MODAL: EDIT MENU                           -->
            <!-- ========================================== -->
            <div
                v-if="isEditModalOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
            >
                <div class="w-full max-w-lg rounded-3xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 p-6 shadow-xl space-y-4">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3">
                        <h3 class="font-bold text-gray-900 dark:text-white text-base flex items-center gap-2">
                            <i class="fa-solid fa-pen-to-square text-[#435ebe]"></i>
                            <span>Edit Konfigurasi Menu: {{ activeMenu?.name }}</span>
                        </h3>
                        <button @click="isEditModalOpen = false" class="text-gray-400 hover:text-gray-700 dark:hover:text-white transition">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitEditMenu" class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    Section Kategori
                                </label>
                                <select
                                    :value="editForm.section_key"
                                    @change="onSectionSelect(editForm, $event.target.value)"
                                    class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-xs focus:border-[#435ebe] outline-none"
                                >
                                    <option v-for="sec in sections" :key="sec.key" :value="sec.key">
                                        {{ sec.title }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    Urutan (Order)
                                </label>
                                <input
                                    v-model="editForm.order"
                                    type="number"
                                    required
                                    class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-xs focus:border-[#435ebe] outline-none"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    Nama Menu
                                </label>
                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    required
                                    class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-xs focus:border-[#435ebe] outline-none"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    Rute URL
                                </label>
                                <input
                                    v-model="editForm.url"
                                    type="text"
                                    required
                                    class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-xs font-mono focus:border-[#435ebe] outline-none"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    FontAwesome Icon
                                </label>
                                <input
                                    v-model="editForm.icon"
                                    type="text"
                                    required
                                    class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-xs font-mono focus:border-[#435ebe] outline-none"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                    Badge (Opsional)
                                </label>
                                <input
                                    v-model="editForm.badge"
                                    type="text"
                                    class="w-full px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-xs focus:border-[#435ebe] outline-none"
                                />
                            </div>
                        </div>

                        <!-- Roles allowed -->
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                Role yang Diizinkan Melihat Menu Ini:
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <label
                                    v-for="r in roles"
                                    :key="r.name"
                                    class="flex items-center gap-2 p-2 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer text-xs"
                                >
                                    <input
                                        type="checkbox"
                                        :value="r.name"
                                        v-model="editForm.roles"
                                        class="w-4 h-4 rounded text-[#435ebe]"
                                    />
                                    <span>{{ r.label }}</span>
                                </label>
                            </div>
                        </div>

                        <!-- Status toggle -->
                        <div class="flex items-center gap-2 pt-2">
                            <input
                                id="edit_active"
                                v-model="editForm.is_active"
                                type="checkbox"
                                class="w-4 h-4 rounded text-[#435ebe]"
                            />
                            <label for="edit_active" class="text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                Aktifkan Menu (Tampil di Sidebar)
                            </label>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                            <button
                                type="button"
                                @click="isEditModalOpen = false"
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="px-4 py-2 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#354bb4] text-white transition disabled:opacity-50"
                            >
                                Perbarui Menu
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
