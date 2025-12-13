<?php

namespace App\Services;

class AiAgent
{
    private GeminiClient $client;

    private string $emergencyNumber = '08159880048';

    /** @var string[] */
    private array $emergencyKeywords = [
        'emergency', 'darurat', 'chest pain', 'nyeri dada', 'severe bleeding', 'pendarahan hebat',
        'bleeding heavily', 'unconscious', 'pingsan', 'tidak sadarkan diri',
        'kejang', 'seizure', 'shortness of breath', 'sesak napas', 'suffocat', 'suffocating',
        'suicide', 'bunuh diri', 'ingin mati', 'stopped breathing', 'not breathing',
        'no pulse', 'cardiac arrest', 'serangan jantung', 'heart attack',
        'stroke', 'paralyzed', 'lumpuh', 'severe pain', 'sakit parah',
        'can\'t breathe', 'tidak bisa bernapas', 'overdose',
    ];

    /** @var string[] */
    private array $greetingKeywords = [
        'hello', 'hi', 'hey', 'halo', 'hai', 'ola', 'good morning', 'good afternoon',
        'good evening', 'selamat pagi', 'selamat siang', 'selamat sore', 'selamat malam',
        'apa kabar', 'how are you', 'test', 'testing',
    ];

    public function __construct(GeminiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Respond to a user prompt using the Gemini client while enforcing the "Mei" persona
     * and detecting urgent/emergency content. Returns an array with reply, raw response
     * and an urgent flag.
     */
    public function respondTo(string $prompt, array $options = []): array
    {
        $systemInstruction = 'You are Mei, a gentle, empathetic, and informative virtual health assistant. '.
            'Speak naturally, politely, and with a warm feminine tone as a caring female health assistant. '.
            "When the user's message suggests a REAL medical emergency (severe symptoms like chest pain, difficulty breathing, severe bleeding, unconsciousness), ".
            "immediately advise them to call {$this->emergencyNumber} and include the word 'consultation' at the end. ".
            "For simple greetings or non-urgent health questions, respond warmly without marking as urgent.";

        $fullPrompt = $systemInstruction."\n\nUser: ".$prompt;

        $response = $this->client->generateText($fullPrompt, $options);

        $replyText = $this->extractTextFromResponse($response);

        // Only detect emergency from USER prompt, not from AI reply
        $urgent = $this->detectEmergency($prompt);

        if ($urgent && stripos($replyText, 'consultation') === false) {
            $language = $this->detectLanguage($prompt);
            $emergencyMessage = $language === 'id' 
                ? "Jika ini darurat, segera hubungi {$this->emergencyNumber}."
                : "If this is an emergency, please call {$this->emergencyNumber} immediately.";
            
            $replyText = trim($replyText)."\n\n{$emergencyMessage}\n\nconsultation";
        }

        return [
            'reply' => $replyText,
            'raw' => $response,
            'urgent' => $urgent,
        ];
    }

    private function detectEmergency(string $text): bool
    {
        $hay = strtolower(trim($text));
        
        // Skip if it's just a greeting
        foreach ($this->greetingKeywords as $greeting) {
            if ($hay === strtolower($greeting) || 
                preg_match('/^' . preg_quote(strtolower($greeting), '/') . '[!?\s]*$/i', $hay)) {
                return false;
            }
        }
        
        // Skip if message is too short (likely not an emergency)
        if (strlen($hay) < 10) {
            return false;
        }
        
        // Check for emergency keywords
        foreach ($this->emergencyKeywords as $kw) {
            if (strpos($hay, strtolower($kw)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Simple language detection based on common Indonesian words.
     * Returns 'id' for Indonesian, 'en' for English.
     */
    private function detectLanguage(string $text): string
    {
        $hay = strtolower($text);
        
        $indonesianIndicators = [
            'saya', 'aku', 'kamu', 'anda', 'yang', 'dan', 'dengan', 'untuk', 
            'dari', 'ini', 'itu', 'ada', 'tidak', 'ya', 'sudah', 'akan',
            'bagaimana', 'apa', 'kenapa', 'dimana', 'kapan', 'siapa',
        ];
        
        $indonesianCount = 0;
        foreach ($indonesianIndicators as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $hay)) {
                $indonesianCount++;
            }
        }
        
        // If found 2 or more Indonesian indicators, it's likely Indonesian
        return $indonesianCount >= 2 ? 'id' : 'en';
    }

    /**
     * Try to extract the most relevant textual reply from the Gemini response array.
     * Properly parses the Gemini API response structure.
     */
    private function extractTextFromResponse(array $response): string
    {
        // Gemini API response structure:
        // { "candidates": [{ "content": { "parts": [{ "text": "..." }] } }] }
        $candidates = $response['candidates'] ?? [];
        if (!empty($candidates)) {
            $firstCandidate = $candidates[0] ?? [];
            $content = $firstCandidate['content'] ?? [];
            $parts = $content['parts'] ?? [];
            
            if (!empty($parts)) {
                $text = $parts[0]['text'] ?? null;
                if ($text !== null && is_string($text)) {
                    return trim($text);
                }
            }
        }

        // Fallback: collect strings but filter out base64/encoded data
        $strings = [];
        $this->collectStringsRecursive($response, $strings);

        if (empty($strings)) {
            return '';
        }

        // Filter out base64-like strings
        $readableStrings = array_filter($strings, function($s) {
            if (strlen($s) > 100 && preg_match('/^[A-Za-z0-9+\/=]+$/', $s)) {
                return false;
            }
            $spaceRatio = substr_count($s, ' ') / max(strlen($s), 1);
            if (strlen($s) > 50 && $spaceRatio < 0.05) {
                return false;
            }
            return true;
        });

        if (empty($readableStrings)) {
            return 'Maaf, terjadi kesalahan dalam memproses respons. Silakan coba lagi.';
        }

        usort($readableStrings, function ($a, $b) {
            return strlen($b) <=> strlen($a);
        });

        return trim($readableStrings[0]);
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
