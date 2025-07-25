<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SupplyMutation extends BaseModel
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $fillable = [
        'id',
        'mutation_id',
        'company_id',
        'date',
        'from_farm_id',
        'to_farm_id',
        'from_livestock_id',
        'to_livestock_id',
        'from_coop_id',
        'to_coop_id',
        'status',
        'approved_by',
        'approved_at',
        'verified_by',
        'verified_at',
        'rejected_by',
        'rejected_at',
        'rejected_reason',
        'data',
        'notes',
        'metadata',
        'number',
        'number_full',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'verified_at' => 'datetime',
        'rejected_at' => 'datetime',
        'metadata' => 'array',
        'data' => 'array',
    ];

    /**
     * Get the master mutation
     */
    public function mutation()
    {
        return $this->belongsTo(Mutation::class, 'mutation_id');
    }

    /**
     * Get the source farm
     */
    public function fromFarm()
    {
        return $this->belongsTo(Farm::class, 'from_farm_id');
    }

    /**
     * Get the destination farm
     */
    public function toFarm()
    {
        return $this->belongsTo(Farm::class, 'to_farm_id');
    }

    /**
     * Get the source livestock (if applicable)
     */
    public function fromLivestock()
    {
        return $this->belongsTo(Livestock::class, 'from_livestock_id');
    }

    /**
     * Get the destination livestock (if applicable)
     */
    public function toLivestock()
    {
        return $this->belongsTo(Livestock::class, 'to_livestock_id');
    }

    /**
     * Get the source coop (if applicable)
     */
    public function fromCoop()
    {
        return $this->belongsTo(Coop::class, 'from_coop_id');
    }

    /**
     * Get the destination coop (if applicable)
     */
    public function toCoop()
    {
        return $this->belongsTo(Coop::class, 'to_coop_id');
    }

    /**
     * Get the company
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the approver
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the rejector
     */
    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get the verifier
     */
    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get mutation items
     */
    public function supplyMutationDetails()
    {
        return $this->hasMany(SupplyMutationItem::class);
    }

    public function supplyMutationItems()
    {
        return $this->hasMany(SupplyMutationItem::class, 'supply_mutation_id', 'id');
    }

    /**
     * Get destination type (coop or livestock)
     */
    public function getDestinationTypeAttribute(): string
    {
        if ($this->to_coop_id) {
            return 'coop';
        }
        if ($this->to_livestock_id) {
            return 'livestock';
        }
        return 'unknown';
    }

    /**
     * Get destination info
     */
    public function getDestinationInfoAttribute(): ?array
    {
        if ($this->to_coop_id) {
            $coop = $this->toCoop;
            return $coop ? [
                'type' => 'coop',
                'id' => $coop->id,
                'name' => $coop->name,
                'farm_id' => $coop->farm_id,
                'farm_name' => $coop->farm->name ?? 'Unknown',
                'capacity' => $coop->capacity,
            ] : null;
        }

        if ($this->to_livestock_id) {
            $livestock = $this->toLivestock;
            return $livestock ? [
                'type' => 'livestock',
                'id' => $livestock->id,
                'name' => $livestock->name,
                'farm_id' => $livestock->farm_id,
                'farm_name' => $livestock->farm->name ?? 'Unknown',
                'coop_id' => $livestock->coop_id,
                'coop_name' => $livestock->coop->name ?? 'Unknown',
            ] : null;
        }

        return null;
    }

    /**
     * Check if mutation is in draft
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if mutation is in process
     */
    public function isInProcess(): bool
    {
        return $this->status === 'in_process';
    }

    /**
     * Check if mutation is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if mutation is cancelled
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if mutation can move to in_process
     */
    public function canBeInProcess(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if mutation can be completed
     */
    public function canBeCompleted(): bool
    {
        return $this->status === 'in_process';
    }

    /**
     * Check if mutation can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'in_process', 'completed']);
    }

    /**
     * Check if mutation is verified (separate from status)
     */
    public function isVerified(): bool
    {
        return !is_null($this->verified_at);
    }

    /**
     * Check if mutation can be rejected
     */
    public function canBeRejected(): bool
    {
        return $this->status === 'pending' && is_null($this->rejected_at);
    }

    /**
     * Check if mutation can bypass approval
     */
    public function canBypassApproval(): bool
    {
        $config = config('supply_mutation.workflow.bypass_approval');

        if (!$config['enabled']) {
            return false;
        }

        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            return false;
        }

        // Check if user has bypass role
        $hasBypassRole = false;
        foreach ($config['bypass_roles'] as $role) {
            if ($user->hasRole($role)) {
                $hasBypassRole = true;
                break;
            }
        }

        if (!$hasBypassRole) {
            return false;
        }

        // Check bypass conditions
        $totalQuantity = $this->supplyMutationItems->sum('quantity');
        $totalValue = $this->supplyMutationItems->sum(function ($item) {
            return $item->quantity * ($item->unit_price ?? 0);
        });

        // Check quantity threshold
        if ($totalQuantity > $config['bypass_quantity_threshold']) {
            return false;
        }

        // Check value threshold
        if ($totalValue > $config['bypass_value_threshold']) {
            return false;
        }

        // Check same farm mutation
        if (
            $config['bypass_conditions']['same_farm_mutation'] &&
            $this->from_farm_id === $this->to_farm_id
        ) {
            return true;
        }

        // Check small quantity
        if (
            $config['bypass_conditions']['small_quantity'] &&
            $totalQuantity <= $config['bypass_quantity_threshold']
        ) {
            return true;
        }

        // Check emergency mutation
        if ($config['bypass_conditions']['emergency_mutation']) {
            return true;
        }

        // Verification is no longer required for bypass
        // Removed verified_data check

        return false;
    }

    /**
     * Check if mutation requires verification for bypass
     */
    public function requiresVerificationForBypass(): bool
    {
        $config = config('supply_mutation.workflow.verification');
        return false; // Verification is no longer required for bypass
    }
}
