<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'RM',
            'BM',
            'BSM',
            'AM',
            'BAM',
            'CIV Maker',
            'CIV Checker',
            'CS Maker',
            'CS Checker',
            'DV Maker',
            'DV Checker',
            'Legal Maker',
            'Legal Checker',
            'OCR Maker',
            'OCR Checker',
            'Underwriter Maker',
            'Underwriter Checker',
            'PLI',
            'PO Trade Maker',
            'PO Trade Checker',
            'PO Value Chain Maker',
            'PO Value Chain Checker',
            'Treasury Maker',
            'Treasury Checker',
            'Unit Syariah Primover Maker',
            'Unit Syariah Primover Checker',
            'Valuer Internal',
            'Valuer External',
            'Credam Maker',
            'Credam Checker',
        ];

        // Create a test user for each role
        foreach ($roles as $index => $roleName) {
            $user = User::create([
                'name' => 'Test ' . str_replace(' ', '_', $roleName),
                'email' => 'test' . ($index + 1) . '@example.com',
                'password' => bcrypt('password'),
            ]);

            $user->assignRole($roleName);
        }
    }
}
