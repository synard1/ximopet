<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Recording extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'tanggal',
        'livestock_id',
        'age',
        'stock_awal',
        'stock_akhir',
        'total_deplesi',
        'total_penjualan',
        'berat_semalam',
        'berat_hari_ini',
        'kenaikan_berat',
        'pakan_jenis',
        'pakan_harian',
        'pakan_total',
        'payload',
        'data_operational',
        'data_audit',
        'data',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'pakan_harian' => 'decimal:2',
        'pakan_total' => 'decimal:2',
        'payload' => 'array',
        'data_operational' => 'array',
        'data_audit' => 'array',
        'data' => 'array',
    ];

    public function details()
    {
        return $this->hasMany(RecordingItem::class);
    }

    public function deplesiData()
    {
        return $this->hasMany(LivestockDepletion::class);
    }

    public function ternak(): BelongsTo
    {
        return $this->belongsTo(Ternak::class, 'ternak_id', 'id');
    }

    public function livestock(): BelongsTo
    {
        return $this->belongsTo(Livestock::class, 'livestock_id');
    }

    public function livestockCost()
    {
        return $this->hasOne(LivestockCost::class);
    }

    public function feedUsages()
    {
        return $this->hasMany(FeedUsage::class);
    }
}
