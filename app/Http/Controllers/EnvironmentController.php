<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use App\Models\GlobalActivityLog;
use App\Models\SystemSetting;
use Inertia\Inertia;
use Illuminate\Http\Request;

class EnvironmentController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index()
    {
        $variables = $this->hermes->getEnvironmentVariables();
        $categories = array_values(array_unique(array_column($variables, 'category')));

        $stats = [
            'total' => count($variables),
            'configured' => count(array_filter($variables, fn($v) => $v['isSet'])),
            'missing' => count(array_filter($variables, fn($v) => !$v['isSet'])),
            'db_synced' => SystemSetting::whereIn('group', ['api_keys', 'environment', 'paths', 'gateway'])->count(),
        ];

        return Inertia::render('Environment', compact('variables', 'categories', 'stats'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string',
            'value' => 'nullable|string',
        ]);

        $key = trim($validated['key']);
        $value = $validated['value'] !== null ? trim($validated['value']) : '';

        $updated = $this->hermes->updateEnv([$key => $value]);

        if ($updated) {
            GlobalActivityLog::create([
                'global_activity_action' => 'env_variable_update',
                'global_activity_description' => "Memperbarui variabel environment {$key} di Database dan .env",
                'global_activity_status' => 'success',
            ]);

            return redirect()->back()->with('success', "Variabel {$key} berhasil diperbarui di database dan .env!");
        }

        return redirect()->back()->with('error', "Gagal memperbarui variabel {$key}.");
    }

    public function syncFromEnvFile()
    {
        $env = $this->hermes->getEnv();
        $count = 0;
        foreach ($env as $k => $v) {
            $isSecret = str_contains(strtoupper($k), 'KEY') ||
                str_contains(strtoupper($k), 'TOKEN') ||
                str_contains(strtoupper($k), 'SECRET') ||
                str_contains(strtoupper($k), 'PASSWORD');
            $group = (str_contains(strtoupper($k), 'KEY') || str_contains(strtoupper($k), 'TOKEN')) ? 'api_keys' : 'environment';

            SystemSetting::set($k, $v, $group, $isSecret ? 'password' : 'string', null, null, $isSecret);
            $count++;
        }

        GlobalActivityLog::create([
            'global_activity_action' => 'env_sync_to_db',
            'global_activity_description' => "Sinkronisasi {$count} variabel dari .env ke Database",
            'global_activity_status' => 'success',
        ]);

        return redirect()->back()->with('success', "Berhasil menyinkronkan {$count} variabel dari .env ke database!");
    }
}
