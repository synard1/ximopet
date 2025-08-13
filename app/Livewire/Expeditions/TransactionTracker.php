<?php

namespace App\Livewire\Expeditions;

use App\Services\ExpeditionService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class TransactionTracker extends Component
{
    use WithPagination;

    public $expeditionId = '';
    public $transactionType = '';
    public $zone = '';
    public $status = '';
    public $startDate = '';
    public $endDate = '';
    public $perPage = 15;

    public $expeditions = [];
    public $zones = [];
    public $summary = [];
    public $showFilters = false;

    protected ExpeditionService $expeditionService;

    public function boot(ExpeditionService $expeditionService)
    {
        $this->expeditionService = $expeditionService;
    }

    public function mount()
    {
        $this->loadExpeditions();
        $this->loadSummary();
    }

    public function loadExpeditions()
    {
        try {
            $this->expeditions = $this->expeditionService->getActiveExpeditions();

            Log::info('Expeditions loaded in Livewire component', [
                'user_id' => Auth::user()->id,
                'count' => $this->expeditions->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading expeditions in Livewire', [
                'error' => $e->getMessage(),
                'user_id' => Auth::user()->id
            ]);

            $this->addError('expeditions', 'Gagal memuat data ekspedisi');
        }
    }

    public function loadZones()
    {
        if (empty($this->expeditionId)) {
            $this->zones = [];
            return;
        }

        try {
            $this->zones = $this->expeditionService->getAvailableZones($this->expeditionId);

            Log::info('Zones loaded for expedition', [
                'user_id' => Auth::user()->id,
                'expedition_id' => $this->expeditionId,
                'zone_count' => count($this->zones)
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading zones in Livewire', [
                'error' => $e->getMessage(),
                'user_id' => Auth::user()->id,
                'expedition_id' => $this->expeditionId
            ]);

            $this->addError('zones', 'Gagal memuat zona yang tersedia');
        }
    }

    public function loadSummary()
    {
        try {
            $filters = $this->getFilters();
            $this->summary = $this->expeditionService->getExpeditionCostSummary($filters);

            Log::info('Expedition summary loaded in Livewire', [
                'user_id' => Auth::user()->id,
                'filters' => $filters,
                'total_transactions' => $this->summary['total_transactions'] ?? 0
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading expedition summary in Livewire', [
                'error' => $e->getMessage(),
                'user_id' => Auth::user()->id
            ]);

            $this->addError('summary', 'Gagal memuat ringkasan ekspedisi');
        }
    }

    public function getTransactions()
    {
        try {
            $filters = $this->getFilters();
            $result = $this->expeditionService->getExpeditionTransactions($filters, $this->perPage);

            Log::info('Expedition transactions loaded in Livewire', [
                'user_id' => Auth::user()->id,
                'filters' => $filters,
                'total' => $result['pagination']['total'] ?? 0
            ]);

            return $result;
        } catch (\Exception $e) {
            Log::error('Error loading expedition transactions in Livewire', [
                'error' => $e->getMessage(),
                'user_id' => Auth::user()->id
            ]);

            $this->addError('transactions', 'Gagal memuat transaksi ekspedisi');
            return ['data' => [], 'pagination' => []];
        }
    }

    public function getEstimatedCost($expeditionId, $zone, $weight)
    {
        try {
            $estimatedCost = $this->expeditionService->getEstimatedCost(
                $expeditionId,
                $zone,
                $weight
            );

            Log::info('Estimated cost calculated in Livewire', [
                'user_id' => Auth::user()->id,
                'expedition_id' => $expeditionId,
                'zone' => $zone,
                'weight' => $weight,
                'estimated_cost' => $estimatedCost['estimated_cost'] ?? null
            ]);

            return $estimatedCost;
        } catch (\Exception $e) {
            Log::error('Error calculating estimated cost in Livewire', [
                'error' => $e->getMessage(),
                'user_id' => Auth::user()->id
            ]);

            $this->addError('estimation', 'Gagal menghitung estimasi biaya');
            return null;
        }
    }

    public function updatedExpeditionId()
    {
        $this->loadZones();
        $this->resetPage();
        $this->loadSummary();
    }

    public function updatedTransactionType()
    {
        $this->resetPage();
        $this->loadSummary();
    }

    public function updatedZone()
    {
        $this->resetPage();
        $this->loadSummary();
    }

    public function updatedStatus()
    {
        $this->resetPage();
        $this->loadSummary();
    }

    public function updatedStartDate()
    {
        $this->resetPage();
        $this->loadSummary();
    }

    public function updatedEndDate()
    {
        $this->resetPage();
        $this->loadSummary();
    }

    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    public function resetFilters()
    {
        $this->expeditionId = '';
        $this->transactionType = '';
        $this->zone = '';
        $this->status = '';
        $this->startDate = '';
        $this->endDate = '';
        $this->resetPage();
        $this->loadSummary();
    }

    private function getFilters(): array
    {
        $filters = [];

        if (!empty($this->expeditionId)) {
            $filters['expedition_id'] = $this->expeditionId;
        }

        if (!empty($this->transactionType)) {
            $filters['transaction_type'] = $this->transactionType;
        }

        if (!empty($this->zone)) {
            $filters['zone'] = $this->zone;
        }

        if (!empty($this->status)) {
            $filters['status'] = $this->status;
        }

        if (!empty($this->startDate)) {
            $filters['start_date'] = $this->startDate;
        }

        if (!empty($this->endDate)) {
            $filters['end_date'] = $this->endDate;
        }

        return $filters;
    }

    public function render()
    {
        $transactions = $this->getTransactions();

        return view('livewire.expeditions.transaction-tracker', [
            'transactions' => $transactions['data'],
            'pagination' => $transactions['pagination'],
            'summary' => $this->summary,
            'expeditions' => $this->expeditions,
            'zones' => $this->zones
        ]);
    }
}
