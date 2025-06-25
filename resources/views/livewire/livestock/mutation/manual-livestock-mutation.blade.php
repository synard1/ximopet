<div>
    {{-- Manual Livestock Mutation Modal --}}
    <div class="modal fade" id="manualLivestockMutationModal" tabindex="-1"
        aria-labelledby="manualLivestockMutationModalLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                {{-- Modal Header --}}
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="manualLivestockMutationModalLabel">
                        <i class="ki-duotone ki-arrows-loop fs-2 me-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        {{ $isEditing ? 'Edit Mutasi Ternak Manual' : 'Mutasi Ternak Manual' }}
                    </h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="closeModal"></button>
                </div>

                {{-- Modal Body --}}
                <div class="modal-body">
                    {{-- Edit Mode Alert --}}
                    @if($isEditing)
                    <div class="alert alert-info d-flex align-items-center mb-6">
                        <i class="ki-duotone ki-information-5 fs-2hx text-info me-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        <div class="d-flex flex-column">
                            <h4 class="mb-1 text-info">Mode Edit Aktif</h4>
                            <span>{{ $editModeMessage }}</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-light-warning ms-auto" wire:click="cancelEditMode">
                            <i class="ki-duotone ki-cross fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            Batal Edit
                        </button>
                    </div>
                    @endif

                    {{-- Error Message --}}
                    @if($errorMessage)
                    <div class="alert alert-danger d-flex align-items-center mb-6">
                        <i class="ki-duotone ki-cross-circle fs-2hx text-danger me-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <div class="d-flex flex-column">
                            <h4 class="mb-1 text-danger">Terjadi Kesalahan</h4>
                            <span>{{ $errorMessage }}</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-light-danger ms-auto"
                            wire:click="$set('errorMessage', '')">
                            <i class="ki-duotone ki-cross fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </button>
                    </div>
                    @endif

                    {{-- Success Message --}}
                    @if($successMessage)
                    <div class="alert alert-success d-flex align-items-center mb-6">
                        <i class="ki-duotone ki-check-circle fs-2hx text-success me-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        <div class="d-flex flex-column">
                            <h4 class="mb-1 text-success">Berhasil</h4>
                            <span>{{ $successMessage }}</span>
                        </div>
                        <button type="button" class="btn btn-sm btn-light-success ms-auto"
                            wire:click="$set('successMessage', '')">
                            <i class="ki-duotone ki-cross fs-2">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                        </button>
                    </div>
                    @endif

                    {{-- Loading Overlay --}}
                    @if($isLoading)
                    <div class="overlay overlay-block rounded">
                        <div class="overlay-layer bg-dark bg-opacity-5 rounded">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Main Form --}}
                    <form wire:submit.prevent="processMutation">
                        <div class="row">
                            {{-- Left Column - Basic Information --}}
                            <div class="col-lg-6">
                                <div class="card card-flush h-100">
                                    <div class="card-header">
                                        <h3 class="card-title">Informasi Mutasi</h3>
                                    </div>
                                    <div class="card-body">
                                        {{-- Mutation Date --}}
                                        <div class="fv-row mb-7">
                                            <label class="required fw-semibold fs-6 mb-2">Tanggal Mutasi</label>
                                            <input type="date" class="form-control form-control-solid"
                                                wire:model.live="mutationDate" required />
                                            @error('mutationDate')
                                            <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        {{-- Source Livestock --}}
                                        <div class="fv-row mb-7">
                                            <label class="required fw-semibold fs-6 mb-2">Ternak Sumber</label>
                                            <select class="form-select form-select-solid"
                                                wire:model.live="sourceLivestockId" required>
                                                <option value="">Pilih Ternak Sumber</option>
                                                @foreach($allLivestock as $livestock)
                                                <option value="{{ $livestock['id'] }}">
                                                    {{ $livestock['display_name'] }} ({{ $livestock['current_quantity']
                                                    }} ekor)
                                                </option>
                                                @endforeach
                                            </select>
                                            @error('sourceLivestockId')
                                            <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                            @enderror

                                            {{-- Show selected source livestock info --}}
                                            @if($sourceLivestock)
                                            <div class="alert alert-light-success mt-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="ki-duotone ki-check-circle fs-2hx text-success me-4">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                    </i>
                                                    <div>
                                                        <h5 class="mb-1">{{ $sourceLivestock->name }}</h5>
                                                        <p class="mb-0">
                                                            Farm: {{ $sourceLivestock->farm->name ?? 'Unknown' }} |
                                                            Kandang: {{ $sourceLivestock->coop->name ?? 'Unknown' }} |
                                                            Saat ini: {{ $sourceLivestock->currentLivestock->quantity ??
                                                            0 }} ekor
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                        </div>

                                        {{-- Mutation Type --}}
                                        <div class="fv-row mb-7">
                                            <label class="required fw-semibold fs-6 mb-2">Jenis Mutasi</label>
                                            <select class="form-select form-select-solid" wire:model.live="mutationType"
                                                required>
                                                <option value="internal">Transfer Internal</option>
                                                <option value="external">Transfer Eksternal</option>
                                                <option value="farm_transfer">Transfer Antar Farm</option>
                                                <option value="location_transfer">Transfer Lokasi</option>
                                                <option value="emergency_transfer">Transfer Darurat</option>
                                            </select>
                                            @error('mutationType')
                                            <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        {{-- Mutation Direction --}}
                                        <div class="fv-row mb-7">
                                            <label class="required fw-semibold fs-6 mb-2">Arah Mutasi</label>
                                            <div class="row">
                                                <div class="col-6">
                                                    <label class="form-check form-check-custom form-check-solid">
                                                        <input class="form-check-input" type="radio"
                                                            wire:model.live="mutationDirection" value="out" />
                                                        <span class="form-check-label fw-semibold">
                                                            Keluar (Out)
                                                        </span>
                                                    </label>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-check form-check-custom form-check-solid">
                                                        <input class="form-check-input" type="radio"
                                                            wire:model.live="mutationDirection" value="in" />
                                                        <span class="form-check-label fw-semibold">
                                                            Masuk (In)
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                            @error('mutationDirection')
                                            <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        {{-- Destination Selection (for outgoing mutations) --}}
                                        @if($mutationDirection === 'out')
                                        <div class="fv-row mb-7">
                                            <label class="required fw-semibold fs-6 mb-2">Kandang Tujuan</label>
                                            <select class="form-select form-select-solid"
                                                wire:model.live="destinationCoopId" required>
                                                <option value="">Pilih Kandang Tujuan</option>
                                                @foreach($allCoops as $coop)
                                                <option value="{{ $coop['id'] }}">
                                                    {{ $coop['display_name'] }}
                                                </option>
                                                @endforeach
                                            </select>
                                            @error('destinationCoopId')
                                            <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                            @enderror

                                            {{-- Show destination coop info --}}
                                            @if($destinationCoop)
                                            <div class="alert alert-light-info mt-3">
                                                <div class="d-flex align-items-center">
                                                    <i class="ki-duotone ki-information-5 fs-2hx text-info me-4">
                                                        <span class="path1"></span>
                                                        <span class="path2"></span>
                                                        <span class="path3"></span>
                                                    </i>
                                                    <div>
                                                        <h5 class="mb-1">{{ $destinationCoop->name }}</h5>
                                                        <p class="mb-0">
                                                            Farm: {{ $destinationCoop->farm->name ?? 'Unknown' }} |
                                                            Kapasitas: {{ $destinationCoop->capacity }} ekor |
                                                            Saat ini: {{ $destinationCoop->livestocks->sum(function($l)
                                                            { return $l->currentLivestock->quantity ?? 0; }) }} ekor
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            @endif
                                        </div>

                                        {{-- Alternative: Ternak Tujuan (untuk kompatibilitas) --}}
                                        <div class="fv-row mb-7">
                                            <label class="fw-semibold fs-6 mb-2">
                                                Ternak Tujuan
                                                <small class="text-muted">(opsional, jika ada ternak spesifik di
                                                    kandang)</small>
                                            </label>
                                            <select class="form-select form-select-solid"
                                                wire:model.live="destinationLivestockId">
                                                <option value="">Pilih Ternak Tujuan (Opsional)</option>
                                                @foreach($allLivestock as $livestock)
                                                @if($livestock['id'] !== $sourceLivestockId)
                                                <option value="{{ $livestock['id'] }}">
                                                    {{ $livestock['display_name'] }} ({{ $livestock['current_quantity']
                                                    }} ekor)
                                                </option>
                                                @endif
                                                @endforeach
                                            </select>
                                            @error('destinationLivestockId')
                                            <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        @endif

                                        {{-- Reason --}}
                                        <div class="fv-row mb-7">
                                            <label class="fw-semibold fs-6 mb-2">Alasan Mutasi</label>
                                            <input type="text" class="form-control form-control-solid"
                                                wire:model="reason" placeholder="Masukkan alasan mutasi (opsional)" />
                                        </div>

                                        {{-- Notes --}}
                                        <div class="fv-row mb-0">
                                            <label class="fw-semibold fs-6 mb-2">Catatan</label>
                                            <textarea class="form-control form-control-solid" wire:model="notes"
                                                rows="3" placeholder="Catatan tambahan (opsional)"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Right Column - Batch Selection --}}
                            <div class="col-lg-6">
                                <div class="card card-flush h-100">
                                    <div class="card-header">
                                        <h3 class="card-title">Seleksi Batch Manual</h3>
                                        <div class="card-toolbar">
                                            @if($sourceLivestockId)
                                            <span class="badge badge-light-info">
                                                {{ count($availableBatches) }} batch tersedia
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        {{-- Batch Selection Form --}}
                                        @if($sourceLivestockId && !empty($availableBatches))
                                        <div class="mb-7">
                                            <label class="fw-semibold fs-6 mb-2">Pilih Batch</label>
                                            <select class="form-select form-select-solid mb-3"
                                                wire:model.live="selectedBatchId"
                                                @if(count($manualBatches)===count($availableBatches)) disabled @endif>
                                                <option value="">Pilih Batch</option>
                                                @foreach($availableBatches as $batch)
                                                @if(!collect($manualBatches)->pluck('batch_id')->contains($batch['batch_id']))
                                                <option value="{{ $batch['batch_id'] }}">
                                                    {{ $batch['batch_name'] }} ({{ $batch['available_quantity'] }} ekor
                                                    tersedia{{ isset($batch['age_days']) ? ', ' . $batch['age_days'] . '
                                                    hari' : '' }})
                                                </option>
                                                @endif
                                                @endforeach
                                            </select>
                                            @if(count($manualBatches) === count($availableBatches))
                                            <div class="alert alert-info mt-2">Semua batch sudah dipilih.</div>
                                            @endif

                                            @if($selectedBatchId && count($manualBatches) < count($availableBatches))
                                                <div class="row mb-3">
                                                <div class="col-8">
                                                    <input type="number" class="form-control form-control-solid"
                                                        wire:model.live="selectedBatchQuantity"
                                                        placeholder="Jumlah ekor" min="1" />
                                                </div>
                                                <div class="col-4">
                                                    <button type="button" class="btn btn-primary w-100"
                                                        wire:click="addBatch" @if(!$this->canAddBatch) disabled
                                                        @endif>
                                                        <i class="ki-duotone ki-plus fs-2"></i>
                                                        Tambah
                                                    </button>

                                                    {{-- Debug Info (remove in production) --}}
                                                    @if(config('app.debug'))
                                                    <small class="text-muted d-block mt-1">
                                                        canAdd: {{ $this->canAddBatch ? 'true' : 'false' }}<br>
                                                        BatchId: {{ $selectedBatchId ? 'set' : 'empty' }}<br>
                                                        Quantity: {{ $selectedBatchQuantity ?? 'null' }} ({{
                                                        gettype($selectedBatchQuantity) }})
                                                    </small>
                                                    @endif
                                                </div>
                                        </div>

                                        <input type="text" class="form-control form-control-solid"
                                            wire:model="selectedBatchNote"
                                            placeholder="Catatan untuk batch ini (opsional)" />
                                        @endif
                                    </div>
                                    @elseif($sourceLivestockId && empty($availableBatches))
                                    <div class="alert alert-warning">
                                        <i class="ki-duotone ki-information fs-2hx text-warning me-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                        Tidak ada batch yang tersedia untuk mutasi pada ternak ini.
                                    </div>
                                    @else
                                    <div class="alert alert-info">
                                        <i class="ki-duotone ki-information fs-2hx text-info me-4">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                        Pilih ternak sumber terlebih dahulu untuk melihat batch yang tersedia.
                                    </div>
                                    @endif

                                    {{-- Selected Batches List --}}
                                    @if(!empty($manualBatches))
                                    <div class="separator my-7"></div>

                                    <h4 class="fw-bold mb-4">
                                        Batch Terpilih
                                        <span class="badge badge-light-primary ms-2">
                                            {{ $this->totalBatches }} batch, {{ $this->totalQuantity }} ekor
                                        </span>
                                    </h4>

                                    <div class="table-responsive">
                                        <table class="table table-row-dashed table-row-gray-300 gy-7">
                                            <thead>
                                                <tr class="fw-bold fs-6 text-gray-800">
                                                    <th>Batch</th>
                                                    <th>Jumlah</th>
                                                    <th>Umur</th>
                                                    <th>Catatan</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($manualBatches as $index => $batch)
                                                <tr>
                                                    <td>
                                                        <span class="fw-bold">{{ $batch['batch_name'] }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-light-warning">{{
                                                            $batch['quantity'] }} ekor</span>
                                                    </td>
                                                    <td>
                                                        {{ $batch['age_days'] ?? '-' }} hari
                                                    </td>
                                                    <td>
                                                        {{ $batch['note'] ?? '-' }}
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-light-danger"
                                                            wire:click="removeBatch({{ $index }})">
                                                            <i class="ki-duotone ki-trash fs-2"></i> Hapus
                                                        </button>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @endif

                                    {{-- Tombol Preview dan Proses --}}
                                    @if(!empty($manualBatches))
                                    <div class="d-flex justify-content-end mt-8">
                                        <button type="button" class="btn btn-light me-3" wire:click="showPreview"
                                            @if(!$this->canProcess) disabled @endif>
                                            <i class="ki-duotone ki-eye fs-2"></i> Preview
                                        </button>
                                        <button type="submit" class="btn btn-primary" @if(!$this->canProcess)
                                            disabled @endif>
                                            <i class="ki-duotone ki-check fs-2"></i> Proses Mutasi
                                        </button>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                </div>

                {{-- Preview Section --}}
                @if($showPreview && !empty($previewData))
                <div class="separator my-10"></div>

                <div class="card card-flush">
                    <div class="card-header">
                        <h3 class="card-title">Preview Mutasi</h3>
                        <div class="card-toolbar">
                            <button type="button" class="btn btn-sm btn-light" wire:click="hidePreview">
                                <i class="ki-duotone ki-cross fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                Tutup Preview
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-3">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-40px me-3">
                                        <div class="symbol-label bg-light-primary">
                                            <i class="ki-duotone ki-chart-simple fs-1 text-primary">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                                <span class="path4"></span>
                                            </i>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-muted fw-semibold d-block">Total Quantity</span>
                                        <span class="text-gray-800 fw-bold fs-6">{{
                                            $previewData['total_quantity'] }} ekor</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-40px me-3">
                                        <div class="symbol-label bg-light-info">
                                            <i class="ki-duotone ki-category fs-1 text-info">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                                <span class="path4"></span>
                                            </i>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-muted fw-semibold d-block">Batches</span>
                                        <span class="text-gray-800 fw-bold fs-6">{{
                                            $previewData['batches_count'] }} batch</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-40px me-3">
                                        <div
                                            class="symbol-label {{ $previewData['can_fulfill'] ? 'bg-light-success' : 'bg-light-danger' }}">
                                            <i
                                                class="ki-duotone {{ $previewData['can_fulfill'] ? 'ki-check-circle' : 'ki-cross-circle' }} fs-1 {{ $previewData['can_fulfill'] ? 'text-success' : 'text-danger' }}">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-muted fw-semibold d-block">Status</span>
                                        <span
                                            class="fw-bold fs-6 {{ $previewData['can_fulfill'] ? 'text-success' : 'text-danger' }}">
                                            {{ $previewData['can_fulfill'] ? 'Dapat Diproses' : 'Tidak Dapat
                                            Diproses' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="d-flex align-items-center">
                                    <div class="symbol symbol-40px me-3">
                                        <div class="symbol-label bg-light-warning">
                                            <i class="ki-duotone ki-gear fs-1 text-warning">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-muted fw-semibold d-block">Method</span>
                                        <span class="text-gray-800 fw-bold fs-6">{{ $previewData['method']
                                            }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if(!empty($previewData['errors']))
                        <div class="alert alert-danger mt-5">
                            <h4 class="alert-heading">Validation Errors:</h4>
                            <ul class="mb-0">
                                @foreach($previewData['errors'] as $error)
                                <li>{{ $error['error'] }} (Batch ID: {{ $error['batch_id'] }})</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif

                        @if(!empty($previewData['batches_preview']))
                        <div class="table-responsive mt-5">
                            <table class="table table-row-dashed table-row-gray-300 gy-7">
                                <thead>
                                    <tr class="fw-bold fs-6 text-gray-800">
                                        <th>Batch</th>
                                        <th>Umur</th>
                                        <th>Tersedia</th>
                                        <th>Diminta</th>
                                        <th>Status</th>
                                        <th>Kekurangan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($previewData['batches_preview'] as $batch)
                                    <tr>
                                        <td>{{ $batch['batch_name'] }}</td>
                                        <td>{{ $batch['batch_age_days'] ?? 0 }} hari</td>
                                        <td>{{ $batch['available_quantity'] }} ekor</td>
                                        <td>{{ $batch['requested_quantity'] }} ekor</td>
                                        <td>
                                            <span
                                                class="badge {{ $batch['can_fulfill'] ? 'badge-light-success' : 'badge-light-danger' }}">
                                                {{ $batch['can_fulfill'] ? 'OK' : 'Kurang' }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($batch['shortfall'] > 0)
                                            <span class="text-danger">{{ $batch['shortfall'] }} ekor</span>
                                            @else
                                            <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
                </form>
            </div>

            {{-- Modal Footer --}}
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" wire:click="closeModal">
                    <i class="ki-duotone ki-cross fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Tutup
                </button>

                @if($this->canProcess && !empty($manualBatches))
                <button type="button" class="btn btn-info" wire:click="showPreview" @if($isLoading) disabled @endif>
                    <i class="ki-duotone ki-eye fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                    </i>
                    Preview
                </button>

                <button type="button" class="btn btn-primary" wire:click="processMutation" @if($isLoading) disabled
                    @endif>
                    @if($isLoading)
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Processing...
                    @else
                    <i class="ki-duotone ki-check fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    {{ $isEditing ? 'Update Mutasi' : 'Proses Mutasi' }}
                    @endif
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- JavaScript for Modal Handling --}}
<script>
    document.addEventListener('livewire:init', function () {
            // Listen for modal events
            Livewire.on('show-livestock-mutation', function () {
                const modal = new bootstrap.Modal(document.getElementById('manualLivestockMutationModal'));
                modal.show();
            });

            Livewire.on('close-livestock-mutation', function () {
                const modal = bootstrap.Modal.getInstance(document.getElementById('manualLivestockMutationModal'));
                if (modal) {
                    modal.hide();
                }
            });

            Livewire.on('mutation-completed', function (event) {
                // SUPPORT ARRAY PAYLOAD (Livewire dispatches)
                let payload = Array.isArray(event) ? event[0] : event;
                console.group('[ManualLivestockMutation] mutation-completed event');
                console.log('Event payload:', payload);

                if (payload.result) {
                    console.log('Mutation result:', payload.result);
                }
                const modal = bootstrap.Modal.getInstance(document.getElementById('manualLivestockMutationModal'));
                console.log('Modal instance before:', modal);

                if (payload.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: payload.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                    }
                    if (modal) {
                        modal.hide();
                        console.log('Modal closed after success.');
                    }
                } else {
                    let errorMsg = payload.message || 'Terjadi kesalahan saat memproses mutasi.';
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Error!',
                            text: errorMsg,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                    console.warn('Mutation failed:', errorMsg);
                }
                const modalAfter = bootstrap.Modal.getInstance(document.getElementById('manualLivestockMutationModal'));
                console.log('Modal instance after:', modalAfter);
                console.groupEnd();
            });

            Livewire.on('edit-mode-enabled', function (event) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Mode Edit Diaktifkan',
                        text: event.message,
                        icon: 'info',
                        confirmButtonText: 'OK'
                    });
                }
            });

            Livewire.on('edit-mode-cancelled', function (event) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Edit Mode Dibatalkan',
                        text: event.message,
                        icon: 'info',
                        confirmButtonText: 'OK'
                    });
                }
            });
        });

        // Global function for debugging
        function openLivestockMutationModal(livestockId = null, editData = null) {
            Livewire.dispatch('openMutationModal', { livestockId: livestockId, editData: editData });
        }

        function closeLivestockMutationModal() {
            Livewire.dispatch('closeMutationModal');
        }
</script>
</div>