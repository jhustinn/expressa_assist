<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('global.agent_profiles', function (Blueprint $table) {
            $table->id('agent_profile_id');
            $table->string('slug')->unique(); // 'jhustyn'
            $table->string('name'); // 'Jhustyn'
            $table->string('role_title')->default('Lead Enterprise Architect & Senior AI Engineer');
            $table->string('project_name')->default('ERP JPP (Jasa Pelayanan Pabrik - PT Petrokimia Gresik)');
            $table->string('principal_partner')->default('Jesa');
            $table->string('workspace_path')->default('E:\App\SWA\JPP-ERP');
            $table->string('repository_url')->nullable()->default('https://github.com/expressa-repo/petrokimia-gersik.git');
            $table->string('branch')->default('main');
            $table->string('model_default')->default('gemini-flash-lite-latest');
            $table->string('status')->default('active'); // active, idle, busy
            $table->string('engine_platform')->default('Hermes / Expressa Assist');

            // 1. Ilmu & Kemampuan (Knowledge & Skills)
            $table->jsonb('knowledge_skills')->nullable();
            $table->jsonb('business_modules')->nullable();

            // 2. Project Responsibilities & Paths
            $table->jsonb('responsibility_paths')->nullable();

            // 3. User Access Control
            $table->jsonb('allowed_users')->nullable(); // user emails / usernames
            $table->jsonb('allowed_telegram_ids')->nullable(); // Telegram user IDs

            // 4. File Specification
            $table->text('system_prompt')->nullable();
            $table->longText('markdown_content')->nullable(); // Content of Jhustyn.md
            $table->string('markdown_file_path')->nullable(); // Path on disk

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global.agent_profiles');
    }
};
