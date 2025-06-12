<?php

namespace App\Events;

use App\Models\SupplyPurchaseBatch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SupplyPurchaseStatusChanged implements ShouldBroadcast
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
        SupplyPurchaseBatch $batch,
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
        $this->notes = $notes;
        $this->timestamp = now()->toISOString();
        $this->metadata = array_merge([
            'batch_id' => $batch->id,
            'invoice_number' => $batch->invoice_number,
            'supplier_name' => $batch->supplier?->name ?? 'Unknown Supplier',
            'total_value' => $batch->supplyPurchases->sum(function ($purchase) {
                return $purchase->quantity * $purchase->price_per_unit;
            }),
            'updated_by_name' => \App\Models\User::find($updatedBy)?->name ?? 'Unknown User',
            'requires_refresh' => $this->requiresRefresh($oldStatus, $newStatus),
            'priority' => $this->getPriority($oldStatus, $newStatus),
        ], $metadata);

        Log::info('SupplyPurchaseStatusChanged Event created', [
            'batch_id' => $batch->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'updated_by' => $updatedBy,
            'requires_refresh' => $this->metadata['requires_refresh'],
            'priority' => $this->metadata['priority']
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            // General channel for all supply purchase updates
            new Channel('supply-purchases'),

            // Specific channel for this batch
            new Channel('supply-purchase.' . $this->batch->id),

            // Farm-specific channel if farm is available
            $this->getFarmChannel(),

            // User-specific channel for the updater
            new PrivateChannel('App.Models.User.' . $this->updatedBy)
        ];
    }

    /**
     * Get farm-specific channel
     */
    private function getFarmChannel(): ?Channel
    {
        $firstPurchase = $this->batch->supplyPurchases->first();
        if ($firstPurchase && $firstPurchase->farm_id) {
            return new Channel('farm.' . $firstPurchase->farm_id . '.supply-purchases');
        }
        return new Channel('supply-purchases.general');
    }

    /**
     * Get the broadcast event name
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
            'type' => 'supply_purchase_status_changed',
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
     * Determine if this status change requires data refresh
     */
    private function requiresRefresh(string $oldStatus, string $newStatus): bool
    {
        // Critical status changes that affect stock and data integrity
        $criticalChanges = [
            'draft' => ['arrived', 'confirmed'],
            'confirmed' => ['arrived', 'cancelled'],
            'arrived' => ['completed', 'cancelled'],
            'pending' => ['arrived', 'cancelled'],
        ];

        return isset($criticalChanges[$oldStatus]) &&
            in_array($newStatus, $criticalChanges[$oldStatus]);
    }

    /**
     * Get notification priority
     */
    private function getPriority(string $oldStatus, string $newStatus): string
    {
        if ($newStatus === 'arrived') return 'high';
        if ($newStatus === 'cancelled') return 'medium';
        if ($newStatus === 'completed') return 'low';
        return 'normal';
    }

    /**
     * Get user-friendly notification message
     */
    private function getNotificationMessage(): string
    {
        $statusLabels = SupplyPurchaseBatch::STATUS_LABELS;
        $oldLabel = $statusLabels[$this->oldStatus] ?? $this->oldStatus;
        $newLabel = $statusLabels[$this->newStatus] ?? $this->newStatus;

        return sprintf(
            'Supply Purchase #%s status changed from %s to %s by %s',
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

        if ($this->newStatus === 'arrived') {
            $actions[] = 'stock_updated';
        }

        if ($this->newStatus === 'cancelled') {
            $actions[] = 'review_cancellation';
        }

        return $actions;
    }
}
