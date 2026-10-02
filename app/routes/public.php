<?php

use App\Http\Controllers\DeputyController;
use App\Http\Controllers\RollCallController;
use Illuminate\Support\Facades\Route;

// The MVP's public paths (plan door 10): a link shared from the static site keeps working
// once the origin points here. Both slash forms match; the canonical always has the slash.
Route::get('/deputados/{id}/', [DeputyController::class, 'show'])->whereNumber('id')->name('deputies.show');
Route::get('/votacoes/{id}/', [RollCallController::class, 'show'])->where('id', '[0-9]+-[0-9]+')->name('roll-calls.show');
