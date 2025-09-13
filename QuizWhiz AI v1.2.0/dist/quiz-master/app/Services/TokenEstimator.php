<?php

namespace App\Services;

class TokenEstimator
{
    /**
     * Estimate token count for text content
     * Rough estimation: 1 token ≈ 4 characters for English text
     */
    public static function estimateTokens(string $text): int
    {
        if (empty($text)) {
            return 0;
        }

        // Remove extra whitespace and normalize
        $text = preg_replace('/\s+/', ' ', trim($text));
        
        // Basic estimation: 4 characters per token for English
        // This is a rough approximation - actual tokenization varies
        $estimatedTokens = ceil(strlen($text) / 4);
        
        // Add some buffer for punctuation and special characters
        $estimatedTokens = (int) ($estimatedTokens * 1.1);
        
        return max(1, $estimatedTokens);
    }

    /**
     * Estimate tokens for different languages
     */
    public static function estimateTokensForLanguage(string $text, string $language = 'en'): int
    {
        $baseTokens = self::estimateTokens($text);
        
        // Adjust for different languages
        $multipliers = [
            'en' => 1.0,    // English
            'hi' => 1.2,    // Hindi (Devanagari script)
            'zh' => 1.5,    // Chinese (more characters per token)
            'ja' => 1.4,    // Japanese
            'ko' => 1.3,    // Korean
            'ar' => 1.1,    // Arabic
            'ru' => 1.1,    // Russian (Cyrillic)
            'es' => 1.0,    // Spanish
            'fr' => 1.0,    // French
            'de' => 1.0,    // German
            'it' => 1.0,    // Italian
            'pt' => 1.0,    // Portuguese
            'tr' => 1.0,    // Turkish
            'vi' => 1.1,    // Vietnamese
        ];
        
        $multiplier = $multipliers[$language] ?? 1.0;
        
        return (int) ($baseTokens * $multiplier);
    }

    /**
     * Check if text exceeds token limit
     */
    public static function exceedsLimit(string $text, int $limit, string $language = 'en'): bool
    {
        return self::estimateTokensForLanguage($text, $language) > $limit;
    }

    /**
     * Truncate text to fit within token limit
     */
    public static function truncateToLimit(string $text, int $limit, string $language = 'en'): string
    {
        if (!self::exceedsLimit($text, $limit, $language)) {
            return $text;
        }

        // Estimate characters per token for the language
        $charsPerToken = 4;
        $multipliers = [
            'hi' => 0.8,    // Hindi characters are more dense
            'zh' => 0.7,    // Chinese characters are very dense
            'ja' => 0.7,    // Japanese characters are dense
            'ko' => 0.8,    // Korean characters are dense
        ];
        
        $multiplier = $multipliers[$language] ?? 1.0;
        $charsPerToken = $charsPerToken * $multiplier;
        
        $maxChars = (int) ($limit * $charsPerToken);
        
        if (strlen($text) <= $maxChars) {
            return $text;
        }
        
        // Truncate and add ellipsis
        return substr($text, 0, $maxChars - 3) . '...';
    }
}