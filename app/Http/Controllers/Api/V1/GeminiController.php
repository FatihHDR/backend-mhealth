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
     * Uses retry mechanism to ensure AI-generated title.
     */
    private function generateChatTitle(GeminiClient $client, string $userMessage, string $botReply): string
    {
        $maxRetries = 2;
        $attempt = 0;
        
        while ($attempt <= $maxRetries) {
            try {
                $attempt++;
                
                // Detect language from user message
                $language = $this->detectLanguageForTitle($userMessage);
                
                // Simplified prompt for better results
                $titlePrompt = <<<PROMPT
                Generate a descriptive title (2-6 words) summarizing this health conversation.

                Important rules:
                - Use the SAME language as the user's message
                - Create a meaningful phrase, NOT just one word
                - Focus on the main health complaint or topic
                - NO quotation marks, NO punctuation at end
                - Output ONLY the title

                User: {$userMessage}
                Assistant: {$botReply}

                Title:
                PROMPT;

                Log::debug('Generating chat title', [
                    'attempt' => $attempt,
                    'user_message' => mb_substr($userMessage, 0, 100),
                    'language' => $language,
                ]);

                $response = $client->generateText($titlePrompt, [
                    'generationConfig' => [
                        'temperature' => 0.5,
                        'maxOutputTokens' => 30,
                    ],
                ]);

                $title = $this->extractTextFromResponse($response);
                
                Log::debug('Generated title raw', [
                    'raw_title' => $title,
                    'attempt' => $attempt,
                    'response_structure' => json_encode($response),
                ]);
                
                // Clean up the title
                $title = trim($title, " \t\n\r\0\x0B\"'");
                $title = preg_replace('/^(Title:|Judul:|Judul singkat:|Başlık:)\s*/i', '', $title);
                $title = trim($title, " \t\n\r\0\x0B\"'.:"); // Remove trailing punctuation too
                
                Log::debug('Title after cleanup', [
                    'cleaned_title' => $title,
                    'length' => mb_strlen($title),
                ]);
                
                // Count words in title
                $wordCount = str_word_count($title, 0, 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýþÿ');
                
                // Validate title quality: must be 2-8 words, minimum 5 characters
                if (empty($title) || mb_strlen($title) < 5 || $wordCount < 2 || $wordCount > 8) {
                    Log::warning('Title invalid (too short/too few words/too many words), retrying', [
                        'title' => $title,
                        'word_count' => $wordCount,
                        'char_length' => mb_strlen($title),
                        'attempt' => $attempt,
                    ]);
                    
                    if ($attempt <= $maxRetries) {
                        sleep(1); // Brief pause before retry
                        continue; // Retry
                    }
                }
                
                // Filter out invalid titles (technical strings)
                $invalidPatterns = ['gemini', 'flash', 'preview', 'model', 'version', 'api'];
                $isInvalid = false;
                foreach ($invalidPatterns as $pattern) {
                    if (stripos($title, $pattern) !== false) {
                        Log::warning('Title contains invalid pattern, retrying', [
                            'title' => $title,
                            'pattern' => $pattern,
                            'attempt' => $attempt,
                        ]);
                        $isInvalid = true;
                        break;
                    }
                }
                
                if ($isInvalid && $attempt <= $maxRetries) {
                    sleep(1);
                    continue; // Retry
                } elseif ($isInvalid) {
                    // All retries exhausted, use fallback
                    Log::error('All AI title generation attempts failed, using fallback');
                    return $this->generateFallbackTitle($userMessage);
                }
                
                // Valid title - check length
                if (mb_strlen($title) > 100) {
                    $title = mb_substr($title, 0, 80);
                }

                Log::info('Chat title generated successfully', [
                    'title' => $title,
                    'attempt' => $attempt,
                ]);
                return $title;
                
            } catch (\Throwable $e) {
                Log::warning('Title generation attempt failed', [
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                ]);
                
                if ($attempt > $maxRetries) {
                    // All retries exhausted
                    Log::error('All AI title generation attempts failed with errors, using fallback');
                    return $this->generateFallbackTitle($userMessage);
                }
                
                sleep(1); // Brief pause before retry
                continue;
            }
        }
        
        // Should not reach here, but just in case
        return $this->generateFallbackTitle($userMessage);
    }

    /**
     * Generate a smart fallback title from user message
     * Extracts key topic words instead of just copying first 50 chars
     */
    public function generateFallbackTitle(string $userMessage): string
    {
        $cleanMessage = preg_replace('/^(hello|hi|halo|hai|hey|good morning|good afternoon|selamat pagi|selamat siang|ola)[,!\s]*/i', '', $userMessage);
        $cleanMessage = trim($cleanMessage);
        
        // Safety check: if message is empty after cleaning, return default
        if (empty($cleanMessage) || mb_strlen($cleanMessage) < 2) {
            return 'New Conversation';
        }
        
        if (mb_strlen($cleanMessage) <= 50) {
            return $cleanMessage;
        }
        
        $sentences = preg_split('/[.!?]/', $cleanMessage, 2);
        $firstSentence = trim($sentences[0]);
        
        if (mb_strlen($firstSentence) <= 50 && mb_strlen($firstSentence) > 10) {
            return $firstSentence;
        }
        
        if (mb_strlen($cleanMessage) > 50) {
            $truncated = mb_substr($cleanMessage, 0, 50);
            $lastSpace = mb_strrpos($truncated, ' ');
            if ($lastSpace !== false && $lastSpace > 20) {
                return mb_substr($cleanMessage, 0, $lastSpace) . '...';
            }
        }
        
        return mb_substr($cleanMessage, 0, 50) . '...';
    }

    /**
     * Simple language detection for title generation
     */
    private function detectLanguageForTitle(string $text): string
    {
        $hay = strtolower($text);
        
        // Indonesian indicators
        $indonesianWords = [
            'saya', 'aku', 'kamu', 'anda', 'yang', 'dan', 'dengan', 'untuk', 'dari', 'ini', 'itu', 'apa', 'bagaimana',
            'halo', 'hai', 'pagi', 'siang', 'sore', 'malam', 'apa kabar'
        ];
        $idCount = 0;
        foreach ($indonesianWords as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $hay)) {
                $idCount++;
            }
        }
        
        // Spanish/Portuguese indicators
        $latinWords = ['hola', 'ola', 'como', 'que', 'esta', 'por', 'para', 'cuando'];
        $latinCount = 0;
        foreach ($latinWords as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $hay)) {
                $latinCount++;
            }
        }
        
        if ($idCount >= 1) return 'Indonesian';
        if ($latinCount >= 1) return 'Spanish/Portuguese';
        
        // If not clearly something else, default to Indonesian
        return 'Indonesian'; 
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
            'user_id' => ['sometimes', 'string', 'nullable'], // From Supabase auth
            'new_session' => ['sometimes', 'boolean'],
            'replyTo' => ['sometimes', 'nullable'], // Accept camelCase from frontend
            'status' => ['sometimes', 'string', 'in:public,private'],
        ]);

        $messageCount = 0;
        if (! empty($validated['messages']) && is_array($validated['messages'])) {
            $messageCount = count($validated['messages']);
        }

        $systemInstruction = 'You are Mei, a gentle, empathetic, and informative virtual health assistant. '.
            'Speak naturally, politely, and with a warm feminine tone as a caring female health assistant. '.
            'Primary and default language is INDONESIAN. Even for brief messages, respond in Indonesian. '.
            'Answer questions directly without introducing yourself or using greetings like "Halo", "Hi", etc. '.
            'Get straight to answering the user\'s question in a friendly but professional manner. '.
            "\n\n🚫 ABSOLUTE FORBIDDEN WORDS IN INITIAL RESPONSES 🚫\n".
            "NEVER use these words when user FIRST mentions symptoms (in first 1-3 exchanges):\n".
            "- consultation / konsultasi / consult\n".
            "- doctor / dokter / physician\n".
            "- emergency / darurat (except for life-threatening cases)\n".
            "- hospital / rumah sakit / clinic\n\n".
            "✅ CORRECT BEHAVIOR:\n".
            "1. FIRST: Ask follow-up questions about symptoms (duration, severity 1-10, other symptoms)\n".
            "2. THEN: Provide health info, self-care tips, and education\n".
            "3. ONLY AFTER gathering sufficient info through multiple exchanges: Suggest consultation if truly needed\n".
            "4. For SEVERE life-threatening symptoms only: Immediately advise to call {$this->emergencyNumber}\n\n".
            "Focus on understanding the user's condition thoroughly before making any referrals.";

        // Remove the shouldSuggestDoctor logic from system instruction
        // We'll handle consultation suggestions differently based on message count

        // Note: messages array will be populated from existing session later in the code
        // This will be rebuilt after loading session data
        $fullPromptParts = [$systemInstruction, ''];

        // This section will be executed later after loading session

        Log::debug('GeminiController request received', [
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'referer' => $request->header('Referer'),
        ]);

        $currentMessageCount = $messageCount;        
        // Initialize variables outside try block to ensure they're available throughout
        $isLifeThreatening = false;
        $explicitConsultationRequest = false;
        $urgent = false;
        $replyText = '';
        try {
            // Get user_id from request body first (from frontend), then from middleware
            $userId = $validated['user_id'] ?? $request->attributes->get('supabase_user_id') ?? null;

            $tsUser = (int) (microtime(true) * 1000);
            $tsBot = $tsUser + 10;

            $incomingSessionId = $validated['session_id'] ?? null;
            $incomingPublicId = $validated['public_id'] ?? null;
            $forceNewSession = $validated['new_session'] ?? false;
            $replyToData = $validated['replyTo'] ?? null; // Bisa ID string atau message object
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

            // Load existing messages to provide context to AI
            $existingMessages = [];
            if ($existingSession) {
                $sessionData = $existingSession->chat_activity_data;
                $existingMessages = $sessionData['messages'] ?? [];
                $existingMessagesCount = count($existingMessages);
                $messageCount = max($messageCount, $existingMessagesCount);
                
                // Build conversation history for AI context
                // Override the messages array if session exists
                if (!empty($existingMessages)) {
                    $validated['messages'] = $existingMessages;
                }
            }

            Log::debug('GeminiController session found', [
                'found' => $existingSession ? true : false,
                'existing_id' => $existingSession?->id,
                'existing_public_id' => $existingSession?->public_id,
                'message_count' => count($existingMessages),
            ]);

            // Build full prompt with conversation history BEFORE sending to AI
            $fullPromptParts = [$systemInstruction, ''];
            
            if (!empty($validated['messages']) && is_array($validated['messages'])) {
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

            // Add context about replied message if present
            $replyToData = $validated['replyTo'] ?? null;
            if ($replyToData) {
                // If replyTo is an object/array, use it directly
                if (is_array($replyToData)) {
                    $repliedMsg = $replyToData['message'] ?? '';
                    if (!empty($repliedMsg)) {
                        $repliedSender = strtolower($replyToData['sender'] ?? 'user');
                        $senderLabel = in_array($repliedSender, ['user', 'u', 'me', 'saya', 'client'], true) ? 'User' : 'Assistant';
                        $fullPromptParts[] = "[User is replying to {$senderLabel}'s message: \"{$repliedMsg}\"]";
                    }
                }
                // If it's just an ID string, we'll look it up from existingMessages later
            }

            $fullPromptParts[] = "User: " . $validated['prompt'];
            $fullPrompt = implode("\n", $fullPromptParts);

            Log::debug('Full prompt built', [
                'prompt_length' => strlen($fullPrompt),
                'message_count' => count($validated['messages'] ?? []),
            ]);

            // Send to Gemini with full context
            $response = $client->generateText(
                $fullPrompt,
                $validated['options'] ?? []
            );

            $replyText = $this->extractTextFromResponse($response);

            if (strlen($replyText) < 10 || preg_match('/^[A-Za-z0-9+\/=]{50,}$/', substr($replyText, 0, 100))) {
                Log::warning('Gemini extracted text looks suspicious', [
                    'text_length' => strlen($replyText),
                    'text_preview' => substr($replyText, 0, 200),
                    'raw_response' => $response,
                ]);
            }

            // Check if user explicitly requests consultation
            $explicitConsultationRequest = $this->detectExplicitConsultationRequest($validated['prompt']);
            
            // Check for immediate life-threatening emergency ONLY
            $isLifeThreatening = $this->detectLifeThreateningEmergency($validated['prompt']);
            
            // Initialize urgent flag - will be true for explicit request, life-threatening, OR after sufficient consultation need
            $urgent = false;
            
            if ($explicitConsultationRequest) {
                // User explicitly wants consultation - no need to gather info
                $urgent = true;
                if (stripos($replyText, 'consultation') === false) {
                    $replyText = trim($replyText) . "\n\nconsultation";
                }
            } elseif ($isLifeThreatening) {
                // CRITICAL EMERGENCY - immediate action required
                $urgent = true; // ALWAYS true for life-threatening
                
                // Add consultation keyword if not present
                if (stripos($replyText, 'consultation') === false) {
                    $replyText = trim($replyText) . "\n\nconsultation";
                }
                
                // Add emergency contact if AI didn't include it
                if (stripos($replyText, (string) $this->emergencyNumber) === false) {
                    $suffix = "\n\n⚠️ INI SITUASI DARURAT! Segera hubungi {$this->emergencyNumber} untuk bantuan medis segera.";
                    $replyText = trim($replyText).$suffix;
                }
            } else {
                // Check if AI response contains emergency number (means AI detected emergency)
                // This handles cases where emergency is detected after gathering info
                if (stripos($replyText, (string) $this->emergencyNumber) !== false) {
                    $urgent = true;
                    if (stripos($replyText, 'consultation') === false) {
                        $replyText = trim($replyText) . "\n\nconsultation";
                    }
                }
            }

            // Ensure public_id is always a valid UUID
            if (!empty($incomingPublicId) && \Ramsey\Uuid\Uuid::isValid($incomingPublicId)) {
                $publicId = $incomingPublicId;
            } else {
                $publicId = (string) Str::uuid();
            }
            
            $sessionId = null;

            if ($existingSession) {
                $sessionData = $existingSession->chat_activity_data;
                $existingMessages = $sessionData['messages'] ?? [];

                // Handle pesan yang di-reply (jika ada)
                $repliedMessage = null;
                if ($replyToData) {
                    if (is_array($replyToData)) {
                        // Jika sudah berbentuk object, langsung pakai
                        // ID optional, yang wajib hanya message
                        if (!empty($replyToData['message'])) {
                            $repliedMessage = [
                                'message' => $replyToData['message'],
                                'sender' => $replyToData['sender'] ?? 'user',
                            ];
                            // Include ID only if provided
                            if (isset($replyToData['id'])) {
                                $repliedMessage['id'] = $replyToData['id'];
                            }
                        }
                    } elseif (is_string($replyToData)) {
                        // Jika hanya ID, lookup dari existing messages
                        foreach ($existingMessages as $msg) {
                            if (isset($msg['id']) && $msg['id'] === $replyToData) {
                                $repliedMessage = [
                                    'id' => $msg['id'],
                                    'message' => $msg['message'],
                                    'sender' => $msg['sender'],
                                ];
                                break;
                            }
                        }
                    }
                }

                // Log replyTo untuk debugging
                Log::debug('Adding message with replyTo', [
                    'reply_to_raw' => $replyToData,
                    'replied_message' => $repliedMessage,
                    'has_reply' => !is_null($repliedMessage),
                ]);

                $existingMessages[] = [
                    'id' => (string) $tsUser,
                    'message' => $validated['prompt'],
                    'sender' => 'user',
                    'timestamp' => now()->toIso8601String(),
                    'replyTo' => $repliedMessage, // Null jika tidak reply
                ];

                // Bot reply doesn't have replyTo (bot never replies to specific messages)
                $existingMessages[] = [
                    'id' => (string) $tsBot,
                    'message' => $replyText,
                    'sender' => 'bot',
                    'timestamp' => now()->toIso8601String(),
                    'replyTo' => null, // Bot tidak pernah reply ke message tertentu
                    'urgent' => $urgent, // Track urgent status in bot response
                ];

                $publicId = $existingSession->public_id;
                $sessionId = $existingSession->id;

                $session = [
                    'id' => $sessionId,
                    'title' => $sessionData['title'] ?? substr($validated['prompt'], 0, 200),
                    'messages' => $existingMessages,
                    'urgent' => $urgent, // Track emergency status
                    'updatedAt' => now()->toIso8601String(),
                ];

                $userId = $existingSession->user_id ?? $userId;
            } else {
                $sessionId = (string) Str::uuid();

                // Generate AI-powered title for new sessions
                // This mimics how ChatGPT/Gemini generates conversation titles
                $generatedTitle = $this->generateChatTitle($client, $validated['prompt'], $replyText);
                
                // Final safety check: ensure title is never empty
                if (empty($generatedTitle) || trim($generatedTitle) === '') {
                    Log::error('Generated title is empty, using fallback');
                    $generatedTitle = $this->generateFallbackTitle($validated['prompt']);
                }

                $status = $validated['status'] ?? 'private';
                $shareSlug = null;
                if ($status === 'public') {
                    $shareSlug = (string) Str::random(16);
                }

                $session = [
                    'id' => $sessionId,
                    'title' => $generatedTitle,
                    'messages' => [
                        [
                            'id' => (string) $tsUser,
                            'message' => $validated['prompt'],
                            'sender' => 'user',
                            'timestamp' => now()->toIso8601String(),
                            'replyTo' => null, // First message tidak pernah reply
                        ],
                        [
                            'id' => (string) $tsBot,
                            'message' => $replyText,
                            'sender' => 'bot',
                            'timestamp' => now()->toIso8601String(),
                            'replyTo' => null, // Bot tidak pernah reply ke message tertentu
                            'urgent' => $urgent, // Track urgent status in bot response
                        ],
                    ],
                    'urgent' => $urgent, // Track emergency status
                    'updatedAt' => now()->toIso8601String(),
                    'status' => $status,
                    'share_slug' => $shareSlug,
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

        // After sufficient information gathered (4+ exchanges = 8+ messages), check if AI suggests consultation
        if (!$isLifeThreatening && $currentMessageCount >= 8) {
            $needsConsultation = $this->detectConsultationNeed($replyText);
            
            if ($needsConsultation) {
                $urgent = true; 
                
                if (stripos($replyText, 'consultation') === false) {
                    $replyText = trim($replyText) . "\n\nconsultation";
                }
            } elseif (stripos($replyText, 'konsultasi') === false && stripos($replyText, 'dokter') === false) {
                $doctorSuggestion = "\n\n💡 *Sudah beberapa kali kita berdiskusi tentang kesehatan Anda. Untuk penanganan yang lebih tepat dan menyeluruh, saya sarankan untuk berkonsultasi langsung dengan dokter kami ya!*\n\nconsultation";
                $replyText = trim($replyText) . $doctorSuggestion;
                $urgent = true;
            }
        }

        $packageSuggestions = $this->detectPackageRecommendations($validated['prompt'].' '.$replyText);

        $suggestConsultation = $currentMessageCount >= 6;

        $actions = [];
        if ($urgent) {
            $actions[] = [
                'type' => 'consultation',
                'label' => 'Konsultasi dengan Dokter',
                'url' => '/consultation',
                'reason' => 'urgent',
            ];
        } elseif ($suggestConsultation) {
            // Add consultation suggestion after 3-5 chat exchanges
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

        return new JsonResponse([
            'reply' => $replyText,
            'raw' => $response,
            'urgent' => $urgent,
            'actions' => $actions,
            'session_id' => $sessionId ?? null,
            'public_id' => $publicId ?? null,
            'title' => $session['title'] ?? null,
            'status' => $session['status'] ?? 'private',
            'share_slug' => $session['share_slug'] ?? null,
        ], 201);
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

    /**
     * Detect if user explicitly requests consultation with doctor
     */
    private function detectExplicitConsultationRequest(string $text): bool
    {
        $hay = strtolower(trim($text));
        
        // Keywords that indicate user wants to consult with doctor
        $consultationKeywords = [
            'ingin konsul',
            'mau konsul',
            'ingin konsultasi',
            'mau konsultasi',
            'konsul dengan dokter',
            'konsultasi dengan dokter',
            'konsul dokter',
            'konsultasi dokter',
            'bicara dengan dokter',
            'bertemu dokter',
            'jumpa dokter',
            'periksa ke dokter',
            'want to consult',
            'need to consult',
            'consult with doctor',
            'see a doctor',
            'talk to doctor',
            'speak with doctor',
        ];
        
        foreach ($consultationKeywords as $kw) {
            if (strpos($hay, $kw) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Detect ONLY life-threatening emergencies that need immediate action
     */
    private function detectLifeThreateningEmergency(string $text): bool
    {
        $hay = strtolower($text);
        
        // Only the most critical keywords
        $criticalKeywords = [
            // Medical emergencies
            'unconscious', 'pingsan', 'tidak sadarkan diri',
            'stopped breathing', 'not breathing', 'tidak bisa bernapas',
            'no pulse', 'cardiac arrest', 'serangan jantung',
            'severe bleeding', 'pendarahan hebat', 'bleeding heavily',
            'suffocating', 'tercekik',
            'overdose',
            'seizure', 'kejang',
            
            // Breathing emergencies
            'sesak nafas berat', 'sesak napas berat',
            'severe shortness of breath', 'can\'t breathe',
            'difficulty breathing', 'kesulitan bernapas',
            'mengi', 'wheezing', 'napas berbunyi',
            
            // Suicide/self-harm indicators
            'suicide', 'bunuh diri', 'ingin mati', 'mau mati',
            'ingin jatuh', 'mau jatuh', 'loncat', 'melompat',
            'mau bunuh diri', 'akan bunuh diri',
            'tidak ingin hidup', 'sudah tidak tahan',
            'want to die', 'want to jump', 'going to jump',
            'end my life', 'kill myself',
        ];
        
        foreach ($criticalKeywords as $kw) {
            if (strpos($hay, strtolower($kw)) !== false) {
                return true;
            }
        }
        
        // Context-based detection for severe breathing issues
        // "sesak" + "berat" / "parah" / "sangat"
        $hasSevereBreathing = (
            (strpos($hay, 'sesak') !== false || strpos($hay, 'sesak nafas') !== false || strpos($hay, 'sesak napas') !== false) &&
            (strpos($hay, 'berat') !== false || strpos($hay, 'parah') !== false || strpos($hay, 'sangat') !== false || strpos($hay, 'hebat') !== false)
        );
        
        if ($hasSevereBreathing) {
            return true;
        }
        
        // Context-based detection for suicide risk
        // If mentions "gedung"/"building" + "tinggi"/"lantai" + "jatuh"/"loncat"
        $hasBuildingContext = (
            (strpos($hay, 'gedung') !== false || strpos($hay, 'building') !== false || strpos($hay, 'rooftop') !== false) &&
            (strpos($hay, 'lantai') !== false || strpos($hay, 'tinggi') !== false || strpos($hay, 'atas') !== false) &&
            (strpos($hay, 'jatuh') !== false || strpos($hay, 'loncat') !== false || strpos($hay, 'melompat') !== false || strpos($hay, 'terjun') !== false)
        );
        
        if ($hasBuildingContext) {
            return true;
        }

        return false;
    }
    
    /**
     * Detect if AI response indicates that consultation is needed
     * This is called after sufficient information has been gathered
     */
    private function detectConsultationNeed(string $aiResponse): bool
    {
        $hay = strtolower($aiResponse);
        
        // Keywords that indicate AI is recommending medical consultation
        $consultationIndicators = [
            'sebaiknya konsultasi',
            'perlu konsultasi',
            'hubungi dokter',
            'periksa ke dokter',
            'temui dokter',
            'kunjungi dokter',
            'should consult',
            'need to see a doctor',
            'visit a doctor',
            'medical attention',
            'seek medical',
        ];
        
        foreach ($consultationIndicators as $indicator) {
            if (strpos($hay, $indicator) !== false) {
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
                
                if ($text !== null && is_string($text) && !empty(trim($text))) {
                    return trim($text);
                }
            }
        }

        Log::warning('Failed to extract text from Gemini response', [
            'response_keys' => array_keys($response),
            'has_candidates' => isset($response['candidates']),
        ]);
        
        return '';
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
