<?php

namespace App\Events;

use App\Models\FeedPurchaseBatch;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FeedPurchaseStatusChanged implements ShouldBroadcast
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
        FeedPurchaseBatch $batch,
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
            'supplier_name' => $batch->supplier?->name ?? 'Unknown',
            'farm_name' => $this->getFarmName($batch),
            'coop_name' => $this->getCoopName($batch),
            'total_value' => $this->calculateTotalValue($batch),
            'updated_by_name' => User::find($updatedBy)?->name ?? 'Unknown',
            'requires_refresh' => $this->requiresRefresh($oldStatus, $newStatus),
            'priority' => $this->getPriority($oldStatus, $newStatus),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ], $metadata);

        Log::info('FeedPurchaseStatusChanged event created', [
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
            new Channel('feed-purchases'),                    // General
            new Channel('feed-purchase.' . $this->batch->id), // Specific
            new PrivateChannel('App.Models.User.' . $this->updatedBy) // User-specific
        ];

        // Add farm-specific channel if farm is available from feed purchases
        $farmId = $this->getFarmId($this->batch);
        if ($farmId) {
            $channels[] = new Channel('farm.' . $farmId . '.feed-purchases');
        }

        Log::info('FeedPurchase broadcast channels', [
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
            'type' => 'feed_purchase_status_changed',
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
     * Get farm name from feed purchases
     */
    private function getFarmName(FeedPurchaseBatch $batch): string
    {
        $firstPurchase = $batch->feedPurchases->first();
        return $firstPurchase?->livestock?->farm?->name ?? 'Unknown';
    }

    /**
     * Get coop name from feed purchases
     */
    private function getCoopName(FeedPurchaseBatch $batch): string
    {
        $firstPurchase = $batch->feedPurchases->first();
        return $firstPurchase?->livestock?->coop?->name ?? 'Unknown';
    }

    /**
     * Get farm ID from feed purchases
     */
    private function getFarmId(FeedPurchaseBatch $batch): ?string
    {
        $firstPurchase = $batch->feedPurchases->first();
        return $firstPurchase?->livestock?->farm_id;
    }

    /**
     * Calculate total value of the feed purchase
     */
    private function calculateTotalValue(FeedPurchaseBatch $batch): float
    {
        return $batch->feedPurchases->sum(function ($purchase) {
            return $purchase->quantity * $purchase->price_per_unit;
        }) + ($batch->expedition_fee ?? 0);
    }

    /**
     * Determine if this status change requires data refresh
     */
    private function requiresRefresh(string $oldStatus, string $newStatus): bool
    {
        // Critical status changes that affect feed stock and data integrity
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
        $statusLabels = FeedPurchaseBatch::STATUS_LABELS;
        $oldLabel = $statusLabels[$this->oldStatus] ?? $this->oldStatus;
        $newLabel = $statusLabels[$this->newStatus] ?? $this->newStatus;

        return sprintf(
            'Feed Purchase #%s status changed from %s to %s by %s',
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
            $actions[] = 'feed_stock_updated';
        }

        if ($this->newStatus === 'cancelled') {
            $actions[] = 'review_cancellation';
        }

        return $actions;
    }
}
