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
use App\Http\Controllers\Api\V1\UploadController;
use App\Http\Controllers\Api\V1\ChatActivityController;
use App\Http\Middleware\VerifySupabaseJwt;
use App\Http\Controllers\Api\V1\ChatHistoryController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::group([
    'prefix' => 'v1',
    'namespace' => 'App\Http\Controllers\Api\V1',
    // 'middleware' => [\App\Http\Middleware\ForceJsonForApi::class, \App\Http\Middleware\RequireSupabaseForNonGet::class],
], function () {
    // Public auth
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login'])->middleware('web')->name('login');
    Route::post('auth/google', [AuthController::class, 'googleSignIn']);
    Route::post('password/reset', [AuthController::class, 'resetPassword']);

    // New
    Route::post('gemini/generate', GeminiController::class);

    Route::post('upload-image', [UploadController::class, 'uploadImage']);
    Route::apiResource('about-us', AboutUsController::class);
    Route::apiResource('latest-packages', LatestPackagesController::class);
    
    // Medical with slug route
    Route::get('medical/slug/{slug}', [MedicalController::class, 'showBySlug']);
    Route::apiResource('medical', MedicalController::class);
    
    // Hotels with slug route
    Route::get('hotels/slug/{slug}', [HotelsController::class, 'showBySlug']);
    Route::apiResource('hotels', HotelsController::class);
    
    // Medical Equipment with slug route
    Route::get('medical-equipment/slug/{slug}', [MedicalEquipmentController::class, 'showBySlug']);
    Route::apiResource('medical-equipment', MedicalEquipmentController::class);
    
    // Vendors with slug route
    Route::get('vendors/slug/{slug}', [VendorsController::class, 'showBySlug']);
    Route::apiResource('vendors', VendorsController::class);
    
    // Packages with slug route
    Route::get('packages/slug/{slug}', [PackagesController::class, 'showBySlug']);
    Route::apiResource('packages', PackagesController::class);
    
    // Wellness with slug route
    Route::get('wellness/slug/{slug}', [WellnessController::class, 'showBySlug']);
    Route::apiResource('wellness', WellnessController::class);
    
    Route::apiResource('wellness-packages', WellnessPackagesController::class);
    
    // Articles with slug route
    Route::get('articles/slug/{slug}', [ArticleController::class, 'showBySlug']);
    Route::apiResource('articles', ArticleController::class);
    
    // Events with slug route
    Route::get('events/slug/{slug}', [EventController::class, 'showBySlug']);
    Route::apiResource('events', EventController::class);
    
    Route::apiResource('payments', PaymentController::class);
    Route::apiResource('error-logs', ErrorLogController::class);

    Route::post('chat-activities', [ChatHistoryController::class, 'store']);
    Route::get('chat-activities', [ChatActivityController::class, 'index']);
    Route::get('chat-activities/all/{public_id}', [ChatActivityController::class, 'all']);
    Route::delete('chat-activities/all/{public_id}', [ChatActivityController::class, 'destroyByPublicId']);
    Route::get('chat-activities/{session_id}/message/{message_id}', [ChatActivityController::class, 'getMessage']);
    Route::get('chat-activities/{chat_activity}', [ChatActivityController::class, 'show']);
    Route::put('chat-activities/{chat_activity}', [ChatActivityController::class, 'update']);
    Route::patch('chat-activities/{chat_activity}', [ChatActivityController::class, 'update']);
    Route::delete('chat-activities/{chat_activity}', [ChatActivityController::class, 'destroy']);

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

    Route::middleware(VerifySupabaseJwt::class)->group(function () {
        Route::get('me', function (\Illuminate\Http\Request $request) {

            $supabase = $request->attributes->get('supabase_user');
            $supabaseId = $request->attributes->get('supabase_user_id');
            $email = $supabase->email ?? null;

            $account = null;
            if ($supabaseId) {
                $account = \App\Models\Account::find($supabaseId);
            }

            if (! $account && $email) {
                $account = \App\Models\Account::where('email', $email)->first();
            }

            if (! $account) {
                $account = new \App\Models\Account();
                if ($supabaseId) {
                    $account->id = $supabaseId;
                }
                $account->email = $email;

                $fullname = null;
                if (isset($supabase->user_metadata) && is_object($supabase->user_metadata)) {
                    $fullname = $supabase->user_metadata->full_name ?? $supabase->user_metadata->name ?? null;
                }
                $account->fullname = $fullname;
                $account->save();
            }

            $userMeta = $supabase->user_metadata ?? null;
            $isGoogle = $userMeta && isset($userMeta->iss) && str_contains($userMeta->iss, 'google.com');
            $googleFullname = $isGoogle ? ($userMeta->full_name ?? $userMeta->name ?? null) : null;
            $googleAvatar = $isGoogle ? ($userMeta->avatar_url ?? $userMeta->picture ?? null) : null;

            $resp = [
                'id' => (string) $account->id,
                'created_at' => $account->created_at ? $account->created_at->toISOString() : null,
                'updated_at' => $account->updated_at ? $account->updated_at->toISOString() : null,
                'email' => $account->email,
                'fullname' => $account->fullname,
                'phone' => $account->phone,
                'gender' => $account->gender,
                'domicile' => $account->domicile,
                'height' => $account->height,
                'weight' => $account->weight,
                'avatar_url' => $account->avatar_url,
                'birthdate' => $account->birthdate ? $account->birthdate->toDateString() : null,
                'google_fullname' => $googleFullname,
                'google_avatar' => $googleAvatar,
            ];

            return response()->json($resp);
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
