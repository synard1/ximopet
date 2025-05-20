<div class="d-flex justify-content-end flex-shrink-0">
    {{-- <button type="button" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1"
        wire:click="$dispatch('showEditForm', { id: {{ $record->id }} })">
        <i class="ki-duotone ki-pencil fs-2"><span class="path1"></span><span class="path2"></span></i>
    </button> --}}
    <a href="#" class="button px-3" onclick="Livewire.dispatch('showEditForm', [@js($record->id)])">
        <i class="ki-duotone ki-pencil fs-2"><span class="path1"></span><span class="path2"></span></i>
    </a>
    <a href="#" class="button px-3" onclick="if(confirm('Apakah Anda yakin ingin menghapus record ini?')) { Livewire.dispatch('deleteOVKRecord', [@js($record->id)]) }">
        <i class="ki-duotone ki-trash fs-2"><span class="path1"></span><span class="path2"></span></i>
    </a>
    {{-- <button type="button" class="btn btn-icon btn-bg-light btn-active-color-danger btn-sm"
        wire:click="$dispatch('deleteOVKRecord', { id: {{ $record->id }} })"
        wire:confirm="Apakah Anda yakin ingin menghapus record ini?">
        <i class="ki-duotone ki-trash fs-2"><span class="path1"></span><span class="path2"></span></i>
    </button> --}}
</div>