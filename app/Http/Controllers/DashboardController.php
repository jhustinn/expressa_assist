<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use App\Models\TelegramCommand;
use App\Models\HermesActivityLog;
use Inertia\Inertia;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index()
    {
        $gatewayStatus = $this->hermes->getGatewayStatus();
        $config = $this->hermes->getConfig();
        $env = $this->hermes->getEnv();
        $profiles = $this->hermes->getProfiles();
        $commands = TelegramCommand::all();
        $recentLogs = $this->hermes->getRecentLogs(30);
        $activityLogs = HermesActivityLog::latest()->take(10)->get();
        $overviewData = $this->hermes->getOverviewData();
        $recentSessions = array_slice($this->hermes->getSessions(), 0, 5);

        return Inertia::render('Dashboard', compact(
            'gatewayStatus',
            'config',
            'env',
            'profiles',
            'commands',
            'recentLogs',
            'activityLogs',
            'overviewData',
            'recentSessions'
        ));
    }
}
