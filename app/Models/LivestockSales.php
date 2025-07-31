<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

/**
 * Model untuk menangani data penjualan ternak
 * 
 * @property string $id UUID primary key
 * @property string $company_id ID perusahaan
 * @property string $invoice_number Nomor invoice penjualan
 * @property string $do_number Nomor delivery order/surat jalan
 * @property string $farm_id ID farm/kebun
 * @property string $coop_id ID kandang
 * @property string $livestock_id ID jenis ternak
 * @property string $livestock_batch_id ID batch ternak
 * @property \Carbon\Carbon $date Tanggal penjualan
 * @property \Carbon\Carbon $delivery_date Tanggal pengiriman
 * @property string $delivery_address Alamat pengiriman
 * @property string $customer_name Nama customer
 * @property string $customer_id ID customer/partner
 * @property string $expedition_id ID ekspedisi pengiriman
 * @property decimal $expedition_fee Biaya ekspedisi
 * @property int $total_quantity Total quantity terjual
 * @property decimal $total_weight Total berat terjual
 * @property decimal $total_amount Total nilai penjualan
 * @property string $payment_method Metode pembayaran
 * @property string $payment_status Status pembayaran
 * @property \Carbon\Carbon $due_date Tanggal jatuh tempo
 * @property string $status Status transaksi
 * @property string $notes Catatan
 * @property array $data Data tambahan dalam format JSON
 * @property string $approved_by ID user yang approve
 * @property \Carbon\Carbon $approved_at Tanggal approval
 * @property string $created_by ID user yang membuat
 * @property string $updated_by ID user yang update
 */
class LivestockSales extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    /**
     * Status constants untuk transaksi penjualan
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Payment status constants
     */
    const PAYMENT_STATUS_UNPAID = 'unpaid';
    const PAYMENT_STATUS_PARTIAL = 'partial';
    const PAYMENT_STATUS_PAID = 'paid';
    const PAYMENT_STATUS_OVERDUE = 'overdue';

    /**
     * Payment method constants
     */
    const PAYMENT_METHOD_CASH = 'cash';
    const PAYMENT_METHOD_TRANSFER = 'transfer';
    const PAYMENT_METHOD_CREDIT = 'credit';

    /**
     * Status labels untuk display
     */
    const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PENDING => 'Pending',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_DELIVERED => 'Delivered',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    const PAYMENT_STATUS_LABELS = [
        self::PAYMENT_STATUS_UNPAID => 'Unpaid',
        self::PAYMENT_STATUS_PARTIAL => 'Partial',
        self::PAYMENT_STATUS_PAID => 'Paid',
        self::PAYMENT_STATUS_OVERDUE => 'Overdue',
    ];

    const PAYMENT_METHOD_LABELS = [
        self::PAYMENT_METHOD_CASH => 'Cash',
        self::PAYMENT_METHOD_TRANSFER => 'Transfer',
        self::PAYMENT_METHOD_CREDIT => 'Credit',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'company_id',
        'invoice_number',
        'do_number',
        'farm_id',
        'coop_id',
        'livestock_id',
        'livestock_batch_id',
        'date',
        'delivery_date',
        'delivery_address',
        'customer_name',
        'customer_id',
        'expedition_id',
        'expedition_fee',
        'total_quantity',
        'total_weight',
        'total_amount',
        'payment_method',
        'payment_status',
        'due_date',
        'status',
        'notes',
        'data',
        'approved_by',
        'approved_at',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'datetime',
        'delivery_date' => 'datetime',
        'due_date' => 'datetime',
        'approved_at' => 'datetime',
        'expedition_fee' => 'decimal:2',
        'total_quantity' => 'integer',
        'total_weight' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'data' => 'array',
    ];

    /**
     * Boot method untuk logging
     */
    protected static function boot()
    {
        parent::boot();

        // Log saat model dibuat
        static::created(function ($model) {
            Log::info('LivestockSales created', [
                'id' => $model->id,
                'invoice_number' => $model->invoice_number,
                'customer_name' => $model->customer_name,
                'total_amount' => $model->total_amount,
                'created_by' => $model->created_by
            ]);
        });

        // Log saat model diupdate
        static::updated(function ($model) {
            Log::info('LivestockSales updated', [
                'id' => $model->id,
                'invoice_number' => $model->invoice_number,
                'status' => $model->status,
                'payment_status' => $model->payment_status,
                'updated_by' => $model->updated_by
            ]);
        });
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    /**
     * Get payment status label
     */
    public function getPaymentStatusLabelAttribute(): string
    {
        return self::PAYMENT_STATUS_LABELS[$this->payment_status] ?? $this->payment_status;
    }

    /**
     * Get payment method label
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return self::PAYMENT_METHOD_LABELS[$this->payment_method] ?? $this->payment_method;
    }

    /**
     * Check if transaction is approved
     */
    public function isApproved(): bool
    {
        return !empty($this->approved_by) && !empty($this->approved_at);
    }

    /**
     * Check if payment is overdue
     */
    public function isPaymentOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->payment_status !== self::PAYMENT_STATUS_PAID;
    }

    /**
     * Calculate remaining amount (for partial payments)
     */
    public function getRemainingAmount(): float
    {
        // TODO: Implement payment tracking logic
        return (float) $this->total_amount;
    }

    /**
     * Relationship: Belongs to company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * Relationship: Belongs to farm
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class, 'farm_id');
    }

    /**
     * Relationship: Belongs to coop
     */
    public function coop(): BelongsTo
    {
        return $this->belongsTo(Coop::class, 'coop_id');
    }

    /**
     * Relationship: Belongs to livestock
     */
    public function livestock(): BelongsTo
    {
        return $this->belongsTo(Livestock::class, 'livestock_id');
    }

    /**
     * Relationship: Belongs to livestock batch
     */
    public function livestockBatch(): BelongsTo
    {
        return $this->belongsTo(LivestockBatch::class, 'livestock_batch_id');
    }

    /**
     * Relationship: Has many livestock sales items
     */
    public function livestockSalesItems(): HasMany
    {
        return $this->hasMany(LivestockSalesItem::class, 'livestock_sales_id');
    }

    /**
     * Relationship: Belongs to customer/partner
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'customer_id');
    }

    /**
     * Relationship: Belongs to expedition
     */
    public function expedition(): BelongsTo
    {
        return $this->belongsTo(Expedition::class, 'expedition_id');
    }

    /**
     * Relationship: Approved by user
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Relationship: Created by user
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship: Updated by user
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter by payment status
     */
    public function scopeByPaymentStatus($query, $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope: Filter by customer
     */
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope: Overdue payments
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->where('payment_status', '!=', self::PAYMENT_STATUS_PAID);
    }
}
