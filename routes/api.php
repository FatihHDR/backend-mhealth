<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ErrorLogController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\GeminiController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\AboutUsController;
use App\Http\Controllers\Api\V1\LatestPackagesController;
use App\Http\Controllers\Api\V1\MedicalController;
use App\Http\Controllers\Api\V1\HotelsController;
use App\Http\Controllers\Api\V1\MedicalEquipmentController;
use App\Http\Controllers\Api\V1\VendorsController;
use App\Http\Controllers\Api\V1\PackagesController;
use App\Http\Controllers\Api\V1\WellnessController;
use App\Http\Controllers\Api\V1\WellnessPackagesController;
use App\Http\Middleware\VerifySupabaseJwt;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::group(['prefix' => 'v1', 'namespace' => 'App\Http\Controllers\Api\V1'], function () {
    // Public auth
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login'])->middleware('web')->name('login');
    Route::post('auth/google', [AuthController::class, 'googleSignIn']);
    Route::post('password/reset', [AuthController::class, 'resetPassword']);

    // New
    Route::post('gemini/generate', GeminiController::class);
    Route::apiResource('about-us', AboutUsController::class);
    Route::apiResource('latest-packages', LatestPackagesController::class);
    Route::apiResource('medical', MedicalController::class);
    Route::apiResource('hotels', HotelsController::class);
    Route::apiResource('medical-equipment', MedicalEquipmentController::class);
    Route::apiResource('vendors', VendorsController::class);
    Route::apiResource('packages', PackagesController::class);
    Route::apiResource('wellness', WellnessController::class);
    Route::apiResource('wellness-packages', WellnessPackagesController::class);
    Route::apiResource('articles', ArticleController::class);
    Route::apiResource('events', EventController::class);
    Route::apiResource('payments', PaymentController::class);
    Route::apiResource('error-logs', ErrorLogController::class);

    Route::middleware(VerifySupabaseJwt::class)->group(function () {
    Route::get('profile', function (Illuminate\Http\Request $request) {
        return response()->json([
            'user_id' => $request->attributes->get('user_id'),
            'payload' => $request->attributes->get('supabase_user'),
        ]);
    });

    Route::post('payments/snap', [PaymentController::class, 'createSnap']);
    });

    Route::post('payments/notification', [PaymentController::class, 'notification']);

    // Temporary debug route: log headers and cookies for troubleshooting auth/cors issues.
    // This route is intentionally public and should be removed after debugging.
    // Route::match(['GET', 'POST'], 'debug/headers', function (\Illuminate\Http\Request $request) {
    //     \Illuminate\Support\Facades\Log::debug('Debug headers endpoint called', [
    //         'headers' => $request->headers->all(),
    //         'cookies' => $request->cookies->all(),
    //         'input' => $request->all(),
    //         'session_id' => session()->getId(),
    //         'session' => session()->all(),
    //         'user' => optional($request->user())->id ?? null,
    //         'ip' => $request->ip(),
    //     ]);

    //     return response()->json([
    //         'headers' => $request->headers->all(),
    //         'cookies' => $request->cookies->all(),
    //         'input' => $request->all(),
    //         'session_id' => session()->getId(),
    //         'session' => session()->all(),
    //         'user' => optional($request->user())->id ?? null,
    //     ]);
    // });

    Route::middleware(VerifySupabaseJwt::class)->group(function () {
        Route::get('me', function (\Illuminate\Http\Request $request) {
            \Illuminate\Support\Facades\Log::debug('API /me called', [
                'headers' => $request->headers->all(),
                'cookies' => $request->cookies->all(),
                'session_id' => session()->getId(),
                'session' => session()->all(),
                'supabase_user' => $request->attributes->get('supabase_user'),
                'supabase_user_id' => $request->attributes->get('supabase_user_id'),
                'supabase_user_role' => $request->attributes->get('supabase_user_role'),
            ]);

            return response()->json([
                'user' => [
                    'supabase_id' => $request->attributes->get('supabase_user_id'),
                    'payload' => $request->attributes->get('supabase_user'),
                ],
            ]);
        });

        Route::post('logout', [AuthController::class, 'logout']);
        Route::apiResource('users', UserController::class);
        Route::post('password/send-link', [AuthController::class, 'sendResetLinkAuthenticated']);
        Route::post('password/change', [AuthController::class, 'changePassword']);
    });
});

// Route::middleware('web')->get('/debug/session-inspect', function (\Illuminate\Http\Request $request) {
//     if (app()->environment() !== 'local') {
//         return response()->json(['message' => 'Not available in this environment'], 404);
//     }

//     $cookieRawHeader = $request->headers->get('cookie');
//     $cookieLaravel = $request->cookie('laravel-session'); // decrypted by EncryptCookies when web middleware is used
//     $sessionId = session()->getId();
//     $sessionData = session()->all();

//     $sessionsPath = storage_path('framework/sessions');
//     $recent = [];
//     if (is_dir($sessionsPath)) {
//         $files = collect(File::files($sessionsPath))->sortByDesc->getLastModified();
//         // Build a small array of recent files (name + mtime)
//         $recent = collect(File::files($sessionsPath))->map(function ($f) {
//             return [
//                 'name' => $f->getFilename(),
//                 'mtime' => date('c', $f->getMTime()),
//             ];
//         })->take(12)->values();
//     }

//     // If the decrypted cookie matches a session file, include its contents (first 200 chars)
//     $sessionFileContent = null;
//     if ($cookieLaravel) {
//         $path = $sessionsPath.DIRECTORY_SEPARATOR.$cookieLaravel;
//         if (file_exists($path)) {
//             $sessionFileContent = substr(file_get_contents($path), 0, 800);
//         }
//     }

//     return response()->json([
//         'cookie_header' => $cookieRawHeader,
//         'cookie_decrypted' => $cookieLaravel,
//         'server_session_id' => $sessionId,
//         'server_session' => $sessionData,
//         'recent_session_files' => $recent,
//         'session_file_sample' => $sessionFileContent,
//     ]);
// });
