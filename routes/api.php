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

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);

        Route::get('me', function (\Illuminate\Http\Request $request) {
            return response()->json(['user' => $request->user()]);
        });
        Route::apiResource('users', UserController::class);
        Route::post('password/send-link', [AuthController::class, 'sendResetLinkAuthenticated']);
        Route::post('password/change', [AuthController::class, 'changePassword']);
    });
});
