<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use App\Models\SystemSetting;
use App\Models\GlobalActivityLog;
use Inertia\Inertia;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index(Request $request)
    {
        $profile = $request->query('profile', 'default');
        $stats = $this->hermes->getSystemStats($profile);
        $gatewayStatus = $this->hermes->getGatewayStatus();

        $profiles = array_merge([['name' => 'default', 'path' => '']], $this->hermes->getProfiles());
        $uniqueProfiles = [];
        foreach ($profiles as $p) {
            $uniqueProfiles[$p['name']] = $p;
        }

        $systemPaths = [
            'hermes_home_path' => SystemSetting::get('hermes_home_path', 'E:\\hermes'),
            'hermes_bin_path' => SystemSetting::get('hermes_bin_path', 'E:\\hermes\\bin\\hermes.exe'),
            'project_directory_path' => SystemSetting::get('project_directory_path', base_path()),
            'backup_path' => SystemSetting::get('backup_path', 'E:\\hermes\\backups'),
            'gateway_host' => SystemSetting::get('gateway_host', '127.0.0.1'),
            'gateway_port' => SystemSetting::get('gateway_port', 9119),
            'default_profile' => SystemSetting::get('default_profile', 'default'),
        ];

        return Inertia::render('System/Index', [
            'stats' => $stats,
            'gateway' => $gatewayStatus,
            'hermesHome' => $systemPaths['hermes_home_path'],
            'systemPaths' => $systemPaths,
            'profiles' => array_values($uniqueProfiles),
            'currentProfile' => $profile,
        ]);
    }

    public function updatePaths(Request $request)
    {
        $validated = $request->validate([
            'hermes_home_path' => 'required|string',
            'hermes_bin_path' => 'required|string',
            'project_directory_path' => 'required|string',
            'backup_path' => 'nullable|string',
            'gateway_host' => 'nullable|string',
            'gateway_port' => 'nullable|integer',
        ]);

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                SystemSetting::set(
                    $key,
                    $value,
                    str_contains($key, 'path') ? 'paths' : 'gateway',
                    is_int($value) ? 'integer' : 'string',
                    ucwords(str_replace('_', ' ', $key)),
                    "Konfigurasi direktori sistem yang disimpan di database."
                );
            }
        }

        GlobalActivityLog::create([
            'global_activity_action' => 'system_paths_update',
            'global_activity_description' => "Memperbarui konfigurasi direktori path sistem dan gateway di database",
            'global_activity_status' => 'success',
        ]);

        return redirect()->back()->with('success', 'Konfigurasi path direktori berhasil disimpan ke database!');
    }

    public function logs(Request $request)
    {
        $profile = $request->query('profile', 'default');
        $logs = $this->hermes->getRecentLogs(250);

        return Inertia::render('Logs/Index', [
            'logs' => $logs,
            'currentProfile' => $profile,
        ]);
    }
}
