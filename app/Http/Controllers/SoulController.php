<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use App\Models\GlobalActivityLog;
use Inertia\Inertia;
use Illuminate\Http\Request;

class SoulController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index()
    {
        $soul = $this->hermes->getSoulData();

        return Inertia::render('Soul/Index', [
            'soul' => $soul,
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $saved = $this->hermes->saveSoul($validated['content']);

        if ($saved) {
            GlobalActivityLog::create([
                'global_activity_action' => 'soul_update',
                'global_activity_description' => "Memperbarui persona sistem SOUL.md",
                'global_activity_status' => 'success',
            ]);

            return redirect()->back()->with('success', 'Persona sistem SOUL.md berhasil diperbarui.');
        }

        return redirect()->back()->with('error', 'Gagal menyimpan SOUL.md.');
    }
}
