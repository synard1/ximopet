<x-default-layout>
    @section('title')
    Laporan Batch Pekerja
    @endsection

    @if(auth()->user()->can('read report batch worker'))
    <livewire:reports.batch-worker-report />

    @else
    <div class="card">
        <div class="card-body">
            <div class="text-center">
                <i class="fas fa-lock fa-3x text-danger mb-3"></i>
                <h3 class="text-danger">Unauthorized Access</h3>
                <p class="text-muted">You do not have permission to view report batch worker.</p>
            </div>
        </div>
    </div>
    @endif




</x-default-layout>