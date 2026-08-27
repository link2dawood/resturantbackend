<?php

namespace App\Services;

use App\Models\CategorizationDecision;
use App\Models\Store;
use App\Models\TransactionMappingRule;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Central categorization engine (Phase 4).
 *
 * - Suggests a Chart-of-Accounts code for a statement line by FUZZY matching the
 *   description against learned mapping rules (Levenshtein + similar_text +
 *   substring), blended with each rule's learned reliability.
 * - Scopes learning PER CLIENT (owner): a rule learned for one business is only
 *   suggested back to that business (global owner_id = NULL rules act as a
 *   backward-compatible fallback).
 * - Logs EVERY decision (suggestion, manual assignment, learned rule) to
 *   categorization_decisions for audit and improvement.
 *
 * Nothing here writes to expense/transaction tables — callers still require user
 * review before committing.
 */
class CategorizationEngine
{
    /** Minimum blended score (0..100) for a suggestion to be offered. */
    public const SUGGEST_THRESHOLD = 55.0;

    /** Confidence seeded for a freshly learned rule (0..1). */
    private const SEED_CONFIDENCE = 0.75;

    /**
     * Best COA suggestion for a description, or null. Logs the decision.
     *
     * @param  array  $context  extra columns for the decision log
     *                          (store_id, expense_transaction_id, bank_transaction_id, decided_by)
     * @return array{rule: TransactionMappingRule, coa_id: int, confidence: float, source: string}|null
     */
    public function suggest(string $description, ?int $ownerId = null, array $context = []): ?array
    {
        $best = $this->bestRule($description, $ownerId);

        $this->logDecision(array_merge([
            'owner_id' => $ownerId,
            'description' => Str::limit($description, 250, ''),
            'source' => $best['source'] ?? 'none',
            'decision' => 'suggested',
            'transaction_mapping_rule_id' => $best['rule']->id ?? null,
            'suggested_coa_id' => $best['coa_id'] ?? null,
            'confidence' => $best['confidence'] ?? null,
        ], $context));

        return $best;
    }

    /**
     * Learn (or reinforce) an owner-scoped rule from a reviewer's assignment,
     * and log a "learned" decision.
     */
    public function learn(string $description, int $coaId, ?int $ownerId, ?int $vendorId = null, array $context = []): TransactionMappingRule
    {
        $pattern = trim($description) !== '' ? trim($description) : 'Unknown';

        $rule = TransactionMappingRule::firstOrNew([
            'owner_id' => $ownerId,
            'description_pattern' => $pattern,
            'coa_id' => $coaId,
        ]);

        if (! $rule->exists) {
            $rule->fill([
                'vendor_id' => $vendorId,
                'confidence_score' => self::SEED_CONFIDENCE,
                'is_active' => true,
                'times_used' => 0,
                'times_correct' => 0,
                'times_incorrect' => 0,
            ]);
        } else {
            // The reviewer re-confirmed an existing rule: reinforce it.
            $rule->times_correct++;
            $total = $rule->times_correct + $rule->times_incorrect;
            $rule->confidence_score = round($rule->times_correct / max(1, $total), 2);
            if ($vendorId && ! $rule->vendor_id) {
                $rule->vendor_id = $vendorId;
            }
        }

        $rule->last_used = now();
        $rule->save();

        $this->logDecision(array_merge([
            'owner_id' => $ownerId,
            'transaction_mapping_rule_id' => $rule->id,
            'description' => Str::limit($description, 250, ''),
            'source' => 'learned',
            'decision' => 'learned',
            'chosen_coa_id' => $coaId,
            'confidence' => round((float) $rule->confidence_score * 100, 2),
        ], $context));

        return $rule;
    }

    /** Record a reviewer's manual assignment (accepted / overridden / manual). */
    public function logAssignment(string $description, ?int $coaId, ?int $ownerId, string $decision = 'manual', array $context = []): CategorizationDecision
    {
        return $this->logDecision(array_merge([
            'owner_id' => $ownerId,
            'description' => Str::limit($description, 250, ''),
            'source' => 'manual',
            'decision' => $decision,
            'chosen_coa_id' => $coaId,
        ], $context));
    }

    public function logDecision(array $data): CategorizationDecision
    {
        return CategorizationDecision::create($data + ['decided_by' => auth()->id()]);
    }

    /** Resolve the owning business (client) for a store. */
    public function ownerIdForStore(?Store $store): ?int
    {
        return $store?->controllingOwner()?->id;
    }

    /** Resolve the owning business (client) for the acting user. */
    public function ownerIdForUser(?User $user): ?int
    {
        if (! $user) {
            return null;
        }
        if ($user->isOwner()) {
            return $user->id;
        }
        if ($user->isManager()) {
            return $user->store?->controllingOwner()?->id;
        }

        return null; // admin / franchisor operate across all clients
    }

    /**
     * Highest-scoring active rule for this owner, or null below threshold.
     *
     * @return array{rule: TransactionMappingRule, coa_id: int, confidence: float, source: string}|null
     */
    private function bestRule(string $description, ?int $ownerId): ?array
    {
        $needle = $this->normalize($description);
        if ($needle === '') {
            return null;
        }

        $rules = TransactionMappingRule::query()->active()->forOwner($ownerId)->get();

        $best = null;
        $bestScore = 0.0;
        $bestSimilarity = 0.0;

        foreach ($rules as $rule) {
            $similarity = $this->similarity($needle, $this->normalize((string) $rule->description_pattern));
            // Blend text similarity with the rule's learned reliability.
            $blended = $similarity * (0.6 + 0.4 * (float) $rule->confidence_score);
            // Prefer a client's own rule over a global fallback on close calls.
            if ($ownerId !== null && (int) $rule->owner_id === $ownerId) {
                $blended += 2.0;
            }
            if ($blended > $bestScore) {
                $bestScore = $blended;
                $bestSimilarity = $similarity;
                $best = $rule;
            }
        }

        if (! $best || $bestScore < self::SUGGEST_THRESHOLD) {
            return null;
        }

        return [
            'rule' => $best,
            'coa_id' => (int) $best->coa_id,
            'confidence' => round(min(100.0, $bestScore), 2),
            'source' => $bestSimilarity >= 99.5 ? 'rule' : 'fuzzy',
        ];
    }

    /** Similarity of two normalized strings, 0..100. */
    public function similarity(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0.0;
        }
        if ($a === $b) {
            return 100.0;
        }

        // Substring containment is a strong signal for merchant lines
        // ("SAMS CLUB #123" vs "sams club").
        if (str_contains($a, $b) || str_contains($b, $a)) {
            $ratio = min(strlen($a), strlen($b)) / max(strlen($a), strlen($b));

            return round(80 + 20 * $ratio, 2); // 80..100
        }

        similar_text($a, $b, $percent);                    // 0..100

        // levenshtein() only accepts strings up to 255 chars.
        $x = substr($a, 0, 255);
        $y = substr($b, 0, 255);
        $maxLen = max(strlen($x), strlen($y));
        $lev = $maxLen > 0 ? (1 - levenshtein($x, $y) / $maxLen) * 100 : 0.0;

        return round(($percent + max(0.0, $lev)) / 2, 2);
    }

    /** Lowercase, strip punctuation, collapse whitespace. */
    public function normalize(string $s): string
    {
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9 ]+/', ' ', $s);
        $s = preg_replace('/\s+/', ' ', (string) $s);

        return trim((string) $s);
    }
}
