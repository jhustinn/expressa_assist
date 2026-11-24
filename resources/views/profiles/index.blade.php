@extends('layouts.app')

@section('page_title', 'Agent Profiles & Personalities')

@section('content')
<div class="space-y-8">
    
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-base font-bold text-slate-100">Hermes Agent Profiles</h3>
            <p class="text-xs text-slate-400 mt-1">Daftar profil agen otonom dan direktori kerja lokal masing-masing</p>
        </div>
    </div>

    <!-- Profiles Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($profiles as $profile)
            <div class="bg-dark-900 border border-slate-800 rounded-2xl p-6 flex flex-col justify-between hover:border-slate-700 transition">
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 flex items-center justify-center font-bold font-mono">
                                {{ strtoupper(substr($profile['name'], 0, 2)) }}
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-100 text-sm capitalize">{{ $profile['name'] }}</h4>
                                <span class="text-[11px] font-mono text-slate-400">{{ $profile['model'] ?? 'Inherit default' }}</span>
                            </div>
                        </div>
                        @if($profile['name'] === 'jhustyn')
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                ERP JPP LEAD
                            </span>
                        @endif
                    </div>

                    <!-- Directory Workspace -->
                    <div class="bg-dark-950/70 border border-slate-800 rounded-xl p-3 text-xs font-mono">
                        <span class="text-slate-500 block text-[10px] uppercase font-bold tracking-wider mb-1">Target Workspace</span>
                        <span class="text-slate-300 truncate block">{{ $profile['cwd'] ?? 'Inherit global cwd' }}</span>
                    </div>

                    <!-- SOUL / Personality preview -->
                    <div class="text-xs text-slate-400 line-clamp-3 italic bg-dark-950/40 p-3 rounded-xl border border-slate-800/60">
                        "{{ $profile['soul_preview'] ?: 'Default Hermes autonomous behavior' }}"
                    </div>
                </div>

                <div class="pt-5 mt-5 border-t border-slate-800 flex items-center justify-end">
                    <a href="{{ route('profiles.edit', $profile['name']) }}" class="px-4 py-2 rounded-xl text-xs font-semibold bg-dark-800 hover:bg-dark-700 text-brand-400 border border-slate-700 transition flex items-center gap-2">
                        <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                        <span>Edit Profil & SOUL</span>
                    </a>
                </div>
            </div>
        @endforeach
    </div>

</div>
@endsection
