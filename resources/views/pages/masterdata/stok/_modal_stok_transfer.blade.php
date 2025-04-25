<!-- Stock Transfer Modal -->
<div class="modal fade" id="modalStokTransfer" tabindex="-1" role="dialog" aria-labelledby="modalStokTransferLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalStokTransferLabel">Transfer Stok</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            {{-- {{  dd($kelompokTernaks ); }} --}}
            <form id="stokTransferForm" method="POST" action="{{ route('stocks.transfer') }}">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="source_kelompok_ternak_id">Sumber Kelompok Ternak <span class="text-danger">*</span></label>
                                <select class="form-control" id="source_kelompok_ternak_id" name="source_kelompok_ternak_id" required>
                                    <option value="">Pilih Kelompok Ternak</option>
                                    @foreach($kelompokTernaks ?? [] as $kelompokTernak)
                                        <option value="{{ $kelompokTernak->id }}">{{ $kelompokTernak->name }} ({{ $kelompokTernak->farm->nama ?? 'Unknown' }})</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="source_kelompok_ternak_id_error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="destination_kelompok_ternak_id">Tujuan Kelompok Ternak <span class="text-danger">*</span></label>
                                <select class="form-control " id="destination_kelompok_ternak_id" name="destination_kelompok_ternak_id" required>
                                    <option value="">Pilih Kelompok Ternak</option>
                                    @foreach($kelompokTernaks ?? [] as $kelompokTernak)
                                        <option value="{{ $kelompokTernak->id }}">{{ $kelompokTernak->name }} ({{ $kelompokTernak->farm->nama ?? 'Unknown' }})</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="destination_kelompok_ternak_id_error"></div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="item_id">Item <span class="text-danger">*</span></label>
                                <select class="form-control " id="item_id" name="item_id" required>
                                    <option value="">Pilih Item</option>
                                    @foreach($items ?? [] as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->satuan_kecil }})</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="item_id_error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="quantity">Jumlah <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0.01" class="form-control" id="quantity" name="quantity" placeholder="Masukkan jumlah" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text item-unit">Unit</span>
                                    </div>
                                </div>
                                <div class="invalid-feedback" id="quantity_error"></div>
                                <small class="text-muted available-stock">Stok tersedia: <span id="available_stock">0</span></small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tanggal">Tanggal Transfer <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tanggal" name="tanggal" value="{{ date('Y-m-d') }}" required>
                                <div class="invalid-feedback" id="tanggal_error"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Catatan</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Tambahkan catatan (opsional)"></textarea>
                        <div class="invalid-feedback" id="notes_error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btn-transfer">Transfer Stok</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize select2
        $('.select2').select2({
            dropdownParent: $('#modalStokTransfer')
        });
        
        // Check available stock when source and item are selected
        $('#source_kelompok_ternak_id, #item_id').change(function() {
            checkAvailableStock();
        });
        
        // Prevent selecting same source and destination
        $('#destination_kelompok_ternak_id').change(function() {
            var sourceId = $('#source_kelompok_ternak_id').val();
            var destinationId = $(this).val();
            
            if (sourceId === destinationId && sourceId !== '') {
                alert('Sumber dan tujuan tidak boleh sama!');
                $(this).val('').trigger('change');
            }
        });
        
        // Update item unit when item is selected
        $('#item_id').change(function() {
            var selectedItem = $(this).find('option:selected');
            var unit = selectedItem.text().match(/\((.*?)\)/);
            
            if (unit && unit[1]) {
                $('.item-unit').text(unit[1]);
            } else {
                $('.item-unit').text('Unit');
            }
        });
        
        // Check available stock
        function checkAvailableStock() {
            var sourceId = $('#source_kelompok_ternak_id').val();
            var itemId = $('#item_id').val();
            
            if (sourceId && itemId) {
                $.ajax({
                    url: "{{ route('stocks.check-available') }}",
                    type: "GET",
                    data: {
                        location_id: sourceId,
                        item_id: itemId
                    },
                    success: function(response) {
                        if (response.status === 'success') {
                            $('#available_stock').text(response.data.quantity + ' ' + response.data.unit);
                        } else {
                            $('#available_stock').text('0');
                        }
                    },
                    error: function() {
                        $('#available_stock').text('0');
                    }
                });
            } else {
                $('#available_stock').text('0');
            }
        }
        
        // Form submission
        $('#stokTransferForm').submit(function(e) {
            e.preventDefault();
            
            // Reset error messages
            $('.invalid-feedback').hide();
            $('.form-control').removeClass('is-invalid');
            
            // Disable submit button
            $('#btn-transfer').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');
            
            $.ajax({
                url: $(this).attr('action'),
                type: "POST",
                data: $(this).serialize(),
                success: function(response) {
                    if (response.status === 'success') {
                        // Show success message
                        Swal.fire({
                            title: 'Berhasil!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then((result) => {
                            // Reload page or update data
                            $('#modalStokTransfer').modal('hide');
                            window.location.reload();
                        });
                    } else {
                        // Show error message
                        Swal.fire({
                            title: 'Gagal!',
                            text: response.message,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                        
                        // Re-enable submit button
                        $('#btn-transfer').prop('disabled', false).text('Transfer Stok');
                    }
                },
                error: function(xhr) {
                    // Re-enable submit button
                    $('#btn-transfer').prop('disabled', false).text('Transfer Stok');
                    
                    if (xhr.status === 422) {
                        // Validation errors
                        var errors = xhr.responseJSON.errors;
                        
                        // Display validation errors
                        $.each(errors, function(key, value) {
                            $('#' + key).addClass('is-invalid');
                            $('#' + key + '_error').text(value[0]).show();
                        });
                    } else {
                        // Show generic error message
                        Swal.fire({
                            title: 'Error!',
                            text: 'Terjadi kesalahan saat memproses permintaan.',
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                }
            });
        });
        
        // Reset form when modal is closed
        $('#modalStokTransfer').on('hidden.bs.modal', function() {
            $('#stokTransferForm')[0].reset();
            $('.select2').val('').trigger('change');
            $('.invalid-feedback').hide();
            $('.form-control').removeClass('is-invalid');
            $('#available_stock').text('0');
            $('.item-unit').text('Unit');
        });
    });
    </script>
@endpush

