<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    collapsed: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['toggle']);
const page = usePage();

const menuSections = [
    {
        title: 'Core & Chat',
        items: [
            { name: 'Dashboard', url: '/', icon: 'fa-solid fa-gauge-high' },
            { name: 'Interactive Chat', url: '/chat', icon: 'fa-solid fa-terminal', badge: 'Live' },
            { name: 'Sessions & History', url: '/sessions', icon: 'fa-regular fa-comment-dots' },
            { name: 'Workspace Files', url: '/files', icon: 'fa-regular fa-folder' },
        ]
    },
    {
        title: 'Intelligence & Tools',
        items: [
            { name: 'Skills Registry', url: '/skills', icon: 'fa-solid fa-puzzle-piece' },
            { name: 'MCP Servers', url: '/mcp', icon: 'fa-solid fa-plug' },
            { name: 'Cron Automations', url: '/cron-jobs', icon: 'fa-regular fa-clock' },
            { name: 'Agent Memory', url: '/memory', icon: 'fa-solid fa-brain' },
            { name: 'Soul & Persona', url: '/soul', icon: 'fa-solid fa-scroll' },
            { name: 'Agent Jhustyn', url: '/agent-jhustyn', icon: 'fa-solid fa-user-astronaut', badge: 'ERP' },
        ]
    },
    {
        title: 'Channels & Integrations',
        items: [
            { name: 'Channels & Platforms', url: '/channels', icon: 'fa-solid fa-tower-broadcast', badge: '31' },
            { name: 'Telegram Bot & RBAC', url: '/telegram', icon: 'fa-brands fa-telegram' },
            { name: 'Webhooks', url: '/webhooks', icon: 'fa-solid fa-code-branch' },
            { name: 'Device Pairing', url: '/pairing', icon: 'fa-regular fa-circle-check' },
        ]
    },
    {
        title: 'Config & Models',
        items: [
            { name: 'Expressa Config', url: '/config', icon: 'fa-solid fa-sliders' },
            { name: 'AI Models & Routing', url: '/models', icon: 'fa-solid fa-microchip' },
            { name: 'Profiles & Agents', url: '/profiles', icon: 'fa-solid fa-user-astronaut' },
            { name: 'API Keys & Env', url: '/environment', icon: 'fa-solid fa-key' },
        ]
    },
    {
        title: 'System & Operations',
        items: [
            { name: 'Live Gateway Console', url: '/monitoring', icon: 'fa-solid fa-chart-line' },
            { name: 'System Diagnostics', url: '/system', icon: 'fa-solid fa-wrench' },
            { name: 'Execution Logs', url: '/logs', icon: 'fa-regular fa-file-lines' },
            { name: 'User Management', url: '/users', icon: 'fa-solid fa-users-gear' },
            { name: 'Menu & RBAC Manager', url: '/menus', icon: 'fa-solid fa-list-check', badge: 'RBAC' },
            { name: 'Documentation', url: '/documentation', icon: 'fa-solid fa-book-open' },
        ]
    }
];

const dynamicSections = computed(() => {
    if (page.props.menuSections && page.props.menuSections.length > 0) {
        return page.props.menuSections;
    }
    return menuSections;
});

const isActive = (url) => {
    if (url === '/') return page.url === '/';
    return page.url.startsWith(url);
};
</script>

<template>
    <aside
        :class="[
            'h-screen flex flex-col bg-white dark:bg-[#111726] border-r border-gray-200 dark:border-[#1a2337] transition-all duration-300 z-20 shrink-0 select-none shadow-xs',
            collapsed ? 'w-20' : 'w-64'
        ]"
    >
        <!-- Brand Header -->
        <div class="h-16 flex items-center justify-between px-4 border-b border-gray-200 dark:border-[#1a2337] shrink-0">
            <Link href="/" class="flex items-center gap-3 overflow-hidden">
                <div class="w-9 h-9 rounded-xl bg-[#435ebe] flex items-center justify-center text-white shrink-0 shadow-sm">
                    <i class="fa-solid fa-robot text-base"></i>
                </div>
                <div v-if="!collapsed" class="truncate">
                    <h1 class="font-bold text-gray-900 dark:text-white text-sm tracking-tight leading-tight">EXPRESSA ASSIST</h1>
                    <span class="text-[10px] font-bold text-[#435ebe] dark:text-blue-400 uppercase tracking-wider block">Control Center</span>
                </div>
            </Link>

            <button
                @click="emit('toggle')"
                class="hidden md:flex p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-800/80 transition"
                :title="collapsed ? 'Expand Sidebar' : 'Collapse Sidebar'"
            >
                <i :class="['fa-solid text-xs', collapsed ? 'fa-angles-right' : 'fa-angles-left']"></i>
            </button>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 py-3 px-3 space-y-4 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-200 dark:scrollbar-thumb-gray-800">
            <div v-for="section in dynamicSections" :key="section.title || section.key" class="space-y-1">
                <!-- Section Header -->
                <div
                    v-if="!collapsed"
                    class="px-2.5 pb-1 pt-2 text-[10px] font-bold uppercase tracking-wider text-gray-400 dark:text-slate-500"
                >
                    {{ section.title }}
                </div>

                <!-- Menu Items -->
                <Link
                    v-for="item in section.items"
                    :key="item.url"
                    :href="item.url"
                    :class="[
                        'flex items-center gap-3 px-3.5 py-2.5 rounded-2xl text-xs font-medium transition-all group relative',
                        isActive(item.url)
                            ? 'bg-[#435ebe] text-white font-semibold shadow-sm'
                            : 'text-gray-600 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-white'
                    ]"
                    :title="collapsed ? item.name : undefined"
                >
                    <i
                        :class="[
                            item.icon,
                            'text-sm w-5 text-center shrink-0 transition-colors',
                            isActive(item.url)
                                ? 'text-white'
                                : 'text-gray-400 dark:text-slate-400 group-hover:text-[#435ebe] dark:group-hover:text-blue-400'
                        ]"
                    ></i>

                    <span v-if="!collapsed" class="truncate flex-1 text-xs">
                        {{ item.name }}
                    </span>

                    <!-- Badge if any -->
                    <span
                        v-if="!collapsed && item.badge"
                        :class="[
                            'text-[10px] font-bold px-2 py-0.5 rounded-full uppercase shrink-0',
                            isActive(item.url)
                                ? 'bg-white/20 text-white'
                                : (item.badge.toUpperCase() === 'LIVE'
                                    ? 'bg-blue-950/70 text-sky-400 border border-blue-600/40'
                                    : (item.badge === '31'
                                        ? 'bg-[#18233c] text-sky-400 font-semibold'
                                        : 'bg-purple-900/40 text-purple-300 border border-purple-700/40'))
                        ]"
                    >
                        {{ item.badge }}
                    </span>

                    <!-- Active Indicator Bar on Collapsed -->
                    <span
                        v-if="collapsed && isActive(item.url)"
                        class="absolute right-1 w-1.5 h-6 bg-white rounded-full"
                    ></span>
                </Link>
            </div>
        </nav>

        <!-- Sidebar Footer Status -->
        <div class="p-3.5 border-t border-gray-200 dark:border-[#1a2337] bg-gray-50/50 dark:bg-black/20 shrink-0">
            <div v-if="!collapsed" class="space-y-1.5">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-gray-500 dark:text-slate-400 font-medium">Gateway Daemon</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> ONLINE
                    </span>
                </div>
                <div class="flex items-center justify-between text-[11px] text-gray-400 dark:text-slate-400">
                    <span class="font-mono">Port 8000 & 9119</span>
                    <a href="http://127.0.0.1:9119" target="_blank" class="hover:text-white underline text-[10px]">Native UI</a>
                </div>
            </div>
            <div v-else class="flex justify-center">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500" title="Gateway Online"></span>
            </div>
        </div>
    </aside>
</template>
