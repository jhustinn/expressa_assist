<?php

namespace Database\Seeders;

use App\Models\GlobalUser;
use App\Models\MasterTelegramCommand;
use App\Models\GlobalActivityLog;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Master Telegram Commands
        $defaultCommands = [
            [
                'master_command_command' => '/jhustyn',
                'master_command_nama' => 'Jhustyn ERP Lead',
                'master_command_deskripsi' => 'Mengarahkan prompt langsung ke agent Jhustyn di repository ERP JPP',
                'master_command_target_profile' => 'jhustyn',
                'master_command_prompt_template' => '{prompt}',
                'master_command_is_active' => true,
            ],
            [
                'master_command_command' => '/stop',
                'master_command_nama' => 'Stop Execution',
                'master_command_deskripsi' => 'Menghentikan proses agent atau perintah yang sedang running',
                'master_command_target_profile' => 'default',
                'master_command_prompt_template' => '/stop',
                'master_command_is_active' => true,
            ],
            [
                'master_command_command' => '/status',
                'master_command_nama' => 'System Status',
                'master_command_deskripsi' => 'Mengecek status model, direktori kerja, dan memory agent',
                'master_command_target_profile' => 'default',
                'master_command_prompt_template' => 'Cek status environment dan workspace kamu saat ini.',
                'master_command_is_active' => true,
            ],
            [
                'master_command_command' => '/erp',
                'master_command_nama' => 'Quick ERP Build',
                'master_command_deskripsi' => 'Jalankan vite build & verifikasi schema di E:\App\SWA\JPP-ERP',
                'master_command_target_profile' => 'jhustyn',
                'master_command_prompt_template' => 'Jalankan build test di workspace ERP JPP dan laporkan hasilnya.',
                'master_command_is_active' => true,
            ],
        ];

        foreach ($defaultCommands as $cmd) {
            MasterTelegramCommand::updateOrCreate(
                ['master_command_command' => $cmd['master_command_command']],
                $cmd
            );
        }

        // 2. Seed Global Users with Realistic Data
        $samples = [
            ['telegram_id' => '7605759955', 'name' => 'Jesa Ase', 'username' => '@jesaase', 'email' => 'jesa@expressa.id', 'role' => 'admin', 'can_code' => true, 'can_terminal' => true, 'is_active' => true, 'notes' => 'Principal Stakeholder & Super Admin'],
            ['telegram_id' => '1234567890', 'name' => 'Contoh Developer', 'username' => '@dev_team', 'email' => 'dev@expressa.id', 'role' => 'developer', 'can_code' => true, 'can_terminal' => false, 'is_active' => false, 'notes' => 'Frontend / Backend Engineer'],
            ['telegram_id' => '8821938472', 'name' => 'Ahmad Fauzi', 'username' => '@fauzi_dev', 'email' => 'fauzi@expressa.id', 'role' => 'developer', 'can_code' => true, 'can_terminal' => true, 'is_active' => true, 'notes' => 'Senior Backend Engineer'],
            ['telegram_id' => '8837192837', 'name' => 'Budi Santoso', 'username' => '@budi_s', 'email' => 'budi@expressa.id', 'role' => 'reviewer', 'can_code' => false, 'can_terminal' => false, 'is_active' => true, 'notes' => 'QA & Code Reviewer'],
            ['telegram_id' => '7719283741', 'name' => 'Citra Lestari', 'username' => '@citra_ui', 'email' => 'citra@expressa.id', 'role' => 'developer', 'can_code' => true, 'can_terminal' => false, 'is_active' => true, 'notes' => 'UI/UX & Frontend Engineer'],
            ['telegram_id' => '6628192831', 'name' => 'Dimas Pratama', 'username' => '@dimas_p', 'email' => 'dimas@expressa.id', 'role' => 'developer', 'can_code' => true, 'can_terminal' => true, 'is_active' => true, 'notes' => 'DevOps & Infrastructure'],
            ['telegram_id' => '5519283742', 'name' => 'Eko Wahyudi', 'username' => '@eko_w', 'email' => 'eko@expressa.id', 'role' => 'reviewer', 'can_code' => false, 'can_terminal' => false, 'is_active' => false, 'notes' => 'Internal Security Auditor'],
            ['telegram_id' => '4419283743', 'name' => 'Fitri Handayani', 'username' => '@fitri_h', 'email' => 'fitri@expressa.id', 'role' => 'guest', 'can_code' => false, 'can_terminal' => false, 'is_active' => true, 'notes' => 'Project Manager Intern'],
            ['telegram_id' => '3319283744', 'name' => 'Gilang Ramadhan', 'username' => '@gilang_r', 'email' => 'gilang@expressa.id', 'role' => 'developer', 'can_code' => true, 'can_terminal' => true, 'is_active' => true, 'notes' => 'Fullstack Engineer JPP'],
            ['telegram_id' => '2219283745', 'name' => 'Hendra Wijaya', 'username' => '@hendra_w', 'email' => 'hendra@expressa.id', 'role' => 'admin', 'can_code' => true, 'can_terminal' => true, 'is_active' => true, 'notes' => 'Co-Architect ERP'],
            ['telegram_id' => '1119283746', 'name' => 'Indah Permata', 'username' => '@indah_p', 'email' => 'indah@expressa.id', 'role' => 'reviewer', 'can_code' => false, 'can_terminal' => false, 'is_active' => true, 'notes' => 'Database Administrator Reviewer'],
            ['telegram_id' => '9919283747', 'name' => 'Joko Susilo', 'username' => '@joko_s', 'email' => 'joko@expressa.id', 'role' => 'developer', 'can_code' => true, 'can_terminal' => false, 'is_active' => false, 'notes' => 'Mobile Flutter Dev']
        ];

        foreach ($samples as $s) {
            GlobalUser::updateOrCreate(
                ['global_user_telegram_id' => $s['telegram_id']],
                [
                    'global_user_nama' => $s['name'],
                    'global_user_username' => $s['username'],
                    'global_user_email' => $s['email'],
                    'global_user_role' => $s['role'],
                    'global_user_can_code' => $s['can_code'],
                    'global_user_can_terminal' => $s['can_terminal'],
                    'global_user_is_active' => $s['is_active'],
                    'global_user_notes' => $s['notes'],
                    'global_user_allowed_commands' => $s['role'] === 'admin' ? ['*'] : ($s['role'] === 'developer' ? ['/jhustyn', '/erp', '/status', '/stop', '/start'] : ['/status']),
                ]
            );
        }

        // 3. Activity Log
        GlobalActivityLog::create([
            'global_activity_action' => 'database_initialized',
            'global_activity_description' => 'Database PostgreSQL berhasil diinisialisasi dengan data user dan command.',
            'global_activity_sender' => 'system',
            'global_activity_status' => 'success',
        ]);
    }
}
