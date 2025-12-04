<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class SlugHelper
{
    /**
     * Generate a slug with a random 12-character suffix.
     * Example: "hotel-name-abc123xyz789"
     *
     * @param string $text The text to slugify
     * @return string
     */
    public static function generate(string $text): string
    {
        $baseSlug = Str::slug($text);
        $randomSuffix = Str::lower(Str::random(12));
        
        return $baseSlug . '-' . $randomSuffix;
    }

    /**
     * Regenerate slug only if name/title changed (for update operations).
     * Compares the new value with current model value.
     *
     * @param string $newText The new name/title
     * @param string|null $currentSlug The current slug from model
     * @param string|null $currentText The current name/title from model
     * @return string|null Returns new slug if text changed, null otherwise
     */
    public static function regenerateIfChanged(string $newText, ?string $currentSlug, ?string $currentText): ?string
    {
        // If the name/title hasn't changed, keep the existing slug
        if ($currentText === $newText) {
            return null; // Don't update slug
        }

        // Name/title changed, generate new slug with random suffix
        return self::generate($newText);
    }
}
