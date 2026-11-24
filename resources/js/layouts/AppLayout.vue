<script setup>
import { ref } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import Sidebar from '../components/Sidebar.vue';
import { useTheme } from '../composables/useTheme';

const page = usePage();
const { isDark, toggle: toggleTheme } = useTheme();
const sidebarCollapsed = ref(localStorage.getItem('sidebar_collapsed') === '1');

const toggleSidebar = () => {
    sidebarCollapsed.value = !sidebarCollapsed.value;
    localStorage.setItem('sidebar_collapsed', sidebarCollapsed.value ? '1' : '0');
};

const restarting = ref(false);

const restartGateway = () => {
    restarting.value = true;
    router.post('/monitoring/action', { action: 'restart' }, {
        onFinish: () => {
            restarting.value = false;
        }
    });
};
</script>

<template>
    <div class="h-screen flex bg-gray-50 dark:bg-gray-950 overflow-hidden text-gray-800 dark:text-gray-100 font-sans">
        <!-- Sidebar -->
        <Sidebar :collapsed="sidebarCollapsed" @toggle="toggleSidebar" />

        <!-- Main Content Column -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- Navbar Header -->
            <header class="h-16 shrink-0 bg-white dark:bg-[#111726] border-b border-gray-200 dark:border-[#1a2337] flex items-center justify-between px-6 z-10 shadow-xs">
                <!-- Left Title / Path -->
                <div class="flex items-center gap-4">
                    <button
                        @click="toggleSidebar"
                        class="p-2 rounded-lg text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition md:hidden"
                    >
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div>
                        <h2 class="text-base font-bold text-gray-900 dark:text-white leading-tight">
                            <slot name="title">Expressa Assist</slot>
                        </h2>
                        <span class="text-[11px] text-gray-400 dark:text-gray-500 hidden sm:block">
                            Autonomous AI Engineer & Telegram Integration
                        </span>
                    </div>
                </div>

                <!-- Right Action Controls -->
                <div class="flex items-center gap-3">
                    <!-- Quick Restart Gateway Button -->
                    <button
                        @click="restartGateway"
                        :disabled="restarting"
                        class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold bg-gray-100 dark:bg-gray-800/80 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 border border-gray-300 dark:border-gray-700 transition"
                        title="Restart Gateway Daemon"
                    >
                        <i :class="['fa-solid fa-rotate-right text-[#435ebe] dark:text-blue-400', restarting ? 'fa-spin' : '']"></i>
                        <span class="hidden sm:inline">Restart Gateway</span>
                    </button>

                    <!-- Active Agent Badge Link -->
                    <Link
                        href="/agent-jhustyn"
                        class="hidden md:flex items-center gap-2 px-3 py-1.5 rounded-lg bg-[#435ebe]/10 hover:bg-[#435ebe]/20 border border-[#435ebe]/20 text-xs font-semibold text-[#435ebe] dark:text-blue-300 transition"
                        title="Buka Manajemen Agent Jhustyn (Jhustyn.md)"
                    >
                        <span class="w-2 h-2 rounded-full bg-[#435ebe] animate-pulse"></span>
                        <span>Jhustyn (ERP JPP)</span>
                    </Link>

                    <!-- Theme Toggle -->
                    <button
                        @click="toggleTheme"
                        class="p-2 text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition"
                        :title="isDark ? 'Light mode' : 'Dark mode'"
                    >
                        <i :class="['fa-solid text-sm', isDark ? 'fa-sun text-amber-400' : 'fa-moon text-indigo-600']"></i>
                    </button>

                    <!-- User Profile & Authentication -->
                    <div class="flex items-center gap-2 pl-2 border-l border-gray-200 dark:border-gray-800">
                        <template v-if="page.props.auth?.user">
                            <div class="w-8 h-8 rounded-xl bg-[#435ebe] text-white font-bold text-xs flex items-center justify-center uppercase shrink-0">
                                {{ (page.props.auth.user.name || 'U').substring(0, 2) }}
                            </div>
                            <div class="hidden lg:block text-left">
                                <div class="text-xs font-bold text-gray-800 dark:text-gray-200 leading-tight truncate max-w-[120px]">
                                    {{ page.props.auth.user.name }}
                                </div>
                                <div class="text-[10px] text-emerald-500 font-semibold uppercase tracking-wider">
                                    {{ page.props.auth.user.role }}
                                </div>
                            </div>
                            <!-- Logout Button -->
                            <button
                                @click="router.post('/logout')"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-rose-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                                title="Logout dari sesi akun"
                            >
                                <i class="fa-solid fa-arrow-right-from-bracket text-xs"></i>
                            </button>
                        </template>
                        <template v-else>
                            <Link
                                href="/login"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold bg-[#435ebe] hover:bg-[#354bb4] text-white flex items-center gap-1.5 transition shadow-xs"
                            >
                                <i class="fa-solid fa-right-to-bracket text-xs"></i>
                                <span>Login</span>
                            </Link>
                        </template>
                    </div>
                </div>
            </header>

            <!-- Flash Alert Messages -->
            <div v-if="page.props.flash?.success" class="mx-6 mt-4 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-700 dark:text-emerald-300 flex items-center justify-between text-xs font-medium">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-check text-emerald-500 text-base"></i>
                    <span>{{ page.props.flash.success }}</span>
                </div>
                <button @click="page.props.flash.success = null" class="text-emerald-500 hover:text-emerald-700">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div v-if="page.props.flash?.error" class="mx-6 mt-4 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-700 dark:text-rose-300 flex items-center justify-between text-xs font-medium">
                <div class="flex items-center gap-2.5">
                    <i class="fa-solid fa-circle-exclamation text-rose-500 text-base"></i>
                    <span>{{ page.props.flash.error }}</span>
                </div>
                <button @click="page.props.flash.error = null" class="text-rose-500 hover:text-rose-700">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Page Body Content -->
            <main class="flex-1 overflow-y-auto p-4 md:p-6 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
