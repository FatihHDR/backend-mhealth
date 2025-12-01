<?php

namespace App\Services;

class AiAgent
{
    private GeminiClient $client;

    private string $emergencyNumber = '08159880048';

    /** @var string[] */
    private array $emergencyKeywords = [
        'emergency', 'darurat', 'chest pain', 'severe bleeding', 'bleeding', 'unconscious', 'pingsan',
        'kejang', 'shortness of breath', 'suffocat', 'suffocating', 'suicide', 'bunuh diri', 'mati',
        'stopped breathing', 'not breathing', 'no pulse', 'cardiac arrest',
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
            "When the user's message suggests an emergency, immediately advise them to call {$this->emergencyNumber} ".
            "and include the word 'consultation' at the end of your message to prompt for a professional follow-up.";

        $fullPrompt = $systemInstruction."\n\nUser: ".$prompt;

        $response = $this->client->generateText($fullPrompt, $options);

        $replyText = $this->extractTextFromResponse($response);

        $urgent = $this->detectEmergency($prompt.' '.$replyText);

        if ($urgent && stripos($replyText, 'consultation') === false) {
            $replyText = trim($replyText)."\n\nconsultation";
        }

        return [
            'reply' => $replyText,
            'raw' => $response,
            'urgent' => $urgent,
        ];
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
