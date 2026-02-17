<?php

namespace App\Services\Ai;

class DeterministicSimulationSummary
{
    public function generate(string $status, int $value, array $result): string
    {
        return $this->generateCombined([
            ['status' => $status, 'value' => $value],
        ], $result);
    }

    public function generateCombined(array $items, array $result): string
    {
        $baselineSla = $this->num($result['baseline_sla'] ?? 0);
        $projectedSla = $this->num($result['projected_sla'] ?? 0);
        $tatDelta = $this->num($result['tat_delta'] ?? 0);
        $totalSavingDays = $this->num($result['total_saving_days'] ?? 0);
        $tatAbs = abs($tatDelta);
        $label = $this->buildTargetLabel($items);

        return implode("\n", [
            "Penurunan efisiensi {$label}:",
            'SLA breach: '.number_format($baselineSla, 0, '.', '').' -> '.number_format($projectedSla, 0, '.', ''),
            'Avg TAT turun '.number_format($tatAbs, 1, '.', '').' hari/aplikasi',
            'Total penghematan '.number_format($totalSavingDays, 0, ',', '.').' hari proses',
        ]);
    }

    private function buildTargetLabel(array $items): string
    {
        $parts = [];
        foreach ($items as $item) {
            $status = trim((string) ($item['status'] ?? ''));
            $value = (int) round((float) ($item['value'] ?? 0));
            if ($status === '') {
                continue;
            }
            $parts[] = "{$status} {$value}%";
        }

        return !empty($parts) ? implode(', ', $parts) : '-';
    }

    private function num(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }
}
