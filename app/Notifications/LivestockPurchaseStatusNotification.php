<?php

namespace App\Notifications;

use App\Events\LivestockPurchaseStatusChanged;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class LivestockPurchaseStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private LivestockPurchaseStatusChanged $event;

    /**
     * Create a new notification instance.
     */
    public function __construct(LivestockPurchaseStatusChanged $event)
    {
        $this->event = $event;

        // Set queue priority based on event priority
        $this->onQueue($this->getQueueName($event->metadata['priority'] ?? 'normal'));

        Log::info('LivestockPurchaseStatusNotification created', [
            'batch_id' => $event->batch->id,
            'old_status' => $event->oldStatus,
            'new_status' => $event->newStatus,
            'priority' => $event->metadata['priority'] ?? 'normal'
        ]);
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Add broadcast for real-time updates
        $channels[] = 'broadcast';

        // Add mail for high priority notifications
        if (($this->event->metadata['priority'] ?? 'normal') === 'high') {
            $channels[] = 'mail';
        }

        Log::info('LivestockPurchase notification channels', [
            'batch_id' => $this->event->batch->id,
            'notifiable_id' => $notifiable->id,
            'channels' => $channels
        ]);

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusLabels = \App\Models\LivestockPurchase::STATUS_LABELS;
        $oldLabel = $statusLabels[$this->event->oldStatus] ?? $this->event->oldStatus;
        $newLabel = $statusLabels[$this->event->newStatus] ?? $this->event->newStatus;

        $mailMessage = (new MailMessage)
            ->subject('Livestock Purchase Status Update - #' . $this->event->metadata['invoice_number'])
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A livestock purchase status has been updated:')
            ->line('**Invoice:** ' . $this->event->metadata['invoice_number'])
            ->line('**Supplier:** ' . $this->event->metadata['supplier_name'])
            ->line('**Farm:** ' . $this->event->metadata['farm_name'])
            ->line('**Kandang:** ' . $this->event->metadata['coop_name'])
            ->line('**Status Changed:** ' . $oldLabel . ' → ' . $newLabel)
            ->line('**Updated By:** ' . $this->event->metadata['updated_by_name'])
            ->line('**Total Value:** Rp ' . number_format($this->event->metadata['total_value'], 2, ',', '.'));

        if ($this->event->notes) {
            $mailMessage->line('**Notes:** ' . $this->event->notes);
        }

        // Add action button based on new status
        if ($this->event->newStatus === 'in_coop') {
            $mailMessage->action('View Livestock Details', url('/transaction/livestock-purchases'))
                ->line('Livestock has been placed in coop. Please review the details.');
        } elseif ($this->event->newStatus === 'cancelled') {
            $mailMessage->action('Review Cancellation', url('/transaction/livestock-purchases'))
                ->line('This purchase has been cancelled. Please review the details.');
        } else {
            $mailMessage->action('View Purchase Details', url('/transaction/livestock-purchases'));
        }

        if ($this->event->metadata['requires_refresh']) {
            $mailMessage->line('⚠️ **Important:** Please refresh your data to see the latest changes.');
        }

        $mailMessage->line('Thank you for using our livestock management system!');

        Log::info('Mail notification prepared', [
            'batch_id' => $this->event->batch->id,
            'notifiable_id' => $notifiable->id,
            'subject' => 'Livestock Purchase Status Update - #' . $this->event->metadata['invoice_number']
        ]);

        return $mailMessage;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $data = [
            'type' => 'livestock_purchase_status_changed',
            'title' => 'Livestock Purchase Status Updated',
            'message' => $this->getNotificationMessage(),
            'batch_id' => $this->event->batch->id,
            'old_status' => $this->event->oldStatus,
            'new_status' => $this->event->newStatus,
            'updated_by' => $this->event->updatedBy,
            'timestamp' => $this->event->timestamp,
            'metadata' => $this->event->metadata,
            'action_required' => $this->getActionRequired(),
            'priority' => $this->event->metadata['priority'] ?? 'normal',
            'read_at' => null,
            'action_url' => url('/transaction/livestock-purchases?batch_id=' . $this->event->batch->id),
        ];

        Log::info('Database notification data prepared', [
            'batch_id' => $this->event->batch->id,
            'notifiable_id' => $notifiable->id,
            'priority' => $data['priority']
        ]);

        return $data;
    }

    /**
     * Get the broadcast representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        $data = [
            'id' => $this->id,
            'type' => 'livestock_purchase_status_changed',
            'title' => 'Livestock Purchase Status Updated',
            'message' => $this->getNotificationMessage(),
            'batch_id' => $this->event->batch->id,
            'old_status' => $this->event->oldStatus,
            'new_status' => $this->event->newStatus,
            'updated_by' => $this->event->updatedBy,
            'timestamp' => $this->event->timestamp,
            'metadata' => $this->event->metadata,
            'action_required' => $this->getActionRequired(),
            'priority' => $this->event->metadata['priority'] ?? 'normal',
            'action_url' => url('/transaction/livestock-purchases?batch_id=' . $this->event->batch->id),
            'created_at' => now()->toISOString(),
        ];

        Log::info('Broadcast notification prepared', [
            'batch_id' => $this->event->batch->id,
            'notifiable_id' => $notifiable->id,
            'notification_id' => $this->id
        ]);

        return new BroadcastMessage($data);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Get user-friendly notification message
     */
    private function getNotificationMessage(): string
    {
        $statusLabels = \App\Models\LivestockPurchase::STATUS_LABELS;
        $oldLabel = $statusLabels[$this->event->oldStatus] ?? $this->event->oldStatus;
        $newLabel = $statusLabels[$this->event->newStatus] ?? $this->event->newStatus;

        return sprintf(
            'Livestock Purchase #%s status changed from %s to %s by %s',
            $this->event->metadata['invoice_number'],
            $oldLabel,
            $newLabel,
            $this->event->metadata['updated_by_name']
        );
    }

    /**
     * Get required actions for users
     */
    private function getActionRequired(): array
    {
        $actions = [];

        if ($this->event->metadata['requires_refresh']) {
            $actions[] = 'refresh_data';
        }

        if ($this->event->newStatus === 'in_coop') {
            $actions[] = 'livestock_updated';
        }

        if ($this->event->newStatus === 'cancelled') {
            $actions[] = 'review_cancellation';
        }

        return $actions;
    }

    /**
     * Get appropriate queue name based on priority
     */
    private function getQueueName(string $priority): string
    {
        return match ($priority) {
            'high' => 'notifications-high',
            'medium' => 'notifications-medium',
            'low' => 'notifications-low',
            default => 'notifications'
        };
    }

    /**
     * Determine the notification's delivery delay.
     */
    public function withDelay(object $notifiable): \DateTimeInterface|\DateInterval|array|null
    {
        // Immediate delivery for high priority notifications
        if (($this->event->metadata['priority'] ?? 'normal') === 'high') {
            return null;
        }

        // Small delay for other notifications to batch them
        return now()->addSeconds(30);
    }
}
 