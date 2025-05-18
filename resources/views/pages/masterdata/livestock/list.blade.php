<x-default-layout>
    @section('title')
    Data {{ trans('content.ternak',[],'id') }}
    @endsection

    @section('breadcrumbs')
    @endsection
    <div class="card" id="ternaksTables">
        <!--begin::Card body-->
        <div class="card-body py-4">
            <!--begin::Table-->
            <div class="table-responsive">
                {{ $dataTable->table(['id' => 'ternaks-table']) }}
            </div>
            <!--end::Table-->
        </div>
        <!--end::Card body-->
    </div>

    {{-- @include('pages.masterdata.ternak._modal_ternak_details') --}}
    @include('pages.masterdata.ternak._detail_modal')
    @include('pages.masterdata.ternak._detail_reports_modal')

    <!-- Livewire Container (Hidden by Default) -->
    <div id="assignWorkerContainer" style="display: none;">
        <button class="btn btn-danger mb-3 closeRecordsBtn">Kembali ke Tabel</button>
        <livewire:master-data.worker.assign-worker />

    </div>

    <!-- Livewire Container (Hidden by Default) -->
    <div id="livewireRecordsContainer" style="display: none;">
        <button class="btn btn-danger mb-3 closeRecordsBtn">Kembali ke Tabel</button>
        <livewire:records />
    </div>

    @push('scripts')
    {{ $dataTable->scripts() }}

    <script>
        window.addEventListener('notify', event => {
            alert(event.detail.message); // ganti dengan toastr atau lainnya jika ada
        });
    </script>

    @endpush
</x-default-layout>