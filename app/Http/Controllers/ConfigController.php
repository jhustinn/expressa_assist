<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use App\Models\GlobalActivityLog;
use App\Models\HermesActivityLog;
use Inertia\Inertia;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index()
    {
        $config = $this->hermes->getConfig();
        $env = $this->hermes->getEnv();
        $configSections = $this->hermes->getConfigSections();

        return Inertia::render('Config', compact('config', 'env', 'configSections'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'model_default' => 'required|string',
            'model_provider' => 'required|string',
        ]);

        $config = $this->hermes->getConfig();
        $config['model']['default'] = $request->input('model_default');
        $config['model']['provider'] = $request->input('model_provider');

        if ($request->filled('model_base_url')) {
            $config['model']['base_url'] = $request->input('model_base_url');
        }

        if ($request->filled('model_api_key')) {
            $config['model']['api_key'] = $request->input('model_api_key');
        }

        if ($request->filled('terminal_cwd')) {
            $config['terminal']['cwd'] = $request->input('terminal_cwd');
        }

        $this->hermes->saveConfig($config);

        // Update .env keys
        $envUpdates = [];
        if ($request->has('groq_api_key')) {
            $envUpdates['GROQ_API_KEY'] = $request->input('groq_api_key');
        }
        if ($request->has('google_api_key')) {
            $envUpdates['GOOGLE_API_KEY'] = $request->input('google_api_key');
            $envUpdates['GEMINI_API_KEY'] = $request->input('google_api_key');
        }
        if ($request->has('openrouter_api_key')) {
            $envUpdates['OPENROUTER_API_KEY'] = $request->input('openrouter_api_key');
        }
        if ($request->has('telegram_bot_token')) {
            $envUpdates['TELEGRAM_BOT_TOKEN'] = $request->input('telegram_bot_token');
        }
        if ($request->has('telegram_allowed_users')) {
            $envUpdates['TELEGRAM_ALLOWED_USERS'] = $request->input('telegram_allowed_users');
        }

        if (!empty($envUpdates)) {
            $this->hermes->updateEnv($envUpdates);
        }

        GlobalActivityLog::create([
            'global_activity_action' => 'config_update',
            'global_activity_sender' => 'Dashboard Web',
            'global_activity_description' => 'Konfigurasi Hermes & API Keys diperbarui',
            'global_activity_status' => 'success',
            'global_activity_metadata' => ['model' => $config['model']['default'], 'provider' => $config['model']['provider']],
        ]);

        return redirect()->route('config.index')->with('success', 'Konfigurasi berhasil disimpan!');
    }
}
