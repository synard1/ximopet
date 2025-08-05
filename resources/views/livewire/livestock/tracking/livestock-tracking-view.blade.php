<div class="px-2">
    <!-- Header Section -->
    <div class="card mb-5 shadow-sm rounded-3">
        <div
            class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center py-4 px-4">
            <h5 class="mb-0 fw-bold">
                <i class="bi bi-graph-up me-3 fs-4"></i>
                Livestock Tracking & Origin Chain
            </h5>
            <div>
                @if($livestockId && !empty($trackingData))
                <button type="button" class="btn btn-light btn-sm me-2 d-flex align-items-center px-3 py-2"
                    wire:click="$set('showExportModal', true)">
                    <i class="bi bi-download me-2 fs-5"></i> Export
                </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Livestock Selection -->
    <div class="card mb-5 shadow-sm rounded-3">
        <div class="card-body py-4 px-4">
            <div class="row g-4 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-semibold mb-2 text-dark">
                        <i class="bi bi-search me-2"></i>Search Livestock
                    </label>
                    <input type="text" wire:model.live="searchTerm" class="form-control form-control-lg rounded-3"
                        placeholder="Search by name, farm, or coop...">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold mb-2 text-dark">
                        <i class="bi bi-funnel me-2"></i>Status
                    </label>
                    <select wire:model.live="filterStatus" class="form-select form-select-lg rounded-3">
                        <option value="">All Status</option>
                        <option value="draft">Draft</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="in_transit">In Transit</option>
                        <option value="in_use">In Use</option>
                        <option value="arrived">Arrived</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="completed">Completed</option>
                        <option value="ready">Ready</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold mb-2 text-dark">
                        <i class="bi bi-tags me-2"></i>Source Type
                    </label>
                    <select wire:model.live="filterSourceType" class="form-select form-select-lg rounded-3">
                        <option value="">All Sources</option>
                        <option value="purchase">Purchase</option>
                        <option value="mutation">Mutation</option>
                        <option value="mixed">Mixed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold mb-2">&nbsp;</label>
                    <button type="button"
                        class="btn btn-primary w-100 d-flex align-items-center justify-content-center py-2 rounded-3"
                        wire:click="$set('searchTerm', '')">
                        <i class="bi bi-arrow-clockwise me-2 fs-5"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Livestock List -->
    <div class="card mb-5 shadow-sm rounded-3">
        <div class="card-header bg-light py-3 px-4">
            <h6 class="mb-0 fw-bold text-dark">
                <i class="bi bi-list-ul me-2"></i>Select Livestock for Tracking
            </h6>
        </div>
        <div class="card-body p-0">
            @if($livestockList->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="py-3 px-4">
                                <i class="bi bi-tag me-2"></i>Name
                            </th>
                            <th class="py-3 px-4">
                                <i class="bi bi-building me-2"></i>Farm
                            </th>
                            <th class="py-3 px-4">
                                <i class="bi bi-house me-2"></i>Coop
                            </th>
                            <th class="py-3 px-4">
                                <i class="bi bi-dna me-2"></i>Strain
                            </th>
                            <th class="py-3 px-4">
                                <i class="bi bi-circle-fill me-2"></i>Status
                            </th>
                            <th class="py-3 px-4">
                                <i class="bi bi-box me-2"></i>Available
                            </th>
                            <th class="py-3 px-4">
                                <i class="bi bi-gear me-2"></i>Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($livestockList as $livestock)
                        <tr class="{{ $livestockId == $livestock->id ? 'table-primary' : '' }} border-bottom">
                            <td class="py-3 px-4">
                                <div class="fw-bold fs-6">{{ $livestock->name }}</div>
                                <small class="text-muted">ID: {{ $livestock->id }}</small>
                            </td>
                            <td class="py-3 px-4">
                                <i class="bi bi-building me-2 text-primary"></i>
                                {{ $livestock->farm->name ?? 'N/A' }}
                            </td>
                            <td class="py-3 px-4">
                                <i class="bi bi-house me-2 text-success"></i>
                                {{ $livestock->coop->name ?? 'N/A' }}
                            </td>
                            <td class="py-3 px-4">
                                <i class="bi bi-dna me-2 text-info"></i>
                                {{ $livestock->livestockStrain->name ?? 'N/A' }}
                            </td>
                            <td class="py-3 px-4">
                                <span
                                    class="badge bg-{{ $livestock->status === 'active' ? 'success' : 'secondary' }} fs-6 px-3 py-2 rounded-pill">
                                    <i class="bi bi-circle-fill me-2"></i>
                                    {{ ucfirst($livestock->status) }}
                                </span>
                            </td>
                            <td class="py-3 px-4">
                                <div class="fw-bold fs-6 text-success">
                                    <i class="bi bi-box me-2"></i>
                                    {{ number_format($livestock->getTotalAvailableQuantity()) }}
                                </div>
                                <small class="text-muted">
                                    <i class="bi bi-arrow-up me-2"></i>
                                    {{ number_format($livestock->getTotalInitialQuantity()) }} initial
                                </small>
                            </td>
                            <td class="py-3 px-4">
                                <button type="button"
                                    class="btn btn-primary btn-sm d-flex align-items-center px-3 py-2 rounded-3"
                                    wire:click="selectLivestock('{{ $livestock->id }}')">
                                    <i class="bi bi-eye me-2 fs-5"></i> Track
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-light p-3">
                {{ $livestockList->links() }}
            </div>
            @else
            <div class="text-center py-5 text-muted">
                <i class="bi bi-search fs-1 mb-3 text-muted"></i>
                <p class="mb-0 fs-5">No livestock found matching your criteria</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Tracking Data Display -->
    @if($livestockId && !empty($trackingData) && !isset($trackingData['error']))
    <div class="card shadow-sm rounded-3">
        <div class="card-header bg-light py-3 px-4">
            <ul class="nav nav-tabs card-header-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $selectedTab === 'overview' ? 'active' : '' }} d-flex align-items-center"
                        wire:click="setTab('overview')" type="button" role="tab">
                        <i class="bi bi-info-circle me-2 fs-5"></i> Overview
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $selectedTab === 'origin' ? 'active' : '' }} d-flex align-items-center"
                        wire:click="setTab('origin')" type="button" role="tab">
                        <i class="bi bi-diagram-3 me-2 fs-5"></i> Origin Chain
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $selectedTab === 'batches' ? 'active' : '' }} d-flex align-items-center"
                        wire:click="setTab('batches')" type="button" role="tab">
                        <i class="bi bi-collection me-2 fs-5"></i> Batch History
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link {{ $selectedTab === 'mutations' ? 'active' : '' }} d-flex align-items-center"
                        wire:click="setTab('mutations')" type="button" role="tab">
                        <i class="bi bi-arrow-left-right me-2 fs-5"></i> Mutations
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button
                        class="nav-link {{ $selectedTab === 'performance' ? 'active' : '' }} d-flex align-items-center"
                        wire:click="setTab('performance')" type="button" role="tab">
                        <i class="bi bi-graph-up me-2 fs-5"></i> Performance
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $selectedTab === 'audit' ? 'active' : '' }} d-flex align-items-center"
                        wire:click="setTab('audit')" type="button" role="tab">
                        <i class="bi bi-clock-history me-2 fs-5"></i> Audit Trail
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            @if($isLoading)
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3 text-muted fs-5">Loading tracking data...</p>
            </div>
            @else
            <!-- Overview Tab -->
            @if($selectedTab === 'overview')
            <div class="row g-4">
                <!-- Basic Info -->
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm rounded-3">
                        <div class="card-header bg-gradient-primary text-white py-3 px-4">
                            <h6 class="mb-0 fw-bold">
                                <i class="bi bi-info-circle me-2"></i>Basic Information
                            </h6>
                        </div>
                        <div class="card-body p-4">
                            @if(isset($trackingData['livestock_info']))
                            <div class="row g-4">
                                <div class="col-6">
                                    <label class="form-label small text-muted fw-semibold">
                                        <i class="bi bi-tag me-2"></i>Name:
                                    </label>
                                    <div class="fw-bold fs-6 text-dark">{{ $trackingData['livestock_info']['name'] }}
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted fw-semibold">
                                        <i class="bi bi-building me-2"></i>Farm:
                                    </label>
                                    <div class="text-dark">{{ $trackingData['livestock_info']['farm'] }}</div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted fw-semibold">
                                        <i class="bi bi-house me-2"></i>Coop:
                                    </label>
                                    <div class="text-dark">{{ $trackingData['livestock_info']['coop'] }}</div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted fw-semibold">
                                        <i class="bi bi-dna me-2"></i>Strain:
                                    </label>
                                    <div class="text-dark">{{ $trackingData['livestock_info']['strain'] }}</div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted fw-semibold">
                                        <i class="bi bi-circle-fill me-2"></i>Status:
                                    </label>
                                    <div>
                                        <span
                                            class="badge bg-{{ $trackingData['livestock_info']['status'] === 'active' ? 'success' : 'secondary' }} fs-6 px-3 py-2 rounded-pill">
                                            <i class="bi bi-circle-fill me-2"></i>
                                            {{ $trackingData['livestock_info']['status_label'] }}
                                        </span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted fw-semibold">
                                        <i class="bi bi-calendar me-2"></i>Created:
                                    </label>
                                    <div class="text-dark">{{
                                        \Carbon\Carbon::parse($trackingData['livestock_info']['created_at'])->format('d/m/Y
                                        H:i') }}</div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Performance Metrics -->
                <div class="col-md-6">
                    <div class="card h-100 shadow-sm rounded-3">
                        <div class="card-header bg-gradient-success text-white py-3 px-4">
                            <h6 class="mb-0 fw-bold">
                                <i class="bi bi-graph-up me-2"></i>Performance Metrics
                            </h6>
                        </div>
                        <div class="card-body p-4">
                            @if(isset($trackingData['performance_metrics']))
                            <div class="row g-4">
                                <div class="col-6">
                                    <label class="form-label small text-muted fw-semibold">
                                        <i class="bi bi-check-circle me-2"></i>Survival Rate:
                                    </label>
                                    <div class="fw-bold text-success fs-4">{{
                                        $trackingData['performance_metrics']['survival_rate_percentage'] }}%</div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted fw-semibold">
                                        <i class="bi bi-x-circle me-2"></i>Mortality Rate:
                                    </label>
                                    <div class="fw-bold text-danger fs-4">{{
                                        $trackingData['performance_metrics']['mortality_rate_percentage'] }}%</div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted fw-semibold">
                                        <i class="bi bi-cart me-2"></i>Sales Rate:
                                    </label>
                                    <div class="fw-bold text-info fs-4">{{
                                        $trackingData['performance_metrics']['sales_rate_percentage'] }}%</div>
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted fw-semibold">
                                        <i class="bi bi-box me-2"></i>Availability:
                                    </label>
                                    <div class="fw-bold text-primary fs-4">{{
                                        $trackingData['performance_metrics']['availability_percentage'] }}%</div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Cost Analysis -->
                @if(isset($trackingData['cost_analysis']))
                <div class="col-12">
                    <div class="card shadow-sm rounded-3">
                        <div class="card-header bg-gradient-warning text-dark py-3 px-4">
                            <h6 class="mb-0 fw-bold">
                                <i class="bi bi-currency-dollar me-2"></i>Cost Analysis
                            </h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-4">
                                <div class="col-md-3">
                                    <label class="form-label small text-muted fw-semibold">
                                        <i class="bi bi-cash me-2"></i>Initial Cost:
                                    </label>
                                    <div class="fw-bold fs-5 text-dark">Rp {{
                                        number_format($trackingData['cost_analysis']['initial_cost'], 0, ',', '.') }}
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted fw-semibold">
                                        <i class="bi bi-basket me-2"></i>Feed Cost:
                                    </label>
                                    <div class="fw-bold fs-5 text-dark">Rp {{
                                        number_format($trackingData['cost_analysis']['feed_cost'], 0, ',', '.') }}</div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted fw-semibold">
                                        <i class="bi bi-calculator me-2"></i>Total Cost:
                                    </label>
                                    <div class="fw-bold text-primary fs-5">Rp {{
                                        number_format($trackingData['cost_analysis']['total_cost'], 0, ',', '.') }}
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small text-muted fw-semibold">
                                        <i class="bi bi-percent me-2"></i>Cost per Unit:
                                    </label>
                                    <div class="fw-bold fs-5 text-dark">Rp {{
                                        number_format($trackingData['cost_analysis']['cost_per_unit'], 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @endif

            <!-- Origin Chain Tab -->
            @if($selectedTab === 'origin')
            <div class="card shadow-sm rounded-3">
                <div class="card-header bg-gradient-info text-white py-3 px-4">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-diagram-3 me-2"></i>Origin Chain
                    </h6>
                </div>
                <div class="card-body p-4">
                    @if(isset($trackingData['origin_chain']) && count($trackingData['origin_chain']) > 0)
                    <div class="timeline">
                        @foreach($trackingData['origin_chain'] as $index => $chain)
                        <div class="timeline-item">
                            <div
                                class="timeline-marker bg-{{ $chain['type'] === 'purchase' ? 'primary' : 'success' }} shadow">
                                <i
                                    class="bi bi-{{ $chain['type'] === 'purchase' ? 'cart-plus' : 'arrow-left-right' }} fs-5"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="card shadow-sm rounded-3 mb-4">
                                    <div class="card-header bg-light py-3 px-4">
                                        <h6 class="mb-0 fw-bold text-dark">
                                            <i
                                                class="bi bi-{{ $chain['type'] === 'purchase' ? 'cart-plus' : 'arrow-left-right' }} me-2"></i>
                                            {{ ucfirst($chain['type']) }} - {{
                                            \Carbon\Carbon::parse($chain['date'])->format('d/m/Y') }}
                                        </h6>
                                    </div>
                                    <div class="card-body p-4">
                                        <div class="row g-4">
                                            @if($chain['type'] === 'purchase')
                                            <div class="col-md-3">
                                                <label class="form-label small text-muted fw-semibold">
                                                    <i class="bi bi-receipt me-2"></i>Invoice:
                                                </label>
                                                <div class="fw-bold fs-6 text-dark">{{ $chain['invoice_number'] ?? 'N/A'
                                                    }}</div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small text-muted fw-semibold">
                                                    <i class="bi bi-building me-2"></i>Supplier:
                                                </label>
                                                <div class="text-dark">{{ $chain['supplier'] }}</div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small text-muted fw-semibold">
                                                    <i class="bi bi-box me-2"></i>Quantity:
                                                </label>
                                                <div class="fw-bold fs-6 text-success">{{
                                                    number_format($chain['quantity']) }}</div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small text-muted fw-semibold">
                                                    <i class="bi bi-currency-dollar me-2"></i>Price/Unit:
                                                </label>
                                                <div class="fw-bold text-dark">Rp {{
                                                    number_format($chain['price_per_unit'], 0, ',', '.') }}</div>
                                            </div>
                                            @else
                                            <div class="col-md-3">
                                                <label class="form-label small text-muted fw-semibold">
                                                    <i class="bi bi-tags me-2"></i>Type:
                                                </label>
                                                <div class="fw-bold fs-6 text-dark">{{ ucfirst($chain['mutation_type'])
                                                    }}</div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small text-muted fw-semibold">
                                                    <i class="bi bi-arrow-left-right me-2"></i>Direction:
                                                </label>
                                                <div class="text-dark">{{ ucfirst($chain['direction']) }}</div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small text-muted fw-semibold">
                                                    <i class="bi bi-box me-2"></i>Quantity:
                                                </label>
                                                <div class="fw-bold fs-6 text-info">{{ number_format($chain['quantity'])
                                                    }}</div>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small text-muted fw-semibold">
                                                    <i class="bi bi-arrow-up me-2"></i>Source:
                                                </label>
                                                <div class="text-dark">{{ $chain['source_livestock'] }}</div>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-info-circle fs-1 mb-3 text-muted"></i>
                        <p class="mb-0 fs-5">No origin chain data available</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Batch History Tab -->
            @if($selectedTab === 'batches')
            <div class="card shadow-sm rounded-3">
                <div class="card-header bg-gradient-warning text-dark py-3 px-4">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-collection me-2"></i>Batch History
                    </h6>
                </div>
                <div class="card-body p-4">
                    @if(isset($trackingData['batch_history']) && count($trackingData['batch_history']) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th class="py-3 px-4">
                                        <i class="bi bi-tag me-2"></i>Batch Name
                                    </th>
                                    <th class="py-3 px-4">
                                        <i class="bi bi-tags me-2"></i>Source Type
                                    </th>
                                    <th class="py-3 px-4">
                                        <i class="bi bi-arrow-up me-2"></i>Initial Qty
                                    </th>
                                    <th class="py-3 px-4">
                                        <i class="bi bi-box me-2"></i>Available Qty
                                    </th>
                                    <th class="py-3 px-4">
                                        <i class="bi bi-arrow-down me-2"></i>Depletion Qty
                                    </th>
                                    <th class="py-3 px-4">
                                        <i class="bi bi-cart me-2"></i>Sales Qty
                                    </th>
                                    <th class="py-3 px-4">
                                        <i class="bi bi-arrow-left-right me-2"></i>Mutated Qty
                                    </th>
                                    <th class="py-3 px-4">
                                        <i class="bi bi-currency-dollar me-2"></i>Price/Unit
                                    </th>
                                    <th class="py-3 px-4">
                                        <i class="bi bi-circle-fill me-2"></i>Status
                                    </th>
                                    <th class="py-3 px-4">
                                        <i class="bi bi-calendar me-2"></i>Created
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($trackingData['batch_history'] as $batch)
                                <tr class="border-bottom">
                                    <td class="py-3 px-4">
                                        <div class="fw-bold fs-6">{{ $batch['name'] }}</div>
                                        <small class="text-muted">{{ $batch['number'] }}</small>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span
                                            class="badge bg-{{ $batch['source_type'] === 'purchase' ? 'primary' : 'success' }} fs-6 px-3 py-2 rounded-pill">
                                            <i
                                                class="bi bi-{{ $batch['source_type'] === 'purchase' ? 'cart-plus' : 'arrow-left-right' }} me-2"></i>
                                            {{ ucfirst($batch['source_type']) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 fw-bold fs-6 text-dark">{{
                                        number_format($batch['initial_quantity']) }}</td>
                                    <td class="py-3 px-4 text-success fw-bold fs-6">
                                        <i class="bi bi-box me-2"></i>
                                        {{ number_format($batch['quantity_available']) }}
                                    </td>
                                    <td class="py-3 px-4 text-danger fw-bold">
                                        <i class="bi bi-arrow-down me-2"></i>
                                        {{ number_format($batch['quantity_depletion']) }}
                                    </td>
                                    <td class="py-3 px-4 text-info fw-bold">
                                        <i class="bi bi-cart me-2"></i>
                                        {{ number_format($batch['quantity_sales']) }}
                                    </td>
                                    <td class="py-3 px-4 text-warning fw-bold">
                                        <i class="bi bi-arrow-left-right me-2"></i>
                                        {{ number_format($batch['quantity_mutated']) }}
                                    </td>
                                    <td class="py-3 px-4 fw-bold text-dark">Rp {{
                                        number_format($batch['price_per_unit'], 0, ',', '.') }}</td>
                                    <td class="py-3 px-4">
                                        <span
                                            class="badge bg-{{ $batch['status'] === 'active' ? 'success' : 'secondary' }} fs-6 px-3 py-2 rounded-pill">
                                            <i class="bi bi-circle-fill me-2"></i>
                                            {{ ucfirst($batch['status']) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-dark">{{
                                        \Carbon\Carbon::parse($batch['created_at'])->format('d/m/Y') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-collection fs-1 mb-3 text-muted"></i>
                        <p class="mb-0 fs-5">No batch history available</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Mutations Tab -->
            @if($selectedTab === 'mutations')
            <div class="card shadow-sm rounded-3">
                <div class="card-header bg-gradient-info text-white py-3 px-4">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-arrow-left-right me-2"></i>Mutation History
                    </h6>
                </div>
                <div class="card-body p-4">
                    @if(isset($trackingData['mutation_history']) && count($trackingData['mutation_history']) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th class="py-3 px-4">
                                        <i class="bi bi-calendar me-2"></i>Date
                                    </th>
                                    <th class="py-3 px-4">
                                        <i class="bi bi-tags me-2"></i>Type
                                    </th>
                                    <th class="py-3 px-4">
                                        <i class="bi bi-arrow-left-right me-2"></i>Direction
                                    </th>
                                    <th class="py-3 px-4">
                                        <i class="bi bi-box me-2"></i>Quantity
                                    </th>
                                    <th class="py-3 px-4">
                                        <i class="bi bi-arrow-up me-2"></i>Source Livestock
                                    </th>
                                    <th class="py-3 px-4">
                                        <i class="bi bi-arrow-down me-2"></i>Destination Livestock
                                    </th>
                                    <th class="py-3 px-4">
                                        <i class="bi bi-circle-fill me-2"></i>Status
                                    </th>
                                    <th class="py-3 px-4">
                                        <i class="bi bi-chat me-2"></i>Notes
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($trackingData['mutation_history'] as $mutation)
                                <tr class="border-bottom">
                                    <td class="py-3 px-4 text-dark">{{
                                        \Carbon\Carbon::parse($mutation['date'])->format('d/m/Y') }}</td>
                                    <td class="py-3 px-4">
                                        <span class="badge bg-info fs-6 px-3 py-2 rounded-pill">
                                            <i class="bi bi-arrow-left-right me-2"></i>
                                            {{ ucfirst($mutation['type']) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4">
                                        <span
                                            class="badge bg-{{ $mutation['direction'] === 'in' ? 'success' : 'warning' }} fs-6 px-3 py-2 rounded-pill">
                                            <i
                                                class="bi bi-arrow-{{ $mutation['direction'] === 'in' ? 'down' : 'up' }} me-2"></i>
                                            {{ ucfirst($mutation['direction']) }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 fw-bold fs-6 text-dark">
                                        <i class="bi bi-box me-2 text-success"></i>
                                        {{ number_format($mutation['quantity']) }}
                                    </td>
                                    <td class="py-3 px-4 text-dark">
                                        <i class="bi bi-arrow-up me-2 text-success"></i>
                                        {{ $mutation['source_livestock'] }}
                                    </td>
                                    <td class="py-3 px-4 text-dark">
                                        <i class="bi bi-arrow-down me-2 text-warning"></i>
                                        {{ $mutation['destination_livestock'] }}
                                    </td>
                                    <td class="py-3 px-4">
                                        <span
                                            class="badge bg-{{ ($mutation['status'] ?? 'completed') === 'completed' ? 'success' : 'secondary' }} fs-6 px-3 py-2 rounded-pill">
                                            <i class="bi bi-circle-fill me-2"></i>
                                            {{ ucfirst($mutation['status'] ?? 'completed') }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-4 text-dark">{{ $mutation['notes'] ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-arrow-left-right fs-1 mb-3 text-muted"></i>
                        <p class="mb-0 fs-5">No mutation history available</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Performance Tab -->
            @if($selectedTab === 'performance')
            <div class="row g-4">
                @if(isset($trackingData['performance_metrics']))
                <div class="col-md-6">
                    <div class="card shadow-sm rounded-3">
                        <div class="card-header bg-gradient-primary text-white py-3 px-4">
                            <h6 class="mb-0 fw-bold">
                                <i class="bi bi-box me-2"></i>Quantity Breakdown
                            </h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-4">
                                <div class="col-6">
                                    <div class="text-center p-4 bg-light rounded-3 shadow-sm">
                                        <div class="h3 text-primary mb-2">
                                            <i class="bi bi-arrow-up me-2"></i>
                                            {{
                                            number_format($trackingData['performance_metrics']['total_initial_quantity'])
                                            }}
                                        </div>
                                        <div class="text-muted fw-semibold">Initial Quantity</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-4 bg-light rounded-3 shadow-sm">
                                        <div class="h3 text-success mb-2">
                                            <i class="bi bi-box me-2"></i>
                                            {{
                                            number_format($trackingData['performance_metrics']['total_available_quantity'])
                                            }}
                                        </div>
                                        <div class="text-muted fw-semibold">Available Quantity</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-4 bg-light rounded-3 shadow-sm">
                                        <div class="h3 text-danger mb-2">
                                            <i class="bi bi-arrow-down me-2"></i>
                                            {{
                                            number_format($trackingData['performance_metrics']['total_depletion_quantity'])
                                            }}
                                        </div>
                                        <div class="text-muted fw-semibold">Depletion Quantity</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-4 bg-light rounded-3 shadow-sm">
                                        <div class="h3 text-info mb-2">
                                            <i class="bi bi-cart me-2"></i>
                                            {{
                                            number_format($trackingData['performance_metrics']['total_sales_quantity'])
                                            }}
                                        </div>
                                        <div class="text-muted fw-semibold">Sales Quantity</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow-sm rounded-3">
                        <div class="card-header bg-gradient-success text-white py-3 px-4">
                            <h6 class="mb-0 fw-bold">
                                <i class="bi bi-graph-up me-2"></i>Performance Rates
                            </h6>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-4">
                                <div class="col-6">
                                    <div class="text-center p-4 bg-light rounded-3 shadow-sm">
                                        <div class="h3 text-success mb-2">
                                            <i class="bi bi-check-circle me-2"></i>
                                            {{ $trackingData['performance_metrics']['survival_rate_percentage'] }}%
                                        </div>
                                        <div class="text-muted fw-semibold">Survival Rate</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-4 bg-light rounded-3 shadow-sm">
                                        <div class="h3 text-danger mb-2">
                                            <i class="bi bi-x-circle me-2"></i>
                                            {{ $trackingData['performance_metrics']['mortality_rate_percentage'] }}%
                                        </div>
                                        <div class="text-muted fw-semibold">Mortality Rate</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-4 bg-light rounded-3 shadow-sm">
                                        <div class="h3 text-info mb-2">
                                            <i class="bi bi-cart me-2"></i>
                                            {{ $trackingData['performance_metrics']['sales_rate_percentage'] }}%
                                        </div>
                                        <div class="text-muted fw-semibold">Sales Rate</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="text-center p-4 bg-light rounded-3 shadow-sm">
                                        <div class="h3 text-primary mb-2">
                                            <i class="bi bi-box me-2"></i>
                                            {{ $trackingData['performance_metrics']['availability_percentage'] }}%
                                        </div>
                                        <div class="text-muted fw-semibold">Availability</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @endif

            <!-- Audit Trail Tab -->
            @if($selectedTab === 'audit')
            <div class="card shadow-sm rounded-3">
                <div class="card-header bg-gradient-secondary text-white py-3 px-4">
                    <h6 class="mb-0 fw-bold">
                        <i class="bi bi-clock-history me-2"></i>Audit Trail
                    </h6>
                </div>
                <div class="card-body p-4">
                    @if(isset($trackingData['audit_trail']) && count($trackingData['audit_trail']) > 0)
                    <div class="timeline">
                        @foreach($trackingData['audit_trail'] as $audit)
                        <div class="timeline-item">
                            <div
                                class="timeline-marker bg-{{ $audit['action'] === 'livestock_created' ? 'primary' : ($audit['action'] === 'batch_created' ? 'success' : 'info') }} shadow">
                                <i
                                    class="bi bi-{{ $audit['action'] === 'livestock_created' ? 'plus-circle' : ($audit['action'] === 'batch_created' ? 'collection' : 'arrow-left-right') }} fs-5"></i>
                            </div>
                            <div class="timeline-content">
                                <div class="card shadow-sm rounded-3 mb-4">
                                    <div class="card-body p-4">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <h6 class="mb-2 fw-bold text-dark">
                                                    <i
                                                        class="bi bi-{{ $audit['action'] === 'livestock_created' ? 'plus-circle' : ($audit['action'] === 'batch_created' ? 'collection' : 'arrow-left-right') }} me-2"></i>
                                                    {{ $audit['description'] }}
                                                </h6>
                                                <p class="mb-3 text-muted">
                                                    <i class="bi bi-calendar me-2"></i>
                                                    {{ \Carbon\Carbon::parse($audit['date'])->format('d/m/Y H:i:s') }}
                                                </p>
                                                @if(isset($audit['data']))
                                                <div class="d-flex flex-wrap gap-2">
                                                    @foreach($audit['data'] as $key => $value)
                                                    <span class="badge bg-light text-dark px-3 py-2 rounded-pill">
                                                        <i class="bi bi-tag me-2"></i>
                                                        <span class="fw-semibold">{{ ucwords(str_replace('_', ' ',
                                                            $key)) }}:</span> {{ $value }}
                                                    </span>
                                                    @endforeach
                                                </div>
                                                @endif
                                            </div>
                                            <div class="text-end ms-3">
                                                <span class="badge bg-secondary px-3 py-2 rounded-pill">
                                                    <i class="bi bi-person me-2"></i>
                                                    {{ $audit['user'] ?? 'System' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-clock-history fs-1 mb-3 text-muted"></i>
                        <p class="mb-0 fs-5">No audit trail available</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif
            @endif
        </div>
    </div>
    @endif

    @if($error)
    <div class="alert alert-danger mt-4 shadow-sm rounded-3 p-4">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-3 fs-3 text-danger"></i>
            <div class="fw-semibold fs-6">{{ $error }}</div>
        </div>
    </div>
    @endif

    <!-- Export Modal -->
    @if($showExportModal)
    <div class="modal fade show" style="display: block;" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow rounded-3">
                <div class="modal-header bg-gradient-primary text-white py-3 px-4">
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-download me-2"></i>Export Tracking Data
                    </h5>
                    <button type="button" class="btn-close btn-close-white"
                        wire:click="$set('showExportModal', false)"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">
                            <i class="bi bi-file-earmark me-2"></i>Export Format
                        </label>
                        <select wire:model="exportFormat" class="form-select form-select-lg rounded-3">
                            <option value="json">JSON</option>
                            <option value="csv">CSV</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer py-3 px-4">
                    <button type="button" class="btn btn-light btn-lg px-4 rounded-3"
                        wire:click="$set('showExportModal', false)">
                        <i class="bi bi-x-circle me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-primary btn-lg px-4 rounded-3" wire:click="exportData">
                        <i class="bi bi-download me-2"></i>Export
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <style>
        .timeline {
            position: relative;
            padding-left: 40px;
            margin-left: 20px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 40px;
        }

        .timeline-marker {
            position: absolute;
            left: -45px;
            top: 0;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .timeline-item:not(:last-child)::after {
            content: '';
            position: absolute;
            left: -25px;
            top: 40px;
            width: 3px;
            height: calc(100% + 30px);
            background-color: #e9ecef;
        }

        .nav-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: #6c757d;
            font-weight: 600;
            padding: 12px 20px;
            transition: all 0.3s ease;
            font-size: 15px;
        }

        .nav-tabs .nav-link:hover {
            border-color: #dee2e6;
            color: #495057;
            background-color: rgba(0, 0, 0, 0.03);
        }

        .nav-tabs .nav-link.active {
            border-color: #0d6efd;
            color: #0d6efd;
            background-color: transparent;
        }

        .table th {
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.5px;
        }

        .badge {
            font-weight: 500;
        }

        .card {
            border: none;
            border-radius: 10px;
            overflow: hidden;
        }

        .card-header {
            border-radius: 10px 10px 0 0 !important;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 0.75rem 1rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 0.5rem 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
            border: none;
        }

        .btn-success {
            background: linear-gradient(135deg, #198754, #157347);
            border: none;
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #0d6efd, #0a58ca);
        }

        .bg-gradient-success {
            background: linear-gradient(135deg, #198754, #157347);
        }

        .bg-gradient-info {
            background: linear-gradient(135deg, #0dcaf0, #0aa2c0);
        }

        .bg-gradient-warning {
            background: linear-gradient(135deg, #ffc107, #d39e00);
        }

        .bg-gradient-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
        }

        .text-dark {
            color: #343a40 !important;
        }

        .rounded-pill {
            border-radius: 50rem !important;
        }

        .rounded-3 {
            border-radius: 0.5rem !important;
        }

        .shadow-sm {
            box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075) !important;
        }

        .table {
            --bs-table-hover-bg: rgba(0, 0, 0, 0.02);
        }

        .table-dark {
            --bs-table-bg: #343a40;
        }
    </style>
</div>