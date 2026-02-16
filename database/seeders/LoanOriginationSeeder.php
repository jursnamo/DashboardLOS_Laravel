<?php

namespace Database\Seeders;

use App\Models\LoanApplication;
use App\Models\LoanApprovalMatrix;
use Illuminate\Database\Seeder;

class LoanOriginationSeeder extends Seeder
{
    public function run(): void
    {
        $defaultMatrix = [
            ['division' => 'Commercial', 'segment' => 'commercial', 'bwmk_type' => null, 'actor_type' => 'maker', 'sequence_no' => 1, 'role_name' => 'RM', 'sla_hours' => 12, 'is_active' => true],
            ['division' => 'Commercial', 'segment' => 'commercial', 'bwmk_type' => null, 'actor_type' => 'checker', 'sequence_no' => 2, 'role_name' => 'BM', 'sla_hours' => 24, 'is_active' => true],
            ['division' => 'Commercial', 'segment' => 'commercial', 'bwmk_type' => null, 'actor_type' => 'approver', 'sequence_no' => 3, 'role_name' => 'BSM', 'sla_hours' => 24, 'is_active' => true],
            ['division' => 'Corporate', 'segment' => 'corporate', 'bwmk_type' => null, 'actor_type' => 'maker', 'sequence_no' => 1, 'role_name' => 'RM', 'sla_hours' => 12, 'is_active' => true],
            ['division' => 'Corporate', 'segment' => 'corporate', 'bwmk_type' => null, 'actor_type' => 'checker', 'sequence_no' => 2, 'role_name' => 'AM', 'sla_hours' => 24, 'is_active' => true],
            ['division' => 'Corporate', 'segment' => 'corporate', 'bwmk_type' => null, 'actor_type' => 'approver', 'sequence_no' => 3, 'role_name' => 'BAM', 'sla_hours' => 24, 'is_active' => true],
            ['division' => 'Commex', 'segment' => 'commex', 'bwmk_type' => null, 'actor_type' => 'maker', 'sequence_no' => 1, 'role_name' => 'RM', 'sla_hours' => 12, 'is_active' => true],
            ['division' => 'Commex', 'segment' => 'commex', 'bwmk_type' => null, 'actor_type' => 'checker', 'sequence_no' => 2, 'role_name' => 'BM', 'sla_hours' => 24, 'is_active' => true],
            ['division' => 'Commex', 'segment' => 'commex', 'bwmk_type' => null, 'actor_type' => 'approver', 'sequence_no' => 3, 'role_name' => 'BSM', 'sla_hours' => 24, 'is_active' => true],
        ];

        foreach ($defaultMatrix as $row) {
            LoanApprovalMatrix::updateOrCreate(
                [
                    'division' => $row['division'],
                    'segment' => $row['segment'],
                    'bwmk_type' => $row['bwmk_type'],
                    'actor_type' => $row['actor_type'],
                    'sequence_no' => $row['sequence_no'],
                    'role_name' => $row['role_name'],
                ],
                [
                    'sla_hours' => $row['sla_hours'],
                    'is_active' => $row['is_active'],
                ]
            );
        }

        $application = LoanApplication::create([
            'cif_number' => 'CIF-LOS-0001',
            'customer_name' => 'PT Maju Lancar Abadi',
            'division' => 'Commercial',
            'segment' => 'commercial',
            'loan_type' => 'working_capital',
            'apk_type' => 'manufaktur',
            'purpose' => 'Pembiayaan modal kerja dan ekspansi lini produksi',
            'plafond_amount' => 15000000000,
            'tenor_months' => 48,
            'bwmk_type' => 'non_deviasi',
            'rm_name' => 'RM Commercial A',
            'branch_name' => 'Jakarta Sudirman',
            'current_stage' => 'draft',
            'ideb_result_status' => 'clear',
        ]);

        $application->collaterals()->createMany([
            [
                'collateral_type' => 'property',
                'collateral_subtype' => 'Tanah dan Bangunan',
                'description' => 'Pabrik utama kawasan industri',
                'appraisal_value' => 10000000000,
                'liquidation_value' => 7500000000,
                'is_primary' => true,
            ],
            [
                'collateral_type' => 'non_property',
                'collateral_subtype' => 'Deposito',
                'description' => 'Cash collateral deposito',
                'appraisal_value' => 2500000000,
                'liquidation_value' => 2500000000,
            ],
        ]);

        $application->documents()->createMany([
            ['document_name' => 'Akta Perusahaan', 'document_category' => 'predefined', 'is_required' => true, 'is_uploaded' => true, 'verification_status' => 'valid'],
            ['document_name' => 'Laporan Keuangan', 'document_category' => 'predefined', 'is_required' => true, 'is_uploaded' => true, 'verification_status' => 'valid'],
            ['document_name' => 'Hasil IDEB', 'document_category' => 'predefined', 'is_required' => true, 'is_uploaded' => true, 'verification_status' => 'valid'],
        ]);

        $application->covenants()->createMany([
            ['covenant_phase' => 'pre_disbursement', 'covenant_text' => 'Perusahaan wajib menyerahkan polis asuransi agunan', 'is_mandatory' => true, 'status' => 'open'],
            ['covenant_phase' => 'post_disbursement', 'covenant_text' => 'Menjaga DSCR minimum 1.2x', 'is_mandatory' => true, 'status' => 'open'],
        ]);

        $application->approvals()->createMany([
            ['sequence_no' => 1, 'approver_role' => 'RM Head', 'decision' => 'pending'],
            ['sequence_no' => 2, 'approver_role' => 'Credit Risk Head', 'decision' => 'pending'],
        ]);

        $application->recalculateCollateralTotals();
    }
}
