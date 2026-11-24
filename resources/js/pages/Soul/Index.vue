<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';

const props = defineProps({
    soul: {
        type: Object,
        required: true,
    },
});

const isEditing = ref(false);

const form = useForm({
    content: props.soul.content || '',
});

const saveSoul = () => {
    form.post('/soul', {
        onSuccess: () => {
            isEditing.value = false;
        },
    });
};
</script>

<template>
    <AppLayout title="SOUL & Agent Persona">
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white tracking-tight">
                        SOUL & Agent Persona
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Definisi karakter utama, gaya bahasa, etika, dan batasan perilaku otonom Expressa Assist di <code class="font-mono">SOUL.md</code>.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button
                        v-if="!isEditing"
                        @click="isEditing = true"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-[#435ebe] text-white hover:bg-[#3950a2] transition flex items-center gap-2 shadow-xs"
                    >
                        <i class="fa-solid fa-pen-to-square"></i> Edit SOUL Prompt
                    </button>
                    <button
                        v-else
                        @click="isEditing = false"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 transition"
                    >
                        Batal
                    </button>
                </div>
            </div>

            <!-- Editor / Viewer Card -->
            <div class="rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 overflow-hidden shadow-xs">
                <div class="p-4 border-b border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-black/10 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-scroll text-[#435ebe]"></i>
                        <span class="font-bold text-xs text-gray-900 dark:text-white font-mono">E:\hermes\SOUL.md</span>
                    </div>
                    <span class="text-[11px] text-gray-400 font-mono">
                        Terakhir diubah: {{ soul.updated_at || 'Baru' }}
                    </span>
                </div>

                <!-- Form Mode -->
                <div v-if="isEditing" class="p-5 space-y-4">
                    <textarea
                        v-model="form.content"
                        rows="18"
                        class="w-full p-4 rounded-xl font-mono text-xs bg-gray-50 dark:bg-[#151521] border border-gray-300 dark:border-gray-700 text-gray-900 dark:text-white leading-relaxed focus:border-[#435ebe] outline-none transition"
                        placeholder="Masukkan instruksi persona sistem SOUL..."
                    ></textarea>

                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="isEditing = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 transition"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="saveSoul"
                            :disabled="form.processing"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-[#435ebe] text-white hover:bg-[#3950a2] transition shadow-xs flex items-center gap-2"
                        >
                            <i class="fa-solid fa-floppy-disk"></i> Simpan SOUL.md
                        </button>
                    </div>
                </div>

                <!-- View Mode -->
                <div v-else class="p-6">
                    <div class="p-5 rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200/80 dark:border-gray-800 font-mono text-xs text-gray-800 dark:text-gray-200 whitespace-pre-wrap leading-relaxed">
                        {{ soul.content || 'File SOUL.md kosong atau belum dibuat.' }}
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
