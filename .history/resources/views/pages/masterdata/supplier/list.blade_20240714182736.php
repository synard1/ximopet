<x-default-layout>

    @section('title')
        Suppliers
    @endsection

    @section('breadcrumbs')
    @endsection

@include('livewire.master')
    @push('scripts')
        {{ $dataTable->scripts() }}
        <script>
            document.getElementById('mySearchInput').addEventListener('keyup', function () {
                window.LaravelDataTables['suppliers-table'].search(this.value).draw();
            });
            document.addEventListener('livewire:init', function () {
                Livewire.on('success', function () {
                    // $('#kt_modal_add_user').modal('hide');
                    window.LaravelDataTables['suppliers-table'].ajax.reload();
                });
            });
            // $('#kt_modal_add_user').on('hidden.bs.modal', function () {
            //     Livewire.dispatch('new_user');
            // });
        </script>
    @endpush

</x-default-layout>
