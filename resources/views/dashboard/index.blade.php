@extends('layouts.app')

@section('page_title', 'Overview Dashboard')

@section('page_badge')
    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-brand-500/10 text-brand-400 border border-brand-500/20">
        v1.0 Live
    </span>
@endsection

@section('content')
<div class="space-y-8">
    
    <!-- 4 Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        
        <!-- Card 1: Gateway Daemon -->
        <div class="bg-dark-900 border border-slate-800 rounded-2xl p-5 shadow-sm hover:border-slate-700 transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Gateway Status</span>
                <div class="p-2 rounded-xl {{ $gatewayStatus['running'] ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' }}">
                    <i data-lucide="radio" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="flex items-baseline gap-2 mb-1">
                <h3 class="text-2xl font-bold {{ $gatewayStatus['running'] ? 'text-emerald-400' : 'text-rose-400' }}">
                    {{ $gatewayStatus['status'] }}
                </h3>
                @if($gatewayStatus['pid'])
                    <span class="text-xs text-slate-500 font-mono">PID: {{ $gatewayStatus['pid'] }}</span>
                @endif
            </div>
            <p class="text-xs text-slate-400">Telegram daemon listener is active</p>
        </div>

        <!-- Card 2: Active Model & Provider -->
        <div class="bg-dark-900 border border-slate-800 rounded-2xl p-5 shadow-sm hover:border-slate-700 transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Active LLM Model</span>
                <div class="p-2 rounded-xl bg-purple-500/10 text-purple-400">
                    <i data-lucide="cpu" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mb-1 truncate">
                <h3 class="text-lg font-bold text-slate-100 truncate" title="{{ $config['model']['default'] ?? 'N/A' }}">
                    {{ $config['model']['default'] ?? 'N/A' }}
                </h3>
            </div>
            <div class="flex items-center gap-1.5 text-xs text-slate-400">
                <span class="font-medium text-purple-300 uppercase">Provider:</span>
                <span class="font-mono text-slate-300">{{ $config['model']['provider'] ?? 'custom' }}</span>
            </div>
        </div>

        <!-- Card 3: Telegram Bot -->
        <div class="bg-dark-900 border border-slate-800 rounded-2xl p-5 shadow-sm hover:border-slate-700 transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Telegram Bot</span>
                <div class="p-2 rounded-xl bg-brand-500/10 text-brand-400">
                    <i data-lucide="send" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="mb-1 truncate">
                <h3 class="text-lg font-bold text-slate-100">@jhustyn_hermes_bot</h3>
            </div>
            <div class="flex items-center gap-1.5 text-xs text-slate-400 truncate">
                <span>Pairing User ID:</span>
                <span class="font-mono text-brand-300 font-semibold">{{ $env['TELEGRAM_ALLOWED_USERS'] ?? '7605759955' }}</span>
            </div>
        </div>

        <!-- Card 4: Commands Registered -->
        <div class="bg-dark-900 border border-slate-800 rounded-2xl p-5 shadow-sm hover:border-slate-700 transition">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-slate-400 uppercase tracking-wider">Telegram Commands</span>
                <div class="p-2 rounded-xl bg-amber-500/10 text-amber-400">
                    <i data-lucide="terminal" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="flex items-baseline gap-2 mb-1">
                <h3 class="text-2xl font-bold text-slate-100">{{ $commands->count() }}</h3>
                <span class="text-xs text-slate-500">custom skills</span>
            </div>
            <p class="text-xs text-slate-400">
                <a href="{{ route('telegram.index') }}" class="text-brand-400 hover:underline">Kelola Command &rarr;</a>
            </p>
        </div>
    </div>

    <!-- Quick Actions Banner -->
    <div class="bg-gradient-to-r from-dark-900 via-dark-850 to-dark-900 border border-slate-800 rounded-2xl p-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center shrink-0">
                <i data-lucide="shield-check" class="w-6 h-6"></i>
            </div>
            <div>
                <h4 class="font-bold text-slate-100 text-base">Jhustyn Agent — ERP JPP Automation Ready</h4>
                <p class="text-xs text-slate-400 mt-0.5">
                    Workspace: <span class="text-slate-300 font-mono">E:\App\SWA\JPP-ERP</span> &bull; Terhubung ke GitHub & Telegram
                </p>
            </div>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <a href="{{ route('telegram.index') }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-brand-600 text-white hover:bg-brand-500 transition shadow-lg shadow-brand-600/20 flex items-center gap-2">
                <i data-lucide="terminal" class="w-3.5 h-3.5"></i>
                <span>Kelola Command</span>
            </a>
            <a href="{{ route('config.index') }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-dark-800 text-slate-200 hover:bg-dark-700 border border-slate-700 transition flex items-center gap-2">
                <i data-lucide="sliders" class="w-3.5 h-3.5"></i>
                <span>Setting API Key</span>
            </a>
        </div>
    </div>

    <!-- Grid Two Columns: Telegram Commands & Recent Gateway Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Left: Quick Commands List -->
        <div class="bg-dark-900 border border-slate-800 rounded-2xl p-6">
            <div class="flex items-center justify-between mb-5">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="command" class="w-5 h-5 text-brand-400"></i>
                    <h3 class="font-bold text-slate-100 text-sm">Command Telegram Aktif</h3>
                </div>
                <a href="{{ route('telegram.index') }}" class="text-xs text-brand-400 hover:text-brand-300 font-medium">
                    + Tambah Command
                </a>
            </div>

            <div class="space-y-3">
                @forelse($commands as $cmd)
                    <div class="p-3.5 rounded-xl bg-dark-950/60 border border-slate-800/80 flex items-center justify-between hover:border-slate-700 transition">
                        <div class="flex items-start gap-3">
                            <span class="px-2.5 py-1 rounded-lg text-xs font-mono font-bold bg-brand-500/10 text-brand-400 border border-brand-500/20">
                                {{ $cmd->command }}
                            </span>
                            <div>
                                <h4 class="text-xs font-semibold text-slate-200">{{ $cmd->name }}</h4>
                                <p class="text-[11px] text-slate-400 mt-0.5">{{ $cmd->description }}</p>
                            </div>
                        </div>
                        <span class="text-[11px] font-mono text-indigo-300 px-2 py-0.5 rounded bg-indigo-500/10 border border-indigo-500/20">
                            {{ $cmd->target_profile }}
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 py-4 text-center">Belum ada command yang didaftarkan.</p>
                @endforelse
            </div>
        </div>

        <!-- Right: Real-time Gateway Logs Preview -->
        <div class="bg-dark-900 border border-slate-800 rounded-2xl p-6 flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2.5">
                    <i data-lucide="file-text" class="w-5 h-5 text-emerald-400"></i>
                    <h3 class="font-bold text-slate-100 text-sm">Recent Gateway Logs</h3>
                </div>
                <a href="{{ route('monitoring.index') }}" class="text-xs text-brand-400 hover:text-brand-300 font-medium">
                    Full Console &rarr;
                </a>
            </div>

            <!-- Terminal Log Window -->
            <div class="flex-1 bg-black/70 border border-slate-800 rounded-xl p-4 font-mono text-[11px] text-slate-300 overflow-y-auto max-h-[300px] space-y-1">
                @forelse($recentLogs as $log)
                    <div class="leading-relaxed hover:bg-white/5 px-1 rounded truncate">
                        <span class="text-emerald-500 font-bold">&gt;</span> {{ $log }}
                    </div>
                @empty
                    <div class="text-slate-500">Log gateway masih kosong. Jalankan gateway untuk mulai memantau aktivitas bot.</div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
