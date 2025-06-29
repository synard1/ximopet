<?php

namespace App\Events;

use App\Models\LivestockPurchase;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LivestockPurchaseStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $batch;
    public $oldStatus;
    public $newStatus;
    public $updatedBy;
    public $timestamp;
    public $notes;
    public $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(
        LivestockPurchase $batch,
        string $oldStatus,
        string $newStatus,
        int $updatedBy,
        ?string $notes = null,
        array $metadata = []
    ) {
        $this->batch = $batch;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
        $this->updatedBy = $updatedBy;
        $this->timestamp = now()->toISOString();
        $this->notes = $notes;
        $this->metadata = array_merge([
            'batch_id' => $batch->id,
            'invoice_number' => $batch->invoice_number,
            'supplier_name' => $batch->vendor?->name ?? 'Unknown',
            'farm_name' => $batch->farm?->name ?? 'Unknown',
            'coop_name' => $batch->coop?->name ?? 'Unknown',
            'total_value' => $this->calculateTotalValue($batch),
            'updated_by_name' => User::find($updatedBy)?->name ?? 'Unknown',
            'requires_refresh' => $this->requiresRefresh($oldStatus, $newStatus),
            'priority' => $this->getPriority($oldStatus, $newStatus),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $metadata);

        Log::info('LivestockPurchaseStatusChanged event created', [
            'batch_id' => $batch->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'updated_by' => $updatedBy,
            'metadata' => $this->metadata
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        $channels = [
            new Channel('livestock-purchases'),                    // General
            new Channel('livestock-purchase.' . $this->batch->id), // Specific
            new PrivateChannel('App.Models.User.' . $this->updatedBy) // User-specific
        ];

        // Add farm-specific channel if farm is available
        if ($this->batch->farm_id) {
            $channels[] = new Channel('farm.' . $this->batch->farm_id . '.livestock-purchases');
        }

        Log::info('LivestockPurchase broadcast channels', [
            'batch_id' => $this->batch->id,
            'channels' => count($channels)
        ]);

        return $channels;
    }

    /**
     * Get the event name for broadcasting
     */
    public function broadcastAs(): string
    {
        return 'status-changed';
    }

    /**
     * Get the data to broadcast
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'livestock_purchase_status_changed',
            'batch_id' => $this->batch->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'updated_by' => $this->updatedBy,
            'timestamp' => $this->timestamp,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
            'message' => $this->getNotificationMessage(),
            'action_required' => $this->getActionRequired(),
        ];
    }

    /**
     * Calculate total value of the livestock purchase
     */
    private function calculateTotalValue(LivestockPurchase $batch): float
    {
        return $batch->details->sum(function ($detail) {
            return $detail->price_total;
        }) + ($batch->expedition_fee ?? 0);
    }

    /**
     * Determine if this status change requires data refresh
     */
    private function requiresRefresh(string $oldStatus, string $newStatus): bool
    {
        // Critical status changes that affect livestock and data integrity
        $criticalChanges = [
            'draft' => ['in_coop', 'confirmed'],
            'confirmed' => ['in_coop', 'cancelled'],
            'in_coop' => ['completed', 'cancelled'],
            'pending' => ['in_coop', 'cancelled'],
        ];

        return isset($criticalChanges[$oldStatus]) &&
            in_array($newStatus, $criticalChanges[$oldStatus]);
    }

    /**
     * Get notification priority
     */
    private function getPriority(string $oldStatus, string $newStatus): string
    {
        if ($newStatus === 'in_coop') return 'high';
        if ($newStatus === 'cancelled') return 'medium';
        if ($newStatus === 'completed') return 'low';
        return 'normal';
    }

    /**
     * Get user-friendly notification message
     */
    private function getNotificationMessage(): string
    {
        $statusLabels = LivestockPurchase::STATUS_LABELS;
        $oldLabel = $statusLabels[$this->oldStatus] ?? $this->oldStatus;
        $newLabel = $statusLabels[$this->newStatus] ?? $this->newStatus;

        return sprintf(
            'Livestock Purchase #%s status changed from %s to %s by %s',
            $this->metadata['invoice_number'],
            $oldLabel,
            $newLabel,
            $this->metadata['updated_by_name']
        );
    }

    /**
     * Get required actions for users
     */
    private function getActionRequired(): array
    {
        $actions = [];

        if ($this->metadata['requires_refresh']) {
            $actions[] = 'refresh_data';
        }

        if ($this->newStatus === 'in_coop') {
            $actions[] = 'livestock_updated';
        }

        if ($this->newStatus === 'cancelled') {
            $actions[] = 'review_cancellation';
        }

        return $actions;
    }
}
