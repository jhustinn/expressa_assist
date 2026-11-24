<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';

const props = defineProps({
    currentProfile: {
        type: String,
        default: 'default',
    },
    modelInfo: {
        type: Object,
        default: () => ({}),
    },
    sessions: {
        type: Array,
        default: () => [],
    },
    profiles: {
        type: Array,
        default: () => [],
    },
});

const selectedProfile = ref(props.currentProfile);
const switchProfile = (pName) => {
    selectedProfile.value = pName;
    router.get('/chat', { profile: pName }, { preserveState: false, preserveScroll: true });
};

// History Drawer / Sidebar Toggle
const showHistory = ref(true);

// Chat state
const currentSessionId = ref(null);
const currentSessionTitle = ref('Percakapan Baru');
const messages = ref([]);
const inputPrompt = ref('');
const isSending = ref(false);
const chatScrollContainer = ref(null);
const sessionSearch = ref('');
const copiedIdx = ref(null);
const sessionList = ref([...props.sessions]);

const scrollToBottom = async () => {
    await nextTick();
    if (chatScrollContainer.value) {
        chatScrollContainer.value.scrollTop = chatScrollContainer.value.scrollHeight;
    }
};

const startNewChat = () => {
    currentSessionId.value = null;
    currentSessionTitle.value = 'Percakapan Baru';
    messages.value = [
        {
            id: 'welcome',
            role: 'assistant',
            content: 'Halo! Saya Expressa Assist. Ada yang bisa saya bantu hari ini? Anda dapat bertanya seputar coding, konfigurasi, status gateway, atau eksekusi tools otomatis.',
            timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        }
    ];
    scrollToBottom();
};

const loadSession = async (sess) => {
    currentSessionId.value = sess.id || sess.session_key;
    currentSessionTitle.value = sess.title || 'Sesi ' + (sess.id || '').substring(0, 8);
    messages.value = [];
    isSending.value = true;
    try {
        const res = await fetch(`/api/chat/sessions/${currentSessionId.value}`);
        if (res.ok) {
            const data = await res.json();
            messages.value = (data.messages || []).map(m => ({
                id: m.id || Math.random(),
                role: m.role || 'user',
                content: m.content || '',
                timestamp: m.timestamp || ''
            }));
            if (messages.value.length === 0) {
                startNewChat();
            }
        }
    } catch (e) {
        console.error('Failed to load session messages', e);
        startNewChat();
    } finally {
        isSending.value = false;
        scrollToBottom();
    }
};

const sendUserMessage = async () => {
    const text = inputPrompt.value.trim();
    if (!text || isSending.value) return;

    // Push User message
    const userTime = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    messages.value.push({
        id: 'user_' + Date.now(),
        role: 'user',
        content: text,
        timestamp: userTime
    });

    inputPrompt.value = '';
    isSending.value = true;
    scrollToBottom();

    try {
        const res = await fetch('/api/chat/send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                message: text,
                session_id: currentSessionId.value,
                profile: selectedProfile.value,
            })
        });

        if (res.ok) {
            const data = await res.json();
            if (data.session_id) {
                currentSessionId.value = data.session_id;

                // Add or update to sessionList in real time
                const existingIdx = sessionList.value.findIndex(s => s.id === data.session_id || s.session_key === data.session_id);
                if (existingIdx === -1) {
                    sessionList.value.unshift({
                        id: data.session_id,
                        title: text.length > 25 ? text.substring(0, 25) + '...' : text,
                        platform: 'cli',
                        messageCount: messages.value.length,
                        startTime: new Date().toISOString()
                    });
                    currentSessionTitle.value = text.length > 25 ? text.substring(0, 25) + '...' : text;
                }
            }

            messages.value.push({
                id: 'asst_' + Date.now(),
                role: 'assistant',
                content: data.reply || 'Tidak ada respon.',
                timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
            });
        } else {
            messages.value.push({
                id: 'err_' + Date.now(),
                role: 'assistant',
                content: 'Terjadi kesalahan saat memproses prompt di Expressa Assist.',
                timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
            });
        }
    } catch (e) {
        messages.value.push({
            id: 'err_' + Date.now(),
            role: 'assistant',
            content: 'Gagal terhubung ke Expressa Assist: ' + e.message,
            timestamp: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
        });
    } finally {
        isSending.value = false;
        scrollToBottom();
    }
};

const handleKeydown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendUserMessage();
    }
};

const copyMessage = (content, idx) => {
    navigator.clipboard.writeText(content);
    copiedIdx.value = idx;
    setTimeout(() => {
        copiedIdx.value = null;
    }, 2000);
};

const applyPreset = (promptText) => {
    inputPrompt.value = promptText;
    sendUserMessage();
};

onMounted(() => {
    startNewChat();
});
</script>

<template>
    <AppLayout title="Expressa Assist Interactive Chat">
        <div class="h-[calc(100vh-8.5rem)] flex gap-4 overflow-hidden relative">
            <!-- Left Sidebar: Sessions History -->
            <transition
                enter-active-class="transition-all duration-200 ease-out"
                leave-active-class="transition-all duration-150 ease-in"
                enter-from-class="-translate-x-full opacity-0"
                leave-to-class="-translate-x-full opacity-0"
            >
                <div
                    v-if="showHistory"
                    class="flex flex-col w-72 sm:w-80 rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs p-3.5 select-none shrink-0 z-10"
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800 mb-3">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-clock-rotate-left text-[#435ebe]"></i>
                            <span class="text-xs font-bold text-gray-900 dark:text-white">Riwayat Chat</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-500">
                                {{ sessionList.length }}
                            </span>
                        </div>
                        <a
                            href="/sessions"
                            class="text-[11px] text-[#435ebe] dark:text-blue-400 hover:underline inline-flex items-center gap-1"
                            title="Buka halaman Session Manager lengkap"
                        >
                            <span>Semua Sesi</span>
                            <i class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                        </a>
                    </div>

                    <!-- New Chat Button -->
                    <button
                        @click="startNewChat"
                        class="w-full py-2.5 px-3 rounded-xl bg-[#435ebe] hover:bg-[#354bb4] text-white text-xs font-semibold flex items-center justify-center gap-2 shadow-sm transition-colors mb-3 cursor-pointer"
                    >
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>+ Sesi Chat Baru</span>
                    </button>

                    <!-- Search Sessions -->
                    <div class="relative mb-3">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-[10px]"></i>
                        <input
                            v-model="sessionSearch"
                            type="text"
                            placeholder="Cari riwayat percakapan..."
                            class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none"
                        />
                    </div>

                    <!-- Session Items List -->
                    <div class="flex-1 overflow-y-auto space-y-1 pr-1">
                        <div v-if="sessionList.length === 0" class="text-center py-8 text-xs text-gray-400">
                            Belum ada riwayat sesi.
                        </div>

                        <div
                            v-for="s in sessionList.filter(item => !sessionSearch || (item.title || '').toLowerCase().includes(sessionSearch.toLowerCase()))"
                            :key="s.id"
                            @click="loadSession(s)"
                            class="p-2.5 rounded-xl cursor-pointer transition-colors text-xs border"
                            :class="currentSessionId === (s.id || s.session_key)
                                ? 'bg-[#435ebe]/10 dark:bg-[#435ebe]/20 border-[#435ebe]/40 text-[#435ebe] dark:text-blue-300 font-semibold'
                                : 'border-transparent text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5'"
                        >
                            <div class="flex items-center justify-between mb-0.5">
                                <span class="truncate font-medium flex-1 text-xs">
                                    {{ s.title || ('Sesi ' + (s.id || '').substring(0, 8)) }}
                                </span>
                                <span class="text-[10px] text-gray-400 shrink-0 ml-1.5">
                                    {{ s.startTime ? s.startTime.substring(11, 16) : '' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 text-[10px] text-gray-400 font-mono">
                                <span class="uppercase px-1.5 py-0.2 rounded bg-gray-100 dark:bg-gray-800 text-[9px]">{{ s.platform || 'cli' }}</span>
                                <span>•</span>
                                <span>{{ s.messageCount || 0 }} pesan</span>
                            </div>
                        </div>
                    </div>
                </div>
            </transition>

            <!-- Right Main Chat Pane -->
            <div class="flex-1 flex flex-col rounded-2xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 shadow-xs overflow-hidden min-w-0">
                <!-- Top Status Header -->
                <div class="h-14 px-5 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between shrink-0 bg-white dark:bg-[#1e1e2d]">
                    <div class="flex items-center gap-3 min-w-0">
                        <!-- Toggle History Button -->
                        <button
                            @click="showHistory = !showHistory"
                            class="p-2 rounded-xl text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors text-xs flex items-center gap-1.5"
                            :title="showHistory ? 'Sembunyikan panel riwayat' : 'Tampilkan panel riwayat chat'"
                        >
                            <i class="fa-solid fa-clock-rotate-left text-xs" :class="showHistory ? 'text-[#435ebe]' : ''"></i>
                            <span class="text-xs font-semibold hidden sm:inline">Riwayat ({{ sessionList.length }})</span>
                        </button>

                        <div class="h-4 w-px bg-gray-200 dark:border-gray-800"></div>

                        <div class="truncate">
                            <h3 class="text-xs font-bold text-gray-900 dark:text-white leading-tight truncate">
                                {{ currentSessionTitle }}
                            </h3>
                            <div class="flex items-center gap-2 text-[10px] text-gray-400 mt-0.5">
                                <span class="flex items-center gap-1 text-emerald-500 font-semibold">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Expressa Assist Online
                                </span>
                                <span>•</span>
                                <span class="font-mono truncate">{{ modelInfo.model || 'gemini-flash-lite-latest' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2.5 shrink-0">
                        <!-- Profile Selector -->
                        <div class="flex items-center bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-700/80 rounded-xl px-2.5 py-1 text-xs">
                            <span class="text-gray-400 mr-1.5 text-[11px]">Profil:</span>
                            <select
                                :value="selectedProfile"
                                @change="switchProfile($event.target.value)"
                                class="bg-transparent font-semibold text-gray-900 dark:text-white focus:outline-none cursor-pointer text-xs"
                            >
                                <option v-for="p in profiles" :key="p.name" :value="p.name" class="bg-white dark:bg-[#1e1e2d] text-gray-900 dark:text-white">
                                    {{ p.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Clear Screen Button -->
                        <button
                            @click="startNewChat"
                            class="p-2 rounded-xl text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors text-xs"
                            title="Bersihkan layar & mulai sesi baru"
                        >
                            <i class="fa-solid fa-broom"></i>
                        </button>
                    </div>
                </div>

                <!-- Messages Stream Viewport -->
                <div ref="chatScrollContainer" class="flex-1 p-5 overflow-y-auto space-y-4 bg-gray-50/50 dark:bg-[#151521]/40">
                    <!-- Suggested Presets if only 1 welcome message -->
                    <div v-if="messages.length <= 1" class="my-4">
                        <div class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-2.5">
                            Contoh Pertanyaan Cepat:
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <button
                                @click="applyPreset('Tampilkan daftar skill dan tools yang sedang aktif')"
                                class="p-3 text-left rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 hover:border-[#435ebe] text-xs text-gray-700 dark:text-gray-300 transition-all flex items-center justify-between group cursor-pointer"
                            >
                                <span>Tampilkan daftar skill & tools aktif</span>
                                <i class="fa-solid fa-arrow-right text-[10px] text-gray-400 group-hover:text-[#435ebe] transition-colors"></i>
                            </button>
                            <button
                                @click="applyPreset('Bagaimana status koneksi gateway bot Telegram saat ini?')"
                                class="p-3 text-left rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 hover:border-[#435ebe] text-xs text-gray-700 dark:text-gray-300 transition-all flex items-center justify-between group cursor-pointer"
                            >
                                <span>Cek status bot Telegram & allowed users</span>
                                <i class="fa-solid fa-arrow-right text-[10px] text-gray-400 group-hover:text-[#435ebe] transition-colors"></i>
                            </button>
                            <button
                                @click="applyPreset('Periksa apakah ada task cron job yang dijadwalkan hari ini')"
                                class="p-3 text-left rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 hover:border-[#435ebe] text-xs text-gray-700 dark:text-gray-300 transition-all flex items-center justify-between group cursor-pointer"
                            >
                                <span>Cek task cron job terjadwal</span>
                                <i class="fa-solid fa-arrow-right text-[10px] text-gray-400 group-hover:text-[#435ebe] transition-colors"></i>
                            </button>
                            <button
                                @click="applyPreset('Berikan ringkasan identitas dan SOUL agent')"
                                class="p-3 text-left rounded-xl bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 hover:border-[#435ebe] text-xs text-gray-700 dark:text-gray-300 transition-all flex items-center justify-between group cursor-pointer"
                            >
                                <span>Ringkasan persona & SOUL agent</span>
                                <i class="fa-solid fa-arrow-right text-[10px] text-gray-400 group-hover:text-[#435ebe] transition-colors"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Conversation Bubbles -->
                    <div
                        v-for="(msg, idx) in messages"
                        :key="msg.id || idx"
                        class="flex gap-3 text-xs leading-relaxed"
                        :class="msg.role === 'user' ? 'justify-end' : 'justify-start'"
                    >
                        <!-- Assistant Avatar -->
                        <div
                            v-if="msg.role !== 'user'"
                            class="w-8 h-8 rounded-xl bg-[#435ebe] text-white flex items-center justify-center shrink-0 mt-0.5 text-xs shadow-xs"
                        >
                            <i class="fa-solid fa-robot"></i>
                        </div>

                        <!-- Bubble Card -->
                        <div
                            class="max-w-[80%] rounded-2xl px-4 py-3 shadow-xs relative group"
                            :class="msg.role === 'user'
                                ? 'bg-[#435ebe] text-white rounded-tr-xs'
                                : 'bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 text-gray-800 dark:text-gray-200 rounded-tl-xs'"
                        >
                            <div class="whitespace-pre-wrap font-sans text-xs select-text leading-relaxed">
                                {{ msg.content }}
                            </div>

                            <div
                                class="flex items-center justify-between gap-3 mt-1.5 pt-1 text-[10px]"
                                :class="msg.role === 'user' ? 'text-blue-100 border-t border-white/10' : 'text-gray-400 border-t border-gray-100 dark:border-gray-800/80'"
                            >
                                <span>{{ msg.timestamp }}</span>
                                <button
                                    @click="copyMessage(msg.content, idx)"
                                    class="hover:opacity-100 opacity-60 transition-opacity inline-flex items-center gap-1 cursor-pointer"
                                    title="Salin teks pesan"
                                >
                                    <i :class="copiedIdx === idx ? 'fa-solid fa-check text-emerald-400' : 'fa-regular fa-copy'"></i>
                                    <span>{{ copiedIdx === idx ? 'Disalin' : 'Salin' }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- User Avatar -->
                        <div
                            v-if="msg.role === 'user'"
                            class="w-8 h-8 rounded-xl bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300 flex items-center justify-center shrink-0 mt-0.5 text-xs font-bold"
                        >
                            JA
                        </div>
                    </div>

                    <!-- Typing Indicator -->
                    <div v-if="isSending" class="flex gap-3 justify-start text-xs items-center">
                        <div class="w-8 h-8 rounded-xl bg-[#435ebe] text-white flex items-center justify-center text-xs shadow-xs animate-pulse">
                            <i class="fa-solid fa-robot"></i>
                        </div>
                        <div class="bg-white dark:bg-[#1e1e2d] border border-gray-200 dark:border-gray-800 rounded-2xl px-4 py-2.5 text-gray-500 dark:text-gray-400 flex items-center gap-2 shadow-xs">
                            <i class="fa-solid fa-spinner animate-spin text-[#435ebe] text-xs"></i>
                            <span class="text-xs">Expressa Assist sedang berpikir dan mengeksekusi tools...</span>
                        </div>
                    </div>
                </div>

                <!-- Bottom Input Box -->
                <div class="p-4 border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-[#1e1e2d]">
                    <div class="flex items-end gap-2.5 rounded-2xl bg-gray-50 dark:bg-[#151521] border border-gray-200 dark:border-gray-800 p-2 focus-within:border-[#435ebe] transition-all">
                        <textarea
                            v-model="inputPrompt"
                            @keydown="handleKeydown"
                            :disabled="isSending"
                            rows="2"
                            placeholder="Tanyakan apa saja ke Expressa Assist... (Enter untuk kirim, Shift+Enter untuk baris baru)"
                            class="w-full bg-transparent text-xs text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none resize-none px-2 py-1 leading-relaxed"
                        ></textarea>

                        <button
                            @click="sendUserMessage"
                            :disabled="isSending || !inputPrompt.trim()"
                            class="w-9 h-9 rounded-xl bg-[#435ebe] hover:bg-[#354bb4] disabled:opacity-40 text-white flex items-center justify-center transition-all shrink-0 cursor-pointer shadow-sm"
                            title="Kirim Pesan"
                        >
                            <i v-if="isSending" class="fa-solid fa-spinner animate-spin text-xs"></i>
                            <i v-else class="fa-solid fa-paper-plane text-xs"></i>
                        </button>
                    </div>

                    <div class="flex items-center justify-between text-[11px] text-gray-400 mt-2 px-1">
                        <span>Expressa Assist (Autonomous AI Agent)</span>
                        <span>Shift + Enter untuk baris baru</span>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
