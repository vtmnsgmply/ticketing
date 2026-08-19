<?php

namespace App\Services;

use App\Data\NormalizedText;
use Normalizer;

class TextNormalizationService
{
    private const LEET_MAP = [
        '0' => 'o',
        '1' => 'i',
        '!' => 'i',
        '3' => 'e',
        '4' => 'a',
        '@' => 'a',
        '5' => 's',
        '$' => 's',
        '7' => 't',
        '+' => 't',
    ];

    private const CONFUSABLE_MAP = [
        'Α' => 'a', 'А' => 'a', 'а' => 'a', 'ɑ' => 'a',
        'Β' => 'b', 'В' => 'b', 'Ь' => 'b',
        'Ϲ' => 'c', 'С' => 'c', 'с' => 'c',
        'Ε' => 'e', 'Е' => 'e', 'е' => 'e',
        'Η' => 'h', 'Н' => 'h', 'н' => 'h',
        'Ι' => 'i', 'І' => 'i', 'і' => 'i', 'ı' => 'i',
        'Κ' => 'k', 'К' => 'k', 'κ' => 'k',
        'Μ' => 'm', 'М' => 'm', 'м' => 'm',
        'Ν' => 'n', 'Ν' => 'n', 'п' => 'n',
        'Ο' => 'o', 'О' => 'o', 'о' => 'o',
        'Ρ' => 'p', 'Р' => 'p', 'р' => 'p',
        'Τ' => 't', 'Т' => 't', 'т' => 't',
        'Χ' => 'x', 'Х' => 'x', 'х' => 'x',
        'Υ' => 'y', 'У' => 'y', 'у' => 'y',
    ];

    public function normalize(string $text): NormalizedText
    {
        $unicode = $this->unicodeNormalize($text);
        $withoutInvisible = $this->removeInvisibleCharacters($unicode);
        $lower = mb_strtolower($withoutInvisible);
        $asciiFolded = $this->stripDiacritics($lower);
        $skeleton = $this->buildConfusableSkeleton($asciiFolded);
        $leet = $this->normalizeLeetspeak($skeleton);
        $alternateLeet = strtr($skeleton, array_merge(self::LEET_MAP, ['@' => 'u']));
        $compact = $this->compactInternalSeparators($leet);
        $alternateCompact = $this->compactInternalSeparators($alternateLeet);
        $collapsed = $this->collapseRepeatedCharacters($compact);
        $tokens = $this->tokenize($leet);
        $collapsedTokens = array_values(array_unique(array_map(fn ($token) => $this->collapseRepeatedCharacters($token), $tokens)));
        $comparisonForms = array_values(array_unique(array_filter([
            $compact,
            $alternateCompact,
            $collapsed,
            $this->collapseRepeatedCharacters($alternateCompact),
            preg_replace('/[^\pL\pN]+/u', '', $skeleton) ?? '',
        ])));

        return new NormalizedText(
            original: $text,
            unicodeNormalized: $unicode,
            withoutInvisible: $withoutInvisible,
            asciiFolded: $asciiFolded,
            confusableSkeleton: $skeleton,
            leetNormalized: $leet,
            compact: $compact,
            collapsed: $collapsed,
            lettersOnly: preg_replace('/[^\pL\pN]+/u', '', $leet) ?? '',
            tokens: $tokens,
            collapsedTokens: $collapsedTokens,
            comparisonForms: $comparisonForms,
        );
    }

    public function normalizeTerm(string $term): string
    {
        return $this->normalize($term)->compact;
    }

    public function unicodeNormalize(string $text): string
    {
        return class_exists(Normalizer::class)
            ? (Normalizer::normalize($text, Normalizer::FORM_KC) ?: $text)
            : $text;
    }

    public function stripDiacritics(string $text): string
    {
        $decomposed = class_exists(Normalizer::class)
            ? (Normalizer::normalize($text, Normalizer::FORM_D) ?: $text)
            : $text;

        return preg_replace('/\pM+/u', '', $decomposed) ?? $text;
    }

    public function removeInvisibleCharacters(string $text): string
    {
        return preg_replace('/[\x{00AD}\x{034F}\x{061C}\x{180E}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{206F}\x{FEFF}]/u', '', $text) ?? $text;
    }

    public function normalizeLeetspeak(string $text): string
    {
        return strtr($text, self::LEET_MAP);
    }

    public function buildConfusableSkeleton(string $text): string
    {
        return strtr($text, self::CONFUSABLE_MAP);
    }

    public function compactInternalSeparators(string $text): string
    {
        return preg_replace('/[^\pL\pN]+/u', '', $text) ?? '';
    }

    public function collapseRepeatedCharacters(string $text): string
    {
        return preg_replace('/(.)\1{2,}/u', '$1', $text) ?? $text;
    }

    public function tokenize(string $text): array
    {
        preg_match_all('/[\pL\pN]+/u', $text, $matches);

        return array_values(array_unique(array_filter($matches[0] ?? [], fn ($token) => $token !== '')));
    }
}
