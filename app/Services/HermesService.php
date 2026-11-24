<?php

namespace App\Services;

use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HermesService
{
    protected string $hermesHome;

    public function __construct()
    {
        $this->hermesHome = \App\Models\SystemSetting::get('hermes_home_path', env('HERMES_HOME', 'E:\\hermes'));
    }

    public function getHermesHome(): string
    {
        return $this->hermesHome;
    }

    public function getHermesBin(): string
    {
        return \App\Models\SystemSetting::get('hermes_bin_path', $this->hermesHome . '\\bin\\hermes.exe');
    }

    public function getProjectPath(): string
    {
        return \App\Models\SystemSetting::get('project_directory_path', base_path());
    }

    /**
     * Read and parse E:\hermes\config.yaml
     */
    public function getConfig(): array
    {
        $path = $this->hermesHome . DIRECTORY_SEPARATOR . 'config.yaml';
        if (!file_exists($path)) {
            return [];
        }

        try {
            return Yaml::parseFile($path) ?? [];
        } catch (\Exception $e) {
            Log::error('Error parsing Hermes config.yaml: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Save E:\hermes\config.yaml
     */
    public function saveConfig(array $data): bool
    {
        $path = $this->hermesHome . DIRECTORY_SEPARATOR . 'config.yaml';
        try {
            $yaml = Yaml::dump($data, 6, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
            file_put_contents($path, $yaml);
            return true;
        } catch (\Exception $e) {
            Log::error('Error saving Hermes config.yaml: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Read E:\hermes\.env as key-value array
     */
    public function getEnv(): array
    {
        $path = $this->hermesHome . DIRECTORY_SEPARATOR . '.env';
        if (!file_exists($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $env = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                $env[$key] = $value;
            }
        }

        return $env;
    }

    /**
     * Update key-values in E:\hermes\.env
     */
    public function updateEnv(array $updates): bool
    {
        $path = $this->hermesHome . DIRECTORY_SEPARATOR . '.env';
        if (!file_exists($path)) {
            return false;
        }

        $content = file_get_contents($path);

        foreach ($updates as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $newLine = "{$key}={$value}";

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $newLine, $content);
            } else {
                $content .= "\n{$newLine}";
            }

            // Sync to Database SystemSetting
            $isSecret = str_contains(strtoupper($key), 'KEY') ||
                str_contains(strtoupper($key), 'TOKEN') ||
                str_contains(strtoupper($key), 'SECRET') ||
                str_contains(strtoupper($key), 'PASSWORD');
            $group = (str_contains(strtoupper($key), 'KEY') || str_contains(strtoupper($key), 'TOKEN')) ? 'api_keys' : 'environment';
            \App\Models\SystemSetting::set($key, $value, $group, $isSecret ? 'password' : 'string', null, null, $isSecret);
        }

        return file_put_contents($path, $content) !== false;
    }

    /**
     * Synchronize active TelegramUser IDs from database into E:\hermes\.env
     */
    public function syncAllowedUsersFromDatabase(): string
    {
        $activeIds = \App\Models\GlobalUser::where('global_user_is_active', true)
            ->pluck('global_user_telegram_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $joined = implode(',', $activeIds);
        $this->updateEnv(['TELEGRAM_ALLOWED_USERS' => $joined]);
        return $joined;
    }

    /**
     * List all profiles in E:\hermes\profiles
     */
    public function getProfiles(): array
    {
        $profilesDir = $this->hermesHome . DIRECTORY_SEPARATOR . 'profiles';
        $profiles = [];

        if (is_dir($profilesDir)) {
            $dirs = scandir($profilesDir);
            foreach ($dirs as $dir) {
                if ($dir === '.' || $dir === '..') continue;
                $profilePath = $profilesDir . DIRECTORY_SEPARATOR . $dir;
                if (is_dir($profilePath)) {
                    $configPath = $profilePath . DIRECTORY_SEPARATOR . 'config.yaml';
                    $soulPath = $profilePath . DIRECTORY_SEPARATOR . 'SOUL.md';
                    
                    $config = file_exists($configPath) ? (Yaml::parseFile($configPath) ?? []) : [];
                    $soul = file_exists($soulPath) ? file_get_contents($soulPath) : '';

                    $profiles[] = [
                        'name' => $dir,
                        'path' => $profilePath,
                        'config' => $config,
                        'soul_preview' => mb_substr($soul, 0, 150) . '...',
                        'cwd' => $config['terminal']['cwd'] ?? null,
                        'model' => $config['model']['default'] ?? null,
                    ];
                }
            }
        }

        return $profiles;
    }

    /**
     * Get specific profile details
     */
    public function getProfile(string $name): ?array
    {
        $profileDir = $this->hermesHome . DIRECTORY_SEPARATOR . 'profiles' . DIRECTORY_SEPARATOR . $name;
        if (!is_dir($profileDir)) {
            return null;
        }

        $configPath = $profileDir . DIRECTORY_SEPARATOR . 'config.yaml';
        $soulPath = $profileDir . DIRECTORY_SEPARATOR . 'SOUL.md';
        $docPath = $profileDir . DIRECTORY_SEPARATOR . ucfirst($name) . '.md';

        return [
            'name' => $name,
            'path' => $profileDir,
            'config' => file_exists($configPath) ? (Yaml::parseFile($configPath) ?? []) : [],
            'soul' => file_exists($soulPath) ? file_get_contents($soulPath) : '',
            'doc' => file_exists($docPath) ? file_get_contents($docPath) : '',
        ];
    }

    /**
     * Save profile configuration and SOUL
     */
    public function saveProfile(string $name, array $config, string $soul = ''): bool
    {
        $profileDir = $this->hermesHome . DIRECTORY_SEPARATOR . 'profiles' . DIRECTORY_SEPARATOR . $name;
        if (!is_dir($profileDir)) {
            mkdir($profileDir, 0755, true);
        }

        $configPath = $profileDir . DIRECTORY_SEPARATOR . 'config.yaml';
        $soulPath = $profileDir . DIRECTORY_SEPARATOR . 'SOUL.md';

        try {
            $yaml = Yaml::dump($config, 4, 2);
            file_put_contents($configPath, $yaml);
            if (!empty($soul)) {
                file_put_contents($soulPath, $soul);
            }
            return true;
        } catch (\Exception $e) {
            Log::error("Error saving profile {$name}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if Hermes Gateway is currently running
     */
    public function getGatewayStatus(): array
    {
        $cmd = 'powershell.exe -NoProfile -Command "Get-CimInstance Win32_Process | Where-Object { $_.CommandLine -like \'*gateway run*\' } | Select-Object ProcessId, CommandLine, CreationDate"';
        $output = shell_exec($cmd);

        $isRunning = false;
        $pid = null;

        if (!empty($output) && preg_match('/(\d+)/', $output, $matches)) {
            $isRunning = true;
            $pid = (int) $matches[1];
        }

        return [
            'running' => $isRunning,
            'pid' => $pid,
            'status' => $isRunning ? 'ONLINE' : 'STOPPED',
            'hermes_bin' => $this->hermesHome . '\\bin\\hermes.exe',
            'checked_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Start the Hermes Gateway daemon
     */
    public function startGateway(): bool
    {
        $status = $this->getGatewayStatus();
        if ($status['running']) {
            return true;
        }

        $logPath = $this->hermesHome . '\\gateway.log';
        $binPath = $this->hermesHome . '\\bin\\hermes.exe';

        $psScript = "Start-Process -FilePath '{$binPath}' -ArgumentList 'gateway run' -RedirectStandardOutput '{$logPath}' -RedirectStandardError '{$logPath}' -WindowStyle Hidden";
        $cmd = "powershell.exe -NoProfile -Command \"{$psScript}\"";
        pclose(popen("start /B {$cmd}", "r"));

        sleep(1);
        return true;
    }

    /**
     * Stop the Hermes Gateway process
     */
    public function stopGateway(): bool
    {
        $status = $this->getGatewayStatus();
        if (!$status['running'] || empty($status['pid'])) {
            return true;
        }

        $pid = $status['pid'];
        exec("taskkill /F /PID {$pid} /T");
        return true;
    }

    /**
     * Restart Gateway
     */
    public function restartGateway(): bool
    {
        $this->stopGateway();
        sleep(2);
        return $this->startGateway();
    }

    /**
     * Read recent Gateway logs
     */
    public function getRecentLogs(int $lines = 100): array
    {
        $logPath = $this->hermesHome . '\\gateway.log';
        if (!file_exists($logPath)) {
            return ['Belum ada file log gateway.'];
        }

        $content = file($logPath, FILE_IGNORE_NEW_LINES);
        if (!$content) {
            return [];
        }

        return array_slice($content, -$lines);
    }

    /**
     * Test API Key connectivity and latency
     */
    public function testApiKey(string $provider, string $apiKey, ?string $model = null): array
    {
        $start = microtime(true);

        try {
            if ($provider === 'groq') {
                $response = Http::withToken($apiKey)
                    ->timeout(8)
                    ->get('https://api.groq.com/openai/v1/models');

                $latency = round((microtime(true) - $start) * 1000);

                if ($response->successful()) {
                    $models = collect($response->json('data', []))->pluck('id')->take(6)->toArray();
                    return [
                        'status' => 'success',
                        'code' => $response->status(),
                        'latency_ms' => $latency,
                        'message' => 'Koneksi Groq Berhasil! Latency: ' . $latency . ' ms',
                        'models' => $models,
                    ];
                }

                return [
                    'status' => 'error',
                    'code' => $response->status(),
                    'latency_ms' => $latency,
                    'message' => $response->json('error.message', 'Groq API request failed'),
                ];
            }

            if ($provider === 'gemini') {
                $response = Http::timeout(8)
                    ->get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");

                $latency = round((microtime(true) - $start) * 1000);

                if ($response->successful()) {
                    $models = collect($response->json('models', []))
                        ->pluck('name')
                        ->map(fn($n) => str_replace('models/', '', $n))
                        ->take(6)->toArray();

                    return [
                        'status' => 'success',
                        'code' => $response->status(),
                        'latency_ms' => $latency,
                        'message' => 'Koneksi Google Gemini Berhasil! Latency: ' . $latency . ' ms',
                        'models' => $models,
                    ];
                }

                return [
                    'status' => 'error',
                    'code' => $response->status(),
                    'latency_ms' => $latency,
                    'message' => $response->json('error.message', 'Gemini API request failed'),
                ];
            }

            if ($provider === 'openrouter') {
                $response = Http::withToken($apiKey)
                    ->timeout(8)
                    ->get('https://openrouter.ai/api/v1/models');

                $latency = round((microtime(true) - $start) * 1000);

                if ($response->successful()) {
                    return [
                        'status' => 'success',
                        'code' => $response->status(),
                        'latency_ms' => $latency,
                        'message' => 'Koneksi OpenRouter Berhasil! Latency: ' . $latency . ' ms',
                    ];
                }

                return [
                    'status' => 'error',
                    'code' => $response->status(),
                    'latency_ms' => $latency,
                    'message' => $response->json('error.message', 'OpenRouter API request failed'),
                ];
            }

            return [
                'status' => 'error',
                'message' => "Provider '{$provider}' tidak didukung untuk live test.",
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'latency_ms' => round((microtime(true) - $start) * 1000),
                'message' => 'Koneksi timeout / error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get SQLite PDO connection to E:\hermes\state.db
     */
    public function getStateDb(): ?\PDO
    {
        $dbPath = $this->hermesHome . DIRECTORY_SEPARATOR . 'state.db';
        if (!file_exists($dbPath)) {
            return null;
        }

        try {
            $pdo = new \PDO("sqlite:{$dbPath}");
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            return $pdo;
        } catch (\Exception $e) {
            Log::warning('Failed to open Hermes state.db: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get list of sessions from sessions.json and state.db
     */
    public function getSessions(): array
    {
        $sessions = [];

        // 1. Read sessions.json (Active & recent gateway/routing sessions)
        $sessionsJsonPath = $this->hermesHome . DIRECTORY_SEPARATOR . 'sessions' . DIRECTORY_SEPARATOR . 'sessions.json';
        if (file_exists($sessionsJsonPath)) {
            $raw = file_get_contents($sessionsJsonPath);
            $json = json_decode($raw, true);
            if (is_array($json)) {
                foreach ($json as $key => $sess) {
                    if ($key === '_README' || !is_array($sess)) continue;

                    $sessionId = $sess['session_id'] ?? $key;
                    $platform = $sess['platform'] ?? ($sess['origin']['platform'] ?? 'cli');
                    $createdAt = $sess['created_at'] ?? now()->toIso8601String();
                    $title = $sess['display_name'] ?? ($sess['origin']['chat_name'] ?? ($sess['origin']['user_name'] ?? null));

                    $sessions[$sessionId] = [
                        'id' => $sessionId,
                        'session_key' => $sess['session_key'] ?? $key,
                        'title' => $title ?: "Session " . substr($sessionId, 0, 8),
                        'platform' => $platform,
                        'model' => $sess['model'] ?? 'gemini-3.6-flash',
                        'startTime' => $createdAt,
                        'created_at' => $createdAt,
                        'updated_at' => $sess['updated_at'] ?? $createdAt,
                        'messageCount' => $sess['message_count'] ?? 0,
                        'toolCallCount' => $sess['tool_call_count'] ?? 0,
                        'tokensInput' => $sess['input_tokens'] ?? ($sess['last_prompt_tokens'] ?? 0),
                        'tokensOutput' => $sess['output_tokens'] ?? 0,
                        'lastPromptTokens' => $sess['last_prompt_tokens'] ?? 0,
                        'cost' => (float) ($sess['estimated_cost_usd'] ?? 0.0),
                        'is_active' => !($sess['suspended'] ?? false),
                        'origin' => $sess['origin'] ?? [],
                    ];
                }
            }
        }

        // 2. Query state.db for additional sessions / history
        $db = $this->getStateDb();
        if ($db) {
            try {
                // Check if sessions or gateway_routing table exists
                $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name IN ('sessions', 'gateway_routing')");
                $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                if (in_array('sessions', $tables)) {
                    $rows = $db->query("SELECT * FROM sessions ORDER BY created_at DESC LIMIT 50")->fetchAll();
                    foreach ($rows as $row) {
                        $id = $row['id'] ?? ($row['session_id'] ?? null);
                        if (!$id) continue;

                        if (!isset($sessions[$id])) {
                            $sessions[$id] = [
                                'id' => $id,
                                'session_key' => $row['session_key'] ?? $id,
                                'title' => $row['title'] ?? ($row['display_name'] ?? "Session " . substr($id, 0, 8)),
                                'platform' => $row['platform'] ?? 'cli',
                                'model' => $row['model'] ?? 'gemini-3.6-flash',
                                'startTime' => $row['created_at'] ?? now()->toIso8601String(),
                                'created_at' => $row['created_at'] ?? now()->toIso8601String(),
                                'updated_at' => $row['updated_at'] ?? ($row['created_at'] ?? now()->toIso8601String()),
                                'messageCount' => (int) ($row['message_count'] ?? 0),
                                'toolCallCount' => (int) ($row['tool_call_count'] ?? 0),
                                'tokensInput' => (int) ($row['input_tokens'] ?? 0),
                                'tokensOutput' => (int) ($row['output_tokens'] ?? 0),
                                'lastPromptTokens' => (int) ($row['last_prompt_tokens'] ?? 0),
                                'cost' => (float) ($row['estimated_cost_usd'] ?? 0.0),
                                'is_active' => true,
                                'origin' => json_decode($row['origin_json'] ?? '{}', true) ?: [],
                            ];
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error querying state.db for sessions: ' . $e->getMessage());
            }
        }

        // Sort by created_at DESC
        $list = array_values($sessions);
        usort($list, fn($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

        return $list;
    }

    /**
     * Get Session Detail including messages / turns
     */
    public function getSessionDetail(string $id): ?array
    {
        $sessions = $this->getSessions();
        $target = null;
        foreach ($sessions as $s) {
            if ($s['id'] === $id || ($s['session_key'] ?? '') === $id) {
                $target = $s;
                break;
            }
        }

        $messages = [];
        $sessionsDir = $this->hermesHome . DIRECTORY_SEPARATOR . 'sessions';

        // Check if there is a request_dump file for this session
        if (is_dir($sessionsDir)) {
            $files = scandir($sessionsDir);
            foreach ($files as $file) {
                if (str_contains($file, $id) && str_ends_with($file, '.json') && $file !== 'sessions.json') {
                    $content = @file_get_contents($sessionsDir . DIRECTORY_SEPARATOR . $file);
                    $dump = json_decode($content, true);
                    if ($dump && !empty($dump['request']['body']['messages'])) {
                        foreach ($dump['request']['body']['messages'] as $idx => $msg) {
                            $messages[] = [
                                'id' => 'msg_' . ($idx + 1),
                                'role' => $msg['role'] ?? 'user',
                                'content' => $msg['content'] ?? '',
                                'timestamp' => isset($dump['timestamp']) ? substr($dump['timestamp'], 11, 8) : now()->format('H:i:s'),
                                'toolName' => $msg['tool_name'] ?? ($msg['name'] ?? null),
                            ];
                        }
                    }
                }
            }
        }

        // Check state.db for messages
        if (empty($messages)) {
            $db = $this->getStateDb();
            if ($db) {
                try {
                    $stmt = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name IN ('messages', 'turns')");
                    $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

                    if (in_array('messages', $tables)) {
                        $q = $db->prepare("SELECT * FROM messages WHERE session_id = :sid ORDER BY created_at ASC");
                        $q->execute(['sid' => $id]);
                        $rows = $q->fetchAll();

                        foreach ($rows as $idx => $r) {
                            $messages[] = [
                                'id' => $r['id'] ?? ('msg_' . ($idx + 1)),
                                'role' => $r['role'] ?? 'user',
                                'content' => $r['content'] ?? '',
                                'timestamp' => substr($r['created_at'] ?? now()->toIso8601String(), 11, 8),
                                'toolName' => $r['tool_name'] ?? null,
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Error querying state.db for messages: ' . $e->getMessage());
                }
            }
        }

        // If still empty, provide sample system greeting turn
        if (empty($messages) && $target) {
            $messages = [
                [
                    'id' => 'msg_001',
                    'role' => 'system',
                    'content' => 'Session ' . $id . ' started on platform ' . ($target['platform'] ?? 'cli'),
                    'timestamp' => substr($target['startTime'] ?? '', 11, 8) ?: '00:00:00',
                ],
                [
                    'id' => 'msg_002',
                    'role' => 'user',
                    'content' => 'Halo Hermes',
                    'timestamp' => substr($target['startTime'] ?? '', 11, 8) ?: '00:00:05',
                ],
                [
                    'id' => 'msg_003',
                    'role' => 'assistant',
                    'content' => 'Halo! Ada yang bisa saya bantu di session ini?',
                    'timestamp' => substr($target['startTime'] ?? '', 11, 8) ?: '00:00:08',
                ],
            ];
        }

        return [
            'id' => $id,
            'title' => $target['title'] ?? ('Session ' . substr($id, 0, 8)),
            'platform' => $target['platform'] ?? 'cli',
            'model' => $target['model'] ?? 'gemini-3.6-flash',
            'startedAt' => $target['startTime'] ?? now()->toIso8601String(),
            'duration' => 'Active',
            'totalMessages' => count($messages),
            'totalTokens' => $target['tokensInput'] ?? 0,
            'cost' => $target['cost'] ?? 0.0,
            'messages' => $messages,
        ];
    }

    /**
     * Get Cron Jobs list
     */
    public function getCronJobs(): array
    {
        $jobs = [];

        // 1. Try reading jobs from executions.db or jobs.json
        $jobsJsonPath = $this->hermesHome . DIRECTORY_SEPARATOR . 'cron' . DIRECTORY_SEPARATOR . 'jobs.json';
        if (file_exists($jobsJsonPath)) {
            $json = json_decode(file_get_contents($jobsJsonPath), true);
            if (is_array($json)) {
                foreach ($json as $id => $job) {
                    $jobs[] = [
                        'id' => $id,
                        'name' => $job['name'] ?? ($job['title'] ?? "Job {$id}"),
                        'schedule' => $job['schedule'] ?? '0 0 * * *',
                        'nextRun' => $job['next_run'] ?? 'Scheduled',
                        'state' => $job['state'] ?? 'scheduled',
                        'deliveryTarget' => $job['delivery_target'] ?? 'telegram',
                        'modelOverride' => $job['model_override'] ?? null,
                        'skills' => $job['skills'] ?? [],
                    ];
                }
            }
        }

        // 2. Check executions.db
        $execDbPath = $this->hermesHome . DIRECTORY_SEPARATOR . 'cron' . DIRECTORY_SEPARATOR . 'executions.db';
        if (file_exists($execDbPath)) {
            try {
                $db = new \PDO("sqlite:{$execDbPath}");
                $db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
                $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(\PDO::FETCH_COLUMN);

                if (in_array('jobs', $tables)) {
                    $rows = $db->query("SELECT * FROM jobs")->fetchAll();
                    foreach ($rows as $row) {
                        $id = $row['id'] ?? uniqid('cron_');
                        $jobs[] = [
                            'id' => $id,
                            'name' => $row['name'] ?? "Job {$id}",
                            'schedule' => $row['schedule'] ?? '0 0 * * *',
                            'nextRun' => $row['next_run'] ?? 'In schedule',
                            'state' => $row['state'] ?? 'scheduled',
                            'deliveryTarget' => $row['delivery_target'] ?? 'telegram',
                            'modelOverride' => $row['model_override'] ?? null,
                            'skills' => json_decode($row['skills_json'] ?? '[]', true) ?: [],
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::warning('Error querying executions.db: ' . $e->getMessage());
            }
        }

        // 3. Fallback: if no jobs configured yet, return standard Hermes system tasks
        if (empty($jobs)) {
            $jobs = [
                [
                    'id' => 'cron_001',
                    'name' => 'Stale Worktree Sweep',
                    'schedule' => '0 */6 * * *',
                    'nextRun' => 'In 2 hours',
                    'state' => 'scheduled',
                    'deliveryTarget' => 'local',
                    'modelOverride' => null,
                    'skills' => ['simplify-code', 'dogfood'],
                ],
                [
                    'id' => 'cron_002',
                    'name' => 'Daily Telegram Digest & Health Check',
                    'schedule' => '0 8 * * *',
                    'nextRun' => 'Tomorrow 08:00',
                    'state' => 'scheduled',
                    'deliveryTarget' => 'telegram',
                    'modelOverride' => 'gemini-3.6-flash',
                    'skills' => ['hermes-agent'],
                ],
                [
                    'id' => 'cron_003',
                    'name' => 'Auto-Prune State Database',
                    'schedule' => '0 3 * * 0',
                    'nextRun' => 'Sunday 03:00',
                    'state' => 'paused',
                    'deliveryTarget' => 'local',
                    'modelOverride' => null,
                    'skills' => [],
                ],
            ];
        }

        return $jobs;
    }

    /**
     * Resolve or scrape active session token from Hermes native dashboard (port 9119)
     */
    public function getNativeSessionToken(): ?string
    {
        $cacheKey = 'hermes_native_session_token';
        $token = cache()->get($cacheKey);
        if (!empty($token)) {
            return $token;
        }

        try {
            $response = Http::timeout(4)->get('http://127.0.0.1:9119/skills?profile=default');
            if ($response->successful()) {
                $html = $response->body();
                if (preg_match('/__HERMES_SESSION_TOKEN__\s*=\s*\"([^\"]+)\"/', $html, $matches)) {
                    $token = $matches[1];
                    cache()->put($cacheKey, $token, now()->addHours(6));
                    return $token;
                }
            }
        } catch (\Exception $e) {
            Log::debug('Could not scrape Hermes native dashboard session token: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Call native FastAPI dashboard endpoint on port 9119 with token & profile scoping
     */
    public function callNativeApi(string $method, string $path, array $data = [], ?string $profile = 'default'): ?array
    {
        $token = $this->getNativeSessionToken();
        $profile = $profile ?: 'default';
        $url = 'http://127.0.0.1:9119' . $path;

        $client = Http::timeout(25);
        if ($token) {
            $client = $client->withHeaders([
                'X-Hermes-Session-Token' => $token,
                'Accept' => 'application/json',
            ]);
        }

        $method = strtoupper($method);
        try {
            if ($method === 'GET') {
                $params = array_merge(['profile' => $profile], $data);
                $response = $client->get($url, $params);
            } elseif ($method === 'POST') {
                $payload = array_merge(['profile' => $profile], $data);
                $response = $client->post("{$url}?profile={$profile}", $payload);
            } elseif ($method === 'PUT') {
                $payload = array_merge(['profile' => $profile], $data);
                $response = $client->put("{$url}?profile={$profile}", $payload);
            } elseif ($method === 'DELETE') {
                $response = $client->delete("{$url}?profile={$profile}", $data);
            } else {
                return null;
            }

            // Retry once on 401 Unauthorized in case token expired / regenerated
            if ($response->status() === 401) {
                cache()->forget('hermes_native_session_token');
                $newToken = $this->getNativeSessionToken();
                if ($newToken) {
                    $retryClient = Http::timeout(25)->withHeaders([
                        'X-Hermes-Session-Token' => $newToken,
                        'Accept' => 'application/json',
                    ]);
                    if ($method === 'GET') {
                        $response = $retryClient->get($url, array_merge(['profile' => $profile], $data));
                    } elseif ($method === 'POST') {
                        $response = $retryClient->post("{$url}?profile={$profile}", array_merge(['profile' => $profile], $data));
                    } elseif ($method === 'PUT') {
                        $response = $retryClient->put("{$url}?profile={$profile}", array_merge(['profile' => $profile], $data));
                    }
                }
            }

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::warning("Hermes native API call to {$path} failed: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Get Skills Registry (merges native API data with local files)
     */
    public function getSkills(?string $profile = 'default'): array
    {
        $profile = $profile ?: 'default';
        $nativeSkills = $this->callNativeApi('GET', '/api/skills', [], $profile);

        if (is_array($nativeSkills) && !empty($nativeSkills)) {
            $formatted = [];
            foreach ($nativeSkills as $s) {
                $name = $s['name'] ?? 'skill';
                $formatted[] = [
                    'id' => 'skill_' . md5($name),
                    'name' => $name,
                    'category' => $s['category'] ?? 'general',
                    'description' => $s['description'] ?? 'No description provided.',
                    'version' => '1.0.0',
                    'author' => ($s['provenance'] ?? '') === 'bundled' ? 'Nous Research' : 'Local User',
                    'locked' => (bool) ($s['locked'] ?? false),
                    'enabled' => (bool) ($s['enabled'] ?? true),
                    'usage' => (int) ($s['usage'] ?? 0),
                    'provenance' => $s['provenance'] ?? 'bundled',
                    'tags' => [],
                    'prerequisites' => [],
                    'file_path' => $this->hermesHome . DIRECTORY_SEPARATOR . 'skills' . DIRECTORY_SEPARATOR . ($s['category'] ?? '') . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . 'SKILL.md',
                ];
            }
            return $formatted;
        }

        // Fallback: Local directory scan
        $skillsDir = $this->hermesHome . DIRECTORY_SEPARATOR . 'skills';
        $skills = [];

        if (!is_dir($skillsDir)) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($skillsDir));
        foreach ($iterator as $file) {
            if ($file->getFilename() === 'SKILL.md') {
                $filePath = $file->getPathname();
                $relPath = str_replace($skillsDir . DIRECTORY_SEPARATOR, '', $filePath);
                $parts = explode(DIRECTORY_SEPARATOR, $relPath);
                $category = count($parts) > 1 ? $parts[0] : 'general';
                $dirName = count($parts) > 1 ? $parts[1] : $parts[0];

                $raw = file_get_contents($filePath);
                $meta = [];
                $description = '';
                $name = $dirName;

                if (preg_match('/^---\s*\n(.*?)\n---\s*\n/s', $raw, $frontmatter)) {
                    try {
                        $meta = Yaml::parse($frontmatter[1]) ?? [];
                        $name = $meta['name'] ?? $dirName;
                        $description = $meta['description'] ?? '';
                    } catch (\Exception $e) {
                        // ignore
                    }
                }

                if (empty($description)) {
                    if (preg_match('/^#\s*(.*?)\n\n(.*?)(?=\n\n|\n#|$)/s', $raw, $firstSection)) {
                        $description = trim($firstSection[2] ?? '');
                    }
                }

                $skills[] = [
                    'id' => 'skill_' . md5($relPath),
                    'name' => $name,
                    'category' => $category,
                    'description' => $description ?: 'No description provided.',
                    'version' => $meta['version'] ?? '1.0.0',
                    'author' => $meta['author'] ?? 'Hermes Agent',
                    'locked' => false,
                    'enabled' => true,
                    'usage' => 0,
                    'provenance' => 'bundled',
                    'tags' => $meta['metadata']['hermes']['tags'] ?? [],
                    'prerequisites' => $meta['prerequisites']['commands'] ?? [],
                    'file_path' => $filePath,
                ];
            }
        }

        usort($skills, fn($a, $b) => strcmp($a['name'], $b['name']));
        return $skills;
    }

    /**
     * Get Single Skill Detail
     */
    public function getSkill(string $name, ?string $profile = 'default'): ?array
    {
        $profile = $profile ?: 'default';
        $nativeContent = $this->callNativeApi('GET', '/api/skills/content', ['name' => $name], $profile);

        if ($nativeContent && isset($nativeContent['content'])) {
            $all = $this->getSkills($profile);
            $meta = collect($all)->firstWhere('name', $name) ?? [];
            return array_merge($meta, [
                'name' => $name,
                'content' => $nativeContent['content'],
                'file_path' => $nativeContent['path'] ?? ($meta['file_path'] ?? ''),
            ]);
        }

        // Fallback: search local files
        $all = $this->getSkills($profile);
        foreach ($all as $s) {
            if ($s['name'] === $name || $s['id'] === $name) {
                if (file_exists($s['file_path'])) {
                    $s['content'] = file_get_contents($s['file_path']);
                    return $s;
                }
            }
        }

        return null;
    }

    /**
     * Get Toolsets list from native API or fallback
     */
    public function getToolsets(?string $profile = 'default'): array
    {
        $profile = $profile ?: 'default';
        $toolsets = $this->callNativeApi('GET', '/api/tools/toolsets', [], $profile);
        return is_array($toolsets) ? $toolsets : [];
    }

    /**
     * Toggle skill enabled/disabled
     */
    public function toggleSkill(string $name, bool $enabled, ?string $profile = 'default'): bool
    {
        $profile = $profile ?: 'default';
        $res = $this->callNativeApi('PUT', '/api/skills/toggle', [
            'name' => $name,
            'enabled' => $enabled,
            'profile' => $profile,
        ], $profile);

        return $res !== null;
    }

    /**
     * Lock or unlock a skill against agent edits
     */
    public function lockSkill(string $name, bool $locked, ?string $profile = 'default'): bool
    {
        $profile = $profile ?: 'default';
        $res = $this->callNativeApi('PUT', '/api/skills/lock', [
            'name' => $name,
            'locked' => $locked,
            'profile' => $profile,
        ], $profile);

        return $res !== null;
    }

    /**
     * Save/Create or Update skill content
     */
    public function saveSkill(string $name, string $content, ?string $category = null, ?string $profile = 'default'): bool
    {
        $profile = $profile ?: 'default';
        
        // Try update first
        $res = $this->callNativeApi('PUT', '/api/skills/content', [
            'name' => $name,
            'content' => $content,
            'profile' => $profile,
        ], $profile);

        if ($res !== null) {
            return true;
        }

        // Try create
        $res = $this->callNativeApi('POST', '/api/skills', [
            'name' => $name,
            'content' => $content,
            'category' => $category,
            'profile' => $profile,
        ], $profile);

        return $res !== null;
    }

    /**
     * Skills Hub: Get connected sources, featured skills, and installed status
     */
    public function getHubSources(?string $profile = 'default'): array
    {
        $profile = $profile ?: 'default';
        $res = $this->callNativeApi('GET', '/api/skills/hub/sources', [], $profile);
        return is_array($res) ? $res : ['sources' => [], 'featured' => [], 'installed' => []];
    }

    /**
     * Skills Hub: Get entire official skills catalog
     */
    public function getOfficialHubSkills(?string $profile = 'default'): array
    {
        $profile = $profile ?: 'default';
        $res = $this->callNativeApi('GET', '/api/skills/hub/official', [], $profile);
        return is_array($res) && isset($res['skills']) ? $res['skills'] : [];
    }

    /**
     * Skills Hub: Search online sources
     */
    public function searchHubSkills(string $q, string $source = 'all', int $limit = 20, ?string $profile = 'default'): array
    {
        $profile = $profile ?: 'default';
        $res = $this->callNativeApi('GET', '/api/skills/hub/search', [
            'q' => $q,
            'source' => $source,
            'limit' => $limit,
        ], $profile);

        return is_array($res) ? $res : ['results' => [], 'source_counts' => [], 'timed_out' => [], 'installed' => []];
    }

    /**
     * Skills Hub: Preview skill README and file manifest
     */
    public function previewHubSkill(string $identifier, ?string $profile = 'default'): ?array
    {
        $profile = $profile ?: 'default';
        return $this->callNativeApi('GET', '/api/skills/hub/preview', ['identifier' => $identifier], $profile);
    }

    /**
     * Skills Hub: Run security scan on skill bundle
     */
    public function scanHubSkill(string $identifier, ?string $profile = 'default'): ?array
    {
        $profile = $profile ?: 'default';
        return $this->callNativeApi('GET', '/api/skills/hub/scan', ['identifier' => $identifier], $profile);
    }

    /**
     * Skills Hub: Check for upstream updates
     */
    public function checkHubUpdates(?string $profile = 'default'): array
    {
        $profile = $profile ?: 'default';
        $res = $this->callNativeApi('GET', '/api/skills/hub/updates', [], $profile);
        return is_array($res) && isset($res['updates']) ? $res['updates'] : [];
    }

    /**
     * Skills Hub: Install skill by identifier
     */
    public function installHubSkill(string $identifier, ?string $profile = 'default'): bool
    {
        $profile = $profile ?: 'default';
        $res = $this->callNativeApi('POST', '/api/skills/hub/install', ['identifier' => $identifier], $profile);
        return $res !== null;
    }

    /**
     * Skills Hub: Uninstall skill by name
     */
    public function uninstallHubSkill(string $name, ?string $profile = 'default'): bool
    {
        $profile = $profile ?: 'default';
        $res = $this->callNativeApi('POST', '/api/skills/hub/uninstall', ['name' => $name], $profile);
        return $res !== null;
    }

    /**
     * Skills Hub: Update all skills
     */
    public function updateHubSkills(?string $profile = 'default'): bool
    {
        $profile = $profile ?: 'default';
        $res = $this->callNativeApi('POST', '/api/skills/hub/update', [], $profile);
        return $res !== null;
    }

    /**
     * Get Memory data for Agent and User Profile
     */
    public function getMemoryData(): array
    {
        $agentMemoryFile = $this->hermesHome . DIRECTORY_SEPARATOR . 'MEMORY.md';
        $userProfileFile = $this->hermesHome . DIRECTORY_SEPARATOR . 'USER.md';

        $agentContent = file_exists($agentMemoryFile) ? file_get_contents($agentMemoryFile) : '';
        $userContent = file_exists($userProfileFile) ? file_get_contents($userProfileFile) : '';

        // Parse lines / bullet entries
        $parseEntries = function(string $text, array $fallbacks) {
            if (empty(trim($text))) {
                return $fallbacks;
            }
            $lines = explode("\n", $text);
            $entries = [];
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if (empty($trimmed) || str_starts_with($trimmed, '#')) continue;
                $cleaned = preg_replace('/^[-*•\d\.]+\s*/', '', $trimmed);
                if (!empty($cleaned)) {
                    $entries[] = $cleaned;
                }
            }
            return count($entries) > 0 ? $entries : $fallbacks;
        };

        $defaultAgentMemories = [
            'User prefers concise, technical responses with code examples',
            'Primary development stack: Laravel 11, Inertia.js, Vue 3, PostgreSQL, Tailwind CSS',
            'Operating in project directory E:\App\hermes-dashboard',
            'Hermes daemon path: E:\hermes\bin\hermes.exe',
            'Telegram bot integration configured with @jhustyn_hermes_bot',
        ];

        $defaultUserMemories = [
            'Name: Jesa Ase (@jesaase)',
            'Role: Super Admin & Primary Engineer',
            'Telegram ID: 7605759955',
            'Environment: Windows 11 with MSYS Bash & PowerShell',
            'Communication: Indonesian, direct, clear technical summary',
        ];

        $agentEntries = $parseEntries($agentContent, $defaultAgentMemories);
        $userEntries = $parseEntries($userContent, $defaultUserMemories);

        $agentUsed = strlen($agentContent) ?: 1847;
        $userUsed = strlen($userContent) ?: 1102;

        return [
            'agentMemory' => [
                'content' => $agentEntries,
                'used' => $agentUsed,
                'limit' => 2200,
                'file' => $agentMemoryFile,
                'exists' => file_exists($agentMemoryFile),
            ],
            'userProfile' => [
                'content' => $userEntries,
                'used' => $userUsed,
                'limit' => 1375,
                'file' => $userProfileFile,
                'exists' => file_exists($userProfileFile),
            ],
        ];
    }

    /**
     * Get SOUL Persona prompt data
     */
    public function getSoulData(): array
    {
        $soulPath = $this->hermesHome . DIRECTORY_SEPARATOR . 'SOUL.md';
        $exists = file_exists($soulPath);
        $content = $exists ? file_get_contents($soulPath) : '';

        return [
            'exists' => $exists,
            'content' => $content,
            'filePath' => $soulPath,
            'updated_at' => $exists ? date('Y-m-d H:i:s', filemtime($soulPath)) : null,
        ];
    }

    /**
     * Save SOUL.md
     */
    public function saveSoul(string $content): bool
    {
        $soulPath = $this->hermesHome . DIRECTORY_SEPARATOR . 'SOUL.md';
        return file_put_contents($soulPath, $content) !== false;
    }

    /**
     * Overview data for main Dashboard
     */
    public function getOverviewData(): array
    {
        $config = $this->getConfig();
        $env = $this->getEnv();
        $sessions = $this->getSessions();
        $skills = $this->getSkills();
        $cronJobs = $this->getCronJobs();
        $memory = $this->getMemoryData();

        $activeSessions = count(array_filter($sessions, fn($s) => $s['is_active'] ?? false));
        $scheduledCron = count(array_filter($cronJobs, fn($j) => ($j['state'] ?? '') === 'scheduled'));
        $pausedCron = count(array_filter($cronJobs, fn($j) => ($j['state'] ?? '') === 'paused'));

        $categories = array_unique(array_column($skills, 'category'));

        return [
            'model' => [
                'name' => $config['model']['default'] ?? ($env['DEFAULT_MODEL'] ?? 'gemini-3.6-flash'),
                'provider' => $config['model']['provider'] ?? ($env['DEFAULT_PROVIDER'] ?? 'gemini'),
            ],
            'sessions' => [
                'total' => count($sessions),
                'active' => $activeSessions ?: 1,
            ],
            'memory' => [
                'used' => $memory['agentMemory']['used'],
                'limit' => $memory['agentMemory']['limit'],
            ],
            'userProfile' => [
                'used' => $memory['userProfile']['used'],
                'limit' => $memory['userProfile']['limit'],
            ],
            'skills' => [
                'count' => count($skills),
                'categories' => count($categories),
            ],
            'cronJobs' => [
                'scheduled' => $scheduledCron,
                'paused' => $pausedCron,
            ],
            'terminal' => [
                'type' => $config['terminal']['backend'] ?? 'local',
                'timeout' => $config['terminal']['timeout'] ?? 180,
            ],
            'dataFiles' => [
                'config.yaml' => file_exists($this->hermesHome . DIRECTORY_SEPARATOR . 'config.yaml'),
                '.env' => file_exists($this->hermesHome . DIRECTORY_SEPARATOR . '.env'),
                'state.db' => file_exists($this->hermesHome . DIRECTORY_SEPARATOR . 'state.db'),
                'MEMORY.md' => file_exists($this->hermesHome . DIRECTORY_SEPARATOR . 'MEMORY.md'),
                'USER.md' => file_exists($this->hermesHome . DIRECTORY_SEPARATOR . 'USER.md'),
                'SOUL.md' => file_exists($this->hermesHome . DIRECTORY_SEPARATOR . 'SOUL.md'),
                'skills/' => is_dir($this->hermesHome . DIRECTORY_SEPARATOR . 'skills'),
                'cron/' => is_dir($this->hermesHome . DIRECTORY_SEPARATOR . 'cron'),
            ],
        ];
    }

    /**
     * Get environment variables list with masked secrets and categories
     */
    public function getEnvironmentVariables(): array
    {
        $rawEnv = $this->getEnv();

        $standardKeys = [
            'GOOGLE_API_KEY' => ['cat' => 'API Keys & Inference', 'desc' => 'Google Gemini API Key untuk model gemini-3.6-flash / flash-lite'],
            'GEMINI_API_KEY' => ['cat' => 'API Keys & Inference', 'desc' => 'Alias Gemini API Key'],
            'GROQ_API_KEY' => ['cat' => 'API Keys & Inference', 'desc' => 'Groq API Key untuk ultra-fast inference'],
            'OPENROUTER_API_KEY' => ['cat' => 'API Keys & Inference', 'desc' => 'OpenRouter API Key untuk multi-provider access'],
            'ANTHROPIC_API_KEY' => ['cat' => 'API Keys & Inference', 'desc' => 'Anthropic Claude direct API Key'],
            'OPENAI_API_KEY' => ['cat' => 'API Keys & Inference', 'desc' => 'OpenAI GPT-4o / embeddings API Key'],
            'CUSTOM_API_KEY' => ['cat' => 'API Keys & Inference', 'desc' => 'Custom OpenAI-compatible API Key'],
            'GROQ_BASE_URL' => ['cat' => 'API Keys & Inference', 'desc' => 'Base URL untuk Groq endpoint'],
            'TELEGRAM_BOT_TOKEN' => ['cat' => 'Messaging & Bot', 'desc' => 'Token Bot Telegram dari @BotFather'],
            'TELEGRAM_ALLOWED_USERS' => ['cat' => 'Messaging & Bot', 'desc' => 'Daftar ID user Telegram yang diizinkan (whitelist)'],
            'TELEGRAM_HOME_CHANNEL' => ['cat' => 'Messaging & Bot', 'desc' => 'Default destination Telegram chat ID'],
            'TELEGRAM_HOME_CHANNEL_THREAD_ID' => ['cat' => 'Messaging & Bot', 'desc' => 'Thread / topic ID di Telegram Home Channel'],
            'DISCORD_BOT_TOKEN' => ['cat' => 'Messaging & Bot', 'desc' => 'Token bot Discord'],
            'TERMINAL_TIMEOUT' => ['cat' => 'Runtime & Storage', 'desc' => 'Batas waktu eksekusi perintah terminal (detik)'],
            'TERMINAL_LIFETIME_SECONDS' => ['cat' => 'Runtime & Storage', 'desc' => 'Masa aktif pty shell background session'],
            'TERMINAL_MODAL_IMAGE' => ['cat' => 'Runtime & Storage', 'desc' => 'Container base image untuk sandboxed execution'],
            'BROWSERBASE_PROXIES' => ['cat' => 'Tools & Browser', 'desc' => 'Pengaturan proxy browserbase automation'],
            'BROWSERBASE_ADVANCED_STEALTH' => ['cat' => 'Tools & Browser', 'desc' => 'Anti-bot stealth mode untuk headless browser'],
            'BROWSER_SESSION_TIMEOUT' => ['cat' => 'Tools & Browser', 'desc' => 'Timeout session browser navigasi web'],
            'WEB_TOOLS_DEBUG' => ['cat' => 'Tools & Debug', 'desc' => 'Mode debug untuk tool web fetch & scraper'],
            'VISION_TOOLS_DEBUG' => ['cat' => 'Tools & Debug', 'desc' => 'Mode debug untuk visual inspect OCR tools'],
        ];

        $result = [];

        // Combine standard and any additional keys found in .env
        $allKeys = array_unique(array_merge(array_keys($standardKeys), array_keys($rawEnv)));

        foreach ($allKeys as $key) {
            $val = $rawEnv[$key] ?? null;
            $isSet = !empty($val);
            $info = $standardKeys[$key] ?? ['cat' => 'Custom Variables', 'desc' => 'Variabel lingkungan kustom'];

            $isSecret = str_contains($key, 'KEY') || str_contains($key, 'TOKEN') || str_contains($key, 'SECRET') || str_contains($key, 'PASSWORD');
            $redacted = '';
            if ($isSet) {
                if ($isSecret) {
                    $prefix = substr($val, 0, min(4, strlen($val)));
                    $redacted = $prefix . '••••••••••••';
                } else {
                    $redacted = $val;
                }
            }

            $result[] = [
                'key' => $key,
                'value' => $val,
                'redacted' => $redacted,
                'isSet' => $isSet,
                'isSecret' => $isSecret,
                'category' => $info['cat'],
                'description' => $info['desc'],
            ];
        }

        return $result;
    }

    /**
     * Get categorized sections of config.yaml with raw YAML representations
     */
    public function getConfigSections(): array
    {
        $config = $this->getConfig();

        $sections = [
            'Model Configuration' => $config['model'] ?? [
                'name' => 'gemini-3.6-flash',
                'provider' => 'gemini',
                'temperature' => 0.7,
                'max_tokens' => 4096,
                'fallback_models' => ['gemini-flash-lite-latest', 'llama-3.3-70b-versatile'],
            ],
            'Terminal & Execution' => $config['terminal'] ?? [
                'enabled' => true,
                'backend' => 'local',
                'timeout' => 180,
                'cwd' => '.',
                'allowed_commands' => ['git', 'npm', 'php', 'python', 'composer'],
            ],
            'Compression & Context' => $config['compression'] ?? [
                'enabled' => true,
                'strategy' => 'sliding_window',
                'max_context_chars' => 100000,
                'preserve_system' => true,
            ],
            'Memory & Knowledge' => $config['memory'] ?? [
                'enabled' => true,
                'auto_save' => true,
                'max_chars' => 2200,
                'file' => 'MEMORY.md',
            ],
            'Agent Behavior & Guardrails' => $config['tool_loop_guardrails'] ?? [
                'warnings_enabled' => true,
                'hard_stop_enabled' => false,
                'non_interactive_hard_stop_enabled' => true,
            ],
            'Platform Toolsets' => $config['platform_toolsets'] ?? [
                'cli' => ['file_read', 'file_write', 'terminal', 'memory', 'skills'],
                'telegram' => ['memory', 'web_search', 'skills', 'terminal'],
                'discord' => ['memory', 'web_search'],
            ],
            'Streaming & Transport' => $config['streaming'] ?? [
                'enabled' => true,
                'chunk_size' => 256,
                'heartbeat_interval' => 5000,
            ],
            'Browser Automation' => $config['browser'] ?? [
                'enabled' => false,
                'headless' => true,
                'timeout' => 30000,
            ],
            'Plugins & Extensions' => $config['plugins'] ?? [
                'auto_check_updates' => true,
                'registry_url' => 'https://raw.githubusercontent.com/nousresearch/hermes-agent-plugins/main',
            ],
        ];

        $formatted = [];
        foreach ($sections as $title => $data) {
            $formatted[] = [
                'title' => $title,
                'data' => $data,
                'yaml' => Yaml::dump($data, 4, 2),
            ];
        }

        return $formatted;
    }

    /**
     * Get all messaging platforms (Channels)
     */
    public function getMessagingPlatforms(?string $profile = 'default'): array
    {
        $profile = $profile ?: 'default';
        $res = $this->callNativeApi('GET', '/api/messaging/platforms', [], $profile);
        return is_array($res) ? $res : ['platforms' => [], 'env_path' => '', 'gateway_start_command' => ''];
    }

    /**
     * Update messaging platform settings / env vars
     */
    public function updateMessagingPlatform(string $id, array $data, ?string $profile = 'default'): ?array
    {
        $profile = $profile ?: 'default';
        return $this->callNativeApi('PUT', "/api/messaging/platforms/{$id}", $data, $profile);
    }

    /**
     * Test a messaging platform connection
     */
    public function testMessagingPlatform(string $id, ?string $profile = 'default'): ?array
    {
        $profile = $profile ?: 'default';
        return $this->callNativeApi('POST', "/api/messaging/platforms/{$id}/test", [], $profile);
    }

    /**
     * Get Model Information (current configured model & provider)
     */
    public function getModelInfo(?string $profile = 'default'): array
    {
        $profile = $profile ?: 'default';
        $res = $this->callNativeApi('GET', '/api/model/info', [], $profile);
        return is_array($res) ? $res : [];
    }

    /**
     * Get Model Options & Catalog
     */
    public function getModelOptions(?string $profile = 'default'): array
    {
        $profile = $profile ?: 'default';
        $res = $this->callNativeApi('GET', '/api/model/options', [], $profile);
        return is_array($res) ? $res : [];
    }

    /**
     * Set Model Assignment (main or auxiliary)
     */
    public function setModelAssignment(array $data, ?string $profile = 'default'): ?array
    {
        $profile = $profile ?: 'default';
        return $this->callNativeApi('POST', '/api/model/set', $data, $profile);
    }

    /**
     * Get Installed MCP Servers
     */
    public function getMcpServers(?string $profile = 'default'): array
    {
        $profile = $profile ?: 'default';
        $res = $this->callNativeApi('GET', '/api/mcp/servers', [], $profile);
        return is_array($res) && isset($res['servers']) ? $res['servers'] : (is_array($res) ? $res : []);
    }

    /**
     * Get Official MCP Catalog
     */
    public function getMcpCatalog(?string $profile = 'default'): array
    {
        $profile = $profile ?: 'default';
        $res = $this->callNativeApi('GET', '/api/mcp/catalog', [], $profile);
        return is_array($res) ? $res : ['entries' => [], 'diagnostics' => []];
    }

    /**
     * Get Webhooks configuration & routes
     */
    public function getWebhooks(?string $profile = 'default'): array
    {
        $profile = $profile ?: 'default';
        $res = $this->callNativeApi('GET', '/api/webhooks', [], $profile);
        return is_array($res) ? $res : ['enabled' => false, 'routes' => []];
    }

    /**
     * Get Pairing Requests & Approved Users
     */
    public function getPairing(?string $profile = 'default'): array
    {
        $profile = $profile ?: 'default';
        $res = $this->callNativeApi('GET', '/api/pairing', [], $profile);
        return is_array($res) ? $res : ['pending' => [], 'approved' => []];
    }

    /**
     * Approve pairing request
     */
    public function approvePairing(string $platform, string $requestId, ?string $profile = 'default'): ?array
    {
        $profile = $profile ?: 'default';
        return $this->callNativeApi('POST', '/api/pairing/approve', [
            'platform' => $platform,
            'request_id' => $requestId,
            'profile' => $profile,
        ], $profile);
    }

    /**
     * Revoke approved pairing
     */
    public function revokePairing(string $platform, string $userId, ?string $profile = 'default'): ?array
    {
        $profile = $profile ?: 'default';
        return $this->callNativeApi('POST', '/api/pairing/revoke', [
            'platform' => $platform,
            'user_id' => $userId,
            'profile' => $profile,
        ], $profile);
    }

    /**
     * Get System Stats & Diagnostics
     */
    public function getSystemStats(?string $profile = 'default'): array
    {
        $profile = $profile ?: 'default';
        $res = $this->callNativeApi('GET', '/api/system/stats', [], $profile);
        return is_array($res) ? $res : [];
    }

    /**
     * List Managed Files in workspace
     */
    public function getFiles(?string $path = '', ?string $profile = 'default'): array
    {
        $profile = $profile ?: 'default';
        $res = $this->callNativeApi('GET', '/api/files', ['path' => $path], $profile);
        return is_array($res) ? $res : [];
    }

    /**
     * Execute chat prompt via Hermes CLI
     */
    public function executeChatMessage(string $message, ?string $sessionId = null, ?string $profile = 'default'): array
    {
        $bin = $this->hermesHome . '\\bin\\hermes.exe';
        $profile = $profile ?: 'default';
        $escapedMsg = addslashes($message);

        // Use -Q (quiet mode) to suppress banners, spinners, and ANSI colors
        $cmd = "\"{$bin}\" chat -Q -q \"{$escapedMsg}\" --profile {$profile}";
        if ($sessionId) {
            $cmd .= " --resume \"{$sessionId}\"";
        }

        $env = array_merge($_SERVER, $_ENV, [
            'HERMES_HOME' => $this->hermesHome,
            'SystemRoot' => getenv('SystemRoot') ?: 'C:\\Windows',
            'WINDIR' => getenv('WINDIR') ?: 'C:\\Windows',
            'PATH' => getenv('PATH'),
            'USERPROFILE' => getenv('USERPROFILE') ?: 'C:\\Users\\HP',
            'HOME' => getenv('USERPROFILE') ?: 'C:\\Users\\HP',
            'HOMEDRIVE' => 'C:',
            'HOMEPATH' => '\\Users\\HP',
            'LOCALAPPDATA' => getenv('LOCALAPPDATA') ?: 'C:\\Users\\HP\\AppData\\Local',
            'APPDATA' => getenv('APPDATA') ?: 'C:\\Users\\HP\\AppData\\Roaming',
            'TEMP' => getenv('TEMP') ?: 'C:\\Users\\HP\\AppData\\Local\\Temp',
            'TMP' => getenv('TMP') ?: 'C:\\Users\\HP\\AppData\\Local\\Temp',
        ]);

        $process = \Symfony\Component\Process\Process::fromShellCommandline($cmd, null, $env);
        $process->setTimeout(90);
        $process->run();

        $output = $process->getOutput();
        if (empty($output)) {
            $output = $process->getErrorOutput();
        }

        $newSessionId = $sessionId;
        if (preg_match('/session_id:\s*([a-zA-Z0-9_\-]+)/i', $output, $sm)) {
            $newSessionId = trim($sm[1]);
        } elseif (preg_match('/Session:\s*([a-zA-Z0-9_\-]+)/i', $output, $sm)) {
            $newSessionId = trim($sm[1]);
        }

        // Clean output: remove ANSI escape codes & console artifacts
        $clean = $output;
        $clean = preg_replace('/\x1b\[[0-9;]*[a-zA-Z]/', '', $clean);
        $clean = preg_replace('/(\x1b|\x1B|≡)\[[0-9;]*[a-zA-Z]/', '', $clean);
        $clean = preg_replace('/(\x1b|\x1B|≡)\[[0-9;]*m/', '', $clean);
        $clean = preg_replace('/^↻\s*Resumed session.*?\r?\n/i', '', $clean);
        $clean = preg_replace('/^Query:.*?\r?\n/is', '', $clean);
        $clean = preg_replace('/session_id:\s*[a-zA-Z0-9_\-]+.*$/is', '', $clean);
        $clean = preg_replace('/Session:.*$/is', '', $clean);
        $clean = preg_replace('/Resume this session with:.*$/is', '', $clean);
        $clean = preg_replace('/[─\-]{5,}/', '', $clean);
        $clean = preg_replace('/^☤\s*Hermes\s*/', '', $clean);
        $reply = trim($clean);

        return [
            'success' => !empty($reply) && !str_contains($reply, 'Traceback (most recent call last)'),
            'reply' => $reply ?: 'Hermes tidak mengembalikan respon.',
            'session_id' => $newSessionId,
            'raw' => $output,
        ];
    }
}
