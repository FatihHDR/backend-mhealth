<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\Paginates;
use Illuminate\Http\Request;
use App\Models\ChatActivity;
use Illuminate\Support\Facades\Log;

class ChatActivityController extends Controller
{
    use Paginates;
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
     * Get ALL chat sessions for a specific public_id or user_id with pagination support.
     * Endpoint: GET /chat-activities/all/{id}
     * Parameter {id} can be either public_id or user_id
     * 
     * Query Parameters:
     * - per_page: Number of items per page (default: 15, max: 100, or 'all' for no pagination)
     * - page: Page number (default: 1)
     * 
     * Examples:
     * - GET /chat-activities/all/{id}?per_page=20&page=1
     * - GET /chat-activities/all/{id}?per_page=all (returns all without pagination)
     */
    public function all(string $id)
    {
        $query = ChatActivity::where('public_id', $id)
            ->orWhere('user_id', $id)
            ->orderBy('updated_at', 'desc');

        return response()->json($this->paginateQuery($query));
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
     * Get specific message from a chat session.
     * Endpoint: GET /api/v1/chat-activities/{session_id}/message/{message_id}
     */
    public function getMessage(string $sessionId, string $messageId)
    {
        $session = ChatActivity::find($sessionId);

        if (! $session) {
            return response()->json(['message' => 'Session not found'], 404);
        }

        $messages = $session->chat_activity_data['messages'] ?? [];
        
        foreach ($messages as $message) {
            if (isset($message['id']) && $message['id'] === $messageId) {
                return response()->json([
                    'message' => $message,
                    'session_id' => $session->id,
                    'session_title' => $session->title,
                ]);
            }
        }

        return response()->json(['message' => 'Message not found'], 404);
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

    /**
     * Remove ALL sessions for a specific public_id or user_id.
     * Endpoint: DELETE /chat-activities/all/{id}
     * Parameter {id} can be either public_id or user_id
     */
    public function destroyByPublicId(string $id)
    {
        try {
            $count = ChatActivity::where('public_id', $id)
                ->orWhere('user_id', $id)
                ->count();

            if ($count === 0) {
                return response()->json(['message' => 'No sessions found for this id'], 404);
            }

            ChatActivity::where('public_id', $id)
                ->orWhere('user_id', $id)
                ->delete();

            return response()->json([
                'message' => 'All sessions deleted',
                'deleted_count' => $count,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('Failed to delete sessions by id', [
                'error' => $e->getMessage(),
                'id' => $id,
            ]);
            return response()->json(['message' => 'Delete failed'], 500);
        }
    }
}
