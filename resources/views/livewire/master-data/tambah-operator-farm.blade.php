<div class="modal fade" tabindex="-1" role="dialog" id="kt_modal_tambah_operator_farm" data-bs-backdrop="static"
    data-bs-keyboard="false" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Data</h5>
            </div>
            <div class="modal-body">
                <form id="form_farm_operator">
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Farm</label>
                        <!--end::Label-->
                        <!--begin::Select2-->
                        <select wire:model="selectedFarm" name="farm_id" id="farm_id" class="form-select">
                            <option value="">=== Pilih Farm ===</option>
                            @foreach($farms as $farm)
                            <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                            @endforeach
                        </select>
                        <!--end::Select2-->
                        @error('selectedFarm')
                        <span class="text-danger">{{ $message }}</span> @enderror
                        @error('selectedFarm')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <!--end::Input group-->

                    <div class="fv-row mb-7">
                        <label for="name" class="required fw-semibold fs-6 mb-2">Nama Operator</label>
                        <select wire:model="selectedOperator" class="js-select2 form-control" id="operators">
                            <option value="">=== Pilih Operator ===</option>
                            @foreach ($operators as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                        @error('selectedOperator') <span class="text-danger error">{{ $message }}</span>@enderror
                    </div>

                    {{--
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Status</label>
                        <!--end::Label-->
                        <!--begin::Select2-->
                        <select id="status" name="status" wire:model="status" class="js-select2 form-control">
                            <option value="">=== Pilih ===</option>
                            <option value="Aktif">Aktif</option>
                            <option value="Tidak Aktif">Tidak Aktif</option>
                        </select>
                        <!--end::Select2-->
                        @error('status')
                        <span class="text-danger">{{ $message }}</span> @enderror
                        @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <!--end::Input group--> --}}
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" wire:click="storeFarmOperator()">Save changes</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Select the farm dropdown
        const farmSelect = document.getElementById('farms');
        const operatorSelect = document.getElementById('operators');
        var modal = document.getElementById('kt_modal_tambah_operator_farm');


        modal.addEventListener('show.bs.modal', function (event) {
            // Clear existing options in the farmSelect dropdown
            while (farmSelect.options.length > 0) {
                farmSelect.remove(0);
            }

            // Reset operator dropdown
            farmSelect.innerHTML = '<option value="">=== Pilih Farm ===</option>';

            // Fetch operators for the selected farm via AJAX
            // Replace this URL with your actual API endpoint
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
                            option.text = farm.name;
                            farmSelect.appendChild(option);
                        });
                    }
                    console.log(data);
                })
                .catch(error => console.error('Error fetching operators:', error));
        });





    farmSelect.addEventListener('change', function () {
        const selectedFarm = this.value;

        console.log(selectedFarm);

        // Reset operator dropdown
        operatorSelect.innerHTML = '<option value="">=== Pilih Operator ===</option>';

        if (selectedFarm) {
            // Fetch operators for the selected farm via AJAX
            // Replace this URL with your actual API endpoint
			const apiUrl = `/api/v1/get-operators/${selectedFarm}`;
	
            fetch(apiUrl, {
                headers: {
                                'Authorization': 'Bearer ' + '{{ session('auth_token') }}',
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
            })
                .then(response => response.json())
                .then(data => {
                    if (data.operators && data.operators.length > 0) {
                        data.operators.forEach(operator => {
                            const option = document.createElement('option');
                            option.value = operator.id;
                            option.text = operator.name;
                            operatorSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error fetching operators:', error));
        }
    });
});

</script>
@endpush