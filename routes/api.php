<?php

use App\Http\Controllers\AuthenticationController;
use App\Http\Controllers\TranslateController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->prefix('translations')
    ->group(function () {
        Route::post('/import', [TranslateController::class, 'import',])->name('translation.import');
        Route::get('/export/{lang}', [TranslateController::class, 'export'])->name('translation.export');
        Route::get('', [TranslateController::class, 'index'])->name('translation.index');
        Route::get('{translation}', [TranslateController::class, 'show'])->name('translation.show');
        Route::post('', [TranslateController::class, 'create'])->name('translation.create');
        Route::put('{translation}', [TranslateController::class, 'update'])->name('translation.update');
        Route::delete('{translation}', [TranslateController::class, 'delete'])->name('translation.delete');
    });


Route::middleware('throttle:60,1')
    ->group(function () {
        Route::post('/login', [AuthenticationController::class, 'login'])->name('auth.login');
        Route::middleware('auth:sanctum')->post('/logout', [AuthenticationController::class, 'logout'])
            ->name('auth.logout');
        Route::post('/register', [AuthenticationController::class, 'register'])->name('auth.register');
    });
