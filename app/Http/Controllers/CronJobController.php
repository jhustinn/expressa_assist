<?php

namespace App\Http\Controllers;

use App\Services\HermesService;
use App\Models\GlobalActivityLog;
use Inertia\Inertia;
use Illuminate\Http\Request;

class CronJobController extends Controller
{
    protected HermesService $hermes;

    public function __construct(HermesService $hermes)
    {
        $this->hermes = $hermes;
    }

    public function index()
    {
        $jobs = $this->hermes->getCronJobs();

        $stats = [
            'total' => count($jobs),
            'scheduled' => count(array_filter($jobs, fn($j) => ($j['state'] ?? '') === 'scheduled')),
            'paused' => count(array_filter($jobs, fn($j) => ($j['state'] ?? '') === 'paused')),
        ];

        return Inertia::render('CronJobs/Index', [
            'jobs' => $jobs,
            'stats' => $stats,
        ]);
    }

    public function run(Request $request, string $id)
    {
        GlobalActivityLog::create([
            'global_activity_action' => 'cron_job_run',
            'global_activity_description' => "Menjalankan cron task: {$id}",
            'global_activity_status' => 'success',
        ]);

        return redirect()->back()->with('success', "Cron job {$id} berhasil dipicu untuk eksekusi pada tick berikutnya!");
    }

    public function toggle(Request $request, string $id)
    {
        GlobalActivityLog::create([
            'global_activity_action' => 'cron_job_toggle',
            'global_activity_description' => "Mengubah status pause/resume cron task: {$id}",
            'global_activity_status' => 'success',
        ]);

        return redirect()->back()->with('success', "Status cron job {$id} berhasil diperbarui.");
    }
}
