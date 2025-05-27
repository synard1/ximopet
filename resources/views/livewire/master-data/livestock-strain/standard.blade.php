<div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $isEditing ? 'Edit' : 'Create' }} Strain Standard</h3>
        </div>
        <div class="card-body">
            <form wire:submit.prevent="saveOrUpdateStrainStandard">
                <div class="mb-5">
                    <label class="required fw-semibold fs-6 mb-2">Strain</label>
                    <select wire:model="strain_id" class="form-select form-select-solid" @if($isEditing) disabled
                        @endif>
                        <option value="">Select a strain</option>
                        @foreach($strains as $strain)
                        <option value="{{ $strain->id }}">{{ $strain->name }}</option>
                        @endforeach
                    </select>
                    @if($isEditing)
                    <input type="hidden" wire:model="strain_id">
                    @endif
                    @error('strain_id') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="mb-5">
                    <label class="fw-semibold fs-6 mb-2">Description</label>
                    <textarea wire:model="description" class="form-control form-control-solid" rows="2"
                        placeholder="Enter description"></textarea>
                    @error('description') <span class="text-danger">{{ $message }}</span> @enderror
                </div>

                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-primary">
                        {{ $isEditing ? 'Update Strain Standard' : 'Create Strain Standard' }}
                    </button>
                </div>

                <div class="separator separator-dashed my-5"></div>

                @foreach($standards as $index => $standard)
                <div class="border rounded p-4 mb-4">
                    <div class="d-flex justify-content-between mb-4">
                        <h4 class="fw-bold">Standard #{{ (int)$index + 1 }}</h4>
                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmRemove({{ $index }})">
                            Remove
                        </button>
                    </div>

                    @if (session()->has("message-{$index}"))
                    <div class="alert alert-success">
                        {{ session("message-{$index}") }}
                    </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="required fw-semibold fs-6 mb-2">Umur (hari)</label>
                            <input type="number" wire:model="standards.{{ $index }}.umur"
                                class="form-control form-control-solid" placeholder="Enter umur" min="0">
                            @error("standards.{$index}.umur") <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <!-- Bobot Section -->
                        <div class="col-md-12">
                            <h5 class="fw-bold mb-3">Bobot (gram)</h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="required fw-semibold fs-6 mb-2">Minimum</label>
                                    <input type="number" step="0.01"
                                        wire:model="standards.{{ $index }}.standar_data.bobot.min"
                                        class="form-control form-control-solid">
                                    @error("standards.{$index}.standar_data.bobot.min") <span class="text-danger">{{
                                        $message }}</span> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="required fw-semibold fs-6 mb-2">Maximum</label>
                                    <input type="number" step="0.01"
                                        wire:model="standards.{{ $index }}.standar_data.bobot.max"
                                        class="form-control form-control-solid">
                                    @error("standards.{$index}.standar_data.bobot.max") <span class="text-danger">{{
                                        $message }}</span> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="required fw-semibold fs-6 mb-2">Target</label>
                                    <input type="number" step="0.01"
                                        wire:model="standards.{{ $index }}.standar_data.bobot.target"
                                        class="form-control form-control-solid">
                                    @error("standards.{$index}.standar_data.bobot.target") <span class="text-danger">{{
                                        $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Feed Intake Section -->
                        <div class="col-md-12">
                            <h5 class="fw-bold mb-3">Feed Intake (gram)</h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="required fw-semibold fs-6 mb-2">Minimum</label>
                                    <input type="number" step="0.01"
                                        wire:model="standards.{{ $index }}.standar_data.feed_intake.min"
                                        class="form-control form-control-solid">
                                    @error("standards.{$index}.standar_data.feed_intake.min") <span
                                        class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="required fw-semibold fs-6 mb-2">Maximum</label>
                                    <input type="number" step="0.01"
                                        wire:model="standards.{{ $index }}.standar_data.feed_intake.max"
                                        class="form-control form-control-solid">
                                    @error("standards.{$index}.standar_data.feed_intake.max") <span
                                        class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="required fw-semibold fs-6 mb-2">Target</label>
                                    <input type="number" step="0.01"
                                        wire:model="standards.{{ $index }}.standar_data.feed_intake.target"
                                        class="form-control form-control-solid">
                                    @error("standards.{$index}.standar_data.feed_intake.target") <span
                                        class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <!-- FCR Section -->
                        <div class="col-md-12">
                            <h5 class="fw-bold mb-3">FCR</h5>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="required fw-semibold fs-6 mb-2">Minimum</label>
                                    <input type="number" step="0.01"
                                        wire:model="standards.{{ $index }}.standar_data.fcr.min"
                                        class="form-control form-control-solid">
                                    @error("standards.{$index}.standar_data.fcr.min") <span class="text-danger">{{
                                        $message }}</span> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="required fw-semibold fs-6 mb-2">Maximum</label>
                                    <input type="number" step="0.01"
                                        wire:model="standards.{{ $index }}.standar_data.fcr.max"
                                        class="form-control form-control-solid">
                                    @error("standards.{$index}.standar_data.fcr.max") <span class="text-danger">{{
                                        $message }}</span> @enderror
                                </div>
                                <div class="col-md-4">
                                    <label class="required fw-semibold fs-6 mb-2">Target</label>
                                    <input type="number" step="0.01"
                                        wire:model="standards.{{ $index }}.standar_data.fcr.target"
                                        class="form-control form-control-solid">
                                    @error("standards.{$index}.standar_data.fcr.target") <span class="text-danger">{{
                                        $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-primary" wire:click="saveStandard({{ $index }})">
                            Save Standard
                        </button>
                    </div>
                </div>
                @endforeach

                <div class="text-center">
                    <button type="button" class="btn btn-secondary" wire:click="addStandard">
                        Add New Standard
                    </button>
                </div>


            </form>
        </div>
    </div>
</div>

<script>
    function confirmRemove(index) {
        Swal.fire({
            text: 'Are you sure you want to remove this standard?',
            icon: 'warning',
            buttonsStyling: false,
            showCancelButton: true,
            confirmButtonText: 'Yes, remove it!',
            cancelButtonText: 'No, keep it',
            customClass: {
                confirmButton: 'btn btn-danger',
                cancelButton: 'btn btn-secondary',
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Livewire.dispatch('removeStandard', [index]);
            }
        });
    }
</script>