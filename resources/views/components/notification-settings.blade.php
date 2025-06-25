@props(['notificationSettings', 'livewireComponent'])

{{-- Notification Settings Component --}}
<div>
    <h4 class="mb-3">Notification Settings</h4>

    {{-- Notification Channels --}}
    <div class="mb-7 pb-4 border-bottom">
        <h5 class="mb-3">Notification Channels</h5>
        <div class="row">
            <div class="col-md-4">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" wire:model="notificationSettings.channels.email">
                    <label class="form-check-label">Email Notifications</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" wire:model="notificationSettings.channels.database">
                    <label class="form-check-label">Database Notifications</label>
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox"
                        wire:model="notificationSettings.channels.broadcast">
                    <label class="form-check-label">Real-time Notifications</label>
                </div>
            </div>
        </div>
    </div>

    {{-- Event Notifications --}}
    <div class="mb-7 pb-4 border-bottom">
        <h5 class="mb-3">Event Notifications</h5>

        {{-- Purchase Events --}}
        <div class="mb-4">
            <h6>Purchase Events</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="notificationSettings.events.purchase.enabled">
                        <label class="form-check-label">Enable Purchase Notifications</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notify On</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="created"
                                wire:model="notificationSettings.events.purchase.notify_on">
                            <label class="form-check-label">Created</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="updated"
                                wire:model="notificationSettings.events.purchase.notify_on">
                            <label class="form-check-label">Updated</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="approved"
                                wire:model="notificationSettings.events.purchase.notify_on">
                            <label class="form-check-label">Approved</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="rejected"
                                wire:model="notificationSettings.events.purchase.notify_on">
                            <label class="form-check-label">Rejected</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Channels</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="email"
                            wire:model="notificationSettings.events.purchase.channels">
                        <label class="form-check-label">Email</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="database"
                            wire:model="notificationSettings.events.purchase.channels">
                        <label class="form-check-label">Database</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="broadcast"
                            wire:model="notificationSettings.events.purchase.channels">
                        <label class="form-check-label">Broadcast</label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Mutation Events --}}
        <div class="mb-4">
            <h6>Mutation Events</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="notificationSettings.events.mutation.enabled">
                        <label class="form-check-label">Enable Mutation Notifications</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Channels</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="email"
                            wire:model="notificationSettings.events.mutation.channels">
                        <label class="form-check-label">Email</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="database"
                            wire:model="notificationSettings.events.mutation.channels">
                        <label class="form-check-label">Database</label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Usage Events --}}
        <div class="mb-4">
            <h6>Usage Events</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="notificationSettings.events.usage.enabled">
                        <label class="form-check-label">Enable Usage Notifications</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Channels</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="email"
                            wire:model="notificationSettings.events.usage.channels">
                        <label class="form-check-label">Email</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="database"
                            wire:model="notificationSettings.events.usage.channels">
                        <label class="form-check-label">Database</label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Batch Completion --}}
        <div class="mb-4">
            <h6>Batch Completion</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="notificationSettings.events.batch_completion.enabled">
                        <label class="form-check-label">Enable Batch Completion Notifications</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Channels</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="email"
                            wire:model="notificationSettings.events.batch_completion.channels">
                        <label class="form-check-label">Email</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="database"
                            wire:model="notificationSettings.events.batch_completion.channels">
                        <label class="form-check-label">Database</label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Low Stock --}}
        <div class="mb-4">
            <h6>Low Stock Alerts</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="notificationSettings.events.low_stock.enabled">
                        <label class="form-check-label">Enable Low Stock Notifications</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Channels</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="email"
                            wire:model="notificationSettings.events.low_stock.channels">
                        <label class="form-check-label">Email</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="database"
                            wire:model="notificationSettings.events.low_stock.channels">
                        <label class="form-check-label">Database</label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Age Threshold --}}
        <div class="mb-4">
            <h6>Age Threshold Alerts</h6>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox"
                            wire:model="notificationSettings.events.age_threshold.enabled">
                        <label class="form-check-label">Enable Age Threshold Notifications</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Channels</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="email"
                            wire:model="notificationSettings.events.age_threshold.channels">
                        <label class="form-check-label">Email</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="database"
                            wire:model="notificationSettings.events.age_threshold.channels">
                        <label class="form-check-label">Database</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>