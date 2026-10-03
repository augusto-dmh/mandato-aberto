<?php

use App\Http\Controllers\MemberController;
use App\Http\Controllers\MethodologyController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\RollCallController;
use Illuminate\Support\Facades\Route;

// The public paths (skeleton door 10, plan door 4): a link shared from the static site keeps working
// once the origin points here. Both slash forms match; the canonical always has the slash.
Route::get('/deputados/{id}/', [MemberController::class, 'deputy'])->whereNumber('id')->name('deputies.show');
Route::get('/deputados/{id}/legislatura/{n}/', [MemberController::class, 'deputy'])->whereNumber(['id', 'n'])->name('deputies.legislature');
Route::get('/senadores/{id}/', [MemberController::class, 'senator'])->whereNumber('id')->name('senators.show');
Route::get('/senadores/{id}/legislatura/{n}/', [MemberController::class, 'senator'])->whereNumber(['id', 'n'])->name('senators.legislature');
Route::get('/votacoes/{id}/', [RollCallController::class, 'camara'])->where('id', '[0-9]+-[0-9]+')->name('roll-calls.show');
Route::get('/senado/votacoes/{id}/', [RollCallController::class, 'senado'])->whereNumber('id')->name('senate-roll-calls.show');
Route::get('/metodologia/', [MethodologyController::class, 'show'])->name('methodology');

// Official photos from our origin, content-addressed (share-cards door 6).
Route::get('/fotos/{sha256}.jpg', [PhotoController::class, 'show'])->where('sha256', '[0-9a-f]{64}')->name('photos.show');
