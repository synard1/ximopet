<?php

namespace App\Services\Livestock;

use Illuminate\Support\Facades\DB;

use App\Services\Recording\RecordingService;
use App\Services\Livestock\LivestockCostService;

use App\Models\LivestockMutation;
use App\Models\LivestockMutationItem;

class LivestockMutationService
{
    protected $recordingService;
    protected $costService;

    public function __construct(
        // RecordingService $recordingService,
        LivestockCostService $costService
    ) {
        // $this->recordingService = $recordingService;
        $this->costService = $costService;
    }

    public function mutate(array $data)
    {
        DB::beginTransaction();

        try {
            // Buat record LivestockMutation
            $mutation = LivestockMutation::create([
                'tanggal' => $data['date'],
                'from_livestock_id' => $data['from_livestock_id'],
                'to_livestock_id' => $data['to_livestock_id'],
                'keterangan' => $data['keterangan'] ?? null,
            ]);

            // Buat satu atau lebih LivestockMutationItem
            LivestockMutationItem::create([
                'livestock_mutation_id' => $mutation->id,
                'quantity' => $data['quantity'],
                'weight' => $data['weight'] ?? 0,
                'keterangan' => $data['keterangan'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Snapshot recording dari sumber (from_livestock_id)
            // $this->recordingService->snapshot([
            //     'livestock_id' => $data['from_livestock_id'],
            //     'tanggal' => $data['date'],
            //     'notes' => 'Snapshot sebelum mutasi keluar',
            // ]);

            // // Snapshot recording ke tujuan (to_livestock_id)
            // $this->recordingService->snapshot([
            //     'livestock_id' => $data['to_livestock_id'],
            //     'tanggal' => $data['date'],
            //     'notes' => 'Snapshot setelah mutasi masuk',
            // ]);

            // // Hitung dan catat biaya ke to_livestock_id
            // $this->costService->recordCost([
            //     'livestock_id' => $data['to_livestock_id'],
            //     'tanggal' => $data['date'],
            // ]);

            DB::commit();
            return $mutation;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
