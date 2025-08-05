<?php

namespace App\Livewire\Livestock\Tracking;

use App\Models\Livestock;
use App\Services\Livestock\LivestockTrackingService;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;

class LivestockTrackingView extends Component
{
    use WithPagination;

    public $livestockId;
    public $trackingData = [];
    public $selectedTab = 'overview';
    public $isLoading = false;
    public $error = null;
    public $searchTerm = '';
    public $filterStatus = '';
    public $filterSourceType = '';
    public $showExportModal = false;
    public $exportFormat = 'json';

    protected $queryString = [
        'selectedTab' => ['except' => 'overview'],
        'searchTerm' => ['except' => ''],
        'filterStatus' => ['except' => ''],
        'filterSourceType' => ['except' => '']
    ];

    public function mount($livestockId = null)
    {
        // Get livestockId from route parameter if not provided
        if (!$livestockId && request()->route('livestockId')) {
            $livestockId = request()->route('livestockId');
        }

        $this->livestockId = $livestockId;
        if ($this->livestockId) {
            $this->loadTrackingData();
        }
    }

    public function loadTrackingData()
    {
        try {
            $this->isLoading = true;
            $this->error = null;

            $trackingService = app(LivestockTrackingService::class);
            $this->trackingData = $trackingService->getCompleteTrackingInfo($this->livestockId);

            if (isset($this->trackingData['error'])) {
                $this->error = $this->trackingData['message'];
            }

            Log::info('Livestock tracking data loaded', [
                'livestock_id' => $this->livestockId,
                'data_keys' => array_keys($this->trackingData)
            ]);
        } catch (\Exception $e) {
            $this->error = 'Failed to load tracking data: ' . $e->getMessage();
            Log::error('Error loading livestock tracking data', [
                'livestock_id' => $this->livestockId,
                'error' => $e->getMessage()
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    public function setTab($tab)
    {
        $this->selectedTab = $tab;
    }

    public function selectLivestock($livestockId)
    {
        $this->livestockId = $livestockId;
        $this->loadTrackingData();
    }

    public function exportData()
    {
        try {
            $trackingService = app(LivestockTrackingService::class);
            $exportData = $trackingService->exportTrackingData($this->livestockId, $this->exportFormat);

            if ($this->exportFormat === 'json') {
                return response()->json($exportData)
                    ->header('Content-Type', 'application/json')
                    ->header('Content-Disposition', 'attachment; filename="livestock_tracking_' . $this->livestockId . '.json"');
            } else {
                // Handle CSV export
                return response()->streamDownload(function () use ($exportData) {
                    echo $this->generateCsvContent($exportData);
                }, 'livestock_tracking_' . $this->livestockId . '.csv');
            }
        } catch (\Exception $e) {
            $this->error = 'Export failed: ' . $e->getMessage();
        }
    }

    private function generateCsvContent($data)
    {
        $csv = [];

        // Add headers
        $csv[] = ['Livestock Tracking Report'];
        $csv[] = ['Generated at: ' . now()->format('Y-m-d H:i:s')];
        $csv[] = [];

        // Add livestock info
        if (isset($data['livestock_info'])) {
            $csv[] = ['Livestock Information'];
            foreach ($data['livestock_info'] as $key => $value) {
                $csv[] = [ucwords(str_replace('_', ' ', $key)), $value];
            }
            $csv[] = [];
        }

        // Add origin chain
        if (isset($data['origin_chain'])) {
            $csv[] = ['Origin Chain'];
            $csv[] = ['Type', 'Date', 'Invoice', 'Supplier', 'Quantity', 'Price/Unit', 'Weight/Unit'];
            foreach ($data['origin_chain'] as $chain) {
                $csv[] = [
                    $chain['type'],
                    $chain['date'] ?? '',
                    $chain['invoice_number'] ?? '',
                    $chain['supplier'] ?? '',
                    $chain['quantity'] ?? '',
                    $chain['price_per_unit'] ?? '',
                    $chain['weight_per_unit'] ?? ''
                ];
            }
            $csv[] = [];
        }

        // Add performance metrics
        if (isset($data['performance_metrics'])) {
            $csv[] = ['Performance Metrics'];
            foreach ($data['performance_metrics'] as $key => $value) {
                $csv[] = [ucwords(str_replace('_', ' ', $key)), $value];
            }
            $csv[] = [];
        }

        // Convert to CSV string
        $output = fopen('php://temp', 'r+');
        foreach ($csv as $row) {
            fputcsv($output, $row);
        }
        rewind($output);
        $csvString = stream_get_contents($output);
        fclose($output);

        return $csvString;
    }

    public function getLivestockList()
    {
        $query = Livestock::query()
            ->with(['farm', 'coop', 'livestockStrain'])
            ->when($this->searchTerm, function ($query) {
                $query->where('name', 'like', '%' . $this->searchTerm . '%')
                    ->orWhereHas('farm', function ($q) {
                        $q->where('name', 'like', '%' . $this->searchTerm . '%');
                    })
                    ->orWhereHas('coop', function ($q) {
                        $q->where('name', 'like', '%' . $this->searchTerm . '%');
                    });
            })
            ->when($this->filterStatus, function ($query) {
                $query->where('status', $this->filterStatus);
            });

        return $query->paginate(10);
    }

    public function render()
    {
        $livestockList = $this->getLivestockList();

        return view('livewire.livestock.tracking.livestock-tracking-view', [
            'livestockList' => $livestockList,
            'trackingData' => $this->trackingData,
            'selectedTab' => $this->selectedTab,
            'isLoading' => $this->isLoading,
            'error' => $this->error
        ]);
    }
}
