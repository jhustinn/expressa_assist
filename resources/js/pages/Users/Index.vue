<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';

const props = defineProps({
    users: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
});

// Quick Search, Filter & Pagination State
const searchQuery = ref('');
const roleFilter = ref('all');
const statusFilter = ref('all');
const perPage = ref(10);
const currentPage = ref(1);

// Modals State
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const activeUser = ref(null);

// Telegram Auto-Lookup State
const isLookingUp = ref(false);
const lookupSuccess = ref(false);
const lookupMessage = ref('');
let lookupTimer = null;

const onTelegramIdInput = () => {
    lookupSuccess.value = false;
    lookupMessage.value = '';
    clearTimeout(lookupTimer);
    const id = (addForm.telegram_id || '').trim();
    if (id.length >= 6 && /^\d+$/.test(id)) {
        lookupTimer = setTimeout(() => {
            fetchTelegramProfile();
        }, 500);
    }
};

const fetchTelegramProfile = async () => {
    const id = (addForm.telegram_id || '').trim();
    if (!id || !/^\d+$/.test(id)) {
        lookupMessage.value = 'Masukkan ID angka valid (misal: 7605759955)';
        return;
    }

    isLookingUp.value = true;
    lookupSuccess.value = false;
    lookupMessage.value = '';

    try {
        const response = await fetch(`/api/telegram/lookup/${id}`);
        const data = await response.json();

        if (response.ok && data.success) {
            lookupSuccess.value = true;
            if (data.name) {
                addForm.name = data.name;
            }
            if (data.username) {
                addForm.username = data.username;
            } else if (data.name) {
                const autoSlug = data.name.toLowerCase().replace(/[^a-z0-9_]/g, '');
                addForm.username = autoSlug ? ('@' + autoSlug) : '';
            }
            lookupMessage.value = `Profil Telegram terdeteksi: ${data.name}${addForm.username ? ' (' + addForm.username + ')' : ''}`;
        } else {
            lookupSuccess.value = false;
            lookupMessage.value = data.message || 'User belum pernah berinteraksi dengan bot, silakan lengkapi nama manual.';
        }
    } catch (e) {
        lookupSuccess.value = false;
        lookupMessage.value = 'Gagal menghubungi server untuk pencarian profil Telegram.';
    } finally {
        isLookingUp.value = false;
    }
};

const addForm = useForm({
    telegram_id: '',
    name: '',
    username: '',
    email: '',
    password: '',
    role: 'developer',
    can_code: true,
    can_terminal: false,
    notes: '',
});

const editForm = useForm({
    name: '',
    username: '',
    email: '',
    password: '',
    role: 'developer',
    can_code: true,
    can_terminal: false,
    notes: '',
});

// Reset Password Modal
const isResetPasswordModalOpen = ref(false);
const targetUserForPassword = ref(null);
const resetPasswordForm = useForm({
    password: '',
});

const openResetPasswordModal = (user) => {
    targetUserForPassword.value = user;
    resetPasswordForm.reset();
    isResetPasswordModalOpen.value = true;
};

const submitResetPassword = () => {
    if (!targetUserForPassword.value) return;
    const userId = targetUserForPassword.value.id || targetUserForPassword.value.global_user_id;
    resetPasswordForm.post(`/users/${userId}/password`, {
        onSuccess: () => {
            isResetPasswordModalOpen.value = false;
            targetUserForPassword.value = null;
            resetPasswordForm.reset();
        },
    });
};

// Filter logic
const filteredUsers = computed(() => {
    return props.users.filter((user) => {
        const q = searchQuery.value.trim().toLowerCase();
        const name = (user.name || user.global_user_nama || '').toLowerCase();
        const username = (user.username || user.global_user_username || '').toLowerCase();
        const telegramId = String(user.telegram_id || user.global_user_telegram_id || '').toLowerCase();
        const role = (user.role || user.global_user_role || '').toLowerCase();
        const notes = (user.notes || user.global_user_notes || '').toLowerCase();

        const matchesQuery = !q || (
            name.includes(q) ||
            username.includes(q) ||
            telegramId.includes(q) ||
            role.includes(q) ||
            notes.includes(q)
        );

        const currentRole = user.role || user.global_user_role;
        const matchesRole = roleFilter.value === 'all' || currentRole === roleFilter.value;

        const isActive = user.is_active !== undefined ? user.is_active : user.global_user_is_active;
        const matchesStatus = statusFilter.value === 'all' ||
            (statusFilter.value === 'active' && Boolean(isActive)) ||
            (statusFilter.value === 'suspended' && !Boolean(isActive));

        return matchesQuery && matchesRole && matchesStatus;
    });
});

// Pagination logic
const totalPages = computed(() => Math.ceil(filteredUsers.value.length / perPage.value) || 1);

const paginatedUsers = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredUsers.value.slice(start, start + perPage.value);
});

const fromRecord = computed(() => {
    if (filteredUsers.value.length === 0) return 0;
    return (currentPage.value - 1) * perPage.value + 1;
});

const toRecord = computed(() => {
    return Math.min(currentPage.value * perPage.value, filteredUsers.value.length);
});

const setPage = (p) => {
    if (p >= 1 && p <= totalPages.value) {
        currentPage.value = p;
    }
};

const resetFilters = () => {
    searchQuery.value = '';
    roleFilter.value = 'all';
    statusFilter.value = 'all';
    currentPage.value = 1;
};

// Reset to page 1 whenever filters change
watch([searchQuery, roleFilter, statusFilter, perPage], () => {
    currentPage.value = 1;
});

// Modal Actions
const openAddModal = () => {
    addForm.reset();
    lookupSuccess.value = false;
    lookupMessage.value = '';
    isLookingUp.value = false;
    isAddModalOpen.value = true;
};

const submitAddUser = () => {
    addForm.post('/users', {
        onSuccess: () => {
            isAddModalOpen.value = false;
            addForm.reset();
        },
    });
};

const openEditModal = (user) => {
    activeUser.value = user;
    editForm.name = user.name || user.global_user_nama;
    editForm.username = (user.username || user.global_user_username || '').replace('@', '');
    editForm.email = user.email || user.global_user_email || '';
    editForm.password = '';
    editForm.role = user.role || user.global_user_role;
    editForm.can_code = Boolean(user.can_code !== undefined ? user.can_code : user.global_user_can_code);
    editForm.can_terminal = Boolean(user.can_terminal !== undefined ? user.can_terminal : user.global_user_can_terminal);
    editForm.notes = user.notes || user.global_user_notes || '';
    isEditModalOpen.value = true;
};

const submitEditUser = () => {
    if (!activeUser.value) return;
    const userId = activeUser.value.id || activeUser.value.global_user_id;
    editForm.put(`/users/${userId}`, {
        onSuccess: () => {
            isEditModalOpen.value = false;
            activeUser.value = null;
        },
    });
};

const toggleStatus = (user) => {
    const userId = user.id || user.global_user_id;
    router.post(`/users/${userId}/toggle`, {}, {
        preserveScroll: true,
    });
};

const deleteUser = (user) => {
    const userId = user.id || user.global_user_id;
    const userName = user.name || user.global_user_nama;
    const telegramId = user.telegram_id || user.global_user_telegram_id;
    if (confirm(`Yakin ingin menghapus user ${userName} (${telegramId}) dari whitelist?`)) {
        router.delete(`/users/${userId}`, {
            preserveScroll: true,
        });
    }
};

const syncEnv = () => {
    router.post('/users/sync-env', {}, {
        preserveScroll: true,
    });
};

const getInitials = (name) => {
    if (!name) return '??';
    return name
        .split(' ')
        .map(n => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
};

const getRoleClass = (role) => {
    switch (role) {
        case 'admin':
            return 'bg-purple-100 text-purple-700 border border-purple-200 dark:bg-purple-950/50 dark:text-purple-300 dark:border-purple-500/40';
        case 'developer':
            return 'bg-blue-100 text-[#435ebe] border border-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-500/40';
        case 'reviewer':
            return 'bg-emerald-100 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-500/40';
        default:
            return 'bg-gray-100 text-gray-700 border border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700';
    }
};
</script>

<template>
    <AppLayout title="User Management & RBAC">
        <div class="space-y-6">
            <!-- Top Header & Breadcrumb -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                        User Management & Whitelist
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Atur akses Telegram User ID, hak akses Role-Based Access Control (RBAC), dan otorisasi eksekusi kode.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        @click="syncEnv"
                        class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-700 transition flex items-center gap-2"
                        title="Resync ke E:\hermes\.env"
                    >
                        <i class="fa-solid fa-rotate text-[#435ebe]"></i> Sync .env
                    </button>
                    <button
                        @click="openAddModal"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-[#435ebe] text-white hover:bg-[#3950a2] transition flex items-center gap-2 shadow-xs"
                    >
                        <i class="fa-solid fa-user-plus"></i> Tambah User Whitelist
                    </button>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="p-4 rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Total User</span>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ stats.total || 0 }}</div>
                    <span class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 block">Terdaftar di database</span>
                </div>
                <div class="p-4 rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Whitelisted (Active)</span>
                    <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1">{{ stats.active || 0 }}</div>
                    <span class="text-[11px] text-emerald-700/80 dark:text-emerald-400/80 mt-0.5 block">Dapat mengakses agent</span>
                </div>
                <div class="p-4 rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Admin & Developer</span>
                    <div class="text-2xl font-bold text-[#435ebe] dark:text-blue-400 mt-1">
                        {{ (stats.admins || 0) + (stats.developers || 0) }}
                    </div>
                    <span class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5 block">{{ stats.admins || 0 }} Admin, {{ stats.developers || 0 }} Dev</span>
                </div>
                <div class="p-4 rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs">
                    <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider block">Expressa .env Status</span>
                    <div class="text-sm font-mono font-bold text-gray-900 dark:text-gray-100 mt-2 truncate" :title="stats.env_synced_string">
                        {{ stats.env_synced_string || 'Kosong' }}
                    </div>
                    <span class="text-[10px] font-medium text-emerald-600 dark:text-emerald-400 mt-0.5 flex items-center gap-1">
                        <i class="fa-solid fa-circle-check text-[10px]"></i> Tersinkron ke daemon
                    </span>
                </div>
            </div>

            <!-- Table Card: Telegram Whitelist & RBAC Management -->
            <div class="rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 overflow-hidden shadow-xs">
                <!-- Header -->
                <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-gray-200 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-[#435ebe]/20 text-[#435ebe] dark:text-blue-400 flex items-center justify-center text-lg shrink-0 border border-blue-100 dark:border-blue-500/20">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-900 dark:text-white text-base">
                                Telegram Whitelist & RBAC Management
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                Kelola user Telegram yang diizinkan mengakses bot dan berikan hak akses role sesuai kebutuhan.
                            </p>
                        </div>
                    </div>
                    <button
                        @click="openAddModal"
                        class="px-4 py-2.5 rounded-xl text-xs font-semibold bg-[#435ebe] text-white hover:bg-[#3950a2] transition flex items-center gap-2 shrink-0 self-start sm:self-auto shadow-xs"
                    >
                        <i class="fa-solid fa-user-plus"></i> Tambah User Whitelist
                    </button>
                </div>

                <!-- Quick Search & Filter Toolbar -->
                <div class="p-4 border-b border-gray-200 dark:border-gray-800/80 bg-gray-50/50 dark:bg-black/10 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-2.5 flex-1">
                        <!-- Quick Search Input -->
                        <div class="relative w-full sm:w-72">
                            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Quick search nama, username, ID..."
                                class="w-full pl-9 pr-8 py-2 rounded-xl text-xs bg-white dark:bg-[#151521] border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:border-[#435ebe] focus:ring-1 focus:ring-[#435ebe] outline-none transition"
                            />
                            <button
                                v-if="searchQuery"
                                @click="searchQuery = ''"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-0.5"
                                title="Hapus pencarian"
                            >
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                        </div>

                        <!-- Role Filter -->
                        <select
                            v-model="roleFilter"
                            class="px-3 py-2 rounded-xl text-xs bg-white dark:bg-[#151521] border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 outline-none focus:border-[#435ebe]"
                        >
                            <option value="all">Semua Role</option>
                            <option value="admin">Admin</option>
                            <option value="developer">Developer</option>
                            <option value="reviewer">Reviewer</option>
                            <option value="guest">Guest</option>
                        </select>

                        <!-- Status Filter -->
                        <select
                            v-model="statusFilter"
                            class="px-3 py-2 rounded-xl text-xs bg-white dark:bg-[#151521] border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 outline-none focus:border-[#435ebe]"
                        >
                            <option value="all">Semua Status</option>
                            <option value="active">Active (Whitelisted)</option>
                            <option value="suspended">Suspended</option>
                        </select>

                        <!-- Reset Filter Button -->
                        <button
                            v-if="searchQuery || roleFilter !== 'all' || statusFilter !== 'all'"
                            @click="resetFilters"
                            class="px-3 py-2 rounded-xl text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-800 transition flex items-center gap-1.5"
                            title="Reset semua filter"
                        >
                            <i class="fa-solid fa-filter-circle-xmark text-[#435ebe]"></i> Reset Filter
                        </button>
                    </div>

                    <!-- Per Page Selector -->
                    <div class="flex items-center gap-2 self-end md:self-auto text-xs text-gray-500 dark:text-gray-400">
                        <span>Tampilkan:</span>
                        <select
                            v-model="perPage"
                            class="px-2.5 py-1.5 rounded-lg text-xs bg-white dark:bg-[#151521] border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 outline-none focus:border-[#435ebe]"
                        >
                            <option :value="5">5</option>
                            <option :value="10">10</option>
                            <option :value="25">25</option>
                            <option :value="50">50</option>
                        </select>
                        <span>baris</span>
                    </div>
                </div>

                <!-- Data Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-black/20 text-[11px] font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                <th class="px-6 py-3.5">USER</th>
                                <th class="px-6 py-3.5">TELEGRAM USER ID</th>
                                <th class="px-6 py-3.5">ROLE</th>
                                <th class="px-6 py-3.5">PERMISSIONS</th>
                                <th class="px-6 py-3.5">STATUS WHITELIST</th>
                                <th class="px-6 py-3.5 text-right">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800/60">
                            <tr
                                v-for="user in paginatedUsers"
                                :key="user.id || user.global_user_id"
                                class="hover:bg-gray-50/70 dark:hover:bg-white/[0.02] transition"
                            >
                                <!-- USER -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-[#435ebe]/20 text-[#435ebe] dark:text-blue-400 font-bold text-xs flex items-center justify-center shrink-0 border border-blue-100 dark:border-blue-500/20">
                                            {{ getInitials(user.name || user.global_user_nama) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900 dark:text-white text-sm flex items-center gap-2">
                                                <span>{{ user.name || user.global_user_nama }}</span>
                                                <span v-if="user.has_password" class="text-[9px] px-1.5 py-0.2 rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-500/30 font-semibold" title="Password aktif di database">
                                                    <i class="fa-solid fa-lock text-[8px] mr-0.5"></i> Password
                                                </span>
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400 font-mono flex items-center gap-2">
                                                <span>{{ user.username || user.global_user_username || '-' }}</span>
                                                <span v-if="user.email || user.global_user_email" class="text-[11px] text-gray-400">• {{ user.email || user.global_user_email }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- TELEGRAM USER ID -->
                                <td class="px-6 py-4">
                                    <span class="font-mono text-sm font-semibold text-gray-800 dark:text-gray-200">
                                        {{ user.telegram_id || user.global_user_telegram_id }}
                                    </span>
                                </td>

                                <!-- ROLE -->
                                <td class="px-6 py-4">
                                    <span
                                        :class="[
                                            'font-mono font-bold text-[11px] uppercase px-2.5 py-1 rounded inline-block tracking-wider',
                                            getRoleClass(user.role || user.global_user_role)
                                        ]"
                                    >
                                        {{ user.role || user.global_user_role }}
                                    </span>
                                </td>

                                <!-- PERMISSIONS -->
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span
                                            v-if="user.can_code !== undefined ? user.can_code : user.global_user_can_code"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-50 text-[#435ebe] border border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-500/30"
                                        >
                                            <i class="fa-solid fa-code text-[11px]"></i>
                                            Coding
                                        </span>
                                        <span
                                            v-if="user.can_terminal !== undefined ? user.can_terminal : user.global_user_can_terminal"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-500/30"
                                        >
                                            <i class="fa-solid fa-terminal text-[11px]"></i>
                                            Shell
                                        </span>
                                        <span
                                            v-if="!(user.can_code !== undefined ? user.can_code : user.global_user_can_code) && !(user.can_terminal !== undefined ? user.can_terminal : user.global_user_can_terminal)"
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700"
                                        >
                                            <i class="fa-solid fa-eye text-[11px]"></i>
                                            Read-only
                                        </span>
                                    </div>
                                </td>

                                <!-- STATUS WHITELIST -->
                                <td class="px-6 py-4">
                                    <button
                                        @click="toggleStatus(user)"
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold cursor-pointer transition select-none',
                                            (user.is_active !== undefined ? user.is_active : user.global_user_is_active)
                                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-500/30 dark:hover:bg-emerald-900/40'
                                                : 'bg-gray-100 text-gray-700 border border-gray-200 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700'
                                        ]"
                                        :title="(user.is_active !== undefined ? user.is_active : user.global_user_is_active) ? 'Klik untuk suspend' : 'Klik untuk whitelist'"
                                    >
                                        <i
                                            :class="[
                                                'fa-solid fa-circle text-[7px]',
                                                (user.is_active !== undefined ? user.is_active : user.global_user_is_active) ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500'
                                            ]"
                                        ></i>
                                        {{ (user.is_active !== undefined ? user.is_active : user.global_user_is_active) ? 'Active (Whitelisted)' : 'Suspended' }}
                                    </button>
                                </td>

                                <!-- AKSI -->
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="openResetPasswordModal(user)"
                                            class="p-1.5 rounded-lg text-gray-500 hover:text-amber-500 dark:text-gray-400 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-500/10 transition"
                                            title="Reset Password Akun"
                                        >
                                            <i class="fa-solid fa-key text-sm"></i>
                                        </button>
                                        <button
                                            @click="openEditModal(user)"
                                            class="p-1.5 rounded-lg text-gray-500 hover:text-[#435ebe] dark:text-gray-400 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                            title="Edit User"
                                        >
                                            <i class="fa-regular fa-pen-to-square text-sm"></i>
                                        </button>
                                        <button
                                            @click="deleteUser(user)"
                                            class="p-1.5 rounded-lg text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition"
                                            title="Hapus User"
                                        >
                                            <i class="fa-regular fa-trash-can text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty Search / Filter State -->
                            <tr v-if="filteredUsers.length === 0 && users.length > 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                    <i class="fa-solid fa-magnifying-glass text-3xl mb-2 text-gray-400 dark:text-gray-600"></i>
                                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Tidak ada user yang cocok</p>
                                    <p class="text-xs text-gray-500 mt-1">Coba sesuaikan kata kunci pencarian atau reset filter.</p>
                                    <button
                                        @click="resetFilters"
                                        class="mt-3 px-3.5 py-1.5 rounded-xl text-xs font-semibold bg-[#435ebe] text-white hover:bg-[#3950a2] transition inline-flex items-center gap-1.5 shadow-xs"
                                    >
                                        <i class="fa-solid fa-rotate-left"></i> Reset Filter
                                    </button>
                                </td>
                            </tr>

                            <!-- Empty Database State -->
                            <tr v-if="users.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                    <i class="fa-solid fa-user-shield text-3xl mb-2 text-gray-400 dark:text-gray-600"></i>
                                    <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Belum ada user di whitelist</p>
                                    <p class="text-xs text-gray-500 mt-1">Klik tombol di atas untuk menambahkan user Telegram pertama Anda.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-black/10 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <!-- Showing records info -->
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Menampilkan <span class="font-bold text-gray-800 dark:text-gray-200">{{ fromRecord }}</span> - <span class="font-bold text-gray-800 dark:text-gray-200">{{ toRecord }}</span> dari <span class="font-bold text-gray-800 dark:text-gray-200">{{ filteredUsers.length }}</span> data
                        <span v-if="filteredUsers.length !== users.length" class="text-gray-400 ml-1">
                            (difilter dari {{ users.length }} total)
                        </span>
                    </div>

                    <!-- Pagination Controls -->
                    <div v-if="totalPages > 1" class="flex items-center gap-1">
                        <!-- Prev Button -->
                        <button
                            @click="setPage(currentPage - 1)"
                            :disabled="currentPage === 1"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-medium border border-gray-200 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1"
                        >
                            <i class="fa-solid fa-chevron-left text-[10px]"></i> Prev
                        </button>

                        <!-- Page Numbers -->
                        <template v-for="p in totalPages" :key="p">
                            <button
                                v-if="p === 1 || p === totalPages || (p >= currentPage - 1 && p <= currentPage + 1)"
                                @click="setPage(p)"
                                :class="[
                                    'min-w-[32px] h-8 rounded-lg text-xs font-semibold transition flex items-center justify-center',
                                    p === currentPage
                                        ? 'bg-[#435ebe] text-white shadow-xs'
                                        : 'bg-white dark:bg-[#151521] border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800'
                                ]"
                            >
                                {{ p }}
                            </button>
                            <span
                                v-else-if="p === currentPage - 2 || p === currentPage + 2"
                                class="px-1 text-gray-400 text-xs"
                            >
                                ...
                            </span>
                        </template>

                        <!-- Next Button -->
                        <button
                            @click="setPage(currentPage + 1)"
                            :disabled="currentPage === totalPages"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-medium border border-gray-200 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition disabled:opacity-40 disabled:cursor-not-allowed flex items-center gap-1"
                        >
                            Next <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add User Modal -->
        <div
            v-if="isAddModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        >
            <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-3">
                    <h3 class="font-bold text-gray-900 dark:text-white text-base flex items-center gap-2">
                        <i class="fa-solid fa-user-plus text-[#435ebe]"></i> Tambah User Whitelist
                    </h3>
                    <button @click="isAddModalOpen = false" class="text-gray-400 hover:text-gray-700 dark:hover:text-white transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form @submit.prevent="submitAddUser" class="space-y-4">
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">
                                Telegram User ID <span class="text-red-500">*</span>
                            </label>
                            <span v-if="isLookingUp" class="text-[11px] text-[#435ebe] dark:text-blue-400 flex items-center gap-1 font-medium">
                                <i class="fa-solid fa-spinner fa-spin text-[10px]"></i> Mencari profil...
                            </span>
                            <span v-else-if="lookupSuccess" class="text-[11px] text-emerald-600 dark:text-emerald-400 flex items-center gap-1 font-semibold">
                                <i class="fa-solid fa-circle-check text-[10px]"></i> Profil Terdeteksi!
                            </span>
                        </div>
                        <div class="relative">
                            <input
                                v-model="addForm.telegram_id"
                                @input="onTelegramIdInput"
                                @blur="fetchTelegramProfile"
                                type="text"
                                required
                                placeholder="Contoh: 7605759955"
                                class="w-full pl-3.5 pr-24 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-sm font-mono focus:border-[#435ebe] focus:ring-1 focus:ring-[#435ebe] outline-none transition"
                            />
                            <button
                                type="button"
                                @click="fetchTelegramProfile"
                                :disabled="!addForm.telegram_id || isLookingUp"
                                class="absolute right-1.5 top-1/2 -translate-y-1/2 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-[#435ebe]/10 text-[#435ebe] hover:bg-[#435ebe] hover:text-white dark:hover:text-white transition disabled:opacity-40 flex items-center gap-1 cursor-pointer"
                                title="Auto-fill Nama & Username dari Telegram"
                            >
                                <i :class="['fa-solid text-[10px]', isLookingUp ? 'fa-spinner fa-spin' : 'fa-wand-magic-sparkles']"></i>
                                <span>Auto-fill</span>
                            </button>
                        </div>
                        <!-- Feedback Message -->
                        <div v-if="lookupSuccess" class="p-2.5 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-xs text-emerald-700 dark:text-emerald-300 mt-2 flex items-center gap-2 font-medium">
                            <i class="fa-solid fa-circle-check text-emerald-500 text-sm shrink-0"></i>
                            <span>{{ lookupMessage }}</span>
                        </div>

                        <div v-else-if="lookupMessage && !lookupSuccess" class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/20 text-xs text-amber-800 dark:text-amber-200 mt-2 space-y-1.5">
                            <div class="flex items-center gap-1.5 font-semibold text-amber-700 dark:text-amber-300">
                                <i class="fa-solid fa-circle-info text-amber-500"></i>
                                <span>User Belum Memulai Chat ke Bot</span>
                            </div>
                            <p class="text-[11px] leading-relaxed text-gray-600 dark:text-gray-300">
                                Kebijakan privasi Telegram: bot hanya bisa membaca profil user jika pemilik akun sudah pernah klik <strong>/start</strong> di bot 
                                <a href="https://t.me/jhustyn_hermes_bot" target="_blank" class="text-[#435ebe] dark:text-blue-400 font-semibold underline">@jhustyn_hermes_bot</a>.
                            </p>
                            <div class="text-[11px] text-gray-600 dark:text-gray-300 pt-1 border-t border-amber-500/10 flex items-center gap-1.5">
                                <i class="fa-solid fa-lightbulb text-amber-500"></i>
                                <span><strong>Solusi Cepat:</strong> Langsung ketik <strong>Nama</strong> & <strong>Username</strong> manual di bawah ini lalu klik Simpan. User akan langsung ter-whitelist!</span>
                            </div>
                        </div>

                        <p v-else class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                            Ketik ID Telegram (contoh: 7605759955), nama & username akan otomatis terisi.
                        </p>
                        <p v-if="addForm.errors.telegram_id" class="text-xs text-red-500 mt-1">{{ addForm.errors.telegram_id }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Nama Lengkap <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="addForm.name"
                                type="text"
                                required
                                placeholder="Jesa Ase"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-sm focus:border-[#435ebe] focus:ring-0 outline-none"
                            />
                            <p v-if="addForm.errors.name" class="text-xs text-red-500 mt-1">{{ addForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Username Telegram
                            </label>
                            <input
                                v-model="addForm.username"
                                type="text"
                                placeholder="@username"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-sm font-mono focus:border-[#435ebe] focus:ring-0 outline-none"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Email Akun
                            </label>
                            <input
                                v-model="addForm.email"
                                type="email"
                                placeholder="user@expressa.id"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-sm focus:border-[#435ebe] focus:ring-0 outline-none"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Password Login
                            </label>
                            <input
                                v-model="addForm.password"
                                type="password"
                                placeholder="Minimal 8 karakter"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-sm focus:border-[#435ebe] focus:ring-0 outline-none"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Role Akses <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="addForm.role"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-sm focus:border-[#435ebe] focus:ring-0 outline-none"
                        >
                            <option value="admin">ADMIN (Akses Penuh: Terminal, Git, Config, Coding)</option>
                            <option value="developer">DEVELOPER (Akses Coding, Git, Run Commands)</option>
                            <option value="reviewer">REVIEWER (Review PR, Git Status, Read-only)</option>
                            <option value="guest">GUEST (Chat Umum / FAQ Only)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Granular Permissions
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input
                                    v-model="addForm.can_code"
                                    type="checkbox"
                                    class="w-4 h-4 rounded text-[#435ebe] border-gray-600 focus:ring-0 bg-transparent"
                                />
                                <span class="flex items-center gap-2">
                                    <i class="fa-solid fa-code text-[#435ebe] w-4 text-center"></i>
                                    Izinkan Edit Kode & File (write_file, edit_file)
                                </span>
                            </label>
                            <label class="flex items-center gap-2.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input
                                    v-model="addForm.can_terminal"
                                    type="checkbox"
                                    class="w-4 h-4 rounded text-[#435ebe] border-gray-600 focus:ring-0 bg-transparent"
                                />
                                <span class="flex items-center gap-2">
                                    <i class="fa-solid fa-terminal text-emerald-500 w-4 text-center"></i>
                                    Izinkan Terminal & Shell Execution (run_command)
                                </span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Catatan (Optional)
                        </label>
                        <input
                            v-model="addForm.notes"
                            type="text"
                            placeholder="Contoh: Principal Stakeholder / Lead Dev"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-sm focus:border-[#435ebe] focus:ring-0 outline-none"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-200 dark:border-gray-800">
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
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-[#435ebe] text-white hover:bg-[#3950a2] transition disabled:opacity-50"
                        >
                            Simpan & Sync .env
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit User Modal -->
        <div
            v-if="isEditModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        >
            <div class="w-full max-w-lg rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-3">
                    <h3 class="font-bold text-gray-900 dark:text-white text-base flex items-center gap-2">
                        <i class="fa-solid fa-user-pen text-[#435ebe]"></i> Edit User: {{ activeUser?.telegram_id || activeUser?.global_user_telegram_id }}
                    </h3>
                    <button @click="isEditModalOpen = false" class="text-gray-400 hover:text-gray-700 dark:hover:text-white transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <form @submit.prevent="submitEditUser" class="space-y-4">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Nama Lengkap
                            </label>
                            <input
                                v-model="editForm.name"
                                type="text"
                                required
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-sm focus:border-[#435ebe] focus:ring-0 outline-none"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Username Telegram
                            </label>
                            <input
                                v-model="editForm.username"
                                type="text"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-sm font-mono focus:border-[#435ebe] focus:ring-0 outline-none"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Email Akun
                            </label>
                            <input
                                v-model="editForm.email"
                                type="email"
                                placeholder="user@expressa.id"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-sm focus:border-[#435ebe] focus:ring-0 outline-none"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Ubah Password (Opsional)
                            </label>
                            <input
                                v-model="editForm.password"
                                type="password"
                                placeholder="Kosongkan jika tidak diubah"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-sm focus:border-[#435ebe] focus:ring-0 outline-none"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Role Akses
                        </label>
                        <select
                            v-model="editForm.role"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-sm focus:border-[#435ebe] focus:ring-0 outline-none"
                        >
                            <option value="admin">ADMIN (Akses Penuh: Terminal, Git, Config, Coding)</option>
                            <option value="developer">DEVELOPER (Akses Coding, Git, Run Commands)</option>
                            <option value="reviewer">REVIEWER (Review PR, Git Status, Read-only)</option>
                            <option value="guest">GUEST (Chat Umum / FAQ Only)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                            Granular Permissions
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input
                                    v-model="editForm.can_code"
                                    type="checkbox"
                                    class="w-4 h-4 rounded text-[#435ebe] border-gray-600 focus:ring-0 bg-transparent"
                                />
                                <span class="flex items-center gap-2">
                                    <i class="fa-solid fa-code text-[#435ebe] w-4 text-center"></i>
                                    Izinkan Edit Kode & File (write_file, edit_file)
                                </span>
                            </label>
                            <label class="flex items-center gap-2.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input
                                    v-model="editForm.can_terminal"
                                    type="checkbox"
                                    class="w-4 h-4 rounded text-[#435ebe] border-gray-600 focus:ring-0 bg-transparent"
                                />
                                <span class="flex items-center gap-2">
                                    <i class="fa-solid fa-terminal text-emerald-500 w-4 text-center"></i>
                                    Izinkan Terminal & Shell Execution (run_command)
                                </span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Catatan
                        </label>
                        <input
                            v-model="editForm.notes"
                            type="text"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-sm focus:border-[#435ebe] focus:ring-0 outline-none"
                        />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-200 dark:border-gray-800">
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
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-[#435ebe] text-white hover:bg-[#3950a2] transition disabled:opacity-50"
                        >
                            Perbarui User
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Reset Password Modal -->
        <div
            v-if="isResetPasswordModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        >
            <div class="w-full max-w-md rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-3">
                    <h3 class="font-bold text-gray-900 dark:text-white text-base flex items-center gap-2">
                        <i class="fa-solid fa-key text-amber-500"></i> Reset Password
                    </h3>
                    <button @click="isResetPasswordModalOpen = false" class="text-gray-400 hover:text-gray-700 dark:hover:text-white transition">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </div>

                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Masukkan password baru untuk user:
                    <div class="font-bold text-gray-900 dark:text-white mt-1">
                        {{ targetUserForPassword?.name || targetUserForPassword?.global_user_nama }} ({{ targetUserForPassword?.email || targetUserForPassword?.global_user_email || '-' }})
                    </div>
                </div>

                <form @submit.prevent="submitResetPassword" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Password Baru <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="resetPasswordForm.password"
                            type="password"
                            required
                            placeholder="Minimal 6 karakter"
                            class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-[#151521] text-gray-900 dark:text-white text-sm focus:border-[#435ebe] outline-none"
                        />
                        <p v-if="resetPasswordForm.errors.password" class="text-xs text-red-500 mt-1">
                            {{ resetPasswordForm.errors.password }}
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-200 dark:border-gray-800">
                        <button
                            type="button"
                            @click="isResetPasswordModalOpen = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="resetPasswordForm.processing"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-amber-500 hover:bg-amber-600 text-white transition disabled:opacity-50"
                        >
                            Simpan Password Baru
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
