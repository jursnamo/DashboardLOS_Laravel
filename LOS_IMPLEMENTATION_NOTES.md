# Loan Origination System Implementation Notes

Sumber kebutuhan bisnis diambil dari file Excel:
`C:\Users\Jursnamo\Downloads\DLOAN 05102020 (Phase1).xlsx`

## Mapping UAT ke Fitur

- `Debtor Management`:
  - Data aplikasi menyimpan CIF, nama nasabah, segment, RM, branch.
  - Validasi mandatory field saat create/update.
- `Commercial / Corporate / Commex Loan`:
  - Segment didukung: `commercial`, `corporate`, `commex`.
  - APK type bisa diisi (manufaktur, kontraktor, real estate, trader, hotel, dll).
- `Collateral Input`:
  - Mendukung `property` dan `non_property`.
  - Simpan appraisal dan liquidation value.
  - Auto recalc total collateral dan total liquidation di level aplikasi.
- `Upload Document`:
  - Dokumen predefined dibuat otomatis pada saat create aplikasi.
  - Bisa update status upload dan status verifikasi.
- `Approval Page based on BWMK`:
  - `non_deviasi`: 2 step approval.
  - `deviasi`: 3 step approval (tambahan Credit Committee).
- `Terms, Condition, Covenants`:
  - Covenant phase: sebelum, saat, dan sesudah pencairan.
- `Monitoring`:
  - Activity log per aplikasi.
  - KPI card ringkas pada halaman list (total, draft, review, approved, rejected, total plafond).

## Workflow Inti

1. Create aplikasi kredit.
2. Tambah collateral + update dokumen wajib.
3. Submit ke reviewer (`draft/rejected` -> `review`).
4. Proses approval sequence.
5. Jika semua step approve -> status `approved`.
6. Jika reject di step manapun -> status `rejected` dengan catatan.
