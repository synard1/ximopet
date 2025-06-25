@props(['notificationSettings', 'livewireComponent'])
<div>
    <h4 class="mb-3">Event Notifications</h4>
    <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" wire:model="notificationSettings.events.purchase.enabled">
        <label class="form-check-label">Enable Purchase Notifications</label>
    </div>
    <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" wire:model="notificationSettings.events.mutation.enabled">
        <label class="form-check-label">Enable Mutation Notifications</label>
    </div>
    <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" wire:model="notificationSettings.events.usage.enabled">
        <label class="form-check-label">Enable Usage Notifications</label>
    </div>
</div>