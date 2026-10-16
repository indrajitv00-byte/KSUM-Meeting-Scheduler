<?php

use App\Livewire\Admin\Meeting\AllMeetingsPage;
use App\Livewire\Admin\Meeting\OverviewPage;
use App\Livewire\Admin\Meeting\SchedulePage;
use App\Livewire\Admin\Recording\RecordingsPage;
use App\Livewire\Admin\Role\RolePage;
use App\Livewire\Admin\User\UserPage;
use App\Livewire\Admin\ZoomAccount\ZoomAccountPage;
use Illuminate\Support\Facades\Route;

Route::get('/overview', OverviewPage::class)->middleware('can:admin:dashboard:view')->name('overview');
Route::get('/zoom-accounts', ZoomAccountPage::class)->middleware('can:admin:zoom-accounts:view')->name('zoom-accounts');
Route::get('/schedule', SchedulePage::class)->middleware('can:admin:meetings:view')->name('schedule');
Route::get('/meetings', AllMeetingsPage::class)->middleware('can:admin:meetings:view')->name('meetings');
Route::get('/recordings', RecordingsPage::class)->middleware('can:admin:meetings:view')->name('recordings');
Route::get('/users', UserPage::class)->middleware('can:admin:users:view')->name('users');
Route::get('/roles', RolePage::class)->middleware('can:admin:roles:view')->name('roles');
