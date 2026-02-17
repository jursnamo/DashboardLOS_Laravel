<?php

namespace App\Services\Ai;

use App\Services\Dashboard\StatusCatalog;

class SimulationIntentDetector
{
    public function __construct(
        private readonly StatusCatalog $statusCatalog
    ) {
    }

    public function detect(string $question): array
    {
        $raw = trim($question);
        if ($raw === '') {
            return ['intent' => 'normal_chat'];
        }

        $statuses = $this->statusCatalog->getAvailableStatuses();
        if (empty($statuses)) {
            return ['intent' => 'normal_chat'];
        }

        $segments = $this->splitSegments($raw);
        $pairs = [];
        $statusesMentioned = [];

        foreach ($segments as $segment) {
            $status = $this->detectStatus($segment, $statuses);
            if (!$status) {
                continue;
            }
            $statusesMentioned[$status] = true;
            $value = $this->detectPercentage($segment);
            if ($value === null) {
                continue;
            }
            [$applied, $wasClamped] = $this->applyClamp($value);
            $pairs[$status] = [
                'status' => $status,
                'value' => $applied,
                'original_value' => $value,
                'was_clamped' => $wasClamped,
            ];
        }

        if (!empty($pairs)) {
            $items = array_values($pairs);
            if (count($items) === 1) {
                $single = $items[0];
                return [
                    'intent' => 'simulate_efficiency',
                    'status' => $single['status'],
                    'value' => $single['value'],
                    'meta' => [
                        'original_value' => $single['original_value'],
                        'was_clamped' => $single['was_clamped'],
                    ],
                ];
            }

            return [
                'intent' => 'simulate_efficiency_multi',
                'items' => array_map(fn ($x) => [
                    'status' => $x['status'],
                    'value' => $x['value'],
                    'original_value' => $x['original_value'],
                    'was_clamped' => $x['was_clamped'],
                ], $items),
                'meta' => [
                    'target_count' => count($items),
                    'has_clamped' => count(array_filter($items, fn ($x) => (bool) ($x['was_clamped'] ?? false))) > 0,
                ],
            ];
        }

        if (!empty($statusesMentioned)) {
            if (count($statusesMentioned) === 1) {
                return [
                    'intent' => 'simulate_missing_percentage',
                    'status' => array_key_first($statusesMentioned),
                ];
            }

            return [
                'intent' => 'simulate_missing_percentage_multi',
                'statuses' => array_values(array_keys($statusesMentioned)),
            ];
        }

        return ['intent' => 'normal_chat'];
    }

    private function splitSegments(string $question): array
    {
        $parts = preg_split('/\b(?:dan|and)\b|[,;+&]/iu', $question) ?: [$question];
        return array_values(array_filter(array_map('trim', $parts), fn ($x) => $x !== ''));
    }

    private function detectStatus(string $segment, array $availableStatuses): ?string
    {
        $cleanSegment = preg_replace('/\d+(?:[\.,]\d+)?\s*%/u', ' ', $segment) ?? $segment;
        $segmentNorm = $this->normalize($cleanSegment);
        if ($segmentNorm === '') {
            return null;
        }

        $segmentTokens = $this->tokens($cleanSegment);
        $bestStatus = null;
        $bestScore = 0.0;

        foreach ($availableStatuses as $status) {
            $statusNorm = $this->normalize($status);
            if ($statusNorm === '') {
                continue;
            }

            if (str_contains($segmentNorm, $statusNorm)) {
                return $status;
            }

            $statusTokens = $this->tokens($status);
            if (empty($statusTokens)) {
                continue;
            }

            $matched = 0;
            foreach ($statusTokens as $token) {
                if ($this->hasApproxTokenMatch($token, $segmentTokens)) {
                    $matched++;
                }
            }

            $score = $matched / max(1, count($statusTokens));
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestStatus = $status;
            }
        }

        return $bestScore >= 0.6 ? $bestStatus : null;
    }

    private function detectPercentage(string $question): ?int
    {
        if (!preg_match('/(\d+(?:[\.,]\d+)?)\s*%/u', $question, $m)) {
            return null;
        }

        $value = str_replace(',', '.', (string) ($m[1] ?? ''));
        $n = (float) $value;
        if (!is_finite($n)) {
            return null;
        }

        $n = max(0, $n);

        return (int) round($n);
    }

    private function applyClamp(int $value): array
    {
        if ($value > 100) {
            return [50, true];
        }

        return [$value, false];
    }

    private function normalize(string $text): string
    {
        $upper = strtoupper($text);
        $alnum = preg_replace('/[^A-Z0-9]+/u', '', $upper);
        return (string) $alnum;
    }

    private function tokens(string $text): array
    {
        $upper = strtoupper($text);
        $raw = preg_split('/[^A-Z0-9]+/u', $upper) ?: [];
        $tokens = array_values(array_filter(array_map('trim', $raw), fn ($x) => strlen($x) >= 3));
        return array_values(array_unique($tokens));
    }

    private function hasApproxTokenMatch(string $needle, array $haystackTokens): bool
    {
        foreach ($haystackTokens as $token) {
            if ($token === $needle) {
                return true;
            }
            if (abs(strlen($token) - strlen($needle)) > 2) {
                continue;
            }
            if (levenshtein($token, $needle) <= 1) {
                return true;
            }
        }

        return false;
    }
}
