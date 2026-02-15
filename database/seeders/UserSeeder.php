<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Schema;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Test RM', 'email' => 'test1@example.com', 'role' => 'RM'],
            ['name' => 'Test BM', 'email' => 'test2@example.com', 'role' => 'BM'],
            ['name' => 'Test BSM', 'email' => 'test3@example.com', 'role' => 'BSM'],
            ['name' => 'Test AM', 'email' => 'test4@example.com', 'role' => 'AM'],
            ['name' => 'Test BAM', 'email' => 'test5@example.com', 'role' => 'BAM'],
            ['name' => 'Test CIV_Maker', 'email' => 'test6@example.com', 'role' => 'CIV Maker'],
            ['name' => 'Test CIV_Checker', 'email' => 'test7@example.com', 'role' => 'CIV Checker'],
            ['name' => 'Test CS_Maker', 'email' => 'test8@example.com', 'role' => 'CS Maker'],
            ['name' => 'Test CS_Checker', 'email' => 'test9@example.com', 'role' => 'CS Checker'],
            ['name' => 'Test DV_Maker', 'email' => 'test10@example.com', 'role' => 'DV Maker'],
            ['name' => 'Test DV_Checker', 'email' => 'test11@example.com', 'role' => 'DV Checker'],
            ['name' => 'Test Legal_Maker', 'email' => 'test12@example.com', 'role' => 'Legal Maker'],
            ['name' => 'Test Legal_Checker', 'email' => 'test13@example.com', 'role' => 'Legal Checker'],
            ['name' => 'Test OCR_Maker', 'email' => 'test14@example.com', 'role' => 'OCR Maker'],
            ['name' => 'Test OCR_Checker', 'email' => 'test15@example.com', 'role' => 'OCR Checker'],
            ['name' => 'Test Underwriter_Maker', 'email' => 'test16@example.com', 'role' => 'Underwriter Maker'],
            ['name' => 'Test Underwriter_Checker', 'email' => 'test17@example.com', 'role' => 'Underwriter Checker'],
            ['name' => 'Test PLI', 'email' => 'test18@example.com', 'role' => 'PLI'],
            ['name' => 'Test PO_Trade_Maker', 'email' => 'test19@example.com', 'role' => 'PO Trade Maker'],
            ['name' => 'Test PO_Trade_Checker', 'email' => 'test20@example.com', 'role' => 'PO Trade Checker'],
            ['name' => 'Test PO_Value_Chain_Maker', 'email' => 'test21@example.com', 'role' => 'PO Value Chain Maker'],
            ['name' => 'Test PO_Value_Chain_Checker', 'email' => 'test22@example.com', 'role' => 'PO Value Chain Checker'],
            ['name' => 'Test Treasury_Maker', 'email' => 'test23@example.com', 'role' => 'Treasury Maker'],
            ['name' => 'Test Treasury_Checker', 'email' => 'test24@example.com', 'role' => 'Treasury Checker'],
            ['name' => 'Test Unit_Syariah_Primover_Maker', 'email' => 'test25@example.com', 'role' => 'Unit Syariah Primover Maker'],
            ['name' => 'Test Unit_Syariah_Primover_Checker', 'email' => 'test26@example.com', 'role' => 'Unit Syariah Primover Checker'],
            ['name' => 'Test Valuer_Internal', 'email' => 'test27@example.com', 'role' => 'Valuer Internal'],
            ['name' => 'Test Valuer_External', 'email' => 'test28@example.com', 'role' => 'Valuer External'],
            ['name' => 'Test Credam_Maker', 'email' => 'test29@example.com', 'role' => 'Credam Maker'],
            ['name' => 'Test Credam_Checker', 'email' => 'test30@example.com', 'role' => 'Credam Checker'],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => bcrypt('password'),
                ]
            );

            if (Schema::hasTable('roles')) {
                Role::firstOrCreate(['name' => $userData['role']]);
                if (! $user->hasRole($userData['role'])) {
                    $user->assignRole($userData['role']);
                }
            }
        }
    }
}
