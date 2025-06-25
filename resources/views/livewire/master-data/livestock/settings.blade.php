<div id="livestockSettingContainer">
    @if($showFormSettings)
    <div class="card-header">
        <h5 class="card-title">Pengaturan Metode Pencatatan Ayam {{ $livestock_name }}</h5>
    </div>
    <div class="card-body">
        <form wire:submit.prevent="saveRecordingMethod">
            @if(session()->has('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            {{-- Single/Multiple Batch Status Alert --}}
            @if($has_single_batch)
            <div class="alert alert-info mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle me-2"></i>
                    <div>
                        <strong>Status Batch:</strong> Ternak ini hanya memiliki 1 batch aktif.<br>
                        <small class="text-muted">Metode pencatatan otomatis diset ke "Total" dan opsi lain menggunakan
                            default values.</small>
                    </div>
                </div>
            </div>
            @elseif(isset($has_single_batch) && !$has_single_batch)
            <div class="alert alert-success mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-layer-group me-2"></i>
                    <div>
                        <strong>Status Batch:</strong> Ternak ini memiliki lebih dari 1 batch aktif.<br>
                        <small class="text-muted">Metode pencatatan otomatis diset ke <b>"Batch"</b> dan opsi lain dapat
                            dipilih sesuai kebutuhan.</small>
                    </div>
                </div>
            </div>
            @endif

            <div class="mb-2">
                <span class="badge bg-info">Sumber Config: {{ $is_override ? 'Override per Ternak' : 'Default
                    Perusahaan' }}</span>
            </div>

            {{-- Recording Method --}}
            <div class="mb-5">
                <label class="form-label">
                    Metode Pencatatan
                    @if($has_single_batch)
                    <span class="badge bg-warning ms-2">Auto-set ke Total</span>
                    @elseif(isset($has_single_batch) && !$has_single_batch)
                    <span class="badge bg-primary ms-2">Auto-set ke Batch</span>
                    @endif
                </label>
                <select class="form-select" wire:model="recording_method" disabled>
                    @foreach($available_methods['recording_method'] as $method)
                    @if($has_single_batch && $method === 'total')
                    <option value="{{ $method }}" selected>{{ strtoupper($method) }}</option>
                    @elseif(!$has_single_batch && $method === 'batch')
                    <option value="{{ $method }}" selected>{{ strtoupper($method) }}</option>
                    @else
                    <option value="{{ $method }}">{{ strtoupper($method) }}</option>
                    @endif
                    @endforeach
                </select>
                <div class="form-text text-muted">
                    <i class="fas fa-lock me-1"></i>
                    @if($has_single_batch)
                    Metode pencatatan <b>dikunci ke "Total"</b> karena hanya ada 1 batch aktif.
                    @else
                    Metode pencatatan <b>dikunci ke "Batch"</b> karena ada lebih dari 1 batch aktif.
                    @endif
                </div>
            </div>

            {{-- Depletion Method --}}
            <div class="mb-5">
                <label class="form-label">
                    Metode Depletion
                    @if($has_single_batch)
                    <span class="badge bg-success ms-2">Default FIFO</span>
                    @endif
                </label>

                {{-- Debug Information --}}
                @if(config('app.debug'))
                <div class="alert alert-warning small mb-2">
                    <strong>Debug:</strong> Available depletion methods:
                    @if(isset($available_methods['depletion_methods']))
                    {{ count($available_methods['depletion_methods']) }} methods -
                    {{ implode(', ', array_keys($available_methods['depletion_methods'])) }}
                    @else
                    No depletion methods found
                    @endif
                </div>
                @endif

                <div class="col-md-4 mb-3">
                    <label for="depletion_method" class="form-label">Metode Depletion</label>
                    <select class="form-select" wire:model="depletion_method" id="depletion_method" {{ $has_single_batch
                        ? 'disabled' : '' }}>
                        @if(isset($available_methods['depletion_methods']))
                        @foreach($available_methods['depletion_methods'] as $key => $method)
                        @php
                        $isUsable = isset($method['enabled']) && $method['enabled'] === true &&
                        isset($method['status']) && $method['status'] === 'ready';
                        $statusText = $this->getStatusText($method);
                        @endphp
                        <option value="{{ $key }}" {{ !$isUsable ? 'disabled' : '' }}>
                            {{ strtoupper($key) }} ({{ $statusText }})
                        </option>
                        @endforeach
                        @else
                        <option value="">Tidak ada metode tersedia</option>
                        @endif
                    </select>
                    @if($has_single_batch)
                    <div class="form-text text-muted">
                        <i class="fas fa-lock me-1"></i>
                        Metode depletion dikunci karena hanya ada 1 batch aktif.
                    </div>
                    @endif
                </div>
            </div>

            {{-- Mutation Method --}}
            <div class="mb-5">
                <label class="form-label">
                    Metode Mutasi
                    @if($has_single_batch)
                    <span class="badge bg-success ms-2">Default FIFO</span>
                    @endif
                </label>

                {{-- Debug Information --}}
                @if(config('app.debug'))
                <div class="alert alert-warning small mb-2">
                    <strong>Debug:</strong> Available mutation methods:
                    @if(isset($available_methods['mutation_methods']))
                    {{ count($available_methods['mutation_methods']) }} methods -
                    {{ implode(', ', array_keys($available_methods['mutation_methods'])) }}
                    @else
                    No mutation methods found
                    @endif
                </div>
                @endif

                <div class="col-md-4 mb-3">
                    <label for="mutation_method" class="form-label">Metode Mutasi</label>
                    <select class="form-select" wire:model="mutation_method" id="mutation_method" {{ $has_single_batch
                        ? 'disabled' : '' }}>
                        @if(isset($available_methods['mutation_methods']))
                        @foreach($available_methods['mutation_methods'] as $key => $method)
                        @php
                        $isUsable = isset($method['enabled']) && $method['enabled'] === true &&
                        isset($method['status']) && $method['status'] === 'ready';
                        $statusText = $this->getStatusText($method);
                        @endphp
                        <option value="{{ $key }}" {{ !$isUsable ? 'disabled' : '' }}>
                            {{ strtoupper($key) }} ({{ $statusText }})
                        </option>
                        @endforeach
                        @else
                        <option value="">Tidak ada metode tersedia</option>
                        @endif
                    </select>
                    @if($has_single_batch)
                    <div class="form-text text-muted">
                        <i class="fas fa-lock me-1"></i>
                        Metode mutasi dikunci karena hanya ada 1 batch aktif.
                    </div>
                    @endif
                </div>
            </div>

            {{-- Feed Usage Method --}}
            <div class="mb-5">
                <label class="form-label">
                    Metode Pemakaian Pakan
                    @if($has_single_batch)
                    <span class="badge bg-success ms-2">Default Total</span>
                    @else
                    <span class="badge bg-success ms-2">Default FIFO</span>
                    @endif
                </label>

                {{-- Debug Information --}}
                @if(config('app.debug') && !$has_single_batch)
                <div class="alert alert-warning small mb-2">
                    <strong>Debug:</strong> Available feed usage methods:
                    @if(isset($available_methods['feed_usage_methods']))
                    {{ count($available_methods['feed_usage_methods']) }} methods -
                    {{ implode(', ', array_keys($available_methods['feed_usage_methods'])) }}
                    @else
                    No feed usage methods found
                    @endif
                </div>
                @endif

                <div class="col-md-4 mb-3">
                    <label for="feed_usage_method" class="form-label">Metode Pemakaian Pakan</label>
                    <select class="form-select" wire:model="feed_usage_method" id="feed_usage_method" {{
                        $has_single_batch ? 'disabled' : '' }}>
                        @if($has_single_batch)
                        <option value="total" selected>TOTAL (Tersedia)</option>
                        @else
                        @if(isset($available_methods['feed_usage_methods']))
                        @foreach($available_methods['feed_usage_methods'] as $key => $method)
                        @php
                        $isUsable = isset($method['enabled']) && $method['enabled'] === true &&
                        isset($method['status']) && $method['status'] === 'ready';
                        $statusText = $this->getStatusText($method);
                        @endphp
                        <option value="{{ $key }}" {{ !$isUsable ? 'disabled' : '' }}>
                            {{ strtoupper($key) }} ({{ $statusText }})
                        </option>
                        @endforeach
                        @else
                        <option value="">Tidak ada metode tersedia</option>
                        @endif
                        @endif
                    </select>
                    @if($has_single_batch)
                    <div class="form-text text-muted">
                        <i class="fas fa-lock me-1"></i>
                        Metode pemakaian pakan dikunci ke "TOTAL" untuk single batch.
                    </div>
                    @endif
                </div>
            </div>

            {{-- LEGEND STATUS METODE --}}
            <div class="mb-4">
                <div class="card card-body bg-light-subtle border-info">
                    <h6 class="mb-2 text-dark"><i class="fas fa-info-circle me-1"></i> <b>Legend Status Metode</b></h6>
                    <ul class="mb-0 ps-3 text-dark">
                        <li><b>Metode Pencatatan:</b>
                            <span class="badge bg-primary text-white">Batch</span> = Pencatatan per batch,
                            <span class="badge bg-warning text-dark">Total</span> = Pencatatan total tanpa batch.
                        </li>
                        <li><b>Status Metode:</b>
                            <span class="badge bg-success text-white">Tersedia</span> = Siap digunakan,
                            <span class="badge bg-danger text-white">Dalam Pengembangan</span> = Belum dapat dipilih,
                            <span class="badge bg-secondary text-white">Tidak Aktif</span> = Tidak diaktifkan.
                        </li>
                        <li><b>Konfigurasi Saat Ini:</b>
                            <ul class="mt-1">
                                <li><strong>Depletion:</strong> FIFO (Tersedia), LIFO (Dalam Pengembangan), MANUAL
                                    (Tersedia)</li>
                                <li><strong>Mutasi:</strong> FIFO (Tersedia), LIFO (Dalam Pengembangan), MANUAL (Dalam
                                    Pengembangan)</li>
                                <li><strong>Pemakaian Pakan:</strong> FIFO (Tersedia), LIFO (Dalam Pengembangan), MANUAL
                                    (Tersedia)</li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-between">
                <button type="button" class="btn btn-secondary" wire:click="closeSettings">
                    <i class="fas fa-arrow-left me-1"></i>
                    Kembali
                </button>
                <div>
                    @if(config('app.debug'))
                    <button type="button" class="btn btn-warning me-2" wire:click="testConfig">
                        <i class="fas fa-bug me-1"></i>
                        Test Config
                    </button>
                    @endif
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>
                        Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>
    @endif
</div>