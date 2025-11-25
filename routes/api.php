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
    // Login needs session cookies for Sanctum SPA flow; add `web` middleware so session is started.
    Route::post('login', [AuthController::class, 'login'])->middleware('web')->name('login');
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
            'session_id' => session()->getId(),
            'session' => session()->all(),
            'user' => optional($request->user())->id ?? null,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'headers' => $request->headers->all(),
            'cookies' => $request->cookies->all(),
            'input' => $request->all(),
            'session_id' => session()->getId(),
            'session' => session()->all(),
            'user' => optional($request->user())->id ?? null,
        ]);
    });

    Route::middleware('auth:sanctum')->group(function () {
        // Authenticated 'me' endpoint — returns current user and useful debug info.
        Route::get('me', function (\Illuminate\Http\Request $request) {
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

        Route::post('logout', [AuthController::class, 'logout']);
        Route::apiResource('users', UserController::class);
        Route::post('password/send-link', [AuthController::class, 'sendResetLinkAuthenticated']);
        Route::post('password/change', [AuthController::class, 'changePassword']);
    });
});

// Local-only helper to inspect cookies vs server session files.
// This route uses the `web` middleware so cookies are decrypted and session is available.
Route::middleware('web')->get('/debug/session-inspect', function (\Illuminate\Http\Request $request) {
    if (app()->environment() !== 'local') {
        return response()->json(['message' => 'Not available in this environment'], 404);
    }

    $cookieRawHeader = $request->headers->get('cookie');
    $cookieLaravel = $request->cookie('laravel-session'); // decrypted by EncryptCookies when web middleware is used
    $sessionId = session()->getId();
    $sessionData = session()->all();

    $sessionsPath = storage_path('framework/sessions');
    $recent = [];
    if (is_dir($sessionsPath)) {
        $files = collect(File::files($sessionsPath))->sortByDesc->getLastModified();
        // Build a small array of recent files (name + mtime)
        $recent = collect(File::files($sessionsPath))->map(function ($f) {
            return [
                'name' => $f->getFilename(),
                'mtime' => date('c', $f->getMTime()),
            ];
        })->take(12)->values();
    }

    // If the decrypted cookie matches a session file, include its contents (first 200 chars)
    $sessionFileContent = null;
    if ($cookieLaravel) {
        $path = $sessionsPath.DIRECTORY_SEPARATOR.$cookieLaravel;
        if (file_exists($path)) {
            $sessionFileContent = substr(file_get_contents($path), 0, 800);
        }
    }

    return response()->json([
        'cookie_header' => $cookieRawHeader,
        'cookie_decrypted' => $cookieLaravel,
        'server_session_id' => $sessionId,
        'server_session' => $sessionData,
        'recent_session_files' => $recent,
        'session_file_sample' => $sessionFileContent,
    ]);
});
