<di

    @if($isOpen)
        @include('livewire.master-data._create_supllier')
    @endif

    @push('scripts')
        {{ $dataTable->scripts() }}
    @endpush
