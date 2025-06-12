<?php

namespace App\Traits;

use App\Models\SupplyStatusHistory;

trait HasSupplyStatusHistory
{
    /**
     * Get all status histories for this model
     */
    public function supplyStatusHistories()
    {
        return $this->morphMany(SupplyStatusHistory::class, 'supplyable');
    }

    /**
     * Update status and create history record
     */
    public function updateSupplyStatus($newStatus, $notes = null, $metadata = [])
    {
        $oldStatus = $this->status ?? null;

        // Validasi notes wajib untuk status tertentu
        if ($this->requiresNotesForSupplyStatus($newStatus) && empty($notes)) {
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
        SupplyStatusHistory::createForModel($this, $oldStatus, $newStatus, $notes, $metadata);

        return $this;
    }

    /**
     * Get the latest status history
     */
    public function getLatestSupplyStatusHistory()
    {
        return $this->supplyStatusHistories()->latest()->first();
    }

    /**
     * Get status history for a specific transition
     */
    public function getSupplyStatusHistoryFor($fromStatus, $toStatus)
    {
        return $this->supplyStatusHistories()
            ->statusTransition($fromStatus, $toStatus)
            ->get();
    }

    /**
     * Check if notes are required for a specific status
     * Override this method in each model to define specific requirements
     */
    protected function requiresNotesForSupplyStatus($status)
    {
        // Default requirements for Supply models
        $statusesRequiringNotes = [
            'cancelled',
            'rejected',
            'completed',
            'expired'
        ];

        return in_array($status, $statusesRequiringNotes);
    }

    /**
     * Get all available statuses for this model
     * Override this method in each model to define available statuses
     */
    public function getAvailableSupplyStatuses()
    {
        return []; // Default: empty array - should be overridden in each model
    }

    /**
     * Get status history timeline
     */
    public function getSupplyStatusTimeline()
    {
        return $this->supplyStatusHistories()
            ->with(['creator', 'updater'])
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Boot the trait
     */
    public static function bootHasSupplyStatusHistory()
    {
        // Automatically create initial status history when creating a new record
        static::created(function ($model) {
            if (isset($model->status)) {
                SupplyStatusHistory::createForModel(
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
