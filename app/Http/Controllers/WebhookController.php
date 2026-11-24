<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use Inertia\Inertia;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index(Request $request)
    {
        $profile = $request->query('profile', 'default');
        $webhooks = $this->hermes->getWebhooks($profile);

        $profiles = array_merge([['name' => 'default', 'path' => '']], $this->hermes->getProfiles());
        $uniqueProfiles = [];
        foreach ($profiles as $p) {
            $uniqueProfiles[$p['name']] = $p;
        }

        return Inertia::render('Webhooks/Index', [
            'webhooks' => $webhooks,
            'profiles' => array_values($uniqueProfiles),
            'currentProfile' => $profile,
        ]);
    }
}
