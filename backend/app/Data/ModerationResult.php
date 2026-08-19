<?php

namespace App\Data;

class ModerationResult
{
    public function __construct(
        public readonly string $originalText,
        public readonly string $filteredText,
        public readonly bool $containsViolation,
        public readonly array $matchedTerms,
        public readonly string $highestSeverity,
        public readonly string $action,
        public readonly bool $shouldBlock,
        public readonly bool $shouldFlag,
    ) {
    }
}
