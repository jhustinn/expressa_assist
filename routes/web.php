<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ConfigController;
use App\Http\Controllers\TelegramController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\SessionsController;
use App\Http\Controllers\CronJobController;
use App\Http\Controllers\SkillController;
use App\Http\Controllers\MemoryController;
use App\Http\Controllers\SoulController;
use App\Http\Controllers\EnvironmentController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\ModelController;
use App\Http\Controllers\McpController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\PairingController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\DocController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuManagementController;
use App\Http\Controllers\AgentManagementController;

// =========================================================================
// PUBLIC AUTHENTICATION ROUTES (GUEST ONLY)
// =========================================================================
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// =========================================================================
// STRICTLY PROTECTED ROUTES (AUTHENTICATION REQUIRED - NO GUESTS)
// =========================================================================
Route::middleware(['auth'])->group(function () {

    // 1. Core & Chat
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::post('/api/chat/send', [ChatController::class, 'send'])->name('chat.send');
    Route::get('/api/chat/sessions/{id}', [ChatController::class, 'getSessionMessages'])->name('chat.session.messages');
    Route::get('/sessions', [SessionsController::class, 'index'])->name('sessions.index');
    Route::get('/sessions/{id}', [SessionsController::class, 'show'])->name('sessions.show');
    Route::get('/files', [FileController::class, 'index'])->name('files.index');
    Route::get('/api/files/read', [FileController::class, 'readFile'])->name('files.read');
    Route::get('/api/files/download', [FileController::class, 'download'])->name('files.download');

    // 2. Intelligence & Tools
    Route::get('/skills', [SkillController::class, 'index'])->name('skills.index');
    Route::get('/api/skills/{name}', [SkillController::class, 'show'])->name('skills.show');
    Route::post('/api/skills/{name}/toggle', [SkillController::class, 'toggle'])->name('skills.toggle');
    Route::post('/api/skills/{name}/lock', [SkillController::class, 'lock'])->name('skills.lock');
    Route::post('/api/skills/{name}/save', [SkillController::class, 'save'])->name('skills.save');
    Route::get('/api/skills-hub/sources', [SkillController::class, 'hubSources'])->name('skills.hub.sources');
    Route::get('/api/skills-hub/official', [SkillController::class, 'hubOfficial'])->name('skills.hub.official');
    Route::get('/api/skills-hub/search', [SkillController::class, 'hubSearch'])->name('skills.hub.search');
    Route::get('/api/skills-hub/preview', [SkillController::class, 'hubPreview'])->name('skills.hub.preview');
    Route::get('/api/skills-hub/scan', [SkillController::class, 'hubScan'])->name('skills.hub.scan');
    Route::get('/api/skills-hub/updates', [SkillController::class, 'hubUpdates'])->name('skills.hub.updates');
    Route::post('/api/skills-hub/install', [SkillController::class, 'hubInstall'])->name('skills.hub.install');
    Route::post('/api/skills-hub/uninstall', [SkillController::class, 'hubUninstall'])->name('skills.hub.uninstall');
    Route::post('/api/skills-hub/update-all', [SkillController::class, 'hubUpdateAll'])->name('skills.hub.update-all');

    Route::get('/mcp', [McpController::class, 'index'])->name('mcp.index');
    Route::get('/cron-jobs', [CronJobController::class, 'index'])->name('cron.index');
    Route::post('/cron-jobs/{id}/run', [CronJobController::class, 'run'])->name('cron.run');
    Route::post('/cron-jobs/{id}/toggle', [CronJobController::class, 'toggle'])->name('cron.toggle');
    Route::get('/memory', [MemoryController::class, 'index'])->name('memory.index');
    Route::post('/memory', [MemoryController::class, 'store'])->name('memory.store');
    Route::get('/agent-jhustyn', [AgentManagementController::class, 'index'])->name('agent.jhustyn');
    Route::put('/agent-jhustyn', [AgentManagementController::class, 'update'])->name('agent.jhustyn.update');
    Route::post('/agent-jhustyn/sync', [AgentManagementController::class, 'syncFromDisk'])->name('agent.jhustyn.sync');

    // 3. Channels & Integrations
    Route::get('/channels', [ChannelController::class, 'index'])->name('channels.index');
    Route::put('/api/channels/{id}', [ChannelController::class, 'update'])->name('channels.update');
    Route::post('/api/channels/{id}/test', [ChannelController::class, 'test'])->name('channels.test');
    Route::get('/webhooks', [WebhookController::class, 'index'])->name('webhooks.index');

    // 4. Models & Diagnostics
    Route::get('/models', [ModelController::class, 'index'])->name('models.index');
    Route::post('/api/models/set', [ModelController::class, 'set'])->name('models.set');
    Route::get('/profiles', [ProfileController::class, 'index'])->name('profiles.index');
    Route::post('/profiles', [ProfileController::class, 'store'])->name('profiles.store');
    Route::get('/profiles/{name}/edit', [ProfileController::class, 'edit'])->name('profiles.edit');
    Route::put('/profiles/{name}', [ProfileController::class, 'update'])->name('profiles.update');
    Route::delete('/profiles/{name}', [ProfileController::class, 'destroy'])->name('profiles.destroy');

    // 5. Monitoring & Logs
    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
    Route::post('/monitoring/action', [MonitoringController::class, 'action'])->name('monitoring.action');
    Route::post('/monitoring/test-api', [MonitoringController::class, 'testApi'])->name('monitoring.test-api');
    Route::get('/api/gateway/status', [MonitoringController::class, 'statusJson'])->name('api.gateway.status');
    Route::get('/api/gateway/logs', [MonitoringController::class, 'logsJson'])->name('api.gateway.logs');
    Route::get('/api/logs', [SystemController::class, 'apiLogs'])->name('api.logs');
    Route::get('/logs', [SystemController::class, 'logs'])->name('logs.index');
    Route::get('/system', [SystemController::class, 'index'])->name('system.index');
    Route::get('/documentation', [DocController::class, 'index'])->name('docs.index');

    // =========================================================================
    // STRICT ADMINISTRATOR-ONLY ROUTES (ROLE: ADMIN)
    // =========================================================================
    Route::middleware(['role:admin'])->group(function () {
        // User Management & RBAC
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{id}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::post('/users/{id}/password', [UserController::class, 'resetPassword'])->name('users.password.reset');
        Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('/users/sync-env', [UserController::class, 'syncEnv'])->name('users.sync-env');
        Route::get('/api/telegram/lookup/{id}', [UserController::class, 'lookupTelegram'])->name('api.telegram.lookup');

        // Menu & RBAC Management
        Route::get('/menus', [MenuManagementController::class, 'index'])->name('menus.index');
        Route::post('/menus', [MenuManagementController::class, 'store'])->name('menus.store');
        Route::put('/menus/{id}', [MenuManagementController::class, 'update'])->name('menus.update');
        Route::post('/menus/{id}/toggle', [MenuManagementController::class, 'toggle'])->name('menus.toggle');
        Route::post('/menus/matrix', [MenuManagementController::class, 'updateMatrix'])->name('menus.matrix.update');
        Route::delete('/menus/{id}', [MenuManagementController::class, 'destroy'])->name('menus.destroy');

        // Sensitive System Configuration & Keys
        Route::get('/config', [ConfigController::class, 'index'])->name('config.index');
        Route::post('/config', [ConfigController::class, 'update'])->name('config.update');
        Route::get('/environment', [EnvironmentController::class, 'index'])->name('environment.index');
        Route::post('/environment', [EnvironmentController::class, 'update'])->name('environment.update');
        Route::post('/environment/sync', [EnvironmentController::class, 'syncFromEnvFile'])->name('environment.sync');
        Route::post('/system/paths', [SystemController::class, 'updatePaths'])->name('system.paths.update');

        // Soul & Persona Prompt
        Route::get('/soul', [SoulController::class, 'index'])->name('soul.index');
        Route::post('/soul', [SoulController::class, 'update'])->name('soul.update');

        // Telegram Bot Whitelist
        Route::get('/telegram', [TelegramController::class, 'index'])->name('telegram.index');
        Route::post('/telegram/auth', [TelegramController::class, 'updateTelegramAuth'])->name('telegram.auth.update');
        Route::post('/telegram/sync-env', [TelegramController::class, 'syncEnv'])->name('telegram.sync-env');
        Route::post('/telegram/users', [TelegramController::class, 'storeUser'])->name('telegram.users.store');
        Route::put('/telegram/users/{id}', [TelegramController::class, 'updateUser'])->name('telegram.users.update');
        Route::post('/telegram/users/{id}/toggle', [TelegramController::class, 'toggleUserStatus'])->name('telegram.users.toggle');
        Route::delete('/telegram/users/{id}', [TelegramController::class, 'destroyUser'])->name('telegram.users.destroy');
        Route::post('/telegram/commands', [TelegramController::class, 'storeCommand'])->name('telegram.commands.store');
        Route::put('/telegram/commands/{id}', [TelegramController::class, 'updateCommand'])->name('telegram.commands.update');
        Route::delete('/telegram/commands/{id}', [TelegramController::class, 'destroyCommand'])->name('telegram.commands.destroy');

        // Device Pairing
        Route::get('/pairing', [PairingController::class, 'index'])->name('pairing.index');
        Route::post('/api/pairing/approve', [PairingController::class, 'approve'])->name('pairing.approve');
        Route::post('/api/pairing/revoke', [PairingController::class, 'revoke'])->name('pairing.revoke');
    });
});
