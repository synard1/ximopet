<div class="card">
    <!--begin::Card header-->
    <div class="card-header border-0 pt-6">
        <!--begin::Card title-->
        <div class="card-title">
            <!--begin::Search-->
            {{-- <div class="d-flex align-items-center position-relative my-1">
                {!! getIcon('magnifier', 'fs-3 position-absolute ms-5') !!}
                <input type="text" data-kt-user-table-filter="search"
                    class="form-control form-control-solid w-250px ps-13" placeholder="Cari Stok" id="mySearchInput" />
            </div> --}}
            <!--end::Search-->
        </div>
        <!--begin::Card title-->

        <!--begin::Card toolbar-->
        <div class="card-toolbar">

            <!--begin::Modal-->
            <livewire:master-data.stok-list />
            <!--end::Modal-->
        </div>
        <!--end::Card toolbar-->
    </div>
    <!--end::Card header-->

    <!--begin::Card body-->
    <div class="card-body py-4">
        <!--begin::Table-->
        <div class="table-responsive">
            {{ $dataTable->table() }}
        </div>
        <!--end::Table-->
    </div>
    <!--end::Card body-->
</div>

@push('scripts')
<script>
    // document.getElementById('mySearchInput').addEventListener('keyup', function () {
    //     window.LaravelDataTables['stoks-table'].search(this.value).draw();
    // });

    $(document).ready(function () {
        // Restore filter dari localStorage
        if (localStorage.getItem('stok_filter_farm_id')) {
            $('#farm_id').val(localStorage.getItem('stok_filter_farm_id'));
        }
        if (localStorage.getItem('stok_filter_supply_id')) {
            $('#supply_id').val(localStorage.getItem('stok_filter_supply_id'));
        }

        // $('#filter-form').on('submit', function (e) {
        //     e.preventDefault();
        //     // Simpan filter ke localStorage
        //     localStorage.setItem('stok_filter_farm_id', $('#farm_id').val());
        //     localStorage.setItem('stok_filter_supply_id', $('#supply_id').val());
        //     window.LaravelDataTables['stocks-table'].draw();
        // });

        // window.LaravelDataTables['stocks-table'].on('preXhr.dt', function (e, settings, data) {
        //     data.farm_id = $('#farm_id').val();
        //     data.supply_id = $('#supply_id').val();
        // });
    });
</script>
@endpush