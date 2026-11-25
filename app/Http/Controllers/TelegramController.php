<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use App\Models\TelegramCommand;
use App\Models\TelegramUser;
use App\Models\HermesActivityLog;
use Inertia\Inertia;
use Illuminate\Http\Request;

class TelegramController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index()
    {
        $commands = TelegramCommand::latest()->get();
        $users = TelegramUser::orderByRaw("CASE global_user_role WHEN 'admin' THEN 1 WHEN 'developer' THEN 2 WHEN 'reviewer' THEN 3 WHEN 'guest' THEN 4 ELSE 5 END")->get();
        $env = $this->hermes->getEnv();
        $profiles = $this->hermes->getProfiles();

        return Inertia::render('Telegram', compact('commands', 'users', 'env', 'profiles'));
    }

    // ── RBAC User Management ─────────────────────────────────────────────────

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'telegram_id' => 'required|string|unique:telegram_users,telegram_id',
            'name' => 'required|string',
            'username' => 'nullable|string',
            'role' => 'required|in:admin,developer,reviewer,guest',
            'notes' => 'nullable|string',
        ]);

        $validated['username'] = $validated['username'] ? (str_starts_with($validated['username'], '@') ? $validated['username'] : '@' . $validated['username']) : null;
        $validated['can_code'] = $request->has('can_code') || in_array($validated['role'], ['admin', 'developer']);
        $validated['can_terminal'] = $request->has('can_terminal') || $validated['role'] === 'admin';
        $validated['is_active'] = true;

        if ($validated['role'] === 'admin') {
            $validated['allowed_commands'] = ['*'];
        } elseif ($validated['role'] === 'developer') {
            $validated['allowed_commands'] = ['/jhustyn', '/erp', '/status', '/stop', '/start'];
        } else {
            $validated['allowed_commands'] = ['/status'];
        }

        TelegramUser::create($validated);

        // Sync to .env
        $synced = $this->hermes->syncAllowedUsersFromDatabase();

        HermesActivityLog::create([
            'type' => 'telegram_user_add',
            'sender' => 'Dashboard Web',
            'message' => "User Telegram {$validated['name']} ({$validated['telegram_id']}) ditambahkan dengan role {$validated['role']}",
        ]);

        return redirect()->route('telegram.index')->with('success', "User {$validated['name']} berhasil ditambahkan ke whitelist dan .env telah disinkronkan ({$synced})!");
    }

    public function updateUser(Request $request, $id)
    {
        $user = TelegramUser::findOrFail($id);

        $validated = $request->validate([
            'telegram_id' => 'required|string|unique:telegram_users,telegram_id,' . $id,
            'name' => 'required|string',
            'username' => 'nullable|string',
            'role' => 'required|in:admin,developer,reviewer,guest',
            'notes' => 'nullable|string',
        ]);

        $validated['username'] = $validated['username'] ? (str_starts_with($validated['username'], '@') ? $validated['username'] : '@' . $validated['username']) : null;
        $validated['can_code'] = $request->has('can_code');
        $validated['can_terminal'] = $request->has('can_terminal');
        $validated['is_active'] = $request->has('is_active');

        $user->update($validated);
        $this->hermes->syncAllowedUsersFromDatabase();

        return redirect()->route('telegram.index')->with('success', "Data user {$user->name} berhasil diperbarui!");
    }

    public function toggleUserStatus($id)
    {
        $user = TelegramUser::findOrFail($id);
        $user->is_active = !$user->is_active;
        $user->save();

        $synced = $this->hermes->syncAllowedUsersFromDatabase();

        $statusText = $user->is_active ? 'Diaktifkan (Whitelisted)' : 'Dinonaktifkan (Suspended)';
        return redirect()->route('telegram.index')->with('success', "Status user {$user->name} berhasil diubah menjadi {$statusText}. Sync: {$synced}");
    }

    public function destroyUser($id)
    {
        $user = TelegramUser::findOrFail($id);
        $name = $user->name;
        $user->delete();

        $synced = $this->hermes->syncAllowedUsersFromDatabase();

        return redirect()->route('telegram.index')->with('success', "User {$name} telah dihapus dari whitelist. Sync: {$synced}");
    }

    public function syncEnv()
    {
        $synced = $this->hermes->syncAllowedUsersFromDatabase();
        return redirect()->route('telegram.index')->with('success', "Berhasil sinkronisasi whitelist Telegram ke E:\\hermes\\.env: {$synced}");
    }

    // ── Commands ─────────────────────────────────────────────────────────────

    public function storeCommand(Request $request)
    {
        $validated = $request->validate([
            'command' => 'required|string|starts_with:/',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'target_profile' => 'required|string',
            'prompt_template' => 'nullable|string',
        ]);

        TelegramCommand::create($validated);

        return redirect()->route('telegram.index')->with('success', "Command {$validated['command']} berhasil ditambahkan!");
    }

    public function updateCommand(Request $request, $id)
    {
        $command = TelegramCommand::findOrFail($id);

        $validated = $request->validate([
            'command' => 'required|string|starts_with:/',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'target_profile' => 'required|string',
            'prompt_template' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $command->update($validated);

        return redirect()->route('telegram.index')->with('success', "Command {$command->command} berhasil diperbarui!");
    }

    public function destroyCommand($id)
    {
        $command = TelegramCommand::findOrFail($id);
        $name = $command->command;
        $command->delete();

        return redirect()->route('telegram.index')->with('success', "Command {$name} telah dihapus!");
    }

    public function updateTelegramAuth(Request $request)
    {
        $request->validate([
            'telegram_bot_token' => 'required|string',
        ]);

        $this->hermes->updateEnv([
            'TELEGRAM_BOT_TOKEN' => $request->input('telegram_bot_token'),
        ]);

        // Also ensure current active users in database are synced
        $this->hermes->syncAllowedUsersFromDatabase();

        return redirect()->route('telegram.index')->with('success', 'Bot Token Telegram & Whitelist berhasil disinkronkan!');
    }

    // ── Simulator & Broadcast ───────────────────────────────────────────────

    public function simulateMessage(Request $request)
    {
        $validated = $request->validate([
            'telegram_id' => 'required|string',
            'message' => 'required|string',
            'profile' => 'nullable|string',
        ]);

        $telegramId = $validated['telegram_id'];
        $message = trim($validated['message']);
        $profile = $validated['profile'] ?: 'default';

        // Check if user is registered & active in DB
        $user = TelegramUser::where('global_user_telegram_id', $telegramId)
            ->where('global_user_is_active', true)
            ->first();

        if (!$user) {
            return response()->json([
                'status' => 'rejected',
                'reason' => 'unauthorized',
                'reply' => "⛔ AKSES DITOLAK: ID Telegram {$telegramId} tidak terdaftar dalam whitelist atau akun sedang dinonaktifkan. Hubungi Administrator.",
            ]);
        }

        // Check command permissions
        if (str_starts_with($message, '/')) {
            $parts = explode(' ', $message);
            $cmd = strtolower($parts[0]);

            $allowed = $user->global_user_allowed_commands ?? [];
            $role = $user->global_user_role ?? 'guest';

            if (!in_array('*', $allowed) && $role !== 'admin' && !in_array($cmd, $allowed)) {
                return response()->json([
                    'status' => 'rejected',
                    'reason' => 'forbidden_command',
                    'reply' => "⚠️ PERINGATAN RBAC: Peran Anda [{$role}] tidak memiliki izin untuk mengeksekusi perintah '{$cmd}'.",
                ]);
            }

            // Check if command is registered in telegram_commands
            $registeredCmd = TelegramCommand::where('command', $cmd)->where('is_active', true)->first();
            if ($registeredCmd) {
                if ($registeredCmd->target_profile) {
                    $profile = $registeredCmd->target_profile;
                }
                if ($registeredCmd->prompt_template) {
                    $argText = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';
                    $message = str_replace('{args}', $argText, $registeredCmd->prompt_template);
                }
            }
        }

        // Execute via safe AI Provider Engine (no hermes.exe)
        $sessionId = 'sim_tg_' . $telegramId;
        $response = $this->hermes->executeChatMessage($message, $sessionId, $profile);

        HermesActivityLog::create([
            'type' => 'telegram_simulated_chat',
            'sender' => "TG:{$user->global_user_nama} ({$telegramId})",
            'message' => "Simulasi pesan: \"{$message}\" -> " . substr($response['reply'] ?? '', 0, 100),
        ]);

        return response()->json([
            'status' => 'success',
            'reply' => $response['reply'] ?? 'Tidak ada respon.',
            'provider' => $response['provider'] ?? 'safe-api',
            'user' => [
                'name' => $user->global_user_nama,
                'role' => $user->global_user_role,
                'telegram_id' => $user->global_user_telegram_id,
            ],
        ]);
    }

    public function broadcastMessage(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string',
            'target_role' => 'nullable|string',
        ]);

        $query = TelegramUser::where('global_user_is_active', true)
            ->whereNotNull('global_user_telegram_id');

        if (!empty($validated['target_role']) && $validated['target_role'] !== 'all') {
            $query->where('global_user_role', $validated['target_role']);
        }

        $users = $query->get();
        $env = $this->hermes->getEnv();
        $botToken = $env['TELEGRAM_BOT_TOKEN'] ?? null;

        $sentCount = 0;
        $errors = [];

        foreach ($users as $user) {
            if ($botToken) {
                try {
                    $res = \Illuminate\Support\Facades\Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                        'chat_id' => $user->global_user_telegram_id,
                        'text' => "📢 [BROADCAST EXPRESSA ASSIST]\n\n" . $validated['message'],
                        'parse_mode' => 'Markdown',
                    ]);
                    if ($res->successful()) {
                        $sentCount++;
                    } else {
                        $errors[] = "User {$user->global_user_nama}: " . $res->body();
                    }
                } catch (\Exception $e) {
                    $errors[] = "User {$user->global_user_nama}: " . $e->getMessage();
                }
            } else {
                $sentCount++;
            }
        }

        HermesActivityLog::create([
            'type' => 'telegram_broadcast',
            'sender' => 'Dashboard Web Broadcast',
            'message' => "Broadcast dikirim ke {$sentCount} pengguna: \"{$validated['message']}\"",
        ]);

        return response()->json([
            'status' => 'success',
            'sent_count' => $sentCount,
            'total_targets' => count($users),
            'has_bot_token' => !empty($botToken),
            'errors' => $errors,
        ]);
    }
}
