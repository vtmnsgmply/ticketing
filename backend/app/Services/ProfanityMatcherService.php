<?php

namespace App\Services;

use App\Data\NormalizedText;
use App\Models\BlockedWord;
use App\Repository\BlockedWordRepository;

class ProfanityMatcherService
{
    public function __construct(
        private readonly BlockedWordRepository $blockedWords,
        private readonly TextNormalizationService $normalizer,
    ) {
    }

    public function match(NormalizedText $text): array
    {
        $matches = [];
        $tokens = array_values(array_unique(array_merge($text->tokens, $text->collapsedTokens)));

        foreach ($this->blockedWords->getActiveWords() as $word) {
            foreach ($this->entriesForWord($word) as $entry) {
                $match = $this->matchEntry($entry, $text, $tokens);
                if ($match === null) {
                    continue;
                }

                $matches[] = [
                    'word_id' => $word->id,
                    'variant_id' => $entry['variant_id'],
                    'variant_type' => $entry['variant_type'],
                    'severity' => $word->severity,
                    'action' => $word->action,
                    'detection_layer' => $match,
                    'count' => 1,
                ];
                break;
            }
        }

        return $matches;
    }

    private function matchEntry(array $entry, NormalizedText $text, array $tokens): ?string
    {
        $term = $entry['normalized'];
        if ($term === '') {
            return null;
        }

        if (in_array($term, $tokens, true)) {
            return $entry['variant_type'] === 'canonical' ? 'exact' : 'variant_exact';
        }

        $hasSeparatorObfuscation = $this->hasSeparatorObfuscation($text->asciiFolded);

        foreach ($text->comparisonForms as $form) {
            if ($form === $term) {
                return 'normalized_exact';
            }

            if ($hasSeparatorObfuscation && mb_strlen($term) >= 4 && str_contains($form, $term)) {
                return in_array($entry['variant_type'], ['compressed', 'common_misspelling', 'morphological'], true)
                    ? 'variant_exact'
                    : 'obfuscation_high_confidence';
            }
        }

        $collapsedTerm = $this->normalizer->collapseRepeatedCharacters($term);
        foreach ($text->comparisonForms as $form) {
            if ($collapsedTerm !== '' && mb_strlen($collapsedTerm) >= 4 && str_contains($this->normalizer->collapseRepeatedCharacters($form), $collapsedTerm)) {
                if (! $hasSeparatorObfuscation && $this->normalizer->collapseRepeatedCharacters($form) !== $collapsedTerm) {
                    continue;
                }

                return 'obfuscation_high_confidence';
            }
        }

        foreach ($tokens as $token) {
            if ($this->fuzzyMatches($token, $term)) {
                return 'fuzzy';
            }
        }

        return null;
    }

    private function entriesForWord(BlockedWord $word): array
    {
        $entries = [[
            'variant_id' => null,
            'variant_type' => 'canonical',
            'normalized' => $word->normalized_word ?: $this->normalizer->normalizeTerm($word->word),
        ]];

        foreach ($word->variants ?? [] as $variant) {
            if (! $variant->is_active) {
                continue;
            }

            $entries[] = [
                'variant_id' => $variant->id,
                'variant_type' => $variant->variant_type,
                'normalized' => $variant->normalized_variant ?: $this->normalizer->normalizeTerm($variant->variant),
            ];
        }

        return $entries;
    }

    private function fuzzyMatches(string $token, string $term): bool
    {
        $tokenLength = mb_strlen($token);
        $termLength = mb_strlen($term);
        if ($termLength < 4 || $tokenLength < 4 || abs($tokenLength - $termLength) > 2) {
            return false;
        }

        if (mb_substr($token, 0, 1) !== mb_substr($term, 0, 1) || mb_substr($token, -1) !== mb_substr($term, -1)) {
            return false;
        }

        $maxDistance = $termLength >= 10 ? 2 : 1;

        return levenshtein($token, $term) <= $maxDistance;
    }

    private function hasSeparatorObfuscation(string $text): bool
    {
        return (bool) preg_match('/\pL(?:[\s._\-\/\\\\|]+)\pL(?:[\s._\-\/\\\\|]+\pL)+/u', $text);
    }
}
