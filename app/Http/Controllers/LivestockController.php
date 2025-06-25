<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DataTables\LivestockDataTable;
use App\DataTables\LivestockMutationDataTable;
use App\DataTables\OVKRecordDataTable;
use App\DataTables\LivestockPurchaseDataTable;
use App\DataTables\LivestockStrainDataTable;
use App\DataTables\LivestockStandardDataTable;
use App\Config\LivestockDepletionConfig;
use Carbon\Carbon;

use App\Models\FeedUsage;
use App\Models\FeedUsageDetail;
use App\Models\Livestock;
use App\Models\LivestockDepletion;
use App\Models\Recording;
use App\Models\SupplyUsageDetail;

class LivestockController extends Controller
{
    public function index(LivestockDataTable $dataTable)
    {
        addVendors(['datatables']);

        // Handle case when DataTable is not available (for batch routes)
        if (!$dataTable) {
            return view('pages.masterdata.livestock.list');
        }

        return $dataTable->render('pages.masterdata.livestock.list');
    }

    public function create()
    {
        // Return view untuk create batch/livestock
        return view('pages.masterdata.livestock.create');
    }

    public function store(Request $request)
    {
        // Handle store untuk batch/livestock
        return redirect()->back()->with('success', 'Data berhasil disimpan');
    }

    public function show($id)
    {
        // Handle show untuk batch/livestock specific
        return $this->showLivestockDetails($id);
    }

    public function edit($id)
    {
        // Handle edit untuk batch/livestock
        return view('pages.masterdata.livestock.edit', compact('id'));
    }

    public function update(Request $request, $id)
    {
        // Handle update untuk batch/livestock
        return redirect()->back()->with('success', 'Data berhasil diupdate');
    }

    public function destroy($id)
    {
        // Handle delete untuk batch/livestock
        return redirect()->back()->with('success', 'Data berhasil dihapus');
    }

    public function mutasi(LivestockMutationDataTable $dataTable)
    {
        //
        addVendors(['datatables']);

        return $dataTable->render('pages.masterdata.livestock.mutasi');
    }

    public function livestockStrainIndex(LivestockStrainDataTable $dataTable)
    {
        addVendors(['datatables']);

        return $dataTable->render('pages.masterdata.livestock-strain.list');
    }

    public function livestockStandardIndex(LivestockStandardDataTable $dataTable)
    {
        addVendors(['datatables']);

        return $dataTable->render('pages.masterdata.livestock-standard.list');
    }
    public function purchaseIndex(LivestockPurchaseDataTable $dataTable)
    {
        addVendors(['datatables']);

        return $dataTable->render('pages.livestock.purchase.index');
    }

    public function mutationIndex(LivestockMutationDataTable $dataTable)
    {
        // return view('pages.livestock.mutation.index');
        addVendors(['datatables']);

        return $dataTable->render('pages.livestock.mutation.index');
    }

    public function supplyRecordingIndex(OVKRecordDataTable $dataTable)
    {
        // return view('pages.ovk-records.index');
        addVendors(['datatables']);

        return $dataTable->render('pages.ovk-records.index');
    }

    public function showLivestockDetails($id)
    {
        \Log::info('LivestockController::showLivestockDetails - Starting livestock details retrieval', ['livestock_id' => $id]);

        $livestock = Livestock::findOrFail($id);
        $startDate = Carbon::parse($livestock->start_date);
        $today = Carbon::today();

        $endDate = $livestock->status === 'active' || $livestock->status === 'locked'
            ? max(
                Carbon::parse(
                    Recording::where('livestock_id', $livestock->id)->latest('tanggal')->value('tanggal') ?? now()
                )->addDay(),
                $startDate->copy()->addDay()
            )
            : Carbon::parse($livestock->end_date);

        $records = collect();
        $currentDate = $startDate->copy();
        $stockAwal = $livestock->populasi_awal;
        $totalPakanUsage = 0;
        $totalDeplesi = 0;
        $totalOvkUsage = 0;

        // Standar target FCR dan bobot
        $standarData = $livestock->data[0]['standar_bobot'] ?? [];

        \Log::info('LivestockController::showLivestockDetails - Processing daily records', [
            'livestock_id' => $livestock->id,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'populasi_awal' => $stockAwal
        ]);

        while ($currentDate <= $today) {
            $dateStr = $currentDate->format('Y-m-d');
            $umur = $startDate->diffInDays($currentDate);

            $existingRecord = Recording::where('livestock_id', $livestock->id)
                ->whereDate('tanggal', $dateStr)
                ->first();

            // Get deplesi using config normalization for backward compatibility
            $deplesi = LivestockDepletion::where('livestock_id', $livestock->id)
                ->whereDate('tanggal', $dateStr)
                ->get();

            // Normalize depletion types using config
            $mortalityTypes = [
                LivestockDepletionConfig::LEGACY_TYPE_MATI,
                LivestockDepletionConfig::TYPE_MORTALITY
            ];
            $cullingTypes = [
                LivestockDepletionConfig::LEGACY_TYPE_AFKIR,
                LivestockDepletionConfig::TYPE_CULLING
            ];

            $mati = $deplesi->whereIn('jenis', $mortalityTypes)->sum('jumlah');
            $afkir = $deplesi->whereIn('jenis', $cullingTypes)->sum('jumlah');
            $deplesiHarian = $mati + $afkir;
            $totalDeplesi += $deplesiHarian;

            \Log::debug('LivestockController::showLivestockDetails - Daily depletion calculation', [
                'date' => $dateStr,
                'mortality_types' => $mortalityTypes,
                'culling_types' => $cullingTypes,
                'mati' => $mati,
                'afkir' => $afkir,
                'deplesi_harian' => $deplesiHarian
            ]);

            // Feed Usage
            $usage = FeedUsageDetail::whereHas('feedUsage', function ($q) use ($livestock, $dateStr) {
                $q->where('livestock_id', $livestock->id)
                    ->whereDate('usage_date', $dateStr);
            })->get();

            $pakanHarian = $usage->sum('quantity_taken');
            $pakanJenis = $usage->pluck('feed.name')->unique()->implode(', ') ?: '-';
            $totalPakanUsage += $pakanHarian;

            // OVK Usage
            $ovkUsage = SupplyUsageDetail::whereHas('supplyUsage', function ($q) use ($livestock, $dateStr) {
                $q->where('livestock_id', $livestock->id)
                    ->whereDate('usage_date', $dateStr);
            })->sum('quantity_taken');

            $totalOvkUsage += $ovkUsage;

            // Target standar
            $standarBobot = $standarData['data'][$umur] ?? null;

            $record = [
                'recording_id' => $existingRecord->id ?? null,
                'tanggal' => $dateStr,
                'umur' => $umur,
                'fcr_target' => $standarBobot['fcr']['target'] ?? 0,
                'bobot_target' => $standarBobot['bobot']['target'] ?? 0,
                'stock_awal' => $stockAwal,
                'mati' => $mati,
                'afkir' => $afkir,
                'total_deplesi' => $deplesiHarian,
                'deplesi_percentage' => $stockAwal > 0 ? round(($totalDeplesi / $stockAwal) * 100, 2) : 0,
                'stock_akhir' => $stockAwal - $deplesiHarian,
                'pakan_jenis' => $pakanJenis,
                'pakan_harian' => $pakanHarian,
                'pakan_total' => $totalPakanUsage,
                'ovk_harian' => $ovkUsage,
                'total_ovk' => $totalOvkUsage,
                'fcr_actual' => $stockAwal - $totalDeplesi > 0 ? round($totalPakanUsage / ($stockAwal - $totalDeplesi), 2) : 0,
            ];

            $records->push($record);

            $stockAwal = $record['stock_akhir'];
            $currentDate->addDay();
        }

        $result = [
            'result' => $records->sortByDesc('tanggal')->values(),
            'livestock_id' => $livestock->id,
            'nama' => $livestock->name,
            'populasi_awal' => $livestock->populasi_awal,
            'populasi_akhir' => $stockAwal,
            'total_deplesi' => $totalDeplesi,
            'deplesi_percentage' => $livestock->populasi_awal > 0 ? round(($totalDeplesi / $livestock->populasi_awal) * 100, 2) : 0,
            'total_pakan' => $totalPakanUsage,
            'total_ovk' => $totalOvkUsage,
            'fcr_actual' => $stockAwal > 0 ? round($totalPakanUsage / $stockAwal, 2) : 0,
        ];

        \Log::info('LivestockController::showLivestockDetails - Successfully retrieved livestock details', [
            'livestock_id' => $livestock->id,
            'total_records' => $records->count(),
            'total_deplesi' => $totalDeplesi,
            'total_pakan' => $totalPakanUsage,
            'total_ovk' => $totalOvkUsage
        ]);

        return response()->json($result);
    }
}
