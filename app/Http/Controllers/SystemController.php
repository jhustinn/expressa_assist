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

    protected function getLogFilePath(string $file, string $profile): ?string
    {
        $logFiles = [
            'agent' => 'agent.log',
            'errors' => 'errors.log',
            'gateway' => 'gateway.log',
            'gui' => 'gui.log',
        ];

        $filename = $logFiles[$file] ?? 'agent.log';
        $hermesHome = SystemSetting::get('hermes_home_path', 'E:\\hermes');

        if ($profile !== 'default' && !empty($profile)) {
            $profilePath = $hermesHome . DIRECTORY_SEPARATOR . 'profiles' . DIRECTORY_SEPARATOR . $profile . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($profilePath)) {
                return $profilePath;
            }
        }

        $defaultPath = $hermesHome . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . $filename;
        if (file_exists($defaultPath)) {
            return $defaultPath;
        }

        $rootPath = $hermesHome . DIRECTORY_SEPARATOR . $filename;
        if (file_exists($rootPath)) {
            return $rootPath;
        }

        return null;
    }

    protected function readTailLines(?string $filePath, int $maxLines, ?string $level = null, ?string $component = null, ?string $search = null): array
    {
        if (!$filePath || !file_exists($filePath)) {
            return [];
        }

        $componentPrefixes = [
            'gateway' => ['gateway', 'hermes_plugins', 'plugins.platforms', 'agent.memory_caller_scope'],
            'agent' => ['agent', 'run_agent', 'model_tools', 'batch_runner'],
            'tools' => ['tools'],
            'cli' => ['hermes_cli', 'cli'],
            'cron' => ['cron'],
            'gui' => ['hermes_cli.web_server', 'hermes_cli.pty_bridge', 'hermes_cli.desktop', 'tui_gateway', 'uvicorn'],
        ];

        $levelHierarchy = [
            'DEBUG' => 10,
            'INFO' => 20,
            'WARNING' => 30,
            'WARN' => 30,
            'ERROR' => 40,
            'CRITICAL' => 50,
            'FATAL' => 50,
        ];

        $minLevelVal = ($level && strtoupper($level) !== 'ALL' && isset($levelHierarchy[strtoupper($level)]))
            ? $levelHierarchy[strtoupper($level)]
            : 0;

        $targetPrefixes = null;
        if ($component && strtolower($component) !== 'all' && isset($componentPrefixes[strtolower($component)])) {
            $targetPrefixes = $componentPrefixes[strtolower($component)];
        }

        // Read last chunk of file for performance
        $lines = [];
        $fp = @fopen($filePath, 'r');
        if (!$fp) {
            return [];
        }

        $buffer = '';
        $chunkSize = 65536; // 64KB
        fseek($fp, 0, SEEK_END);
        $fileSize = ftell($fp);
        $pos = $fileSize;
        $readCount = 0;
        $scanTarget = max($maxLines * 10, 2000);

        while ($pos > 0 && $readCount < $scanTarget) {
            $readSize = min($chunkSize, $pos);
            $pos -= $readSize;
            fseek($fp, $pos);
            $chunk = fread($fp, $readSize);
            $buffer = $chunk . $buffer;

            $tempLines = explode("\n", $buffer);
            if ($pos > 0) {
                // Keep the first partial line in buffer
                $buffer = array_shift($tempLines);
            }
            $readCount = count($tempLines);
        }
        fclose($fp);

        $allLines = explode("\n", $buffer);
        $filtered = [];

        foreach ($allLines as $rawLine) {
            $line = rtrim($rawLine, "\r");
            if ($line === '') continue;

            // Search filter
            if ($search !== null && $search !== '') {
                if (stripos($line, $search) === false) {
                    continue;
                }
            }

            // Component filter
            if ($targetPrefixes !== null) {
                $compMatched = false;
                $lineLower = strtolower($line);
                foreach ($targetPrefixes as $prefix) {
                    if (str_contains($lineLower, $prefix)) {
                        $compMatched = true;
                        break;
                    }
                }
                if (!$compMatched) {
                    continue;
                }
            }

            // Level filter
            if ($minLevelVal > 0) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}[ T][\d:,.]+\s+(DEBUG|INFO|WARNING|WARN|ERROR|CRITICAL|FATAL)\b/i', $line, $m)) {
                    $lvl = strtoupper($m[1]);
                    $lvlVal = $levelHierarchy[$lvl] ?? 20;
                    if ($lvlVal < $minLevelVal) {
                        continue;
                    }
                } elseif (preg_match('/\b(DEBUG|INFO|WARNING|WARN|ERROR|CRITICAL|FATAL)\b/i', $line, $m)) {
                    $lvl = strtoupper($m[1]);
                    $lvlVal = $levelHierarchy[$lvl] ?? 20;
                    if ($lvlVal < $minLevelVal) {
                        continue;
                    }
                }
            }

            $filtered[] = $line;
        }

        return array_slice($filtered, -$maxLines);
    }

    public function logs(Request $request)
    {
        $profile = $request->query('profile', 'default');
        $file = $request->query('file', 'agent');
        $level = $request->query('level', 'ALL');
        $component = $request->query('component', 'ALL');
        $lines = min((int) $request->query('lines', 100), 500);
        $search = $request->query('search', '');

        $filePath = $this->getLogFilePath($file, $profile);
        $logLines = $this->readTailLines($filePath, $lines, $level, $component, $search);

        $profiles = array_merge([['name' => 'default', 'path' => '']], $this->hermes->getProfiles());
        $uniqueProfiles = [];
        foreach ($profiles as $p) {
            $uniqueProfiles[$p['name']] = $p;
        }

        return Inertia::render('Logs/Index', [
            'logs' => $logLines,
            'currentFile' => $file,
            'currentLevel' => $level,
            'currentComponent' => $component,
            'currentLines' => $lines,
            'currentSearch' => $search,
            'currentProfile' => $profile,
            'currentFilePath' => $filePath ?? 'Belum ada log file',
            'profiles' => array_values($uniqueProfiles),
            'logFiles' => ['agent', 'errors', 'gateway'],
            'levels' => ['ALL', 'DEBUG', 'INFO', 'WARNING', 'ERROR'],
            'components' => ['ALL', 'GATEWAY', 'AGENT', 'TOOLS', 'CLI', 'CRON'],
            'lineOptions' => [50, 100, 200, 500],
        ]);
    }

    public function apiLogs(Request $request)
    {
        $profile = $request->query('profile', 'default');
        $file = $request->query('file', 'agent');
        $level = $request->query('level', 'ALL');
        $component = $request->query('component', 'ALL');
        $lines = min((int) $request->query('lines', 100), 500);
        $search = $request->query('search', '');

        $filePath = $this->getLogFilePath($file, $profile);
        $logLines = $this->readTailLines($filePath, $lines, $level, $component, $search);

        return response()->json([
            'file' => $file,
            'profile' => $profile,
            'filePath' => $filePath,
            'lines' => $logLines,
            'count' => count($logLines),
        ]);
    }
}
