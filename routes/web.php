<?php

use App\Http\Controllers\BugController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CopilotController;
use App\Http\Controllers\DailyStandupController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

// PWA Offline Fallback View
Route::view('/offline', 'offline')->name('offline');

Auth::routes();

Route::middleware(['auth','auth.status'])->group(function () {
    // Dashboard / Home
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    // Projects CRUD Routes
    Route::resource('projects', ProjectsController::class);

    // QA Test Reports CRUD Routes
    Route::get('reports/export/all',      [ReportController::class, 'export'])->name('reports.export.all');
    Route::get('reports/{report}/export', [ReportController::class, 'export'])->name('reports.export.single');
    Route::get('reports/csv-template',    [ReportController::class, 'downloadCsvTemplate'])->name('reports.test-cases.csv-template');
    Route::resource('reports', ReportController::class);

    // Test Case Management within a Report (Tahap 2)
    Route::post('reports/{report}/test-cases',                   [ReportController::class, 'storeTestCase'])->name('reports.test-cases.store');
    Route::put('reports/{report}/test-cases/{testCase}',         [ReportController::class, 'updateTestCase'])->name('reports.test-cases.update');
    Route::delete('reports/{report}/test-cases/{testCase}',      [ReportController::class, 'destroyTestCase'])->name('reports.test-cases.destroy');
    Route::post('reports/{report}/test-cases/bulk-status',       [ReportController::class, 'bulkStatusTestCase'])->name('reports.test-cases.bulk-status');
    Route::post('reports/{report}/test-cases/bulk-delete',       [ReportController::class, 'bulkDeleteTestCase'])->name('reports.test-cases.bulk-delete');
    Route::post('reports/{report}/test-cases/bulk-approve',      [ReportController::class, 'bulkApproveAiTestCase'])->name('reports.test-cases.bulk-approve');
    Route::post('reports/{report}/test-cases/duplicate',        [ReportController::class, 'duplicateTestCase'])->name('reports.test-cases.duplicate');
    Route::post('reports/{report}/test-cases/ai-generate',       [ReportController::class, 'aiGenerateTestCases'])->name('reports.test-cases.ai-generate');
    Route::post('reports/{report}/test-cases/import-csv',        [ReportController::class, 'importCsvTestCases'])->name('reports.test-cases.import-csv');
    Route::post('reports/{report}/test-cases/{testCase}/approve',[ReportController::class, 'approveAiTestCase'])->name('reports.test-cases.approve');

    // Bug List CRUD Routes (QA-TPL-001)
    Route::post('bugs/quick-store', [BugController::class, 'quickStore'])->name('bugs.quick-store');
    Route::resource('bugs', BugController::class);

    // AI Auto-Fill Routes
    Route::post('reports/ai-parse',     [ReportController::class, 'aiParse'])->name('reports.ai-parse');
    Route::get('reports/ai-providers',  [ReportController::class, 'aiProviders'])->name('reports.ai-providers');

    // AI Daily Standup & Assistant Routes
    Route::get('daily-standup',               [DailyStandupController::class, 'index'])->name('daily-standup.index');
    Route::get('daily-standup/activity',      [DailyStandupController::class, 'fetchActivity'])->name('daily-standup.activity');
    Route::post('daily-standup/generate',     [DailyStandupController::class, 'generate'])->name('daily-standup.generate');
    Route::post('daily-standup/save',         [DailyStandupController::class, 'store'])->name('daily-standup.store');
    Route::delete('daily-standup/{id}',       [DailyStandupController::class, 'destroy'])->name('daily-standup.destroy');

    // Real-Time Team Chat WebSocket Routes (Replacing Copilot with Live Team Collaboration)
    Route::get('chat',                                      [ChatController::class, 'index'])->name('chat.index');
    Route::post('chat/channels',                            [ChatController::class, 'createChannel'])->name('chat.channels.create');
    Route::get('chat/dm/{targetUserId}',                    [ChatController::class, 'directMessage'])->name('chat.dm');
    Route::post('chat/channels/{channelId}/messages',       [ChatController::class, 'sendMessage'])->name('chat.messages.send');
    Route::get('chat/channels/{channelId}/thread/{messageId}', [ChatController::class, 'getThread'])->name('chat.thread');
    Route::match(['put', 'post'], 'chat/messages/{messageId}', [ChatController::class, 'editMessage'])->name('chat.messages.edit');
    Route::match(['put', 'post'], 'chat/messages/{messageId}/edit', [ChatController::class, 'editMessage']);
    Route::match(['delete', 'post'], 'chat/messages/{messageId}', [ChatController::class, 'deleteMessage'])->name('chat.messages.delete');
    Route::match(['delete', 'post'], 'chat/messages/{messageId}/delete', [ChatController::class, 'deleteMessage']);
    Route::post('chat/messages/{messageId}/pin',            [ChatController::class, 'togglePin'])->name('chat.messages.pin');
    Route::post('chat/messages/{messageId}/reaction',       [ChatController::class, 'toggleReaction'])->name('chat.messages.reaction');
    Route::post('chat/channels/{channelId}/read',           [ChatController::class, 'markAsRead'])->name('chat.channels.read');
    Route::get('chat/search/{channelId?}',                  [ChatController::class, 'search'])->name('chat.search');
    Route::get('chat/channels/{channelId}/search',          [ChatController::class, 'search']);

    // Legacy Copilot redirect to Chat
    Route::get('copilot', fn() => redirect()->route('chat.index'))->name('copilot.index');

    // Settings Routes
    Route::get('settings',             [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings/profile',     [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::put('settings/api-keys',    [SettingsController::class, 'updateApiKeys'])->name('settings.api-keys.update');
});
