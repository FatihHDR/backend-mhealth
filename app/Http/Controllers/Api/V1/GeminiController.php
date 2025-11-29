<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Packages;
use App\Services\GeminiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\ChatActivity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Bus;
use App\Jobs\SaveChatActivity;

class GeminiController extends Controller
{
    private string $emergencyNumber = '08159880048';

    /** @var string[] */
    private array $emergencyKeywords = [
        'emergency', 'darurat', 'chest pain', 'severe bleeding', 'bleeding', 'unconscious', 'pingsan',
        'kejang', 'shortness of breath', 'suffocat', 'suffocating', 'suicide', 'bunuh diri', 'mati',
        'stopped breathing', 'not breathing', 'no pulse', 'cardiac arrest',
    ];

    public function __invoke(Request $request, GeminiClient $client): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string'],
            'options' => ['sometimes', 'array'],
            // Optional chat history: array of { sender: 'user'|'bot'|'ai', message: string }
            'messages' => ['sometimes', 'array'],
            'messages.*.sender' => ['required_with:messages', 'string'],
            'messages.*.message' => ['required_with:messages', 'string'],
        ]);

        $systemInstruction = 'You are Mei, a gentle, empathetic, and informative virtual health assistant. '.
            'Speak naturally, politely, and with a warm feminine tone as a caring female health assistant. '.
            "When the user's message suggests an emergency, immediately advise them to call {$this->emergencyNumber} ".
            "and include the word 'consultation' at the end of your message to prompt for a professional follow-up.";

        // Build stacked conversation from optional history messages, then append the new user prompt.
        $fullPromptParts = [$systemInstruction, ''];

        if (! empty($validated['messages']) && is_array($validated['messages'])) {
            foreach ($validated['messages'] as $m) {
                $sender = strtolower($m['sender'] ?? 'user');
                $text = trim($m['message'] ?? '');
                if ($text === '') {
                    continue;
                }

                if (in_array($sender, ['user', 'u', 'me', 'saya', 'client'], true)) {
                    $fullPromptParts[] = "User: {$text}";
                } else {
                    // treat any other sender as assistant/bot
                    $fullPromptParts[] = "Assistant: {$text}";
                }
            }
        }

        // Append the latest user prompt as the final user message
        $fullPromptParts[] = "User: " . $validated['prompt'];

        $fullPrompt = implode("\n", $fullPromptParts);

        $response = $client->generateText(
            $fullPrompt,
            $validated['options'] ?? []
        );

        $replyText = $this->extractTextFromResponse($response);

        $urgent = $this->detectEmergency($validated['prompt'].' '.$replyText);

        if ($urgent) {
            $suffix = "\n\nJika ini darurat, segera hubungi {$this->emergencyNumber}.\n\nconsultation";
            if (stripos($replyText, (string) $this->emergencyNumber) === false) {
                $replyText = trim($replyText).$suffix;
            } elseif (stripos($replyText, 'consultation') === false) {
                $replyText = trim($replyText)."\n\nconsultation";
            }
        }

        try {
            $userId = $request->attributes->get('supabase_user_id') ?? null;

            $tsUser = (int) (microtime(true) * 1000);
            $tsBot = $tsUser + 10;

            // If anonymous, generate a UUID public id; if logged in, attach to user_id
            $publicId = null;
            if (empty($userId)) {
                $publicId = (string) Str::uuid();
            }

            // Use the publicId as session id for anonymous sessions so clients can reference it
            $sessionId = $publicId ?? '_'.substr(bin2hex(random_bytes(8)), 0, 20);

            $session = [
                'id' => $sessionId,
                'title' => substr($validated['prompt'], 0, 200),
                'messages' => [
                    [
                        'id' => (string) $tsUser,
                        'message' => $validated['prompt'],
                        'sender' => 'user',
                        'timestamp' => now()->toIso8601String(),
                        'replyTo' => null,
                    ],
                    [
                        'id' => (string) $tsBot,
                        'message' => $replyText,
                        'sender' => 'bot',
                        'timestamp' => now()->toIso8601String(),
                    ],
                ],
                'updatedAt' => now()->toIso8601String(),
            ];

            // Dispatch saving after response to avoid blocking the API response
            Bus::dispatchAfterResponse(new SaveChatActivity($session, $userId, $publicId));
        } catch (\Throwable $e) {
            Log::error('Failed to persist chat activity', ['error' => $e->getMessage()]);
        }

        $packageSuggestions = $this->detectPackageRecommendations($validated['prompt'].' '.$replyText);

        $actions = [];
        if ($urgent) {
            $actions[] = [
                'type' => 'consultation',
                'label' => 'Konsultasi dengan Dokter',
                'url' => '/consultation',
            ];
        }

        if (! empty($packageSuggestions)) {
            $actions[] = [
                'type' => 'packages',
                'label' => 'Package yang Disarankan',
                'packages' => $packageSuggestions,
            ];
        }

        return response()->json([
            'reply' => $replyText,
            'raw' => $response,
            'urgent' => $urgent,
            'actions' => $actions,
        ]);
    }

    private function detectPackageRecommendations(string $text): array
    {
        $hay = strtolower($text);

        $packageKeywords = ['paket', 'package', 'rekomendasi paket', 'saran paket', 'butuh paket', 'paket yang disarankan', 'treatment package'];

        $hasPackageKeyword = false;
        foreach ($packageKeywords as $kw) {
            if (strpos($hay, $kw) !== false) {
                $hasPackageKeyword = true;
                break;
            }
        }

        // Load available packages (safe fallback to empty if DB not available)
        try {
            $allPackages = Packages::select('id', 'name', 'description', 'price', 'image')->get();
        } catch (\Throwable $e) {
            return [];
        }

        $matched = [];
        foreach ($allPackages as $pkg) {
            if (stripos($hay, strtolower($pkg->name)) !== false) {
                $matched[] = [
                    'id' => $pkg->id,
                    'name' => $pkg->name,
                    'description' => $pkg->description,
                    'price' => $pkg->price,
                    'image' => $pkg->image,
                ];
            }
        }

        // If explicit matches found, return them
        if (! empty($matched)) {
            return $matched;
        }

        // If user mentioned packages in general, return top 3 suggestions
        if ($hasPackageKeyword) {
            $suggest = [];
            foreach ($allPackages->take(3) as $pkg) {
                $suggest[] = [
                    'id' => $pkg->id,
                    'name' => $pkg->name,
                    'description' => $pkg->description,
                    'price' => $pkg->price,
                    'image' => $pkg->image,
                ];
            }

            return $suggest;
        }

        return [];
    }

    private function detectEmergency(string $text): bool
    {
        $hay = strtolower($text);
        foreach ($this->emergencyKeywords as $kw) {
            if (strpos($hay, strtolower($kw)) !== false) {
                return true;
            }
        }

        return false;
    }

    private function extractTextFromResponse(array $response): string
    {
        $strings = [];
        $this->collectStringsRecursive($response, $strings);

        if (empty($strings)) {
            return '';
        }

        usort($strings, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        return trim($strings[0]);
    }

    private function collectStringsRecursive($value, array &$out)
    {
        if (is_string($value)) {
            $clean = trim(preg_replace('/\s+/', ' ', $value));
            if ($clean !== '') {
                $out[] = $clean;
            }

            return;
        }

        if (is_array($value)) {
            foreach ($value as $v) {
                $this->collectStringsRecursive($v, $out);
            }
        }
    }
}
