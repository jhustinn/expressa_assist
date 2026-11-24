<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use App\Models\GlobalUser;
use App\Models\GlobalActivityLog;
use Inertia\Inertia;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index()
    {
        $users = GlobalUser::orderByRaw("CASE global_user_role WHEN 'admin' THEN 1 WHEN 'developer' THEN 2 WHEN 'reviewer' THEN 3 WHEN 'guest' THEN 4 ELSE 5 END")
            ->orderBy('created_at', 'desc')
            ->get();

        $stats = [
            'total' => $users->count(),
            'active' => $users->where('global_user_is_active', true)->count(),
            'admins' => $users->where('global_user_role', 'admin')->where('global_user_is_active', true)->count(),
            'developers' => $users->where('global_user_role', 'developer')->where('global_user_is_active', true)->count(),
            'env_synced_string' => $users->where('global_user_is_active', true)->pluck('global_user_telegram_id')->implode(','),
        ];

        return Inertia::render('Users/Index', compact('users', 'stats'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'telegram_id' => 'required|string|unique:global.global_user,global_user_telegram_id',
            'name' => 'required|string|max:100',
            'username' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:150|unique:global.global_user,global_user_email',
            'password' => 'nullable|string|min:6',
            'role' => 'required|in:admin,developer,reviewer,guest',
            'can_code' => 'nullable|boolean',
            'can_terminal' => 'nullable|boolean',
            'notes' => 'nullable|string|max:255',
        ]);

        $username = $validated['username'] ?? null;
        if ($username) {
            $username = str_starts_with($username, '@') ? $username : '@' . $username;
        }

        $canCode = $request->boolean('can_code', in_array($validated['role'], ['admin', 'developer']));
        $canTerminal = $request->boolean('can_terminal', $validated['role'] === 'admin');

        $allowedCommands = match ($validated['role']) {
            'admin' => ['*'],
            'developer' => ['/jhustyn', '/erp', '/status', '/stop', '/start'],
            default => ['/status'],
        };

        $password = !empty($validated['password'])
            ? \Illuminate\Support\Facades\Hash::make($validated['password'])
            : \Illuminate\Support\Facades\Hash::make('password123');

        $user = GlobalUser::create([
            'global_user_telegram_id' => $validated['telegram_id'],
            'global_user_nama' => $validated['name'],
            'global_user_username' => $username,
            'global_user_email' => $validated['email'] ?? null,
            'global_user_password' => $password,
            'global_user_role' => $validated['role'],
            'global_user_can_code' => $canCode,
            'global_user_can_terminal' => $canTerminal,
            'global_user_is_active' => true,
            'global_user_allowed_commands' => $allowedCommands,
            'global_user_notes' => $validated['notes'] ?? null,
        ]);

        // Auto-sync to Hermes .env
        $synced = $this->hermes->syncAllowedUsersFromDatabase();

        GlobalActivityLog::create([
            'global_activity_action' => 'user_whitelist_add',
            'global_activity_description' => "Menambahkan user global: {$user->global_user_nama} ({$user->global_user_telegram_id}) sebagai {$user->global_user_role}.",
            'global_activity_status' => 'success',
        ]);

        return redirect()->back()->with('success', "User {$user->global_user_nama} berhasil ditambahkan dan disinkronkan ke whitelist Hermes!");
    }

    public function update(Request $request, $id)
    {
        $user = GlobalUser::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'username' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:150|unique:global.global_user,global_user_email,' . $user->global_user_id . ',global_user_id',
            'password' => 'nullable|string|min:6',
            'role' => 'required|in:admin,developer,reviewer,guest',
            'can_code' => 'nullable|boolean',
            'can_terminal' => 'nullable|boolean',
            'notes' => 'nullable|string|max:255',
        ]);

        $username = $validated['username'] ?? null;
        if ($username) {
            $username = str_starts_with($username, '@') ? $username : '@' . $username;
        }

        $canCode = $request->boolean('can_code', in_array($validated['role'], ['admin', 'developer']));
        $canTerminal = $request->boolean('can_terminal', $validated['role'] === 'admin');

        $allowedCommands = match ($validated['role']) {
            'admin' => ['*'],
            'developer' => ['/jhustyn', '/erp', '/status', '/stop', '/start'],
            default => ['/status'],
        };

        $updateData = [
            'global_user_nama' => $validated['name'],
            'global_user_username' => $username,
            'global_user_email' => $validated['email'] ?? $user->global_user_email,
            'global_user_role' => $validated['role'],
            'global_user_can_code' => $canCode,
            'global_user_can_terminal' => $canTerminal,
            'global_user_allowed_commands' => $allowedCommands,
            'global_user_notes' => $validated['notes'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $updateData['global_user_password'] = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }

        $user->update($updateData);

        // Resync
        $synced = $this->hermes->syncAllowedUsersFromDatabase();

        GlobalActivityLog::create([
            'global_activity_action' => 'user_whitelist_update',
            'global_activity_description' => "Memperbarui user global: {$user->global_user_nama} ({$user->global_user_telegram_id}) ke role {$user->global_user_role}.",
            'global_activity_status' => 'success',
        ]);

        return redirect()->back()->with('success', "User {$user->global_user_nama} berhasil diperbarui.");
    }

    public function toggle($id)
    {
        $user = GlobalUser::findOrFail($id);
        $user->global_user_is_active = !$user->global_user_is_active;
        $user->save();

        // Resync to .env
        $synced = $this->hermes->syncAllowedUsersFromDatabase();

        $statusStr = $user->global_user_is_active ? 'diaktifkan (Whitelisted)' : 'dinonaktifkan (Suspended)';

        GlobalActivityLog::create([
            'global_activity_action' => 'user_whitelist_toggle',
            'global_activity_description' => "Status user {$user->global_user_nama} ({$user->global_user_telegram_id}) diubah menjadi {$statusStr}.",
            'global_activity_status' => 'success',
        ]);

        return redirect()->back()->with('success', "User {$user->global_user_nama} berhasil {$statusStr}. Whitelist .env tersinkron!");
    }

    public function destroy($id)
    {
        $user = GlobalUser::findOrFail($id);
        $name = $user->global_user_nama;
        $tId = $user->global_user_telegram_id;
        $user->delete();

        // Resync
        $synced = $this->hermes->syncAllowedUsersFromDatabase();

        GlobalActivityLog::create([
            'global_activity_action' => 'user_whitelist_delete',
            'global_activity_description' => "Menghapus user {$name} ({$tId}) dari whitelist.",
            'global_activity_status' => 'warning',
        ]);

        return redirect()->back()->with('success', "User {$name} berhasil dihapus dari daftar whitelist.");
    }

    public function syncEnv()
    {
        $synced = $this->hermes->syncAllowedUsersFromDatabase();

        GlobalActivityLog::create([
            'global_activity_action' => 'telegram_whitelist_sync_env',
            'global_activity_description' => "Manual sync Telegram Whitelist ke E:\\hermes\\.env: TELEGRAM_ALLOWED_USERS={$synced}",
            'global_activity_status' => 'success',
        ]);

        return redirect()->back()->with('success', "Whitelist berhasil disinkronkan ke E:\\hermes\\.env ({$synced})");
    }

    public function resetPassword(Request $request, $id)
    {
        $user = GlobalUser::findOrFail($id);
        $validated = $request->validate([
            'password' => 'required|string|min:6',
        ]);

        $user->global_user_password = \Illuminate\Support\Facades\Hash::make($validated['password']);
        $user->save();

        GlobalActivityLog::create([
            'global_activity_action' => 'user_password_reset',
            'global_activity_description' => "Reset password untuk user: {$user->global_user_nama} ({$user->global_user_email})",
            'global_activity_status' => 'success',
        ]);

        return redirect()->back()->with('success', "Password untuk {$user->global_user_nama} berhasil diperbarui di database!");
    }

    public function lookupTelegram($id)
    {
        $telegramId = trim($id);
        if (!is_numeric($telegramId)) {
            return response()->json([
                'success' => false,
                'message' => 'Telegram ID harus berupa angka numerik.'
            ], 422);
        }

        // 1. Cek dari database global.global_user jika pernah ada
        $existingUser = \App\Models\GlobalUser::where('global_user_telegram_id', $telegramId)->first();
        $dbUsername = $existingUser?->global_user_username;
        $dbName = $existingUser?->global_user_nama;

        // 2. Cek dari local channel_directory.json Hermes
        $channelDirFile = $this->hermes->getHermesHome() . DIRECTORY_SEPARATOR . 'channel_directory.json';
        $localName = $dbName;
        if (file_exists($channelDirFile)) {
            $data = json_decode(file_get_contents($channelDirFile), true);
            if (!empty($data['platforms']['telegram'])) {
                foreach ($data['platforms']['telegram'] as $chat) {
                    if (strval($chat['id'] ?? '') === strval($telegramId)) {
                        $localName = $chat['name'] ?? $localName;
                        break;
                    }
                }
            }
        }

        // 3. Query Telegram Bot API (getChat)
        $env = $this->hermes->getEnv();
        $botToken = $env['TELEGRAM_BOT_TOKEN'] ?? null;

        if ($botToken) {
            try {
                $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                    ->timeout(6)
                    ->get("https://api.telegram.org/bot{$botToken}/getChat", [
                        'chat_id' => $telegramId,
                    ]);

                if ($response->successful() && $response->json('ok')) {
                    $res = $response->json('result');
                    $firstName = $res['first_name'] ?? '';
                    $lastName = $res['last_name'] ?? '';
                    $fullName = trim("{$firstName} {$lastName}") ?: ($localName ?: 'Telegram User');
                    
                    // Prioritas username:
                    // A. Dari Telegram API jika akun punya username publik
                    // B. Dari database jika sebelumnya pernah didaftarkan
                    // C. Auto-generate cerdas dari nama lengkap (@firstnamelastname)
                    $username = null;
                    if (!empty($res['username'])) {
                        $username = str_starts_with($res['username'], '@') ? $res['username'] : '@' . $res['username'];
                    } elseif ($dbUsername) {
                        $username = $dbUsername;
                    } else {
                        $cleanSlug = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', "{$firstName}{$lastName}"));
                        if (!empty($cleanSlug)) {
                            $username = '@' . $cleanSlug;
                        }
                    }

                    return response()->json([
                        'success' => true,
                        'name' => $fullName,
                        'username' => $username,
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'has_telegram_username' => !empty($res['username']),
                        'source' => 'telegram_api',
                    ]);
                }
            } catch (\Exception $e) {
                // Lanjut ke fallback
            }
        }

        // Fallback jika API Telegram gagal tapi ada data lokal
        if ($localName || $dbUsername) {
            $cleanSlug = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $localName ?: 'user'));
            return response()->json([
                'success' => true,
                'name' => $localName ?: 'Telegram User',
                'username' => $dbUsername ?: ('@' . $cleanSlug),
                'has_telegram_username' => false,
                'source' => 'local_fallback',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'User belum pernah mengirim pesan /start ke bot @jhustyn_hermes_bot atau privasi akun dibatasi. Silakan isi nama secara manual.',
        ], 404);
    }
}
