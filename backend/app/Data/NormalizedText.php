<?php

namespace App\Data;

class NormalizedText
{
    public function __construct(
        public readonly string $original,
        public readonly string $unicodeNormalized,
        public readonly string $withoutInvisible,
        public readonly string $asciiFolded,
        public readonly string $confusableSkeleton,
        public readonly string $leetNormalized,
        public readonly string $compact,
        public readonly string $collapsed,
        public readonly string $lettersOnly,
        public readonly array $tokens,
        public readonly array $collapsedTokens,
        public readonly array $comparisonForms,
    ) {
    }
}
