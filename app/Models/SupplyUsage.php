<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Company;
use Carbon\Carbon;
use App\Config\SupplyUsageBypassConfig;

// Import logDebugIfDebug if available
if (!function_exists('App\\Helpers\\logDebugIfDebug')) {
    function logDebugIfDebug($msg, $data = []) {}
}

class SupplyUsage extends BaseModel
{
    use HasFactory, HasUuids, SoftDeletes;

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROCESS = 'in_process';
    const STATUS_COMPLETED = 'completed';
    const STATUS_UNDER_REVIEW = 'under_review';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PARTIALLY_USED = 'partially_used';
    const STATUS_EXPIRED = 'expired';
    const STATUS_DAMAGED = 'damaged';

    // Configuration constants for bypass rules
    const BYPASS_CONFIG_KEY = 'supply_usage_bypass_rules';

    // Default bypass configuration
    const DEFAULT_BYPASS_CONFIG = [
        'allow_direct_to_in_process' => false,      // Allow draft → in_process directly
        'allow_direct_to_completed' => false,       // Allow draft → completed directly
        'skip_approval_for_operator' => false,      // Skip approval for operator role
        'skip_approval_for_supervisor' => false,    // Skip approval for supervisor role
        'allow_operator_status_changes' => [        // Status changes allowed for operator
            'draft' => ['pending', 'cancelled'],
            'pending' => ['in_process', 'cancelled'],
            'in_process' => ['completed', 'partially_used', 'cancelled']
        ],
        'allow_supervisor_status_changes' => [      // Status changes allowed for supervisor
            'draft' => ['pending', 'in_process', 'cancelled'],
            'pending' => ['in_process', 'under_review', 'cancelled'],
            'in_process' => ['completed', 'partially_used', 'cancelled'],
            'under_review' => ['in_process', 'rejected', 'cancelled']
        ],
        'allow_manager_status_changes' => [         // Status changes allowed for manager
            'draft' => ['pending', 'in_process', 'completed', 'cancelled'],
            'pending' => ['in_process', 'under_review', 'completed', 'cancelled'],
            'in_process' => ['completed', 'partially_used', 'cancelled'],
            'under_review' => ['in_process', 'rejected', 'completed', 'cancelled'],
            'rejected' => ['draft', 'cancelled']
        ],
        'auto_approve_for_roles' => [               // Roles that get auto-approval
            'Manager',
            'Administrator'
        ],
        'require_notes_for_status_change' => false, // Require notes when changing status
        'enable_audit_trail' => true,               // Enable audit trail for status changes
        'stock_impact_bypass' => [                  // Status changes that bypass stock impact
            'draft_to_in_process',
            'draft_to_completed'
        ]
    ];

    // Status Labels
    const STATUS_LABELS = [
        self::STATUS_DRAFT => 'Draft',
        self::STATUS_PENDING => 'Pending',
        self::STATUS_IN_PROCESS => 'In Process',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_UNDER_REVIEW => 'Under Review',
        self::STATUS_REJECTED => 'Rejected',
        self::STATUS_PARTIALLY_USED => 'Partially Used',
        self::STATUS_EXPIRED => 'Expired',
        self::STATUS_DAMAGED => 'Damaged',
    ];

    // Status Badge Classes
    const STATUS_BADGE_CLASSES = [
        self::STATUS_DRAFT => 'badge-secondary',
        self::STATUS_PENDING => 'badge-warning',
        self::STATUS_IN_PROCESS => 'badge-info',
        self::STATUS_COMPLETED => 'badge-success',
        self::STATUS_CANCELLED => 'badge-danger',
        self::STATUS_UNDER_REVIEW => 'badge-primary',
        self::STATUS_REJECTED => 'badge-danger',
        self::STATUS_PARTIALLY_USED => 'badge-warning',
        self::STATUS_EXPIRED => 'badge-danger',
        self::STATUS_DAMAGED => 'badge-danger',
    ];

    // Status Icons
    const STATUS_ICONS = [
        self::STATUS_DRAFT => 'ki-duotone ki-file fs-6',
        self::STATUS_PENDING => 'ki-duotone ki-clock fs-6',
        self::STATUS_IN_PROCESS => 'ki-duotone ki-gear fs-6',
        self::STATUS_COMPLETED => 'ki-duotone ki-check-circle fs-6',
        self::STATUS_CANCELLED => 'ki-duotone ki-cross-circle fs-6',
        self::STATUS_UNDER_REVIEW => 'ki-duotone ki-eye fs-6',
        self::STATUS_REJECTED => 'ki-duotone ki-cross fs-6',
        self::STATUS_PARTIALLY_USED => 'ki-duotone ki-half-star fs-6',
        self::STATUS_EXPIRED => 'ki-duotone ki-calendar-cross fs-6',
        self::STATUS_DAMAGED => 'ki-duotone ki-warning fs-6',
    ];

    // Status Permissions
    const STATUS_PERMISSIONS = [
        self::STATUS_DRAFT => ['edit', 'delete', 'submit'],
        self::STATUS_PENDING => ['view', 'cancel', 'approve'],
        self::STATUS_IN_PROCESS => ['view', 'cancel'],
        self::STATUS_COMPLETED => ['view', 'edit_limited', 'delete_limited'],
        self::STATUS_CANCELLED => ['view', 'restore'],
        self::STATUS_UNDER_REVIEW => ['view'],
        self::STATUS_REJECTED => ['view', 'edit', 'resubmit'],
        self::STATUS_PARTIALLY_USED => ['view', 'complete'],
        self::STATUS_EXPIRED => ['view', 'dispose'],
        self::STATUS_DAMAGED => ['view', 'report'],
    ];

    // Stock Impact Rules
    const STOCK_IMPACT = [
        self::STATUS_DRAFT => 'NO_IMPACT',
        self::STATUS_PENDING => 'RESERVED',
        self::STATUS_IN_PROCESS => 'REDUCING',
        self::STATUS_COMPLETED => 'REDUCED',
        self::STATUS_CANCELLED => 'RESTORED',
        self::STATUS_UNDER_REVIEW => 'RESERVED',
        self::STATUS_REJECTED => 'NO_IMPACT',
        self::STATUS_PARTIALLY_USED => 'PARTIALLY_REDUCED',
        self::STATUS_EXPIRED => 'NO_IMPACT',
        self::STATUS_DAMAGED => 'NO_IMPACT',
    ];

    // Display Statuses for Legend
    const DISPLAY_STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PENDING,
        self::STATUS_IN_PROCESS,
        self::STATUS_COMPLETED,
        self::STATUS_CANCELLED,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_REJECTED,
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'company_id',
        'farm_id',
        'coop_id',
        'livestock_id',
        'usage_date',
        'total_quantity',
        'notes',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'usage_date' => 'datetime',
        'total_quantity' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->status) {
                $model->status = self::STATUS_DRAFT;
            }
        });
    }

    // Helper Methods for Status Checks
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isInProcess(): bool
    {
        return $this->status === self::STATUS_IN_PROCESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isUnderReview(): bool
    {
        return $this->status === self::STATUS_UNDER_REVIEW;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isPartiallyUsed(): bool
    {
        return $this->status === self::STATUS_PARTIALLY_USED;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    public function isDamaged(): bool
    {
        return $this->status === self::STATUS_DAMAGED;
    }

    // Permission Methods
    public function canBeEdited(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING,
            self::STATUS_REJECTED
        ]);
    }

    public function canBeDeleted(): bool
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $isSuperAdmin = $user && $user->hasRole('SuperAdmin');
        $result = $isSuperAdmin ? true : in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_CANCELLED
        ]);
        $roles = null;
        if ($user) {
            if (method_exists($user, 'getRoleNames')) {
                $roles = $user->getRoleNames();
                if (is_object($roles) && method_exists($roles, 'toArray')) {
                    $roles = $roles->toArray();
                }
            } else if (property_exists($user, 'roles')) {
                $roles = $user->roles;
            } else {
                $roles = 'unknown';
            }
        }
        if (function_exists('logDebugIfDebug')) {
            logDebugIfDebug('canBeDeleted called', [
                'user_id' => $user ? $user->id : null,
                'roles' => $roles,
                'isSuperAdmin' => $isSuperAdmin,
                'usage_id' => $this->id,
                'usage_status' => $this->status,
                'result' => $result
            ]);
        }
        return $result;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_PENDING,
            self::STATUS_IN_PROCESS
        ]);
    }

    public function canBeApproved(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_REJECTED
        ]);
    }

    public function canBeSubmitted(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canBeRestored(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    // Status Label and Badge Methods
    public function getStatusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'Unknown';
    }

    public function getStatusBadgeClass(): string
    {
        return self::STATUS_BADGE_CLASSES[$this->status] ?? 'badge-secondary';
    }

    public function getStatusIcon(): string
    {
        return self::STATUS_ICONS[$this->status] ?? 'ki-duotone ki-question fs-6';
    }

    public function getStockImpact(): string
    {
        return self::STOCK_IMPACT[$this->status] ?? 'NO_IMPACT';
    }

    public function getAvailablePermissions(): array
    {
        return self::STATUS_PERMISSIONS[$this->status] ?? ['view'];
    }

    // Status Transition Methods
    public function submit(): bool
    {
        if ($this->canBeSubmitted()) {
            $this->update(['status' => self::STATUS_PENDING]);
            return true;
        }
        return false;
    }

    public function approve(): bool
    {
        if ($this->canBeApproved()) {
            $this->update(['status' => self::STATUS_IN_PROCESS]);
            return true;
        }
        return false;
    }

    public function complete(): bool
    {
        if ($this->isInProcess()) {
            $this->update(['status' => self::STATUS_COMPLETED]);
            return true;
        }
        return false;
    }

    public function cancel(): bool
    {
        if ($this->canBeCancelled()) {
            $this->update(['status' => self::STATUS_CANCELLED]);
            return true;
        }
        return false;
    }

    public function reject(string $reason = null): bool
    {
        if ($this->canBeApproved()) {
            $this->update([
                'status' => self::STATUS_REJECTED,
                'notes' => $reason ? ($this->notes . "\nRejection Reason: " . $reason) : $this->notes
            ]);
            return true;
        }
        return false;
    }

    public function restore(): bool
    {
        if ($this->canBeRestored()) {
            $this->update(['status' => self::STATUS_PENDING]);
            return true;
        }
        return false;
    }

    /**
     * Relationship with Farm
     */
    public function farm(): BelongsTo
    {
        return $this->belongsTo(Farm::class);
    }

    /**
     * Relationship with Coop
     */
    public function coop(): BelongsTo
    {
        return $this->belongsTo(Coop::class);
    }

    /**
     * Relationship with Livestock
     */
    public function livestock(): BelongsTo
    {
        return $this->belongsTo(Livestock::class);
    }

    /**
     * Relationship with SupplyUsageDetails
     */
    public function details(): HasMany
    {
        return $this->hasMany(SupplyUsageDetail::class);
    }

    /**
     * Relationship with User (created by)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship with User (updated by)
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get formatted usage date
     */
    public function getFormattedUsageDateAttribute(): string
    {
        return $this->usage_date ? $this->usage_date->format('d M Y H:i') : '';
    }

    /**
     * Get status badge class (Legacy method for backward compatibility)
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return $this->getStatusBadgeClass();
    }

    /**
     * Scope for active records
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_REJECTED]);
    }

    /**
     * Scope for specific farm
     */
    public function scopeForFarm($query, $farmId)
    {
        return $query->where('farm_id', $farmId);
    }

    /**
     * Scope for specific coop
     */
    public function scopeForCoop($query, $coopId)
    {
        return $query->where('coop_id', $coopId);
    }

    /**
     * Scope for specific livestock
     */
    public function scopeForLivestock($query, $livestockId)
    {
        return $query->where('livestock_id', $livestockId);
    }

    /**
     * Scope for date range
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('usage_date', [
            Carbon::parse($startDate)->startOfDay(),
            Carbon::parse($endDate)->endOfDay()
        ]);
    }

    /**
     * Scope for recent records
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('usage_date', '>=', Carbon::now()->subDays($days));
    }

    /**
     * Scope for specific status
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for editable records
     */
    public function scopeEditable($query)
    {
        return $query->whereIn('status', [
            self::STATUS_DRAFT,
            self::STATUS_PENDING,
            self::STATUS_REJECTED
        ]);
    }

    /**
     * Get summary data for reporting
     */
    public function getSummaryData(): array
    {
        return [
            'id' => $this->id,
            'farm_name' => $this->farm->name ?? '',
            'coop_name' => $this->coop->name ?? '',
            'livestock_name' => $this->livestock->name ?? '',
            'usage_date' => $this->formatted_usage_date,
            'total_quantity' => $this->total_quantity,
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            'status_badge_class' => $this->getStatusBadgeClass(),
            'notes' => $this->notes,
            'created_by' => $this->creator->name ?? '',
            'created_at' => $this->created_at->format('d M Y H:i'),
        ];
    }

    /**
     * Get status statistics for reporting
     */
    public static function getStatusStatistics(): array
    {
        $stats = [];
        foreach (self::DISPLAY_STATUSES as $status) {
            $stats[$status] = [
                'count' => self::where('status', $status)->count(),
                'label' => self::STATUS_LABELS[$status],
                'badge_class' => self::STATUS_BADGE_CLASSES[$status],
                'icon' => self::STATUS_ICONS[$status],
            ];
        }
        return $stats;
    }

    /**
     * Get bypass configuration for supply usage
     */
    public static function getBypassConfig()
    {
        return SupplyUsageBypassConfig::getConfig();
    }

    /**
     * Update bypass configuration
     */
    public static function updateBypassConfig($config)
    {
        // No-op for hardcoded config. For future DB migration.
        return false;
    }

    /**
     * Check if user can bypass approval for specific status transition
     */
    public function canBypassApproval($newStatus, $user = null)
    {
        $user = $user ?? Auth::user();
        $config = self::getBypassConfig();

        // Check if user role is in auto-approve list
        if (in_array($user->getRoleNames()->first(), $config['auto_approve_for_roles'])) {
            return true;
        }

        // Check role-specific bypass rules
        if ($user->hasRole('Operator') && $config['skip_approval_for_operator']) {
            return true;
        }

        if ($user->hasRole('Supervisor') && $config['skip_approval_for_supervisor']) {
            return true;
        }

        // Check direct transition bypass rules
        $transition = $this->status . '_to_' . $newStatus;
        if (in_array($transition, $config['stock_impact_bypass'])) {
            return true;
        }

        return false;
    }

    /**
     * Get allowed status transitions for current user
     */
    public function getAllowedStatusTransitions($user = null)
    {
        $user = $user ?? Auth::user();
        $userRole = $user->getRoleNames()->first();

        // Use SupplyUsageBypassConfig for consistent transition logic
        return SupplyUsageBypassConfig::getAllowedTransitions($userRole, $this->status);
    }

    /**
     * Check if status transition is valid for current user
     */
    public function canTransitionTo($newStatus, $user = null)
    {
        $user = $user ?? Auth::user();
        $allowedTransitions = $this->getAllowedStatusTransitions($user);

        return in_array($newStatus, $allowedTransitions);
    }

    /**
     * Check if status transition bypasses stock impact
     */
    public function bypassesStockImpact($newStatus)
    {
        $config = self::getBypassConfig();
        $transition = $this->status . '_to_' . $newStatus;

        return in_array($transition, $config['stock_impact_bypass']);
    }

    /**
     * Check if notes are required for status change
     */
    public static function requiresNotesForStatusChange()
    {
        $config = self::getBypassConfig();
        return $config['require_notes_for_status_change'] ?? false;
    }

    /**
     * Check if audit trail is enabled
     */
    public static function isAuditTrailEnabled()
    {
        $config = self::getBypassConfig();
        return $config['enable_audit_trail'] ?? true;
    }

    /**
     * Get bypass configuration for specific company
     */
    public static function getBypassConfigForCompany($companyId)
    {
        return SupplyUsageBypassConfig::getConfig();
    }

    /**
     * Reset bypass configuration to default
     */
    public static function resetBypassConfig($companyId = null)
    {
        // No-op for hardcoded config. For future DB migration.
        return false;
    }

    /**
     * Get bypass configuration summary for UI
     */
    public static function getBypassConfigSummary()
    {
        $config = self::getBypassConfig();

        return [
            'direct_transitions' => [
                'draft_to_in_process' => $config['allow_direct_to_in_process'],
                'draft_to_completed' => $config['allow_direct_to_completed']
            ],
            'role_bypass' => [
                'operator' => $config['skip_approval_for_operator'],
                'supervisor' => $config['skip_approval_for_supervisor']
            ],
            'auto_approve_roles' => $config['auto_approve_for_roles'],
            'require_notes' => $config['require_notes_for_status_change'],
            'audit_trail' => $config['enable_audit_trail'],
            'stock_impact_bypass' => $config['stock_impact_bypass']
        ];
    }
}
