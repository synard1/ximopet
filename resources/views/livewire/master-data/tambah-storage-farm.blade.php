<div class="modal fade" tabindex="-1" role="dialog" id="kt_modal_tambah_storage" data-bs-backdrop="static"
    data-bs-keyboard="false" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Storage</h5>
            </div>
            <div class="modal-body">
                <form id="form_storage_location">
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Farm</label>
                        <!--end::Label-->
                        <!--begin::Select2-->
                        <select id="farms" wire:model="selectedFarm" class="js-select2 form-control">
                            <option value="">=== Pilih Farm ===</option>
                            @foreach($farms as $farm)
                            <option value="{{ $farm->id }}">{{ $farm->nama }}</option>
                            @endforeach
                        </select>
                        <!--end::Select2-->
                        @error('selectedFarm')
                        <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <!--end::Input group-->

                    <div class="fv-row mb-7">
                        <label for="name" class="required fw-semibold fs-6 mb-2">Nama Storage</label>
                        <input type="text" wire:model="storageName" class="form-control" id="storageName" placeholder="Nama Storage">
                        @error('storageName') <span class="text-danger error">{{ $message }}</span>@enderror
                    </div>

                    <div class="fv-row mb-7">
                        <label for="code" class="required fw-semibold fs-6 mb-2">Kode Storage</label>
                        <input type="text" wire:model="storageCode" class="form-control" id="storageCode" placeholder="Kode Storage">
                        @error('storageCode') <span class="text-danger error">{{ $message }}</span>@enderror
                    </div>

                    <div class="fv-row mb-7">
                        <label for="type" class="required fw-semibold fs-6 mb-2">Tipe Storage</label>
                        <select wire:model="storageType" class="js-select2 form-control" id="storageType">
                            <option value="">=== Pilih Tipe ===</option>
                            <option value="warehouse">Warehouse</option>
                            <option value="farm">Farm</option>
                            <option value="kandang">Kandang</option>
                            <option value="silo">Silo</option>
                        </select>
                        @error('storageType') <span class="text-danger error">{{ $message }}</span>@enderror
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" wire:click="storeStorageLocation()">Save changes</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const farmSelect = document.getElementById('farms');
        var modal = document.getElementById('kt_modal_tambah_storage_farm');

        modal.addEventListener('show.bs.modal', function (event) {
            while (farmSelect.options.length > 0) {
                farmSelect.remove(0);
            }

            farmSelect.innerHTML = '<option value="">=== Pilih Farm ===</option>';

            const apiUrl = '/api/v1/get-farms/';
            fetch(apiUrl, {
                headers: {
                    'Authorization': 'Bearer ' + '{{ session('auth_token') }}',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.farms && data.farms.length > 0) {
                    data.farms.forEach(farm => {
                        const option = document.createElement('option');
                        option.value = farm.id;
                        option.text = farm.nama;
                        farmSelect.appendChild(option);
                    });
                }
            })
            .catch(error => console.error('Error fetching farms:', error));
        });
    });
</script>
@endpush