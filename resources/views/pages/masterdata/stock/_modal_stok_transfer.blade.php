<!-- Stock Transfer Modal -->
<div class="modal fade" id="modalStokTransfer" tabindex="-1" role="dialog" aria-labelledby="modalStokTransferLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalStokTransferLabel">Transfer Stok</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="stokTransferForm" method="POST" action="{{ route('stock.transfer') }}">
                @csrf
                <div class="modal-body">
                    <x-error-messages />
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="source_farm_id">Sumber Farm <span class="text-danger">*</span></label>
                                <select class="form-control" id="source_farm_id" name="source_farm_id" required>
                                    <option value="">Pilih Farm</option>
                                    @foreach($farms ?? [] as $farm)
                                    <option value="{{ $farm->id }}">{{ $farm->name ?? 'Unknown' }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="source_farm_id_error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="destination_farm_id">Tujuan Farm <span class="text-danger">*</span></label>
                                <select class="form-control " id="destination_farm_id" name="destination_farm_id"
                                    required>
                                    <option value="">Pilih Farm</option>
                                    @foreach($farms ?? [] as $farm)
                                    <option value="{{ $farm->id }}">{{ $farm->name ?? 'Unknown' }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="destination_farm_id_error"></div>
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
                                    <option value="{{ $item->id }}">{{ $item->name }} ({{ $item->unit }})</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback" id="item_id_error"></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="quantity">Jumlah <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0.01" class="form-control" id="quantity"
                                        name="quantity" placeholder="Masukkan jumlah" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text item-unit">Unit</span>
                                    </div>
                                </div>
                                <div class="invalid-feedback" id="quantity_error"></div>
                                <small class="text-muted available-stock">Stok tersedia: <span
                                        id="available_stock">0</span></small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tanggal">Tanggal Transfer <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="tanggal" name="tanggal"
                                    value="{{ date('Y-m-d') }}" required>
                                <div class="invalid-feedback" id="tanggal_error"></div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Catatan</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"
                            placeholder="Tambahkan catatan (opsional)"></textarea>
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
                        url: "{{ route('stock.check-available') }}",
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

<script>
    document.getElementById('stokTransferForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const form = e.target;

            // Ambil data dari form
            const data = {
                source_farm_id: form.source_farm_id.value,
                destination_farm_id: form.destination_farm_id.value,
                item_id: form.item_id.value,
                quantity: form.quantity.value,
                tanggal: form.tanggal.value,
                notes: form.notes.value,
            };

            // Clear previous errors
            document.querySelectorAll('.invalid-feedback').forEach(el => el.innerText = '');

            // Kirim request ke Laravel
            axios.post("{{ route('stock.transfer') }}", data, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name=_token]').value,
                    'Accept': 'application/json',
                },
                withCredentials: true // penting untuk Sanctum
            })
            .then(response => {
                alert('Transfer berhasil!');
                // Tutup modal, refresh data, dll
            })
            .catch(error => {
                if (error.response && error.response.status === 422) {
                    const errors = error.response.data.errors;
                    for (const key in errors) {
                        const errorDiv = document.getElementById(`${key}_error`);
                        if (errorDiv) {
                            errorDiv.innerText = errors[key][0];
                        }
                    }
                } else {
                    alert('Terjadi kesalahan saat mengirim data.');
                }
            });
        });
</script>
@endpush