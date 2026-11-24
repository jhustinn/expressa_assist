<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use App\Models\GlobalActivityLog;
use Inertia\Inertia;
use Illuminate\Http\Request;

class MemoryController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index()
    {
        $memory = $this->hermes->getMemoryData();

        return Inertia::render('Memory/Index', [
            'memory' => $memory,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:agent,user',
            'entry' => 'required|string|max:500',
        ]);

        $filePath = $validated['type'] === 'agent'
            ? $this->hermes->getHermesHome() . DIRECTORY_SEPARATOR . 'MEMORY.md'
            : $this->hermes->getHermesHome() . DIRECTORY_SEPARATOR . 'USER.md';

        $current = file_exists($filePath) ? file_get_contents($filePath) : '';
        $newEntry = "- " . trim($validated['entry']);
        $updated = trim($current) . "\n" . $newEntry . "\n";

        file_put_contents($filePath, $updated);

        GlobalActivityLog::create([
            'global_activity_action' => 'memory_entry_add',
            'global_activity_description' => "Menambahkan entri memori baru ({$validated['type']}): {$validated['entry']}",
            'global_activity_status' => 'success',
        ]);

        return redirect()->back()->with('success', 'Entri memori berhasil disimpan ke ' . basename($filePath));
    }
}
