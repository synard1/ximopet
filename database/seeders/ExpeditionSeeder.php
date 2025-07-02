<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Support\Str;

class ExpeditionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin user as primary user for created_by
        $adminUser = User::where('email', 'admin@peternakan.digital')->first();
        if (!$adminUser) {
            $adminUser = User::first(); // Fallback to any user
        }

        $expeditions = [
            [
                'code' => 'EXP001',
                'name' => 'PT Ekspedisi Utama',
                'contact_person' => 'John Doe',
                'phone_number' => '081234567890',
                'address' => 'Jl. Raya Utama No. 123, Jakarta',
                'description' => 'Ekspedisi pengiriman barang ke seluruh Indonesia',
                'status' => 'active',
            ],
            [
                'code' => 'EXP002',
                'name' => 'CV Logistik Jaya',
                'contact_person' => 'Jane Smith',
                'phone_number' => '082345678901',
                'address' => 'Jl. Logistik No. 45, Surabaya',
                'description' => 'Layanan pengiriman cepat dan aman',
                'status' => 'active',
            ],
            [
                'code' => 'EXP003',
                'name' => 'PT Cargo Express',
                'contact_person' => 'Ahmad Rizki',
                'phone_number' => '083456789012',
                'address' => 'Jl. Cargo No. 67, Bandung',
                'description' => 'Spesialis pengiriman barang berat',
                'status' => 'active',
            ],
            [
                'code' => 'EXP004',
                'name' => 'UD Transportasi Mandiri',
                'contact_person' => 'Siti Aminah',
                'phone_number' => '084567890123',
                'address' => 'Jl. Transportasi No. 89, Medan',
                'description' => 'Layanan pengiriman lokal dan antar kota',
                'status' => 'active',
            ],
            [
                'code' => 'EXP005',
                'name' => 'PT Ekspedisi Global',
                'contact_person' => 'Budi Santoso',
                'phone_number' => '085678901234',
                'address' => 'Jl. Global No. 101, Makassar',
                'description' => 'Pengiriman domestik dan internasional',
                'status' => 'active',
            ],
        ];

        foreach ($expeditions as $expedition) {
            Partner::create([
                'id' => Str::uuid(),
                'type' => 'Expedition',
                'code' => $expedition['code'],
                'name' => $expedition['name'],
                'contact_person' => $expedition['contact_person'],
                'phone_number' => $expedition['phone_number'],
                'address' => $expedition['address'],
                'description' => $expedition['description'],
                'status' => $expedition['status'],
                'created_by' => $adminUser ? $adminUser->id : null,
                'updated_by' => $adminUser ? $adminUser->id : null,
            ]);
        }
    }
}
