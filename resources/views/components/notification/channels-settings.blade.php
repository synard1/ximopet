@props(['notificationSettings', 'livewireComponent'])
<div>
    <h4 class="mb-3">Notification Channels</h4>
    <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" wire:model="notificationSettings.channels.email">
        <label class="form-check-label">Email Notifications</label>
    </div>
    <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" wire:model="notificationSettings.channels.database">
        <label class="form-check-label">Database Notifications</label>
    </div>
    <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" wire:model="notificationSettings.channels.broadcast">
        <label class="form-check-label">Broadcast Notifications</label>
    </div>
</div>