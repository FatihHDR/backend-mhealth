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
    // Public auth endpoints
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    // Public resources (read-only or public access)
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

    // Protected routes - require authenticated user with Sanctum
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::apiResource('users', UserController::class);
    });
});
