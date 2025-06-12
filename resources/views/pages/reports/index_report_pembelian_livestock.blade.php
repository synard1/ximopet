<x-default-layout>

    @section('title', 'Laporan Pembelian Livestock')

    <!--begin::Content wrapper-->
    <div class="d-flex flex-column flex-column-fluid">
        {{--
        <!--begin::Toolbar-->
        <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
            <!--begin::Toolbar container-->
            <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
                <!--begin::Page title-->
                <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                    <!--begin::Title-->
                    <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                        Laporan Pembelian Livestock
                    </h1>
                    <!--end::Title-->
                    <!--begin::Breadcrumb-->
                    <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                        <li class="breadcrumb-item text-muted">
                            <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Home</a>
                        </li>
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">Reports</li>
                        <li class="breadcrumb-item">
                            <span class="bullet bg-gray-400 w-5px h-2px"></span>
                        </li>
                        <li class="breadcrumb-item text-muted">Pembelian Livestock</li>
                    </ul>
                    <!--end::Breadcrumb-->
                </div>
                <!--end::Page title-->
            </div>
            <!--end::Toolbar container-->
        </div>
        <!--end::Toolbar--> --}}

        <!--begin::Card-->
        <div class="card">
            <!--begin::Card header-->
            <div class="card-header border-0 pt-6">
                <!--begin::Card title-->
                <div class="card-title">
                    <!--begin::Search-->
                    <div class="d-flex align-items-center position-relative my-1">
                        <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <h3 class="fw-bold m-0">Filter Laporan Pembelian Livestock</h3>
                    </div>
                    <!--end::Search-->
                </div>
                <!--begin::Card title-->
            </div>
            <!--end::Card header-->

            <!--begin::Card body-->
            <div class="card-body py-4">
                <!--begin::Form-->
                <form id="reportForm" action="{{ route('purchase-reports.export-livestock') }}" method="POST">
                    @csrf
                    <div class="row g-6 mb-6">
                        <!--begin::Input group - Periode-->
                        <div class="col-md-6">
                            <label class="required form-label">Periode</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="date" name="start_date" class="form-control"
                                        value="{{ date('Y-m-01') }}" required>
                                    <div class="form-text">Tanggal Mulai</div>
                                </div>
                                <div class="col-6">
                                    <input type="date" name="end_date" class="form-control" value="{{ date('Y-m-t') }}"
                                        required>
                                    <div class="form-text">Tanggal Selesai</div>
                                </div>
                            </div>
                        </div>
                        <!--end::Input group-->

                        <!--begin::Input group - Farm-->
                        <div class="col-md-6">
                            <label class="form-label">Farm</label>
                            <select name="farm_id" class="form-select" data-control="select2"
                                data-placeholder="Pilih Farm (Kosongkan untuk semua)">
                                <option value="">Semua Farm</option>
                                @foreach($farms as $farm)
                                <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <!--end::Input group-->

                        <!--begin::Input group - Supplier-->
                        <div class="col-md-6">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" class="form-select" data-control="select2"
                                data-placeholder="Pilih Supplier (Kosongkan untuk semua)">
                                <option value="">Semua Supplier</option>
                                @foreach($partners as $partner)
                                <option value="{{ $partner->id }}">{{ $partner->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <!--end::Input group-->

                        <!--begin::Input group - Expedition-->
                        <div class="col-md-6">
                            <label class="form-label">Ekspedisi</label>
                            <select name="expedition_id" class="form-select" data-control="select2"
                                data-placeholder="Pilih Ekspedisi (Kosongkan untuk semua)">
                                <option value="">Semua Ekspedisi</option>
                                @foreach($expeditions as $expedition)
                                <option value="{{ $expedition->id }}">{{ $expedition->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <!--end::Input group-->

                        <!--begin::Input group - Status-->
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" data-control="select2"
                                data-placeholder="Pilih Status (Kosongkan untuk semua)">
                                <option value="">Semua Status</option>
                                <option value="draft">Draft</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="arrived">Arrived</option>
                                <option value="completed">Completed</option>
                            </select>
                        </div>
                        <!--end::Input group-->

                        <!--begin::Input group - Format Export-->
                        <div class="col-md-6">
                            <label class="form-label">Format Export</label>
                            <select name="export_format" class="form-select" data-control="select2">
                                <option value="html">HTML (Preview)</option>
                                <option value="excel">Excel (.xlsx)</option>
                                <option value="pdf">PDF</option>
                                <option value="csv">CSV</option>
                            </select>
                        </div>
                        <!--end::Input group-->
                    </div>

                    <!--begin::Actions-->
                    <div class="d-flex justify-content-end">
                        <button type="reset" class="btn btn-light me-3">Reset</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ki-duotone ki-document-download fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Generate Laporan
                        </button>
                    </div>
                    <!--end::Actions-->
                </form>
                <!--end::Form-->
            </div>
            <!--end::Card body-->
        </div>
        <!--end::Card-->

        <!--begin::Info Card-->
        <div class="card mt-6">
            <div class="card-body">
                <div class="d-flex align-items-center mb-6">
                    <i class="ki-duotone ki-information-5 fs-2x text-primary me-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                    <div>
                        <h4 class="mb-1">Informasi Laporan</h4>
                        <p class="text-muted mb-0">Panduan penggunaan laporan pembelian livestock</p>
                    </div>
                </div>

                <div class="row g-6">
                    <div class="col-md-4">
                        <div class="bg-light-primary rounded p-4">
                            <i class="ki-duotone ki-calendar fs-2x text-primary mb-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <h6 class="fw-bold mb-2">Filter Periode</h6>
                            <p class="text-muted fs-7 mb-0">
                                Tentukan rentang tanggal untuk data pembelian yang ingin ditampilkan
                            </p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="bg-light-success rounded p-4">
                            <i class="ki-duotone ki-filter fs-2x text-success mb-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <h6 class="fw-bold mb-2">Filter Lanjutan</h6>
                            <p class="text-muted fs-7 mb-0">
                                Gunakan filter farm, supplier, ekspedisi, dan status untuk hasil yang lebih
                                spesifik
                            </p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="bg-light-warning rounded p-4">
                            <i class="ki-duotone ki-document fs-2x text-warning mb-3">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <h6 class="fw-bold mb-2">Format Export</h6>
                            <p class="text-muted fs-7 mb-0">
                                Pilih format sesuai kebutuhan: HTML untuk preview, Excel/PDF untuk dokumen
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--end::Info Card-->
    </div>
    <!--end::Content wrapper-->


    @push('scripts')
    <script>
        $(document).ready(function() {
        // Initialize Select2
        $('[data-control="select2"]').select2();

        // Form validation
        $('#reportForm').on('submit', function(e) {
            const startDate = $('input[name="start_date"]').val();
            const endDate = $('input[name="end_date"]').val();
            
            if (startDate && endDate && startDate > endDate) {
                e.preventDefault();
                Swal.fire({
                    text: "Tanggal mulai tidak boleh lebih besar dari tanggal selesai",
                    icon: "error",
                    buttonsStyling: false,
                    confirmButtonText: "OK",
                    customClass: {
                        confirmButton: "btn btn-primary"
                    }
                });
                return;
            }

            // Show loading state
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.html('<span class="spinner-border spinner-border-sm me-2"></span>Generating...');
            submitBtn.prop('disabled', true);

            // Re-enable button after timeout (in case of error)
            setTimeout(() => {
                submitBtn.html(originalText);
                submitBtn.prop('disabled', false);
            }, 30000);
        });

        // Reset form
        $('button[type="reset"]').on('click', function() {
            $('[data-control="select2"]').val('').trigger('change');
            $('input[name="start_date"]').val('{{ date("Y-m-01") }}');
            $('input[name="end_date"]').val('{{ date("Y-m-t") }}');
            $('select[name="export_format"]').val('html').trigger('change');
        });
    });
    </script>
    @endpush
</x-default-layout>