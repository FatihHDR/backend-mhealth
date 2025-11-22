<?php

use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ChatbotController;
use App\Http\Controllers\Api\V1\ErrorLogController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\GeminiController;
use App\Http\Controllers\Api\V1\HospitalRelationController;
use App\Http\Controllers\Api\V1\MedicalTechController;
use App\Http\Controllers\Api\V1\PackageController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\RecomendationPackageController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'v1', 'namespace' => 'App\Http\Controllers\Api\V1'], function () {
    // Public auth
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    // Google sign-in (stateless)
    Route::post('auth/google', [AuthController::class, 'googleSignIn']);

    // Password reset submission endpoint (public) used by frontend reset form
    Route::post('password/reset', [AuthController::class, 'resetPassword']);

    // Public resources
    Route::apiResource('recomendation-packages', RecomendationPackageController::class);
    Route::apiResource('articles', ArticleController::class);
    Route::apiResource('events', EventController::class);
    Route::apiResource('hospital-relations', HospitalRelationController::class);
    Route::apiResource('medical-techs', MedicalTechController::class);
    Route::apiResource('packages', PackageController::class);
    Route::apiResource('payments', PaymentController::class);
    Route::apiResource('chatbots', ChatbotController::class);
    Route::apiResource('error-logs', ErrorLogController::class);
    Route::post('gemini/generate', GeminiController::class);

    // Temporary debug route: log headers and cookies for troubleshooting auth/cors issues.
    // This route is intentionally public and should be removed after debugging.
    Route::match(['GET', 'POST'], 'debug/headers', function (\Illuminate\Http\Request $request) {
        \Illuminate\Support\Facades\Log::debug('Debug headers endpoint called', [
            'headers' => $request->headers->all(),
            'cookies' => $request->cookies->all(),
            'input' => $request->all(),
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'headers' => $request->headers->all(),
            'cookies' => $request->cookies->all(),
            'input' => $request->all(),
        ]);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);

        Route::get('me', function (\Illuminate\Http\Request $request) {
            // Debug logging: record headers, cookies, session and user for troubleshooting
            \Illuminate\Support\Facades\Log::debug('API /me called', [
                'headers' => $request->headers->all(),
                'cookies' => $request->cookies->all(),
                'session_id' => session()->getId(),
                'session' => session()->all(),
                'user' => optional($request->user())->id ?? null,
            ]);

            if (! $request->user()) {
                \Illuminate\Support\Facades\Log::debug('API /me unauthenticated');
            }

            return response()->json(['user' => $request->user()]);
        });
        Route::apiResource('users', UserController::class);
        Route::post('password/send-link', [AuthController::class, 'sendResetLinkAuthenticated']);
        Route::post('password/change', [AuthController::class, 'changePassword']);
    });
});
