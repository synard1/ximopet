<div class="modal fade" id="kt_modal_ternak_detail_report" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Detail Report Ternak</h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="bonusForm" class="mb-5">
                    <h5 class="mb-3">Data Bonus</h5>
                    <input type="hidden" id="ternak_id" name="ternak_id">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="tanggal" class="form-label">Tanggal</label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" required>
                        </div>
                        <div class="col-md-6">
                            <label for="jumlah" class="form-label">Jumlah</label>
                            <input type="text" class="form-control" id="jumlah" name="jumlah" required step="0.01" pattern="^\d*(\.\d{0,2})?$">
                        </div>
                        <div class="col-12">
                            <label for="keterangan" class="form-label">Keterangan</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary btn-sm">Simpan Bonus</button>
                    </div>
                </form>

                <form id="administrasiForm">
                    <h5 class="mb-3">Administrasi Laporan</h5>
                    <div class="row g-3">
                        <input type="hidden" id="ternak_id" name="ternak_id">
                        <!--begin::Input group-->
                        <div class="form-floating col-6">
                            <input type="text" class="form-control" id="persetujuan_nama" name="persetujuan_nama" placeholder="Disetujui Oleh">
                            <label for="persetujuan_nama">Disetujui Oleh</label>
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="form-floating col-6">
                            <input type="text" class="form-control" id="persetujuan_jabatan" name="persetujuan_jabatan" placeholder="Disetujui Oleh">
                            <label for="persetujuan_jabatan">Jabatan</label>
                        </div>
                        <!--end::Input group-->

                        <!--begin::Input group-->
                        <div class="form-floating col-6">
                            <input type="text" class="form-control" id="verifikator_nama" name="verifikator_nama" placeholder="Diverifikasi Oleh">
                            <label for="verifikator_nama">Diverifikasi Oleh</label>
                        </div>
                        <!--end::Input group-->
                        <!--begin::Input group-->
                        <div class="form-floating col-6">
                            <input type="text" class="form-control" id="verifikator_jabatan" name="verifikator_jabatan" placeholder="Disetujui Oleh">
                            <label for="verifikator_jabatan">Jabatan</label>
                        </div>
                        <!--end::Input group-->
                        <div class="col-md-6">
                            <label for="tanggal_laporan" class="form-label">Tanggal Laporan</label>
                            <input type="date" class="form-control" id="tanggal_laporan" name="tanggal_laporan">
                            <div class="form-text">Sistem akan menggunakan tanggal hari ini, jika tanggal tidak di input manual</div>
                        </div>
                    </div>
                    <div class="text-end mt-3">
                        <button type="submit" class="btn btn-primary btn-sm">Simpan Administrasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const bonusForm = document.getElementById('bonusForm');
    const administrasiForm = document.getElementById('administrasiForm');

    // Bonus Form Validation
    const bonusValidator = FormValidation.formValidation(bonusForm, {
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
                rowSelector: '.col-md-6, .col-12',
                eleInvalidClass: 'is-invalid',
                eleValidClass: 'is-valid'
            })
        }
    });

    // Administrasi Form Validation
    const administrasiValidator = FormValidation.formValidation(administrasiForm, {
        fields: {
            // tanggal_laporan: {
            //     validators: {
            //         notEmpty: {
            //             message: 'Tanggal laporan harus diisi'
            //         }
            //     }
            // },
            persetujuan_nama: {
                validators: {
                    notEmpty: {
                        message: 'Nama persetujuan harus diisi'
                    }
                }
            },
            persetujuan_jabatan: {
                validators: {
                    notEmpty: {
                        message: 'Jabatan persetujuan harus diisi'
                    }
                }
            },
            verifikator_nama: {
                validators: {
                    notEmpty: {
                        message: 'Nama verifikator harus diisi'
                    }
                }
            },
            verifikator_jabatan: {
                validators: {
                    notEmpty: {
                        message: 'Jabatan verifikator harus diisi'
                    }
                }
            }
        },
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap: new FormValidation.plugins.Bootstrap5({
                rowSelector: '.col-md-6, .form-floating',
                eleInvalidClass: 'is-invalid',
                eleValidClass: 'is-valid'
            })
        }
    });

    // Bonus Form Submission
    bonusForm.addEventListener('submit', function(e) {
        e.preventDefault();
        bonusValidator.validate().then(function(status) {
            if (status === 'Valid') {
                const formData = new FormData(bonusForm);
                submitForm('/api/v2/save-bonus', formData, 'Bonus berhasil disimpan.');
            }
        });
    });

    // Administrasi Form Submission
    administrasiForm.addEventListener('submit', function(e) {
        e.preventDefault();
        administrasiValidator.validate().then(function(status) {
            if (status === 'Valid') {
                const formData = new FormData(administrasiForm);
                submitForm('/api/v2/save-administrasi', formData, 'Administrasi berhasil disimpan.');
            }
        });
    });

    // Function to submit form data
    function submitForm(url, formData, successMessage) {
        fetch(url, {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + window.AuthToken,
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            Swal.fire({
                title: 'Sukses!',
                text: successMessage,
                icon: 'success',
                confirmButtonText: 'OK'
            }).then((result) => {
                if (result.isConfirmed) {
                    // $('#kt_modal_ternak_detail_report').modal('hide');
                    // Optionally, refresh the page or update the UI
                }
            });
        })
        .catch(error => {
            Swal.fire({
                title: 'Error!',
                text: 'Terjadi kesalahan saat menyimpan data.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        });
    }
});
</script>
@endpush