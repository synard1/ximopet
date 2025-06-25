<?php

namespace App\Services\Alert;

use App\Mail\Alert\FeedStatsAlert;
use App\Mail\Alert\FeedUsageAlert;

class FeedAlertService extends AlertService
{
    // Feed-specific Alert Types
    const TYPE_FEED_STATS_DISCREPANCY = 'feed_stats_discrepancy';
    const TYPE_FEED_USAGE_CREATED = 'feed_usage_created';
    const TYPE_FEED_USAGE_UPDATED = 'feed_usage_updated';
    const TYPE_FEED_USAGE_DELETED = 'feed_usage_deleted';
    const TYPE_FEED_CONSUMPTION_ANOMALY = 'feed_consumption_anomaly';

    /**
     * Send feed stats discrepancy alert
     */
    public function sendFeedStatsDiscrepancyAlert(array $data): bool
    {
        return $this->sendGenericAlert(
            self::TYPE_FEED_STATS_DISCREPANCY,
            self::LEVEL_CRITICAL,
            'Feed Stats Discrepancy Detected',
            'Feed consumption statistics do not match actual usage data',
            $data,
            [
                'recipient_category' => 'feed_stats',
                'mail_class' => FeedStatsAlert::class,
                'throttle' => [
                    'key' => 'feed_stats_' . ($data['livestock_id'] ?? 'unknown'),
                    'minutes' => 60 // Don't send same alert more than once per hour
                ]
            ]
        );
    }

    /**
     * Send feed usage activity alert
     */
    public function sendFeedUsageAlert(string $action, array $data): bool
    {
        $types = [
            'created' => self::TYPE_FEED_USAGE_CREATED,
            'updated' => self::TYPE_FEED_USAGE_UPDATED,
            'deleted' => self::TYPE_FEED_USAGE_DELETED,
        ];

        $levels = [
            'created' => self::LEVEL_INFO,
            'updated' => self::LEVEL_WARNING,
            'deleted' => self::LEVEL_ERROR,
        ];

        return $this->sendGenericAlert(
            $types[$action] ?? self::TYPE_FEED_USAGE_CREATED,
            $levels[$action] ?? self::LEVEL_INFO,
            'Feed Usage ' . ucfirst($action),
            "Feed usage has been {$action}",
            $data,
            [
                'recipient_category' => 'feed_usage',
                'mail_class' => FeedUsageAlert::class,
                'throttle' => [
                    'key' => 'feed_usage_' . $action . '_' . ($data['livestock_id'] ?? 'unknown'),
                    'minutes' => 5 // Allow multiple alerts but throttle spam
                ]
            ]
        );
    }

    /**
     * Send feed consumption anomaly alert
     */
    public function sendFeedConsumptionAnomalyAlert(array $data): bool
    {
        return $this->sendGenericAlert(
            self::TYPE_FEED_CONSUMPTION_ANOMALY,
            self::LEVEL_WARNING,
            'Feed Consumption Anomaly Detected',
            'Unusual feed consumption pattern detected',
            $data,
            [
                'recipient_category' => 'anomaly',
                'mail_class' => FeedStatsAlert::class,
                'throttle' => [
                    'key' => 'anomaly_' . ($data['livestock_id'] ?? 'unknown'),
                    'minutes' => 30
                ]
            ]
        );
    }

    /**
     * Get appropriate mail class for feed alert types
     */
    protected function getDefaultMailClass(string $type): string
    {
        $mailClasses = [
            self::TYPE_FEED_STATS_DISCREPANCY => FeedStatsAlert::class,
            self::TYPE_FEED_USAGE_CREATED => FeedUsageAlert::class,
            self::TYPE_FEED_USAGE_UPDATED => FeedUsageAlert::class,
            self::TYPE_FEED_USAGE_DELETED => FeedUsageAlert::class,
            self::TYPE_FEED_CONSUMPTION_ANOMALY => FeedStatsAlert::class,
        ];

        return $mailClasses[$type] ?? FeedStatsAlert::class;
    }

    /**
     * Check feed usage anomalies based on quantity and cost thresholds
     */
    public function checkFeedUsageAnomalies(array $feedUsageData): bool
    {
        $largeQuantityThreshold = config('alerts.feed_usage.large_quantity_threshold', 1000);
        $highCostThreshold = config('alerts.feed_usage.high_cost_threshold', 10000000);

        $totalQuantity = $feedUsageData['total_quantity'] ?? 0;
        $totalCost = $feedUsageData['total_cost'] ?? 0;

        $isAnomalous = false;
        $anomalyReasons = [];

        // Check for large quantity usage
        if (config('alerts.feed_usage.alert_on_large_quantity', true) && $totalQuantity > $largeQuantityThreshold) {
            $isAnomalous = true;
            $anomalyReasons[] = "Large quantity usage: {$totalQuantity} kg (threshold: {$largeQuantityThreshold} kg)";
        }

        // Check for high cost usage
        if (config('alerts.feed_usage.alert_on_high_cost', true) && $totalCost > $highCostThreshold) {
            $isAnomalous = true;
            $anomalyReasons[] = "High cost usage: Rp " . number_format($totalCost, 0, ',', '.') .
                " (threshold: Rp " . number_format($highCostThreshold, 0, ',', '.') . ")";
        }

        if ($isAnomalous) {
            $anomalyData = array_merge($feedUsageData, [
                'anomaly_reasons' => $anomalyReasons,
                'thresholds' => [
                    'quantity_threshold' => $largeQuantityThreshold,
                    'cost_threshold' => $highCostThreshold
                ]
            ]);

            return $this->sendFeedConsumptionAnomalyAlert($anomalyData);
        }

        return false;
    }

    /**
     * Send feed usage activity alert with anomaly check
     */
    public function sendFeedUsageAlertWithAnomalyCheck(string $action, array $data): bool
    {
        // Send the main feed usage alert
        $mainAlertResult = $this->sendFeedUsageAlert($action, $data);

        // Check for anomalies (only for created and updated actions)
        $anomalyResult = true;
        if (in_array($action, ['created', 'updated'])) {
            $anomalyResult = $this->checkFeedUsageAnomalies($data);
        }

        return $mainAlertResult;
    }
}
