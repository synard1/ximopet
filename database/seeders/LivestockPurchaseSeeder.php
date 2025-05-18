<?php

namespace Database\Seeders;

use App\Models\CurrentLivestock;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Farm;
use App\Models\Kandang;
use App\Models\Rekanan;
use App\Models\Partner;
use App\Models\Livestock;
use App\Models\LivestockBreed;
use App\Models\LivestockBreedStandard;
use App\Models\LivestockPurchase;
use App\Models\LivestockPurchaseItem;
use App\Models\User;

use App\Services\Livestock\LivestockBreedStandardService;

class LivestockPurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $emails = [
            'supervisor@demo.com' => 'DF01',
            'supervisor@demo2.com' => 'DF02',
        ];

        $vendor = Partner::where('type', 'Supplier')->inRandomOrder()->first();
        $vendorId = optional($vendor)->id; // Gunakan optional()
        
        $breed = LivestockBreed::inRandomOrder()->first();
        $breedId = optional($breed)->id;   // Gunakan optional()

        if (!$breed) {
            $this->command->warn("No livestock breed found. Using null for livestock_breed_id.");
            // Anda mungkin ingin menambahkan logika alternatif di sini, seperti membuat breed default
        }

        $hargaPerEkor = 5500;

        // Inisialisasi service
        $livestockBreedStandardService = new LivestockBreedStandardService();

        foreach ($emails as $email => $farmCode) {
            $user = User::where('email', $email)->first();
            $userId = optional($user)->id;

            $farm = Farm::where('code', $farmCode)->where('status', 'Aktif')->first();
            $farmId = optional($farm)->id;

            $kandang1 = Kandang::where('farm_id', $farmId)->where('kode', "K01-{$farmCode}")->first();
            $kandang2 = Kandang::where('farm_id', $farmId)->where('kode', "K02-{$farmCode}")->first();

            // Pastikan kandang1 dan kandang2 tidak null sebelum mengakses properti
            $kandang1Id = optional($kandang1)->id;
            $kandang2Id = optional($kandang2)->id;

            $data = [
                [
                    'kandang_id' => $kandang1Id,
                    'tanggal' => '2025-03-01',
                    'jumlah' => 10000,
                    'name' => "Periode Maret - " . optional($kandang1)->nama,
                ],
                [
                    'kandang_id' => $kandang2Id,
                    'tanggal' => '2025-03-03',
                    'jumlah' => 8000,
                    'name' => "Periode Maret - " . optional($kandang2)->nama,
                ],
            ];

            try {
                $livestockBreedStandard = LivestockBreedStandard::where('livestock_breed_id', $breedId)->first();
                $livestockBreedStandardId = optional($livestockBreedStandard)->id;

                DB::beginTransaction();

                foreach ($data as $index => $item) {
                    // 1. Buat Livestock
                    $livestock = Livestock::create([
                        'farm_id' => $farmId,
                        'kandang_id' => $item['kandang_id'],
                        'name' => $item['name'],
                        'livestock_breed_id' => $breedId, // Sekarang aman, bisa null
                        'breed' => $breed->name, // Sekarang aman, bisa null
                        'livestock_breed_standard_id' => $livestockBreedStandardId, // Tambahkan ini
                        'start_date' => Carbon::parse($item['tanggal']),
                        'populasi_awal' => $item['jumlah'],
                        'quantity_depletion' => 0,
                        'quantity_sales' => 0,
                        'quantity_mutated' => 0,
                        'berat_awal' => 40,
                        'harga' => $hargaPerEkor,
                        'status' => 'active',
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);

                    if ($livestock) {
                        // Jalankan service setelah Livestock dibuat
                        try {
                            $livestockBreedStandardService->updateLivestockBreedStandard([
                                'livestock_id' => $livestock->id,
                                'livestock_breed_standard_id' => $livestockBreedStandardId,
                            ]);
                            $this->command->info("LivestockBreedStandardService dijalankan untuk Livestock ID: {$livestock->id}");
                        } catch (\Exception $e) {
                            $this->command->error("Gagal menjalankan LivestockBreedStandardService untuk Livestock ID: {$livestock->id}. Error: " . $e->getMessage());
                            // Pertimbangkan untuk melempar kembali exception atau melakukan rollback transaksi
                            throw $e; // Contoh: Lempar kembali untuk rollback
                        }
                    }

                    // 2. Livestock Purchase
                    $purchase = LivestockPurchase::create([
                        'invoice_number' => 'INV-' . strtoupper(optional($farm)->kode) . '-' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                        'tanggal' => $item['tanggal'],
                        'vendor_id' => $vendorId,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);

                    // 3. Purchase Item
                    LivestockPurchaseItem::create([
                        'livestock_purchase_id' => optional($purchase)->id,
                        'livestock_id' => optional($livestock)->id,
                        'jumlah' => $item['jumlah'],
                        'harga_per_ekor' => $hargaPerEkor,
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);

                    // 4. Current Livestock
                    CurrentLivestock::create([
                        'livestock_id' => optional($livestock)->id,
                        'farm_id' => $farmId,
                        'kandang_id' => $item['kandang_id'],
                        'quantity' => $item['jumlah'],
                        'berat_total' => $item['jumlah'] * 40,
                        'avg_berat' => 40,
                        'age' => 0,
                        'status' => 'active',
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);

                    // 5. Update Kandang
                    if ($item['kandang_id']) { // Pastikan kandang_id tidak null
                        $kandang = Kandang::find($item['kandang_id']);
                        if ($kandang) { // Pastikan kandang ditemukan
                            $kandang->update([
                                'livestock_id' => optional($livestock)->id,
                                'jumlah' => $item['jumlah'],
                                'berat' => $item['jumlah'] * 40,
                                'status' => 'Digunakan',
                                'updated_by' => $userId,
                            ]);
                        }
                    }
                }

                DB::commit();
            } catch (\Throwable $th) {
                DB::rollBack();
                $this->command->info("Seeder gagal untuk $email: " . $th->getMessage());
            }
        }
    }

}

