<div>
    <div class="card card-flush">
        <!--begin::Card header-->
        <div class="card-header pt-5">
            <!--begin::Card title-->
            <div class="card-title d-flex flex-column">
                <h2>OVK Records</h2>
                <div class="fs-6 fw-semibold text-muted">Manage OVK usage records</div>
            </div>
            <!--end::Card title-->
            <!--begin::Card toolbar-->
            <div class="card-toolbar">
                <button type="button" class="btn btn-primary" wire:click="$set('showForm', true)">
                    <i class="ki-duotone ki-plus fs-2"></i>Add New Record
                </button>
            </div>
            <!--end::Card toolbar-->
        </div>
        <!--end::Card header-->
        <!--begin::Card body-->
        <div class="card-body pt-2">
            @if (session()->has('success'))
            <div class="alert alert-success d-flex align-items-center p-5 mb-10">
                <i class="ki-duotone ki-shield-tick fs-2hx text-success me-4"><span class="path1"></span><span
                        class="path2"></span></i>
                <div class="d-flex flex-column">
                    <h4 class="mb-1 text-success">Success</h4>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
            @endif

            @if (session()->has('error'))
            <div class="alert alert-danger d-flex align-items-center p-5 mb-10">
                <i class="ki-duotone ki-shield-cross fs-2hx text-danger me-4"><span class="path1"></span><span
                        class="path2"></span></i>
                <div class="d-flex flex-column">
                    <h4 class="mb-1 text-danger">Error</h4>
                    <span>{{ session('error') }}</span>
                </div>
            </div>
            @endif

            @if ($showForm)
            @include('livewire.ovk.create')
            @else
            {{ $dataTable->table(['class' => 'table align-middle table-row-dashed fs-6 gy-5']) }}
            @endif
        </div>
        <!--end::Card body-->
    </div>
</div>

@push('scripts')
{{ $dataTable->scripts() }}
@endpush