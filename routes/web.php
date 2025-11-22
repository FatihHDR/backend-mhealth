<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('auth/google/redirect', [AuthController::class, 'redirectToProvider']);
Route::get('auth/google/callback', [AuthController::class, 'handleProviderCallback']);
