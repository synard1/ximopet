<x-default-layout>
    @section('title')
    Create Livestock Batch
    @endsection

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Create New Livestock Batch</h3>
        </div>
        <div class="card-body">
            <p>This page is for creating new livestock batch data.</p>
            <a href="{{ route('livestock.batch.index') }}" class="btn btn-secondary">
                <i class="ki-duotone ki-arrow-left fs-2"></i>
                Back to Livestock
            </a>
        </div>
    </div>
</x-default-layout>