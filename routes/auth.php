<?php

use App\Http\Controllers\Auth\SocialLoginController;
use App\Livewire\Auth\ForgotPage;
use App\Livewire\Auth\LoginPage;
use App\Livewire\Auth\ResetPage;
use Illuminate\Support\Facades\Route;

Route::get('login', LoginPage::class)->name('login');
Route::get('password/forgot', ForgotPage::class)->name('password.forgot');
Route::get('password/reset', ResetPage::class)->name('password.reset');

// "Continue with Google" / "Continue with Startup Mission ID"
Route::get('login/{provider}', [SocialLoginController::class, 'redirect'])
    ->whereIn('provider', ['google', 'uid'])->name('social.redirect');

Route::get('{provider}/callback', [SocialLoginController::class, 'callback'])
    ->whereIn('provider', ['google', 'uid'])->name('social.callback');
