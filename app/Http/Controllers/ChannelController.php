<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use Inertia\Inertia;
use Illuminate\Http\Request;

class ChannelController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index(Request $request)
    {
        $profile = $request->query('profile', 'default');
        $data = $this->hermes->getMessagingPlatforms($profile);
        $platforms = $data['platforms'] ?? [];

        $profiles = array_merge([['name' => 'default', 'path' => '']], $this->hermes->getProfiles());
        $uniqueProfiles = [];
        foreach ($profiles as $p) {
            $uniqueProfiles[$p['name']] = $p;
        }

        return Inertia::render('Channels/Index', [
            'platforms' => $platforms,
            'envPath' => $data['env_path'] ?? '',
            'startCommand' => $data['gateway_start_command'] ?? '',
            'profiles' => array_values($uniqueProfiles),
            'currentProfile' => $profile,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $profile = $request->input('profile', 'default');
        $payload = [
            'enabled' => $request->has('enabled') ? (bool) $request->input('enabled') : null,
            'env' => $request->input('env', []),
            'clear_env' => $request->input('clear_env', []),
        ];

        // Filter out nulls
        $payload = array_filter($payload, fn($val) => $val !== null);

        $res = $this->hermes->updateMessagingPlatform($id, $payload, $profile);
        return response()->json($res ?? ['ok' => false]);
    }

    public function test(Request $request, string $id)
    {
        $profile = $request->input('profile', 'default');
        $res = $this->hermes->testMessagingPlatform($id, $profile);
        return response()->json($res ?? ['ok' => false, 'message' => 'Connection test failed']);
    }
}
