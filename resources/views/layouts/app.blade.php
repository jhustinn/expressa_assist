<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Hermes Control Center' }} — AI Agent Orchestrator</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                            950: '#082f49',
                        },
                        dark: {
                            800: '#1e222d',
                            850: '#171a23',
                            900: '#0f1117',
                            950: '#0a0c10',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0f1117; }
        ::-webkit-scrollbar-thumb { background: #262c3d; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #38bdf8; }
    </style>
</head>
<body class="bg-dark-950 text-slate-200 min-h-screen flex flex-col font-sans antialiased selection:bg-brand-500 selection:text-white">

    <!-- App Container -->
    <div class="flex flex-1 min-h-screen">
        
        <!-- Sidebar -->
        <aside class="w-64 bg-dark-900 border-r border-slate-800 flex flex-col shrink-0">
            <!-- Brand -->
            <div class="h-16 flex items-center px-6 border-b border-slate-800 gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-brand-500/20">
                    <i data-lucide="bot" class="w-5 h-5"></i>
                </div>
                <div>
                    <h1 class="font-bold text-slate-100 text-sm tracking-wide">HERMES CONTROL</h1>
                    <span class="text-[10px] font-medium text-brand-400 uppercase tracking-wider">Agent & Telegram Hub</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('dashboard') ? 'bg-brand-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-slate-200 hover:bg-dark-800' }}">
                    <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('config.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('config.*') ? 'bg-brand-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-slate-200 hover:bg-dark-800' }}">
                    <i data-lucide="settings-2" class="w-4 h-4"></i>
                    <span>Hermes Config</span>
                </a>

                <a href="{{ route('telegram.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('telegram.*') ? 'bg-brand-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-slate-200 hover:bg-dark-800' }}">
                    <i data-lucide="send" class="w-4 h-4"></i>
                    <span>Telegram & Commands</span>
                </a>

                <a href="{{ route('profiles.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('profiles.*') ? 'bg-brand-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-slate-200 hover:bg-dark-800' }}">
                    <i data-lucide="users" class="w-4 h-4"></i>
                    <span>Profiles & Agents</span>
                </a>

                <a href="{{ route('monitoring.index') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all {{ request()->routeIs('monitoring.*') ? 'bg-brand-600 text-white shadow-lg shadow-brand-600/30' : 'text-slate-400 hover:text-slate-200 hover:bg-dark-800' }}">
                    <i data-lucide="activity" class="w-4 h-4"></i>
                    <span>Live Monitoring & Logs</span>
                </a>
            </nav>

            <!-- Bottom System Status -->
            <div class="p-4 border-t border-slate-800 bg-dark-950/50">
                <div class="flex items-center justify-between text-xs text-slate-400 mb-2">
                    <span class="font-medium">Gateway Daemon</span>
                    <span id="sidebar-gateway-pill" class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> ONLINE
                    </span>
                </div>
                <div class="text-[11px] text-slate-400 truncate">
                    Home: <span class="text-slate-300 font-mono">E:\hermes</span>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            
            <!-- Topbar -->
            <header class="h-16 bg-dark-900 border-b border-slate-800 px-8 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <h2 class="text-lg font-semibold text-slate-100">@yield('page_title', 'Dashboard')</h2>
                    @yield('page_badge')
                </div>

                <div class="flex items-center gap-4">
                    <!-- Quick Restart Gateway Button -->
                    <form action="{{ route('monitoring.action') }}" method="POST">
                        @csrf
                        <input type="hidden" name="action" value="restart">
                        <button type="submit" class="flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-dark-800 text-slate-200 hover:bg-dark-850 hover:text-white border border-slate-700 transition shadow-sm">
                            <i data-lucide="rotate-cw" class="w-3.5 h-3.5 text-brand-400"></i>
                            <span>Restart Gateway</span>
                        </button>
                    </form>

                    <!-- Active Agent Badge -->
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-dark-850 border border-slate-700/60 text-xs">
                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                        <span class="text-slate-400">Agent:</span>
                        <span class="font-semibold text-indigo-300">Jhustyn (ERP JPP)</span>
                    </div>
                </div>
            </header>

            <!-- Alerts / Flash Messages -->
            @if(session('success'))
                <div class="mx-8 mt-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 flex items-center justify-between">
                    <div class="flex items-center gap-3 text-sm">
                        <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-400"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-200">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            @endif

            @if(session('error'))
                <div class="mx-8 mt-6 p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-300 flex items-center justify-between">
                    <div class="flex items-center gap-3 text-sm">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-rose-400"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-200">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>
            @endif

            <!-- Main Page Content -->
            <main class="flex-1 p-8 overflow-y-auto">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Initialize Lucide Icons -->
    <script>
        lucide.createIcons();
    </script>
    @stack('scripts')
</body>
</html>
