<div class="card">
    <!-- Header -->
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <h3 class="fw-bold">Konfigurasi Livestock Purchase</h3>
            <span class="text-muted fs-6">Kelola pengaturan sistem pembelian ternak</span>
        </div>

        <div class="card-toolbar">
            @if(!$isEditing)
            <button type="button" class="btn btn-primary" wire:click="startEditing">
                <i class="fas fa-edit"></i> Edit Konfigurasi
            </button>
            @else
            <div class="d-flex gap-2">
                @if($hasChanges)
                <button type="button" class="btn btn-success" wire:click="saveConfiguration">
                    <i class="fas fa-save"></i> Simpan
                </button>
                @endif
                <button type="button" class="btn btn-secondary" wire:click="cancelEditing">
                    <i class="fas fa-times"></i> Batal
                </button>
            </div>
            @endif
        </div>
    </div>

    <!-- Configuration Summary -->
    <div class="card-body border-bottom">
        <div class="row g-4">
            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-50px me-3">
                        <div class="symbol-label bg-light-primary">
                            <i class="fas fa-route text-primary fs-2x"></i>
                        </div>
                    </div>
                    <div>
                        <div class="fw-bold fs-6">{{ $summary['enabled_statuses'] }}/{{ $summary['total_statuses'] }}
                        </div>
                        <div class="text-muted fs-7">Status Aktif</div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-50px me-3">
                        <div class="symbol-label bg-light-success">
                            <i class="fas fa-check-circle text-success fs-2x"></i>
                        </div>
                    </div>
                    <div>
                        <div class="fw-bold fs-6">{{ $summary['approval_required'] ? 'Ya' : 'Tidak' }}</div>
                        <div class="text-muted fs-7">Approval Diperlukan</div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-50px me-3">
                        <div class="symbol-label bg-light-warning">
                            <i class="fas fa-boxes text-warning fs-2x"></i>
                        </div>
                    </div>
                    <div>
                        <div class="fw-bold fs-6">{{ $summary['batch_management_enabled'] ? 'Aktif' : 'Nonaktif' }}
                        </div>
                        <div class="text-muted fs-7">Batch Management</div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="d-flex align-items-center">
                    <div class="symbol symbol-50px me-3">
                        <div class="symbol-label bg-light-info">
                            <i class="fas fa-bell text-info fs-2x"></i>
                        </div>
                    </div>
                    <div>
                        <div class="fw-bold fs-6">{{ $summary['notification_channels'] }}</div>
                        <div class="text-muted fs-7">Channel Notifikasi</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="card-body">
        <ul class="nav nav-tabs nav-line-tabs mb-5 fs-6">
            @foreach($tabs as $tabKey => $tabName)
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === $tabKey ? 'active' : '' }}" wire:click="setTab('{{ $tabKey }}')"
                    style="cursor: pointer;">
                    {{ $tabName }}
                </a>
            </li>
            @endforeach
        </ul>

        <!-- Tab Content -->
        <div class="tab-content">
            <!-- Workflow Tab -->
            @if($activeTab === 'workflow')
            <div class="tab-pane fade show active">
                <div class="row">
                    <div class="col-12">
                        <h5 class="mb-4">Status Workflow</h5>

                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                                <thead>
                                    <tr class="fw-bold text-muted">
                                        <th>Status</th>
                                        <th>Deskripsi</th>
                                        <th>Dapat Diedit</th>
                                        <th>Dapat Dihapus</th>
                                        <th>Perlu Approval</th>
                                        <th>Auto Numbering</th>
                                        <th>Status Berikutnya</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($workflowConfig['status_flow'] ?? [] as $status => $config)
                                    <tr>
                                        <td>
                                            <span class="badge badge-{{ $config['enabled'] ? 'success' : 'danger' }}">
                                                {{ $config['enabled'] ? 'Aktif' : 'Nonaktif' }}
                                            </span>
                                            <span class="fw-bold ms-2">{{ ucfirst($status) }}</span>
                                        </td>
                                        <td>{{ $config['description'] ?? '-' }}</td>
                                        <td>
                                            <span class="badge badge-{{ $config['can_edit'] ? 'success' : 'danger' }}">
                                                {{ $config['can_edit'] ? 'Ya' : 'Tidak' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge badge-{{ $config['can_delete'] ? 'success' : 'danger' }}">
                                                {{ $config['can_delete'] ? 'Ya' : 'Tidak' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge badge-{{ $config['requires_approval'] ? 'warning' : 'success' }}">
                                                {{ $config['requires_approval'] ? 'Ya' : 'Tidak' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge badge-{{ $config['auto_numbering'] ? 'success' : 'danger' }}">
                                                {{ $config['auto_numbering'] ? 'Ya' : 'Tidak' }}
                                            </span>
                                        </td>
                                        <td>
                                            @foreach($config['next_statuses'] ?? [] as $nextStatus)
                                            <span class="badge badge-light-primary me-1">{{ ucfirst($nextStatus)
                                                }}</span>
                                            @endforeach
                                        </td>
                                        <td>
                                            @if($isEditing)
                                            <button type="button"
                                                class="btn btn-sm btn-{{ $config['enabled'] ? 'danger' : 'success' }}"
                                                wire:click="toggleWorkflowStatus('{{ $status }}')">
                                                {{ $config['enabled'] ? 'Nonaktifkan' : 'Aktifkan' }}
                                            </button>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Validation Tab -->
            @if($activeTab === 'validation')
            <div class="tab-pane fade show active">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-4">Field Wajib</h5>

                        @foreach($validationConfig['required_fields'] ?? [] as $field => $required)
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="required_{{ $field }}"
                                wire:model="validationConfig.required_fields.{{ $field }}" {{ $isEditing ? ''
                                : 'disabled' }}>
                            <label class="form-check-label" for="required_{{ $field }}">
                                {{ ucwords(str_replace('_', ' ', $field)) }}
                            </label>
                        </div>
                        @endforeach
                    </div>

                    <div class="col-md-6">
                        <h5 class="mb-4">Business Rules</h5>

                        @foreach($validationConfig['business_rules'] ?? [] as $rule => $value)
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="rule_{{ $rule }}"
                                wire:model="validationConfig.business_rules.{{ $rule }}" {{ $isEditing ? '' : 'disabled'
                                }}>
                            <label class="form-check-label" for="rule_{{ $rule }}">
                                {{ ucwords(str_replace('_', ' ', $rule)) }}
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Approval Tab -->
            @if($activeTab === 'approval')
            <div class="tab-pane fade show active">
                <div class="row">
                    <div class="col-12">
                        <h5 class="mb-4">Level Approval</h5>

                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                                <thead>
                                    <tr class="fw-bold text-muted">
                                        <th>Level</th>
                                        <th>Nama</th>
                                        <th>Role</th>
                                        <th>Wajib</th>
                                        <th>Auto Approve</th>
                                        <th>Threshold</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($approvalConfig['levels'] ?? [] as $level => $config)
                                    <tr>
                                        <td>{{ $level }}</td>
                                        <td>
                                            @if($isEditing)
                                            <input type="text" class="form-control form-control-sm"
                                                wire:model="approvalConfig.levels.{{ $level }}.name">
                                            @else
                                            {{ $config['name'] }}
                                            @endif
                                        </td>
                                        <td>
                                            @if($isEditing)
                                            <input type="text" class="form-control form-control-sm"
                                                wire:model="approvalConfig.levels.{{ $level }}.roles"
                                                placeholder="admin,manager">
                                            @else
                                            {{ implode(', ', $config['roles'] ?? []) }}
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $config['required'] ? 'warning' : 'success' }}">
                                                {{ $config['required'] ? 'Ya' : 'Tidak' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span
                                                class="badge badge-{{ $config['auto_approve'] ? 'success' : 'danger' }}">
                                                {{ $config['auto_approve'] ? 'Ya' : 'Tidak' }}
                                            </span>
                                        </td>
                                        <td>{{ number_format($config['threshold'] ?? 0) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Batch Management Tab -->
            @if($activeTab === 'batch')
            <div class="tab-pane fade show active">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-4">Batch Management</h5>

                        @foreach($batchConfig as $feature => $enabled)
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="batch_{{ $feature }}"
                                wire:model="batchConfig.{{ $feature }}" {{ $isEditing ? '' : 'disabled' }}>
                            <label class="form-check-label" for="batch_{{ $feature }}">
                                {{ ucwords(str_replace('_', ' ', $feature)) }}
                            </label>
                        </div>
                        @endforeach
                    </div>

                    <div class="col-md-6">
                        <h5 class="mb-4">Batch Settings</h5>

                        @if(isset($batchConfig['auto_create_batch']))
                        <div class="mb-3">
                            <label class="form-label">Naming Convention</label>
                            <input type="text" class="form-control"
                                wire:model="batchConfig.auto_create_batch.naming_convention" {{ $isEditing ? ''
                                : 'readonly' }} placeholder="LSP-{FARM}-{COOP}-{DATE}-{SEQ}">
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Cost Tracking Tab -->
            @if($activeTab === 'cost')
            <div class="tab-pane fade show active">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-4">Cost Tracking Methods</h5>

                        @foreach($costConfig['tracking_methods'] ?? [] as $method => $config)
                        <div class="card mb-3">
                            <div class="card-header">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="cost_{{ $method }}"
                                        wire:model="costConfig.tracking_methods.{{ $method }}.enabled" {{ $isEditing
                                        ? '' : 'disabled' }}>
                                    <label class="form-check-label fw-bold" for="cost_{{ $method }}">
                                        {{ ucwords(str_replace('_', ' ', $method)) }}
                                    </label>
                                </div>
                            </div>
                            <div class="card-body">
                                @foreach($config as $setting => $value)
                                @if($setting !== 'enabled')
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox"
                                        id="cost_{{ $method }}_{{ $setting }}"
                                        wire:model="costConfig.tracking_methods.{{ $method }}.{{ $setting }}" {{
                                        $isEditing ? '' : 'disabled' }}>
                                    <label class="form-check-label" for="cost_{{ $method }}_{{ $setting }}">
                                        {{ ucwords(str_replace('_', ' ', $setting)) }}
                                    </label>
                                </div>
                                @endif
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="col-md-6">
                        <h5 class="mb-4">Cost Validation</h5>

                        @foreach($costConfig['cost_validation'] ?? [] as $rule => $value)
                        <div class="mb-3">
                            <label class="form-label">{{ ucwords(str_replace('_', ' ', $rule)) }}</label>
                            <input type="number" class="form-control"
                                wire:model="costConfig.cost_validation.{{ $rule }}" {{ $isEditing ? '' : 'readonly' }}>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Document Management Tab -->
            @if($activeTab === 'document')
            <div class="tab-pane fade show active">
                <div class="row">
                    <div class="col-12">
                        <h5 class="mb-4">Required Documents</h5>

                        <div class="table-responsive">
                            <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                                <thead>
                                    <tr class="fw-bold text-muted">
                                        <th>Document</th>
                                        <th>Required</th>
                                        <th>Format</th>
                                        <th>Max Size</th>
                                        <th>OCR Required</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($documentConfig['required_documents'] ?? [] as $doc => $config)
                                    <tr>
                                        <td>{{ ucwords(str_replace('_', ' ', $doc)) }}</td>
                                        <td>
                                            <span class="badge badge-{{ $config['required'] ? 'warning' : 'success' }}">
                                                {{ $config['required'] ? 'Ya' : 'Tidak' }}
                                            </span>
                                        </td>
                                        <td>{{ implode(', ', $config['validation']['format'] ?? []) }}</td>
                                        <td>{{ $config['validation']['max_size'] ?? '-' }}</td>
                                        <td>
                                            <span
                                                class="badge badge-{{ $config['validation']['require_ocr'] ? 'warning' : 'success' }}">
                                                {{ $config['validation']['require_ocr'] ? 'Ya' : 'Tidak' }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Notification Tab -->
            @if($activeTab === 'notification')
            <div class="tab-pane fade show active">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-4">Notification Channels</h5>

                        @foreach($notificationConfig['channels'] ?? [] as $channel => $config)
                        <div class="card mb-3">
                            <div class="card-header">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="notif_{{ $channel }}"
                                        wire:model="notificationConfig.channels.{{ $channel }}.enabled" {{ $isEditing
                                        ? '' : 'disabled' }}>
                                    <label class="form-check-label fw-bold" for="notif_{{ $channel }}">
                                        {{ ucwords($channel) }}
                                    </label>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>Recipients:</strong> {{ implode(', ', $config['recipients'] ?? []) }}
                                </div>
                                <div>
                                    <strong>Templates:</strong> {{ count($config['templates'] ?? []) }} template
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="col-md-6">
                        <h5 class="mb-4">Notification Triggers</h5>

                        @foreach($notificationConfig['triggers'] ?? [] as $trigger => $config)
                        <div class="card mb-3">
                            <div class="card-header">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="trigger_{{ $trigger }}"
                                        wire:model="notificationConfig.triggers.{{ $trigger }}.enabled" {{ $isEditing
                                        ? '' : 'disabled' }}>
                                    <label class="form-check-label fw-bold" for="trigger_{{ $trigger }}">
                                        {{ ucwords(str_replace('_', ' ', $trigger)) }}
                                    </label>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>Channels:</strong> {{ implode(', ', $config['channels'] ?? []) }}
                                </div>
                                <div>
                                    <strong>Recipients:</strong> {{ implode(', ', $config['recipients'] ?? []) }}
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Business Rules Tab -->
            @if($activeTab === 'business_rules')
            <div class="tab-pane fade show active">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-4">Purchase Limits</h5>

                        @foreach($businessRulesConfig['purchase_limits'] ?? [] as $limit => $config)
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">{{ ucwords(str_replace('_', ' ', $limit)) }}</h6>
                            </div>
                            <div class="card-body">
                                @foreach($config as $setting => $value)
                                @if(is_bool($value))
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox"
                                        id="limit_{{ $limit }}_{{ $setting }}"
                                        wire:model="businessRulesConfig.purchase_limits.{{ $limit }}.{{ $setting }}" {{
                                        $isEditing ? '' : 'disabled' }}>
                                    <label class="form-check-label" for="limit_{{ $limit }}_{{ $setting }}">
                                        {{ ucwords(str_replace('_', ' ', $setting)) }}
                                    </label>
                                </div>
                                @else
                                <div class="mb-2">
                                    <label class="form-label">{{ ucwords(str_replace('_', ' ', $setting)) }}</label>
                                    <input type="number" class="form-control form-control-sm"
                                        wire:model="businessRulesConfig.purchase_limits.{{ $limit }}.{{ $setting }}" {{
                                        $isEditing ? '' : 'readonly' }}>
                                </div>
                                @endif
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="col-md-6">
                        <h5 class="mb-4">Quality Rules</h5>

                        @foreach($businessRulesConfig['quality_rules'] ?? [] as $rule => $config)
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">{{ ucwords(str_replace('_', ' ', $rule)) }}</h6>
                            </div>
                            <div class="card-body">
                                @foreach($config as $setting => $value)
                                @if(is_bool($value))
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox"
                                        id="quality_{{ $rule }}_{{ $setting }}"
                                        wire:model="businessRulesConfig.quality_rules.{{ $rule }}.{{ $setting }}" {{
                                        $isEditing ? '' : 'disabled' }}>
                                    <label class="form-check-label" for="quality_{{ $rule }}_{{ $setting }}">
                                        {{ ucwords(str_replace('_', ' ', $setting)) }}
                                    </label>
                                </div>
                                @else
                                <div class="mb-2">
                                    <label class="form-label">{{ ucwords(str_replace('_', ' ', $setting)) }}</label>
                                    <input type="number" class="form-control form-control-sm"
                                        wire:model="businessRulesConfig.quality_rules.{{ $rule }}.{{ $setting }}" {{
                                        $isEditing ? '' : 'readonly' }}>
                                </div>
                                @endif
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Reporting Tab -->
            @if($activeTab === 'reporting')
            <div class="tab-pane fade show active">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-4">Reports</h5>

                        @foreach($reportingConfig['reports'] ?? [] as $report => $config)
                        <div class="card mb-3">
                            <div class="card-header">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="report_{{ $report }}"
                                        wire:model="reportingConfig.reports.{{ $report }}.enabled" {{ $isEditing ? ''
                                        : 'disabled' }}>
                                    <label class="form-check-label fw-bold" for="report_{{ $report }}">
                                        {{ ucwords(str_replace('_', ' ', $report)) }}
                                    </label>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="mb-2">
                                    <strong>Frequency:</strong> {{ $config['frequency'] }}
                                </div>
                                <div class="mb-2">
                                    <strong>Recipients:</strong> {{ implode(', ', $config['recipients'] ?? []) }}
                                </div>
                                <div>
                                    <strong>Charts:</strong> {{ $config['include_charts'] ? 'Ya' : 'Tidak' }}
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <div class="col-md-6">
                        <h5 class="mb-4">Export Formats</h5>

                        @foreach($reportingConfig['export_formats'] ?? [] as $format => $enabled)
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="export_{{ $format }}"
                                wire:model="reportingConfig.export_formats.{{ $format }}" {{ $isEditing ? ''
                                : 'disabled' }}>
                            <label class="form-check-label" for="export_{{ $format }}">
                                {{ strtoupper($format) }}
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Integration Tab -->
            @if($activeTab === 'integration')
            <div class="tab-pane fade show active">
                <div class="row">
                    <div class="col-12">
                        <h5 class="mb-4">System Integrations</h5>

                        @foreach($integrationConfig as $integration => $config)
                        <div class="card mb-3">
                            <div class="card-header">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="integration_{{ $integration }}"
                                        wire:model="integrationConfig.{{ $integration }}.enabled" {{ $isEditing ? ''
                                        : 'disabled' }}>
                                    <label class="form-check-label fw-bold" for="integration_{{ $integration }}">
                                        {{ ucwords(str_replace('_', ' ', $integration)) }}
                                    </label>
                                </div>
                            </div>
                            <div class="card-body">
                                @foreach($config as $setting => $value)
                                @if($setting !== 'enabled')
                                @if(is_bool($value))
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox"
                                        id="integration_{{ $integration }}_{{ $setting }}"
                                        wire:model="integrationConfig.{{ $integration }}.{{ $setting }}" {{ $isEditing
                                        ? '' : 'disabled' }}>
                                    <label class="form-check-label" for="integration_{{ $integration }}_{{ $setting }}">
                                        {{ ucwords(str_replace('_', ' ', $setting)) }}
                                    </label>
                                </div>
                                @else
                                <div class="mb-2">
                                    <label class="form-label">{{ ucwords(str_replace('_', ' ', $setting)) }}</label>
                                    <input type="text" class="form-control form-control-sm"
                                        wire:model="integrationConfig.{{ $integration }}.{{ $setting }}" {{ $isEditing
                                        ? '' : 'readonly' }}>
                                </div>
                                @endif
                                @endif
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Footer -->
    <div class="card-footer">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                @if($isEditing && $hasChanges)
                <span class="text-warning">
                    <i class="fas fa-exclamation-triangle"></i> Ada perubahan yang belum disimpan
                </span>
                @endif
            </div>

            <div class="d-flex gap-2">
                <button type="button" class="btn btn-info" wire:click="testConfiguration">
                    <i class="fas fa-vial"></i> Test Konfigurasi
                </button>

                <button type="button" class="btn btn-secondary" wire:click="$set('showResetModal', true)">
                    <i class="fas fa-undo"></i> Reset ke Default
                </button>

                <button type="button" class="btn btn-success" wire:click="exportConfiguration">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reset Modal -->
@if($showResetModal)
<div class="modal fade show" style="display: block;" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Konfigurasi</h5>
                <button type="button" class="btn-close" wire:click="$set('showResetModal', false)"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin mereset konfigurasi ke pengaturan default?</p>
                <p class="text-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Semua pengaturan kustom akan hilang dan tidak dapat dikembalikan.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="$set('showResetModal', false)">
                    Batal
                </button>
                <button type="button" class="btn btn-danger" wire:click="resetToDefault">
                    Reset ke Default
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal-backdrop fade show"></div>
@endif

<!-- Success/Error Messages -->
@if(session()->has('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if(session()->has('error'))
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif