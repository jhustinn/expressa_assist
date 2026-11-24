<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use App\Models\GlobalActivityLog;
use App\Models\HermesActivityLog;
use Inertia\Inertia;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index()
    {
        $status = $this->hermes->getGatewayStatus();
        $logs = $this->hermes->getRecentLogs(60);
        $activityLogs = HermesActivityLog::latest()->take(25)->get();

        return Inertia::render('Monitoring', compact('status', 'logs', 'activityLogs'));
    }

    public function statusJson()
    {
        return response()->json($this->hermes->getGatewayStatus());
    }

    public function logsJson()
    {
        return response()->json([
            'logs' => $this->hermes->getRecentLogs(100),
        ]);
    }

    public function action(Request $request)
    {
        $action = $request->input('action'); // start, stop, restart

        if ($action === 'start') {
            $this->hermes->startGateway();
            $msg = 'Hermes Gateway berhasil dijalankan!';
        } elseif ($action === 'stop') {
            $this->hermes->stopGateway();
            $msg = 'Hermes Gateway dihentikan.';
        } elseif ($action === 'restart') {
            $this->hermes->restartGateway();
            $msg = 'Hermes Gateway berhasil di-restart!';
        } else {
            return response()->json(['success' => false, 'message' => 'Action tidak valid'], 400);
        }

        GlobalActivityLog::create([
            'global_activity_action' => 'gateway_action',
            'global_activity_sender' => 'Dashboard Web',
            'global_activity_description' => "Aksi Gateway: {$action}",
            'global_activity_status' => 'success',
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return redirect()->back()->with('success', $msg);
    }

    public function testApi(Request $request)
    {
        $request->validate([
            'provider' => 'required|string',
            'api_key' => 'required|string',
        ]);

        $result = $this->hermes->testApiKey(
            $request->input('provider'),
            $request->input('api_key')
        );

        return response()->json($result);
    }
}
