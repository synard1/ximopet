<?php

namespace App\Traits;

use App\Services\DatabasePerformanceTrackerService;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\Model;

trait HasPerformanceTracking
{
    /**
     * Boot the trait
     */
    protected static function bootHasPerformanceTracking()
    {
        // Track model creation
        static::creating(function ($model) {
            if (self::shouldTrackPerformance()) {
                $tracker = App::make(DatabasePerformanceTrackerService::class);
                $tracker->startTracking();
                // Store tracker in session for this model instance
                $modelId = uniqid('model_', true);
                session(['performance_tracker_' . $modelId => $tracker]);
                // Store tracker ID in static property
                static::$performanceTrackerId = $modelId;
            }
        });

        static::created(function ($model) {
            if (self::shouldTrackPerformance() && isset(static::$performanceTrackerId)) {
                $trackerId = static::$performanceTrackerId;
                $tracker = session('performance_tracker_' . $trackerId);
                if ($tracker) {
                    $tracker->stopTracking(
                        'create',
                        get_class($model),
                        $model->id,
                        1
                    );
                    session()->forget('performance_tracker_' . $trackerId);
                }
                static::$performanceTrackerId = null;
            }
        });

        // Track model updates
        static::updating(function ($model) {
            if (self::shouldTrackPerformance() && self::shouldTrackModelChanges($model)) {
                $tracker = App::make(DatabasePerformanceTrackerService::class);
                $tracker->startTracking();
                // Store tracker in session for this model instance
                $modelId = uniqid('model_', true);
                session(['performance_tracker_' . $modelId => $tracker]);
                static::$performanceTrackerId = $modelId;
            }
        });

        static::updated(function ($model) {
            if (self::shouldTrackPerformance() && isset(static::$performanceTrackerId)) {
                $trackerId = static::$performanceTrackerId;
                $tracker = session('performance_tracker_' . $trackerId);
                if ($tracker) {
                    $tracker->stopTracking(
                        'update',
                        get_class($model),
                        $model->id,
                        1
                    );
                    session()->forget('performance_tracker_' . $trackerId);
                }
                static::$performanceTrackerId = null;
            }
        });

        // Track model deletion
        static::deleting(function ($model) {
            if (self::shouldTrackPerformance()) {
                $tracker = App::make(DatabasePerformanceTrackerService::class);
                $tracker->startTracking();
                // Store tracker in session for this model instance
                $modelId = uniqid('model_', true);
                session(['performance_tracker_' . $modelId => $tracker]);
                static::$performanceTrackerId = $modelId;
            }
        });

        static::deleted(function ($model) {
            if (self::shouldTrackPerformance() && isset(static::$performanceTrackerId)) {
                $trackerId = static::$performanceTrackerId;
                $tracker = session('performance_tracker_' . $trackerId);
                if ($tracker) {
                    $tracker->stopTracking(
                        'delete',
                        get_class($model),
                        $model->id,
                        1
                    );
                    session()->forget('performance_tracker_' . $trackerId);
                }
                static::$performanceTrackerId = null;
            }
        });

        // Track bulk operations
        static::saving(function ($model) {
            if (self::shouldTrackPerformance() && self::shouldTrackModelChanges($model)) {
                $tracker = App::make(DatabasePerformanceTrackerService::class);
                $tracker->startTracking();
                // Store tracker in session for this model instance
                $modelId = uniqid('model_', true);
                session(['performance_tracker_' . $modelId => $tracker]);
                static::$performanceTrackerId = $modelId;
            }
        });

        static::saved(function ($model) {
            if (self::shouldTrackPerformance() && isset(static::$performanceTrackerId)) {
                $trackerId = static::$performanceTrackerId;
                $tracker = session('performance_tracker_' . $trackerId);
                if ($tracker && !$model->wasRecentlyCreated) {
                    // This was an update operation
                    $tracker->stopTracking(
                        'update',
                        get_class($model),
                        $model->id,
                        1
                    );
                }
                if ($tracker) {
                    session()->forget('performance_tracker_' . $trackerId);
                }
                static::$performanceTrackerId = null;
            }
        });
    }

    /**
     * Static property to store tracker ID
     */
    protected static $performanceTrackerId = null;

    /**
     * Check if performance tracking should be enabled for this model
     */
    protected static function shouldTrackPerformance(): bool
    {
        // Check if tracking is enabled globally
        if (!config('database.performance_tracking.enabled', true)) {
            return false;
        }

        // Check if this specific model should be tracked
        $excludedModels = config('database.performance_tracking.excluded_models', []);
        if (in_array(static::class, $excludedModels)) {
            return false;
        }

        // Check if we're in a testing environment
        if (app()->environment('testing')) {
            return config('database.performance_tracking.track_in_testing', false);
        }

        return true;
    }

    /**
     * Check if model has meaningful changes that warrant tracking
     */
    protected static function shouldTrackModelChanges(Model $model): bool
    {
        // If model doesn't exist, always track (new model)
        if (!$model->exists) {
            return true;
        }

        // Check if model has any changes
        $changes = $model->getChanges();

        // Exclude insignificant fields
        $insignificantFields = [
            'updated_at',
            'updated_by',
            'created_at',
            'created_by'
        ];

        $significantChanges = array_diff_key($changes, array_flip($insignificantFields));

        return !empty($significantChanges);
    }

    /**
     * Track a custom operation manually
     */
    public function trackOperation(string $operationType, callable $operation, ?string $errorMessage = null)
    {
        if (!self::shouldTrackPerformance()) {
            return $operation();
        }

        $tracker = App::make(DatabasePerformanceTrackerService::class);
        return $tracker->trackBulkOperation(
            $operationType,
            get_class($this),
            [$this->toArray()],
            $operation,
            $errorMessage
        );
    }

    /**
     * Track bulk operations
     */
    public static function trackBulkOperation(string $operationType, array $data, callable $operation, ?string $errorMessage = null)
    {
        if (!self::shouldTrackPerformance()) {
            return $operation();
        }

        $tracker = App::make(DatabasePerformanceTrackerService::class);
        return $tracker->trackBulkOperation(
            $operationType,
            static::class,
            $data,
            $operation,
            $errorMessage
        );
    }

    /**
     * Track database transaction
     */
    public static function trackTransaction(callable $transaction, ?string $errorMessage = null)
    {
        if (!self::shouldTrackPerformance()) {
            return \Illuminate\Support\Facades\DB::transaction($transaction);
        }

        $tracker = App::make(DatabasePerformanceTrackerService::class);
        return $tracker->trackTransaction(
            static::class,
            $transaction,
            $errorMessage
        );
    }
}
