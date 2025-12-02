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

    /**
     * Generate a short, descriptive title for a chat session based on the first message and reply.
     * This mimics how ChatGPT/Gemini generates conversation titles.
     */
    private function generateChatTitle(GeminiClient $client, string $userMessage, string $botReply): string
    {
        try {
            // Prompt khusus untuk generate judul singkat
            $titlePrompt = <<<PROMPT
Buatkan judul singkat (maksimal 5-7 kata) untuk percakapan berikut. Judul harus merangkum topik utama percakapan.

Aturan:
- Gunakan bahasa yang sama dengan pesan user
- Jangan gunakan tanda kutip
- Jangan gunakan tanda baca di akhir
- Langsung tulis judulnya saja, tanpa kata "Judul:" atau penjelasan lain

Percakapan:
User: {$userMessage}
Assistant: {$botReply}

Judul singkat:
PROMPT;

            Log::debug('Generating chat title', ['user_message' => mb_substr($userMessage, 0, 100)]);

            $response = $client->generateText($titlePrompt, [
                'temperature' => 0.3,
                'maxOutputTokens' => 50,
            ]);

            $title = $this->extractTextFromResponse($response);
            
            Log::debug('Generated title raw', ['raw_title' => $title]);
            
            // Clean up the title
            $title = trim($title, " \t\n\r\0\x0B\"'");
            $title = preg_replace('/^(Title:|Judul:|Judul singkat:)\s*/i', '', $title);
            $title = trim($title, " \t\n\r\0\x0B\"'.:"); // Remove trailing punctuation too
            
            // Limit length and fallback if empty
            if (empty($title) || mb_strlen($title) > 100) {
                Log::warning('Title generation fallback - empty or too long', ['title_length' => mb_strlen($title)]);
                return mb_substr($userMessage, 0, 50) . (mb_strlen($userMessage) > 50 ? '...' : '');
            }

            Log::info('Chat title generated successfully', ['title' => $title]);
            return $title;
        } catch (\Throwable $e) {
            Log::warning('Failed to generate chat title', ['error' => $e->getMessage()]);
            // Fallback to first 50 chars of user message
            return mb_substr($userMessage, 0, 50) . (mb_strlen($userMessage) > 50 ? '...' : '');
        }
    }

    public function __invoke(Request $request, GeminiClient $client): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string'],
            'options' => ['sometimes', 'array'],
            'messages' => ['sometimes', 'array'],
            'messages.*.sender' => ['required_with:messages', 'string'],
            'messages.*.message' => ['required_with:messages', 'string'],
            'session_id' => ['sometimes', 'string', 'nullable'],
            'public_id' => ['sometimes', 'string', 'nullable'],
            'new_session' => ['sometimes', 'boolean'],
            'reply_to' => ['sometimes', 'string', 'nullable'], // ID pesan yang di-reply
        ]);

        $systemInstruction = 'You are Mei, a gentle, empathetic, and informative virtual health assistant. '.
            'Speak naturally, politely, and with a warm feminine tone as a caring female health assistant. '.
            "When the user's message suggests an emergency, immediately advise them to call {$this->emergencyNumber} ".
            "and include the word 'consultation' at the end of your message to prompt for a professional follow-up.";

        $messageCount = 0;
        if (! empty($validated['messages']) && is_array($validated['messages'])) {
            $messageCount = count($validated['messages']);
        }

        $shouldSuggestDoctor = $messageCount >= 6;

        if ($shouldSuggestDoctor) {
            $systemInstruction .= ' Since you have been chatting for a while about health concerns, '.
                'gently suggest that the user consider consulting with a professional doctor for a more thorough examination. '.
                'Remind them that while you can provide general health information, a doctor can give personalized medical advice. '.
                'Include "consultation" at the end of your response to show the consultation button.';
        }

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
                    $fullPromptParts[] = "Assistant: {$text}";
                }
            }
        }

        $fullPromptParts[] = "User: " . $validated['prompt'];

        $fullPrompt = implode("\n", $fullPromptParts);

        Log::debug('GeminiController request received', [
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'referer' => $request->header('Referer'),
        ]);

        $response = $client->generateText(
            $fullPrompt,
            $validated['options'] ?? []
        );

        // Log raw response structure for debugging
        Log::debug('Gemini API raw response structure', [
            'has_candidates' => isset($response['candidates']),
            'candidates_count' => count($response['candidates'] ?? []),
            'first_candidate_keys' => array_keys($response['candidates'][0] ?? []),
        ]);

        $replyText = $this->extractTextFromResponse($response);

        // Log if extracted text looks suspicious (too short or encoded)
        if (strlen($replyText) < 10 || preg_match('/^[A-Za-z0-9+\/=]{50,}$/', substr($replyText, 0, 100))) {
            Log::warning('Gemini extracted text looks suspicious', [
                'text_length' => strlen($replyText),
                'text_preview' => substr($replyText, 0, 200),
                'raw_response' => $response,
            ]);
        }

        $urgent = $this->detectEmergency($validated['prompt'].' '.$replyText);

        if ($urgent) {
            $suffix = "\n\nJika ini darurat, segera hubungi {$this->emergencyNumber}.\n\nconsultation";
            if (stripos($replyText, (string) $this->emergencyNumber) === false) {
                $replyText = trim($replyText).$suffix;
            } elseif (stripos($replyText, 'consultation') === false) {
                $replyText = trim($replyText)."\n\nconsultation";
            }
        }

        $currentMessageCount = $messageCount;

        try {
            $userId = $request->attributes->get('supabase_user_id') ?? null;

            $tsUser = (int) (microtime(true) * 1000);
            $tsBot = $tsUser + 10;

            $incomingSessionId = $validated['session_id'] ?? null;
            $incomingPublicId = $validated['public_id'] ?? null;
            $forceNewSession = $validated['new_session'] ?? false;
            $replyToId = $validated['reply_to'] ?? null; // Message ID yang di-reply
            $existingSession = null;

            Log::debug('GeminiController session lookup', [
                'incoming_session_id' => $incomingSessionId,
                'incoming_public_id' => $incomingPublicId,
                'force_new_session' => $forceNewSession,
                'user_id' => $userId,
            ]);

            if (! $forceNewSession && ! empty($incomingSessionId)) {
                $existingSession = ChatActivity::find($incomingSessionId);
            }

            if ($existingSession) {
                $sessionData = $existingSession->chat_activity_data;
                $existingMessagesCount = count($sessionData['messages'] ?? []);
                $messageCount = max($messageCount, $existingMessagesCount);
            }

            Log::debug('GeminiController session found', [
                'found' => $existingSession ? true : false,
                'existing_id' => $existingSession?->id,
                'existing_public_id' => $existingSession?->public_id,
            ]);

            $publicId = $incomingPublicId ?? (string) Str::uuid();
            
            $sessionId = null;

            if ($existingSession) {
                $sessionData = $existingSession->chat_activity_data;
                $existingMessages = $sessionData['messages'] ?? [];

                // Cari pesan yang di-reply (jika ada)
                $repliedMessage = null;
                if ($replyToId) {
                    foreach ($existingMessages as $msg) {
                        if (isset($msg['id']) && $msg['id'] === $replyToId) {
                            $repliedMessage = [
                                'id' => $msg['id'],
                                'message' => $msg['message'],
                                'sender' => $msg['sender'],
                            ];
                            break;
                        }
                    }
                }

                $existingMessages[] = [
                    'id' => (string) $tsUser,
                    'message' => $validated['prompt'],
                    'sender' => 'user',
                    'timestamp' => now()->toIso8601String(),
                    'replyTo' => $repliedMessage, // Null jika tidak reply
                ];

                $existingMessages[] = [
                    'id' => (string) $tsBot,
                    'message' => $replyText,
                    'sender' => 'bot',
                    'timestamp' => now()->toIso8601String(),
                    'replyTo' => null,
                ];

                $publicId = $existingSession->public_id;
                $sessionId = $existingSession->id;

                $session = [
                    'id' => $sessionId,
                    'title' => $sessionData['title'] ?? substr($validated['prompt'], 0, 200),
                    'messages' => $existingMessages,
                    'updatedAt' => now()->toIso8601String(),
                ];

                $userId = $existingSession->user_id ?? $userId;
            } else {
                $sessionId = (string) Str::uuid();

                // Generate AI-powered title for new sessions
                // This mimics how ChatGPT/Gemini generates conversation titles
                $generatedTitle = $this->generateChatTitle($client, $validated['prompt'], $replyText);

                $session = [
                    'id' => $sessionId,
                    'title' => $generatedTitle,
                    'messages' => [
                        [
                            'id' => (string) $tsUser,
                            'message' => $validated['prompt'],
                            'sender' => 'user',
                            'timestamp' => now()->toIso8601String(),
                            'replyTo' => null, // First message tidak ada reply
                        ],
                        [
                            'id' => (string) $tsBot,
                            'message' => $replyText,
                            'sender' => 'bot',
                            'timestamp' => now()->toIso8601String(),
                            'replyTo' => null,
                        ],
                    ],
                    'updatedAt' => now()->toIso8601String(),
                ];
            }

            // Dispatch saving after response to avoid blocking the API response
            Bus::dispatchAfterResponse(new SaveChatActivity($session, $userId, $publicId));

            // Update currentMessageCount to reflect the actual count from existing session
            $currentMessageCount = count($session['messages'] ?? []);
        } catch (\Throwable $e) {
            Log::error('Failed to persist chat activity', ['error' => $e->getMessage()]);
            // Fallback: use the message count from request + new exchange
            $currentMessageCount = $messageCount + 2;
        }

        // Add doctor consultation suggestion to reply text after 4 exchanges (8 messages)
        // Only if not already urgent (which already has consultation suggestion)
        if (!$urgent && $currentMessageCount >= 8 && stripos($replyText, 'consultation') === false) {
            $doctorSuggestion = "\n\n💡 *Sudah beberapa kali kita berdiskusi tentang kesehatan Anda. Untuk penanganan yang lebih tepat dan menyeluruh, saya sarankan untuk berkonsultasi langsung dengan dokter kami ya!*\n\nconsultation";
            $replyText = trim($replyText) . $doctorSuggestion;
        }

        $packageSuggestions = $this->detectPackageRecommendations($validated['prompt'].' '.$replyText);

        // Use the actual message count (from session) for consultation suggestion
        // Suggest consultation after 4 exchanges (8 messages)
        $suggestConsultation = $currentMessageCount >= 8;

        $actions = [];
        if ($urgent) {
            $actions[] = [
                'type' => 'consultation',
                'label' => 'Konsultasi dengan Dokter',
                'url' => '/consultation',
                'reason' => 'urgent',
            ];
        } elseif ($suggestConsultation) {
            // Add consultation suggestion after 4-5 chat exchanges
            $actions[] = [
                'type' => 'consultation',
                'label' => 'Konsultasi dengan Dokter',
                'url' => '/consultation',
                'reason' => 'extended_chat',
                'message' => 'Untuk penanganan lebih lanjut, Anda bisa berkonsultasi langsung dengan dokter kami.',
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
            'session_id' => $sessionId ?? null,  // unique per chat session (primary key)
            'public_id' => $publicId ?? null,    // persistent per user/device
            'title' => $session['title'] ?? null, // AI-generated title for the session
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
        // Gemini API response structure:
        // {
        //   "candidates": [
        //     {
        //       "content": {
        //         "parts": [
        //           { "text": "The actual response text" }
        //         ]
        //       }
        //     }
        //   ]
        // }

        // Try to extract from the correct path first
        $candidates = $response['candidates'] ?? [];
        if (!empty($candidates)) {
            $firstCandidate = $candidates[0] ?? [];
            $content = $firstCandidate['content'] ?? [];
            $parts = $content['parts'] ?? [];
            
            if (!empty($parts)) {
                $firstPart = $parts[0] ?? [];
                $text = $firstPart['text'] ?? null;
                
                if ($text !== null && is_string($text)) {
                    return trim($text);
                }
            }
        }

        // Fallback: try to find 'text' key anywhere in response
        $text = $this->findTextInResponse($response);
        if ($text !== null) {
            return trim($text);
        }

        // Last resort: collect all strings and return the longest readable one
        $strings = [];
        $this->collectStringsRecursive($response, $strings);

        if (empty($strings)) {
            Log::warning('Gemini response has no extractable text', ['response' => $response]);
            return '';
        }

        // Filter out base64-like strings (they contain mostly alphanumeric + /+=)
        $readableStrings = array_filter($strings, function($s) {
            // Skip if it looks like base64 (mostly alphanumeric, +, /, =)
            if (strlen($s) > 100 && preg_match('/^[A-Za-z0-9+\/=]+$/', $s)) {
                return false;
            }
            // Skip if it has very few spaces (likely encoded data)
            $spaceRatio = substr_count($s, ' ') / max(strlen($s), 1);
            if (strlen($s) > 50 && $spaceRatio < 0.05) {
                return false;
            }
            return true;
        });

        if (empty($readableStrings)) {
            Log::warning('Gemini response contains only encoded data', ['response' => $response]);
            return 'Maaf, terjadi kesalahan dalam memproses respons. Silakan coba lagi.';
        }

        usort($readableStrings, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        return trim($readableStrings[0]);
    }

    /**
     * Recursively find 'text' key in response array
     */
    private function findTextInResponse(array $data): ?string
    {
        if (isset($data['text']) && is_string($data['text'])) {
            return $data['text'];
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $result = $this->findTextInResponse($value);
                if ($result !== null) {
                    return $result;
                }
            }
        }

        return null;
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
