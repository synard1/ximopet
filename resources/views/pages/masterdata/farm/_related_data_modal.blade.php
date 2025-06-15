<!--begin::Modal - Farm Details-->
<div class="modal fade" id="farmDetailsModal" tabindex="-1" aria-hidden="true">
    <!--begin::Modal dialog-->
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <!--begin::Modal content-->
        <div class="modal-content">
            <!--begin::Modal header-->
            <div class="modal-header">
                <!--begin::Modal title-->
                <h2>Detail Kandang</h2>
                <!--end::Modal title-->
                <!--begin::Close-->
                <div class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal"
                    aria-label="Close">
                    {!! getIcon('cross', 'fs-2x') !!}
                </div>
                <!--end::Close-->
            </div>
            <!--end::Modal header-->
            <!--begin::Modal body-->
            <div class="modal-body scroll-y mx-5 mx-xl-15 my-7">
                <!--begin::Table-->
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3"
                        id="kandangsTable">
                        <thead>
                            <tr class="fw-bold text-muted">
                                <th class="min-w-100px">Kode</th>
                                <th class="min-w-150px">Nama</th>
                                <th class="min-w-100px">Kapasitas</th>
                                <th class="min-w-100px">Status</th>
                                <th class="min-w-100px">Tanggal Mulai</th>
                                <th class="min-w-100px">Populasi</th>
                                <th class="min-w-100px">Berat Awal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be populated by JavaScript -->
                            <tr>
                                <td colspan="7" class="text-center">Tidak ada data Kandang</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <!--end::Table-->
            </div>
            <!--end::Modal body-->
        </div>
        <!--end::Modal content-->
    </div>
    <!--end::Modal dialog-->
</div>
<!--end::Modal - Farm Details-->