<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('translate')->group(function(){
    Route::get('/{lang', [TranslateController::class, 'translate']);
});
