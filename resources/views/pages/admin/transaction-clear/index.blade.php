<x-default-layout>
    @section('content')
    <div class="container-xxl">
        <!--begin::Toolbar-->
        <div class="toolbar mb-5 mb-lg-7" id="kt_toolbar">
            <div class="page-title d-flex flex-column me-3">
                <h1 class="d-flex text-dark fw-bolder fs-3 my-1">
                    <i class="fas fa-trash-alt text-danger me-3"></i>
                    Clear Transaction Data
                </h1>
                <span class="text-muted fs-7 fw-bold">Hapus semua data transaksi dan kembalikan livestock ke kondisi
                    awal</span>
            </div>
        </div>
        <!--end::Toolbar-->

        <!--begin::Alert-->
        <div class="alert alert-danger d-flex align-items-center p-5 mb-10">
            <i class="fas fa-exclamation-triangle fs-2hx text-danger me-4"></i>
            <div class="d-flex flex-column">
                <h4 class="mb-1 text-danger">PERINGATAN: Operasi Berbahaya!</h4>
                <span>Fitur ini akan menghapus SEMUA data transaksi yang ada. Pastikan Anda sudah melakukan backup
                    sebelum
                    melanjutkan.</span>
            </div>
        </div>
        <!--end::Alert-->

        <div class="row g-5 g-xl-8">
            <!--begin::Preview Card-->
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-eye text-primary me-2"></i>
                            Preview Data yang Akan Dihapus
                        </h3>
                    </div>
                    <div class="card-body">
                        <!--begin::Transaction Records-->
                        <div class="mb-8">
                            <h5 class="text-dark fw-bolder mb-3">📝 Data Transaksi</h5>
                            <div class="table-responsive">
                                <table class="table table-row-dashed table-row-gray-300 gy-5">
                                    <thead>
                                        <tr class="fw-bold fs-7 text-uppercase text-gray-400">
                                            <th>Jenis Data</th>
                                            <th class="text-end">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Pencatatan Harian</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['transaction_records']['recordings']) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Kematian Ternak</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['transaction_records']['livestock_depletion']) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Penjualan Ternak</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['transaction_records']['livestock_sales']) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Transaksi Penjualan</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['transaction_records']['sales_transactions']) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Record OVK</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['transaction_records']['ovk_records']) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Biaya Ternak</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['transaction_records']['livestock_costs']) }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!--end::Transaction Records-->

                        <!--begin::Usage Data-->
                        <div class="mb-8">
                            <h5 class="text-dark fw-bolder mb-3">🔄 Data Pemakaian & Mutasi</h5>
                            <div class="table-responsive">
                                <table class="table table-row-dashed table-row-gray-300 gy-5">
                                    <tbody>
                                        <tr>
                                            <td>Pemakaian Pakan</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['usage_data']['feed_usage']) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Pemakaian Supply</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['usage_data']['supply_usage']) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Mutasi Pakan</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['usage_data']['feed_mutations']) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Mutasi Supply</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['usage_data']['supply_mutations']) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Mutasi Ternak</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['usage_data']['livestock_mutations']) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!--end::Usage Data-->

                        <!--begin::Stock Data-->
                        <div class="mb-8">
                            <h5 class="text-dark fw-bolder mb-3">📦 Data Stok</h5>
                            <div class="table-responsive">
                                <table class="table table-row-dashed table-row-gray-300 gy-5">
                                    <tbody>
                                        <tr>
                                            <td>Stok Pakan</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['stock_data']['feed_stocks']) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Stok Supply</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['stock_data']['supply_stocks']) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!--end::Stock Data-->

                        <!--begin::Analytics Data-->
                        <div class="mb-8">
                            <h5 class="text-dark fw-bolder mb-3">📊 Data Analitik</h5>
                            <div class="table-responsive">
                                <table class="table table-row-dashed table-row-gray-300 gy-5">
                                    <tbody>
                                        <tr>
                                            <td>Analitik Harian</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['analytics_data']['daily_analytics']) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Analitik Periode</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['analytics_data']['period_analytics']) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Alert Analitik</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['analytics_data']['analytics_alerts']) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!--end::Analytics Data-->

                        <!--begin::Status History Data-->
                        <div class="mb-8">
                            <h5 class="text-dark fw-bolder mb-3">📜 Data Status History</h5>
                            <div class="table-responsive">
                                <table class="table table-row-dashed table-row-gray-300 gy-5">
                                    <tbody>
                                        <tr>
                                            <td>Livestock Purchase Status History</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['status_history_data']['livestock_purchase_status_history'])
                                                }}</td>
                                        </tr>
                                        <tr>
                                            <td>Feed Status History</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['status_history_data']['feed_status_history']) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Supply Status History</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['status_history_data']['supply_status_history'])
                                                }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!--end::Status History Data-->

                        <!--begin::Current Data-->
                        <div class="mb-8">
                            <h5 class="text-dark fw-bolder mb-3">📊 Data Current</h5>
                            <div class="table-responsive">
                                <table class="table table-row-dashed table-row-gray-300 gy-5">
                                    <tbody>
                                        <tr>
                                            <td>Current Feed</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['current_data']['current_feed']) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Current Livestock</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['current_data']['current_livestock']) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Current Supply</td>
                                            <td class="text-end fw-bold">{{
                                                number_format($preview['current_data']['current_supply']) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <!--end::Current Data-->
                    </div>
                </div>
            </div>
            <!--end::Preview Card-->

            <!--begin::Action Card-->
            <div class="col-xl-6">
                <!--begin::Preserved Data-->
                <div class="card mb-5">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-shield-alt text-success me-2"></i>
                            Data yang Akan Dipertahankan
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success d-flex align-items-center p-5 mb-5">
                            <i class="fas fa-check-circle fs-2hx text-success me-4"></i>
                            <div>
                                <h5 class="text-success">Data Pembelian Aman</h5>
                                <span>Semua data pembelian dan master data akan tetap ada</span>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-row-dashed table-row-gray-300 gy-5">
                                <thead>
                                    <tr class="fw-bold fs-7 text-uppercase text-gray-400">
                                        <th>Data yang Dipertahankan</th>
                                        <th class="text-end">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Pembelian Ternak</td>
                                        <td class="text-end fw-bold text-success">{{
                                            number_format($preview['purchase_data_preserved']['livestock_purchases']) }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Pembelian Pakan</td>
                                        <td class="text-end fw-bold text-success">{{
                                            number_format($preview['purchase_data_preserved']['feed_purchases']) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Pembelian Supply</td>
                                        <td class="text-end fw-bold text-success">{{
                                            number_format($preview['purchase_data_preserved']['supply_purchases']) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-danger d-flex align-items-center p-5 mt-5">
                            <i class="fas fa-trash fs-2hx text-danger me-4"></i>
                            <div>
                                <h5 class="text-danger">Livestock akan Dihapus</h5>
                                <div class="small">
                                    <div>• Livestock: {{ number_format($preview['livestock_to_delete']['livestock']) }}
                                    </div>
                                    <div>• Livestock Batches: {{
                                        number_format($preview['livestock_to_delete']['livestock_batches']) }}</div>
                                    <div>• Coops akan direset: {{
                                        number_format($preview['livestock_to_delete']['coops_to_reset']) }}</div>
                                </div>
                                <span class="text-muted small">Semua data ternak akan dihapus secara permanen</span>
                            </div>
                        </div>

                        <div class="alert alert-warning d-flex align-items-center p-5 mt-5">
                            <i class="fas fa-edit fs-2hx text-warning me-4"></i>
                            <div>
                                <h5 class="text-warning">Status Pembelian akan Diubah ke Draft</h5>
                                <div class="small">
                                    <div>• Livestock Purchases: {{
                                        number_format($preview['purchase_status_to_change']['livestock_purchases_non_draft'])
                                        }}</div>
                                    <div>• Feed Purchase Batches: {{
                                        number_format($preview['purchase_status_to_change']['feed_purchase_batches_non_draft'])
                                        }}</div>
                                    <div>• Supply Purchase Batches: {{
                                        number_format($preview['purchase_status_to_change']['supply_purchase_batches_non_draft'])
                                        }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!--end::Preserved Data-->

                <!--begin::Data Integrity Issues-->
                @if(isset($preview['integrity_issues_detected']) && array_sum($preview['integrity_issues_detected']) >
                0)
                <div class="card mb-5">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            Masalah Integritas Data Terdeteksi
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning d-flex align-items-center p-5 mb-5">
                            <i class="fas fa-tools fs-2hx text-warning me-4"></i>
                            <div>
                                <h5 class="text-warning">Perbaikan Otomatis Diperlukan</h5>
                                <span>Sistem mendeteksi beberapa masalah integritas data yang akan diperbaiki secara
                                    otomatis</span>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-row-dashed table-row-gray-300 gy-5">
                                <thead>
                                    <tr class="fw-bold fs-7 text-uppercase text-gray-400">
                                        <th>Masalah yang Terdeteksi</th>
                                        <th class="text-end">Jumlah</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($preview['integrity_issues_detected']['orphaned_feed_purchases'] > 0)
                                    <tr>
                                        <td>Feed Purchases tanpa Batch</td>
                                        <td class="text-end fw-bold text-warning">{{
                                            number_format($preview['integrity_issues_detected']['orphaned_feed_purchases'])
                                            }}
                                        </td>
                                    </tr>
                                    @endif
                                    @if($preview['integrity_issues_detected']['empty_feed_batches'] > 0)
                                    <tr>
                                        <td>Feed Batches tanpa Purchase</td>
                                        <td class="text-end fw-bold text-warning">{{
                                            number_format($preview['integrity_issues_detected']['empty_feed_batches'])
                                            }}
                                        </td>
                                    </tr>
                                    @endif
                                    @if($preview['integrity_issues_detected']['orphaned_supply_purchases'] > 0)
                                    <tr>
                                        <td>Supply Purchases tanpa Batch</td>
                                        <td class="text-end fw-bold text-warning">{{
                                            number_format($preview['integrity_issues_detected']['orphaned_supply_purchases'])
                                            }}
                                        </td>
                                    </tr>
                                    @endif
                                    @if($preview['integrity_issues_detected']['empty_supply_batches'] > 0)
                                    <tr>
                                        <td>Supply Batches tanpa Purchase</td>
                                        <td class="text-end fw-bold text-warning">{{
                                            number_format($preview['integrity_issues_detected']['empty_supply_batches'])
                                            }}
                                        </td>
                                    </tr>
                                    @endif
                                    @if(isset($preview['integrity_issues_detected']['empty_livestock_purchases_preserved'])
                                    && $preview['integrity_issues_detected']['empty_livestock_purchases_preserved'] > 0)
                                    <tr>
                                        <td>Livestock Purchases tanpa Items</td>
                                        <td class="text-end fw-bold text-warning">{{
                                            number_format($preview['integrity_issues_detected']['empty_livestock_purchases_preserved'])
                                            }}
                                        </td>
                                    </tr>
                                    @endif
                                    @if($preview['integrity_issues_detected']['orphaned_livestock_batches'] > 0)
                                    <tr>
                                        <td>Livestock Batches tanpa Purchase Item</td>
                                        <td class="text-end fw-bold text-warning">{{
                                            number_format($preview['integrity_issues_detected']['orphaned_livestock_batches'])
                                            }}
                                        </td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info d-flex align-items-center p-5 mt-5">
                            <i class="fas fa-info-circle fs-2hx text-info me-4"></i>
                            <div>
                                <span class="text-info">Semua masalah di atas akan diperbaiki secara otomatis saat
                                    proses clearing untuk menjaga integritas data.</span>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="card mb-5">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Status Integritas Data
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success d-flex align-items-center p-5">
                            <i class="fas fa-shield-check fs-2hx text-success me-4"></i>
                            <div>
                                <h5 class="text-success">Integritas Data Baik</h5>
                                <span>Tidak ada masalah integritas data yang terdeteksi pada purchase models</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                <!--end::Data Integrity Issues-->

                <!--begin::Clear Action-->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-cogs text-warning me-2"></i>
                            Eksekusi Clear Data
                        </h3>
                    </div>
                    <div class="card-body">
                        <form id="clearForm" class="form">
                            <div class="mb-5">
                                <label class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" id="confirmationCheck"
                                        name="confirmation" value="1">
                                    <span class="form-check-label fw-bold text-gray-700">
                                        Saya memahami bahwa operasi ini TIDAK DAPAT DIBATALKAN dan akan menghapus semua
                                        data
                                        transaksi
                                    </span>
                                </label>
                            </div>

                            <div class="mb-8">
                                <label class="required form-label">Masukkan Password Anda untuk Konfirmasi</label>
                                <input type="password" id="passwordInput" name="password" class="form-control"
                                    placeholder="Password Anda" required>
                                <div class="form-text">Password diperlukan untuk keamanan tambahan</div>
                            </div>

                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-light-primary" id="refreshPreviewBtn">
                                    <i class="fas fa-sync me-2"></i>
                                    Refresh Preview
                                </button>

                                <button type="submit" class="btn btn-danger" id="clearDataBtn" disabled>
                                    <i class="fas fa-trash-alt me-2"></i>
                                    <span class="indicator-label">Hapus Data Transaksi</span>
                                    <span class="indicator-progress">
                                        Memproses... <span
                                            class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <!--end::Clear Action-->
            </div>
            <!--end::Action Card-->
        </div>
    </div>

    <!--begin::Confirmation Modal-->
    <div class="modal fade" id="confirmationModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h3 class="modal-title text-white">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Konfirmasi Terakhir
                    </h3>
                </div>
                <div class="modal-body text-center py-10">
                    <i class="fas fa-skull-crossbones text-danger" style="font-size: 5rem;"></i>
                    <h4 class="text-danger mt-5 mb-3">Apakah Anda yakin?</h4>
                    <p class="text-gray-700 fs-6">
                        Operasi ini akan menghapus <strong>SEMUA DATA TRANSAKSI</strong> secara permanen.<br>
                        Data tidak dapat dikembalikan setelah operasi ini dilakukan.
                    </p>
                    <div class="bg-light-danger p-5 rounded mt-5">
                        <strong>Yang akan dihapus:</strong><br>
                        • Semua pencatatan harian<br>
                        • Semua pemakaian pakan & supply<br>
                        • Semua mutasi<br>
                        • Semua penjualan & kematian<br>
                        • Semua data analitik<br>
                        • <strong>SEMUA DATA TERNAK & BATCH</strong>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="finalConfirmBtn">
                        <i class="fas fa-trash-alt me-2"></i>
                        Ya, Hapus Semua Data
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!--end::Confirmation Modal-->

    <!--begin::Result Modal-->
    <div class="modal fade" id="resultModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" id="resultModalHeader">
                    <h3 class="modal-title" id="resultModalTitle"></h3>
                </div>
                <div class="modal-body" id="resultModalBody">
                    <!-- Result content will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    <!--end::Result Modal-->

    @push('scripts')
    <script>
        $(document).ready(function() {
    // Enable/disable clear button based on confirmation
    $('#confirmationCheck').change(function() {
        const isChecked = $(this).is(':checked');
        const hasPassword = $('#passwordInput').val().length > 0;
        $('#clearDataBtn').prop('disabled', !(isChecked && hasPassword));
    });

    $('#passwordInput').on('input', function() {
        const isChecked = $('#confirmationCheck').is(':checked');
        const hasPassword = $(this).val().length > 0;
        $('#clearDataBtn').prop('disabled', !(isChecked && hasPassword));
    });

    // Refresh preview
    $('#refreshPreviewBtn').click(function() {
        location.reload();
    });

    // Handle form submission
    $('#clearForm').submit(function(e) {
        e.preventDefault();
        
        if (!$('#confirmationCheck').is(':checked')) {
            Swal.fire({
                icon: 'warning',
                title: 'Konfirmasi Required',
                text: 'Silakan centang kotak konfirmasi terlebih dahulu'
            });
            return;
        }

        if ($('#passwordInput').val().length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Password Required',
                text: 'Silakan masukkan password Anda'
            });
            return;
        }

        // Show confirmation modal
        $('#confirmationModal').modal('show');
    });

    // Final confirmation
    $('#finalConfirmBtn').click(function() {
        $('#confirmationModal').modal('hide');
        executeClearData();
    });

    function executeClearData() {
        const $btn = $('#clearDataBtn');
        const $indicator = $btn.find('.indicator-label');
        const $progress = $btn.find('.indicator-progress');
        
        // Show loading state
        $btn.prop('disabled', true);
        $indicator.hide();
        $progress.show();

        // Prepare data
        const formData = {
            confirmation: $('#confirmationCheck').is(':checked') ? 1 : 0,
            password: $('#passwordInput').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        // Execute clear
        $.ajax({
            url: '/transaction-clear/clear',
            method: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    showSuccessResult(response);
                } else {
                    showErrorResult(response);
                }
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                showErrorResult(response || {
                    success: false,
                    message: 'Terjadi kesalahan pada server'
                });
            },
            complete: function() {
                // Reset button state
                $btn.prop('disabled', false);
                $indicator.show();
                $progress.hide();
                
                // Clear form
                $('#clearForm')[0].reset();
                $('#clearDataBtn').prop('disabled', true);
            }
        });
    }

    function showSuccessResult(response) {
        const $header = $('#resultModalHeader');
        const $title = $('#resultModalTitle');
        const $body = $('#resultModalBody');

        $header.removeClass('bg-danger').addClass('bg-success');
        $title.html('<i class="fas fa-check-circle me-2"></i>Berhasil Menghapus Data');

        let content = `
            <div class="alert alert-success d-flex align-items-center p-5 mb-5">
                <i class="fas fa-check-circle fs-2hx text-success me-4"></i>
                <div>
                    <h4 class="text-success">${response.message}</h4>
                </div>
            </div>
        `;

        if (response.cleared_data) {
            content += `
                <h5>Data yang Telah Dihapus:</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <tbody>
                            <tr><td>Pencatatan</td><td class="text-end fw-bold">${response.cleared_data.recordings || 0}</td></tr>
                            <tr><td>Kematian Ternak</td><td class="text-end fw-bold">${response.cleared_data.livestock_depletion || 0}</td></tr>
                            <tr><td>Penjualan Ternak</td><td class="text-end fw-bold">${response.cleared_data.livestock_sales || 0}</td></tr>
                            <tr><td>Transaksi Penjualan</td><td class="text-end fw-bold">${response.cleared_data.sales_transactions || 0}</td></tr>
                            <tr><td>Record OVK</td><td class="text-end fw-bold">${response.cleared_data.ovk_records || 0}</td></tr>
                            <tr><td>Biaya Ternak</td><td class="text-end fw-bold">${response.cleared_data.livestock_costs || 0}</td></tr>
                            <tr class="table-danger"><td><strong>Livestock</strong></td><td class="text-end fw-bold">${response.cleared_data.livestock || 0}</td></tr>
                            <tr class="table-danger"><td><strong>Livestock Batches</strong></td><td class="text-end fw-bold">${response.cleared_data.livestock_batches || 0}</td></tr>
                            <tr class="table-warning"><td><strong>Coops Reset</strong></td><td class="text-end fw-bold">${response.cleared_data.coops_reset || 0}</td></tr>
                        </tbody>
                    </table>
                </div>
            `;
        }

        if (response.purchase_status_changed) {
            content += `
                <div class="alert alert-warning mt-5">
                    <h5 class="text-warning">📝 Status Pembelian Diubah ke Draft</h5>
                    <div class="small">
                        <div>• Livestock Purchases: ${response.purchase_status_changed.livestock_purchases || 0}</div>
                        <div>• Feed Purchase Batches: ${response.purchase_status_changed.feed_purchase_batches || 0}</div>
                        <div>• Supply Purchase Batches: ${response.purchase_status_changed.supply_purchase_batches || 0}</div>
                    </div>
                </div>
            `;
        }

        $body.html(content);
        $('#resultModal').modal('show');

        // Refresh page after 3 seconds
        setTimeout(() => {
            location.reload();
        }, 3000);
    }

    function showErrorResult(response) {
        const $header = $('#resultModalHeader');
        const $title = $('#resultModalTitle');
        const $body = $('#resultModalBody');

        $header.removeClass('bg-success').addClass('bg-danger');
        $title.html('<i class="fas fa-times-circle me-2"></i>Gagal Menghapus Data');

        let content = `
            <div class="alert alert-danger d-flex align-items-center p-5 mb-5">
                <i class="fas fa-times-circle fs-2hx text-danger me-4"></i>
                <div>
                    <h4 class="text-danger">${response.message || 'Terjadi kesalahan'}</h4>
                </div>
            </div>
        `;

        if (response.errors && response.errors.length > 0) {
            content += `
                <h5>Detail Error:</h5>
                <ul class="list-group list-group-flush">
            `;
            response.errors.forEach(error => {
                content += `<li class="list-group-item text-danger">${error}</li>`;
            });
            content += `</ul>`;
        }

        $body.html(content);
        $('#resultModal').modal('show');
    }
});
    </script>
    @endpush
</x-default-layout>