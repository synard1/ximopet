<?php

namespace App\Mail\Alert;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FeedStatsAlert extends Mailable
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
        $this->alertTitle = $config['title'] ?? 'Alert';
        $this->alertMessage = $config['message'] ?? 'No message provided';
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = $this->getSubjectByLevel() . ': ' . $this->alertTitle;
        
        return $this->view('emails.alerts.feed-stats')
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
                        'priorityBadge' => $this->getPriorityBadge()
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

        // Format feed stats discrepancy
        if (isset($data['discrepancies'])) {
            $discrepancies = $data['discrepancies'];
            $formatted['Discrepancies Found'] = [
                'Quantity Difference' => number_format($discrepancies['quantity_diff'] ?? 0, 2) . ' kg',
                'Cost Difference' => 'Rp ' . number_format($discrepancies['cost_diff'] ?? 0, 0, ',', '.'),
                'Usage Count Difference' => $discrepancies['count_diff'] ?? 0
            ];
        }

        // Format current vs actual stats
        if (isset($data['current_stats']) && isset($data['actual_stats'])) {
            $formatted['Current feed_stats'] = [
                'Total Consumed' => number_format($data['current_stats']['total_consumed'] ?? 0, 2) . ' kg',
                'Total Cost' => 'Rp ' . number_format($data['current_stats']['total_cost'] ?? 0, 0, ',', '.'),
                'Usage Count' => $data['current_stats']['usage_count'] ?? 0,
                'Last Updated' => $data['current_stats']['last_updated'] ?? 'N/A'
            ];

            $formatted['Actual Usage Data'] = [
                'Total Consumed' => number_format($data['actual_stats']['total_consumed'] ?? 0, 2) . ' kg',
                'Total Cost' => 'Rp ' . number_format($data['actual_stats']['total_cost'] ?? 0, 0, ',', '.'),
                'Usage Count' => $data['actual_stats']['usage_count'] ?? 0
            ];
        }

        // Format feed usage details
        if (isset($data['feed_usage_id'])) {
            $formatted['Feed Usage Details'] = [
                'Usage ID' => $data['feed_usage_id'],
                'Date' => $data['usage_date'] ?? 'N/A',
                'Quantity' => number_format($data['quantity'] ?? 0, 2) . ' kg',
                'Cost' => 'Rp ' . number_format($data['cost'] ?? 0, 0, ',', '.'),
                'Action' => $data['action'] ?? 'N/A'
            ];
        }

        // Format user information
        if (isset($data['user_id'])) {
            $formatted['User Information'] = [
                'User ID' => $data['user_id'],
                'User Name' => $data['user_name'] ?? 'N/A',
                'IP Address' => $data['ip_address'] ?? 'N/A',
                'User Agent' => substr($data['user_agent'] ?? 'N/A', 0, 100) . '...'
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
     * Get priority badge text
     */
    private function getPriorityBadge(): string
    {
        $badges = [
            'info' => 'Low Priority',
            'warning' => 'Medium Priority',
            'error' => 'High Priority',
            'critical' => 'CRITICAL PRIORITY'
        ];

        return $badges[$this->alertLevel] ?? 'Unknown Priority';
    }
} 