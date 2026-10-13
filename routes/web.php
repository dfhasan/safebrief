<?php

use App\Http\Controllers\BriefingController;
use App\Http\Controllers\CrewBriefingController;
use App\Http\Controllers\CrewLinkController;
use App\Http\Controllers\CrewMemberController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\NoReferrer;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::get('/locale/{locale}', function (string $locale) {
    abort_unless(in_array($locale, SetLocale::SUPPORTED, true), 404);
    session(['locale' => $locale]);

    return back();
})->name('locale');

// Crew: no accounts, the unguessable token is the credential.
Route::middleware(NoReferrer::class)->group(function () {
    Route::get('/b/{token}', [CrewBriefingController::class, 'show'])
        ->middleware('throttle:60,1')->name('crew.show');
    Route::post('/b/{token}', [CrewBriefingController::class, 'submit'])
        ->middleware('throttle:20,1')->name('crew.submit');
});

Route::middleware('auth')->group(function () {
    Route::get('/trial-expired', fn () => Inertia::render('TrialExpired'))->name('trial.expired');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'trial'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/briefings', [BriefingController::class, 'index'])->name('briefings.index');
    Route::get('/briefings/create', [BriefingController::class, 'create'])->name('briefings.create');
    Route::post('/briefings', [BriefingController::class, 'store'])
        ->middleware('throttle:10,1')->name('briefings.store');
    Route::get('/briefings/{briefing}', [BriefingController::class, 'show'])->name('briefings.show');
    Route::put('/briefings/{briefing}', [BriefingController::class, 'update'])->name('briefings.update');
    Route::post('/briefings/{briefing}/publish', [BriefingController::class, 'publish'])->name('briefings.publish');
    Route::post('/briefings/{briefing}/links', [CrewLinkController::class, 'store'])->name('briefings.links.store');

    Route::get('/crew', [CrewMemberController::class, 'index'])->name('crew.index');
    Route::post('/crew', [CrewMemberController::class, 'store'])->name('crew.store');
    Route::delete('/crew/{crewMember}', [CrewMemberController::class, 'destroy'])->name('crew.destroy');
});

require __DIR__.'/auth.php';
