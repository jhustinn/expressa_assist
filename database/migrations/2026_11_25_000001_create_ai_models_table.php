<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('global.ai_models', function (Blueprint $table) {
            $table->id('ai_model_id');
            $table->string('name');
            $table->string('model_key')->unique();
            $table->string('provider'); // gemini, groq, openrouter, anthropic, openai, ollama, custom
            $table->string('base_url')->nullable();
            $table->string('api_key_env')->nullable();
            $table->integer('context_window')->default(128000);
            $table->integer('max_output_tokens')->default(8192);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Seed default initial models
        DB::table('global.ai_models')->insert([
            [
                'name' => 'Google Gemini Flash Lite',
                'model_key' => 'gemini-flash-lite-latest',
                'provider' => 'gemini',
                'base_url' => null,
                'api_key_env' => 'GOOGLE_API_KEY',
                'context_window' => 1048576,
                'max_output_tokens' => 8192,
                'description' => 'Ultra-fast lightweight model for real-time chat, tool calls & fast processing.',
                'is_active' => true,
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Google Gemini 2.5 Pro',
                'model_key' => 'gemini-2.5-pro',
                'provider' => 'gemini',
                'base_url' => null,
                'api_key_env' => 'GOOGLE_API_KEY',
                'context_window' => 2097152,
                'max_output_tokens' => 8192,
                'description' => 'Flagship reasoning & multi-step coding model from Google DeepMind.',
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Groq Qwen 3.8 27B',
                'model_key' => 'qwen/qwen3.8-27b',
                'provider' => 'groq',
                'base_url' => 'https://api.groq.com/openai/v1',
                'api_key_env' => 'GROQ_API_KEY',
                'context_window' => 131072,
                'max_output_tokens' => 8192,
                'description' => 'Ultra low-latency LPU inference on Groq, exceptional at coding and architecture reasoning.',
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Groq GPT-OSS 120B',
                'model_key' => 'openai/gpt-oss-120b',
                'provider' => 'groq',
                'base_url' => 'https://api.groq.com/openai/v1',
                'api_key_env' => 'GROQ_API_KEY',
                'context_window' => 131072,
                'max_output_tokens' => 8192,
                'description' => 'High-capacity open-weights model hosted on Groq LPU cluster.',
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Meta Llama 3.3 70B Versatile',
                'model_key' => 'llama-3.3-70b-versatile',
                'provider' => 'groq',
                'base_url' => 'https://api.groq.com/openai/v1',
                'api_key_env' => 'GROQ_API_KEY',
                'context_window' => 131072,
                'max_output_tokens' => 8192,
                'description' => 'Meta flagship 70B open model with broad task capabilities.',
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'DeepSeek R1',
                'model_key' => 'deepseek/deepseek-r1',
                'provider' => 'openrouter',
                'base_url' => 'https://openrouter.ai/api/v1',
                'api_key_env' => 'OPENROUTER_API_KEY',
                'context_window' => 128000,
                'max_output_tokens' => 8192,
                'description' => 'Advanced reasoning model with chain-of-thought verification via OpenRouter.',
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Anthropic Claude 3.7 Sonnet',
                'model_key' => 'claude-3-7-sonnet',
                'provider' => 'anthropic',
                'base_url' => 'https://api.anthropic.com/v1',
                'api_key_env' => 'ANTHROPIC_API_KEY',
                'context_window' => 200000,
                'max_output_tokens' => 8192,
                'description' => 'Anthropic leading hybrid reasoning and coding agent model.',
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ollama Local Qwen 2.5 Coder',
                'model_key' => 'qwen2.5-coder:7b',
                'provider' => 'ollama',
                'base_url' => 'http://localhost:11434/v1',
                'api_key_env' => null,
                'context_window' => 32768,
                'max_output_tokens' => 4096,
                'description' => 'Self-hosted local offline LLM running via Ollama engine on port 11434.',
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Ensure default and jhustyn profiles exist in global.agent_profiles
        $hasJhustyn = DB::table('global.agent_profiles')->where('slug', 'jhustyn')->exists();
        if (!$hasJhustyn) {
            DB::table('global.agent_profiles')->insert([
                'slug' => 'jhustyn',
                'name' => 'Agent Jhustyn',
                'role_title' => 'Lead Enterprise Architect & Senior AI Engineer',
                'project_name' => 'ERP JPP (Jasa Pelayanan Pabrik - PT Petrokimia Gresik)',
                'principal_partner' => 'Jesa',
                'workspace_path' => 'E:\\App\\SWA\\JPP-ERP',
                'repository_url' => 'https://github.com/expressa-repo/petrokimia-gersik.git',
                'branch' => 'main',
                'model_default' => 'qwen/qwen3.8-27b',
                'status' => 'active',
                'engine_platform' => 'Hermes / Expressa Assist',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $hasDefault = DB::table('global.agent_profiles')->where('slug', 'default')->exists();
        if (!$hasDefault) {
            DB::table('global.agent_profiles')->insert([
                'slug' => 'default',
                'name' => 'Expressa Core Agent',
                'role_title' => 'Autonomous General AI Assistant',
                'project_name' => 'Expressa Assist Global Operations',
                'principal_partner' => 'System',
                'workspace_path' => 'E:\\hermes',
                'repository_url' => null,
                'branch' => 'main',
                'model_default' => 'gemini-flash-lite-latest',
                'status' => 'active',
                'engine_platform' => 'Hermes / Expressa Assist',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global.ai_models');
    }
};
