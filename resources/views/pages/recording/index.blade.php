<x-default-layout>

    @section('title')
        Transaksi Harian
    @endsection

    @section('breadcrumbs')
    @endsection
    <div class="card">
        <div class="card-body">
            <livewire:feed-usages.create />

        </div>
    </div>

        

    @push('scripts')

    @endpush
</x-default-layout>