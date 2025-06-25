<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Mail\Alert\FeedStatsAlert;
use App\Mail\Alert\FeedUsageAlert;
use App\Services\Alert\AlertService;
use App\Services\Alert\FeedAlertService;
use Illuminate\Http\Request;

class AlertPreviewController extends Controller
{
    protected $alertService;
    protected $feedAlertService;

    public function __construct(AlertService $alertService, FeedAlertService $feedAlertService)
    {
        $this->alertService = $alertService;
        $this->feedAlertService = $feedAlertService;
    }

    /**
     * Show alert email preview
     */
    public function preview(Request $request, string $type = 'feed-stats')
    {
        // Handle feed-specific alerts
        if (str_starts_with($type, 'feed-')) {
            return $this->previewFeedAlert($request, $type);
        }

        // Handle generic alerts (extensible for future alert types)
        return $this->previewGenericAlert($request, $type);
    }

    /**
     * Preview feed-specific alerts
     */
    private function previewFeedAlert(Request $request, string $type)
    {
        switch ($type) {
            case 'feed-stats':
                return $this->previewFeedStatsAlert($request);

            case 'feed-usage-created':
                return $this->previewFeedUsageAlert($request, 'created');

            case 'feed-usage-updated':
                return $this->previewFeedUsageAlert($request, 'updated');

            case 'feed-usage-deleted':
                return $this->previewFeedUsageAlert($request, 'deleted');

            default:
                return response()->json(['error' => 'Unknown feed alert type'], 404);
        }
    }

    /**
     * Preview generic alerts (extensible for future implementations)
     */
    private function previewGenericAlert(Request $request, string $type)
    {
        // Placeholder for future generic alert types
        // Example: system-error, user-login, data-backup, etc.

        return response()->json([
            'error' => 'Generic alert type not implemented yet',
            'type' => $type,
            'message' => 'This alert type will be available in future versions'
        ], 501);
    }

    /**
     * Preview feed stats discrepancy alert
     */
    private function previewFeedStatsAlert(Request $request)
    {
        $sampleData = [
            'type' => FeedAlertService::TYPE_FEED_STATS_DISCREPANCY,
            'level' => AlertService::LEVEL_CRITICAL,
            'title' => 'Feed Stats Discrepancy Detected',
            'message' => 'Feed consumption statistics do not match actual usage data',
            'data' => [
                'livestock_id' => '9f30ef47-6bf7-4512-ade0-3c2ceb265a91',
                'livestock_name' => 'PR-DF01-K01-DF01-19062025',
                'batch_id' => '9f30ef47-7548-436a-aa84-9a7f77f7726a',
                'batch_name' => 'Batch 001',
                'current_stats' => [
                    'total_consumed' => 650.0,
                    'total_cost' => 4875000.0,
                    'usage_count' => 3,
                    'last_updated' => '2025-06-20T09:07:05.286483Z'
                ],
                'actual_stats' => [
                    'total_consumed' => 850.0,
                    'total_cost' => 4675000.0,
                    'usage_count' => 2
                ],
                'discrepancies' => [
                    'quantity_diff' => -200.0,
                    'cost_diff' => 200000.0,
                    'count_diff' => 1
                ],
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name ?? 'System Administrator',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent()
            ]
        ];

        $mail = new FeedStatsAlert($sampleData);
        return $mail->render();
    }

    /**
     * Preview feed usage alert
     */
    private function previewFeedUsageAlert(Request $request, string $action)
    {
        $types = [
            'created' => FeedAlertService::TYPE_FEED_USAGE_CREATED,
            'updated' => FeedAlertService::TYPE_FEED_USAGE_UPDATED,
            'deleted' => FeedAlertService::TYPE_FEED_USAGE_DELETED,
        ];

        $levels = [
            'created' => AlertService::LEVEL_INFO,
            'updated' => AlertService::LEVEL_WARNING,
            'deleted' => AlertService::LEVEL_ERROR,
        ];

        $sampleData = [
            'type' => $types[$action],
            'level' => $levels[$action],
            'title' => 'Feed Usage ' . ucfirst($action),
            'message' => "Feed usage has been {$action}",
            'data' => [
                'feed_usage_id' => '9f32b7b1-9ed0-4da4-acdd-b8a6179d691d',
                'livestock_id' => '9f30ef47-6bf7-4512-ade0-3c2ceb265a91',
                'livestock_name' => 'PR-DF01-K01-DF01-19062025',
                'batch_id' => '9f30ef47-7548-436a-aa84-9a7f77f7726a',
                'batch_name' => 'Batch 001',
                'usage_date' => '2025-06-20',
                'usage_purpose' => 'feeding',
                'total_quantity' => 1200.0,
                'total_cost' => 9000000.0,
                'manual_stocks' => [
                    [
                        'feed_name' => 'Pakan Starter BR-1',
                        'quantity' => 500.0,
                        'cost_per_unit' => 7500.0,
                        'line_cost' => 3750000.0,
                        'note' => 'Regular feeding schedule'
                    ],
                    [
                        'feed_name' => 'Pakan Grower BR-2',
                        'quantity' => 700.0,
                        'cost_per_unit' => 7500.0,
                        'line_cost' => 5250000.0,
                        'note' => 'Additional feeding for growth'
                    ]
                ],
                'was_edit_mode' => $action === 'updated',
                'user_id' => auth()->id(),
                'user_name' => auth()->user()->name ?? 'System Administrator',
                'ip_address' => request()->ip(),
                'timestamp' => now()->toISOString()
            ]
        ];

        // Add before/after stats for updated action
        if ($action === 'updated') {
            $sampleData['data']['old_stats'] = [
                'total_consumed' => 850.0,
                'total_cost' => 4675000.0,
                'usage_count' => 2
            ];
            $sampleData['data']['new_stats'] = [
                'total_consumed' => 1200.0,
                'total_cost' => 9000000.0,
                'usage_count' => 2
            ];
        }

        $mail = new FeedUsageAlert($sampleData);
        return $mail->render();
    }

    /**
     * Show alert preview selection page
     */
    public function index()
    {
        $alertTypes = [
            // Feed Alert Types
            'feed' => [
                'title' => 'Feed Management Alerts',
                'description' => 'Alerts related to feed usage, statistics, and consumption',
                'alerts' => [
                    'feed-stats' => [
                        'title' => 'Feed Stats Discrepancy Alert',
                        'description' => 'Critical alert when feed consumption statistics don\'t match actual usage data',
                        'level' => 'critical',
                        'icon' => '🚨'
                    ],
                    'feed-usage-created' => [
                        'title' => 'Feed Usage Created Alert',
                        'description' => 'Informational alert when new feed usage is recorded',
                        'level' => 'info',
                        'icon' => '📝'
                    ],
                    'feed-usage-updated' => [
                        'title' => 'Feed Usage Updated Alert',
                        'description' => 'Warning alert when existing feed usage is modified',
                        'level' => 'warning',
                        'icon' => '✏️'
                    ],
                    'feed-usage-deleted' => [
                        'title' => 'Feed Usage Deleted Alert',
                        'description' => 'Error alert when feed usage record is deleted',
                        'level' => 'error',
                        'icon' => '🗑️'
                    ]
                ]
            ],

            // Placeholder for future alert categories
            'system' => [
                'title' => 'System Alerts',
                'description' => 'System-wide alerts and notifications',
                'alerts' => [
                    // Future implementation
                ],
                'coming_soon' => true
            ],

            'user' => [
                'title' => 'User Activity Alerts',
                'description' => 'User login, logout, and activity alerts',
                'alerts' => [
                    // Future implementation
                ],
                'coming_soon' => true
            ]
        ];

        return view('pages.alerts.preview-index', compact('alertTypes'));
    }

    /**
     * Test alert system
     */
    public function test(Request $request)
    {
        $type = $request->get('type', 'feed-usage');
        $action = $request->get('action', 'created');

        try {
            if ($type === 'feed-stats') {
                $result = $this->feedAlertService->sendFeedStatsDiscrepancyAlert([
                    'livestock_id' => '9f30ef47-6bf7-4512-ade0-3c2ceb265a91',
                    'livestock_name' => 'PR-DF01-K01-DF01-19062025 [TEST]',
                    'batch_id' => '9f30ef47-7548-436a-aa84-9a7f77f7726a',
                    'batch_name' => 'Batch 001',
                    'current_stats' => [
                        'total_consumed' => 650.0,
                        'total_cost' => 4875000.0,
                        'usage_count' => 3,
                        'last_updated' => '2025-06-20T09:07:05.286483Z'
                    ],
                    'actual_stats' => [
                        'total_consumed' => 850.0,
                        'total_cost' => 4675000.0,
                        'usage_count' => 2
                    ],
                    'discrepancies' => [
                        'quantity_diff' => -200.0,
                        'cost_diff' => 200000.0,
                        'count_diff' => 1
                    ],
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name ?? 'System Administrator [TEST]',
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ]);
            } else {
                $result = $this->feedAlertService->sendFeedUsageAlert($action, [
                    'feed_usage_id' => '9f32b7b1-9ed0-4da4-acdd-b8a6179d691d',
                    'livestock_id' => '9f30ef47-6bf7-4512-ade0-3c2ceb265a91',
                    'livestock_name' => 'PR-DF01-K01-DF01-19062025 [TEST]',
                    'batch_id' => '9f30ef47-7548-436a-aa84-9a7f77f7726a',
                    'batch_name' => 'Batch 001',
                    'usage_date' => '2025-06-20',
                    'usage_purpose' => 'feeding',
                    'total_quantity' => 1200.0,
                    'total_cost' => 9000000.0,
                    'manual_stocks' => [
                        [
                            'feed_name' => 'Pakan Starter BR-1 [TEST]',
                            'quantity' => 500.0,
                            'cost_per_unit' => 7500.0,
                            'line_cost' => 3750000.0,
                            'note' => 'Test alert - Regular feeding schedule'
                        ],
                        [
                            'feed_name' => 'Pakan Grower BR-2 [TEST]',
                            'quantity' => 700.0,
                            'cost_per_unit' => 7500.0,
                            'line_cost' => 5250000.0,
                            'note' => 'Test alert - Additional feeding for growth'
                        ]
                    ],
                    'was_edit_mode' => $action === 'updated',
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name ?? 'System Administrator [TEST]',
                    'ip_address' => request()->ip(),
                    'timestamp' => now()->toISOString()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Test alert sent successfully',
                'result' => $result,
                'type' => $type,
                'action' => $action
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send test alert',
                'error' => $e->getMessage(),
                'type' => $type,
                'action' => $action
            ], 500);
        }
    }
}
