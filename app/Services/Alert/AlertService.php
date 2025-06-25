<?php

namespace App\Services\Alert;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Models\AlertLog;
use Carbon\Carbon;

class AlertService
{
    // Alert Levels (Universal)
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';

    // Alert Channels (Universal)
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_LOG = 'log';
    const CHANNEL_DATABASE = 'database';
    const CHANNEL_SLACK = 'slack';
    const CHANNEL_SMS = 'sms';

    /**
     * Send alert with comprehensive configuration
     */
    public function sendAlert(array $config): bool
    {
        try {
            // Validate configuration
            $this->validateAlertConfig($config);

            // Check if alert should be throttled
            if ($this->shouldThrottle($config)) {
                Log::info('Alert throttled', ['config' => $config]);
                return false;
            }

            // Process alert through all configured channels
            $results = [];
            $channels = $config['channels'] ?? [self::CHANNEL_EMAIL, self::CHANNEL_LOG, self::CHANNEL_DATABASE];

            foreach ($channels as $channel) {
                $results[$channel] = $this->processChannel($channel, $config);
            }

            // Log alert activity
            $this->logAlertActivity($config, $results);

            // Update throttle cache
            $this->updateThrottle($config);

            return !in_array(false, $results, true);
        } catch (\Exception $e) {
            Log::error('Alert service error', [
                'error' => $e->getMessage(),
                'config' => $config,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Send generic alert with type, level, title, message, and data
     */
    public function sendGenericAlert(string $type, string $level, string $title, string $message, array $data = [], array $options = []): bool
    {
        $config = array_merge([
            'type' => $type,
            'level' => $level,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'channels' => $options['channels'] ?? $this->getDefaultChannelsForLevel($level),
            'recipients' => $options['recipients'] ?? $this->getRecipients($options['recipient_category'] ?? 'default'),
            'throttle' => $options['throttle'] ?? null,
            'mail_class' => $options['mail_class'] ?? null,
        ], $options);

        return $this->sendAlert($config);
    }

    /**
     * Process alert through specific channel
     */
    private function processChannel(string $channel, array $config): bool
    {
        switch ($channel) {
            case self::CHANNEL_EMAIL:
                return $this->sendEmailAlert($config);

            case self::CHANNEL_LOG:
                return $this->logAlert($config);

            case self::CHANNEL_DATABASE:
                return $this->saveAlertToDatabase($config);

            case self::CHANNEL_SLACK:
                return $this->sendSlackAlert($config);

            case self::CHANNEL_SMS:
                return $this->sendSmsAlert($config);

            default:
                Log::warning('Unknown alert channel', ['channel' => $channel]);
                return false;
        }
    }

    /**
     * Send email alert
     */
    private function sendEmailAlert(array $config): bool
    {
        try {
            $recipients = $config['recipients'] ?? [];

            if (empty($recipients)) {
                Log::warning('No email recipients configured for alert', ['config' => $config]);
                return false;
            }

            // Use provided mail class or get default based on type
            $mailClass = $config['mail_class'] ?? $this->getDefaultMailClass($config['type'] ?? 'generic');

            if (!class_exists($mailClass)) {
                Log::error('Mail class not found', ['mail_class' => $mailClass, 'config' => $config]);
                return false;
            }

            foreach ($recipients as $recipient) {
                Mail::to($recipient)->send(new $mailClass($config));
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Email alert failed', [
                'error' => $e->getMessage(),
                'config' => $config
            ]);
            return false;
        }
    }

    /**
     * Log alert to Laravel log
     */
    private function logAlert(array $config): bool
    {
        $level = $config['level'] ?? self::LEVEL_INFO;
        $message = '[ALERT] ' . ($config['title'] ?? 'Alert') . ': ' . ($config['message'] ?? 'No message');

        switch ($level) {
            case self::LEVEL_CRITICAL:
                Log::critical($message, $config);
                break;
            case self::LEVEL_ERROR:
                Log::error($message, $config);
                break;
            case self::LEVEL_WARNING:
                Log::warning($message, $config);
                break;
            default:
                Log::info($message, $config);
        }

        return true;
    }

    /**
     * Save alert to database
     */
    private function saveAlertToDatabase(array $config): bool
    {
        try {
            AlertLog::create([
                'type' => $config['type'] ?? 'unknown',
                'level' => $config['level'] ?? self::LEVEL_INFO,
                'title' => $config['title'] ?? 'Alert',
                'message' => $config['message'] ?? 'No message',
                'data' => $config['data'] ?? [],
                'metadata' => [
                    'channels' => $config['channels'] ?? [],
                    'recipients' => $config['recipients'] ?? [],
                    'timestamp' => now()->toISOString(),
                    'user_id' => auth()->id(),
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent()
                ],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Database alert failed', [
                'error' => $e->getMessage(),
                'config' => $config
            ]);
            return false;
        }
    }

    /**
     * Send Slack alert (placeholder for future implementation)
     */
    private function sendSlackAlert(array $config): bool
    {
        // TODO: Implement Slack integration
        Log::info('Slack alert (not implemented)', $config);
        return true;
    }

    /**
     * Send SMS alert (placeholder for future implementation)
     */
    private function sendSmsAlert(array $config): bool
    {
        // TODO: Implement SMS integration
        Log::info('SMS alert (not implemented)', $config);
        return true;
    }

    /**
     * Get default mail class for alert type (can be overridden by specific services)
     */
    protected function getDefaultMailClass(string $type): string
    {
        // Return a generic mail class - specific services should override this
        return config('alerts.default_mail_class', \App\Mail\Alert\GenericAlert::class);
    }

    /**
     * Get default channels for alert level
     */
    protected function getDefaultChannelsForLevel(string $level): array
    {
        $levelConfig = config("alerts.levels.{$level}", []);
        return $levelConfig['channels'] ?? [self::CHANNEL_LOG, self::CHANNEL_DATABASE];
    }

    /**
     * Get recipients for alert category
     */
    protected function getRecipients(string $alertCategory): array
    {
        $config = config('alerts.recipients', []);

        return $config[$alertCategory] ?? $config['default'] ?? [
            config('mail.from.address', 'noreply@example.com')
        ];
    }

    /**
     * Check if alert should be throttled
     */
    private function shouldThrottle(array $config): bool
    {
        if (!isset($config['throttle']) || !config('alerts.throttling.enabled', true)) {
            return false;
        }

        $throttle = $config['throttle'];
        $key = config('alerts.throttling.cache_prefix', 'alert_throttle_') . ($throttle['key'] ?? 'default');

        return Cache::has($key);
    }

    /**
     * Update throttle cache
     */
    private function updateThrottle(array $config): void
    {
        if (!isset($config['throttle']) || !config('alerts.throttling.enabled', true)) {
            return;
        }

        $throttle = $config['throttle'];
        $key = config('alerts.throttling.cache_prefix', 'alert_throttle_') . ($throttle['key'] ?? 'default');
        $minutes = $throttle['minutes'] ?? config('alerts.throttling.default_minutes', 60);

        Cache::put($key, true, now()->addMinutes($minutes));
    }

    /**
     * Validate alert configuration
     */
    private function validateAlertConfig(array $config): void
    {
        $required = ['type', 'title', 'message'];

        foreach ($required as $field) {
            if (!isset($config[$field])) {
                throw new \InvalidArgumentException("Alert configuration missing required field: {$field}");
            }
        }
    }

    /**
     * Log alert activity
     */
    private function logAlertActivity(array $config, array $results): void
    {
        Log::info('Alert processed', [
            'type' => $config['type'],
            'level' => $config['level'] ?? self::LEVEL_INFO,
            'title' => $config['title'],
            'channels' => array_keys($results),
            'results' => $results,
            'timestamp' => now()->toISOString()
        ]);
    }

    /**
     * Get alert statistics
     */
    public function getAlertStats(int $days = 7): array
    {
        $startDate = now()->subDays($days);

        return [
            'total_alerts' => AlertLog::where('created_at', '>=', $startDate)->count(),
            'by_type' => AlertLog::where('created_at', '>=', $startDate)
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'by_level' => AlertLog::where('created_at', '>=', $startDate)
                ->selectRaw('level, COUNT(*) as count')
                ->groupBy('level')
                ->pluck('count', 'level')
                ->toArray(),
            'recent_alerts' => AlertLog::where('created_at', '>=', $startDate)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->toArray()
        ];
    }
}
