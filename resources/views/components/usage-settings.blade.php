@props(['usageSettings', 'livewireComponent'])
{{-- Usage Settings Component --}}
<div>
    <div class="mb-7 pb-4 border-bottom">
        <x-usage.livestock-usage-settings :usage-settings="$usageSettings" :livewireComponent="$livewireComponent" />
    </div>
    <div class="mb-7 pb-4 border-bottom">
        <x-usage.feed-usage-settings :usage-settings="$usageSettings" :livewireComponent="$livewireComponent" />
    </div>
    <div class="mb-7">
        <x-usage.supply-usage-settings :usage-settings="$usageSettings" :livewireComponent="$livewireComponent" />
    </div>
</div>