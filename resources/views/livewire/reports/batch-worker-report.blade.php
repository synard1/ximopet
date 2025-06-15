<div>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Laporan Penugasan Pekerja</h3>
        </div>
        <div class="card-body">
            <form wire:submit="generateReport">
                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label for="farmId" class="form-label required">Farm</label>
                        <select wire:model.live="farmId" class="form-select @error('farmId') is-invalid @enderror">
                            <option value="">Pilih Farm</option>
                            @foreach($farms as $farm)
                            <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                            @endforeach
                        </select>
                        @error('farmId')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label for="coopId" class="form-label required">Kandang</label>
                        <select wire:model.live="coopId" class="form-select @error('coopId') is-invalid @enderror"
                            @if(!$farmId) disabled @endif>
                            <option value="">Pilih Kandang</option>
                            @foreach($coops as $coop)
                            <option value="{{ $coop->id }}">{{ $coop->name }}</option>
                            @endforeach
                        </select>
                        @error('coopId')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label for="tahun" class="form-label required">Tahun</label>
                        <select wire:model.live="tahun" class="form-select @error('tahun') is-invalid @enderror"
                            @if(!$coopId) disabled @endif>
                            <option value="">Pilih Tahun</option>
                            @foreach($tahunList as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                            @endforeach
                        </select>
                        @error('tahun')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label for="periodeId" class="form-label required">Periode (Batch)</label>
                        <select wire:model.live="periodeId" class="form-select @error('periodeId') is-invalid @enderror"
                            @if(!$tahun) disabled @endif>
                            <option value="">Pilih Periode</option>
                            @foreach($periodeList as $periode)
                            <option value="{{ $periode->id }}">{{ $periode->name }}</option>
                            @endforeach
                        </select>
                        @error('periodeId')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label for="reportType" class="form-label required">Jenis Laporan</label>
                        <select wire:model="reportType" class="form-select @error('reportType') is-invalid @enderror">
                            <option value="detail">Detail</option>
                            <option value="simple">Simple</option>
                        </select>
                        @error('reportType')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary" @if(!$periodeId) disabled @endif>
                        <i class="fas fa-search me-2"></i>Tampilkan
                    </button>
                    <button type="button" class="btn btn-success ms-2" wire:click="exportReport('excel')"
                        @if(!$periodeId) disabled @endif>
                        <i class="fas fa-file-excel me-2"></i>Export Excel
                    </button>
                    <button type="button" class="btn btn-danger ms-2" wire:click="exportReport('pdf')" @if(!$periodeId)
                        disabled @endif>
                        <i class="fas fa-file-pdf me-2"></i>Export PDF
                    </button>
                    <button type="button" class="btn btn-secondary ms-2" wire:click="resetForm">
                        <i class="fas fa-redo me-2"></i>Reset
                    </button>
                    <button type="button" class="btn btn-info ms-2" onclick="printReport()" @if(!$showReport) disabled
                        @endif>
                        <i class="fas fa-print me-2"></i>Print
                    </button>
                </div>
            </form>

            @if($showReport)
            <div class="mt-5" id="print-area">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">Hasil Laporan</h4>
                    </div>
                    <div class="card-body">
                        @if($reportType === 'detail')
                        @include('livewire.reports.partials.batch-worker-detail')
                        @else
                        @include('livewire.reports.partials.batch-worker-simple')
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
<script>
    function printReport() {
    var printContents = document.getElementById('print-area').innerHTML;
    var originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload(); // reload to restore events and state
}
</script>