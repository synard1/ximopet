<?php

namespace App\Livewire\Livestock\Mutation;

use Livewire\Component;
use App\Models\LivestockMutation;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\Livestock\LivestockMutationService;
use App\Config\CompanyConfig;

class DeleteLivestockMutation extends Component
{
    public $mutationId;
    public $mutation;
    public $items = [];
    public $confirmingDelete = false;
    public $deleteSuccess = false;
    public $deleteError = null;
    public $showDeleteMutation = false;

    protected $listeners = ['showDeleteMutation' => 'loadMutation'];

    public function mount()
    {
        // $this->mutationId = $mutationId;
        // $this->loadMutation($mutationId);
    }

    public function loadMutation($mutationId)
    {
        $this->mutation = LivestockMutation::with(['items', 'sourceLivestock', 'destinationLivestock'])
            ->findOrFail($mutationId);
        $this->items = $this->mutation->items;
        $this->deleteSuccess = false;
        $this->deleteError = null;
        $this->showDeleteMutation = true;
    }

    public function confirmDelete()
    {
        $this->confirmingDelete = true;
    }

    public function cancelDelete()
    {
        $this->confirmingDelete = false;
    }

    public function deleteMutation()
    {
        DB::beginTransaction();
        try {
            $service = new LivestockMutationService();
            $mutationId = $this->mutation->id;
            $mutation = LivestockMutation::with(['items', 'sourceLivestock', 'destinationLivestock'])->findOrFail($mutationId);

            $service->reverseMutationQuantities($mutation);

            // Cek config history_enabled
            $historySettings = CompanyConfig::getManualMutationHistorySettings();
            $useForceDelete = !($historySettings['history_enabled'] ?? false);
            Log::info('🛠️ DeleteLivestockMutation mode', ['force_delete' => $useForceDelete]);

            foreach ($mutation->items as $item) {
                if ($useForceDelete) {
                    $item->forceDelete();
                } else {
                    $item->delete();
                }
            }
            if ($useForceDelete) {
                $mutation->forceDelete();
            } else {
                $mutation->delete();
            }

            // Use the new public cleanup method
            $service->cleanupAfterMutationDelete($mutation);

            DB::commit();
            $this->deleteSuccess = true;
            $this->confirmingDelete = false;
            $this->emit('livestockMutationDeleted', $this->mutationId);
            Log::info('✅ LivestockMutation and all related data deleted', ['mutation_id' => $this->mutationId]);
            $this->logDeletionDocumentation($mutationId);
        } catch (\Exception $e) {
            DB::rollBack();
            $this->deleteError = $e->getMessage();
            Log::error('❌ Failed to delete LivestockMutation and related data', [
                'mutation_id' => $this->mutationId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Dokumentasi perubahan dan log untuk traceability
     */
    protected function logDeletionDocumentation($mutationId)
    {
        $logPath = base_path('docs/debugging/delete-livestock-mutation-log.md');
        $logEntry = "\n## [" . now()->format('Y-m-d H:i:s') . "] Deleted LivestockMutation: {$mutationId}\n";
        $logEntry .= "- Semua data terkait (items, batch, livestock, current livestock) telah diupdate/dihapus.\n";
        $logEntry .= "- Proses menggunakan LivestockMutationService::reverseMutationQuantities dan updateLivestockTotals.\n";
        $logEntry .= "- Dijalankan oleh user: " . (auth()->user()->name ?? 'system') . " (ID: " . (auth()->id() ?? '-') . ")\n";
        $logEntry .= "- Status: SUCCESS\n";
        file_put_contents($logPath, $logEntry, FILE_APPEND);
    }

    public function render()
    {
        return view('livewire.livestock.mutation.delete-livestock-mutation', [
            'mutation' => $this->mutation,
            'items' => $this->items,
            'confirmingDelete' => $this->confirmingDelete,
            'deleteSuccess' => $this->deleteSuccess,
            'deleteError' => $this->deleteError,
            'showDeleteMutation' => $this->showDeleteMutation,
        ]);
    }
}
