<?php

namespace App\Traits;

use App\Models\FeedStatusHistory;

trait HasFeedStatusHistory
{
    /**
     * Get all status histories for this model
     */
    public function feedStatusHistories()
    {
        return $this->morphMany(FeedStatusHistory::class, 'feedable');
    }

    /**
     * Update status and create history record
     */
    public function updateFeedStatus($newStatus, $notes = null, $metadata = [])
    {
        $oldStatus = $this->status ?? null;

        // Validasi notes wajib untuk status tertentu
        if ($this->requiresNotesForStatus($newStatus) && empty($notes)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'notes' => 'Catatan wajib diisi untuk status ' . $newStatus . '.'
            ]);
        }

        // Update status in model
        $this->update([
            'status' => $newStatus,
            'updated_by' => auth()->id()
        ]);

        // Create status history
        FeedStatusHistory::createForModel($this, $oldStatus, $newStatus, $notes, $metadata);

        return $this;
    }

    /**
     * Get the latest status history
     */
    public function getLatestStatusHistory()
    {
        return $this->feedStatusHistories()->latest()->first();
    }

    /**
     * Get status history for a specific transition
     */
    public function getStatusHistoryFor($fromStatus, $toStatus)
    {
        return $this->feedStatusHistories()
            ->statusTransition($fromStatus, $toStatus)
            ->get();
    }

    /**
     * Check if notes are required for a specific status
     * Override this method in each model to define specific requirements
     */
    protected function requiresNotesForStatus($status)
    {
        return false; // Default: no notes required
    }

    /**
     * Get all available statuses for this model
     * Override this method in each model to define available statuses
     */
    public function getAvailableStatuses()
    {
        return []; // Default: empty array
    }

    /**
     * Get status history timeline
     */
    public function getStatusTimeline()
    {
        return $this->feedStatusHistories()
            ->with(['creator', 'updater'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Boot the trait
     */
    public static function bootHasFeedStatusHistory()
    {
        // Automatically create initial status history when creating a new record
        static::created(function ($model) {
            if (isset($model->status)) {
                FeedStatusHistory::createForModel(
                    $model,
                    null,
                    $model->status,
                    'Initial status on creation',
                    ['action' => 'created']
                );
            }
        });
    }
}
