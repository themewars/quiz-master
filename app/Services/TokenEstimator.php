<?php

namespace App\Services;

class TokenEstimator
{
    /**
     * Rough token estimator (4 chars ≈ 1 token). Replace with real tokenizer if needed.
     */
    public static function estimateTokens(string $text): int
    {
        $length = mb_strlen($text ?? '', 'UTF-8');
        if ($length === 0) {
            return 0;
        }
        return (int) ceil($length / 4);
    }
}


