@props(['notificationSettings', 'livewireComponent'])
<div>
    <h4 class="mb-3">Notification Settings</h4>
    {{-- Email Notifications --}}
    <div class="mb-3">
        <h5 class="mb-2">Email Notifications</h5>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" wire:model="notificationSettings.email.enabled">
            <label class="form-check-label">Enable Email Notifications</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox"
                wire:model="notificationSettings.email.require_confirmation">
            <label class="form-check-label">Require Email Confirmation</label>
        </div>
        <div class="mb-2">
            <label class="form-label">Default Sender Email</label>
            <input type="email" class="form-control" wire:model="notificationSettings.email.default_sender">
        </div>
        <div class="mb-2">
            <label class="form-label">Default Sender Name</label>
            <input type="text" class="form-control" wire:model="notificationSettings.email.default_sender_name">
        </div>
    </div>

    {{-- SMS Notifications --}}
    <div class="mb-3">
        <h5 class="mb-2">SMS Notifications</h5>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" wire:model="notificationSettings.sms.enabled">
            <label class="form-check-label">Enable SMS Notifications</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" wire:model="notificationSettings.sms.require_confirmation">
            <label class="form-check-label">Require SMS Confirmation</label>
        </div>
        <div class="mb-2">
            <label class="form-label">SMS Provider</label>
            <select class="form-select" wire:model="notificationSettings.sms.provider">
                <option value="twilio">Twilio</option>
                <option value="nexmo">Nexmo</option>
                <option value="custom">Custom</option>
            </select>
        </div>
    </div>

    {{-- Push Notifications --}}
    <div class="mb-3">
        <h5 class="mb-2">Push Notifications</h5>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" wire:model="notificationSettings.push.enabled">
            <label class="form-check-label">Enable Push Notifications</label>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" wire:model="notificationSettings.push.require_confirmation">
            <label class="form-check-label">Require Push Confirmation</label>
        </div>
        <div class="mb-2">
            <label class="form-label">Push Provider</label>
            <select class="form-select" wire:model="notificationSettings.push.provider">
                <option value="firebase">Firebase</option>
                <option value="onesignal">OneSignal</option>
                <option value="custom">Custom</option>
            </select>
        </div>
    </div>

    {{-- Notification Templates --}}
    <div class="mb-3">
        <h5 class="mb-2">Notification Templates</h5>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" wire:model="notificationSettings.templates.enabled">
            <label class="form-check-label">Enable Custom Templates</label>
        </div>
        <div class="mb-2">
            <label class="form-label">Default Template Language</label>
            <select class="form-select" wire:model="notificationSettings.templates.default_language">
                <option value="en">English</option>
                <option value="id">Indonesian</option>
            </select>
        </div>
    </div>
</div>