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
        // Only log if debug mode is enabled
        if (config('app.debug')) {
            Log::info('Processing SupplyPurchaseStatusChanged notification', [
                'batch_id' => $event->batch->id,
                'old_status' => $event->oldStatus,
                'new_status' => $event->newStatus,
                'updated_by' => $event->updatedBy
            ]);
        }

        try {
            // Get users that should be notified
            $usersToNotify = $this->getUsersToNotify($event);

            if ($usersToNotify->isEmpty()) {
                if (config('app.debug')) {
                    Log::info('No users to notify for batch: ' . $event->batch->id);
                }
                return;
            }

            // Send notifications to each user
            foreach ($usersToNotify as $user) {
                try {
                    $user->notify(new SupplyPurchaseStatusNotification($event));

                    if (config('app.debug')) {
                        Log::info('Notification sent to user', [
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'batch_id' => $event->batch->id
                        ]);
                    }
                } catch (\Exception $e) {
                    if (config('app.debug')) {
                        Log::error('Failed to send notification to user', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                            'batch_id' => $event->batch->id
                        ]);
                    }
                }
            }

            if (config('app.debug')) {
                Log::info('SupplyPurchaseStatusChanged notifications completed', [
                    'batch_id' => $event->batch->id,
                    'users_notified' => $usersToNotify->count()
                ]);
            }
        } catch (\Exception $e) {
            if (config('app.debug')) {
                Log::error('Error processing SupplyPurchaseStatusChanged notification', [
                    'batch_id' => $event->batch->id,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
        }
    }

    /**
     * Get users that should be notified about this status change
     */
    private function getUsersToNotify(SupplyPurchaseStatusChanged $event): \Illuminate\Database\Eloquent\Collection
    {
        $batch = $event->batch;
        $userIds = collect();

        try {
            // 1. Get farm operators for the related farm
            $firstPurchase = $batch->supplyPurchases->first();
            if ($firstPurchase && $firstPurchase->farm_id) {
                $farmOperatorIds = User::whereHas('farmOperators', function ($query) use ($firstPurchase) {
                    $query->where('farm_id', $firstPurchase->farm_id);
                })->pluck('id');

                $userIds = $userIds->merge($farmOperatorIds);

                if (config('app.debug')) {
                    Log::info('Found farm operators to notify', [
                        'farm_id' => $firstPurchase->farm_id,
                        'count' => $farmOperatorIds->count()
                    ]);
                }
            }

            // 2. Get supervisors and managers
            $supervisorManagerIds = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['Supervisor', 'Manager', 'Admin']);
            })->pluck('id');

            $userIds = $userIds->merge($supervisorManagerIds);

            if (config('app.debug')) {
                Log::info('Found supervisors/managers to notify', [
                    'count' => $supervisorManagerIds->count()
                ]);
            }

            // 3. Include the user who created the batch (if different from updater)
            if ($batch->created_by && $batch->created_by !== $event->updatedBy) {
                $creator = User::find($batch->created_by);
                if ($creator) {
                    $userIds->push($creator->id);

                    if (config('app.debug')) {
                        Log::info('Added batch creator to notification list', [
                            'creator_id' => $creator->id
                        ]);
                    }
                }
            }

            // 4. For high-priority changes, notify purchasing team
            if (isset($event->metadata['priority']) && $event->metadata['priority'] === 'high') {
                $purchasingTeamIds = User::whereHas('roles', function ($query) {
                    $query->whereIn('name', ['Purchasing', 'Supply Chain']);
                })->pluck('id');

                $userIds = $userIds->merge($purchasingTeamIds);

                if (config('app.debug')) {
                    Log::info('Added purchasing team for high priority change', [
                        'count' => $purchasingTeamIds->count()
                    ]);
                }
            }

            // Remove duplicates and exclude the user who made the change
            $uniqueUserIds = $userIds->unique()->reject(function ($userId) use ($event) {
                return $userId === $event->updatedBy;
            });

            // Get the actual User models
            $usersToNotify = User::whereIn('id', $uniqueUserIds)->get();

            if (config('app.debug')) {
                Log::info('Final notification list prepared', [
                    'batch_id' => $batch->id,
                    'total_users' => $usersToNotify->count(),
                    'excluded_updater' => $event->updatedBy
                ]);
            }

            return $usersToNotify;
        } catch (\Exception $e) {
            if (config('app.debug')) {
                Log::error('Error determining users to notify', [
                    'batch_id' => $batch->id,
                    'error' => $e->getMessage()
                ]);
            }

            return User::whereRaw('1 = 0')->get(); // Return empty Eloquent Collection
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(SupplyPurchaseStatusChanged $event, \Throwable $exception): void
    {
        if (config('app.debug')) {
            Log::error('SupplyPurchaseStatusNotificationListener job failed', [
                'batch_id' => $event->batch->id,
                'error' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ]);
        }
    }
}
