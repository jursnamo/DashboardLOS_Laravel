# AI Dashboard API Catalog (64 Items)

Dokumen ini menjelaskan 64 API context list yang dipakai AI Chat untuk menjawab pertanyaan berbasis datamart dashboard.

Lokasi implementasi:
- `app/Services/Ai/DashboardApiCatalogService.php`
- Endpoint katalog: `GET /api/ai/dashboard-catalog`

Format setiap item:
- `id`: kode API context
- `name`: nama konteks
- `description`: penjelasan data
- `data`: payload ringkas
- `tags`: kata kunci seleksi otomatis

## Daftar API

| No | ID | Nama | Kegunaan Utama | Contoh Pertanyaan |
|---|---|---|---|---|
| 1 | `api_01_overview` | Overview KPI | Ringkasan total aplikasi, average TAT, total outlier | "Overview dashboard sekarang bagaimana?" |
| 2 | `api_02_tat_stats` | TAT Statistics | Q1, median, Q3, IQR, outlier boundary | "Q1, median, Q3 kita berapa?" |
| 3 | `api_03_outlier_summary` | Outlier Summary | Daftar app outlier teratas | "Outlier kita app mana saja?" |
| 4 | `api_04_status_impact` | Status Impact | Status dengan total dampak durasi terbesar | "Status bottleneck terbesar apa?" |
| 5 | `api_05_slowest_status` | Slowest Status | Top status paling lambat | "Status paling lambat saat ini?" |
| 6 | `api_06_fastest_status` | Fastest Status | Top status paling cepat | "Status paling cepat apa?" |
| 7 | `api_07_top_branch_volume` | Top Branch by Volume | Cabang volume aplikasi tertinggi | "Cabang paling banyak aplikasi?" |
| 8 | `api_08_top_branch_tat` | Top Branch by Avg TAT | Cabang average TAT tertinggi | "Cabang paling lambat?" |
| 9 | `api_09_monthly_volume` | Monthly Volume Trend | Tren volume aplikasi per bulan | "Volume 6 bulan terakhir naik/turun?" |
| 10 | `api_10_monthly_tat` | Monthly Avg TAT Trend | Tren average TAT per bulan | "Tren TAT bulanan bagaimana?" |
| 11 | `api_11_purpose_distribution` | Purpose Distribution | Distribusi aplikasi per purpose | "Purpose yang paling dominan?" |
| 12 | `api_12_segment_distribution` | Segment Distribution | Distribusi aplikasi per segment | "Corporate vs Commercial berapa?" |
| 13 | `api_13_limit_summary` | Approved Limit Summary | Total dan average approved limit | "Total approved limit berapa?" |
| 14 | `api_14_top_apps_tat` | Top Applications by TAT | App dengan TAT tertinggi | "Top 10 aplikasi TAT tertinggi?" |
| 15 | `api_15_status_loop` | Status Loop Frequency | Frekuensi loop/rework per status | "Status yang paling sering loop?" |
| 16 | `api_16_sla_proxy` | SLA Proxy | Estimasi breach dari outlier | "Estimasi SLA breach kita?" |
| 17 | `api_17_data_freshness` | Data Freshness | Informasi batch sumber data | "Data ini update kapan?" |
| 18 | `api_18_status_pair_count` | Status Pair Count | Jumlah pasangan app-status | "Berapa jumlah status pair?" |
| 19 | `api_19_flow_event_summary` | Flow Event Summary | Ringkasan flow events | "Berapa total flow event?" |
| 20 | `api_20_recommendation_basis` | Recommendation Basis | Basis prioritas aksi dari impact+loop | "Prioritas action plan apa?" |
| 21 | `api_21_branch_outlier` | Branch Outlier Count | Jumlah outlier per cabang | "Outlier paling banyak di cabang mana?" |
| 22 | `api_22_purpose_outlier` | Purpose Outlier Count | Jumlah outlier per purpose | "Purpose dengan outlier terbanyak?" |
| 23 | `api_23_segment_outlier` | Segment Outlier Count | Jumlah outlier per segment | "Segment paling berisiko outlier?" |
| 24 | `api_24_status_app_count` | Status App Count | Jumlah aplikasi melewati status | "Status mana coverage tertinggi?" |
| 25 | `api_25_status_avg_duration` | Status Avg Duration | Rata-rata durasi per status | "Avg durasi tiap status berapa?" |
| 26 | `api_26_monthly_outlier` | Monthly Outlier Trend | Tren outlier per bulan | "Outlier bulanan meningkat?" |
| 27 | `api_27_limit_stats` | Limit Distribution Stats | Q1/Median/Q3 untuk limit | "Median limit aplikasi berapa?" |
| 28 | `api_28_limit_buckets` | Limit Buckets | Distribusi bucket limit | "Distribusi limit per bucket?" |
| 29 | `api_29_tat_buckets` | TAT Buckets | Distribusi bucket TAT | "Bucket TAT terbanyak?" |
| 30 | `api_30_tat_dispersion` | TAT Dispersion | Stddev dan CV TAT | "Variasi TAT seberapa tinggi?" |
| 31 | `api_31_top_limit_apps` | Top Limit Applications | App dengan limit tertinggi | "Top app berdasarkan limit?" |
| 32 | `api_32_low_limit_apps` | Lowest Limit Applications | App dengan limit terendah | "App limit terendah?" |
| 33 | `api_33_fastest_apps` | Fastest Applications | App dengan TAT terendah | "Aplikasi paling cepat?" |
| 34 | `api_34_slowest_apps` | Slowest Applications | App dengan TAT tertinggi | "Aplikasi paling lambat?" |
| 35 | `api_35_branch_concentration` | Branch Concentration | Share volume per cabang | "Konsentrasi volume cabang?" |
| 36 | `api_36_purpose_concentration` | Purpose Concentration | Share volume per purpose | "Konsentrasi purpose?" |
| 37 | `api_37_segment_concentration` | Segment Concentration | Share volume per segment | "Konsentrasi segment?" |
| 38 | `api_38_purpose_avg_tat` | Purpose Avg TAT | Average TAT per purpose | "Purpose paling lambat?" |
| 39 | `api_39_segment_avg_tat` | Segment Avg TAT | Average TAT per segment | "Segment paling lambat?" |
| 40 | `api_40_monthly_share` | Monthly Volume Share | Share kontribusi per bulan | "Bulan kontribusi terbesar?" |
| 41 | `api_41_mom_volume_delta` | MoM Volume Delta | Delta volume MoM terakhir | "MoM volume terakhir berapa?" |
| 42 | `api_42_mom_tat_delta` | MoM TAT Delta | Delta TAT MoM terakhir | "MoM TAT membaik atau tidak?" |
| 43 | `api_43_month_key_list` | Month Key List | Daftar periode bulan tersedia | "Data mencakup bulan apa saja?" |
| 44 | `api_44_status_coverage_ratio` | Status Coverage Ratio | Persentase coverage status | "Coverage status tertinggi?" |
| 45 | `api_45_outlier_rate_by_branch` | Outlier Rate by Branch | Outlier rate per cabang | "Outlier rate tertinggi cabang mana?" |
| 46 | `api_46_outlier_rate_by_purpose` | Outlier Rate by Purpose | Outlier rate per purpose | "Outlier rate per purpose?" |
| 47 | `api_47_outlier_rate_by_segment` | Outlier Rate by Segment | Outlier rate per segment | "Outlier rate per segment?" |
| 48 | `api_48_branch_limit_avg` | Branch Avg Limit | Average limit per cabang | "Cabang dengan avg limit terbesar?" |
| 49 | `api_49_branch_tat_and_limit` | Branch TAT & Limit | Perbandingan avg TAT + avg limit cabang | "Cabang TAT tinggi tapi limit tinggi mana?" |
| 50 | `api_50_status_vs_loop` | Status vs Loop | Durasi status vs loop count | "Status yang lama sekaligus loop tinggi?" |
| 51 | `api_51_top_risk_combo` | Top Risk Combination | Skor gabungan risk status | "Top risk status kombinasi impact+loop?" |
| 52 | `api_52_bottom_risk_status` | Lowest Risk Status | Status dengan risiko rendah | "Status paling low risk?" |
| 53 | `api_53_mid_tat_band` | Middle TAT Band | Jumlah app di rentang Q1-Q3 | "Berapa app di zona normal?" |
| 54 | `api_54_high_tat_band` | High TAT Band | Jumlah app di atas Q3 | "Berapa app high TAT?" |
| 55 | `api_55_low_tat_band` | Low TAT Band | Jumlah app di bawah Q1 | "Berapa app low TAT?" |
| 56 | `api_56_near_outlier_band` | Near Outlier Band | App mendekati batas outlier | "App near outlier ada berapa?" |
| 57 | `api_57_data_shape` | Data Shape | Ukuran dataset (apps/status/flow/period) | "Ukuran dataset saat ini?" |
| 58 | `api_58_freshness_extended` | Freshness Extended | Metadata batch + status data | "Metadata data terbaru?" |
| 59 | `api_59_exec_summary` | Execution Summary | Ringkasan eksekutif cepat | "Executive summary untuk manajemen?" |
| 60 | `api_60_ai_default_pack` | AI Default Pack | Paket default untuk pertanyaan umum | "Kasih ringkasan umum dashboard." |
| 61 | `api_61_monthly_avg_tat_jan_dec` | Monthly Avg TAT Jan-Dec | Avg TAT per bulan Jan-Des | "Avg TAT Jan sampai Dec berapa?" |
| 62 | `api_62_monthly_tat_with_volume` | Monthly TAT with Volume | Avg TAT + volume per bulan | "TAT bulanan beserta volumenya?" |
| 63 | `api_63_monthly_tat_rank` | Monthly TAT Rank | Ranking bulan berdasarkan Avg TAT | "Bulan dengan TAT tertinggi apa?" |
| 64 | `api_64_top_waiting_bottleneck` | Top Waiting Bottleneck (A->B) | Top 10 waiting time antar transisi status | "Transisi status mana waiting time tertinggi?" |

## Cara Cek Data Katalog

Panggil endpoint:

```http
GET /api/ai/dashboard-catalog
```

Contoh response ringkas:

```json
{
  "count": 64,
  "items": [
    {
      "id": "api_01_overview",
      "name": "Overview KPI",
      "description": "Ringkasan total aplikasi, outlier, dan average TAT.",
      "data": { "...": "..." },
      "tags": ["ringkasan", "overview", "kpi"]
    }
  ]
}
```

## Catatan

- AI Chat tidak selalu memuat semua 60 item sekaligus.
- Sistem akan memilih item yang paling relevan dengan pertanyaan user.
- Data sumber berasal dari datamart terbaru (`dashboard_datamarts.payload_json`).
