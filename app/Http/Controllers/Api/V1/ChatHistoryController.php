<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChatActivity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Bus;
use App\Jobs\SaveChatActivity;

class ChatHistoryController extends Controller
{
    /**
     * Store chat history sessions.
     * Accepts a JSON array of sessions (or a single session object).
     */
    public function store(Request $request)
    {
        $payload = $request->json()->all();

        if (empty($payload)) {
            return response()->json(['message' => 'Empty payload'], 400);
        }

        // Normalize to array of sessions
        if (array_keys($payload) !== range(0, count($payload) - 1)) {
            $payload = [$payload];
        }

        $created = [];

        foreach ($payload as $session) {
            try {
                $title = isset($session['title']) ? (string) $session['title'] : '';

                $userId = $request->attributes->get('supabase_user_id') ?? null;

                // If user is logged in, attach to user_id and leave public_id null.
                // If anonymous, ensure a UUID public_id exists (keep incoming uuid if valid, otherwise generate new one).
                $publicId = null;
                if (empty($userId)) {
                    // Generate or validate public_id
                    $incomingPublicId = $session['public_id'] ?? null;
                    if (!empty($incomingPublicId) && Uuid::isValid((string) $incomingPublicId)) {
                        $publicId = (string) $incomingPublicId;
                    } else {
                        $publicId = (string) Str::uuid();
                    }

                    // Ensure session id is also a valid UUID
                    if (empty($session['id']) || !Uuid::isValid((string) $session['id'])) {
                        $session['id'] = (string) Str::uuid();
                    }
                }

                // Dispatch save after response so this endpoint responds quickly
                Bus::dispatchAfterResponse(new SaveChatActivity($session, $userId, $publicId));

                // Add the session to the created list (job has been queued)
                $created[] = $session;
            } catch (\Throwable $e) {
                Log::error('Failed to store chat session', ['error' => $e->getMessage(), 'session' => $session]);
            }
        }

        return response()->json([
            'created' => count($created),
            'data' => $created,
        ], 201);
    }
}
