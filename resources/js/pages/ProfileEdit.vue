<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../layouts/AppLayout.vue';

const props = defineProps({
    profile: Object,
});

const form = useForm({
    model_default: props.profile?.config?.model?.default || '',
    terminal_cwd: props.profile?.config?.terminal?.cwd || '',
    soul: props.profile?.soul || '',
});

const submit = () => {
    form.put(`/profiles/${props.profile.name}`);
};
</script>

<template>
    <AppLayout>
        <Head :title="`Edit Profile: ${profile.name}`" />

        <div class="max-w-4xl space-y-6">
            <div class="bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-xs">
                
                <div class="flex items-center justify-between pb-4 mb-6 border-b border-gray-200 dark:border-gray-800">
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white capitalize">Konfigurasi Profil: {{ profile.name }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Edit SOUL, kepribadian, instruksi arsitektur, dan path direktori lokal</p>
                    </div>
                    <Link href="/profiles" class="text-xs text-[#435ebe] dark:text-blue-400 hover:underline font-semibold">
                        &larr; Kembali ke Daftar
                    </Link>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                                Override Model (Opsional)
                            </label>
                            <input
                                v-model="form.model_default"
                                type="text"
                                placeholder="Kosongkan jika mengikuti default Expressa Assist"
                                class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm font-mono text-gray-900 dark:text-white focus:border-[#435ebe] outline-none"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                                Workspace Root Directory
                            </label>
                            <input
                                v-model="form.terminal_cwd"
                                type="text"
                                placeholder="E:\App\SWA\JPP-ERP"
                                class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl px-4 py-2.5 text-sm font-mono text-gray-900 dark:text-white focus:border-[#435ebe] outline-none"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                            SOUL & System Prompt Directive (SOUL.md)
                        </label>
                        <textarea
                            v-model="form.soul"
                            rows="16"
                            class="w-full bg-gray-50 dark:bg-[#161622] border border-gray-300 dark:border-gray-700 rounded-xl p-4 text-xs font-mono text-gray-900 dark:text-gray-200 leading-relaxed focus:border-[#435ebe] outline-none"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3">
                        <Link
                            href="/profiles"
                            class="px-5 py-2.5 rounded-xl text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-6 py-2.5 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#364b98] text-white transition flex items-center gap-2"
                        >
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Simpan Perubahan</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </AppLayout>
</template>
