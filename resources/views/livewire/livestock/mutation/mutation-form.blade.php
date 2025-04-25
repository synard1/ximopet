<div class="card shadow-sm border-0" id="cardForm" style="display: none;">
    <form wire:submit.prevent="save" class="card-body">
        <h5 class="mb-4 fw-semibold text-primary">📦 Form Mutasi Ternak</h5>

        @if (session()->has('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <div class="row g-3 row-cols-1 row-cols-md-2">

            <div class="col">
                <div class="form-floating">
                    <input wire:model="date" type="date" id="date" class="form-control" placeholder="Tanggal Mutasi">
                    <label for="date">Tanggal Mutasi</label>
                </div>
                @error('date') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="col">
                <div class="form-floating">
                    <select wire:model="from_livestock_id" id="from_livestock_id" class="form-select">
                        <option value="">-- Pilih --</option>
                        @foreach ($livestocks as $ls)
                            <option value="{{ $ls->id }}">{{ $ls->name }}</option>
                        @endforeach
                    </select>
                    <label for="from_livestock_id">Dari Ternak</label>
                </div>
                @error('from_livestock_id') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="col">
                <div class="form-floating">
                    <select wire:model="to_livestock_id" id="to_livestock_id" class="form-select">
                        <option value="">-- Pilih --</option>
                        @foreach ($livestocks as $ls)
                            <option value="{{ $ls->id }}">{{ $ls->name }}</option>
                        @endforeach
                    </select>
                    <label for="to_livestock_id">Ke Ternak</label>
                </div>
                @error('to_livestock_id') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="col">
                <div class="form-floating">
                    <input wire:model="quantity" type="number" id="quantity" class="form-control" placeholder="Jumlah Mutasi">
                    <label for="quantity">Jumlah Mutasi</label>
                </div>
                @error('quantity') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

            <div class="col">
                <div class="form-floating">
                    <input wire:model="weight" type="number" step="0.01" id="weight" class="form-control" placeholder="Berat Mutasi (kg)">
                    <label for="weight">Berat Mutasi (kg)</label>
                </div>
                @error('weight') <small class="text-danger">{{ $message }}</small> @enderror
            </div>

        </div>

        <div class="mt-4 d-flex justify-content-between">
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-save me-1"></i> Simpan
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('cardForm').style.display='none'">
                Batal
            </button>
        </div>
    </form>
</div>
