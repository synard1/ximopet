@props(['mutationSettings', 'livewireComponent'])
{{-- Mutation Settings Component --}}
<div>
    <div class="mb-7 pb-4 border-bottom">
        <x-mutation.livestock-mutation-settings :mutation-settings="$mutationSettings"
            :livewireComponent="$livewireComponent" />
    </div>
    <div class="mb-7 pb-4 border-bottom">
        <x-mutation.feed-mutation-settings :mutation-settings="$mutationSettings"
            :livewireComponent="$livewireComponent" />
    </div>
    <div class="mb-7">
        <x-mutation.supply-mutation-settings :mutation-settings="$mutationSettings"
            :livewireComponent="$livewireComponent" />
    </div>
</div>