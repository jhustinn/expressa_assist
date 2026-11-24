@extends('layouts.app')

@section('page_title', 'Live Monitoring & Gateway Console')

@section('content')
<div class="space-y-8" x-data="monitoringHub()">

    <!-- Gateway Control Card -->
    <div class="bg-dark-900 border border-slate-800 rounded-2xl p-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl flex items-center justify-center transition shadow-lg"
                     :class="status.running ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 shadow-emerald-500/10' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'">
                    <i data-lucide="activity" class="w-6 h-6"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-bold text-slate-100">Hermes Gateway Process</h3>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold"
                              :class="status.running ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'">
                            <span class="w-1.5 h-1.5 rounded-full" :class="status.running ? 'bg-emerald-400 animate-pulse' : 'bg-rose-400'"></span>
                            <span x-text="status.status"></span>
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">
                        PID: <span class="font-mono text-slate-200" x-text="status.pid || 'N/A'"></span> &bull; 
                        Binary: <span class="font-mono text-slate-400">E:\hermes\bin\hermes.exe gateway run</span>
                    </p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center gap-3">
                <button @click="triggerAction('start')" :disabled="status.running || loading"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 disabled:opacity-40 disabled:pointer-events-none text-white transition flex items-center gap-2 shadow-lg shadow-emerald-600/20">
                    <i data-lucide="play" class="w-3.5 h-3.5"></i>
                    <span>Start Gateway</span>
                </button>

                <button @click="triggerAction('restart')" :disabled="loading"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-dark-800 hover:bg-dark-700 text-brand-400 border border-slate-700 transition flex items-center gap-2">
                    <i data-lucide="rotate-cw" class="w-3.5 h-3.5" :class="loading ? 'animate-spin' : ''"></i>
                    <span>Restart</span>
                </button>

                <button @click="triggerAction('stop')" :disabled="!status.running || loading"
                        class="px-4 py-2 rounded-xl text-xs font-semibold bg-rose-600/20 hover:bg-rose-600/30 text-rose-400 border border-rose-500/30 disabled:opacity-40 disabled:pointer-events-none transition flex items-center gap-2">
                    <i data-lucide="square" class="w-3.5 h-3.5"></i>
                    <span>Stop</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Live Gateway Terminal Logs -->
    <div class="bg-dark-900 border border-slate-800 rounded-2xl p-6 flex flex-col">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                <i data-lucide="terminal-square" class="w-5 h-5 text-emerald-400"></i>
                <h3 class="font-bold text-slate-100 text-sm">Live Gateway Log Console (E:\hermes\gateway.log)</h3>
            </div>
            <div class="flex items-center gap-3">
                <label class="flex items-center gap-2 text-xs text-slate-400 cursor-pointer">
                    <input type="checkbox" x-model="autoScroll" class="rounded bg-dark-950 border-slate-700 text-brand-500 focus:ring-0">
                    <span>Auto-scroll</span>
                </label>
                <button @click="fetchLogs()" class="p-1.5 rounded-lg bg-dark-800 text-slate-300 hover:text-white transition">
                    <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                </button>
            </div>
        </div>

        <div id="log-terminal" class="bg-black/80 border border-slate-800 rounded-xl p-4 font-mono text-xs text-slate-300 overflow-y-auto h-[450px] space-y-1">
            <template x-for="(line, index) in logs" :key="index">
                <div class="leading-relaxed hover:bg-white/5 px-1 rounded break-all">
                    <span class="text-emerald-500 select-none mr-2">&gt;</span>
                    <span x-text="line"></span>
                </div>
            </template>
            <div x-show="logs.length === 0" class="text-slate-500 py-6 text-center">
                Belum ada aktivitas di log.
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function monitoringHub() {
        return {
            status: @json($status),
            logs: @json($logs),
            loading: false,
            autoScroll: true,
            pollTimer: null,

            init() {
                lucide.createIcons();
                this.scrollToBottom();
                // Polling status and logs every 3 seconds
                this.pollTimer = setInterval(() => {
                    this.fetchStatus();
                    this.fetchLogs();
                }, 3000);
            },

            async fetchStatus() {
                try {
                    const res = await fetch("{{ route('api.gateway.status') }}");
                    if (res.ok) {
                        this.status = await res.json();
                        // Update sidebar status badge too
                        const pill = document.getElementById('sidebar-gateway-pill');
                        if (pill) {
                            if (this.status.running) {
                                pill.className = 'inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';
                                pill.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> ONLINE';
                            } else {
                                pill.className = 'inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20';
                                pill.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> STOPPED';
                            }
                        }
                    }
                } catch (e) {}
            },

            async fetchLogs() {
                try {
                    const res = await fetch("{{ route('api.gateway.logs') }}");
                    if (res.ok) {
                        const data = await res.json();
                        this.logs = data.logs;
                        if (this.autoScroll) {
                            this.$nextTick(() => this.scrollToBottom());
                        }
                    }
                } catch (e) {}
            },

            async triggerAction(action) {
                this.loading = true;
                try {
                    const res = await fetch("{{ route('monitoring.action') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ action })
                    });
                    await this.fetchStatus();
                    await this.fetchLogs();
                } catch (e) {
                    alert('Error: ' + e.message);
                } finally {
                    this.loading = false;
                }
            },

            scrollToBottom() {
                const el = document.getElementById('log-terminal');
                if (el) el.scrollTop = el.scrollHeight;
            }
        }
    }
</script>
@endpush
