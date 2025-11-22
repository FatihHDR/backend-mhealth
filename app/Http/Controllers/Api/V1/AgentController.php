<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AiAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function __invoke(Request $request, AiAgent $agent): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string'],
            'options' => ['sometimes', 'array'],
        ]);

        $result = $agent->respondTo($validated['prompt'], $validated['options'] ?? []);

        return response()->json($result);
    }
}
