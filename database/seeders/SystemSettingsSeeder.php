<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SystemSetting;
use App\Models\GlobalUser;
use Illuminate\Support\Facades\Hash;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Directory Paths & System Settings
        $paths = [
            [
                'key' => 'hermes_home_path',
                'value' => 'E:\\hermes',
                'group' => 'paths',
                'type' => 'string',
                'label' => 'Hermes Home Directory',
                'description' => 'Path direktori instalasi utama Hermes Agent.',
                'is_secret' => false,
            ],
            [
                'key' => 'hermes_bin_path',
                'value' => 'E:\\hermes\\bin\\hermes.exe',
                'group' => 'paths',
                'type' => 'string',
                'label' => 'Hermes Binary Executable',
                'description' => 'File executable hermes.exe untuk eksekusi CLI dan daemon.',
                'is_secret' => false,
            ],
            [
                'key' => 'project_directory_path',
                'value' => 'E:\\App\\hermes-dashboard',
                'group' => 'paths',
                'type' => 'string',
                'label' => 'Project Root Directory',
                'description' => 'Path absolut direktori project Laravel Control Center.',
                'is_secret' => false,
            ],
            [
                'key' => 'backup_path',
                'value' => 'E:\\hermes\\backups',
                'group' => 'paths',
                'type' => 'string',
                'label' => 'Backup Directory Path',
                'description' => 'Direktori penyimpanan arsip dan backup snapshot.',
                'is_secret' => false,
            ],
            [
                'key' => 'gateway_host',
                'value' => '127.0.0.1',
                'group' => 'gateway',
                'type' => 'string',
                'label' => 'Gateway Host Bind',
                'description' => 'IP host untuk Hermes Gateway Daemon.',
                'is_secret' => false,
            ],
            [
                'key' => 'gateway_port',
                'value' => '9119',
                'group' => 'gateway',
                'type' => 'integer',
                'label' => 'Gateway Port',
                'description' => 'Port komunikasi Hermes Gateway daemon.',
                'is_secret' => false,
            ],
            [
                'key' => 'default_profile',
                'value' => 'default',
                'group' => 'profile',
                'type' => 'string',
                'label' => 'Default Agent Profile',
                'description' => 'Profil aktif default untuk sesi chat dan eksekusi cron.',
                'is_secret' => false,
            ],
        ];

        foreach ($paths as $p) {
            SystemSetting::updateOrCreate(['key' => $p['key']], $p);
        }

        // 2. Read E:\hermes\.env and seed API keys & env into database
        $envFile = 'E:\\hermes\\.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '#')) continue;
                if (!str_contains($line, '=')) continue;

                [$k, $v] = explode('=', $line, 2);
                $k = trim($k);
                $v = trim($v, " \t\n\r\0\x0B\"'");

                $isSecret = str_contains(strtoupper($k), 'KEY') ||
                    str_contains(strtoupper($k), 'TOKEN') ||
                    str_contains(strtoupper($k), 'SECRET') ||
                    str_contains(strtoupper($k), 'PASSWORD');

                $group = 'environment';
                if (str_contains(strtoupper($k), 'API_KEY') || str_contains(strtoupper($k), 'TOKEN')) {
                    $group = 'api_keys';
                }

                SystemSetting::updateOrCreate(
                    ['key' => $k],
                    [
                        'value' => $v,
                        'group' => $group,
                        'type' => $isSecret ? 'password' : 'string',
                        'label' => ucwords(strtolower(str_replace('_', ' ', $k))),
                        'description' => "Variabel sistem {$k} yang disinkronkan ke E:\\hermes\\.env",
                        'is_secret' => $isSecret,
                    ]
                );
            }
        }

        // 3. Set default hashed password for admin and existing users
        $defaultPassword = Hash::make('password123');
        $users = GlobalUser::all();
        foreach ($users as $user) {
            if (empty($user->global_user_password)) {
                $user->global_user_password = $defaultPassword;
                $user->save();
            }
        }

        // Ensure primary admin account exists
        $admin = GlobalUser::where('global_user_role', 'admin')->first();
        if ($admin) {
            if (empty($admin->global_user_email)) {
                $admin->global_user_email = 'admin@expressa.id';
            }
            $admin->global_user_password = Hash::make('password123');
            $admin->save();
        }
    }
}
