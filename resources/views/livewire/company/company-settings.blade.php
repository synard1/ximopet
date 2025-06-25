@php
$activeConfig = \App\Config\CompanyConfig::getDefaultActiveConfig();
@endphp

<div>
    <form wire:submit.prevent="saveSettings">
        <!-- Dynamic Section Rendering -->
        @foreach(array_keys($activeConfig) as $section)
        @php $sectionVar = $section . 'Settings'; @endphp
        @if($this->isSectionEnabled($$sectionVar))
        <div x-data="{ open: true }" class="card mb-5 mb-xl-10">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title text-capitalize">{{ ucfirst(str_replace('_', ' ', $section)) }} Settings</h3>
                <button type="button" class="btn btn-sm btn-light" @click="open = !open">
                    <span x-show="open">Collapse</span>
                    <span x-show="!open">Expand</span>
                </button>
            </div>
            <div class="card-body" x-show="open" x-transition>
                @if($section === 'purchasing')
                <div class="mb-7 pb-4 border-bottom">
                    <x-purchase.livestock-purchase-settings :purchasing-settings="$purchasingSettings"
                        :template-config="$templateConfig" :active-config="$activeConfig" :livewireComponent="$this" />
                </div>
                <div class="mb-7 pb-4 border-bottom">
                    <x-purchase.feed-purchase-settings :purchasing-settings="$purchasingSettings"
                        :template-config="$templateConfig" :active-config="$activeConfig" :livewireComponent="$this" />
                </div>
                <div class="mb-7">
                    <x-purchase.supply-purchase-settings :purchasing-settings="$purchasingSettings"
                        :template-config="$templateConfig" :active-config="$activeConfig" :livewireComponent="$this" />
                </div>
                @elseif($section === 'livestock')
                <x-livestock-settings-enhanced :livestock-settings="$livestockSettings"
                    :template-config="$templateConfig" :active-config="$activeConfig" :livewireComponent="$this" />
                @elseif($section === 'mutation')
                <x-mutation-settings :mutation-settings="$mutationSettings" :livewireComponent="$this" />
                @elseif($section === 'usage')
                <x-usage-settings :usage-settings="$usageSettings" :livewireComponent="$this" />
                @elseif($section === 'notification')
                <x-notification-settings :notification-settings="$notificationSettings" :livewireComponent="$this" />
                @elseif($section === 'reporting')
                <x-reporting-settings :reporting-settings="$reportingSettings" :livewireComponent="$this" />
                @endif
            </div>
        </div>
        @endif
        @endforeach

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                <span wire:loading.remove>
                    <i class="bi bi-check2"></i> Save Settings
                </span>
                <span wire:loading>
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Saving...
                </span>
            </button>
        </div>
    </form>

    @push('scripts')
    <script>
        document.addEventListener('livewire:init', function () {
            Livewire.on('success', function (message) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: message,
                    buttonsStyling: false,
                    confirmButtonText: 'Ok',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    }
                });
            });
            
            Livewire.on('error', function (message) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: message,
                    buttonsStyling: false,
                    confirmButtonText: 'Ok',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    }
                });
            });
        });
    </script>
    @endpush
</div>

@php
// Komponen reusable untuk badge notifikasi fitur disable
if (!function_exists('featureBadge')) {
function featureBadge($label) {
return '<span class="badge bg-secondary">' . e($label) . ' - Coming Soon</span>';
}
}
@endphp