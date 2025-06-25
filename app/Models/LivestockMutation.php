<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Schema;

/**
 * LivestockMutation Model
 * 
 * Handles livestock mutation records for tracking movements between livestock units
 * 
 * @property string $id
 * @property string $company_id
 * @property string $source_livestock_id|from_livestock_id
 * @property string|null $destination_livestock_id|to_livestock_id
 * @property \Carbon\Carbon $tanggal
 * @property int $jumlah (calculated from items)
 * @property string $jenis
 * @property string $direction
 * @property string $keterangan
 * @property array|null $data
 * @property array|null $metadata
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class LivestockMutation extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'livestock_mutations';

    protected $fillable = [
        'company_id',
        'source_livestock_id',
        'from_livestock_id', // Legacy support
        'destination_livestock_id',
        'to_livestock_id', // Legacy support
        'tanggal',
        'jumlah',
        'jenis',
        'direction',
        'keterangan',
        'data',
        'metadata',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'jumlah' => 'integer',
        'data' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected $dates = [
        'tanggal',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * Mutation types
     */
    const MUTATION_TYPES = [
        'internal' => 'Internal Transfer',
        'external' => 'External Transfer',
        'farm_transfer' => 'Farm Transfer',
        'location_transfer' => 'Location Transfer',
        'emergency_transfer' => 'Emergency Transfer'
    ];

    /**
     * Mutation directions
     */
    const MUTATION_DIRECTIONS = [
        'in' => 'Incoming',
        'out' => 'Outgoing'
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-set company_id from authenticated user
        static::creating(function ($model) {
            if (auth()->check() && !$model->company_id) {
                $model->company_id = auth()->user()->company_id;
            }
        });

        // Auto-calculate total quantity from items
        static::saved(function ($model) {
            $model->calculateTotalQuantity();
        });
    }

    /**
     * Get mutation items
     */
    public function items(): HasMany
    {
        return $this->hasMany(LivestockMutationItem::class, 'livestock_mutation_id');
    }

    /**
     * Get the source livestock (with legacy support)
     */
    public function sourceLivestock(): BelongsTo
    {
        // Check which column exists and use appropriate one
        $sourceColumn = $this->getSourceLivestockColumn();
        return $this->belongsTo(Livestock::class, $sourceColumn);
    }

    /**
     * Get the destination livestock (with legacy support)
     */
    public function destinationLivestock(): BelongsTo
    {
        // Check which column exists and use appropriate one
        $destinationColumn = $this->getDestinationLivestockColumn();
        return $this->belongsTo(Livestock::class, $destinationColumn);
    }

    /**
     * Get the destination coop
     */
    public function destinationCoop(): BelongsTo
    {
        return $this->belongsTo(Coop::class, 'destination_coop_id');
    }

    /**
     * Get the company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the creator user
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the updater user
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get source livestock column name (legacy support)
     */
    private function getSourceLivestockColumn(): string
    {
        return $this->hasColumn('source_livestock_id') ? 'source_livestock_id' : 'from_livestock_id';
    }

    /**
     * Get destination livestock column name (legacy support)
     */
    private function getDestinationLivestockColumn(): string
    {
        return $this->hasColumn('destination_livestock_id') ? 'destination_livestock_id' : 'to_livestock_id';
    }

    /**
     * Check if column exists in table
     */
    private function hasColumn(string $column): bool
    {
        return Schema::hasColumn($this->getTable(), $column);
    }

    /**
     * Get source livestock ID (legacy support)
     */
    public function getSourceLivestockIdAttribute(): ?string
    {
        return $this->attributes['source_livestock_id'] ?? $this->attributes['from_livestock_id'] ?? null;
    }

    /**
     * Get destination livestock ID (legacy support)
     */
    public function getDestinationLivestockIdAttribute(): ?string
    {
        return $this->attributes['destination_livestock_id'] ?? $this->attributes['to_livestock_id'] ?? null;
    }

    /**
     * Calculate total quantity from items
     */
    public function calculateTotalQuantity(): void
    {
        $totalQuantity = $this->items()->sum('quantity');

        // Update without triggering events to avoid infinite loop
        $this->updateQuietly(['jumlah' => $totalQuantity]);
    }

    /**
     * Scope for outgoing mutations
     */
    public function scopeOutgoing($query)
    {
        return $query->where('direction', 'out');
    }

    /**
     * Scope for incoming mutations
     */
    public function scopeIncoming($query)
    {
        return $query->where('direction', 'in');
    }

    /**
     * Scope for specific mutation type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('jenis', $type);
    }

    /**
     * Scope for specific livestock (source or destination)
     */
    public function scopeForLivestock($query, string $livestockId)
    {
        return $query->where(function ($q) use ($livestockId) {
            $sourceColumn = $this->getSourceLivestockColumn();
            $destinationColumn = $this->getDestinationLivestockColumn();

            $q->where($sourceColumn, $livestockId)
                ->orWhere($destinationColumn, $livestockId);
        });
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal', [$startDate, $endDate]);
    }

    /**
     * Get formatted mutation type
     */
    public function getFormattedTypeAttribute(): string
    {
        return self::MUTATION_TYPES[$this->jenis] ?? $this->jenis;
    }

    /**
     * Get formatted direction
     */
    public function getFormattedDirectionAttribute(): string
    {
        return self::MUTATION_DIRECTIONS[$this->direction] ?? $this->direction;
    }

    /**
     * Get batch information from data
     */
    public function getBatchInfoAttribute(): ?array
    {
        $data = $this->data ?? [];

        if (isset($data['batch_id'])) {
            return [
                'batch_id' => $data['batch_id'],
                'batch_name' => $data['batch_name'] ?? null,
                'batch_start_date' => $data['batch_start_date'] ?? null,
            ];
        }

        return null;
    }

    /**
     * Get mutation method from data
     */
    public function getMutationMethodAttribute(): string
    {
        $data = $this->data ?? [];
        return $data['mutation_method'] ?? 'unknown';
    }

    /**
     * Check if mutation is manual
     */
    public function getIsManualAttribute(): bool
    {
        return $this->mutation_method === 'manual';
    }

    /**
     * Check if mutation is edit replacement
     */
    public function getIsEditReplacementAttribute(): bool
    {
        $data = $this->data ?? [];
        return $data['is_edit_replacement'] ?? false;
    }

    /**
     * Get processing metadata
     */
    public function getProcessingInfoAttribute(): array
    {
        $metadata = $this->metadata ?? [];

        return [
            'processed_at' => $metadata['processed_at'] ?? null,
            'processed_by' => $metadata['processed_by'] ?? null,
            'processing_method' => $metadata['processing_method'] ?? null,
            'edit_mode' => $metadata['edit_mode'] ?? false,
        ];
    }

    /**
     * Get mutation summary for display
     */
    public function getSummaryAttribute(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->tanggal->format('Y-m-d'),
            'quantity' => $this->jumlah,
            'type' => $this->formatted_type,
            'direction' => $this->formatted_direction,
            'method' => $this->mutation_method,
            'source_livestock' => $this->sourceLivestock->name ?? 'Unknown',
            'destination_livestock' => $this->destinationLivestock->name ?? 'N/A',
            'batch_info' => $this->batch_info,
            'items_count' => $this->items()->count(),
        ];
    }

    /**
     * Create a mutation record with validation and items
     */
    public static function createMutation(array $data): self
    {
        // Validate required fields
        $required = ['tanggal', 'jenis'];

        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new \Exception("Required field missing: {$field}");
            }
        }

        // Extract items data
        $items = $data['items'] ?? [];
        unset($data['items']);

        // Create mutation record
        $mutation = self::create($data);

        // Create items if provided
        if (!empty($items)) {
            foreach ($items as $itemData) {
                $mutation->items()->create($itemData);
            }
        }

        return $mutation;
    }

    /**
     * Get mutations for livestock with summary
     */
    public static function getLivestockMutationSummary(string $livestockId): array
    {
        $sourceColumn = (new self())->getSourceLivestockColumn();
        $destinationColumn = (new self())->getDestinationLivestockColumn();

        $outgoing = self::where($sourceColumn, $livestockId)
            ->where('direction', 'out')
            ->sum('jumlah');

        $incoming = self::where($destinationColumn, $livestockId)
            ->where('direction', 'in')
            ->sum('jumlah');

        $recent = self::forLivestock($livestockId)
            ->with('items')
            ->orderBy('tanggal', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($mutation) {
                return $mutation->summary;
            });

        return [
            'livestock_id' => $livestockId,
            'total_outgoing' => $outgoing,
            'total_incoming' => $incoming,
            'net_mutation' => $incoming - $outgoing,
            'recent_mutations' => $recent,
            'total_records' => self::forLivestock($livestockId)->count()
        ];
    }
}
