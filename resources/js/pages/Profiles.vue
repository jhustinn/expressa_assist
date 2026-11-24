<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '../layouts/AppLayout.vue';

const props = defineProps({
    profiles: Array,
});

const showCreateModal = ref(false);

const createForm = useForm({
    name: '',
    role_title: '',
    terminal_cwd: 'E:\\App',
    model_default: 'gemini-flash-lite-latest',
    soul: '',
});

const submitCreate = () => {
    createForm.post('/profiles', {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
};

const deleteProfile = (name) => {
    if (confirm(`Apakah Anda yakin ingin menghapus profil agent '${name}'? Seluruh file dan memorinya di E:\\hermes\\profiles\\${name} akan dihapus.`)) {
        router.delete(`/profiles/${name}`, {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Agent Profiles & Personalities - Expressa Assist" />

        <div class="space-y-6">
            <!-- Header with Quick Action -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 rounded-3xl bg-white dark:bg-[#111726] border border-gray-200 dark:border-[#1a2337] shadow-xs">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-[#435ebe]/10 dark:bg-[#435ebe]/20 text-[#435ebe] dark:text-blue-400 flex items-center justify-center font-bold text-xl shrink-0">
                        <i class="fa-solid fa-users-viewfinder"></i>
                    </div>
                    <div>
                        <h2 class="text-lg sm:text-xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                            Expressa Assist Profiles
                        </h2>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                            Daftar profil agen otonom, kepribadian, SOUL.md, dan direktori kerja lokal masing-masing.
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <button
                        @click="showCreateModal = true"
                        class="px-4 py-2.5 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#354bb4] text-white shadow-sm transition flex items-center gap-2 cursor-pointer"
                    >
                        <i class="fa-solid fa-plus"></i>
                        <span>Tambah Profil Baru</span>
                    </button>
                </div>
            </div>

            <!-- Profiles Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div
                    v-for="profile in profiles"
                    :key="profile.name"
                    class="bg-white dark:bg-[#111726] border border-gray-200 dark:border-[#1a2337] rounded-3xl p-6 shadow-xs flex flex-col justify-between hover:border-[#435ebe]/50 transition relative group"
                >
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3.5">
                                <div class="w-12 h-12 rounded-2xl bg-linear-to-tr from-[#435ebe] to-sky-400 text-white font-bold flex items-center justify-center font-mono text-base shadow-sm">
                                    {{ profile.name.substring(0, 2).toUpperCase() }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-extrabold text-gray-900 dark:text-white text-base capitalize">{{ profile.name }}</h4>
                                        <span
                                            v-if="profile.name === 'jhustyn'"
                                            class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#435ebe]/20 text-sky-400 border border-[#435ebe]/30"
                                        >
                                            ERP JPP LEAD
                                        </span>
                                    </div>
                                    <span class="text-xs font-mono text-gray-400 dark:text-slate-400">
                                        Model: <b class="text-gray-700 dark:text-slate-200">{{ profile.model || 'Inherit default' }}</b>
                                    </span>
                                </div>
                            </div>

                            <!-- Delete button (only for custom profiles) -->
                            <button
                                v-if="profile.name !== 'jhustyn' && profile.name !== 'default'"
                                @click="deleteProfile(profile.name)"
                                class="p-2 text-gray-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/30 rounded-xl transition"
                                title="Hapus profil agent"
                            >
                                <i class="fa-solid fa-trash-can text-xs"></i>
                            </button>
                        </div>

                        <!-- Workspace path -->
                        <div class="bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-800 rounded-2xl p-3 text-xs font-mono">
                            <span class="text-gray-400 dark:text-gray-500 block text-[10px] uppercase font-bold tracking-wider mb-0.5">
                                <i class="fa-solid fa-folder-tree mr-1 text-[#435ebe]"></i> Target Workspace
                            </span>
                            <span class="text-gray-800 dark:text-slate-200 truncate block">{{ profile.cwd || 'Inherit global cwd' }}</span>
                        </div>

                        <!-- SOUL Preview -->
                        <div class="text-xs text-gray-600 dark:text-slate-400 italic bg-gray-50/50 dark:bg-[#0c101b] p-3.5 rounded-2xl border border-gray-200/60 dark:border-gray-800/60 line-clamp-3 leading-relaxed">
                            "{{ profile.soul_preview || 'Default Expressa Assist autonomous behavior' }}"
                        </div>
                    </div>

                    <div class="pt-5 mt-5 border-t border-gray-100 dark:border-gray-800/80 flex items-center justify-between gap-2">
                        <Link
                            v-if="profile.name === 'jhustyn'"
                            href="/agent-jhustyn"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold bg-[#435ebe]/10 hover:bg-[#435ebe]/20 text-[#435ebe] dark:text-blue-400 border border-[#435ebe]/30 transition flex items-center gap-1.5"
                        >
                            <i class="fa-solid fa-user-astronaut"></i>
                            <span>Manajemen Jhustyn.md</span>
                        </Link>
                        <span v-else></span>

                        <div class="flex items-center gap-2">
                            <Link
                                :href="`/chat?profile=${profile.name}`"
                                class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-gray-100 hover:bg-gray-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-gray-700 dark:text-slate-200 transition flex items-center gap-1.5"
                            >
                                <i class="fa-solid fa-terminal text-xs"></i>
                                <span>Chat</span>
                            </Link>

                            <Link
                                :href="`/profiles/${profile.name}/edit`"
                                class="px-4 py-2 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#354bb4] text-white transition shadow-sm flex items-center gap-2"
                            >
                                <i class="fa-solid fa-pen-to-square"></i>
                                <span>Edit Profil</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================ -->
        <!-- MODAL: TAMBAH PROFIL AGENT BARU                              -->
        <!-- ============================================================ -->
        <div
            v-if="showCreateModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
        >
            <div class="w-full max-w-lg rounded-3xl bg-white dark:bg-[#111726] border border-gray-200 dark:border-[#1a2337] shadow-2xl flex flex-col overflow-hidden">
                <!-- Modal Header -->
                <div class="p-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-[#435ebe]/10 text-[#435ebe] flex items-center justify-center font-bold text-base">
                            <i class="fa-solid fa-user-plus"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-base text-gray-900 dark:text-white">
                                Buat Profil Agent Baru
                            </h3>
                            <p class="text-xs text-gray-400 mt-0.5">
                                Inisialisasi ruang profil, konfigurasi model, dan SOUL persona baru.
                            </p>
                        </div>
                    </div>

                    <button
                        @click="showCreateModal = false"
                        class="p-2 rounded-xl text-gray-400 hover:text-gray-700 dark:hover:text-white transition"
                    >
                        <i class="fa-solid fa-xmark text-base"></i>
                    </button>
                </div>

                <!-- Modal Form -->
                <form @submit.prevent="submitCreate" class="p-6 space-y-4 text-xs">
                    <!-- Error Alert -->
                    <div
                        v-if="createForm.errors.name"
                        class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs"
                    >
                        {{ createForm.errors.name }}
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Profile Name / Slug -->
                        <div class="space-y-1.5">
                            <label class="block font-bold text-gray-700 dark:text-slate-300">
                                Nama / Slug Profil <span class="text-rose-500">*</span>
                            </label>
                            <input
                                v-model="createForm.name"
                                type="text"
                                required
                                placeholder="misal: billing, devops, marketing"
                                class="w-full px-3.5 py-2.5 rounded-xl font-mono bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:border-[#435ebe]"
                            />
                            <p class="text-[10px] text-gray-400">Hanya huruf kecil, angka, dan tanda strip (-).</p>
                        </div>

                        <!-- Role Title -->
                        <div class="space-y-1.5">
                            <label class="block font-bold text-gray-700 dark:text-slate-300">
                                Jabatan / Role Title
                            </label>
                            <input
                                v-model="createForm.role_title"
                                type="text"
                                placeholder="misal: Invoicing & Billing Specialist"
                                class="w-full px-3.5 py-2.5 rounded-xl bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:border-[#435ebe]"
                            />
                        </div>
                    </div>

                    <!-- Target Workspace Directory -->
                    <div class="space-y-1.5">
                        <label class="block font-bold text-gray-700 dark:text-slate-300">
                            Target Workspace Directory Path
                        </label>
                        <input
                            v-model="createForm.terminal_cwd"
                            type="text"
                            required
                            placeholder="misal: E:\App\SWA\JPP-ERP"
                            class="w-full px-3.5 py-2.5 rounded-xl font-mono bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:border-[#435ebe]"
                        />
                    </div>

                    <!-- Default Model -->
                    <div class="space-y-1.5">
                        <label class="block font-bold text-gray-700 dark:text-slate-300">
                            Model AI Default
                        </label>
                        <select
                            v-model="createForm.model_default"
                            class="w-full px-3.5 py-2.5 rounded-xl font-mono bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white focus:outline-none focus:border-[#435ebe]"
                        >
                            <option value="gemini-flash-lite-latest">gemini-flash-lite-latest (Fast & Cepat)</option>
                            <option value="gemini-2.5-flash">gemini-2.5-flash</option>
                            <option value="gemini-2.5-pro">gemini-2.5-pro (Deep Reasoning & Architecture)</option>
                            <option value="llama-3.3-70b-versatile">llama-3.3-70b-versatile (Groq)</option>
                        </select>
                    </div>

                    <!-- Initial SOUL Prompt -->
                    <div class="space-y-1.5">
                        <label class="block font-bold text-gray-700 dark:text-slate-300">
                            Instruksi Karakter / SOUL.md Awal (Opsional)
                        </label>
                        <textarea
                            v-model="createForm.soul"
                            rows="4"
                            placeholder="Deskripsikan karakter, cara berbicara, dan aturan teknis agen ini..."
                            class="w-full px-3.5 py-2.5 rounded-xl font-mono text-xs bg-gray-50 dark:bg-[#151b2e] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:border-[#435ebe]"
                        ></textarea>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-4 py-2.5 rounded-xl text-xs font-semibold text-gray-500 hover:text-gray-800 dark:text-slate-400 dark:hover:text-white transition"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing || !createForm.name.trim()"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#354bb4] text-white shadow-sm transition flex items-center gap-2 disabled:opacity-50"
                        >
                            <i v-if="createForm.processing" class="fa-solid fa-circle-notch animate-spin"></i>
                            <i v-else class="fa-solid fa-check"></i>
                            <span>{{ createForm.processing ? 'Membuat...' : 'Buat Profil' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
