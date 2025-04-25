<x-default-layout>

    @section('title')
        Ekspedisi
    @endsection

    @section('breadcrumbs')
    @endsection
    <div class="card">
        <div class="card-body">
            <livewire:expeditions.create />

        </div>
    </div>

        

    @push('scripts')

    @endpush
</x-default-layout>