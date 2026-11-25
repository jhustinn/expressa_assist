<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use App\Models\AiModel;
use App\Models\AgentProfile;
use App\Models\GlobalActivityLog;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ModelController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index(Request $request)
    {
        $profile = $request->query('profile', 'default');
        
        // Ensure default profiles exist in database
        $this->ensureDefaultAgentProfiles();

        // 1. All AI Models from database
        $models = AiModel::orderBy('provider')->orderBy('name')->get();

        // 2. All Agent Profiles from database
        $agentProfiles = AgentProfile::where('is_active', true)->orderBy('name')->get();

        // 3. Fallback active model info from hermes service
        $modelInfo = $this->hermes->getModelInfo($profile);
        $modelOptions = $this->hermes->getModelOptions($profile);

        // 4. Combined profiles list for selector
        $profiles = array_merge([['name' => 'default', 'path' => '']], $this->hermes->getProfiles());
        $uniqueProfiles = [];
        foreach ($profiles as $p) {
            $uniqueProfiles[$p['name']] = $p;
        }

        // Available providers metadata
        $providers = [
            ['id' => 'gemini', 'name' => 'Google Gemini', 'env' => 'GOOGLE_API_KEY', 'default_base' => 'https://generativelanguage.googleapis.com'],
            ['id' => 'groq', 'name' => 'Groq LPU', 'env' => 'GROQ_API_KEY', 'default_base' => 'https://api.groq.com/openai/v1'],
            ['id' => 'openrouter', 'name' => 'OpenRouter', 'env' => 'OPENROUTER_API_KEY', 'default_base' => 'https://openrouter.ai/api/v1'],
            ['id' => 'openai', 'name' => 'OpenAI', 'env' => 'OPENAI_API_KEY', 'default_base' => 'https://api.openai.com/v1'],
            ['id' => 'anthropic', 'name' => 'Anthropic Claude', 'env' => 'ANTHROPIC_API_KEY', 'default_base' => 'https://api.anthropic.com/v1'],
            ['id' => 'ollama', 'name' => 'Ollama (Local)', 'env' => null, 'default_base' => 'http://localhost:11434/v1'],
            ['id' => 'custom', 'name' => 'Custom Endpoint', 'env' => null, 'default_base' => ''],
        ];

        return Inertia::render('Models/Index', [
            'models' => $models,
            'agentProfiles' => $agentProfiles,
            'providers' => $providers,
            'modelInfo' => $modelInfo,
            'modelOptions' => $modelOptions,
            'profiles' => array_values($uniqueProfiles),
            'currentProfile' => $profile,
        ]);
    }

    /**
     * Store a new AI model in the database
     */
    public function storeModel(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'model_key' => 'required|string|max:150|unique:global.ai_models,model_key',
            'provider' => 'required|string|in:gemini,groq,openrouter,openai,anthropic,ollama,custom',
            'base_url' => 'nullable|string|max:255',
            'api_key_env' => 'nullable|string|max:100',
            'context_window' => 'required|integer|min:1000',
            'max_output_tokens' => 'required|integer|min:100',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        if (!empty($validated['is_default'])) {
            AiModel::where('is_default', true)->update(['is_default' => false]);
        }

        $model = AiModel::create($validated);

        GlobalActivityLog::create([
            'type' => 'model_created',
            'sender' => auth()->user()->global_user_nama ?? 'Admin',
            'message' => "Model AI baru '{$model->name}' ({$model->model_key}) berhasil ditambahkan ke database.",
        ]);

        return redirect()->route('models.index')->with('success', "Model AI '{$model->name}' berhasil ditambahkan ke database!");
    }

    /**
     * Update an existing AI model
     */
    public function updateModel(Request $request, $id)
    {
        $model = AiModel::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'model_key' => 'required|string|max:150|unique:global.ai_models,model_key,' . $id . ',ai_model_id',
            'provider' => 'required|string|in:gemini,groq,openrouter,openai,anthropic,ollama,custom',
            'base_url' => 'nullable|string|max:255',
            'api_key_env' => 'nullable|string|max:100',
            'context_window' => 'required|integer|min:1000',
            'max_output_tokens' => 'required|integer|min:100',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        if (!empty($validated['is_default']) && !$model->is_default) {
            AiModel::where('is_default', true)->update(['is_default' => false]);
        }

        $model->update($validated);

        GlobalActivityLog::create([
            'type' => 'model_updated',
            'sender' => auth()->user()->global_user_nama ?? 'Admin',
            'message' => "Model AI '{$model->name}' ({$model->model_key}) telah diperbarui.",
        ]);

        return redirect()->route('models.index')->with('success', "Model AI '{$model->name}' berhasil diperbarui!");
    }

    /**
     * Delete an AI model
     */
    public function deleteModel($id)
    {
        $model = AiModel::findOrFail($id);

        // Check if currently assigned as default
        if ($model->is_default) {
            return redirect()->route('models.index')->with('error', "Model default sistem tidak dapat dihapus!");
        }

        $name = $model->name;
        $model->delete();

        GlobalActivityLog::create([
            'type' => 'model_deleted',
            'sender' => auth()->user()->global_user_nama ?? 'Admin',
            'message' => "Model AI '{$name}' telah dihapus dari database.",
        ]);

        return redirect()->route('models.index')->with('success', "Model AI '{$name}' telah dihapus!");
    }

    /**
     * Assign a model to an Agent profile
     */
    public function assignAgentModel(Request $request)
    {
        $validated = $request->validate([
            'agent_slug' => 'required|string',
            'model_key' => 'required|string',
        ]);

        $agent = AgentProfile::where('slug', $validated['agent_slug'])->first();
        if (!$agent) {
            return response()->json([
                'status' => 'error',
                'message' => "Agent profile '{$validated['agent_slug']}' tidak ditemukan di database.",
            ], 404);
        }

        $model = AiModel::where('model_key', $validated['model_key'])->first();
        $modelName = $model ? $model->name : $validated['model_key'];

        $agent->model_default = $validated['model_key'];
        $agent->save();

        // Also sync profile config in HermesService
        $this->hermes->setModelAssignment([
            'model' => $validated['model_key'],
            'provider' => $model?->provider ?? 'gemini',
        ], $validated['agent_slug']);

        GlobalActivityLog::create([
            'type' => 'agent_model_assigned',
            'sender' => auth()->user()->global_user_nama ?? 'Admin',
            'message' => "Model untuk Agent {$agent->name} ({$agent->slug}) diubah ke: {$modelName} ({$validated['model_key']})",
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Berhasil! Agent {$agent->name} kini menggunakan model '{$modelName}'.",
            'agent' => [
                'slug' => $agent->slug,
                'name' => $agent->name,
                'model_default' => $agent->model_default,
            ],
            'model' => $model,
        ]);
    }

    /**
     * Test inference ping to a model
     */
    public function testModel(Request $request)
    {
        $validated = $request->validate([
            'model_key' => 'required|string',
            'prompt' => 'nullable|string',
        ]);

        $modelKey = $validated['model_key'];
        $prompt = $validated['prompt'] ?: 'Halo, perkenalkan identitas model dan kapabilitasmu secara singkat dalam 2 kalimat.';

        $model = AiModel::where('model_key', $modelKey)->first();
        $envVars = $this->hermes->getEnvironmentVariables();

        $startTime = microtime(true);
        $reply = null;
        $error = null;

        $provider = $model ? $model->provider : 'groq';

        try {
            if ($provider === 'groq') {
                $apiKey = $envVars['GROQ_API_KEY'] ?? env('GROQ_API_KEY');
                if (empty($apiKey)) {
                    throw new \Exception("GROQ_API_KEY belum disetel di .env");
                }
                $res = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(20)->withoutVerifying()->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model' => $modelKey,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'max_tokens' => 300,
                ]);
                if (!$res->successful()) {
                    throw new \Exception("Groq error: " . $res->body());
                }
                $reply = $res->json('choices.0.message.content');
            } elseif ($provider === 'gemini') {
                $apiKey = $envVars['GOOGLE_API_KEY'] ?? $envVars['GEMINI_API_KEY'] ?? env('GOOGLE_API_KEY');
                if (empty($apiKey)) {
                    throw new \Exception("GOOGLE_API_KEY belum disetel di .env");
                }
                $res = Http::timeout(20)->withoutVerifying()->post("https://generativelanguage.googleapis.com/v1beta/models/{$modelKey}:generateContent?key={$apiKey}", [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                ]);
                if (!$res->successful()) {
                    throw new \Exception("Gemini error: " . $res->body());
                }
                $reply = $res->json('candidates.0.content.parts.0.text');
            } elseif ($provider === 'ollama') {
                $baseUrl = $model?->base_url ?: 'http://localhost:11434/v1';
                $res = Http::timeout(20)->withoutVerifying()->post("{$baseUrl}/chat/completions", [
                    'model' => $modelKey,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'max_tokens' => 300,
                ]);
                if (!$res->successful()) {
                    throw new \Exception("Ollama error: " . $res->body());
                }
                $reply = $res->json('choices.0.message.content');
            } else {
                // Generic OpenAI compatible
                $baseUrl = $model?->base_url ?: 'https://openrouter.ai/api/v1';
                $envKeyName = $model?->api_key_env ?: 'OPENROUTER_API_KEY';
                $apiKey = $envVars[$envKeyName] ?? env($envKeyName);
                if (empty($apiKey)) {
                    throw new \Exception("API Key {$envKeyName} belum disetel di .env");
                }
                $res = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(20)->withoutVerifying()->post("{$baseUrl}/chat/completions", [
                    'model' => $modelKey,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'max_tokens' => 300,
                ]);
                if (!$res->successful()) {
                    throw new \Exception("Provider error: " . $res->body());
                }
                $reply = $res->json('choices.0.message.content');
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        $latencyMs = round((microtime(true) - $startTime) * 1000);

        if ($error) {
            return response()->json([
                'status' => 'error',
                'message' => $error,
                'latency_ms' => $latencyMs,
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'reply' => $reply,
            'latency_ms' => $latencyMs,
            'model' => $modelKey,
        ]);
    }

    /**
     * Legacy set method
     */
    public function set(Request $request)
    {
        $profile = $request->input('profile', 'default');
        $res = $this->hermes->setModelAssignment($request->all(), $profile);
        return response()->json($res ?? ['ok' => false]);
    }

    /**
     * Ensure default agent profiles exist
     */
    private function ensureDefaultAgentProfiles()
    {
        if (!AgentProfile::where('slug', 'jhustyn')->exists()) {
            AgentProfile::create([
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
            ]);
        }

        if (!AgentProfile::where('slug', 'default')->exists()) {
            AgentProfile::create([
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
            ]);
        }
    }
}
