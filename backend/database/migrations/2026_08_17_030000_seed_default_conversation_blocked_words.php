<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $terms = [
            ['word' => 'fuck', 'severity' => 'critical'],
            ['word' => 'fucking', 'severity' => 'critical'],
            ['word' => 'motherfucker', 'severity' => 'critical'],
            ['word' => 'shit', 'severity' => 'high'],
            ['word' => 'bullshit', 'severity' => 'high'],
            ['word' => 'asshole', 'severity' => 'high'],
            ['word' => 'bitch', 'severity' => 'high'],
            ['word' => 'bastard', 'severity' => 'high'],
            ['word' => 'cunt', 'severity' => 'critical'],
            ['word' => 'dick', 'severity' => 'high'],
            ['word' => 'pussy', 'severity' => 'high'],
            ['word' => 'whore', 'severity' => 'high'],
            ['word' => 'slut', 'severity' => 'high'],
            ['word' => 'putang ina', 'severity' => 'critical'],
            ['word' => 'putangina', 'severity' => 'critical'],
            ['word' => 'puta', 'severity' => 'high'],
            ['word' => 'gago', 'severity' => 'high'],
            ['word' => 'tanga', 'severity' => 'high'],
            ['word' => 'bobo', 'severity' => 'high'],
            ['word' => 'ulol', 'severity' => 'high'],
            ['word' => 'tarantado', 'severity' => 'high'],
            ['word' => 'leche', 'severity' => 'medium'],
            ['word' => 'pakshet', 'severity' => 'high'],
        ];

        foreach ($terms as $term) {
            $normalized = $this->normalize($term['word']);

            DB::table('blocked_words')->updateOrInsert(
                ['normalized_word' => $normalized],
                [
                    'word' => $term['word'],
                    'severity' => $term['severity'],
                    'action' => 'block',
                    'is_active' => true,
                    'notes' => 'Default English/Tagalog profanity blocklist.',
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }

        Cache::forget('conversation_moderation.active_blocked_words');
    }

    public function down(): void
    {
        DB::table('blocked_words')
            ->where('notes', 'Default English/Tagalog profanity blocklist.')
            ->delete();

        Cache::forget('conversation_moderation.active_blocked_words');
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return strtr($value, ['@' => 'a', '4' => 'a', '3' => 'e', '1' => 'i', '!' => 'i', '0' => 'o', '5' => 's', '$' => 's']);
    }
};
