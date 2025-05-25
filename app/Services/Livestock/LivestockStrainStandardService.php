<?php

namespace App\Services\Livestock;

use App\Models\LivestockCost;
use App\Models\Item as Feed;
use App\Models\FeedUsage;
use App\Models\FeedUsageDetail;
use App\Models\Livestock;
use App\Models\Recording;
use App\Models\LivestockStrainStandard;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LivestockStrainStandardService
{
    public function updateLivestockStrainStandard($data)
    {
        try {
            DB::beginTransaction();

            $standarBobot = LivestockStrainStandard::where('id', $data['livestock_strain_standard_id'])->first();
            $kelompokTernak = Livestock::findOrFail($data['livestock_id']);

            // Prepare the new data structure
            $newData = [
                'livestock_strain_standard' => [
                    'id' => $standarBobot->id,
                    'name' => $standarBobot->breed ?? '',
                    'description' => $standarBobot->description ?? '',
                    'data' => $standarBobot->standar_data,
                ]
            ];

            // Get current data or initialize empty array
            $currentData = $kelompokTernak->data ?? [];

            // Remove any existing standar_bobot entries
            $filteredData = array_filter($currentData, function ($item) {
                return !isset($item['livestock_strain_standard']);
            });

            // Add the new standar_bobot data
            $filteredData[] = $newData;

            // Update the kelompok_ternak with new data
            $kelompokTernak->data = array_values($filteredData);
            $kelompokTernak->save();

            DB::commit();
            Log::info("Updated Standar Bobot data for KelompokTernak ID: {$kelompokTernak->id} with new Standar Bobot ID: {$standarBobot->id}");

            return response()->json(['message' => 'Kelompok Ternak updated successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to update Standar Bobot data: " . $e->getMessage());
            throw new \Exception('Failed to update Standar Bobot data: ' . $e->getMessage());
        }
    }
}
