<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use Inertia\Inertia;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index(Request $request)
    {
        $profile = $request->query('profile', 'default');
        $modelInfo = $this->hermes->getModelInfo($profile);
        $sessions = $this->hermes->getSessions();

        $profiles = array_merge([['name' => 'default', 'path' => '']], $this->hermes->getProfiles());
        $uniqueProfiles = [];
        foreach ($profiles as $p) {
            $uniqueProfiles[$p['name']] = $p;
        }

        return Inertia::render('Chat/Index', [
            'currentProfile' => $profile,
            'modelInfo' => $modelInfo,
            'sessions' => array_slice($sessions, 0, 20),
            'profiles' => array_values($uniqueProfiles),
        ]);
    }

    public function send(Request $request)
    {
        $message = (string) $request->input('message', '');
        $sessionId = $request->input('session_id');
        $profile = $request->input('profile', 'default');

        if (empty(trim($message))) {
            return response()->json(['error' => 'Pesan tidak boleh kosong'], 422);
        }

        $res = $this->hermes->executeChatMessage($message, $sessionId, $profile);
        return response()->json($res);
    }

    public function getSessionMessages(Request $request, string $id)
    {
        $detail = $this->hermes->getSessionDetail($id);
        return response()->json($detail ?? ['messages' => []]);
    }
}
