<?php

namespace App\Http\Controllers;

use App\Models\AgentProfile;
use App\Models\GlobalUser;
use App\Models\GlobalActivityLog;
use App\Services\HermesService;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AgentManagementController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index()
    {
        $agent = AgentProfile::getJhustyn();

        // Get all system users for access assignment
        $systemUsers = GlobalUser::orderBy('global_user_nama')->get();

        // File stats
        $fileInfo = [
            'exists' => File::exists($agent->markdown_file_path),
            'path' => $agent->markdown_file_path,
            'size' => File::exists($agent->markdown_file_path) ? File::size($agent->markdown_file_path) : 0,
            'last_modified' => File::exists($agent->markdown_file_path) ? date('Y-m-d H:i:s', File::lastModified($agent->markdown_file_path)) : null,
        ];

        // Workspace directory stats
        $workspaceInfo = [
            'exists' => File::isDirectory($agent->workspace_path),
            'path' => $agent->workspace_path,
            'subdirs' => [],
        ];

        if ($workspaceInfo['exists']) {
            try {
                $workspaceInfo['subdirs'] = collect(File::directories($agent->workspace_path))
                    ->map(fn($d) => basename($d))
                    ->values()
                    ->all();
            } catch (\Throwable $e) {
                // Ignore directory read error
            }
        }

        return Inertia::render('Agent/Jhustyn', [
            'agent' => $agent,
            'systemUsers' => $systemUsers,
            'fileInfo' => $fileInfo,
            'workspaceInfo' => $workspaceInfo,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'role_title' => 'required|string|max:255',
            'project_name' => 'required|string|max:255',
            'principal_partner' => 'required|string|max:255',
            'workspace_path' => 'required|string',
            'repository_url' => 'nullable|string',
            'branch' => 'required|string',
            'model_default' => 'required|string',
            'knowledge_skills' => 'nullable|array',
            'business_modules' => 'nullable|array',
            'responsibility_paths' => 'nullable|array',
            'allowed_users' => 'nullable|array',
            'allowed_telegram_ids' => 'nullable|array',
            'system_prompt' => 'nullable|string',
            'markdown_content' => 'required|string',
        ]);

        $agent = AgentProfile::getJhustyn();
        $agent->fill($validated);
        $agent->save();

        // Write directly to disk
        $agent->syncToDisk();

        // Sync allowed telegram IDs to .env if needed
        if (!empty($validated['allowed_telegram_ids'])) {
            $idsString = implode(',', array_filter($validated['allowed_telegram_ids']));
            $this->hermes->updateEnv(['TELEGRAM_ALLOWED_USERS' => $idsString]);
        }

        GlobalActivityLog::create([
            'global_activity_action' => 'agent_profile_update',
            'global_activity_description' => "Memperbarui spesifikasi dan manajemen agent Jhustyn (Jhustyn.md)",
            'global_activity_status' => 'success',
        ]);

        return redirect()->back()->with('success', 'Manajemen Agent Jhustyn dan file Jhustyn.md berhasil diperbarui!');
    }

    public function syncFromDisk()
    {
        $agent = AgentProfile::getJhustyn();
        $synced = $agent->syncFromDisk();

        if ($synced) {
            GlobalActivityLog::create([
                'global_activity_action' => 'agent_profile_sync_disk',
                'global_activity_description' => "Sinkronisasi ulang Jhustyn.md dari file fisik disk",
                'global_activity_status' => 'success',
            ]);

            return redirect()->back()->with('success', 'Berhasil memuat ulang konten dari file Jhustyn.md di disk.');
        }

        return redirect()->back()->with('error', 'Gagal membaca file Jhustyn.md dari disk.');
    }
}
