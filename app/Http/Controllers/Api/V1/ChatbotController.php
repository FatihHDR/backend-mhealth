<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Chatbot;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ChatbotController extends Controller
{
    public function index()
    {
        $chatbots = Chatbot::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return response()->json($chatbots);
    }

    public function show($id)
    {
        $chatbot = Chatbot::with('user')->findOrFail($id);
        return response()->json($chatbot);
    }

    public function byUser($userId)
    {
        $chatbots = Chatbot::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return response()->json($chatbots);
    }

    public function byToken($token)
    {
        $chatbot = Chatbot::where('public_token', $token)
            ->with('user')
            ->firstOrFail();
        return response()->json($chatbot);
    }
}
