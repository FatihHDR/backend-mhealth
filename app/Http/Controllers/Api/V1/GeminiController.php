<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\GeminiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeminiController extends Controller
{
    public function __invoke(Request $request, GeminiClient $client): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string'],
            'options' => ['sometimes', 'array'],
        ]);

        $response = $client->generateText(
            $validated['prompt'],
            $validated['options'] ?? []
        );

        return response()->json($response);
    }
}
