<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TranslateController;


Route::middleware('auth:sanctum')->prefix('translations')->group(function(){
    Route::get('', [TranslateController::class, 'index'])->name('translation.index');
    Route::get('{translation}', [TranslateController::class, 'show'])->name('translation.show');
    Route::post('', [TranslateController::class, 'create'])->name('translation.create');
    Route::put('{translation}', [TranslateController::class, 'update'])->name('translation.update');
    Route::delete('{translation}', [TranslateController::class, 'delete'])->name('translation.show');
});