<?php

namespace App\Mail\Alert;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FeedUsageAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $alertConfig;
    public $alertData;
    public $alertType;
    public $alertLevel;
    public $alertTitle;
    public $alertMessage;

    /**
     * Create a new message instance.
     */
    public function __construct(array $config)
    {
        $this->alertConfig = $config;
        $this->alertData = $config['data'] ?? [];
        $this->alertType = $config['type'] ?? 'unknown';
        $this->alertLevel = $config['level'] ?? 'info';
        $this->alertTitle = $config['title'] ?? 'Feed Usage Alert';
        $this->alertMessage = $config['message'] ?? 'Feed usage activity detected';
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->getSubjectByLevel() . ': ' . $this->alertTitle;

        return $this->view('emails.alerts.feed-usage')
            ->subject($subject)
            ->with([
                'alertConfig' => $this->alertConfig,
                'alertData' => $this->alertData,
                'alertType' => $this->alertType,
                'alertLevel' => $this->alertLevel,
                'alertTitle' => $this->alertTitle,
                'alertMessage' => $this->alertMessage,
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'formattedData' => $this->getFormattedData(),
                'levelColor' => $this->getLevelColor(),
                'levelIcon' => $this->getLevelIcon(),
                'actionType' => $this->getActionType(),
                'actionColor' => $this->getActionColor()
            ]);
    }

    /**
     * Get subject prefix based on alert level
     */
    private function getSubjectByLevel(): string
    {
        $prefixes = [
            'info' => '[INFO]',
            'warning' => '[WARNING]',
            'error' => '[ERROR]',
            'critical' => '[CRITICAL]'
        ];

        return $prefixes[$this->alertLevel] ?? '[ALERT]';
    }

    /**
     * Get formatted data for email display
     */
    private function getFormattedData(): array
    {
        $data = $this->alertData;
        $formatted = [];

        // Format livestock information
        if (isset($data['livestock_id'])) {
            $formatted['Livestock Information'] = [
                'ID' => $data['livestock_id'],
                'Name' => $data['livestock_name'] ?? 'N/A',
                'Batch ID' => $data['batch_id'] ?? 'N/A',
                'Batch Name' => $data['batch_name'] ?? 'N/A'
            ];
        }

        // Format feed usage details
        if (isset($data['feed_usage_id'])) {
            $formatted['Feed Usage Details'] = [
                'Usage ID' => $data['feed_usage_id'],
                'Date' => $data['usage_date'] ?? 'N/A',
                'Purpose' => $data['usage_purpose'] ?? 'N/A',
                'Total Quantity' => number_format($data['total_quantity'] ?? 0, 2) . ' kg',
                'Total Cost' => 'Rp ' . number_format($data['total_cost'] ?? 0, 0, ',', '.'),
                'Is Edit Mode' => ($data['was_edit_mode'] ?? false) ? 'Yes' : 'No'
            ];
        }

        // Format feed stocks used
        if (isset($data['manual_stocks']) && is_array($data['manual_stocks'])) {
            $stocks = [];
            foreach ($data['manual_stocks'] as $index => $stock) {
                $stocks["Stock " . ($index + 1)] = [
                    'Feed Name' => $stock['feed_name'] ?? 'N/A',
                    'Quantity Used' => number_format($stock['quantity'] ?? 0, 2) . ' kg',
                    'Cost Per Unit' => 'Rp ' . number_format($stock['cost_per_unit'] ?? 0, 0, ',', '.'),
                    'Line Cost' => 'Rp ' . number_format($stock['line_cost'] ?? 0, 0, ',', '.'),
                    'Notes' => $stock['note'] ?? 'No notes'
                ];
            }
            if (!empty($stocks)) {
                $formatted['Feed Stocks Used'] = $stocks;
            }
        }

        // Format before/after stats for edit mode
        if (isset($data['old_stats']) && isset($data['new_stats'])) {
            $formatted['Feed Stats Changes'] = [
                'Before Edit' => [
                    'Total Consumed' => number_format($data['old_stats']['total_consumed'] ?? 0, 2) . ' kg',
                    'Total Cost' => 'Rp ' . number_format($data['old_stats']['total_cost'] ?? 0, 0, ',', '.'),
                    'Usage Count' => $data['old_stats']['usage_count'] ?? 0
                ],
                'After Edit' => [
                    'Total Consumed' => number_format($data['new_stats']['total_consumed'] ?? 0, 2) . ' kg',
                    'Total Cost' => 'Rp ' . number_format($data['new_stats']['total_cost'] ?? 0, 0, ',', '.'),
                    'Usage Count' => $data['new_stats']['usage_count'] ?? 0
                ]
            ];
        }

        // Format user information
        if (isset($data['user_id'])) {
            $formatted['User Information'] = [
                'User ID' => $data['user_id'],
                'User Name' => $data['user_name'] ?? 'N/A',
                'IP Address' => $data['ip_address'] ?? 'N/A',
                'Timestamp' => $data['timestamp'] ?? now()->format('Y-m-d H:i:s')
            ];
        }

        return $formatted;
    }

    /**
     * Get level color for styling
     */
    private function getLevelColor(): string
    {
        $colors = [
            'info' => '#17a2b8',
            'warning' => '#ffc107',
            'error' => '#dc3545',
            'critical' => '#343a40'
        ];

        return $colors[$this->alertLevel] ?? '#6c757d';
    }

    /**
     * Get level icon
     */
    private function getLevelIcon(): string
    {
        $icons = [
            'info' => '🔵',
            'warning' => '⚠️',
            'error' => '❌',
            'critical' => '🚨'
        ];

        return $icons[$this->alertLevel] ?? '🔔';
    }

    /**
     * Get action type from alert type
     */
    private function getActionType(): string
    {
        $actions = [
            'feed_usage_created' => 'Created',
            'feed_usage_updated' => 'Updated',
            'feed_usage_deleted' => 'Deleted'
        ];

        return $actions[$this->alertType] ?? 'Activity';
    }

    /**
     * Get action color based on action type
     */
    private function getActionColor(): string
    {
        $colors = [
            'feed_usage_created' => '#28a745',
            'feed_usage_updated' => '#ffc107',
            'feed_usage_deleted' => '#dc3545'
        ];

        return $colors[$this->alertType] ?? '#6c757d';
    }
}
