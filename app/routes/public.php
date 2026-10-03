<?php

use App\Cards\Code;
use App\Http\Controllers\CardController;
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

// Share card images, content-addressed by their verification code (share-cards door 6).
$card = ['code' => Code::PATTERN, 'format' => '1200x630|1080x1350|1080x1920'];
Route::get('/deputados/{id}/legislatura/{n}/card/{code}/{format}.png', [CardController::class, 'deputy'])->whereNumber(['id', 'n'])->where($card)->name('deputies.card');
Route::get('/senadores/{id}/legislatura/{n}/card/{code}/{format}.png', [CardController::class, 'senator'])->whereNumber(['id', 'n'])->where($card)->name('senators.card');
Route::get('/votacoes/{id}/card/{code}/{format}.png', [CardController::class, 'camara'])->where(['id' => '[0-9]+-[0-9]+', ...$card])->name('roll-calls.card');
Route::get('/senado/votacoes/{id}/card/{code}/{format}.png', [CardController::class, 'senado'])->whereNumber('id')->where($card)->name('senate-roll-calls.card');
