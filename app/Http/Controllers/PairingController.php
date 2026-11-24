<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use Inertia\Inertia;
use Illuminate\Http\Request;

class PairingController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index(Request $request)
    {
        $profile = $request->query('profile', 'default');
        $pairing = $this->hermes->getPairing($profile);

        $profiles = array_merge([['name' => 'default', 'path' => '']], $this->hermes->getProfiles());
        $uniqueProfiles = [];
        foreach ($profiles as $p) {
            $uniqueProfiles[$p['name']] = $p;
        }

        return Inertia::render('Pairing/Index', [
            'pending' => $pairing['pending'] ?? [],
            'approved' => $pairing['approved'] ?? [],
            'profiles' => array_values($uniqueProfiles),
            'currentProfile' => $profile,
        ]);
    }

    public function approve(Request $request)
    {
        $profile = $request->input('profile', 'default');
        $platform = $request->input('platform');
        $requestId = $request->input('request_id');

        $res = $this->hermes->approvePairing($platform, $requestId, $profile);
        return response()->json($res ?? ['ok' => false]);
    }

    public function revoke(Request $request)
    {
        $profile = $request->input('profile', 'default');
        $platform = $request->input('platform');
        $userId = $request->input('user_id');

        $res = $this->hermes->revokePairing($platform, $userId, $profile);
        return response()->json($res ?? ['ok' => false]);
    }
}
