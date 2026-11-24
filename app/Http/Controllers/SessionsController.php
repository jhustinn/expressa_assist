<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use Inertia\Inertia;
use Illuminate\Http\Request;

class SessionsController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index(Request $request)
    {
        $sessions = $this->hermes->getSessions();

        $stats = [
            'total' => count($sessions),
            'active' => count(array_filter($sessions, fn($s) => $s['is_active'] ?? false)),
            'totalTokens' => array_sum(array_column($sessions, 'tokensInput')) + array_sum(array_column($sessions, 'tokensOutput')),
            'totalCost' => array_sum(array_column($sessions, 'cost')),
        ];

        return Inertia::render('Sessions/Index', [
            'sessions' => $sessions,
            'stats' => $stats,
        ]);
    }

    public function show(string $id)
    {
        $session = $this->hermes->getSessionDetail($id);

        if (!$session) {
            return redirect()->route('sessions.index')->with('error', 'Session tidak ditemukan.');
        }

        return Inertia::render('Sessions/Show', [
            'session' => $session,
        ]);
    }
}
