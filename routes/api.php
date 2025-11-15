<?php

use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\ChatbotController;
use App\Http\Controllers\Api\V1\ErrorLogController;
use App\Http\Controllers\Api\V1\EventController;
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
    Route::apiResource('recomendation-packages', RecomendationPackageController::class);
    Route::apiResource('articles', ArticleController::class);
    Route::apiResource('events', EventController::class);
    Route::apiResource('hospital-relations', HospitalRelationController::class);
    Route::apiResource('medical-techs', MedicalTechController::class);
    Route::apiResource('packages', PackageController::class);
    Route::apiResource('payments', PaymentController::class);
    Route::apiResource('chatbots', ChatbotController::class);
    Route::apiResource('error-logs', ErrorLogController::class);
    Route::apiResource('users', UserController::class);
});
