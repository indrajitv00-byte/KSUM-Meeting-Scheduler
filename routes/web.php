<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\RecordingController;
use App\Livewire\Meeting\MeetingsPage;
use App\Livewire\MyProfilePage;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'home');

// Admins land on the overview, everyone else on the booking page.
Route::middleware('auth')->get('home', function () {
    return redirect()->route(auth()->user()->can('admin:dashboard:view') ? 'admin.overview' : 'meetings');
})->name('home');

Route::middleware(['auth', 'can:meetings:view'])->group(function () {
    Route::get('meetings', MeetingsPage::class)->name('meetings');
    Route::get('meetings/{meeting}/start', [MeetingController::class, 'start'])->name('meetings.start');
    Route::get('recordings/{recording}/download', [RecordingController::class, 'download'])->name('recordings.download');
});

// Any signed-in user can edit their own profile and password (no extra permission needed).
Route::middleware('auth')->get('profile', MyProfilePage::class)->name('profile');

Route::get('logout', [LoginController::class, 'logout']);
