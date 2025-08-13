<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Partner;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Traits\HasJsonHistory;

class ExpeditionTransaction extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes, HasJsonHistory;

    const STATUS_PENDING = 'pending';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    const TRANSACTION_TYPE_SALES = 'sales';
    const TRANSACTION_TYPE_PURCHASE = 'purchase';
    const TRANSACTION_TYPE_FEED_PURCHASE = 'feed_purchase';
    const TRANSACTION_TYPE_SUPPLY_PURCHASE = 'supply_purchase';

    protected $fillable = [
        'company_id',
        'transaction_type',
        'transaction_id',
        'invoice_number',
        'expedition_id',
        'shipping_date',
        'destination_address',
        'destination_zone',
        'total_weight',
        'expedition_cost',
        'currency',
        'notes',
        'cost_details',
        'status',
        'delivered_at',
        'tracking_number',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'shipping_date' => 'date',
        'delivered_at' => 'datetime',
        'total_weight' => 'decimal:2',
        'expedition_cost' => 'decimal:2',
        'cost_details' => 'array',
    ];

    /**
     * Relationship to Expedition (Partner typed as Expedition)
     */
    public function expedition(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'expedition_id')->where('type', 'Expedition');
    }

    /**
     * Relationship to Company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Dynamic relationship to related transaction
     */
    public function relatedTransaction()
    {
        switch ($this->transaction_type) {
            case self::TRANSACTION_TYPE_SALES:
                return $this->belongsTo(SalesTransaction::class, 'transaction_id');
            case self::TRANSACTION_TYPE_PURCHASE:
                return $this->belongsTo(LivestockPurchase::class, 'transaction_id');
            case self::TRANSACTION_TYPE_FEED_PURCHASE:
                return $this->belongsTo(FeedPurchase::class, 'transaction_id');
            case self::TRANSACTION_TYPE_SUPPLY_PURCHASE:
                return $this->belongsTo(SupplyPurchaseBatch::class, 'transaction_id');
            default:
                return null;
        }
    }

    /**
     * Create expedition transaction from existing transaction (simplified)
     */
    public static function createFromTransaction(
        $transaction,
        string $transactionType,
        array $expeditionData
    ): self {
        $user = Auth::user();

        return static::create([
            'company_id' => $transaction->company_id ?? ($user ? $user->company_id : null),
            'transaction_type' => $transactionType,
            'transaction_id' => $transaction->id,
            'invoice_number' => $transaction->invoice_number ?? null,
            'expedition_id' => $expeditionData['expedition_id'],
            'shipping_date' => $expeditionData['shipping_date'] ?? Carbon::now()->toDateString(),
            'destination_address' => $expeditionData['destination_address'] ?? '',
            'destination_zone' => $expeditionData['destination_zone'] ?? '',
            'total_weight' => $expeditionData['total_weight'],
            'expedition_cost' => $expeditionData['expedition_cost'], // Actual cost paid
            'currency' => $expeditionData['currency'] ?? 'IDR',
            'notes' => $expeditionData['notes'] ?? null,
            'cost_details' => $expeditionData['cost_details'] ?? null, // Optional breakdown
            'status' => $expeditionData['status'] ?? self::STATUS_PENDING,
            'tracking_number' => $expeditionData['tracking_number'] ?? null,
            'created_by' => $user ? $user->id : null,
        ]);
    }

    /**
     * Calculate cost per kg
     */
    public function getCostPerKgAttribute(): float
    {
        return $this->total_weight > 0 ? $this->expedition_cost / $this->total_weight : 0;
    }

    /**
     * Check if transaction is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    /**
     * Mark as delivered
     */
    public function markAsDelivered(?Carbon $deliveredAt = null): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => $deliveredAt ?? Carbon::now(),
        ]);
    }

    /**
     * Scope for specific transaction type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('shipping_date', [$startDate, $endDate]);
    }

    /**
     * Scope for expedition
     */
    public function scopeForExpedition($query, string $expeditionId)
    {
        return $query->where('expedition_id', $expeditionId);
    }

    /**
     * Scope for zone
     */
    public function scopeInZone($query, string $zone)
    {
        return $query->where('destination_zone', $zone);
    }
}
