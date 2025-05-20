<x-default-layout>

    @section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h2>AI File Changes History</h2>
                    </div>

                    <div class="card-body">
                        <div class="mb-4">
                            <form action="{{ route('ai-changes.by-date') }}" method="GET" class="form-inline">
                                <div class="form-group">
                                    <input type="date" name="date" class="form-control"
                                        value="{{ request('date', date('Y-m-d')) }}">
                                    <button type="submit" class="btn btn-primary ml-2">View Changes</button>
                                </div>
                            </form>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>File</th>
                                        <th>Change Type</th>
                                        <th>Changed At</th>
                                        <th>Description</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($changes as $change)
                                    <tr>
                                        <td>{{ $change->file_path }}</td>
                                        <td>{{ $change->change_type }}</td>
                                        <td>{{ $change->changed_at->format('Y-m-d H:i:s') }}</td>
                                        <td>{{ $change->description }}</td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="showDiff('{{ $change->id }}')">
                                                View Changes
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for showing diff -->
    <div class="modal fade" id="diffModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">File Changes</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="diffContent"></div>
                </div>
            </div>
        </div>
    </div>

    @endsection

    @push('scripts')
    <script>
        function showDiff(changeId) {
    // Here you would typically make an AJAX call to get the diff
    // For now, we'll just show a placeholder
    $('#diffContent').html('Loading diff...');
    $('#diffModal').modal('show');
}
    </script>
    @endpush

</x-default-layout>