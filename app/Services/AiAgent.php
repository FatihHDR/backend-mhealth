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
     * Uses a heuristic: collect all string values and return the longest one.
     */
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
