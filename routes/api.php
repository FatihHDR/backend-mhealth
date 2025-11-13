<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ErrorLogController;
use App\Http\Controllers\HospitalRelationController;
use App\Http\Controllers\MedicalTechController;
use App\Http\Controllers\RecomendationPackageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    
    // User routes
    Route::prefix('users')->group(function () {
        Route::get('/', function (Request $request) {
            return \App\Models\User::paginate(15);
        });
        Route::get('/{id}', function ($id) {
            return \App\Models\User::findOrFail($id);
        });
    });

    // Package routes
    Route::prefix('packages')->group(function () {
        Route::get('/', [PackageController::class, 'index']);
        Route::get('/{id}', [PackageController::class, 'show']);
        Route::get('/medical/list', [PackageController::class, 'medical']);
        Route::get('/entertainment/list', [PackageController::class, 'entertainment']);
    });

    // Payment routes
    Route::prefix('payments')->group(function () {
        Route::get('/', [PaymentController::class, 'index']);
        Route::get('/{id}', [PaymentController::class, 'show']);
        Route::get('/user/{userId}', [PaymentController::class, 'byUser']);
        Route::get('/status/{status}', [PaymentController::class, 'byStatus']);
    });

    // Chatbot routes
    Route::prefix('chatbots')->group(function () {
        Route::get('/', [ChatbotController::class, 'index']);
        Route::get('/{id}', [ChatbotController::class, 'show']);
        Route::get('/user/{userId}', [ChatbotController::class, 'byUser']);
        Route::get('/token/{token}', [ChatbotController::class, 'byToken']);
    });

    // Article routes
    Route::prefix('articles')->group(function () {
        Route::get('/', [ArticleController::class, 'index']);
        Route::get('/{id}', [ArticleController::class, 'show']);
        Route::get('/author/{userId}', [ArticleController::class, 'byAuthor']);
        Route::get('/published', [ArticleController::class, 'published']);
    });

    // Event routes
    Route::prefix('events')->group(function () {
        Route::get('/', [EventController::class, 'index']);
        Route::get('/{id}', [EventController::class, 'show']);
        Route::get('/upcoming', [EventController::class, 'upcoming']);
        Route::get('/past', [EventController::class, 'past']);
    });

    // Hospital Relation routes
    Route::prefix('hospital-relations')->group(function () {
        Route::get('/', [HospitalRelationController::class, 'index']);
        Route::get('/{id}', [HospitalRelationController::class, 'show']);
    });

    // Medical Tech routes
    Route::prefix('medical-techs')->group(function () {
        Route::get('/', [MedicalTechController::class, 'index']);
        Route::get('/{id}', [MedicalTechController::class, 'show']);
    });

    // Recommendation Package routes
    Route::prefix('recommendations')->group(function () {
        Route::get('/', [RecomendationPackageController::class, 'index']);
        Route::get('/{id}', [RecomendationPackageController::class, 'show']);
    });

    // Error Log routes (Admin only in production)
    Route::prefix('error-logs')->group(function () {
        Route::get('/', [ErrorLogController::class, 'index']);
        Route::get('/{id}', [ErrorLogController::class, 'show']);
        Route::get('/recent', [ErrorLogController::class, 'recent']);
    });
});
