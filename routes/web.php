<?php

use Illuminate\Support\Facades\Route;
use Aar\AutoTranslator\Dashboard\Controllers\TranslationDashboardController;

$prefix = config('aar-translator.dashboard.path', 'admin/translations');
$middleware = config('aar-translator.dashboard.middleware', ['web']);

Route::prefix($prefix)
     ->middleware($middleware)
     ->name('aar-translator.')
     ->group(function () {
         Route::get('/', [TranslationDashboardController::class, 'index'])->name('index');
         Route::get('/keys', [TranslationDashboardController::class, 'keys'])->name('keys');
         Route::post('/keys/{key}/translate', [TranslationDashboardController::class, 'translateKey'])->name('keys.translate');
         Route::put('/values/{value}', [TranslationDashboardController::class, 'updateValue'])->name('values.update');
         Route::delete('/keys/{key}', [TranslationDashboardController::class, 'destroyKey'])->name('keys.destroy');
         Route::delete('/keys/dead/all', [TranslationDashboardController::class, 'destroyDeadKeys'])->name('keys.dead.destroy');
         Route::post('/scan', [TranslationDashboardController::class, 'scan'])->name('scan');
         Route::post('/translate-missing', [TranslationDashboardController::class, 'translateMissing'])->name('translate.missing');
         Route::get('/export', [TranslationDashboardController::class, 'export'])->name('export');
         Route::post('/import', [TranslationDashboardController::class, 'import'])->name('import');
         Route::get('/status', [TranslationDashboardController::class, 'status'])->name('status');
     });
