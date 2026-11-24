@extends('layouts.app')

@section('page_title', 'Hermes & Model Configuration')

@section('content')
<div class="max-w-5xl space-y-8" x-data="configManager()">
    
    <!-- Top Action Card -->
    <div class="bg-dark-900 border border-slate-800 rounded-2xl p-6">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-800">
            <div>
                <h3 class="text-base font-bold text-slate-100">LLM Engine & Provider Settings</h3>
                <p class="text-xs text-slate-400 mt-1">Konfigurasi file <span class="font-mono text-slate-300">E:\hermes\config.yaml</span> dan <span class="font-mono text-slate-300">E:\hermes\.env</span></p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-brand-500/10 text-brand-400 border border-brand-500/20">
                Active Provider: {{ $config['model']['provider'] ?? 'custom' }}
            </span>
        </div>

        <form action="{{ route('config.update') }}" method="POST" class="space-y-6">
            @csrf

            <!-- LLM Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Default LLM Model
                    </label>
                    <input type="text" name="model_default" value="{{ old('model_default', $config['model']['default'] ?? 'qwen/qwen3.8-27b') }}"
                           class="w-full bg-dark-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm font-mono text-slate-200 focus:outline-none focus:border-brand-500 transition" required>
                    <p class="text-[11px] text-slate-500 mt-1.5">Contoh: <code class="text-slate-400 font-mono">qwen/qwen3.8-27b</code>, <code class="text-slate-400 font-mono">gemini-3.8-flash</code></p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Inference Provider
                    </label>
                    <select name="model_provider" class="w-full bg-dark-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:outline-none focus:border-brand-500 transition">
                        <option value="custom" {{ ($config['model']['provider'] ?? '') === 'custom' ? 'selected' : '' }}>custom (Groq / OpenAI-Compatible)</option>
                        <option value="gemini" {{ ($config['model']['provider'] ?? '') === 'gemini' ? 'selected' : '' }}>gemini (Google AI Studio)</option>
                        <option value="openrouter" {{ ($config['model']['provider'] ?? '') === 'openrouter' ? 'selected' : '' }}>openrouter (OpenRouter AI)</option>
                        <option value="anthropic" {{ ($config['model']['provider'] ?? '') === 'anthropic' ? 'selected' : '' }}>anthropic (Claude Direct)</option>
                    </select>
                </div>
            </div>

            <!-- Base URL & Working Directory -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Base URL (OpenAI-Compatible Wire)
                    </label>
                    <input type="text" name="model_base_url" value="{{ old('model_base_url', $config['model']['base_url'] ?? 'https://api.groq.com/openai/v1') }}"
                           class="w-full bg-dark-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm font-mono text-slate-200 focus:outline-none focus:border-brand-500 transition">
                    <p class="text-[11px] text-slate-500 mt-1.5">Default Groq: <code class="text-slate-400 font-mono">https://api.groq.com/openai/v1</code></p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Default Terminal Workspace CWD
                    </label>
                    <input type="text" name="terminal_cwd" value="{{ old('terminal_cwd', $config['terminal']['cwd'] ?? 'E:\\App\\SWA\\JPP-ERP') }}"
                           class="w-full bg-dark-950 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm font-mono text-slate-200 focus:outline-none focus:border-brand-500 transition">
                    <p class="text-[11px] text-slate-500 mt-1.5">Direktori eksekusi ngoding lokal</p>
                </div>
            </div>

            <!-- API Keys Section -->
            <div class="pt-6 border-t border-slate-800 space-y-6">
                <div class="flex items-center gap-2">
                    <i data-lucide="key" class="w-5 h-5 text-amber-400"></i>
                    <h4 class="font-bold text-slate-100 text-sm">API Key Vault & Connectivity</h4>
                </div>

                <!-- Groq API Key -->
                <div class="bg-dark-950/60 border border-slate-800 rounded-xl p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-slate-300">GROQ_API_KEY (Ultra-fast & Free)</label>
                        <button type="button" @click="testApi('groq', groqKey)" :disabled="testing"
                                class="px-3 py-1 rounded-lg text-xs font-semibold bg-dark-800 hover:bg-dark-700 text-brand-400 border border-slate-700 transition flex items-center gap-1.5">
                            <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                            <span>Test Groq Connection</span>
                        </button>
                    </div>
                    <input type="password" name="groq_api_key" x-model="groqKey"
                           class="w-full bg-dark-900 border border-slate-700/80 rounded-lg px-4 py-2 text-sm font-mono text-slate-200 focus:outline-none focus:border-brand-500 transition">
                </div>

                <!-- Google Gemini API Key -->
                <div class="bg-dark-950/60 border border-slate-800 rounded-xl p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-slate-300">GOOGLE_API_KEY (Gemini Direct)</label>
                        <button type="button" @click="testApi('gemini', geminiKey)" :disabled="testing"
                                class="px-3 py-1 rounded-lg text-xs font-semibold bg-dark-800 hover:bg-dark-700 text-purple-400 border border-slate-700 transition flex items-center gap-1.5">
                            <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                            <span>Test Gemini Connection</span>
                        </button>
                    </div>
                    <input type="password" name="google_api_key" x-model="geminiKey"
                           class="w-full bg-dark-900 border border-slate-700/80 rounded-lg px-4 py-2 text-sm font-mono text-slate-200 focus:outline-none focus:border-brand-500 transition">
                </div>

                <!-- OpenRouter API Key -->
                <div class="bg-dark-950/60 border border-slate-800 rounded-xl p-4 space-y-3">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-semibold text-slate-300">OPENROUTER_API_KEY (Multi-Model Gateway)</label>
                        <button type="button" @click="testApi('openrouter', openrouterKey)" :disabled="testing"
                                class="px-3 py-1 rounded-lg text-xs font-semibold bg-dark-800 hover:bg-dark-700 text-emerald-400 border border-slate-700 transition flex items-center gap-1.5">
                            <i data-lucide="zap" class="w-3.5 h-3.5"></i>
                            <span>Test OpenRouter</span>
                        </button>
                    </div>
                    <input type="password" name="openrouter_api_key" x-model="openrouterKey"
                           class="w-full bg-dark-900 border border-slate-700/80 rounded-lg px-4 py-2 text-sm font-mono text-slate-200 focus:outline-none focus:border-brand-500 transition">
                </div>

                <!-- Live Test Result Banner -->
                <div x-show="testResult" x-cloak
                     class="p-4 rounded-xl text-xs font-mono transition"
                     :class="testResult && testResult.status === 'success' ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-300' : 'bg-rose-500/10 border border-rose-500/20 text-rose-300'">
                    <div class="flex items-center justify-between">
                        <span class="font-bold" x-text="testResult ? testResult.message : ''"></span>
                        <span x-show="testResult && testResult.latency_ms" class="text-[11px] px-2 py-0.5 rounded bg-black/40">
                            Latency: <strong x-text="testResult ? testResult.latency_ms + ' ms' : ''"></strong>
                        </span>
                    </div>
                    <div x-show="testResult && testResult.models && testResult.models.length > 0" class="mt-2 text-slate-400">
                        Available Models: <span class="text-slate-200" x-text="testResult.models.join(', ')"></span>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 flex items-center justify-end">
                <button type="submit" class="px-6 py-2.5 rounded-xl text-sm font-semibold bg-brand-600 text-white hover:bg-brand-500 transition shadow-lg shadow-brand-600/20 flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Simpan Konfigurasi</span>
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<script>
    function configManager() {
        return {
            groqKey: @json($env['GROQ_API_KEY'] ?? ''),
            geminiKey: @json($env['GOOGLE_API_KEY'] ?? $env['GEMINI_API_KEY'] ?? ''),
            openrouterKey: @json($env['OPENROUTER_API_KEY'] ?? ''),
            testing: false,
            testResult: null,

            async testApi(provider, key) {
                if (!key) {
                    alert('Masukkan API key terlebih dahulu!');
                    return;
                }
                this.testing = true;
                this.testResult = { status: 'loading', message: 'Menguji koneksi ke ' + provider + '...' };

                try {
                    const res = await fetch("{{ route('monitoring.test-api') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ provider, api_key: key })
                    });
                    const data = await res.json();
                    this.testResult = data;
                } catch (err) {
                    this.testResult = { status: 'error', message: 'Gagal menghubungi server: ' + err.message };
                } finally {
                    this.testing = false;
                }
            }
        }
    }
</script>
@endpush
