<?php

namespace App\Notifications;

use App\Events\SupplyPurchaseStatusChanged;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class SupplyPurchaseStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $event;

    /**
     * Create a new notification instance.
     */
    public function __construct(SupplyPurchaseStatusChanged $event)
    {
        $this->event = $event;

        Log::info('SupplyPurchaseStatusNotification created', [
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
        $channels = ['database', 'broadcast'];

        // Add email for high priority notifications
        if (($this->event->metadata['priority'] ?? 'normal') === 'high') {
            $channels[] = 'mail';
        }

        Log::info('Notification channels determined', [
            'batch_id' => $this->event->batch->id,
            'channels' => $channels,
            'notifiable_id' => $notifiable->id
        ]);

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $statusLabels = \App\Models\SupplyPurchaseBatch::STATUS_LABELS;
        $oldLabel = $statusLabels[$this->event->oldStatus] ?? $this->event->oldStatus;
        $newLabel = $statusLabels[$this->event->newStatus] ?? $this->event->newStatus;

        $mailMessage = (new MailMessage)
            ->subject('Supply Purchase Status Update - #' . $this->event->metadata['invoice_number'])
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('A supply purchase status has been updated:')
            ->line('**Invoice:** ' . $this->event->metadata['invoice_number'])
            ->line('**Supplier:** ' . $this->event->metadata['supplier_name'])
            ->line('**Status Changed:** ' . $oldLabel . ' → ' . $newLabel)
            ->line('**Updated By:** ' . $this->event->metadata['updated_by_name'])
            ->line('**Total Value:** Rp ' . number_format($this->event->metadata['total_value'], 2, ',', '.'));

        if ($this->event->notes) {
            $mailMessage->line('**Notes:** ' . $this->event->notes);
        }

        // Add action button based on new status
        if ($this->event->newStatus === 'arrived') {
            $mailMessage->action('View Stock Details', url('/transaction/supply-purchases'))
                ->line('Stock has been updated. Please review the arrival details.');
        } elseif ($this->event->newStatus === 'cancelled') {
            $mailMessage->action('Review Cancellation', url('/transaction/supply-purchases'))
                ->line('This purchase has been cancelled. Please review the details.');
        } else {
            $mailMessage->action('View Purchase Details', url('/transaction/supply-purchases'));
        }

        if ($this->event->metadata['requires_refresh']) {
            $mailMessage->line('⚠️ **Important:** Please refresh your data to see the latest changes.');
        }

        $mailMessage->line('Thank you for using our supply management system!');

        Log::info('Mail notification prepared', [
            'batch_id' => $this->event->batch->id,
            'notifiable_id' => $notifiable->id,
            'subject' => 'Supply Purchase Status Update - #' . $this->event->metadata['invoice_number']
        ]);

        return $mailMessage;
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        $data = [
            'type' => 'supply_purchase_status_changed',
            'title' => 'Supply Purchase Status Updated',
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
            'action_url' => url('/transaction/supply-purchases?batch_id=' . $this->event->batch->id),
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
            'type' => 'supply_purchase_status_changed',
            'title' => 'Supply Purchase Status Updated',
            'message' => $this->getNotificationMessage(),
            'batch_id' => $this->event->batch->id,
            'old_status' => $this->event->oldStatus,
            'new_status' => $this->event->newStatus,
            'updated_by' => $this->event->updatedBy,
            'timestamp' => $this->event->timestamp,
            'metadata' => $this->event->metadata,
            'action_required' => $this->getActionRequired(),
            'priority' => $this->event->metadata['priority'] ?? 'normal',
            'action_url' => url('/transaction/supply-purchases?batch_id=' . $this->event->batch->id),
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
        $statusLabels = \App\Models\SupplyPurchaseBatch::STATUS_LABELS;
        $oldLabel = $statusLabels[$this->event->oldStatus] ?? $this->event->oldStatus;
        $newLabel = $statusLabels[$this->event->newStatus] ?? $this->event->newStatus;

        return sprintf(
            'Supply Purchase #%s status changed from %s to %s by %s',
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

        if ($this->event->newStatus === 'arrived') {
            $actions[] = 'stock_updated';
        }

        if ($this->event->newStatus === 'cancelled') {
            $actions[] = 'review_cancellation';
        }

        return $actions;
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
