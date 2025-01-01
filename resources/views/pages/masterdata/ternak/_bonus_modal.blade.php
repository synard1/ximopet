<div class="modal fade" id="kt_modal_ternak_detail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                {{-- <h2 class="fw-bolder modal-title">Input Bonus Ternak</h2> --}}
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body scroll-y">
                <form id="bonusForm">
                    
                    <div class="py-5">
                        
                        <div class="rounded border p-10">
                            <div class="text-right">
                                Data Bonus
                            </div>
                            <input type="hidden" id="ternak_id" name="ternak_id">

                            <!--begin::Input group-->
                            <div class="form-floating mb-7">
                                <input type="date" class="form-control" id="tanggal" placeholder="Tanggal" name="tanggal" required>
                                <label for="tanggal">Tanggal</label>
                            </div>
                            <!--end::Input group-->
                            
                            <!--begin::Input group-->
                            <div class="form-floating mb-7">
                                <input type="number" class="form-control" id="jumlah" name="jumlah" required>
                                <label for="jumlah">Jumlah</label>
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="form-floating mb-7">
                                <textarea class="form-control" id="keterangan" name="keterangan" rows="3"></textarea>
                                <label for="keterangan">Keterangan</label>
                            </div>
                            <!--end::Input group-->

                            <div class="text-right">
                                <button type="submit" class="btn btn-primary">Simpan Bonus</button>
                            </div>
                        </div>
                    </div>
                </form>

                <form id="administrasiForm">
                    
                    <div class="py-5">
                        
                        <div class="rounded border p-10">
                            <div class="text-right">
                                Admninistrasi Laporan
                            </div>
                            <input type="hidden" id="ternak_id" name="ternak_id">

                            <!--begin::Input group-->
                            <div class="form-floating mb-7">
                                <input type="date" class="form-control" id="tanggal_laporan" placeholder="Tanggal Laporan" name="tanggal_laporan" required>
                                <label for="tanggal_laporan">Tanggal Laporan</label>
                            </div>
                            <!--end::Input group-->
                            
                            <!--begin::Input group-->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control" id="persetujuan" name="persetujuan" required>
                                <label for="persetujuan">Persetujuan</label>
                            </div>
                            <!--end::Input group-->

                            <!--begin::Input group-->
                            <div class="form-floating mb-7">
                                <input type="text" class="form-control" id="verifikator" name="verifikator" required>
                                <label for="verifikator">Verifikator</label>
                            </div>
                            <!--end::Input group-->

                            <div class="text-right">
                                <button type="submit" class="btn btn-primary">Simpan</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('bonusForm');
    if (!form) {
        console.error('Form with id "bonusForm" not found');
        return;
    }

    // Initialize FormValidation
    const validator = FormValidation.formValidation(
        form,
        {
            fields: {
                tanggal: {
                    validators: {
                        notEmpty: {
                            message: 'Tanggal harus diisi'
                        }
                    }
                },
                jumlah: {
                    validators: {
                        notEmpty: {
                            message: 'Jumlah bonus harus diisi'
                        },
                        numeric: {
                            message: 'Jumlah bonus harus berupa angka'
                        }
                    }
                },
                keterangan: {
                    validators: {
                        stringLength: {
                            max: 255,
                            message: 'Keterangan tidak boleh lebih dari 255 karakter'
                        }
                    }
                }
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap: new FormValidation.plugins.Bootstrap5({
                    rowSelector: '.form-floating',
                    eleInvalidClass: 'is-invalid',
                    eleValidClass: 'is-valid'
                })
            }
        }
    );

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        validator.validate().then(function(status) {
            if (status === 'Valid') {
                const formData = new FormData(form);

                fetch('/api/v2/save-bonus', {
                    method: 'POST',
                    headers: {
                        // 'Content-Type': 'application/json',
                        'Authorization': 'Bearer ' + window.AuthToken,
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    Swal.fire({
                        title: 'Sukses!',
                        text: 'Bonus berhasil disimpan.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $('#kt_modal_ternak_bonus').modal('hide');
                            // Optionally, refresh the page or update the UI
                        }
                    });
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Terjadi kesalahan saat menyimpan bonus.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                });
            }
        });
    });
});
</script>
@endpush
