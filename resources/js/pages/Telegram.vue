<script setup>
import { ref } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import AppLayout from '../layouts/AppLayout.vue';

const props = defineProps({
    commands: Array,
    users: Array,
    env: Object,
    profiles: Array,
});

const tokenForm = useForm({
    telegram_bot_token: props.env?.TELEGRAM_BOT_TOKEN || '',
});

const submitToken = () => {
    tokenForm.post('/telegram/auth');
};

const syncingEnv = ref(false);
const syncEnv = () => {
    syncingEnv.value = true;
    router.post('/telegram/sync-env', {}, {
        onFinish: () => syncingEnv.value = false
    });
};

// User Modal state
const showAddUserModal = ref(false);
const showEditUserModal = ref(false);
const userForm = useForm({
    telegram_id: '',
    name: '',
    username: '',
    role: 'developer',
    can_code: true,
    can_terminal: false,
    notes: '',
});

const editUserForm = useForm({
    id: null,
    telegram_id: '',
    name: '',
    username: '',
    role: 'developer',
    can_code: true,
    can_terminal: false,
    is_active: true,
    notes: '',
});

const submitAddUser = () => {
    userForm.post('/telegram/users', {
        onSuccess: () => {
            showAddUserModal.value = false;
            userForm.reset();
        }
    });
};

const openEditUser = (user) => {
    editUserForm.id = user.id;
    editUserForm.telegram_id = user.telegram_id;
    editUserForm.name = user.name;
    editUserForm.username = user.username || '';
    editUserForm.role = user.role;
    editUserForm.can_code = Boolean(user.can_code);
    editUserForm.can_terminal = Boolean(user.can_terminal);
    editUserForm.is_active = Boolean(user.is_active);
    editUserForm.notes = user.notes || '';
    showEditUserModal.value = true;
};

const submitEditUser = () => {
    editUserForm.put(`/telegram/users/${editUserForm.id}`, {
        onSuccess: () => {
            showEditUserModal.value = false;
        }
    });
};

const toggleUser = (userId) => {
    router.post(`/telegram/users/${userId}/toggle`);
};

const deleteUser = (user) => {
    if (confirm(`Hapus user ${user.name} dari whitelist Telegram?`)) {
        router.delete(`/telegram/users/${user.id}`);
    }
};

// Command Modal state
const showAddCommandModal = ref(false);
const commandForm = useForm({
    command: '',
    name: '',
    description: '',
    target_profile: 'jhustyn',
});

const submitAddCommand = () => {
    commandForm.post('/telegram/commands', {
        onSuccess: () => {
            showAddCommandModal.value = false;
            commandForm.reset();
        }
    });
};

const deleteCommand = (cmd) => {
    if (confirm(`Hapus command ${cmd.command}?`)) {
        router.delete(`/telegram/commands/${cmd.id}`);
    }
};

const getRoleBadge = (role) => {
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
    <AppLayout>
        <Head title="Telegram & RBAC Whitelist Hub" />

        <div class="space-y-6">
            
            <!-- Telegram Bot Credentials Card -->
            <div class="bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 mb-5 border-b border-gray-200 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-sky-500/10 text-sky-500 flex items-center justify-center text-lg">
                            <i class="fa-brands fa-telegram"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Telegram Bot Credentials (@jhustyn_hermes_bot)</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Daemon Expressa menerima pesan langsung melalui Telegram Long-Polling</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Connected</span>
                        </span>

                        <button
                            @click="syncEnv"
                            :disabled="syncingEnv"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-[#435ebe] dark:text-blue-400 border border-gray-300 dark:border-gray-700 transition flex items-center gap-1.5"
                        >
                            <i :class="['fa-solid fa-arrows-rotate', syncingEnv ? 'fa-spin' : '']"></i>
                            <span>Sync .env Whitelist</span>
                        </button>
                    </div>
                </div>

                <form @submit.prevent="submitToken" class="space-y-3">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                        Telegram Bot Token
                    </label>
                    <div class="flex gap-3">
                        <input
                            v-model="tokenForm.telegram_bot_token"
                            type="password"
                            class="flex-1 bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2 text-sm font-mono text-gray-900 dark:text-white focus:border-[#435ebe] outline-none"
                            required
                        />
                        <button
                            type="submit"
                            :disabled="tokenForm.processing"
                            class="px-5 py-2 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#364b98] text-white transition shadow-sm"
                        >
                            Update Token
                        </button>
                    </div>
                    <div class="p-3 rounded-xl bg-gray-50 dark:bg-[#161622] border border-gray-200 dark:border-gray-800 flex items-center justify-between text-xs">
                        <span class="text-gray-500 dark:text-gray-400">Aktif di TELEGRAM_ALLOWED_USERS (.env):</span>
                        <span class="font-mono font-bold text-sky-500">{{ env?.TELEGRAM_ALLOWED_USERS || 'Kosong' }}</span>
                    </div>
                </form>
            </div>

            <!-- Whitelist & RBAC User Management Section -->
            <div class="bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-users-gear text-[#435ebe] text-base"></i>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Telegram Whitelist & RBAC Management</h3>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Kelola user Telegram yang diizinkan mengakses bot dan berikan hak akses role sesuai kebutuhan.
                        </p>
                    </div>

                    <button
                        @click="showAddUserModal = true"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#364b98] text-white transition flex items-center gap-2"
                    >
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Tambah User Whitelist</span>
                    </button>
                </div>

                <!-- Users Table -->
                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 dark:bg-[#161622] text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="px-4 py-3.5">User</th>
                                <th class="px-4 py-3.5">Telegram User ID</th>
                                <th class="px-4 py-3.5">Role</th>
                                <th class="px-4 py-3.5">Permissions</th>
                                <th class="px-4 py-3.5">Status Whitelist</th>
                                <th class="px-4 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            <tr v-for="user in users" :key="user.id" class="hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-lg bg-[#435ebe]/10 text-[#435ebe] font-bold flex items-center justify-center font-mono text-xs">
                                            {{ user.name.substring(0, 2).toUpperCase() }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-gray-900 dark:text-white">{{ user.name }}</div>
                                            <div class="text-[11px] text-gray-400 font-mono">{{ user.username || '-' }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3.5 font-mono font-bold text-gray-700 dark:text-gray-300">
                                    {{ user.telegram_id }}
                                </td>

                                <td class="px-4 py-3.5">
                                    <span :class="['px-2.5 py-1 rounded-md text-[11px] font-bold font-mono uppercase', getRoleBadge(user.role)]">
                                        {{ user.role }}
                                    </span>
                                </td>

                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span v-if="user.can_code" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-blue-50 text-[#435ebe] border border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-500/30">
                                            <i class="fa-solid fa-code text-[11px]"></i>
                                            Coding
                                        </span>
                                        <span v-if="user.can_terminal" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-500/30">
                                            <i class="fa-solid fa-terminal text-[11px]"></i>
                                            Shell
                                        </span>
                                        <span v-if="!user.can_code && !user.can_terminal" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-gray-100 text-gray-600 border border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700">
                                            <i class="fa-solid fa-eye text-[11px]"></i>
                                            Read-only
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-3.5">
                                    <button
                                        @click="toggleUser(user.id)"
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold transition cursor-pointer',
                                            user.is_active
                                                ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-500/30 dark:hover:bg-emerald-900/40'
                                                : 'bg-gray-100 text-gray-700 border border-gray-200 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700 dark:hover:bg-gray-700'
                                        ]"
                                    >
                                        <i
                                            :class="[
                                                'fa-solid fa-circle text-[7px]',
                                                user.is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400 dark:text-gray-500'
                                            ]"
                                        ></i>
                                        <span>{{ user.is_active ? 'Active (Whitelisted)' : 'Suspended' }}</span>
                                    </button>
                                </td>

                                <td class="px-4 py-3.5 text-right space-x-2">
                                    <button
                                        @click="openEditUser(user)"
                                        class="p-1.5 rounded-lg text-gray-500 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                    >
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <button
                                        @click="deleteUser(user)"
                                        class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-500/10 transition"
                                    >
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Commands & Custom Skills Registry -->
            <div class="bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-xs">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-terminal text-[#435ebe] text-base"></i>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Daftar Command & Skill Telegram</h3>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Perintah yang dapat dipanggil langsung dari chat Telegram (@jhustyn_hermes_bot)
                        </p>
                    </div>

                    <button
                        @click="showAddCommandModal = true"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-700 transition flex items-center gap-2"
                    >
                        <i class="fa-solid fa-plus"></i>
                        <span>Tambah Command Baru</span>
                    </button>
                </div>

                <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-800">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 dark:bg-[#161622] text-gray-500 dark:text-gray-400 font-semibold uppercase tracking-wider border-b border-gray-200 dark:border-gray-800">
                            <tr>
                                <th class="px-4 py-3.5">Command</th>
                                <th class="px-4 py-3.5">Nama Skill</th>
                                <th class="px-4 py-3.5">Target Profile</th>
                                <th class="px-4 py-3.5">Deskripsi</th>
                                <th class="px-4 py-3.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                            <tr v-for="cmd in commands" :key="cmd.id" class="hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                <td class="px-4 py-3.5 font-mono font-bold text-[#435ebe] dark:text-blue-400">
                                    {{ cmd.command }}
                                </td>
                                <td class="px-4 py-3.5 font-bold text-gray-900 dark:text-white">
                                    {{ cmd.name }}
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-mono bg-[#435ebe]/10 text-[#435ebe] dark:text-blue-300 border border-[#435ebe]/20">
                                        {{ cmd.target_profile }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-gray-500 dark:text-gray-400">
                                    {{ cmd.description }}
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <button
                                        @click="deleteCommand(cmd)"
                                        class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-500/10 transition"
                                    >
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal: Tambah User Whitelist -->
            <div v-if="showAddUserModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                <div class="bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 rounded-2xl w-full max-w-lg p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-800">
                        <h4 class="font-bold text-gray-900 dark:text-white text-sm">Tambah User Whitelist Telegram</h4>
                        <button @click="showAddUserModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitAddUser" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Telegram User ID (Angka)</label>
                            <input
                                v-model="userForm.telegram_id"
                                type="text"
                                placeholder="Contoh: 7605759955"
                                class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3.5 py-2 text-sm font-mono text-gray-900 dark:text-white focus:border-[#435ebe] outline-none"
                                required
                            />
                            <p class="text-[11px] text-gray-400 mt-1">Ketik /start ke @userinfobot di Telegram untuk melihat ID.</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap</label>
                                <input
                                    v-model="userForm.name"
                                    type="text"
                                    placeholder="Nama Lengkap"
                                    class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3.5 py-2 text-sm text-gray-900 dark:text-white focus:border-[#435ebe] outline-none"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Username (@)</label>
                                <input
                                    v-model="userForm.username"
                                    type="text"
                                    placeholder="@handle"
                                    class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3.5 py-2 text-sm font-mono text-gray-900 dark:text-white focus:border-[#435ebe] outline-none"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Role RBAC</label>
                            <select
                                v-model="userForm.role"
                                class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3.5 py-2 text-sm text-gray-900 dark:text-white focus:border-[#435ebe] outline-none"
                            >
                                <option value="developer">Developer (Ngoding ERP JPP, Run Task, Git Commit)</option>
                                <option value="admin">Super Admin (Akses Penuh: Shell, Git Push, Config)</option>
                                <option value="reviewer">Reviewer / QA (Cek Status, Run Test, Read-only)</option>
                                <option value="guest">Guest (Status Saja)</option>
                            </select>
                        </div>

                        <div class="space-y-2 pt-2 border-t border-gray-200 dark:border-gray-800">
                            <span class="block text-xs font-bold text-gray-700 dark:text-gray-300">Hak Akses:</span>
                            <label class="flex items-center gap-2.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input v-model="userForm.can_code" type="checkbox" class="rounded text-[#435ebe] focus:ring-0">
                                <span>Izinkan Edit File Proyek & Git Commit (can_code)</span>
                            </label>
                            <label class="flex items-center gap-2.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input v-model="userForm.can_terminal" type="checkbox" class="rounded text-[#435ebe] focus:ring-0">
                                <span>Izinkan Eksekusi Shell Terminal Lokal (can_terminal)</span>
                            </label>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Catatan / Posisi</label>
                            <input
                                v-model="userForm.notes"
                                type="text"
                                placeholder="Contoh: Frontend Lead"
                                class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3.5 py-2 text-xs text-gray-900 dark:text-white focus:border-[#435ebe] outline-none"
                            />
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3">
                            <button
                                type="button"
                                @click="showAddUserModal = false"
                                class="px-4 py-2 rounded-xl text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="userForm.processing"
                                class="px-5 py-2 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#364b98] text-white"
                            >
                                Simpan & Sinkronkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal: Edit User Whitelist -->
            <div v-if="showEditUserModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                <div class="bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 rounded-2xl w-full max-w-lg p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-800">
                        <h4 class="font-bold text-gray-900 dark:text-white text-sm">Edit User Whitelist</h4>
                        <button @click="showEditUserModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitEditUser" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Telegram User ID</label>
                            <input
                                v-model="editUserForm.telegram_id"
                                type="text"
                                class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3.5 py-2 text-sm font-mono text-gray-900 dark:text-white focus:border-[#435ebe] outline-none"
                                required
                            />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Nama Lengkap</label>
                                <input
                                    v-model="editUserForm.name"
                                    type="text"
                                    class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3.5 py-2 text-sm text-gray-900 dark:text-white focus:border-[#435ebe] outline-none"
                                    required
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Username (@)</label>
                                <input
                                    v-model="editUserForm.username"
                                    type="text"
                                    class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3.5 py-2 text-sm font-mono text-gray-900 dark:text-white focus:border-[#435ebe] outline-none"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Role RBAC</label>
                            <select
                                v-model="editUserForm.role"
                                class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3.5 py-2 text-sm text-gray-900 dark:text-white focus:border-[#435ebe] outline-none"
                            >
                                <option value="developer">Developer (Ngoding ERP JPP, Run Task, Git Commit)</option>
                                <option value="admin">Super Admin (Akses Penuh: Shell, Git Push, Config)</option>
                                <option value="reviewer">Reviewer / QA (Cek Status, Run Test, Read-only)</option>
                                <option value="guest">Guest (Status Saja)</option>
                            </select>
                        </div>

                        <div class="space-y-2 pt-2 border-t border-gray-200 dark:border-gray-800">
                            <span class="block text-xs font-bold text-gray-700 dark:text-gray-300">Hak Akses:</span>
                            <label class="flex items-center gap-2.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input v-model="editUserForm.can_code" type="checkbox" class="rounded text-[#435ebe] focus:ring-0">
                                <span>Izinkan Edit File Proyek & Git Commit</span>
                            </label>
                            <label class="flex items-center gap-2.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input v-model="editUserForm.can_terminal" type="checkbox" class="rounded text-[#435ebe] focus:ring-0">
                                <span>Izinkan Eksekusi Shell Terminal</span>
                            </label>
                            <label class="flex items-center gap-2.5 text-xs text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input v-model="editUserForm.is_active" type="checkbox" class="rounded text-[#435ebe] focus:ring-0">
                                <span>Status Whitelist Aktif</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3">
                            <button
                                type="button"
                                @click="showEditUserModal = false"
                                class="px-4 py-2 rounded-xl text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="editUserForm.processing"
                                class="px-5 py-2 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#364b98] text-white"
                            >
                                Update User
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Modal: Tambah Command -->
            <div v-if="showAddCommandModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
                <div class="bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 rounded-2xl w-full max-w-lg p-6 shadow-2xl space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-gray-200 dark:border-gray-800">
                        <h4 class="font-bold text-gray-900 dark:text-white text-sm">Tambah Command Telegram</h4>
                        <button @click="showAddCommandModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <form @submit.prevent="submitAddCommand" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Prefix Command (diawali slash)</label>
                            <input
                                v-model="commandForm.command"
                                type="text"
                                placeholder="/mycommand"
                                class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3.5 py-2 text-sm font-mono text-gray-900 dark:text-white focus:border-[#435ebe] outline-none"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Nama Perintah / Skill</label>
                            <input
                                v-model="commandForm.name"
                                type="text"
                                placeholder="Misal: Review PR Git"
                                class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3.5 py-2 text-sm text-gray-900 dark:text-white focus:border-[#435ebe] outline-none"
                                required
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Target Profile</label>
                            <select
                                v-model="commandForm.target_profile"
                                class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3.5 py-2 text-sm text-gray-900 dark:text-white focus:border-[#435ebe] outline-none"
                            >
                                <option v-for="p in profiles" :key="p.name" :value="p.name">
                                    {{ p.name }} ({{ p.cwd || 'root' }})
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Deskripsi Singkat</label>
                            <textarea
                                v-model="commandForm.description"
                                rows="2"
                                placeholder="Fungsi dari command ini..."
                                class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-3.5 py-2 text-xs text-gray-900 dark:text-white focus:border-[#435ebe] outline-none"
                            ></textarea>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-3">
                            <button
                                type="button"
                                @click="showAddCommandModal = false"
                                class="px-4 py-2 rounded-xl text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="commandForm.processing"
                                class="px-5 py-2 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#364b98] text-white"
                            >
                                Simpan Command
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
