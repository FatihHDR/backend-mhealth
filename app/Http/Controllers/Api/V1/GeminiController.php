<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Package as PackageModel;
use App\Services\GeminiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        ]);

        // Persona/system instruction for Mei
        $systemInstruction = 'You are Mei, a gentle, empathetic, and informative virtual health assistant. '.
            'Speak naturally, politely, and with a warm feminine tone as a caring female health assistant. '.
            "When the user's message suggests an emergency, immediately advise them to call {$this->emergencyNumber} ".
            "and include the word 'consultation' at the end of your message to prompt for a professional follow-up.";

        $fullPrompt = $systemInstruction."\n\nUser: ".$validated['prompt'];

        $response = $client->generateText(
            $fullPrompt,
            $validated['options'] ?? []
        );

        $replyText = $this->extractTextFromResponse($response);

        $urgent = $this->detectEmergency($validated['prompt'].' '.$replyText);

        if ($urgent) {
            // If urgent, ensure advice to call emergency number and include consultation keyword
            $suffix = "\n\nJika ini darurat, segera hubungi {$this->emergencyNumber}.\n\nconsultation";
            if (stripos($replyText, (string) $this->emergencyNumber) === false) {
                $replyText = trim($replyText).$suffix;
            } elseif (stripos($replyText, 'consultation') === false) {
                $replyText = trim($replyText)."\n\nconsultation";
            }
        }

        // Detect package recommendations based on the prompt and reply text
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
            $allPackages = PackageModel::select('id', 'name', 'description', 'price', 'image')->get();
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
