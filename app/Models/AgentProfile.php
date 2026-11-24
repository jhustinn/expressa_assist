<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class AgentProfile extends Model
{
    use HasFactory;

    protected $table = 'global.agent_profiles';
    protected $primaryKey = 'agent_profile_id';

    protected $fillable = [
        'slug',
        'name',
        'role_title',
        'project_name',
        'principal_partner',
        'workspace_path',
        'repository_url',
        'branch',
        'model_default',
        'status',
        'engine_platform',
        'knowledge_skills',
        'business_modules',
        'responsibility_paths',
        'allowed_users',
        'allowed_telegram_ids',
        'system_prompt',
        'markdown_content',
        'markdown_file_path',
        'is_active',
    ];

    protected $casts = [
        'knowledge_skills' => 'array',
        'business_modules' => 'array',
        'responsibility_paths' => 'array',
        'allowed_users' => 'array',
        'allowed_telegram_ids' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get default Jhustyn instance or create if not exists
     */
    public static function getJhustyn(): self
    {
        $agent = self::where('slug', 'jhustyn')->first();
        if ($agent) {
            return $agent;
        }

        // Default initial markdown path
        $mdPath = 'E:\\hermes\\profiles\\jhustyn\\Jhustyn.md';
        $mdContent = '';
        if (File::exists($mdPath)) {
            $mdContent = File::get($mdPath);
        }

        return self::create([
            'slug' => 'jhustyn',
            'name' => 'Jhustyn',
            'role_title' => 'Lead Enterprise Architect & Senior AI Engineer',
            'project_name' => 'ERP JPP (Jasa Pelayanan Pabrik - PT Petrokimia Gresik)',
            'principal_partner' => 'Jesa',
            'workspace_path' => 'E:\\App\\SWA\\JPP-ERP',
            'repository_url' => 'https://github.com/expressa-repo/petrokimia-gersik.git',
            'branch' => 'main',
            'model_default' => 'gemini-flash-lite-latest',
            'status' => 'active',
            'engine_platform' => 'Hermes / Expressa Assist',
            'knowledge_skills' => [
                'ERP JPP Business Architecture',
                'EBAP 8 Komponen Calculation',
                'Clean Architecture Node.js / Express',
                'Prisma Schema & Atomic Transactions',
                'Vue 3 Composition API & Tailwind CSS',
                'PostgreSQL Double-Entry Accounting',
                'MoM & BRD Document Ingestion',
                'Conventional Commits & Git Automation',
                'Telegram Bot Orchestration',
            ],
            'business_modules' => [
                [
                    'name' => 'Sales & Distribution',
                    'scope' => 'RFQ Masuk -> Kalkulasi EBAP 8 Komponen -> Penawaran DOF -> Negosiasi -> Sales Order (SO)'
                ],
                [
                    'name' => 'Purchasing & Procurement',
                    'scope' => 'Pengadaan Eksternal, PR SAP, OE Rendal, Evaluasi Vendor, BAHPP, BAPP Vendor & SPK/PO'
                ],
                [
                    'name' => 'Inventory & Warehouse',
                    'scope' => 'Multi-warehouse & Storage Bins, Reservasi Material per Service Order, FIFO Valuation'
                ],
                [
                    'name' => 'Finance, Billing & Accounting',
                    'scope' => 'BAPP Konsumen, Surat Jalan DO, Verifikasi Rendal & SAP, Faktur Pajak, Kwitansi, CoA & Jurnal Otomatis'
                ],
                [
                    'name' => 'HR & Multi-Tier Approval Matrix',
                    'scope' => 'Otorisasi batas margin >= 15%, plafon persetujuan (<=100jt, 100-500jt, >500jt), Audit Trail Log'
                ]
            ],
            'responsibility_paths' => [
                'E:\\App\\SWA\\JPP-ERP',
                'E:\\App\\SWA\\JPP-ERP\\BE\\src\\controllers',
                'E:\\App\\SWA\\JPP-ERP\\BE\\src\\services',
                'E:\\App\\SWA\\JPP-ERP\\BE\\src\\prisma\\schema.prisma',
                'E:\\App\\SWA\\JPP-ERP\\FE\\src\\views',
                'E:\\App\\SWA\\JPP-ERP\\FE\\src\\components',
            ],
            'allowed_users' => [
                'jesa@expressa.id',
                'admin@expressa.id',
            ],
            'allowed_telegram_ids' => [
                '8831937787',
            ],
            'system_prompt' => "You are Jhustyn, Senior Enterprise Architect & Autonomous Lead AI Engineer for the \"ERP JPP\" project (PT Petrokimia Gresik).\nYou report directly to Jesa (Principal Stakeholder).\nYour workspace root is located at \"E:\\App\\SWA\\JPP-ERP\".\nRemote repository: \"https://github.com/expressa-repo/petrokimia-gersik.git\".\n\nDirectives:\n1. Speak in clean, assertive, professional, yet casual Indonesian (no fluff, zero basa-basi).\n2. Prioritize concrete code edits, atomic database transactions, and local terminal verification (vite build / tests) over lengthy discussions.\n3. When given Meeting Minutes (MoM), BRD, or task requirements, immediately translate them into schema migrations, backend transactions, and frontend reactive components.\n4. Always follow the Clean Architecture: Controller -> Service/Action -> Repository -> Prisma ORM Model.\n5. After completing tasks, stage modified files, craft a proper conventional commit, and push to origin. Send a concise summary of modified files, applied logic, and commit hash.",
            'markdown_content' => $mdContent,
            'markdown_file_path' => $mdPath,
            'is_active' => true,
        ]);
    }

    /**
     * Sync and write markdown_content back to disk
     */
    public function syncToDisk(): bool
    {
        if (empty($this->markdown_file_path) || empty($this->markdown_content)) {
            return false;
        }

        try {
            $dir = dirname($this->markdown_file_path);
            if (!File::isDirectory($dir)) {
                File::makeDirectory($dir, 0755, true, true);
            }
            File::put($this->markdown_file_path, $this->markdown_content);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Reload raw markdown from disk into model
     */
    public function syncFromDisk(): bool
    {
        if (empty($this->markdown_file_path) || !File::exists($this->markdown_file_path)) {
            return false;
        }

        try {
            $this->markdown_content = File::get($this->markdown_file_path);
            $this->save();
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
