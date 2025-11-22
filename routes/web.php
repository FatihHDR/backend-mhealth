<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('auth/google/redirect', [AuthController::class, 'redirectToProvider']);
Route::get('auth/google/callback', [AuthController::class, 'handleProviderCallback']);

// Password reset route expected by Laravel's ResetPassword notification.
// Redirects to frontend reset page (FRONTEND_URL) with token & email as query params.
Route::get('password/reset/{token}', function ($token) {
    $frontend = env('FRONTEND_URL');
    $email = request()->query('email');

    if ($frontend) {
        $url = rtrim($frontend, '/').'/password/reset?token='.$token;
        if ($email) {
            $url .= '&email='.urlencode($email);
        }

        return Redirect::away($url);
    }

    // Fallback: show a simple message or redirect to home
    return Redirect::to('/')->with('reset_token', $token);
})->name('password.reset');
