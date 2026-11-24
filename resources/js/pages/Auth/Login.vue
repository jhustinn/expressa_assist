<script setup>
import { ref } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';

const form = useForm({
    login: '',
    password: '',
    remember: true,
});

const showPassword = ref(false);

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Sign In - Expressa Assist" />

    <div class="min-h-screen flex items-center justify-center bg-gray-50 dark:bg-[#151521] p-4 select-none">
        <div class="w-full max-w-md">
            <!-- Brand Logo & Title -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 rounded-2xl bg-[#435ebe] text-white flex items-center justify-center mx-auto mb-4 shadow-lg shadow-[#435ebe]/25">
                    <i class="fa-solid fa-robot text-2xl"></i>
                </div>
                <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                    Expressa Assist
                </h1>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1.5">
                    Masuk untuk mengelola agent, sessions, tools, dan konfigurasi sistem.
                </p>
            </div>

            <!-- Login Card -->
            <div class="bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 rounded-3xl p-7 shadow-xs">
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Error Alert -->
                    <div
                        v-if="form.errors.login"
                        class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-600 dark:text-rose-400 text-xs flex items-center gap-2"
                    >
                        <i class="fa-solid fa-circle-exclamation shrink-0"></i>
                        <span>{{ form.errors.login }}</span>
                    </div>

                    <!-- Email / Username Input -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                            Email atau Username
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input
                                v-model="form.login"
                                type="text"
                                required
                                autofocus
                                placeholder="Masukkan email atau username"
                                class="w-full pl-9 pr-3.5 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:border-[#435ebe]"
                            />
                        </div>
                    </div>

                    <!-- Password Input -->
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                            Password
                        </label>
                        <div class="relative">
                            <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                placeholder="••••••••"
                                class="w-full pl-9 pr-10 py-2.5 rounded-xl text-xs bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:border-[#435ebe]"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 text-xs"
                            >
                                <i :class="['fa-solid', showPassword ? 'fa-eye-slash' : 'fa-eye']"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember -->
                    <div class="flex items-center justify-between text-xs">
                        <label class="flex items-center gap-2 cursor-pointer text-gray-600 dark:text-gray-400">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="w-4 h-4 rounded text-[#435ebe] focus:ring-[#435ebe]"
                            />
                            <span>Ingat saya</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full py-3 px-4 rounded-xl bg-[#435ebe] hover:bg-[#354bb4] text-white text-xs font-bold flex items-center justify-center gap-2 shadow-sm transition cursor-pointer disabled:opacity-50"
                    >
                        <i v-if="form.processing" class="fa-solid fa-circle-notch animate-spin"></i>
                        <span>{{ form.processing ? 'Memverifikasi...' : 'Masuk ke Dashboard' }}</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
