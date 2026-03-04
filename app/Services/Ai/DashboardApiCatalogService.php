<?php

namespace App\Services\Ai;

use App\Models\DashboardDatamart;

class DashboardApiCatalogService
{
    public function buildContextForQuestion(string $question, int $maxItems = 6): string
    {
        $catalog = $this->getCatalog();
        if (empty($catalog)) {
            return 'Data API catalog belum tersedia.';
        }

        $selected = $this->selectRelevantCatalog($catalog, $question, $maxItems);

        $lines = [];
        $lines[] = 'API Catalog relevan untuk pertanyaan ini:';
        foreach ($selected as $item) {
            $lines[] = "- {$item['id']} | {$item['name']}";
            $lines[] = "  desc: {$item['description']}";
            $lines[] = '  data: '.json_encode($item['data'], JSON_UNESCAPED_UNICODE);
        }

        return implode("\n", $lines);
    }

    public function getCatalog(): array
    {
        $payload = $this->latestDatamartPayload();
        if ($payload === null) {
            return [];
        }

        $apps = array_values(array_filter((array) ($payload['e2e_data'] ?? []), fn ($x) => is_array($x)));
        $statusAgg = (array) ($payload['status_agg'] ?? []);
        $flows = (array) ($payload['app_flow_events'] ?? []);
        $batch = (array) ($payload['batch'] ?? []);

        if (empty($apps)) {
            return [];
        }

        $tatList = array_map(fn ($x) => (float) ($x['tat'] ?? 0), $apps);
        sort($tatList);
        $count = count($tatList);
        $avgTat = $count > 0 ? array_sum($tatList) / $count : 0.0;
        $q1 = $this->quantile($tatList, 0.25);
        $med = $this->quantile($tatList, 0.5);
        $q3 = $this->quantile($tatList, 0.75);
        $iqr = $q3 - $q1;
        $boundary = $q3 + (1.5 * $iqr);
        $outliers = array_values(array_filter($apps, fn ($x) => (float) ($x['tat'] ?? 0) > $boundary));

        $statusTotals = [];
        foreach ($statusAgg as $key => $duration) {
            $parts = explode('##', (string) $key, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $status = trim((string) $parts[1]);
            if ($status === '') {
                continue;
            }
            $statusTotals[$status] = ($statusTotals[$status] ?? 0) + (float) $duration;
        }
        arsort($statusTotals);

        $branchStats = [];
        $purposeStats = [];
        $purposeTatStats = [];
        $segmentStats = [];
        $segmentTatStats = [];
        $monthStats = [];
        $limitPos = [];
        $loopByStatus = [];
        $appTatRows = [];
        $branchOutlier = [];
        $purposeOutlier = [];
        $segmentOutlier = [];

        foreach ($apps as $app) {
            $tat = (float) ($app['tat'] ?? 0);
            $branch = (string) ($app['branch'] ?? 'Unknown');
            $purpose = (string) ($app['purp'] ?? 'General');
            $segment = (string) ($app['seg'] ?? 'Unknown');
            $month = (string) ($app['mon'] ?? 'Unknown');
            $limit = (float) ($app['limit'] ?? 0);
            $appId = (string) ($app['id'] ?? '');

            $branchStats[$branch]['count'] = ($branchStats[$branch]['count'] ?? 0) + 1;
            $branchStats[$branch]['sum_tat'] = ($branchStats[$branch]['sum_tat'] ?? 0) + $tat;
            $branchStats[$branch]['sum_limit'] = ($branchStats[$branch]['sum_limit'] ?? 0) + max(0, $limit);

            $purposeStats[$purpose] = ($purposeStats[$purpose] ?? 0) + 1;
            $purposeTatStats[$purpose]['count'] = ($purposeTatStats[$purpose]['count'] ?? 0) + 1;
            $purposeTatStats[$purpose]['sum_tat'] = ($purposeTatStats[$purpose]['sum_tat'] ?? 0) + $tat;
            $segmentStats[$segment] = ($segmentStats[$segment] ?? 0) + 1;
            $segmentTatStats[$segment]['count'] = ($segmentTatStats[$segment]['count'] ?? 0) + 1;
            $segmentTatStats[$segment]['sum_tat'] = ($segmentTatStats[$segment]['sum_tat'] ?? 0) + $tat;
            $monthStats[$month]['count'] = ($monthStats[$month]['count'] ?? 0) + 1;
            $monthStats[$month]['sum_tat'] = ($monthStats[$month]['sum_tat'] ?? 0) + $tat;

            if ($limit > 0) {
                $limitPos[] = $limit;
            }
            if ($appId !== '') {
                $appTatRows[] = ['id' => $appId, 'tat' => round($tat, 1), 'branch' => $branch, 'purpose' => $purpose];
            }
            if ($tat > $boundary) {
                $branchOutlier[$branch] = ($branchOutlier[$branch] ?? 0) + 1;
                $purposeOutlier[$purpose] = ($purposeOutlier[$purpose] ?? 0) + 1;
                $segmentOutlier[$segment] = ($segmentOutlier[$segment] ?? 0) + 1;
            }
        }

        foreach ($flows as $flow) {
            $events = is_array($flow) && isset($flow['events']) && is_array($flow['events']) ? $flow['events'] : [];
            $seen = [];
            foreach ($events as $ev) {
                $s = trim((string) ($ev['status'] ?? ''));
                if ($s === '') {
                    continue;
                }
                $seen[$s] = ($seen[$s] ?? 0) + 1;
            }
            foreach ($seen as $status => $cnt) {
                if ($cnt > 1) {
                    $loopByStatus[$status] = ($loopByStatus[$status] ?? 0) + ($cnt - 1);
                }
            }
        }
        arsort($loopByStatus);
        arsort($branchOutlier);
        arsort($purposeOutlier);
        arsort($segmentOutlier);

        uasort($branchStats, fn ($a, $b) => ($b['count'] <=> $a['count']));
        arsort($purposeStats);
        arsort($segmentStats);
        ksort($monthStats);
        usort($appTatRows, fn ($a, $b) => $b['tat'] <=> $a['tat']);
        uasort($purposeTatStats, fn ($a, $b) => (($b['sum_tat'] / max(1, $b['count'])) <=> ($a['sum_tat'] / max(1, $a['count']))) );
        uasort($segmentTatStats, fn ($a, $b) => (($b['sum_tat'] / max(1, $b['count'])) <=> ($a['sum_tat'] / max(1, $a['count']))) );

        $topStatusImpact = $this->takeAssoc($statusTotals, 7, fn ($k, $v) => ['status' => $k, 'total_days' => round((float) $v, 1)]);
        $fastStatus = array_reverse($this->takeAssoc(array_reverse($statusTotals, true), 5, fn ($k, $v) => ['status' => $k, 'total_days' => round((float) $v, 1)]));
        $slowStatus = $this->takeAssoc($statusTotals, 5, fn ($k, $v) => ['status' => $k, 'total_days' => round((float) $v, 1)]);
        $topBranchVolume = $this->takeAssoc($branchStats, 7, fn ($k, $v) => ['branch' => $k, 'count' => (int) ($v['count'] ?? 0)]);
        $topBranchTat = $this->takeAssoc($branchStats, 7, function ($k, $v) {
            $c = max(1, (int) ($v['count'] ?? 0));
            return ['branch' => $k, 'avg_tat' => round(((float) ($v['sum_tat'] ?? 0)) / $c, 1)];
        });
        usort($topBranchTat, fn ($a, $b) => $b['avg_tat'] <=> $a['avg_tat']);

        $monthlyVolume = [];
        $monthlyTat = [];
        $monthlyOutlier = [];
        foreach ($monthStats as $month => $d) {
            $c = (int) ($d['count'] ?? 0);
            $s = (float) ($d['sum_tat'] ?? 0);
            $monthlyVolume[] = ['month' => $month, 'count' => $c];
            $monthlyTat[] = ['month' => $month, 'avg_tat' => round($c > 0 ? ($s / $c) : 0, 1)];
            $monthlyOutlier[] = [
                'month' => $month,
                'outlier_count' => count(array_filter($apps, fn ($x) => (string) ($x['mon'] ?? '') === (string) $month && (float) ($x['tat'] ?? 0) > $boundary)),
            ];
        }

        $limitTotal = array_sum($limitPos);
        $limitAvg = count($limitPos) > 0 ? ($limitTotal / count($limitPos)) : 0.0;
        sort($limitPos);
        $limitMedian = $this->quantile($limitPos, 0.5);
        $limitQ1 = $this->quantile($limitPos, 0.25);
        $limitQ3 = $this->quantile($limitPos, 0.75);

        $tatStd = $this->stddev($tatList, $avgTat);
        $tatCv = $avgTat > 0 ? ($tatStd / $avgTat) * 100 : 0;

        $tatBuckets = [
            'lt_7' => 0,
            '7_14' => 0,
            '14_30' => 0,
            '30_60' => 0,
            '60_90' => 0,
            'gte_90' => 0,
        ];
        foreach ($tatList as $t) {
            if ($t < 7) {
                $tatBuckets['lt_7']++;
            } elseif ($t < 14) {
                $tatBuckets['7_14']++;
            } elseif ($t < 30) {
                $tatBuckets['14_30']++;
            } elseif ($t < 60) {
                $tatBuckets['30_60']++;
            } elseif ($t < 90) {
                $tatBuckets['60_90']++;
            } else {
                $tatBuckets['gte_90']++;
            }
        }

        $limitBuckets = [
            '0_1b' => 0,
            '1_5b' => 0,
            '5_10b' => 0,
            '10_20b' => 0,
            'gte_20b' => 0,
        ];
        foreach ($apps as $a) {
            $l = max(0, (float) ($a['limit'] ?? 0));
            if ($l < 1_000_000_000) {
                $limitBuckets['0_1b']++;
            } elseif ($l < 5_000_000_000) {
                $limitBuckets['1_5b']++;
            } elseif ($l < 10_000_000_000) {
                $limitBuckets['5_10b']++;
            } elseif ($l < 20_000_000_000) {
                $limitBuckets['10_20b']++;
            } else {
                $limitBuckets['gte_20b']++;
            }
        }

        $statusAppCount = [];
        foreach ($statusAgg as $k => $v) {
            $parts = explode('##', (string) $k, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $status = trim((string) $parts[1]);
            if ($status === '') {
                continue;
            }
            $statusAppCount[$status] = ($statusAppCount[$status] ?? 0) + 1;
        }
        arsort($statusAppCount);

        $statusAvgDuration = [];
        foreach ($statusTotals as $s => $total) {
            $c = max(1, (int) ($statusAppCount[$s] ?? 1));
            $statusAvgDuration[$s] = $total / $c;
        }
        arsort($statusAvgDuration);

        $appByLimit = array_values(array_map(function ($a) {
            return [
                'id' => (string) ($a['id'] ?? ''),
                'limit' => round((float) ($a['limit'] ?? 0), 2),
                'tat' => round((float) ($a['tat'] ?? 0), 1),
                'branch' => (string) ($a['branch'] ?? 'Unknown'),
            ];
        }, $apps));
        usort($appByLimit, fn ($a, $b) => $b['limit'] <=> $a['limit']);

        $appByTatFast = array_values($appTatRows);
        usort($appByTatFast, fn ($a, $b) => $a['tat'] <=> $b['tat']);

        $monthKeys = array_map(fn ($x) => (string) ($x['month'] ?? ''), $monthlyVolume);
        $monthLast = end($monthlyVolume);
        $monthPrev = count($monthlyVolume) > 1 ? $monthlyVolume[count($monthlyVolume) - 2] : null;
        $momDeltaCount = ($monthPrev && $monthLast) ? ((int) ($monthLast['count'] ?? 0) - (int) ($monthPrev['count'] ?? 0)) : 0;
        $monthLastTat = end($monthlyTat);
        $monthPrevTat = count($monthlyTat) > 1 ? $monthlyTat[count($monthlyTat) - 2] : null;
        $momDeltaTat = ($monthPrevTat && $monthLastTat) ? ((float) ($monthLastTat['avg_tat'] ?? 0) - (float) ($monthPrevTat['avg_tat'] ?? 0)) : 0.0;

        $topBranchConcentration = $this->takeAssoc($branchStats, 10, function ($k, $v) use ($count) {
            $c = (int) ($v['count'] ?? 0);
            return [
                'branch' => $k,
                'count' => $c,
                'share_pct' => round($count > 0 ? ($c / $count) * 100 : 0, 1),
            ];
        });
        $topPurposeConcentration = $this->takeAssoc($purposeStats, 10, function ($k, $v) use ($count) {
            return [
                'purpose' => $k,
                'count' => (int) $v,
                'share_pct' => round($count > 0 ? (((int) $v / $count) * 100) : 0, 1),
            ];
        });
        $topSegmentConcentration = $this->takeAssoc($segmentStats, 10, function ($k, $v) use ($count) {
            return [
                'segment' => $k,
                'count' => (int) $v,
                'share_pct' => round($count > 0 ? (((int) $v / $count) * 100) : 0, 1),
            ];
        });

        $purposeAvgTat = [];
        foreach ($purposeTatStats as $p => $v) {
            $purposeAvgTat[] = [
                'purpose' => $p,
                'avg_tat' => round(((float) ($v['sum_tat'] ?? 0)) / max(1, (int) ($v['count'] ?? 1)), 1),
                'count' => (int) ($v['count'] ?? 0),
            ];
        }
        usort($purposeAvgTat, fn ($a, $b) => $b['avg_tat'] <=> $a['avg_tat']);

        $segmentAvgTat = [];
        foreach ($segmentTatStats as $p => $v) {
            $segmentAvgTat[] = [
                'segment' => $p,
                'avg_tat' => round(((float) ($v['sum_tat'] ?? 0)) / max(1, (int) ($v['count'] ?? 1)), 1),
                'count' => (int) ($v['count'] ?? 0),
            ];
        }
        usort($segmentAvgTat, fn ($a, $b) => $b['avg_tat'] <=> $a['avg_tat']);

        return [
            $this->item('api_01_overview', 'Overview KPI', 'Ringkasan total aplikasi, outlier, dan average TAT.', [
                'applications' => count($apps),
                'avg_tat' => round($avgTat, 1),
                'outliers' => count($outliers),
            ], ['ringkasan', 'overview', 'kpi', 'total', 'aplikasi']),
            $this->item('api_02_tat_stats', 'TAT Statistics', 'Statistik distribusi TAT global.', [
                'avg' => round($avgTat, 1),
                'q1' => round($q1, 1),
                'median' => round($med, 1),
                'q3' => round($q3, 1),
                'iqr' => round($iqr, 1),
                'outlier_boundary' => round($boundary, 1),
            ], ['tat', 'q1', 'q3', 'median', 'outlier']),
            $this->item('api_03_outlier_summary', 'Outlier Summary', 'Daftar aplikasi outlier teratas.', array_slice(array_map(
                fn ($x) => ['id' => (string) ($x['id'] ?? ''), 'tat' => round((float) ($x['tat'] ?? 0), 1), 'branch' => (string) ($x['branch'] ?? 'Unknown')],
                $outliers
            ), 0, 10), ['outlier', 'anomali', 'exception']),
            $this->item('api_04_status_impact', 'Status Impact', 'Status dengan dampak total hari terbesar.', $topStatusImpact, ['status', 'bottleneck', 'impact', 'lambat']),
            $this->item('api_05_slowest_status', 'Slowest Status', 'Top status paling lama berdasarkan total days.', $slowStatus, ['status', 'slowest', 'bottleneck', 'delay']),
            $this->item('api_06_fastest_status', 'Fastest Status', 'Status paling cepat berdasarkan total days.', $fastStatus, ['status', 'fastest', 'cepat']),
            $this->item('api_07_top_branch_volume', 'Top Branch by Volume', 'Cabang dengan volume aplikasi terbesar.', $topBranchVolume, ['branch', 'cabang', 'volume']),
            $this->item('api_08_top_branch_tat', 'Top Branch by Avg TAT', 'Cabang dengan average TAT tertinggi.', $topBranchTat, ['branch', 'cabang', 'tat', 'delay']),
            $this->item('api_09_monthly_volume', 'Monthly Volume Trend', 'Tren jumlah aplikasi per bulan.', array_slice($monthlyVolume, -12), ['trend', 'monthly', 'month', 'volume']),
            $this->item('api_10_monthly_tat', 'Monthly Avg TAT Trend', 'Tren average TAT per bulan.', array_slice($monthlyTat, -12), ['trend', 'monthly', 'tat']),
            $this->item('api_11_purpose_distribution', 'Purpose Distribution', 'Distribusi jumlah aplikasi berdasarkan purpose.', $this->takeAssoc($purposeStats, 10, fn ($k, $v) => ['purpose' => $k, 'count' => (int) $v]), ['purpose', 'distribusi', 'jenis']),
            $this->item('api_12_segment_distribution', 'Segment Distribution', 'Distribusi aplikasi per segment.', $this->takeAssoc($segmentStats, 10, fn ($k, $v) => ['segment' => $k, 'count' => (int) $v]), ['segment', 'corporate', 'commercial']),
            $this->item('api_13_limit_summary', 'Approved Limit Summary', 'Ringkasan total dan average approved limit.', [
                'total_limit' => round($limitTotal, 2),
                'avg_limit' => round($limitAvg, 2),
                'apps_with_positive_limit' => count($limitPos),
            ], ['limit', 'plafond', 'approved']),
            $this->item('api_14_top_apps_tat', 'Top Applications by TAT', 'Aplikasi dengan TAT tertinggi.', array_slice($appTatRows, 0, 12), ['application', 'app', 'tat', 'tertinggi']),
            $this->item('api_15_status_loop', 'Status Loop Frequency', 'Frekuensi loop/rework per status.', $this->takeAssoc($loopByStatus, 10, fn ($k, $v) => ['status' => $k, 'loop_count' => (int) $v]), ['loop', 'rework', 'repeat', 'status']),
            $this->item('api_16_sla_proxy', 'SLA Proxy', 'Estimasi breach berbasis batas outlier.', [
                'breach_apps_estimate' => count($outliers),
                'breach_pct_estimate' => round(($count > 0 ? (count($outliers) / $count) * 100 : 0), 1),
            ], ['sla', 'breach', 'risk']),
            $this->item('api_17_data_freshness', 'Data Freshness', 'Informasi batch sumber dan waktu import.', [
                'batch_id' => $batch['id'] ?? null,
                'filename' => $batch['filename'] ?? null,
                'imported_at' => $batch['imported_at'] ?? null,
                'calculation_mode' => $batch['calculation_mode'] ?? null,
            ], ['freshness', 'batch', 'latest', 'update']),
            $this->item('api_18_status_pair_count', 'Status Pair Count', 'Jumlah pasangan app-status (status_agg).', [
                'status_pairs' => count($statusAgg),
                'apps' => count($apps),
            ], ['status', 'pair', 'count']),
            $this->item('api_19_flow_event_summary', 'Flow Event Summary', 'Ringkasan data flow event aplikasi.', [
                'apps_with_flow' => count($flows),
                'total_flow_events' => array_sum(array_map(function ($x) {
                    return is_array($x) && isset($x['events']) && is_array($x['events']) ? count($x['events']) : 0;
                }, $flows)),
            ], ['flow', 'event', 'timeline']),
            $this->item('api_20_recommendation_basis', 'Recommendation Basis', 'Basis rekomendasi prioritas dari status impact dan loop.', [
                'top_impact_status' => array_slice($topStatusImpact, 0, 3),
                'top_loop_status' => array_slice($this->takeAssoc($loopByStatus, 3, fn ($k, $v) => ['status' => $k, 'loop_count' => (int) $v]), 0, 3),
            ], ['rekomendasi', 'prioritas', 'aksi']),
            $this->item('api_21_branch_outlier', 'Branch Outlier Count', 'Jumlah outlier per cabang.', $this->takeAssoc($branchOutlier, 10, fn ($k, $v) => ['branch' => $k, 'outlier_count' => (int) $v]), ['branch', 'outlier', 'risk']),
            $this->item('api_22_purpose_outlier', 'Purpose Outlier Count', 'Jumlah outlier per purpose.', $this->takeAssoc($purposeOutlier, 10, fn ($k, $v) => ['purpose' => $k, 'outlier_count' => (int) $v]), ['purpose', 'outlier', 'risk']),
            $this->item('api_23_segment_outlier', 'Segment Outlier Count', 'Jumlah outlier per segment.', $this->takeAssoc($segmentOutlier, 10, fn ($k, $v) => ['segment' => $k, 'outlier_count' => (int) $v]), ['segment', 'outlier', 'risk']),
            $this->item('api_24_status_app_count', 'Status App Count', 'Jumlah aplikasi yang melewati tiap status.', $this->takeAssoc($statusAppCount, 15, fn ($k, $v) => ['status' => $k, 'app_count' => (int) $v]), ['status', 'count', 'coverage']),
            $this->item('api_25_status_avg_duration', 'Status Avg Duration', 'Rata-rata durasi per status.', $this->takeAssoc($statusAvgDuration, 15, fn ($k, $v) => ['status' => $k, 'avg_days' => round((float) $v, 2)]), ['status', 'avg', 'duration']),
            $this->item('api_26_monthly_outlier', 'Monthly Outlier Trend', 'Tren jumlah outlier per bulan.', array_slice($monthlyOutlier, -12), ['trend', 'monthly', 'outlier']),
            $this->item('api_27_limit_stats', 'Limit Distribution Stats', 'Q1, median, Q3 approved limit.', [
                'q1' => round($limitQ1, 2),
                'median' => round($limitMedian, 2),
                'q3' => round($limitQ3, 2),
            ], ['limit', 'distribution', 'median']),
            $this->item('api_28_limit_buckets', 'Limit Buckets', 'Distribusi aplikasi berdasarkan rentang limit.', $limitBuckets, ['limit', 'bucket', 'distribution']),
            $this->item('api_29_tat_buckets', 'TAT Buckets', 'Distribusi aplikasi berdasarkan rentang TAT.', $tatBuckets, ['tat', 'bucket', 'distribution']),
            $this->item('api_30_tat_dispersion', 'TAT Dispersion', 'Stddev dan coefficient variation TAT.', [
                'stddev' => round($tatStd, 2),
                'cv_pct' => round($tatCv, 2),
            ], ['tat', 'volatility', 'dispersion']),
            $this->item('api_31_top_limit_apps', 'Top Limit Applications', 'Aplikasi dengan limit tertinggi.', array_slice($appByLimit, 0, 15), ['application', 'limit', 'top']),
            $this->item('api_32_low_limit_apps', 'Lowest Limit Applications', 'Aplikasi dengan limit terendah.', array_slice(array_reverse($appByLimit), 0, 15), ['application', 'limit', 'lowest']),
            $this->item('api_33_fastest_apps', 'Fastest Applications', 'Aplikasi dengan TAT terendah.', array_slice($appByTatFast, 0, 15), ['application', 'tat', 'fastest']),
            $this->item('api_34_slowest_apps', 'Slowest Applications', 'Aplikasi dengan TAT tertinggi.', array_slice($appTatRows, 0, 15), ['application', 'tat', 'slowest']),
            $this->item('api_35_branch_concentration', 'Branch Concentration', 'Konsentrasi volume aplikasi per cabang.', $topBranchConcentration, ['branch', 'share', 'concentration']),
            $this->item('api_36_purpose_concentration', 'Purpose Concentration', 'Konsentrasi aplikasi per purpose.', $topPurposeConcentration, ['purpose', 'share', 'concentration']),
            $this->item('api_37_segment_concentration', 'Segment Concentration', 'Konsentrasi aplikasi per segment.', $topSegmentConcentration, ['segment', 'share', 'concentration']),
            $this->item('api_38_purpose_avg_tat', 'Purpose Avg TAT', 'Average TAT per purpose.', array_slice($purposeAvgTat, 0, 12), ['purpose', 'avg', 'tat']),
            $this->item('api_39_segment_avg_tat', 'Segment Avg TAT', 'Average TAT per segment.', array_slice($segmentAvgTat, 0, 12), ['segment', 'avg', 'tat']),
            $this->item('api_40_monthly_share', 'Monthly Volume Share', 'Persentase kontribusi volume per bulan.', array_map(function ($x) use ($count) {
                return ['month' => $x['month'], 'share_pct' => round($count > 0 ? ((int) $x['count'] / $count) * 100 : 0, 2)];
            }, array_slice($monthlyVolume, -12)), ['monthly', 'share', 'volume']),
            $this->item('api_41_mom_volume_delta', 'MoM Volume Delta', 'Perubahan volume bulan terakhir vs bulan sebelumnya.', [
                'last_month' => $monthLast['month'] ?? null,
                'previous_month' => $monthPrev['month'] ?? null,
                'delta_count' => $momDeltaCount,
            ], ['mom', 'volume', 'delta']),
            $this->item('api_42_mom_tat_delta', 'MoM TAT Delta', 'Perubahan average TAT bulan terakhir vs bulan sebelumnya.', [
                'last_month' => $monthLastTat['month'] ?? null,
                'previous_month' => $monthPrevTat['month'] ?? null,
                'delta_avg_tat' => round($momDeltaTat, 2),
            ], ['mom', 'tat', 'delta']),
            $this->item('api_43_month_key_list', 'Month Key List', 'Daftar bulan yang tersedia di data.', $monthKeys, ['month', 'calendar', 'period']),
            $this->item('api_44_status_coverage_ratio', 'Status Coverage Ratio', 'Rasio cakupan status dibanding total aplikasi.', $this->takeAssoc($statusAppCount, 15, function ($k, $v) use ($count) {
                return ['status' => $k, 'coverage_pct' => round($count > 0 ? ((int) $v / $count) * 100 : 0, 2)];
            }), ['status', 'coverage', 'ratio']),
            $this->item('api_45_outlier_rate_by_branch', 'Outlier Rate by Branch', 'Estimasi outlier rate per cabang.', $this->takeAssoc($branchStats, 10, function ($k, $v) use ($branchOutlier) {
                $c = max(1, (int) ($v['count'] ?? 1));
                $o = (int) ($branchOutlier[$k] ?? 0);
                return ['branch' => $k, 'outlier_rate_pct' => round(($o / $c) * 100, 2)];
            }), ['outlier', 'branch', 'rate']),
            $this->item('api_46_outlier_rate_by_purpose', 'Outlier Rate by Purpose', 'Estimasi outlier rate per purpose.', $this->takeAssoc($purposeStats, 10, function ($k, $v) use ($purposeOutlier) {
                $c = max(1, (int) $v);
                $o = (int) ($purposeOutlier[$k] ?? 0);
                return ['purpose' => $k, 'outlier_rate_pct' => round(($o / $c) * 100, 2)];
            }), ['outlier', 'purpose', 'rate']),
            $this->item('api_47_outlier_rate_by_segment', 'Outlier Rate by Segment', 'Estimasi outlier rate per segment.', $this->takeAssoc($segmentStats, 10, function ($k, $v) use ($segmentOutlier) {
                $c = max(1, (int) $v);
                $o = (int) ($segmentOutlier[$k] ?? 0);
                return ['segment' => $k, 'outlier_rate_pct' => round(($o / $c) * 100, 2)];
            }), ['outlier', 'segment', 'rate']),
            $this->item('api_48_branch_limit_avg', 'Branch Avg Limit', 'Average approved limit per cabang.', $this->takeAssoc($branchStats, 12, function ($k, $v) {
                $c = max(1, (int) ($v['count'] ?? 1));
                return ['branch' => $k, 'avg_limit' => round(((float) ($v['sum_limit'] ?? 0)) / $c, 2)];
            }), ['branch', 'avg', 'limit']),
            $this->item('api_49_branch_tat_and_limit', 'Branch TAT & Limit', 'Average TAT dan average limit per cabang.', $this->takeAssoc($branchStats, 12, function ($k, $v) {
                $c = max(1, (int) ($v['count'] ?? 1));
                return [
                    'branch' => $k,
                    'avg_tat' => round(((float) ($v['sum_tat'] ?? 0)) / $c, 2),
                    'avg_limit' => round(((float) ($v['sum_limit'] ?? 0)) / $c, 2),
                ];
            }), ['branch', 'tat', 'limit']),
            $this->item('api_50_status_vs_loop', 'Status vs Loop', 'Perbandingan durasi status dan loop frequency.', array_map(function ($x) use ($loopByStatus) {
                $s = (string) ($x['status'] ?? '');
                return [
                    'status' => $s,
                    'total_days' => (float) ($x['total_days'] ?? 0),
                    'loop_count' => (int) ($loopByStatus[$s] ?? 0),
                ];
            }, array_slice($topStatusImpact, 0, 10)), ['status', 'loop', 'compare']),
            $this->item('api_51_top_risk_combo', 'Top Risk Combination', 'Kombinasi status impact tinggi + loop tinggi.', $this->topRiskCombination($topStatusImpact, $loopByStatus), ['risk', 'status', 'loop']),
            $this->item('api_52_bottom_risk_status', 'Lowest Risk Status', 'Status dengan durasi total paling rendah.', array_slice($fastStatus, 0, 5), ['risk', 'status', 'fast']),
            $this->item('api_53_mid_tat_band', 'Middle TAT Band', 'Aplikasi dalam rentang Q1-Q3.', [
                'count' => count(array_filter($apps, fn ($x) => (float) ($x['tat'] ?? 0) >= $q1 && (float) ($x['tat'] ?? 0) <= $q3)),
                'q1' => round($q1, 1),
                'q3' => round($q3, 1),
            ], ['tat', 'band', 'middle']),
            $this->item('api_54_high_tat_band', 'High TAT Band', 'Aplikasi di atas Q3.', [
                'count' => count(array_filter($apps, fn ($x) => (float) ($x['tat'] ?? 0) > $q3)),
                'q3' => round($q3, 1),
            ], ['tat', 'high', 'q3']),
            $this->item('api_55_low_tat_band', 'Low TAT Band', 'Aplikasi di bawah Q1.', [
                'count' => count(array_filter($apps, fn ($x) => (float) ($x['tat'] ?? 0) < $q1)),
                'q1' => round($q1, 1),
            ], ['tat', 'low', 'q1']),
            $this->item('api_56_near_outlier_band', 'Near Outlier Band', 'Aplikasi mendekati outlier boundary (>=90% boundary).', [
                'count' => count(array_filter($apps, fn ($x) => (float) ($x['tat'] ?? 0) >= (0.9 * $boundary) && (float) ($x['tat'] ?? 0) <= $boundary)),
                'boundary' => round($boundary, 1),
            ], ['outlier', 'warning', 'boundary']),
            $this->item('api_57_data_shape', 'Data Shape', 'Ukuran dataset untuk AI reasoning.', [
                'apps' => count($apps),
                'status_agg_pairs' => count($statusAgg),
                'flow_apps' => count($flows),
                'months' => count($monthStats),
                'branches' => count($branchStats),
                'purposes' => count($purposeStats),
                'segments' => count($segmentStats),
            ], ['dataset', 'shape', 'meta']),
            $this->item('api_58_freshness_extended', 'Freshness Extended', 'Informasi batch + versi datamart terakhir.', [
                'batch_id' => $batch['id'] ?? null,
                'filename' => $batch['filename'] ?? null,
                'imported_at' => $batch['imported_at'] ?? null,
                'calculation_mode' => $batch['calculation_mode'] ?? null,
                'status' => $batch['status'] ?? null,
            ], ['freshness', 'batch', 'metadata']),
            $this->item('api_59_exec_summary', 'Execution Summary', 'Ringkasan eksekusi untuk jawaban manajerial cepat.', [
                'top_slowest_status' => $slowStatus[0]['status'] ?? null,
                'top_branch_by_tat' => $topBranchTat[0]['branch'] ?? null,
                'top_outlier_branch' => array_key_first($branchOutlier),
                'avg_tat' => round($avgTat, 1),
                'outlier_count' => count($outliers),
            ], ['summary', 'executive', 'management']),
            $this->item('api_60_ai_default_pack', 'AI Default Pack', 'Paket default data untuk jawaban umum AI.', [
                'overview' => ['applications' => count($apps), 'avg_tat' => round($avgTat, 1), 'outliers' => count($outliers)],
                'top_impact_status' => array_slice($topStatusImpact, 0, 3),
                'top_branch_tat' => array_slice($topBranchTat, 0, 3),
                'monthly_tat_last_6' => array_slice($monthlyTat, -6),
            ], ['default', 'ai', 'general']),
        ];
    }

    private function latestDatamartPayload(): ?array
    {
        $row = DashboardDatamart::query()
            ->where('status', 'completed')
            ->latest('id')
            ->first();

        if (! $row) {
            return null;
        }

        $decoded = json_decode((string) $row->payload_json, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function selectRelevantCatalog(array $catalog, string $question, int $maxItems): array
    {
        $tokens = $this->tokenize($question);

        foreach ($catalog as $idx => $item) {
            $score = 0;
            $haystack = implode(' ', array_merge(
                [$item['id'], $item['name'], $item['description']],
                (array) ($item['tags'] ?? [])
            ));
            $itemTokens = $this->tokenize($haystack);

            foreach ($tokens as $t) {
                if (in_array($t, $itemTokens, true)) {
                    $score += 3;
                }
            }

            if ($score === 0) {
                $score = max(1, 10 - $idx);
            }

            $catalog[$idx]['_score'] = $score;
        }

        usort($catalog, fn ($a, $b) => ($b['_score'] <=> $a['_score']));
        $picked = array_slice($catalog, 0, max(1, $maxItems));

        return array_map(function ($x) {
            unset($x['_score']);
            return $x;
        }, $picked);
    }

    private function tokenize(string $text): array
    {
        $t = strtolower(trim($text));
        if ($t === '') {
            return [];
        }

        $parts = preg_split('/[^a-z0-9]+/i', $t) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), fn ($x) => $x !== '' && strlen($x) >= 2));
        return array_values(array_unique($parts));
    }

    private function quantile(array $sorted, float $q): float
    {
        $n = count($sorted);
        if ($n === 0) {
            return 0.0;
        }
        $pos = ($n - 1) * $q;
        $base = (int) floor($pos);
        $rest = $pos - $base;
        if (! isset($sorted[$base + 1])) {
            return (float) $sorted[$base];
        }
        return ((float) $sorted[$base]) + $rest * (((float) $sorted[$base + 1]) - ((float) $sorted[$base]));
    }

    private function item(string $id, string $name, string $description, $data, array $tags): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'data' => $data,
            'tags' => $tags,
        ];
    }

    private function takeAssoc(array $rows, int $limit, callable $map): array
    {
        $out = [];
        $i = 0;
        foreach ($rows as $k => $v) {
            $out[] = $map($k, $v);
            $i++;
            if ($i >= $limit) {
                break;
            }
        }
        return $out;
    }

    private function stddev(array $values, float $avg): float
    {
        $n = count($values);
        if ($n <= 1) {
            return 0.0;
        }
        $sum = 0.0;
        foreach ($values as $v) {
            $d = ((float) $v) - $avg;
            $sum += $d * $d;
        }
        return sqrt($sum / $n);
    }

    private function topRiskCombination(array $topStatusImpact, array $loopByStatus): array
    {
        $rows = [];
        foreach (array_slice($topStatusImpact, 0, 10) as $x) {
            $status = (string) ($x['status'] ?? '');
            if ($status === '') {
                continue;
            }
            $impact = (float) ($x['total_days'] ?? 0);
            $loop = (int) ($loopByStatus[$status] ?? 0);
            $riskScore = round(($impact * 0.7) + ($loop * 0.3), 2);
            $rows[] = [
                'status' => $status,
                'impact_days' => round($impact, 2),
                'loop_count' => $loop,
                'risk_score' => $riskScore,
            ];
        }
        usort($rows, fn ($a, $b) => $b['risk_score'] <=> $a['risk_score']);
        return array_slice($rows, 0, 10);
    }
}
