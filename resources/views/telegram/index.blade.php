@extends('layouts.app')

@section('page_title', 'Telegram Bot & RBAC Whitelist Hub')

@section('content')
<div class="space-y-8" x-data="telegramHub()">

    <!-- Telegram Bot Credentials & Whitelist Summary -->
    <div class="bg-dark-900 border border-slate-800 rounded-2xl p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5 pb-4 border-b border-slate-800">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-brand-500/10 border border-brand-500/20 text-brand-400 flex items-center justify-center">
                    <i data-lucide="bot" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="text-base font-bold text-slate-100">Telegram Bot Credentials (@jhustyn_hermes_bot)</h3>
                    <p class="text-xs text-slate-400">Daemon Hermes menerima pesan langsung melalui Telegram Long-Polling</p>
                </div>
            </div>
            
            <div class="flex items-center gap-3">
                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Pairing Approved</span>
                </span>

                <form action="{{ route('telegram.sync-env') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-3 py-1 rounded-lg text-xs font-semibold bg-dark-800 hover:bg-dark-700 text-brand-400 border border-slate-700 transition flex items-center gap-1.5">
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                        <span>Sync .env Whitelist</span>
                    </button>
                </form>
            </div>
        </div>

        <form action="{{ route('telegram.auth.update') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                    Telegram Bot Token
                </label>
                <div class="flex gap-3">
                    <input type="password" name="telegram_bot_token" value="{{ old('telegram_bot_token', $env['TELEGRAM_BOT_TOKEN'] ?? '') }}"
                           class="flex-1 bg-dark-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm font-mono text-slate-200 focus:outline-none focus:border-brand-500 transition" required>
                    <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-semibold bg-brand-600 text-white hover:bg-brand-500 transition shrink-0">
                        Update Token
                    </button>
                </div>
            </div>

            <!-- Active in .env preview -->
            <div class="p-3 rounded-xl bg-dark-950/50 border border-slate-800 flex items-center justify-between text-xs">
                <span class="text-slate-400">Aktif di <code class="font-mono text-slate-300">E:\hermes\.env</code> (TELEGRAM_ALLOWED_USERS):</span>
                <span class="font-mono font-bold text-brand-400">{{ $env['TELEGRAM_ALLOWED_USERS'] ?? 'Belum disinkronkan' }}</span>
            </div>
        </form>
    </div>

    <!-- Whitelist & RBAC User Management Section -->
    <div class="bg-dark-900 border border-slate-800 rounded-2xl p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <div class="flex items-center gap-2.5">
                    <i data-lucide="shield-check" class="w-5 h-5 text-indigo-400"></i>
                    <h3 class="text-base font-bold text-slate-100">Telegram Whitelist & RBAC Management</h3>
                </div>
                <p class="text-xs text-slate-400 mt-1">
                    Kelola banyak user Telegram yang memiliki izin mengakses bot, mengeksekusi prompt coding, atau shell terminal.
                </p>
            </div>
            
            <button @click="openAddUserModal()" class="px-4 py-2 rounded-xl text-xs font-semibold bg-brand-600 text-white hover:bg-brand-500 transition shadow-lg shadow-brand-600/20 flex items-center gap-2 self-start sm:self-auto">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                <span>Tambah User Whitelist</span>
            </button>
        </div>

        <!-- Users Table -->
        <div class="overflow-x-auto rounded-xl border border-slate-800">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-dark-950 text-slate-400 font-semibold uppercase tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">User & Handle</th>
                        <th class="px-4 py-3">Telegram User ID</th>
                        <th class="px-4 py-3">Role RBAC</th>
                        <th class="px-4 py-3">Hak Akses (Permissions)</th>
                        <th class="px-4 py-3">Status Whitelist</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    @forelse($users as $user)
                        <tr class="hover:bg-dark-850/50 transition">
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 font-bold flex items-center justify-center font-mono text-xs">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-100 text-xs">{{ $user->name }}</div>
                                        <div class="text-[11px] text-slate-400 font-mono">{{ $user->username ?: 'No username' }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-4 py-3.5 font-mono font-bold text-slate-200">
                                {{ $user->telegram_id }}
                            </td>

                            <td class="px-4 py-3.5">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold font-mono uppercase {{ $user->role_badge_class }}">
                                    {{ $user->role }}
                                </span>
                            </td>

                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @if($user->can_code)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-emerald-500/10 text-emerald-300 border border-emerald-500/20" title="Bisa edit file & git commit">
                                            🛠️ Coding
                                        </span>
                                    @endif
                                    @if($user->can_terminal)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-blue-500/10 text-blue-300 border border-blue-500/20" title="Bisa eksekusi shell terminal">
                                            💻 Terminal
                                        </span>
                                    @endif
                                    @if(!$user->can_code && !$user->can_terminal)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-500/10 text-slate-400 border border-slate-500/20">
                                            👁️ Read-only
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-4 py-3.5">
                                <form action="{{ route('telegram.users.toggle', $user->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold transition cursor-pointer {{ $user->is_active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20' : 'bg-slate-700/40 text-slate-400 border border-slate-700 hover:bg-slate-700/60' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $user->is_active ? 'bg-emerald-400' : 'bg-slate-500' }}"></span>
                                        <span>{{ $user->is_active ? 'Active (Whitelisted)' : 'Suspended' }}</span>
                                    </button>
                                </form>
                            </td>

                            <td class="px-4 py-3.5 text-right space-x-2">
                                <button type="button" @click="openEditUserModal({{ json_encode($user) }})" class="p-1.5 rounded-lg text-slate-400 hover:bg-dark-800 hover:text-white transition">
                                    <i data-lucide="edit-3" class="w-4 h-4"></i>
                                </button>
                                <form action="{{ route('telegram.users.destroy', $user->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Hapus user {{ $user->name }} dari whitelist?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-rose-400 hover:bg-rose-500/10 transition">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-slate-500">Belum ada user yang didaftarkan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Telegram Commands & Custom Skills Registry -->
    <div class="bg-dark-900 border border-slate-800 rounded-2xl p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h3 class="text-base font-bold text-slate-100">Daftar Command & Skill Telegram</h3>
                <p class="text-xs text-slate-400 mt-1">Perintah yang dapat dipanggil langsung dari chat Telegram (@jhustyn_hermes_bot)</p>
            </div>
            <button @click="showAddCommandModal = true" class="px-4 py-2 rounded-xl text-xs font-semibold bg-dark-800 text-slate-200 hover:bg-dark-700 border border-slate-700 transition flex items-center gap-2 self-start sm:self-auto">
                <i data-lucide="plus" class="w-4 h-4"></i>
                <span>Tambah Command Baru</span>
            </button>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-800">
            <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-dark-950 text-slate-400 font-semibold uppercase tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Command</th>
                        <th class="px-4 py-3">Nama Skill</th>
                        <th class="px-4 py-3">Target Profile</th>
                        <th class="px-4 py-3">Deskripsi</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    @forelse($commands as $cmd)
                        <tr class="hover:bg-dark-850/50 transition">
                            <td class="px-4 py-3.5 font-mono font-bold text-brand-400">
                                {{ $cmd->command }}
                            </td>
                            <td class="px-4 py-3.5 font-semibold text-slate-100">
                                {{ $cmd->name }}
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="px-2.5 py-1 rounded-md text-[11px] font-mono bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                    {{ $cmd->target_profile }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-slate-400 max-w-xs truncate">
                                {{ $cmd->description }}
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <form action="{{ route('telegram.commands.destroy', $cmd->id) }}" method="POST" onsubmit="return confirm('Hapus command {{ $cmd->command }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-rose-400 hover:bg-rose-500/10 transition">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-slate-500">Belum ada command yang didaftarkan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal: Tambah User RBAC Baru -->
    <div x-show="showAddUserModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div class="bg-dark-900 border border-slate-800 rounded-2xl w-full max-w-lg p-6 shadow-2xl space-y-5" @click.outside="showAddUserModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h4 class="font-bold text-slate-100 text-sm">Tambah User Whitelist Telegram</h4>
                <button @click="showAddUserModal = false" class="text-slate-400 hover:text-slate-200">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form action="{{ route('telegram.users.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Telegram User ID (Angka Unik)</label>
                    <input type="text" name="telegram_id" placeholder="Misal: 7605759955" required
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3.5 py-2 text-sm font-mono text-slate-200 focus:outline-none focus:border-brand-500">
                    <p class="text-[11px] text-slate-500 mt-1">Dapatkan ID dengan mengetik /start ke bot @userinfobot di Telegram.</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Lengkap</label>
                        <input type="text" name="name" placeholder="Misal: Budi Santoso" required
                               class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Username (@handle)</label>
                        <input type="text" name="username" placeholder="@budi_dev"
                               class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3.5 py-2 text-sm font-mono text-slate-200 focus:outline-none focus:border-brand-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Role RBAC</label>
                    <select name="role" class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-brand-500">
                        <option value="developer">Developer (Ngoding ERP JPP, Run Task, Git Commit)</option>
                        <option value="admin">Super Admin (Akses Penuh: Shell, Git Push, Config)</option>
                        <option value="reviewer">Reviewer / QA (Cek Status, Run Test, Read-only)</option>
                        <option value="guest">Guest (Status Saja)</option>
                    </select>
                </div>

                <div class="space-y-2 pt-2 border-t border-slate-800">
                    <span class="block text-xs font-semibold text-slate-300">Hak Akses Khusus:</span>
                    <label class="flex items-center gap-2.5 text-xs text-slate-300 cursor-pointer">
                        <input type="checkbox" name="can_code" value="1" checked class="rounded bg-dark-950 border-slate-700 text-brand-500 focus:ring-0">
                        <span>Izinkan Edit File Proyek & Git Commit (can_code)</span>
                    </label>
                    <label class="flex items-center gap-2.5 text-xs text-slate-300 cursor-pointer">
                        <input type="checkbox" name="can_terminal" value="1" class="rounded bg-dark-950 border-slate-700 text-brand-500 focus:ring-0">
                        <span>Izinkan Eksekusi Shell Terminal Lokal (can_terminal)</span>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Catatan / Posisi</label>
                    <input type="text" name="notes" placeholder="Contoh: Frontend Lead Engineer"
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-brand-500">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3">
                    <button type="button" @click="showAddUserModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold bg-dark-800 text-slate-300 hover:bg-dark-700">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-brand-600 text-white hover:bg-brand-500 shadow-lg shadow-brand-600/20">
                        Simpan & Sinkronkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Edit User RBAC -->
    <div x-show="showEditUserModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div class="bg-dark-900 border border-slate-800 rounded-2xl w-full max-w-lg p-6 shadow-2xl space-y-5" @click.outside="showEditUserModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h4 class="font-bold text-slate-100 text-sm">Edit User Whitelist Telegram</h4>
                <button @click="showEditUserModal = false" class="text-slate-400 hover:text-slate-200">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form :action="'/telegram/users/' + (editUser ? editUser.id : '')" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Telegram User ID</label>
                    <input type="text" name="telegram_id" :value="editUser ? editUser.telegram_id : ''" required
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3.5 py-2 text-sm font-mono text-slate-200 focus:outline-none focus:border-brand-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Lengkap</label>
                        <input type="text" name="name" :value="editUser ? editUser.name : ''" required
                               class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Username (@handle)</label>
                        <input type="text" name="username" :value="editUser ? editUser.username : ''"
                               class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3.5 py-2 text-sm font-mono text-slate-200 focus:outline-none focus:border-brand-500">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Role RBAC</label>
                    <select name="role" x-model="editUser.role" class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-brand-500">
                        <option value="developer">Developer (Ngoding ERP JPP, Run Task, Git Commit)</option>
                        <option value="admin">Super Admin (Akses Penuh: Shell, Git Push, Config)</option>
                        <option value="reviewer">Reviewer / QA (Cek Status, Run Test, Read-only)</option>
                        <option value="guest">Guest (Status Saja)</option>
                    </select>
                </div>

                <div class="space-y-2 pt-2 border-t border-slate-800">
                    <span class="block text-xs font-semibold text-slate-300">Hak Akses:</span>
                    <label class="flex items-center gap-2.5 text-xs text-slate-300 cursor-pointer">
                        <input type="checkbox" name="can_code" value="1" :checked="editUser && editUser.can_code" class="rounded bg-dark-950 border-slate-700 text-brand-500 focus:ring-0">
                        <span>Izinkan Edit File Proyek & Git Commit</span>
                    </label>
                    <label class="flex items-center gap-2.5 text-xs text-slate-300 cursor-pointer">
                        <input type="checkbox" name="can_terminal" value="1" :checked="editUser && editUser.can_terminal" class="rounded bg-dark-950 border-slate-700 text-brand-500 focus:ring-0">
                        <span>Izinkan Eksekusi Shell Terminal</span>
                    </label>
                    <label class="flex items-center gap-2.5 text-xs text-slate-300 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" :checked="editUser && editUser.is_active" class="rounded bg-dark-950 border-slate-700 text-brand-500 focus:ring-0">
                        <span>Status Whitelist Aktif</span>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Catatan / Posisi</label>
                    <input type="text" name="notes" :value="editUser ? editUser.notes : ''"
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-brand-500">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3">
                    <button type="button" @click="showEditUserModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold bg-dark-800 text-slate-300 hover:bg-dark-700">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-brand-600 text-white hover:bg-brand-500 shadow-lg shadow-brand-600/20">
                        Update User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Tambah Command -->
    <div x-show="showAddCommandModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm">
        <div class="bg-dark-900 border border-slate-800 rounded-2xl w-full max-w-lg p-6 shadow-2xl space-y-5" @click.outside="showAddCommandModal = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <h4 class="font-bold text-slate-100 text-sm">Tambah Command Telegram Baru</h4>
                <button @click="showAddCommandModal = false" class="text-slate-400 hover:text-slate-200">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>

            <form action="{{ route('telegram.commands.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Prefix Command</label>
                    <input type="text" name="command" placeholder="/mycommand" required
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3.5 py-2 text-sm font-mono text-slate-200 focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Perintah / Skill</label>
                    <input type="text" name="name" placeholder="Misal: Review PR Git" required
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Target Profile</label>
                    <select name="target_profile" class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3.5 py-2 text-sm text-slate-200 focus:outline-none focus:border-brand-500">
                        @foreach($profiles as $p)
                            <option value="{{ $p['name'] }}">{{ $p['name'] }} ({{ $p['cwd'] ?? 'root' }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Deskripsi Singkat</label>
                    <textarea name="description" rows="2" placeholder="Fungsi dari command ini..."
                              class="w-full bg-dark-950 border border-slate-700 rounded-xl px-3.5 py-2 text-xs text-slate-200 focus:outline-none focus:border-brand-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3">
                    <button type="button" @click="showAddCommandModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold bg-dark-800 text-slate-300 hover:bg-dark-700">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-brand-600 text-white hover:bg-brand-500 shadow-lg shadow-brand-600/20">
                        Simpan Command
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function telegramHub() {
        return {
            showAddUserModal: false,
            showEditUserModal: false,
            showAddCommandModal: false,
            editUser: null,

            openAddUserModal() {
                this.showAddUserModal = true;
                this.$nextTick(() => lucide.createIcons());
            },

            openEditUserModal(user) {
                this.editUser = user;
                this.showEditUserModal = true;
                this.$nextTick(() => lucide.createIcons());
            }
        }
    }
</script>
@endpush
