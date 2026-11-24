<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use Inertia\Inertia;
use Illuminate\Http\Request;

class McpController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index(Request $request)
    {
        $profile = $request->query('profile', 'default');
        $servers = $this->hermes->getMcpServers($profile);
        $catalog = $this->hermes->getMcpCatalog($profile);

        $profiles = array_merge([['name' => 'default', 'path' => '']], $this->hermes->getProfiles());
        $uniqueProfiles = [];
        foreach ($profiles as $p) {
            $uniqueProfiles[$p['name']] = $p;
        }

        return Inertia::render('Mcp/Index', [
            'servers' => $servers,
            'catalog' => $catalog['entries'] ?? [],
            'profiles' => array_values($uniqueProfiles),
            'currentProfile' => $profile,
        ]);
    }
}
