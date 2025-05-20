<div>
    @if ($showForm)
    <form wire:submit.prevent="update">
        <div class="row g-9">
            <div class="col-md-6 fv-row">
                <label class="required fs-6 fw-semibold mb-2">Supply</label>
                <select wire:model="supply_id"
                    class="form-select form-select-solid @error('supply_id') is-invalid @enderror" required>
                    <option value="">Select Supply</option>
                    @foreach($supplies as $supply)
                    <option value="{{ $supply->id }}">{{ $supply->name }}</option>
                    @endforeach
                </select>
                @error('supply_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 fv-row">
                <label class="required fs-6 fw-semibold mb-2">Farm</label>
                <select wire:model="farm_id"
                    class="form-select form-select-solid @error('farm_id') is-invalid @enderror" required>
                    <option value="">Select Farm</option>
                    @foreach($farms as $farm)
                    <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                    @endforeach
                </select>
                @error('farm_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 fv-row">
                <label class="required fs-6 fw-semibold mb-2">Kandang</label>
                <select wire:model="kandang_id"
                    class="form-select form-select-solid @error('kandang_id') is-invalid @enderror" required>
                    <option value="">Select Kandang</option>
                    @foreach($kandangs as $kandang)
                    <option value="{{ $kandang->id }}">{{ $kandang->name }}</option>
                    @endforeach
                </select>
                @error('kandang_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 fv-row">
                <label class="required fs-6 fw-semibold mb-2">Quantity</label>
                <input type="number" step="0.01" wire:model="quantity"
                    class="form-control form-control-solid @error('quantity') is-invalid @enderror" required>
                @error('quantity')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 fv-row">
                <label class="required fs-6 fw-semibold mb-2">Unit</label>
                <select wire:model="unit_id"
                    class="form-select form-select-solid @error('unit_id') is-invalid @enderror" required>
                    <option value="">Select Unit</option>
                    @foreach($units as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
                @error('unit_id')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 fv-row">
                <label class="required fs-6 fw-semibold mb-2">Usage Date</label>
                <input type="date" wire:model="usage_date"
                    class="form-control form-control-solid @error('usage_date') is-invalid @enderror" required>
                @error('usage_date')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12 fv-row">
                <label class="fs-6 fw-semibold mb-2">Notes</label>
                <textarea wire:model="notes"
                    class="form-control form-control-solid @error('notes') is-invalid @enderror" rows="3"></textarea>
                @error('notes')
                <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="d-flex justify-content-end pt-10">
            <button type="button" class="btn btn-light me-3" wire:click="$set('showForm', false)">Cancel</button>
            <button type="submit" class="btn btn-primary">
                <i class="ki-duotone ki-check fs-2"></i>Update Record
            </button>
        </div>
    </form>
    @endif
</div>