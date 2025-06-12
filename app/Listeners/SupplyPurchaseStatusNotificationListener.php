<?php

namespace App\Listeners;

use App\Events\SupplyPurchaseStatusChanged;
use App\Models\User;
use App\Notifications\SupplyPurchaseStatusNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SupplyPurchaseStatusNotificationListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SupplyPurchaseStatusChanged $event): void
    {
        Log::info('Processing SupplyPurchaseStatusChanged notification', [
            'batch_id' => $event->batch->id,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'updated_by' => $event->updatedBy
        ]);

        try {
            // Get users that should be notified
            $usersToNotify = $this->getUsersToNotify($event);

            if ($usersToNotify->isEmpty()) {
                Log::info('No users to notify for batch: ' . $event->batch->id);
                return;
            }

            // Send notifications to each user
            foreach ($usersToNotify as $user) {
                try {
                    $user->notify(new SupplyPurchaseStatusNotification($event));
                    Log::info('Notification sent to user', [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'batch_id' => $event->batch->id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to send notification to user', [
                        'user_id' => $user->id,
                        'error' => $e->getMessage(),
                        'batch_id' => $event->batch->id
                    ]);
                }
            }

            Log::info('SupplyPurchaseStatusChanged notifications completed', [
                'batch_id' => $event->batch->id,
                'users_notified' => $usersToNotify->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing SupplyPurchaseStatusChanged notification', [
                'batch_id' => $event->batch->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }

    /**
     * Get users that should be notified about this status change
     */
    private function getUsersToNotify(SupplyPurchaseStatusChanged $event): \Illuminate\Database\Eloquent\Collection
    {
        $batch = $event->batch;
        $usersToNotify = collect();

        try {
            // 1. Get farm operators for the related farm
            $firstPurchase = $batch->supplyPurchases->first();
            if ($firstPurchase && $firstPurchase->farm_id) {
                $farmOperators = User::whereHas('farmOperators', function ($query) use ($firstPurchase) {
                    $query->where('farm_id', $firstPurchase->farm_id);
                })->get();

                $usersToNotify = $usersToNotify->merge($farmOperators);
                Log::info('Found farm operators to notify', [
                    'farm_id' => $firstPurchase->farm_id,
                    'count' => $farmOperators->count()
                ]);
            }

            // 2. Get supervisors and managers
            $supervisorsAndManagers = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['Supervisor', 'Manager', 'Admin']);
            })->get();

            $usersToNotify = $usersToNotify->merge($supervisorsAndManagers);
            Log::info('Found supervisors/managers to notify', [
                'count' => $supervisorsAndManagers->count()
            ]);

            // 3. Include the user who created the batch (if different from updater)
            if ($batch->created_by && $batch->created_by !== $event->updatedBy) {
                $creator = User::find($batch->created_by);
                if ($creator) {
                    $usersToNotify->push($creator);
                    Log::info('Added batch creator to notification list', [
                        'creator_id' => $creator->id
                    ]);
                }
            }

            // 4. For high-priority changes, notify purchasing team
            if ($event->metadata['priority'] === 'high') {
                $purchasingTeam = User::whereHas('roles', function ($query) {
                    $query->whereIn('name', ['Purchasing', 'Supply Chain']);
                })->get();

                $usersToNotify = $usersToNotify->merge($purchasingTeam);
                Log::info('Added purchasing team for high priority change', [
                    'count' => $purchasingTeam->count()
                ]);
            }

            // Remove duplicates and exclude the user who made the change
            $usersToNotify = $usersToNotify->unique('id')
                ->reject(function ($user) use ($event) {
                    return $user->id === $event->updatedBy;
                });

            Log::info('Final notification list prepared', [
                'batch_id' => $batch->id,
                'total_users' => $usersToNotify->count(),
                'excluded_updater' => $event->updatedBy
            ]);

            return $usersToNotify;
        } catch (\Exception $e) {
            Log::error('Error determining users to notify', [
                'batch_id' => $batch->id,
                'error' => $e->getMessage()
            ]);

            return collect();
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(SupplyPurchaseStatusChanged $event, \Throwable $exception): void
    {
        Log::error('SupplyPurchaseStatusNotificationListener job failed', [
            'batch_id' => $event->batch->id,
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ]);
    }
}
