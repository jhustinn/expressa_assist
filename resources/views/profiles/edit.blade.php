@extends('layouts.app')

@section('page_title', 'Edit Profile: ' . ucfirst($profile['name']))

@section('content')
<div class="max-w-4xl space-y-8">

    <div class="bg-dark-900 border border-slate-800 rounded-2xl p-6">
        <div class="flex items-center justify-between pb-4 mb-6 border-b border-slate-800">
            <div>
                <h3 class="text-base font-bold text-slate-100">Konfigurasi Profil: {{ $profile['name'] }}</h3>
                <p class="text-xs text-slate-400 mt-1">Mengedit SOUL, kepribadian, instruksi arsitektur, dan path direktori lokal</p>
            </div>
            <a href="{{ route('profiles.index') }}" class="text-xs text-slate-400 hover:text-slate-200">
                &larr; Kembali ke Daftar
            </a>
        </div>

        <form action="{{ route('profiles.update', $profile['name']) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Override Model (Opsional)
                    </label>
                    <input type="text" name="model_default" value="{{ old('model_default', $profile['config']['model']['default'] ?? '') }}"
                           placeholder="Kosongkan jika mengikuti default Hermes"
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-mono text-slate-200 focus:outline-none focus:border-brand-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Workspace Root Directory
                    </label>
                    <input type="text" name="terminal_cwd" value="{{ old('terminal_cwd', $profile['config']['terminal']['cwd'] ?? '') }}"
                           placeholder="E:\App\SWA\JPP-ERP"
                           class="w-full bg-dark-950 border border-slate-700 rounded-xl px-4 py-2.5 text-sm font-mono text-slate-200 focus:outline-none focus:border-brand-500">
                </div>
            </div>

            <!-- SOUL / System Prompt Editor -->
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider">
                        SOUL & System Prompt Directive (SOUL.md)
                    </label>
                    <span class="text-[11px] text-slate-500">Markdown format didukung</span>
                </div>
                <textarea name="soul" rows="16"
                          class="w-full bg-dark-950 border border-slate-700 rounded-xl p-4 text-xs font-mono text-slate-200 leading-relaxed focus:outline-none focus:border-brand-500">{{ old('soul', $profile['soul'] ?? '') }}</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-3">
                <a href="{{ route('profiles.index') }}" class="px-5 py-2.5 rounded-xl text-xs font-semibold bg-dark-800 text-slate-300 hover:bg-dark-700">
                    Batal
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-semibold bg-brand-600 text-white hover:bg-brand-500 shadow-lg shadow-brand-600/20 flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Simpan Perubahan Profil</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
