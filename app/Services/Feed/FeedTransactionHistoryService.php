<?php

namespace App\Services\Feed;

use App\Models\FeedTransactionHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * FeedTransactionHistoryService
 *
 * Service terpusat untuk membangun histori stok pakan/obat secara kronologis dan detail.
 * Mendukung semua tipe transaksi (beli, pakai, mutasi, retur, koreksi, dsb), saldo awal otomatis,
 * filter fleksibel, audit trail, dan extensibility untuk kebutuhan masa depan.
 *
 * Tips extensibility:
 * - Tambahkan tipe transaksi baru cukup di field transaction_type dan builder.
 * - Tambahkan field baru (misal: metadata, dokumen) di kolom data (JSON).
 * - Siap untuk multi-company/tenant dengan menambah filter company_id.
 */
class FeedTransactionHistoryService
{
    /**
     * Ambil histori transaksi feed untuk livestock/feed/periode tertentu (atau global jika filter null).
     * WARNING: Query tanpa filter bisa sangat besar, gunakan dengan hati-hati untuk data produksi!
     *
     * @param string|null $livestockId
     * @param string|null $feedId
     * @param string|null $startDate
     * @param string|null $endDate
     * @param array $extraFilter (opsional: company_id, unit_id, dsb)
     * @return Collection<FeedTransactionHistory>
     */
    public function getHistory($livestockId = null, $feedId = null, $startDate = null, $endDate = null, $extraFilter = []): Collection
    {
        $query = FeedTransactionHistory::query();
        if ($livestockId) {
            $query->where('livestock_id', $livestockId);
        }
        if ($feedId) {
            $query->where('feed_id', $feedId);
        }
        if ($startDate) {
            $query->whereDate('date', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('date', '<=', $endDate);
        }
        foreach ($extraFilter as $key => $value) {
            $query->where($key, $value);
        }
        $query->with(['feed', 'livestock', 'unit', 'supplier']);
        $histories = $query->orderBy('date')->orderBy('id')->get();
        // Hitung stok_awal/stok_akhir secara berurutan
        // Jika global, saldo awal = 0 per group
        $saldo = $this->getInitialStock($livestockId, $feedId, $startDate, $extraFilter);
        foreach ($histories as $i => $trx) {
            $trx->initial_stock = $saldo;
            $trx->quantity_available = $saldo + $trx->quantity_in - $trx->quantity_out;
            $saldo = $trx->quantity_available;
        }
        return $histories;
    }

    /**
     * Hitung saldo awal sebelum periode filter (global jika filter null).
     *
     * @param string|null $livestockId
     * @param string|null $feedId
     * @param string|null $startDate
     * @param array $extraFilter
     * @return float
     */
    public function getInitialStock($livestockId = null, $feedId = null, $startDate = null, $extraFilter = []): float
    {
        if (!$startDate) return 0;
        $query = FeedTransactionHistory::query();
        if ($livestockId) {
            $query->where('livestock_id', $livestockId);
        }
        if ($feedId) {
            $query->where('feed_id', $feedId);
        }
        $query->whereDate('date', '<', $startDate);
        foreach ($extraFilter as $key => $value) {
            $query->where($key, $value);
        }
        $in = $query->sum('quantity_in');
        $out = $query->sum('quantity_out');
        return $in - $out;
    }

    /**
     * Tambah histori transaksi baru (bisa dipakai untuk import/manual entry).
     *
     * @param array $data
     * @return FeedTransactionHistory
     */
    public function create(array $data): FeedTransactionHistory
    {
        // Validasi dan normalisasi data bisa ditambah di sini
        return FeedTransactionHistory::create($data);
    }

    /**
     * Update histori transaksi (misal: koreksi, audit).
     *
     * @param string $id
     * @param array $data
     * @return FeedTransactionHistory|null
     */
    public function update(string $id, array $data): ?FeedTransactionHistory
    {
        $trx = FeedTransactionHistory::find($id);
        if ($trx) {
            $trx->update($data);
        }
        return $trx;
    }

    /**
     * Soft delete histori transaksi.
     *
     * @param string $id
     * @return bool
     */
    public function delete(string $id): bool
    {
        $trx = FeedTransactionHistory::find($id);
        if ($trx) {
            return $trx->delete();
        }
        return false;
    }

    /**
     * Generate/migrasi histori dari data operasional (FeedPurchase, FeedUsage, FeedMutation, dsb)
     * ke feed_transaction_histories. Bisa dipakai untuk migrasi awal atau sinkronisasi.
     *
     * @param string|null $livestockId
     * @param string|null $feedId
     * @param string|null $startDate
     * @param string|null $endDate
     * @param array $extraFilter
     * @param bool $dryRun
     * @return array histori hasil (array of FeedTransactionHistory-like array)
     */
    public function generateFromLegacy($livestockId = null, $feedId = null, $startDate = null, $endDate = null, $extraFilter = [], $dryRun = false): array
    {
        $results = [];
        // 1. Query FeedPurchaseItem (Pembelian)
        $purchaseQuery = \App\Models\FeedPurchaseItem::query();
        if ($livestockId) {
            $purchaseQuery->whereHas('feedPurchase', fn($q) => $q->where('livestock_id', $livestockId));
        }
        if ($feedId) {
            $purchaseQuery->where('feed_id', $feedId);
        }
        if ($startDate) {
            $purchaseQuery->whereHas('feedPurchase', fn($q) => $q->whereDate('date', '>=', $startDate));
        }
        if ($endDate) {
            $purchaseQuery->whereHas('feedPurchase', fn($q) => $q->whereDate('date', '<=', $endDate));
        }
        foreach ($extraFilter as $key => $value) {
            if ($key === 'company_id') {
                $purchaseQuery->whereHas('feedPurchase', fn($q) => $q->where('company_id', $value));
            }
        }
        $purchaseQuery->with(['feedPurchase', 'feed', 'unit', 'feedPurchase.supplier']);
        $purchases = $purchaseQuery->get();
        foreach ($purchases as $item) {
            $feedName = $item->feed->name ?? ($item->feed->nama ?? '-');
            $qty = $item->converted_quantity;
            $unit = $item->unit->name ?? '';
            $supplier = $item->feedPurchase->supplier->name ?? '-';
            $date = $item->feedPurchase->date ?? '-';
            $desc = "Pembelian pakan $feedName $qty $unit dari supplier $supplier pada tanggal $date";
            $trx = [
                'feed_id' => $item->feed_id,
                'livestock_id' => $item->feedPurchase->livestock_id ?? null,
                'unit_id' => $item->unit_id,
                'supplier_id' => $item->feedPurchase->supplier_id ?? null,
                'batch_number' => $item->feedPurchase->batch_number ?? null,
                'expired_date' => null,
                'date' => $item->feedPurchase->date,
                'transaction_type' => 'pembelian',
                'transaction_number' => $item->feedPurchase->invoice_number ?? null,
                'quantity_in' => $item->converted_quantity,
                'quantity_out' => 0,
                'initial_stock' => 0, // akan dihitung di bawah
                'quantity_available' => 0, // akan dihitung di bawah
                'price' => $item->price_per_unit,
                'description' => $desc,
                'data' => [],
                'created_by' => $item->feedPurchase->created_by ?? null,
                'updated_by' => $item->feedPurchase->updated_by ?? null,
            ];
            $results[] = $trx;
        }
        // 2. Query FeedUsageDetail (Pemakaian)
        $usageQuery = \App\Models\FeedUsageDetail::query();
        if ($feedId) {
            $usageQuery->where('feed_id', $feedId);
        }
        if ($startDate) {
            $usageQuery->whereHas('feedUsage', fn($q) => $q->whereDate('usage_date', '>=', $startDate));
        }
        if ($endDate) {
            $usageQuery->whereHas('feedUsage', fn($q) => $q->whereDate('usage_date', '<=', $endDate));
        }
        if ($livestockId) {
            $usageQuery->whereHas('feedUsage', fn($q) => $q->where('livestock_id', $livestockId));
        }
        $usageQuery->with(['feedUsage', 'feedStock', 'feed']);
        $usages = $usageQuery->get();
        foreach ($usages as $usage) {
            $feedName = $usage->feed->name ?? ($usage->feed->nama ?? '-');
            $qty = $usage->quantity_taken;
            $unit = $usage->feedStock->unit->name ?? '';
            $livestockCode = $usage->feedUsage->livestock->code
                ?? $usage->feedUsage->livestock->kode
                ?? $usage->feedUsage->livestock->name
                ?? '-';
            $livestockName = $usage->feedUsage->livestock->name ?? '-';
            $desc = "Pemakaian pakan $feedName sejumlah $qty $unit untuk $livestockCode ($livestockName)";
            $trx = [
                'feed_id' => $usage->feed_id,
                'livestock_id' => $usage->feedUsage->livestock_id ?? null,
                'unit_id' => $usage->feedStock->unit_id ?? null,
                'supplier_id' => null,
                'batch_number' => null,
                'expired_date' => null,
                'date' => $usage->feedUsage->usage_date,
                'transaction_type' => 'pemakaian',
                'transaction_number' => $usage->feedUsage->number_full ?? null,
                'quantity_in' => 0,
                'quantity_out' => $usage->quantity_taken,
                'initial_stock' => 0,
                'quantity_available' => 0,
                'price' => null,
                'description' => $desc,
                'data' => [],
                'created_by' => $usage->feedUsage->created_by ?? null,
                'updated_by' => $usage->feedUsage->updated_by ?? null,
                'feed_stock_id' => $usage->feed_stock_id ?? null, // Tambahkan feed_stock_id
            ];
            $results[] = $trx;
        }
        // 3. Query FeedMutationItem (Mutasi)
        $mutationQuery = \App\Models\FeedMutationItem::query();
        if ($feedId) {
            $mutationQuery->where('feed_id', $feedId);
        }
        if ($startDate) {
            $mutationQuery->whereHas('mutation', fn($q) => $q->whereDate('date', '>=', $startDate));
        }
        if ($endDate) {
            $mutationQuery->whereHas('mutation', fn($q) => $q->whereDate('date', '<=', $endDate));
        }
        $mutationQuery->with(['mutation', 'feedStock']);
        $mutations = $mutationQuery->get();
        foreach ($mutations as $item) {
            $trx = [
                'feed_id' => $item->feed_id,
                'livestock_id' => $item->feedStock->livestock_id ?? null,
                'unit_id' => $item->feedStock->unit_id ?? null,
                'supplier_id' => null,
                'batch_number' => null,
                'expired_date' => null,
                'date' => $item->mutation->date,
                'transaction_type' => 'mutasi',
                'transaction_number' => $item->mutation->number_full ?? null,
                'quantity_in' => 0,
                'quantity_out' => $item->quantity,
                'initial_stock' => 0,
                'quantity_available' => 0,
                'price' => null,
                'description' => 'Mutasi',
                'data' => [],
                'created_by' => $item->mutation->created_by ?? null,
                'updated_by' => $item->mutation->updated_by ?? null,
            ];
            $results[] = $trx;
        }
        // 4. Gabungkan, urutkan, dan hitung stok_awal/akhir
        $results = collect($results)
            ->sortBy([['date', 'asc']])
            ->groupBy('date');

        $saldo = 0;
        $finalResults = [];
        foreach ($results as $date => $trxList) {
            // 1. Proses semua pembelian di tanggal ini
            foreach ($trxList->where('transaction_type', 'pembelian') as $trx) {
                $trx['initial_stock'] = $saldo;
                $trx['quantity_available'] = $saldo + $trx['quantity_in'];
                $saldo = $trx['quantity_available'];
                if (!$dryRun) \App\Models\FeedTransactionHistory::create($trx);
                $finalResults[] = $trx;
            }
            // 2. Proses semua pemakaian di tanggal ini
            foreach ($trxList->where('transaction_type', 'pemakaian') as $trx) {
                $trx['initial_stock'] = $saldo;
                $trx['quantity_available'] = $saldo - $trx['quantity_out'];
                $saldo = $trx['quantity_available'];
                if (!$dryRun) \App\Models\FeedTransactionHistory::create($trx);
                $finalResults[] = $trx;
            }
            // 3. (opsional) proses mutasi/dll jika ada
        }
        return $finalResults;
    }
}
