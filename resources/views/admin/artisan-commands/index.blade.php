<x-default-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            <i class="fas fa-tools"></i>
            Artisan Commands - Data Integrity Management
        </h2>
        <p class="text-sm text-gray-600 mt-1">
            Kelola dan jalankan perintah Artisan untuk perbaikan data integritas
        </p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Main Content -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-database"></i>
                        Data Integrity Commands
                    </h3>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                            data-bs-target="#commandModal">
                            <i class="fas fa-play"></i> Execute Command
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Command Categories -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card bg-light-primary">
                                <div class="card-header">
                                    <h4 class="card-title">
                                        <i class="fas fa-database"></i>
                                        Available Commands
                                    </h4>
                                </div>
                                <div class="card-body">
                                    @foreach($commands as $command)
                                    @if($command['category'] === 'Data Integrity')
                                    <div class="command-item mb-3 p-3 border rounded">
                                        <h5 class="text-primary">{{ $command['name'] }}</h5>
                                        <p class="text-muted">{{ $command['description'] }}</p>

                                        @if(!empty($command['options']))
                                        <div class="mb-2">
                                            <strong>Options:</strong>
                                            <ul class="list-unstyled ms-3">
                                                @foreach($command['options'] as $option => $description)
                                                <li><code>--{{ $option }}</code>: {{ $description }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        @endif

                                        @if(!empty($command['parameters']))
                                        <div class="mb-2">
                                            <strong>Parameters:</strong>
                                            <ul class="list-unstyled ms-3">
                                                @foreach($command['parameters'] as $param => $description)
                                                <li><code>{{ $param }}</code>: {{ $description }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        @endif

                                        <button type="button" class="btn btn-sm btn-outline-primary execute-command"
                                            data-command="{{ $command['name'] }}">
                                            <i class="fas fa-play"></i> Execute
                                        </button>
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card bg-light-info">
                                <div class="card-header">
                                    <h4 class="card-title">
                                        <i class="fas fa-history"></i>
                                        Recent Executions
                                    </h4>
                                </div>
                                <div class="card-body">
                                    @if(!empty($recentExecutions))
                                    @foreach($recentExecutions as $execution)
                                    <div class="execution-item mb-3 p-3 border rounded">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="text-primary">{{ $execution['command'] }}</h6>
                                                <small class="text-muted">
                                                    {{ \Carbon\Carbon::parse($execution['executed_at'])->format('Y-m-d
                                                    H:i:s') }}
                                                </small>
                                            </div>
                                            <span
                                                class="badge {{ $execution['exit_code'] === 0 ? 'bg-success' : 'bg-danger' }}">
                                                {{ $execution['exit_code'] === 0 ? 'Success' : 'Failed' }}
                                            </span>
                                        </div>
                                        @if(!empty($execution['options']))
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                Options: {{ implode(', ', array_keys($execution['options'])) }}
                                            </small>
                                        </div>
                                        @endif
                                    </div>
                                    @endforeach
                                    @else
                                    <p class="text-muted">No recent executions</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Command Execution Modal -->
    <div class="modal fade" id="commandModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-terminal"></i>
                        Execute Artisan Command
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="commandForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="command" class="form-label">Command</label>
                                    <select class="form-select" id="command" name="command" required>
                                        <option value="">Select a command...</option>
                                        @foreach($commands as $cmd)
                                        <option value="{{ $cmd['name'] }}" data-description="{{ $cmd['description'] }}"
                                            data-options="{{ json_encode($cmd['options']) }}"
                                            data-parameters="{{ json_encode($cmd['parameters']) }}">
                                            {{ $cmd['name'] }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Command Description</label>
                                    <p id="commandDescription" class="text-muted">Select a command to see description
                                    </p>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="livestock_id" class="form-label">Livestock ID (Optional)</label>
                                    <input type="text" class="form-control" id="livestock_id" name="livestock_id"
                                        placeholder="Enter livestock ID">
                                </div>

                                <div class="mb-3">
                                    <label for="date" class="form-label">Specific Date (Optional)</label>
                                    <input type="date" class="form-control" id="date" name="date">
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="start_date" class="form-label">Start Date (Optional)</label>
                                            <input type="date" class="form-control" id="start_date" name="start_date">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="end_date" class="form-label">End Date (Optional)</label>
                                            <input type="date" class="form-control" id="end_date" name="end_date">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="mb-3">
                                    <label class="form-label">Command Options</label>
                                    <div id="commandOptions" class="border rounded p-3">
                                        <p class="text-muted">Select a command to see available options</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="dry_run" name="dry_run">
                                    <label class="form-check-label" for="dry_run">
                                        Dry Run (Preview changes without applying)
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="force" name="force">
                                    <label class="form-check-label" for="force">
                                        Force (Skip confirmation)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Preview:</strong>
                                    <span id="previewText">Select parameters to see preview</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-info" id="previewBtn">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-play"></i> Execute Command
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Results Modal -->
    <div class="modal fade" id="resultsModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-terminal"></i>
                        Command Execution Results
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="resultsContent">
                        <!-- Results will be loaded here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        $(document).ready(function() {
        // Update command description when command is selected
        $('#command').change(function() {
            const selectedOption = $(this).find('option:selected');
            const description = selectedOption.data('description');
            const options = selectedOption.data('options');
            const parameters = selectedOption.data('parameters');
            
            $('#commandDescription').text(description);
            
            // Update options display
            let optionsHtml = '';
            if (options && Object.keys(options).length > 0) {
                optionsHtml = '<div class="row">';
                Object.keys(options).forEach(option => {
                    optionsHtml += `
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="options[]" value="${option}" id="option_${option}">
                                <label class="form-check-label" for="option_${option}">
                                    <code>--${option}</code>: ${options[option]}
                                </label>
                            </div>
                        </div>
                    `;
                });
                optionsHtml += '</div>';
            } else {
                optionsHtml = '<p class="text-muted">No specific options available</p>';
            }
            $('#commandOptions').html(optionsHtml);
            
            updatePreview();
        });
        
        // Update preview when parameters change
        $('input, select').on('change keyup', function() {
            updatePreview();
        });
        
        function updatePreview() {
            const command = $('#command').val();
            const livestockId = $('#livestock_id').val();
            const date = $('#date').val();
            const startDate = $('#start_date').val();
            const endDate = $('#end_date').val();
            
            if (!command) {
                $('#previewText').text('Select a command to see preview');
                return;
            }
            
            let preview = `Command: ${command}`;
            if (livestockId) preview += `\nLivestock ID: ${livestockId}`;
            if (date) preview += `\nDate: ${date}`;
            if (startDate && endDate) preview += `\nDate Range: ${startDate} to ${endDate}`;
            
            $('#previewText').text(preview);
        }
        
        // Preview button
        $('#previewBtn').click(function() {
            const formData = new FormData($('#commandForm')[0]);
            
            $.ajax({
                url: '{{ route("administrator.artisan-commands.preview") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        showPreviewResults(response.preview);
                    } else {
                        alert('Preview failed: ' + response.message);
                    }
                },
                error: function() {
                    alert('Preview request failed');
                }
            });
        });
        
        // Execute command
        $('#commandForm').submit(function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to execute this command?')) {
                return;
            }
            
            const formData = new FormData(this);
            
            $.ajax({
                url: '{{ route("administrator.artisan-commands.execute") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    showExecutionResults(response);
                },
                error: function(xhr) {
                    const response = xhr.responseJSON;
                    alert('Command execution failed: ' + (response?.message || 'Unknown error'));
                }
            });
        });
        
        function showPreviewResults(preview) {
            let content = '<div class="alert alert-info"><h6>Preview Results:</h6>';
            
            if (preview.total_records !== undefined) {
                content += `<p><strong>Total Records:</strong> ${preview.total_records}</p>`;
            }
            
            if (preview.negative_stock !== undefined) {
                content += `<p><strong>Negative Stock:</strong> ${preview.negative_stock}</p>`;
            }
            
            if (preview.zero_stock_awal !== undefined) {
                content += `<p><strong>Zero Stock Awal:</strong> ${preview.zero_stock_awal}</p>`;
            }
            
            if (preview.null_depletion !== undefined) {
                content += `<p><strong>Null Depletion:</strong> ${preview.null_depletion}</p>`;
            }
            
            if (preview.sample_records && preview.sample_records.length > 0) {
                content += '<h6>Sample Records:</h6><div class="table-responsive"><table class="table table-sm">';
                content += '<thead><tr><th>ID</th><th>Date</th><th>Livestock</th><th>Stock Awal</th><th>Stock Akhir</th><th>Total Depletion</th><th>Issues</th></tr></thead><tbody>';
                
                preview.sample_records.forEach(record => {
                    content += `<tr>
                        <td>${record.id}</td>
                        <td>${record.date}</td>
                        <td>${record.livestock_name}</td>
                        <td>${record.stock_awal}</td>
                        <td>${record.stock_akhir}</td>
                        <td>${record.total_deplesi}</td>
                        <td>${record.issues.join(', ')}</td>
                    </tr>`;
                });
                
                content += '</tbody></table></div>';
            }
            
            content += '</div>';
            
            $('#resultsContent').html(content);
            $('#resultsModal').modal('show');
        }
        
        function showExecutionResults(response) {
            let content = '';
            
            if (response.success) {
                content += '<div class="alert alert-success"><h6>✅ Command Executed Successfully</h6></div>';
            } else {
                content += '<div class="alert alert-danger"><h6>❌ Command Failed</h6></div>';
            }
            
            content += '<div class="card"><div class="card-header"><h6>Command Output:</h6></div><div class="card-body">';
            content += '<pre class="bg-light p-3 rounded">' + response.output + '</pre>';
            content += '</div></div>';
            
            $('#resultsContent').html(content);
            $('#resultsModal').modal('show');
            
            // Close command modal
            $('#commandModal').modal('hide');
            
            // Reload page after successful execution to update recent executions
            if (response.success) {
                setTimeout(() => {
                    location.reload();
                }, 2000);
            }
        }
        
        // Quick execute buttons
        $('.execute-command').click(function() {
            const command = $(this).data('command');
            $('#command').val(command).trigger('change');
            $('#commandModal').modal('show');
        });
    });
    </script>
    @endpush
</x-default-layout>