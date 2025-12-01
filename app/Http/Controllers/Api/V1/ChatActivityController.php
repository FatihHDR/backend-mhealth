<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChatActivity;
use Illuminate\Support\Facades\Log;

class ChatActivityController extends Controller
{
    /**
     * Display a listing of chat sessions.
     * Supports optional filtering by `public_id` or `user_id` and pagination.
     */
    public function index(Request $request)
    {
        $query = ChatActivity::query();

        if ($public = $request->query('public_id')) {
            $query->where('public_id', $public);
        }

        if ($user = $request->query('user_id')) {
            $query->where('user_id', $user);
        }

        $perPage = (int) $request->query('per_page', 20);
        $data = $query->orderBy('updated_at', 'desc')->paginate($perPage);

        return response()->json($data);
    }

    /**
     * Get ALL chat sessions for a specific public_id (no pagination).
     * Endpoint: GET /chat-activities/all/{public_id}
     */
    public function all(string $public_id)
    {
        $sessions = ChatActivity::where('public_id', $public_id)
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json([
            'data' => $sessions,
            'total' => $sessions->count(),
        ]);
    }

    /**
     * Display the specified chat session.
     */
    public function show($id)
    {
        // Try to find by primary id first
        $session = ChatActivity::find($id);

        // If not found, allow lookup by public_id (useful when frontend supplies session public id)
        if (! $session) {
            $session = ChatActivity::where('public_id', $id)->first();
        }

        if (! $session) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($session);
    }

    /**
     * Update the specified session (title or chat_activity_data).
     */
    public function update(Request $request, $id)
    {
        $session = ChatActivity::find($id);
        if (! $session) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $data = $request->validate([
            'title' => 'sometimes|string|nullable',
            'chat_activity_data' => 'sometimes|array',
        ]);

        try {
            $session->fill($data);
            $session->save();
        } catch (\Throwable $e) {
            Log::error('Failed to update chat session', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json(['message' => 'Update failed'], 500);
        }

        return response()->json($session);
    }

    /**
     * Remove the specified session from storage.
     */
    public function destroy($id)
    {
        $session = ChatActivity::find($id);
        if (! $session) {
            return response()->json(['message' => 'Not found'], 404);
        }

        try {
            $session->delete();
        } catch (\Throwable $e) {
            Log::error('Failed to delete chat session', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json(['message' => 'Delete failed'], 500);
        }

        return response()->json(['message' => 'Deleted'], 200);
    }
}
