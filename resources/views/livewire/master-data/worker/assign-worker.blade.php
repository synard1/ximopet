<div>
    @if($showFormAssignWorker)
    {{-- <div class="card-body p-6 bg-white" id="assign-worker-container" style="display: none;"> --}}
        {{-- Livestock Selection Section --}}
        <div class="mb-4">
            <label class="form-label fw-bold">Livestock (Batch)</label>
            <div class="input-group">
                <input type="text" wire:model.live="livestockName" class="form-control" value="{{ $livestockName }}"
                    disabled readonly>
                <input type="hidden" wire:model="livestockId">
            </div>
            @error('livestockId')
            <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror
        </div>

        {{-- Existing Batch Workers Table --}}
        @if($livestockId && count($existingBatchWorkers) > 0)
        <div class="mb-4">
            <div class="d-flex align-items-center mb-3">
                <i class="bi bi-info-circle-fill text-info me-2"></i>
                <h6 class="mb-0">Daftar Penugasan Pekerja</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap">Pekerja</th>
                            <th class="text-nowrap">Tanggal Mulai</th>
                            <th class="text-nowrap">Tanggal Berakhir</th>
                            <th class="text-nowrap">Peran</th>
                            <th class="text-nowrap">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($existingBatchWorkers as $bw)
                        <tr>
                            <td>{{ $bw->worker->name ?? '-' }}</td>
                            <td>{{ $bw->start_date ? $bw->start_date->format('Y-m-d') : '-' }}</td>
                            <td>{{ $bw->end_date ? $bw->end_date->format('Y-m-d') : '-' }}</td>
                            <td>{{ $bw->role ?? '-' }}</td>
                            <td>{{ $bw->notes ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if (!auth()->user()->can('create worker assignment') && !auth()->user()->can('update worker assignment'))
            <button wire:click="closeFormWorkerAssign" class="btn btn-danger rounded-lg px-6 py-2">Kembali ke
                Tabel</button>
            @endif
        </div>
        @else
        <div class="mb-4">
            <div class="d-flex align-items-center mb-3">
                <i class="bi bi-info-circle-fill text-info me-2"></i>
                <h6 class="mb-0">Daftar Penugasan Pekerja</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-nowrap">Pekerja</th>
                            <th class="text-nowrap">Tanggal Mulai</th>
                            <th class="text-nowrap">Tanggal Berakhir</th>
                            <th class="text-nowrap">Peran</th>
                            <th class="text-nowrap">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" class="text-center">Tidak ada data</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @if (!auth()->user()->can('create worker assignment') && !auth()->user()->can('update worker assignment'))
            <button wire:click="closeFormWorkerAssign" class="btn btn-danger rounded-lg px-6 py-2">Kembali ke
                Tabel</button>
            @endif
        </div>
        @endif

        {{-- Worker Assignment Form --}}
        @if($livestockId)
        @if (auth()->user()->can('create worker assignment'))
        <div class="mb-4">
            <div class="d-flex align-items-center mb-3">
                <i class="bi bi-person-plus-fill text-primary me-2"></i>
                <h6 class="mb-0">Form Penugasan Pekerja</h6>
            </div>

            @foreach ($workersData as $index => $worker)
            <div class="border rounded p-4 mb-3 {{ !empty($worker['id']) ? 'bg-light' : 'bg-white' }}">
                <div class="row g-3">
                    {{-- Worker Selection --}}
                    <div class="col-md-5">
                        <label class="form-label">Pekerja</label>
                        <select wire:model="workersData.{{ $index }}.worker_id"
                            class="form-select @error('workersData.'.$index.'.worker_id') is-invalid @enderror" {{
                            !empty($worker['id']) ? 'disabled' : '' }}>
                            <option value="">-- Pilih Pekerja --</option>
                            @foreach ($workers as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                        @error('workersData.'.$index.'.worker_id')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Start Date --}}
                    <div class="col-md-3">
                        <label class="form-label">Tanggal Mulai</label>
                        <input type="date" wire:model="workersData.{{ $index }}.start_date"
                            class="form-control @error('workersData.'.$index.'.start_date') is-invalid @enderror" {{
                            !empty($worker['id']) ? 'disabled' : '' }}>
                        @error('workersData.'.$index.'.start_date')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Role --}}
                    <div class="col-md-3">
                        <label class="form-label">Peran (Opsional)</label>
                        <input type="text" wire:model="workersData.{{ $index }}.role"
                            class="form-control @error('workersData.'.$index.'.role') is-invalid @enderror">
                        @error('workersData.'.$index.'.role')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Delete Button --}}
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger w-100"
                            wire:click="confirmDeleteWorker({{ $index }})">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>

                    {{-- Notes --}}
                    <div class="col-12">
                        <label class="form-label">Catatan (Opsional)</label>
                        <textarea wire:model="workersData.{{ $index }}.notes"
                            class="form-control @error('workersData.'.$index.'.notes') is-invalid @enderror"
                            rows="2"></textarea>
                        @error('workersData.'.$index.'.notes')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- Database Info --}}
                    @if(!empty($worker['id']))
                    <div class="col-12">
                        <div class="alert alert-info py-2 mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Data ini diambil dari database. Peran dan catatan dapat diubah, field lainnya tidak dapat
                            diubah.
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach

            {{-- Action Buttons --}}
            <div class="d-flex gap-2 mt-3">
                <button type="button" class="btn btn-primary" wire:click="addWorkerRow">
                    <i class="bi bi-plus-circle me-1"></i> Tambah Pekerja
                </button>
                <button wire:click="closeFormWorkerAssign" class="btn btn-danger rounded-lg px-6 py-2">Kembali ke
                    Tabel</button>

                <button class="btn btn-success" wire:click="save">
                    <i class="bi bi-save me-1"></i> Simpan
                </button>
            </div>
        </div>
        @endif
        @else
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle me-2"></i>
            Silakan pilih Livestock terlebih dahulu
        </div>
        @endif

        {{-- End Date Modal --}}
        @if ($showEndDateModal)
        <div class="modal fade show d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Akhiri Tugas Pekerja</h5>
                        <button type="button" class="btn-close" wire:click="closeEndDateModal"></button>
                    </div>
                    <div class="modal-body">
                        <x-input.group label="Tanggal Berakhir">
                            <input type="date" wire:model="endDateToDelete"
                                class="form-control @error('endDateToDelete') is-invalid @enderror">
                            @error('endDateToDelete')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </x-input.group>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeEndDateModal">Batal</button>
                        <button type="button" class="btn btn-primary" wire:click="confirmEndWorker">Akhiri
                            Tugas</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
        @endif

        {{-- Delete Options Modal --}}
        @if($showDeleteOptionsModal)
        <div class="modal fade show d-block" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Opsi Penghapusan Data Pekerja</h5>
                        <button type="button" class="btn-close"
                            wire:click="$set('showDeleteOptionsModal', false)"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Data pekerja ini sudah memiliki tanggal berakhir. Apa yang ingin Anda lakukan?
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-warning" wire:click="updateEndDateOption">
                            <i class="bi bi-calendar-plus me-1"></i> Update Tanggal Berakhir
                        </button>
                        <button type="button" class="btn btn-danger" wire:click="deleteWorkerPermanent">
                            <i class="bi bi-trash me-1"></i> Hapus Permanen
                        </button>
                        <button type="button" class="btn btn-secondary"
                            wire:click="$set('showDeleteOptionsModal', false)">Batal</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
        @endif
        @endif
    </div>