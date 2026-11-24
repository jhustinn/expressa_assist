<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use App\Models\AgentProfile;
use App\Models\GlobalActivityLog;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ProfileController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index()
    {
        $profiles = $this->hermes->getProfiles();
        return Inertia::render('Profiles', compact('profiles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|alpha_dash|max:50',
            'role_title' => 'nullable|string|max:100',
            'model_default' => 'nullable|string',
            'terminal_cwd' => 'nullable|string',
            'soul' => 'nullable|string',
        ]);

        $name = strtolower(trim($request->input('name')));

        // Check if directory already exists
        $profileDir = config('hermes.home', 'E:\hermes') . DIRECTORY_SEPARATOR . 'profiles' . DIRECTORY_SEPARATOR . $name;
        if (File::isDirectory($profileDir)) {
            return redirect()->back()->with('error', "Profil dengan nama '{$name}' sudah ada.");
        }

        $cwd = $request->input('terminal_cwd') ?: 'E:\\App';
        $model = $request->input('model_default') ?: 'gemini-flash-lite-latest';
        $roleTitle = $request->input('role_title') ?: 'Specialized AI Agent';

        $config = [
            'model' => [
                'default' => $model,
            ],
            'terminal' => [
                'backend' => 'local',
                'cwd' => $cwd,
            ],
        ];

        $defaultSoul = $request->input('soul');
        if (empty($defaultSoul)) {
            $defaultSoul = "# " . strtoupper($name) . " — AUTONOMOUS AGENT\n"
                . "> **Role:** {$roleTitle}\n"
                . "> **Target Workspace:** `{$cwd}`\n"
                . "> **Engine:** Expressa Assist\n\n"
                . "## 1. IDENTITY & DIRECTIVES\n"
                . "- Selalu solutif, lugas, zero basa-basi, dan berfokus pada eksekusi kode teruji.\n"
                . "- Lakukan sanity check lokal sebelum menyatakan pekerjaan selesai.\n";
        }

        // Save physical profile files (config.yaml and SOUL.md)
        $saved = $this->hermes->saveProfile($name, $config, $defaultSoul);

        if ($saved) {
            // Also register in database AgentProfile
            AgentProfile::updateOrCreate(
                ['slug' => $name],
                [
                    'name' => ucfirst($name),
                    'role_title' => $roleTitle,
                    'workspace_path' => $cwd,
                    'model_default' => $model,
                    'markdown_content' => $defaultSoul,
                    'markdown_file_path' => $profileDir . DIRECTORY_SEPARATOR . 'SOUL.md',
                    'knowledge_skills' => ['General Task Execution', 'Code Inspection'],
                    'business_modules' => [],
                    'responsibility_paths' => [$cwd],
                    'allowed_users' => ['jesa@expressa.id', 'admin@expressa.id'],
                    'allowed_telegram_ids' => ['8831937787'],
                    'is_active' => true,
                ]
            );

            GlobalActivityLog::create([
                'global_activity_action' => 'profile_create',
                'global_activity_description' => "Membuat profil agent baru: {$name}",
                'global_activity_status' => 'success',
            ]);

            return redirect()->route('profiles.index')->with('success', "Profil agent '{$name}' berhasil dibuat!");
        }

        return redirect()->back()->with('error', "Gagal membuat direktori profil '{$name}'.");
    }

    public function edit(string $name)
    {
        $profile = $this->hermes->getProfile($name);
        if (!$profile) {
            return redirect()->route('profiles.index')->with('error', "Profil {$name} tidak ditemukan.");
        }

        return Inertia::render('ProfileEdit', compact('profile'));
    }

    public function update(Request $request, string $name)
    {
        $request->validate([
            'model_default' => 'nullable|string',
            'terminal_cwd' => 'nullable|string',
            'soul' => 'nullable|string',
        ]);

        $profile = $this->hermes->getProfile($name);
        $config = $profile['config'] ?? [];

        if ($request->filled('model_default')) {
            $config['model']['default'] = $request->input('model_default');
        }
        if ($request->filled('terminal_cwd')) {
            $config['terminal']['backend'] = 'local';
            $config['terminal']['cwd'] = $request->input('terminal_cwd');
        }

        $this->hermes->saveProfile($name, $config, $request->input('soul', ''));

        // Update database AgentProfile if exists
        $dbAgent = AgentProfile::where('slug', $name)->first();
        if ($dbAgent) {
            if ($request->filled('model_default')) {
                $dbAgent->model_default = $request->input('model_default');
            }
            if ($request->filled('terminal_cwd')) {
                $dbAgent->workspace_path = $request->input('terminal_cwd');
            }
            if ($request->filled('soul')) {
                $dbAgent->markdown_content = $request->input('soul');
            }
            $dbAgent->save();
        }

        GlobalActivityLog::create([
            'global_activity_action' => 'profile_update',
            'global_activity_description' => "Memperbarui konfigurasi profil: {$name}",
            'global_activity_status' => 'success',
        ]);

        return redirect()->route('profiles.index')->with('success', "Profil {$name} berhasil diperbarui!");
    }

    public function destroy(string $name)
    {
        if (in_array(strtolower($name), ['jhustyn', 'default'])) {
            return redirect()->back()->with('error', "Profil utama '{$name}' diproteksi dan tidak boleh dihapus.");
        }

        $profileDir = config('hermes.home', 'E:\hermes') . DIRECTORY_SEPARATOR . 'profiles' . DIRECTORY_SEPARATOR . $name;
        if (File::isDirectory($profileDir)) {
            File::deleteDirectory($profileDir);
        }

        AgentProfile::where('slug', $name)->delete();

        GlobalActivityLog::create([
            'global_activity_action' => 'profile_delete',
            'global_activity_description' => "Menghapus profil agent: {$name}",
            'global_activity_status' => 'success',
        ]);

        return redirect()->route('profiles.index')->with('success', "Profil {$name} berhasil dihapus.");
    }
}
