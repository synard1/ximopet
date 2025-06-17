<div class="d-flex flex-column flex-column-fluid">
    <!--begin::Toolbar-->
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                    Smart Analytics
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="#" class="text-muted text-hover-primary">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-400 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Smart Analytics</li>
                </ul>
            </div>
            <div class="d-flex align-items-center gap-2 gap-lg-3">
                <button wire:click="calculateDailyAnalytics" class="btn btn-sm btn-primary"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove>
                        <i class="ki-duotone ki-arrows-circle fs-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        Refresh Analytics
                    </span>
                    <span wire:loading>
                        <span class="spinner-border spinner-border-sm" role="status"></span>
                        Calculating...
                    </span>
                </button>

                <!-- Debug Tools -->
                <button type="button" class="btn btn-sm btn-warning" onclick="debugAnalytics()">
                    <i class="ki-duotone ki-gear fs-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Debug
                </button>

                <button type="button" class="btn btn-sm btn-danger" onclick="forceHideLoading()">
                    <i class="ki-duotone ki-cross fs-4">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Force Hide
                </button>
            </div>
        </div>
    </div>
    <!--end::Toolbar-->

    <!--begin::Content-->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <!--begin::Filters-->
            <div class="card mb-5 mb-xl-8">
                <div class="card-header border-0 pt-5">
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold fs-3 mb-1">Filters</span>
                    </h3>
                </div>
                <div class="card-body py-3">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Farm</label>
                            <select wire:model.live="farmId" class="form-select form-select-sm">
                                <option value="">All Farms</option>
                                @foreach ($farms as $farm)
                                <option value="{{ $farm->id }}">{{ $farm->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Coop</label>
                            <select wire:model.live="coopId" class="form-select form-select-sm">
                                <option value="">All Coops</option>
                                @foreach ($coops as $coop)
                                <option value="{{ $coop->id }}">{{ $coop->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Livestock Batch</label>
                            <select wire:model.live="livestockId" class="form-select form-select-sm">
                                <option value="">All Livestock</option>
                                @foreach ($livestocks as $livestock)
                                <option value="{{ $livestock->id }}">{{ $livestock->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input wire:model.live="dateFrom" type="date" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input wire:model.live="dateTo" type="date" class="form-control form-control-sm">
                        </div>
                    </div>
                </div>
            </div>
            <!--end::Filters-->

            <!--begin::Overview Cards-->
            @if ($activeTab === 'overview')
            <div class="row g-5 g-xl-8 mb-5">
                @php $overview = $this->getOverviewData(); @endphp

                <div class="col-xl-2">
                    <div class="card bg-primary">
                        <div class="card-body">
                            <div class="text-white fw-bold fs-2">{{ number_format($overview['total_livestock']) }}
                            </div>
                            <div class="fw-semibold text-white opacity-75">Total Population</div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-2">
                    <div class="card bg-danger">
                        <div class="card-body">
                            @php
                            $mortalityRate = $overview['avg_mortality_rate'];
                            $displayRate =
                            $mortalityRate < 0.01 ? number_format($mortalityRate, 4) : number_format($mortalityRate, 2);
                                @endphp <div class="text-white fw-bold fs-2">{{ $displayRate }}%</div>
                        <div class="fw-semibold text-white opacity-75">Avg Mortality Rate</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2">
                <div class="card bg-success">
                    <div class="card-body">
                        <div class="text-white fw-bold fs-2">
                            {{ number_format($overview['avg_efficiency_score'], 1) }}</div>
                        <div class="fw-semibold text-white opacity-75">Efficiency Score</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2">
                <div class="card bg-info">
                    <div class="card-body">
                        <div class="text-white fw-bold fs-2">{{ number_format($overview['avg_fcr'], 2) }}</div>
                        <div class="fw-semibold text-white opacity-75">Average FCR</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2">
                <div class="card bg-warning">
                    <div class="card-body">
                        <div class="text-white fw-bold fs-2">{{ number_format($overview['total_revenue'], 0) }}
                        </div>
                        <div class="fw-semibold text-white opacity-75">Total Revenue</div>
                    </div>
                </div>
            </div>

            <div class="col-xl-2">
                <div class="card bg-dark">
                    <div class="card-body">
                        <div class="text-white fw-bold fs-2">
                            {{ $overview['problematic_coops'] }}/{{ $overview['high_performers'] }}</div>
                        <div class="fw-semibold text-white opacity-75">Problem/Top Coops</div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        <!--end::Overview Cards-->

        <!--begin::Performance Insights-->
        @if ($activeTab === 'overview')
        <div class="card mb-5 mb-xl-8">
            <div class="card-header border-0 pt-5">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold fs-3 mb-1">Performance Insights</span>
                </h3>
            </div>
            <div class="card-body py-3">
                <div class="row">
                    @if ($overview['problematic_coops'] > 0)
                    <div class="col-md-6">
                        <div class="alert alert-warning d-flex align-items-center p-5">
                            <i class="ki-duotone ki-shield-tick fs-2hx text-warning me-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <div class="d-flex flex-column">
                                <h4 class="mb-1 text-warning">{{ $overview['problematic_coops'] }} Coops
                                    Need
                                    Attention</h4>
                                <span>These coops have efficiency scores below 60% and require immediate
                                    review.</span>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if ($overview['avg_mortality_rate'] > 5)
                    <div class="col-md-6">
                        <div class="alert alert-danger d-flex align-items-center p-5">
                            <i class="ki-duotone ki-information fs-2hx text-danger me-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                                <span class="path3"></span>
                            </i>
                            <div class="d-flex flex-column">
                                <h4 class="mb-1 text-danger">High Mortality Alert</h4>
                                <span>Average mortality rate is
                                    {{ number_format($overview['avg_mortality_rate'], 2) }}%, above
                                    recommended 5%.</span>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if ($overview['avg_fcr'] > 2.0)
                    <div class="col-md-6">
                        <div class="alert alert-info d-flex align-items-center p-5">
                            <i class="ki-duotone ki-chart-line fs-2hx text-info me-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <div class="d-flex flex-column">
                                <h4 class="mb-1 text-info">FCR Optimization Needed</h4>
                                <span>Average FCR is {{ number_format($overview['avg_fcr'], 2) }}, target
                                    should be
                                    below 2.0.</span>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if ($overview['high_performers'] > 0)
                    <div class="col-md-6">
                        <div class="alert alert-success d-flex align-items-center p-5">
                            <i class="ki-duotone ki-crown fs-2hx text-success me-4">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <div class="d-flex flex-column">
                                <h4 class="mb-1 text-success">{{ $overview['high_performers'] }} Top
                                    Performers!
                                </h4>
                                <span>These coops have excellent efficiency scores above 80%.</span>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif
        <!--end::Performance Insights-->

        <!--begin::Main Content-->
        <div class="card">
            <!--begin::Card header-->
            <div class="card-header border-0 pt-6">
                <div class="card-title">
                    <div class="d-flex align-items-center position-relative my-1">
                        <span class="fs-4 fw-bold text-gray-800">Analytics Dashboard</span>
                    </div>
                </div>
                <div class="card-toolbar">
                    <div class="d-flex justify-content-end" data-kt-user-table-toolbar="base">
                        <!-- Tab Navigation -->
                        <ul class="nav nav-tabs nav-line-tabs nav-stretch fs-6 border-0">
                            <li class="nav-item">
                                <a class="nav-link {{ $activeTab === 'overview' ? 'active' : '' }}" href="#"
                                    wire:click.prevent="setActiveTab('overview')">
                                    <span class="nav-icon">
                                        <i class="ki-duotone ki-element-11 fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                        </i>
                                    </span>
                                    <span class="nav-text">Overview</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $activeTab === 'mortality' ? 'active' : '' }}" href="#"
                                    wire:click.prevent="setActiveTab('mortality')">
                                    <span class="nav-icon">
                                        <i class="ki-duotone ki-cross-square fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="nav-text">Mortality</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $activeTab === 'sales' ? 'active' : '' }}" href="#"
                                    wire:click.prevent="setActiveTab('sales')">
                                    <span class="nav-icon">
                                        <i class="ki-duotone ki-dollar fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                        </i>
                                    </span>
                                    <span class="nav-text">Sales</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $activeTab === 'production' ? 'active' : '' }}" href="#"
                                    wire:click.prevent="setActiveTab('production')">
                                    <span class="nav-icon">
                                        <i class="ki-duotone ki-chart-line-up fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="nav-text">Production</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $activeTab === 'rankings' ? 'active' : '' }}" href="#"
                                    wire:click.prevent="setActiveTab('rankings')">
                                    <span class="nav-icon">
                                        <i class="ki-duotone ki-crown fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                    </span>
                                    <span class="nav-text">Rankings</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $activeTab === 'alerts' ? 'active' : '' }}" href="#"
                                    wire:click.prevent="setActiveTab('alerts')">
                                    <span class="nav-icon">
                                        <i class="ki-duotone ki-notification-status fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                        </i>
                                    </span>
                                    <span class="nav-text">Alerts</span>
                                    @if ($this->getActiveAlerts()->count() > 0)
                                    <span class="badge badge-circle badge-danger ms-2">{{
                                        $this->getActiveAlerts()->count() }}</span>
                                    @endif
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <!--end::Card header-->

            <!--begin::Card body-->
            <div class="card-body py-4">

                <!-- Loading Overlay - Manual Control Only (No wire:loading) -->
                <div class="d-none align-items-center justify-content-center position-absolute w-100 h-100 bg-white bg-opacity-75"
                    style="z-index: 999;" id="loadingOverlay">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="mt-3 fw-semibold text-gray-600">Loading analytics data...</div>
                        <div class="mt-2">
                            <small class="text-muted">This may take a few moments for large datasets</small>
                        </div>
                    </div>
                </div>

                <!-- Error State -->
                <div class="alert alert-warning d-none" id="errorState">
                    <div class="d-flex align-items-center">
                        <i class="ki-duotone ki-information fs-2hx text-warning me-4">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        <div>
                            <h4 class="mb-1 text-warning">Data Loading Issue</h4>
                            <span>Unable to load analytics data. Please check your connection and try
                                refreshing.</span>
                            <div class="mt-2">
                                <button onclick="window.location.reload()" class="btn btn-sm btn-warning">
                                    Refresh Page
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overview Tab -->
                @if ($activeTab === 'overview')
                <div class="row g-5">
                    <!-- Trend Charts -->
                    <div class="col-xl-6">
                        <div class="card card-flush h-xl-100">
                            <div class="card-header pt-5">
                                <h3 class="card-title text-gray-800">Mortality Trend</h3>
                            </div>
                            <div class="card-body pt-6" style="height: 300px;">
                                <canvas id="mortalityChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6">
                        <div class="card card-flush h-xl-100">
                            <div class="card-header pt-5">
                                <h3 class="card-title text-gray-800">Efficiency Trend</h3>
                            </div>
                            <div class="card-body pt-6" style="height: 300px;">
                                <canvas id="efficiencyChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6">
                        <div class="card card-flush h-xl-100">
                            <div class="card-header pt-5">
                                <h3 class="card-title text-gray-800">FCR Trend</h3>
                            </div>
                            <div class="card-body pt-6" style="height: 300px;">
                                <canvas id="fcrChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-6">
                        <div class="card card-flush h-xl-100">
                            <div class="card-header pt-5">
                                <h3 class="card-title text-gray-800">Revenue Trend</h3>
                            </div>
                            <div class="card-body pt-6" style="height: 300px;">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Mortality Analysis Tab -->
                @if ($activeTab === 'mortality')
                <!-- Mortality Chart Section -->
                <div class="row g-5 mb-5">
                    <div class="col-12">
                        <div class="card card-flush h-xl-100">
                            <div class="card-header pt-5">
                                <h3 class="card-title text-gray-800">
                                    <span id="mortalityChartTitle">Mortality Analysis Chart</span>
                                    <small class="text-muted ms-2" id="mortalityChartSubtitle">
                                        @if ($livestockId)
                                        Single Livestock Analysis
                                        @elseif($farmId && $coopId)
                                        Single Coop Analysis
                                        @elseif($farmId)
                                        Single Farm Analysis (by Coop)
                                        @else
                                        All Farms Comparison
                                        @endif
                                    </small>
                                </h3>
                                <div class="card-toolbar">
                                    <div class="d-flex align-items-center flex-wrap">
                                        <!-- Chart Type Selector -->
                                        <div class="me-3">
                                            <span class="text-muted me-2">Chart Type:</span>
                                            <select class="form-select form-select-sm w-auto"
                                                wire:model.live="chartType">
                                                <option value="auto">Auto</option>
                                                <option value="line">Line Chart</option>
                                                <option value="bar">Bar Chart</option>
                                            </select>
                                        </div>

                                        <!-- View Type Selector (show for single coop or single livestock) -->
                                        @if ($coopId || $livestockId)
                                        <div class="me-3">
                                            <span class="text-muted me-2">View:</span>
                                            <select class="form-select form-select-sm w-auto"
                                                wire:model.live="viewType">
                                                @if ($livestockId)
                                                <option value="livestock">Livestock Trend</option>
                                                <option value="daily">Daily Mortality</option>
                                                @else
                                                <option value="livestock">Per Livestock</option>
                                                <option value="daily">Daily Aggregate</option>
                                                @endif
                                            </select>
                                        </div>
                                        @endif

                                        <!-- Debug Buttons -->
                                        <div class="d-flex">
                                            <button class="btn btn-sm btn-light me-2" onclick="debugMortalityChart()"
                                                title="Debug Chart">
                                                🔍 Debug
                                            </button>
                                            <button class="btn btn-sm btn-primary me-2"
                                                onclick="forceInitializeMortalityChart()" title="Retry Chart">
                                                🔄 Retry
                                            </button>
                                            <button class="btn btn-sm btn-success" onclick="refreshMortalityChartData()"
                                                title="Refresh Data">
                                                📊 Refresh Data
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body pt-6" style="min-height: 400px;">
                                <div id="mortalityChartContainer" style="position: relative; height: 350px;">
                                    <canvas id="advancedMortalityChart"></canvas>
                                </div>
                                <div class="d-none" id="mortalityChartNoData">
                                    <div class="text-center py-5">
                                        <i class="ki-duotone ki-chart-line fs-3x text-muted mb-3">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        <h5 class="text-muted">No Mortality Data Available</h5>
                                        <p class="text-muted">Please adjust your filters or check data
                                            availability for
                                            the selected period.</p>
                                    </div>
                                </div>

                                <!-- Debug Information Panel -->
                                <div class="d-none" id="mortalityChartDebug">
                                    <div class="alert alert-info mt-3">
                                        <h6>Debug Information:</h6>
                                        <div id="debugOutput"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Interactive Mortality Statistics Cards (Updated by Chart) -->
                <div class="row g-5 mb-5">
                    <div class="col-xl-3">
                        <div class="card mortality-stat-card"
                            style="background: linear-gradient(145deg, #f3e8ff, #ffffff); border: none; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease;">
                            <div class="card-body text-center" style="padding: 25px;">
                                <h3 style="color: #333; font-size: 1.2em; margin-bottom: 10px;">Total Deaths</h3>
                                <div class="mortality-stat-number"
                                    style="font-size: 2.5em; font-weight: bold; color: #dc3545; margin-bottom: 5px;">
                                    {{ number_format($this->getMortalityAnalysis()->sum('total_mortality') ?? 0) }}
                                </div>
                                <p style="margin: 0; color: #666;">From filtered data</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3">
                        <div class="card mortality-stat-card"
                            style="background: linear-gradient(145deg, #e3f2fd, #ffffff); border: none; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease;">
                            <div class="card-body text-center" style="padding: 25px;">
                                <h3 style="color: #333; font-size: 1.2em; margin-bottom: 10px;">Active Sources</h3>
                                <div class="mortality-stat-number"
                                    style="font-size: 2.5em; font-weight: bold; color: #2196f3; margin-bottom: 5px;">
                                    {{ $this->getMortalityAnalysis()->count() }}
                                </div>
                                <p style="margin: 0; color: #666;">Sources displayed</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3">
                        <div class="card mortality-stat-card"
                            style="background: linear-gradient(145deg, #fff3e0, #ffffff); border: none; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease;">
                            <div class="card-body text-center" style="padding: 25px;">
                                <h3 style="color: #333; font-size: 1.2em; margin-bottom: 10px;">Avg Deaths/Day</h3>
                                <div class="mortality-stat-number"
                                    style="font-size: 2.5em; font-weight: bold; color: #ff9800; margin-bottom: 5px;">
                                    {{ number_format($this->getMortalityAnalysis()->avg('avg_mortality_rate') ?? 0, 1)
                                    }}
                                </div>
                                <p style="margin: 0; color: #666;">Average per day</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3">
                        <div class="card mortality-stat-card"
                            style="background: linear-gradient(145deg, #e8f5e8, #ffffff); border: none; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease;">
                            <div class="card-body text-center" style="padding: 25px;">
                                <h3 style="color: #333; font-size: 1.2em; margin-bottom: 10px;">Healthy Coops</h3>
                                <div class="mortality-stat-number"
                                    style="font-size: 2.5em; font-weight: bold; color: #4caf50; margin-bottom: 5px;">
                                    {{ $this->getMortalityAnalysis()->where('avg_mortality_rate', '<', 3)->count() }}/{{
                                        $this->getMortalityAnalysis()->count() }}
                                </div>
                                <p style="margin: 0; color: #666;">Low mortality rate</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Info with Tips -->
                <div
                    style="background: linear-gradient(145deg, #e3f2fd, #ffffff); border-radius: 10px; padding: 15px; margin-bottom: 20px; border-left: 4px solid #2196f3;">
                    <p style="color: #1976d2; font-weight: 500; margin: 0;">
                        💡 Tip: Click on chart legend items to hide/show data sources. Statistics will update
                        dynamically.
                    </p>
                </div>

                <!-- Mortality Analysis Table -->
                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 gy-7">
                        <thead>
                            <tr class="fw-bold fs-6 text-gray-800">
                                <th>Coop</th>
                                <th>Farm</th>
                                <th>Avg Mortality Rate</th>
                                <th>Total Deaths</th>
                                <th>Avg Population</th>
                                <th>Days Recorded</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->getMortalityAnalysis() as $analysis)
                            <tr>
                                <td>{{ $analysis->coop->name ?? 'N/A' }}</td>
                                <td>{{ $analysis->farm->name ?? 'N/A' }}</td>
                                <td>
                                    <span
                                        class="{{ $analysis->avg_mortality_rate > 5 ? 'text-danger' : ($analysis->avg_mortality_rate > 3 ? 'text-warning' : 'text-success') }}">
                                        {{ number_format($analysis->avg_mortality_rate, 2) }}%
                                    </span>
                                </td>
                                <td>{{ number_format($analysis->total_mortality) }}</td>
                                <td>{{ number_format($analysis->avg_population) }}</td>
                                <td>{{ $analysis->days_recorded }}</td>
                                <td>
                                    @if ($analysis->avg_mortality_rate > 5)
                                    <span class="badge badge-danger">Critical</span>
                                    @elseif($analysis->avg_mortality_rate > 3)
                                    <span class="badge badge-warning">High</span>
                                    @else
                                    <span class="badge badge-success">Normal</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <!-- Sales Analysis Tab -->
                @if ($activeTab === 'sales')
                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 gy-7">
                        <thead>
                            <tr class="fw-bold fs-6 text-gray-800">
                                <th>Coop</th>
                                <th>Farm</th>
                                <th>Total Sales</th>
                                <th>Total Weight (kg)</th>
                                <th>Total Revenue</th>
                                <th>Avg Price/Bird</th>
                                <th>Performance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->getSalesAnalysis() as $analysis)
                            <tr>
                                <td>{{ $analysis->coop->name ?? 'N/A' }}</td>
                                <td>{{ $analysis->farm->name ?? 'N/A' }}</td>
                                <td>{{ number_format($analysis->total_sales) }}</td>
                                <td>{{ number_format($analysis->total_weight, 2) }}</td>
                                <td>Rp {{ number_format($analysis->total_revenue, 0) }}</td>
                                <td>Rp {{ number_format($analysis->avg_price_per_bird, 0) }}</td>
                                <td>
                                    @if ($analysis->total_revenue > 100000000)
                                    <span class="badge badge-success">Excellent</span>
                                    @elseif($analysis->total_revenue > 50000000)
                                    <span class="badge badge-primary">Good</span>
                                    @elseif($analysis->total_revenue > 10000000)
                                    <span class="badge badge-warning">Average</span>
                                    @else
                                    <span class="badge badge-secondary">Low</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <!-- Production Analysis Tab -->
                @if ($activeTab === 'production')
                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 gy-7">
                        <thead>
                            <tr class="fw-bold fs-6 text-gray-800">
                                <th>Coop</th>
                                <th>Farm</th>
                                <th>Avg Daily Gain (g)</th>
                                <th>Avg FCR</th>
                                <th>Production Index</th>
                                <th>Efficiency Score</th>
                                <th>Grade</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->getProductionAnalysis() as $analysis)
                            <tr>
                                <td>{{ $analysis->coop->name ?? 'N/A' }}</td>
                                <td>{{ $analysis->farm->name ?? 'N/A' }}</td>
                                <td>
                                    <span
                                        class="{{ $analysis->avg_daily_gain < 30 ? 'text-danger' : ($analysis->avg_daily_gain < 40 ? 'text-warning' : 'text-success') }}">
                                        {{ number_format($analysis->avg_daily_gain, 1) }}g
                                    </span>
                                </td>
                                <td>
                                    <span
                                        class="{{ $analysis->avg_fcr > 2.5 ? 'text-danger' : ($analysis->avg_fcr > 2.0 ? 'text-warning' : 'text-success') }}">
                                        {{ number_format($analysis->avg_fcr, 2) }}
                                    </span>
                                </td>
                                <td>{{ number_format($analysis->avg_production_index, 1) }}</td>
                                <td>
                                    <span class="{{ $this->getPerformanceColor($analysis->avg_efficiency_score) }}">
                                        {{ number_format($analysis->avg_efficiency_score, 1) }}%
                                    </span>
                                </td>
                                <td>
                                    <span
                                        class="badge {{ $this->getPerformanceBadge($analysis->avg_efficiency_score) }}">
                                        @if ($analysis->avg_efficiency_score >= 90)
                                        A+
                                        @elseif($analysis->avg_efficiency_score >= 80)
                                        A
                                        @elseif($analysis->avg_efficiency_score >= 70)
                                        B+
                                        @elseif($analysis->avg_efficiency_score >= 60)
                                        B
                                        @elseif($analysis->avg_efficiency_score >= 50)
                                        C
                                        @else
                                        D
                                        @endif
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <!-- Coop Rankings Tab -->
                @if ($activeTab === 'rankings')
                <div class="table-responsive">
                    <table class="table table-row-dashed table-row-gray-300 gy-7">
                        <thead>
                            <tr class="fw-bold fs-6 text-gray-800">
                                <th>Rank</th>
                                <th>Coop</th>
                                <th>Farm</th>
                                <th>Overall Score</th>
                                <th>Mortality Rate</th>
                                <th>FCR</th>
                                <th>Revenue</th>
                                <th>Award</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->getCoopRankings() as $index => $ranking)
                            <tr>
                                <td>
                                    @if ($index === 0)
                                    <i class="ki-duotone ki-crown fs-1 text-warning">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    @elseif($index === 1)
                                    <i class="ki-duotone ki-medal fs-1 text-secondary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                    @elseif($index === 2)
                                    <i class="ki-duotone ki-medal fs-1 text-warning">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                    @else
                                    <span class="fw-bold text-gray-600">{{ $index + 1 }}</span>
                                    @endif
                                </td>
                                <td>{{ $ranking->coop->name ?? 'N/A' }}</td>
                                <td>{{ $ranking->farm->name ?? 'N/A' }}</td>
                                <td>
                                    <span class="{{ $this->getPerformanceColor($ranking->overall_score) }} fw-bold">
                                        {{ number_format($ranking->overall_score, 1) }}
                                    </span>
                                </td>
                                <td>{{ number_format($ranking->avg_mortality, 2) }}%</td>
                                <td>{{ number_format($ranking->avg_fcr, 2) }}</td>
                                <td>Rp {{ number_format($ranking->total_revenue, 0) }}</td>
                                <td>
                                    @if ($index === 0)
                                    <span class="badge badge-warning">🏆 Champion</span>
                                    @elseif($index <= 2) <span class="badge badge-primary">🥉 Top 3</span>
                                        @elseif($ranking->overall_score >= 80)
                                        <span class="badge badge-success">⭐ Excellent</span>
                                        @elseif($ranking->overall_score >= 60)
                                        <span class="badge badge-info">✓ Good</span>
                                        @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <!-- Alerts Tab -->
                @if ($activeTab === 'alerts')
                <div class="row g-5">
                    @foreach ($this->getActiveAlerts() as $alert)
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-{{ $alert->severity_color }} h-100">
                            <div class="card-header">
                                <div class="d-flex align-items-center">
                                    <i
                                        class="ki-duotone ki-{{ $alert->severity_icon }} fs-2x text-{{ $alert->severity_color }} me-3">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1">{{ $alert->title }}</h5>
                                        <span class="badge badge-{{ $alert->severity_color }}">{{
                                            ucfirst($alert->severity) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="text-gray-700">{{ $alert->description }}</p>
                                <div class="separator my-3"></div>
                                <p class="text-primary fw-semibold">
                                    <i class="ki-duotone ki-information fs-3 text-primary me-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                    {{ $alert->recommendation }}
                                </p>
                                <div class="mt-3">
                                    <small class="text-muted">
                                        <strong>Location:</strong>
                                        {{ $alert->farm->name ?? 'N/A' }} -
                                        {{ $alert->coop->name ?? 'N/A' }}
                                    </small>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button wire:click="resolveAlert('{{ $alert->id }}')"
                                    class="btn btn-sm btn-light-primary w-100">
                                    <i class="ki-duotone ki-check fs-4 me-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                    Mark as Resolved
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    @if ($this->getActiveAlerts()->count() === 0)
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body text-center p-10">
                                <i class="ki-duotone ki-check-circle fs-5x text-success mb-5">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                <h3 class="text-gray-800 mb-3">No Active Alerts</h3>
                                <p class="text-gray-600">All livestock operations are performing within
                                    normal
                                    parameters.</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
                @endif

            </div>
            <!--end::Card body-->
        </div>
        <!--end::Main Content-->

    </div>
</div>
<!--end::Content-->
</div>

@push('scripts')
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('livewire:init', () => {
            // Chart variables - properly declared
            let mortalityChart, efficiencyChart, fcrChart, revenueChart;
            let loadingTimeout;
            let loadingStartTime = null;

            // Initialize advanced mortality chart variable globally
            window.advancedMortalityChart = null;

            // Check Chart.js availability at startup
            log('[Analytics Debug] Chart.js Status:', {
                available: typeof Chart !== 'undefined',
                version: typeof Chart !== 'undefined' ? Chart.version : 'N/A'
            });

                    // Enhanced Livewire event listeners for chart updates
        Livewire.on('mortality-chart-updated', (data) => {
            log('[Mortality Chart Debug] Chart update event received - ENHANCED', {
                trigger: data?.trigger || 'unknown',
                force_refresh: data?.force_refresh || false,
                chart_type: data?.chart_type,
                view_type: data?.view_type,
                timestamp: new Date().toISOString()
            });
            
            // For livestock filter changes, force complete chart refresh
            const isLivestockChange = data?.trigger === 'livestock_filter_change';
            const forceRefresh = data?.force_refresh || isLivestockChange;
            
            log('[Mortality Chart Debug] Chart refresh strategy:', {
                isLivestockChange: isLivestockChange,
                forceRefresh: forceRefresh,
                delay: forceRefresh ? 100 : 200
            });
            
            // Use shorter delay for livestock changes to ensure responsiveness
            setTimeout(() => {
                initializeAdvancedMortalityChart();
            }, forceRefresh ? 100 : 200);
        });

        // NEW: Listen for fresh chart data from server (AJAX refresh)
        Livewire.on('mortality-chart-data-refreshed', (freshChartData) => {
            // log(freshChartData);
            log('[Mortality Chart Debug] Fresh chart data received from server - AJAX REFRESH', {
                type: freshChartData[0]?.type,
                title: freshChartData[0]?.title,
                labels_count: freshChartData[0]?.labels?.length || 0,
                datasets_count: freshChartData[0]?.datasets?.length || 0,
                timestamp: new Date().toISOString()
            });

            // Store fresh data globally for chart initialization
            window.currentMortalityChartData = freshChartData[0];

            // initializeAdvancedMortalityChartWithData(freshChartData[0]);

            // If we're on mortality tab, immediately update the chart with fresh data
            const mortalityTab = document.querySelector('.nav-link[wire\\:click\\.prevent*="mortality"]');
            if (mortalityTab && mortalityTab.classList.contains('active')) {
                log('[Mortality Chart Debug] Updating chart with fresh data immediately');
                
                // Destroy existing chart
                if (window.advancedMortalityChart) {
                    window.advancedMortalityChart.destroy();
                    window.advancedMortalityChart = null;
                }
                
                // Initialize chart with fresh data
                setTimeout(() => {
                    initializeAdvancedMortalityChartWithData(freshChartData[0]);
                }, 100);
            } else {
                log('[Mortality Chart Debug] Fresh data stored for later use');
            }
        });



            log('[Analytics Debug] Livewire initialized');

            // Enhanced loading timeout with multiple clear layers
            function startLoadingTimeout() {
                log('[Analytics Debug] Starting loading timeout with multiple protection layers');

                loadingStartTime = Date.now();

                // Clear any existing timeouts
                clearTimeout(loadingTimeout);

                // Layer 1: Quick timeout for tab changes and immediate clearing (2 seconds)
                loadingTimeout = setTimeout(() => {
                    log('[Analytics Debug] Layer 1 timeout (2s) - Quick clear for responsive UI');
                    const loadingOverlay = document.getElementById('loadingOverlay');
                    if (loadingOverlay && loadingOverlay.offsetParent !== null) {
                        // Check if this might be a tab change (should clear immediately)
                        log('[Analytics Debug] Layer 1: Clearing loading overlay');
                        loadingOverlay.style.display = 'none';
                        loadingOverlay.classList.add('d-none');
                    }
                }, 2000);

                // Layer 2: Standard timeout for data loading (5 seconds)
                setTimeout(() => {
                    log('[Analytics Debug] Layer 2 timeout (5s) - Standard data loading timeout');
                    const loadingOverlay = document.getElementById('loadingOverlay');
                    if (loadingOverlay && loadingOverlay.offsetParent !== null) {
                        log(
                            '[Analytics Debug] Layer 2: Loading still active after 5s, forcing clear');
                        loadingOverlay.style.display = 'none';
                        loadingOverlay.classList.add('d-none');

                        if (typeof toastr !== 'undefined') {
                            toastr.warning('Loading took longer than expected, continuing...');
                        }
                    }
                }, 5000);

                // Layer 3: Maximum timeout for any operation (10 seconds)
                setTimeout(() => {
                    log('[Analytics Debug] Layer 3 timeout (10s) - Maximum operation timeout');
                    const loadingOverlay = document.getElementById('loadingOverlay');
                    if (loadingOverlay && loadingOverlay.offsetParent !== null) {
                        log('[Analytics Debug] Layer 3: Force clearing after 10s maximum');
                        loadingOverlay.style.display = 'none';
                        loadingOverlay.classList.add('d-none');

                        // Show error state if still loading after 10s
                        const errorState = document.getElementById('errorState');
                        if (errorState) {
                            errorState.classList.remove('d-none');
                            errorState.style.display = 'block';
                        }

                        if (typeof toastr !== 'undefined') {
                            toastr.error(
                                'Loading timed out. Please check your connection and try refreshing.');
                        }
                    }
                }, 10000);

                // Show loading overlay
                const loadingOverlay = document.getElementById('loadingOverlay');
                if (loadingOverlay) {
                    loadingOverlay.style.display = 'block';
                    loadingOverlay.classList.remove('d-none');
                    log('[Analytics Debug] Loading overlay shown with multi-layer timeout protection');
                }
            }

            // Enhanced clear loading timeout with immediate overlay hide
            function clearLoadingTimeout() {
                log('[Analytics Debug] Clearing loading timeout and overlay immediately');

                clearTimeout(loadingTimeout);
                loadingStartTime = null;

                // Immediately hide loading overlay with multiple methods
                const loadingOverlay = document.getElementById('loadingOverlay');
                if (loadingOverlay) {
                    loadingOverlay.style.display = 'none';
                    loadingOverlay.classList.add('d-none');
                    loadingOverlay.style.visibility = 'hidden';
                    loadingOverlay.style.opacity = '0';

                    log('[Analytics Debug] Loading overlay immediately hidden with all methods');
                }

                // Hide error state if it was shown
                const errorState = document.getElementById('errorState');
                if (errorState && !errorState.classList.contains('d-none')) {
                    errorState.classList.add('d-none');
                    errorState.style.display = 'none';
                    log('[Analytics Debug] Error state hidden');
                }

                log('[Analytics Debug] All loading states cleared');
            }

            function initializeCharts(forceReinit = false) {
                log('[Analytics Debug] Chart initialization requested', {
                    forceReinit: forceReinit
                });

                // Only initialize charts if we're on the overview tab
                const isOverviewTab = document.querySelector('.nav-link[wire\\:click\\.prevent*="overview"]')
                    ?.classList.contains('active');

                log('[Analytics Debug] Overview tab status:', isOverviewTab);

                if (!isOverviewTab) {
                    log('[Analytics Debug] Not on overview tab, skipping chart initialization');
                    return;
                }

                log('[Analytics Debug] Initializing charts with fresh data', {
                    forceReinit: forceReinit,
                    existingCharts: {
                        mortality: !!mortalityChart,
                        efficiency: !!efficiencyChart,
                        fcr: !!fcrChart,
                        revenue: !!revenueChart
                    }
                });

                try {
                    // Always get fresh data from the server
                    const mortalityData = @json($this->getChartData('mortality'));
                    const efficiencyData = @json($this->getChartData('efficiency'));
                    const fcrData = @json($this->getChartData('fcr'));
                    const revenueData = @json($this->getChartData('revenue'));

                    log('[Analytics Debug] Fresh chart data loaded:', {
                        mortality: mortalityData,
                        efficiency: efficiencyData,
                        fcr: fcrData,
                        revenue: revenueData
                    });

                    // Always destroy existing charts before creating new ones to ensure fresh data
                    if (mortalityChart) {
                        mortalityChart.destroy();
                        mortalityChart = null;
                        log('[Analytics Debug] Destroyed existing mortality chart');
                    }
                    if (efficiencyChart) {
                        efficiencyChart.destroy();
                        efficiencyChart = null;
                        log('[Analytics Debug] Destroyed existing efficiency chart');
                    }
                    if (fcrChart) {
                        fcrChart.destroy();
                        fcrChart = null;
                        log('[Analytics Debug] Destroyed existing FCR chart');
                    }
                    if (revenueChart) {
                        revenueChart.destroy();
                        revenueChart = null;
                        log('[Analytics Debug] Destroyed existing revenue chart');
                    }

                    // Create all charts with fresh data
                    if (document.getElementById('mortalityChart')) {
                        log('[Analytics Debug] Creating fresh mortality chart');
                        const ctx1 = document.getElementById('mortalityChart').getContext('2d');
                        mortalityChart = new Chart(ctx1, {
                            type: 'line',
                            data: {
                                labels: mortalityData.labels || [],
                                datasets: [{
                                    label: mortalityData.label || 'Mortality Rate',
                                    data: mortalityData.data || [],
                                    borderColor: mortalityData.color || 'rgb(239, 68, 68)',
                                    backgroundColor: (mortalityData.color || 'rgb(239, 68, 68)') +
                                        '20',
                                    tension: 0.4,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                aspectRatio: 2,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                        log('[Analytics Debug] Mortality chart created successfully');
                    }

                    if (document.getElementById('efficiencyChart')) {
                        log('[Analytics Debug] Creating fresh efficiency chart');
                        const ctx2 = document.getElementById('efficiencyChart').getContext('2d');
                        efficiencyChart = new Chart(ctx2, {
                            type: 'line',
                            data: {
                                labels: efficiencyData.labels || [],
                                datasets: [{
                                    label: efficiencyData.label || 'Efficiency Score',
                                    data: efficiencyData.data || [],
                                    borderColor: efficiencyData.color || 'rgb(34, 197, 94)',
                                    backgroundColor: (efficiencyData.color || 'rgb(34, 197, 94)') +
                                        '20',
                                    tension: 0.4,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                aspectRatio: 2,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        max: 100
                                    }
                                }
                            }
                        });
                        log('[Analytics Debug] Efficiency chart created successfully');
                    }

                    if (document.getElementById('fcrChart')) {
                        log('[Analytics Debug] Creating fresh FCR chart');
                        const ctx3 = document.getElementById('fcrChart').getContext('2d');
                        fcrChart = new Chart(ctx3, {
                            type: 'line',
                            data: {
                                labels: fcrData.labels || [],
                                datasets: [{
                                    label: fcrData.label || 'Feed Conversion Ratio',
                                    data: fcrData.data || [],
                                    borderColor: fcrData.color || 'rgb(59, 130, 246)',
                                    backgroundColor: (fcrData.color || 'rgb(59, 130, 246)') + '20',
                                    tension: 0.4,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                aspectRatio: 2,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                        log('[Analytics Debug] FCR chart created successfully');
                    }

                    if (document.getElementById('revenueChart')) {
                        log('[Analytics Debug] Creating fresh revenue chart');
                        const ctx4 = document.getElementById('revenueChart').getContext('2d');
                        revenueChart = new Chart(ctx4, {
                            type: 'line',
                            data: {
                                labels: revenueData.labels || [],
                                datasets: [{
                                    label: revenueData.label || 'Daily Revenue',
                                    data: revenueData.data || [],
                                    borderColor: revenueData.color || 'rgb(168, 85, 247)',
                                    backgroundColor: (revenueData.color || 'rgb(168, 85, 247)') +
                                        '20',
                                    tension: 0.4,
                                    fill: true
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                aspectRatio: 2,
                                plugins: {
                                    legend: {
                                        display: false
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                        log('[Analytics Debug] Revenue chart created successfully');
                    }

                    log('[Analytics Debug] All charts created successfully with fresh data');
                } catch (error) {
                    console.error('[Analytics Debug] Error initializing charts:', error);
                    if (typeof toastr !== 'undefined') {
                        toastr.warning('Charts could not be loaded. Data may be empty.');
                    }
                }
            }

            // Initialize charts on page load
            log('[Analytics Debug] Scheduling initial chart initialization');
            setTimeout(initializeCharts, 100);

            // Enhanced Livewire event listeners with comprehensive tab change detection
            document.addEventListener('livewire:request', (event) => {
                log('[Analytics Debug] Livewire request started:', event);

                // Multiple methods to detect tab change requests
                const requestMethod = event.detail?.method;
                const requestPayload = event.detail?.payload;
                const requestComponent = event.detail?.component || {};

                // Check for tab change in multiple ways
                const isTabChange = requestMethod === 'setActiveTab' ||
                    (requestPayload && JSON.stringify(requestPayload).includes('setActiveTab')) ||
                    (requestComponent.calls && requestComponent.calls.some(call =>
                        call.method === 'setActiveTab' ||
                        (call.params && JSON.stringify(call.params).includes('setActiveTab'))
                    ));

                log('[Analytics Debug] Request analysis:', {
                    method: requestMethod,
                    isTabChange: isTabChange,
                    payloadContainsTab: requestPayload ? JSON.stringify(requestPayload).includes(
                        'setActiveTab') : false,
                    componentCalls: requestComponent.calls || []
                });

                if (isTabChange) {
                    log('[Analytics Debug] Tab change request detected - BLOCKING loading timeout');
                    return;
                }

                log('[Analytics Debug] Filter/data request detected - STARTING loading timeout');
                startLoadingTimeout();
            });

            document.addEventListener('livewire:finished', (event) => {
                log('[Analytics Debug] Livewire request finished:', event);
                clearLoadingTimeout();
            });

            // Additional Livewire 3 event listeners
            document.addEventListener('livewire:load', (event) => {
                log('[Analytics Debug] Livewire component loaded:', event);
                clearLoadingTimeout();
            });

            document.addEventListener('livewire:update', (event) => {
                log('[Analytics Debug] Livewire component updated:', event);
                clearLoadingTimeout();
            });

            // Fallback timeout that runs independent of Livewire events
            let fallbackTimeout = setTimeout(() => {
                log('[Analytics Debug] Fallback timeout triggered after 20 seconds');
                const loadingOverlay = document.getElementById('loadingOverlay');
                const errorState = document.getElementById('errorState');

                if (loadingOverlay && loadingOverlay.offsetParent !== null) {
                    log('[Analytics Debug] Fallback: Forcing overlay hide');
                    loadingOverlay.style.display = 'none';
                    loadingOverlay.classList.add('d-none');

                    if (errorState) {
                        errorState.classList.remove('d-none');
                        errorState.style.display = 'block';
                    }

                    if (typeof toastr !== 'undefined') {
                        toastr.error('Loading timeout (fallback). Please refresh the page.');
                    }
                }
            }, 20000); // 20 second fallback

            // Reinitialize charts when analytics are updated - FORCE chart refresh for new data
            Livewire.on('analytics-updated', (data) => {
                log('[Analytics Debug] Analytics updated event received - FORCING chart refresh:',
                    data);
                clearLoadingTimeout();
                setTimeout(() => {
                    initializeCharts(true); // Force reinitialize = true for data updates
                }, 100);
            });

            // Handle tab changes - ENSURE DATA AVAILABILITY and preserve charts
            Livewire.on('tab-changed', (event) => {
                log('[Analytics Debug] Tab changed event received:', event);
                clearLoadingTimeout();

                const tab = event[0]?.tab || event.tab;
                log('[Analytics Debug] Switching to tab:', tab);

                if (tab === 'overview') {
                    // Initialize overview charts after small delay to ensure DOM is ready
                    setTimeout(() => {
                        initializeCharts(true);
                    }, 100);
                } else if (tab === 'mortality') {
                    // Initialize advanced mortality chart for mortality tab
                    log('[Analytics Debug] Mortality tab detected, initializing advanced chart');
                    initializeMortalityChartOnTabChange();
                }
            });

            // Enhanced data refreshed event with livestock filter support
            Livewire.on('data-refreshed', (event) => {
                log('[Analytics Debug] Data refreshed event received - ENHANCED FOR LIVESTOCK:', event);
                clearLoadingTimeout();

                // Check for livestock filter specific changes
                const trigger = event?.trigger || 'unknown';
                const isLivestockChange = trigger === 'livestock_filter_change';
                const livestockId = event?.livestock_id;

                log('[Analytics Debug] Data refresh analysis:', {
                    trigger: trigger,
                    isLivestockChange: isLivestockChange,
                    livestock_id: livestockId,
                    active_tab: event?.active_tab
                });

                // Get current active tab
                const activeTab = document.querySelector('.nav-link.active')?.getAttribute(
                    'wire:click.prevent');
                log('[Analytics Debug] Current active tab for refresh:', activeTab);

                if (activeTab && activeTab.includes('overview')) {
                    log('[Analytics Debug] Overview tab - forcing chart refresh for livestock change');
                    setTimeout(() => {
                        initializeCharts(true);
                    }, isLivestockChange ? 50 : 100); // Faster for livestock changes
                } else if (activeTab && activeTab.includes('mortality')) {
                    log('[Analytics Debug] Mortality tab - reinitializing chart for livestock change');
                    
                    // For livestock changes, force complete reinitialization
                    if (isLivestockChange) {
                        log('[Analytics Debug] Livestock filter change detected - FORCE CHART REFRESH');
                        setTimeout(() => {
                            // Destroy existing chart first
                            if (window.advancedMortalityChart) {
                                log('[Analytics Debug] Destroying existing mortality chart for livestock change');
                                window.advancedMortalityChart.destroy();
                                window.advancedMortalityChart = null;
                            }
                            // Then reinitialize with new data
                            initializeAdvancedMortalityChart();
                        }, 50);
                    } else {
                        // Regular refresh
                        initializeMortalityChartOnTabChange();
                    }
                }

                log('[Analytics Debug] Data refresh handling completed for:', trigger);
            });

            // Handle analytics errors
            Livewire.on('analytics-error', (event) => {
                log('[Analytics Debug] Analytics error event received:', event);
                clearLoadingTimeout();
                if (typeof toastr !== 'undefined') {
                    toastr.error(event.message || 'Failed to load analytics data');
                } else {
                    alert(event.message || 'Failed to load analytics data');
                }
            });

            // Show notifications
            Livewire.on('alert-resolved', (event) => {
                log('[Analytics Debug] Alert resolved event:', event);
                if (typeof toastr !== 'undefined') {
                    if (event.type === 'success') {
                        toastr.success(event.message);
                    } else {
                        toastr.error(event.message);
                    }
                }
            });

            Livewire.on('calculation-complete', (event) => {
                log('[Analytics Debug] Calculation complete event:', event);
                if (typeof toastr !== 'undefined') {
                    if (event.type === 'success') {
                        toastr.success(event.message);
                    } else {
                        toastr.error(event.message);
                    }
                }
            });

            // Debug helper functions
            window.debugAnalytics = function() {
                const loadingOverlay = document.getElementById('loadingOverlay');
                const errorState = document.getElementById('errorState');

                log('[Analytics Debug] Manual debug check:', {
                    loadingOverlay: loadingOverlay,
                    overlayVisible: loadingOverlay ? loadingOverlay.offsetParent !== null : false,
                    overlayDisplay: loadingOverlay ? loadingOverlay.style.display : null,
                    overlayClasses: loadingOverlay ? loadingOverlay.className : null,
                    errorState: errorState,
                    errorVisible: errorState ? errorState.offsetParent !== null : false,
                    loadingStartTime: loadingStartTime,
                    timeoutActive: !!loadingTimeout,
                    elapsedTime: loadingStartTime ? Date.now() - loadingStartTime : 0
                });

                // Show debug info to user
                if (typeof toastr !== 'undefined') {
                    const elapsedTime = loadingStartTime ? Math.round((Date.now() - loadingStartTime) / 1000) :
                        0;
                    toastr.info(`Debug: Loading for ${elapsedTime}s. Check console for details.`);
                }
            };

            window.forceHideLoading = function() {
                log('[Analytics Debug] Force hiding loading overlay');
                const loadingOverlay = document.getElementById('loadingOverlay');
                const errorState = document.getElementById('errorState');

                if (loadingOverlay) {
                    loadingOverlay.style.display = 'none';
                    loadingOverlay.classList.add('d-none');
                    log('[Analytics Debug] Loading overlay forcefully hidden');
                }

                if (errorState) {
                    errorState.classList.remove('d-none');
                    errorState.style.display = 'block';
                    log('[Analytics Debug] Error state shown');
                }

                clearTimeout(loadingTimeout);
                clearTimeout(fallbackTimeout);

                if (typeof toastr !== 'undefined') {
                    toastr.success('Loading overlay forcefully hidden');
                }
            };

            // Additional event listener for manual tab clicks - COMPREHENSIVE prevention
            document.addEventListener('DOMContentLoaded', function() {
                // Use more specific selector to target our exact tab links
                const tabLinks = document.querySelectorAll('a[wire\\:click\\.prevent*="setActiveTab"]');
                log('[Analytics Debug] Setting up comprehensive tab click listeners for', tabLinks
                    .length, 'tabs');

                tabLinks.forEach((link, index) => {
                    // Add data attribute to identify as tab link
                    link.setAttribute('data-tab-link', 'true');

                    link.addEventListener('click', function(e) {
                        log(
                            `[Analytics Debug] Tab click #${index} detected - COMPREHENSIVE PREVENTION`
                            );

                        // Immediately clear all loading states
                        clearLoadingTimeout();

                        // Multiple overlay hiding methods
                        const loadingOverlay = document.getElementById('loadingOverlay');
                        if (loadingOverlay) {
                            loadingOverlay.style.display = 'none !important';
                            loadingOverlay.classList.add('d-none');
                            loadingOverlay.style.visibility = 'hidden';
                            loadingOverlay.style.opacity = '0';
                            loadingOverlay.style.zIndex = '-1';
                            log(
                                '[Analytics Debug] Tab click: Loading overlay completely hidden'
                                );
                        }

                        // Ultra-aggressive prevention with multiple timings
                        [10, 25, 50, 100, 200, 500].forEach(delay => {
                            setTimeout(() => {
                                const overlay = document.getElementById(
                                    'loadingOverlay');
                                if (overlay) {
                                    overlay.style.display =
                                        'none !important';
                                    overlay.classList.add('d-none');
                                    overlay.style.visibility = 'hidden';
                                    overlay.style.opacity = '0';
                                    overlay.style.zIndex = '-1';
                                }
                            }, delay);
                        });
                    });

                    // Pre-emptive prevention on mousedown
                    link.addEventListener('mousedown', function(e) {
                        log(
                            `[Analytics Debug] Tab mousedown #${index} - PRE-EMPTIVE prevention`
                            );
                        clearLoadingTimeout();

                        const loadingOverlay = document.getElementById('loadingOverlay');
                        if (loadingOverlay) {
                            loadingOverlay.style.display = 'none !important';
                            loadingOverlay.classList.add('d-none');
                            loadingOverlay.style.visibility = 'hidden';
                            loadingOverlay.style.opacity = '0';
                            loadingOverlay.style.zIndex = '-1';
                        }
                    });

                    // Additional prevention on focus
                    link.addEventListener('focus', function(e) {
                        log(
                            `[Analytics Debug] Tab focus #${index} - FOCUS prevention`);
                        clearLoadingTimeout();
                    });
                });
            });

            // Force clear loading event - Additional safety layer
            Livewire.on('force-clear-loading', () => {
                log('[Analytics Debug] Force clear loading event received - IMMEDIATE ACTION');
                clearLoadingTimeout();

                // Aggressive overlay clearing
                const loadingOverlay = document.getElementById('loadingOverlay');
                if (loadingOverlay) {
                    loadingOverlay.style.display = 'none !important';
                    loadingOverlay.classList.add('d-none');
                    loadingOverlay.style.visibility = 'hidden';
                    loadingOverlay.style.opacity = '0';
                    loadingOverlay.style.zIndex = '-1';
                    log('[Analytics Debug] Force clear: Loading overlay aggressively hidden');
                }
            });

            log('[Analytics Debug] All event listeners registered');
        });

        // Initialize advanced mortality chart with comprehensive logging
        function initializeAdvancedMortalityChart() {
            log('[Mortality Chart Debug] ========== INITIALIZATION START ==========');
            log('[Mortality Chart Debug] Starting advanced mortality chart initialization');

            // Check if we're on the mortality tab
            const mortalityTab = document.querySelector('.nav-link[wire\\:click\\.prevent*="mortality"]');
            const isActive = mortalityTab?.classList.contains('active');
            log('[Mortality Chart Debug] Mortality tab active:', isActive);

            if (!isActive) {
                log('[Mortality Chart Debug] Not on mortality tab, skipping initialization');
                return;
            }

            // Try to use fresh data first, fallback to Blade data
            let chartData;
            if (window.currentMortalityChartData) {
                log('[Mortality Chart Debug] Using fresh data from server');
                chartData = window.currentMortalityChartData;
            } else {
                log('[Mortality Chart Debug] Using Blade template data as fallback');
                chartData = @json($this->getMortalityChartData());
            }

            // Call the new function with data
            initializeAdvancedMortalityChartWithData(chartData);
        }

        // NEW: Initialize mortality chart with specific data (fresh from server or Blade)
        function initializeAdvancedMortalityChartWithData(chartData) {
            // log(chartData);
            log('[Mortality Chart Debug] ========== CHART WITH DATA INITIALIZATION START ==========');
            log('[Mortality Chart Debug] Starting chart initialization with provided data');

            // Check Chart.js availability
            if (typeof Chart === 'undefined') {
                console.error('[Mortality Chart Debug] ❌ Chart.js not loaded!');
                setTimeout(() => {
                    log('[Mortality Chart Debug] Retrying chart initialization in 1 second...');
                    initializeAdvancedMortalityChartWithData(chartData);
                }, 1000);
                return;
            }
            log('[Mortality Chart Debug] ✅ Chart.js available, version:', Chart.version);

            // Check all required elements
            const elements = checkMortalityChartElements();

            if (!elements.canvas) {
                console.error('[Mortality Chart Debug] ❌ Canvas element not found! Retrying in 500ms...');
                setTimeout(() => {
                    initializeAdvancedMortalityChartWithData(chartData);
                }, 500);
                return;
            }

            // Check if canvas is visible and has dimensions
            const canvasRect = elements.canvas.getBoundingClientRect();
            log('[Mortality Chart Debug] Canvas dimensions:', {
                width: canvasRect.width,
                height: canvasRect.height,
                visible: canvasRect.width > 0 && canvasRect.height > 0
            });

            if (canvasRect.width === 0 || canvasRect.height === 0) {
                console.warn('[Mortality Chart Debug] ⚠️ Canvas has zero dimensions, forcing container visibility');
                if (elements.container) {
                    elements.container.style.display = 'block';
                    elements.container.style.height = '400px';
                }
                // Retry after making container visible
                setTimeout(() => {
                    initializeAdvancedMortalityChartWithData(chartData);
                }, 100);
                return;
            }

            try {
                log('[Mortality Chart Debug] Using provided chart data:', chartData);

                // Detailed data validation
                const dataValidation = {
                    hasData: !!chartData,
                    hasLabels: chartData?.labels && Array.isArray(chartData.labels),
                    labelsCount: chartData?.labels?.length || 0,
                    hasDatasets: chartData?.datasets && Array.isArray(chartData.datasets),
                    datasetsCount: chartData?.datasets?.length || 0,
                    chartType: chartData?.type,
                    hasOptions: !!chartData?.options
                };
                log('[Mortality Chart Debug] Data validation:', dataValidation);

                // Check if we have data
                if (!chartData || !chartData.labels || chartData.labels.length === 0) {
                    console.warn('[Mortality Chart Debug] ⚠️ No data available for chart');
                    if (elements.container) {
                        elements.container.style.display = 'none';
                        log('[Mortality Chart Debug] Chart container hidden');
                    }
                    if (elements.noData) {
                        elements.noData.classList.remove('d-none');
                        log('[Mortality Chart Debug] No data message shown');
                    }
                    return;
                }

                // Show chart container and hide no data message
                if (elements.container) {
                    elements.container.style.display = 'block';
                    log('[Mortality Chart Debug] Chart container shown');
                }
                if (elements.noData) {
                    elements.noData.classList.add('d-none');
                    log('[Mortality Chart Debug] No data message hidden');
                }

                // Destroy existing chart safely
                if (window.advancedMortalityChart && typeof window.advancedMortalityChart.destroy === 'function') {
                    log('[Mortality Chart Debug] Destroying existing chart');
                    window.advancedMortalityChart.destroy();
                    window.advancedMortalityChart = null;
                    log('[Mortality Chart Debug] ✅ Existing chart destroyed');
                }

                // Update chart title
                if (elements.title && chartData.title) {
                    elements.title.textContent = chartData.title;
                    log('[Mortality Chart Debug] Chart title updated:', chartData.title);
                }

                // Prepare chart configuration with modern styling
                const chartConfig = {
                    type: chartData.type || 'line',
                    data: {
                        labels: chartData.labels || [],
                        datasets: chartData.datasets || []
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        aspectRatio: 2,
                        plugins: {
                            title: {
                                display: true,
                                text: chartData.title || 'Mortality Chart',
                                font: {
                                    size: 18,
                                    weight: 'bold'
                                },
                                color: '#333',
                                padding: 20
                            },
                            legend: {
                                display: true,
                                position: 'top',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20,
                                    font: {
                                        size: 14
                                    }
                                },
                                onClick: function(e, legendItem, legend) {
                                    const index = legendItem.datasetIndex;
                                    const chart = legend.chart;
                                    
                                    if (chart.isDatasetVisible(index)) {
                                        chart.hide(index);
                                        legendItem.hidden = true;
                                    } else {
                                        chart.show(index);
                                        legendItem.hidden = false;
                                    }
                                    
                                    // Update statistics if available
                                    if (window.updateMortalityStats) {
                                        window.updateMortalityStats(chart);
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: '#fff',
                                borderWidth: 1,
                                cornerRadius: 10,
                                displayColors: true,
                                callbacks: {
                                    title: function(tooltipItems) {
                                        return 'Date: ' + tooltipItems[0].label;
                                    },
                                    label: function(context) {
                                        return context.dataset.label + ': ' + context.parsed.y + ' deaths';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                display: true,
                                title: {
                                    display: true,
                                    text: 'Date',
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    }
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                }
                            },
                            y: {
                                display: true,
                                title: {
                                    display: true,
                                    text: 'Deaths Count',
                                    font: {
                                        size: 14,
                                        weight: 'bold'
                                    }
                                },
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.1)'
                                },
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        },
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        animation: {
                            duration: 1000,
                            easing: 'easeInOutQuart'
                        },
                        // Merge any additional options from chartData
                        ...(chartData.options || {})
                    }
                };

                log('[Mortality Chart Debug] Chart configuration prepared:', {
                    type: chartConfig.type,
                    labelsCount: chartConfig.data.labels.length,
                    datasetsCount: chartConfig.data.datasets.length,
                    hasOptions: !!chartConfig.options
                });

                // Create chart context
                log('[Mortality Chart Debug] Creating chart context...');
                const ctx = elements.canvas.getContext('2d');

                if (!ctx) {
                    console.error('[Mortality Chart Debug] ❌ Failed to get 2D context from canvas');
                    return;
                }
                log('[Mortality Chart Debug] ✅ Chart context created');

                // Create the chart
                log('[Mortality Chart Debug] Creating Chart.js instance...');
                window.advancedMortalityChart = new Chart(ctx, chartConfig);

                log('[Mortality Chart Debug] ✅ Chart created successfully!');
                log('[Mortality Chart Debug] Chart instance:', {
                    id: window.advancedMortalityChart.id,
                    type: window.advancedMortalityChart.config.type,
                    canvas: window.advancedMortalityChart.canvas.id,
                    isInitialized: !!window.advancedMortalityChart.data
                });

                // Update initial statistics
                if (window.updateMortalityStats) {
                    window.updateMortalityStats(window.advancedMortalityChart);
                }

                // Force chart update and render
                setTimeout(() => {
                    if (window.advancedMortalityChart) {
                        try {
                            window.advancedMortalityChart.update('none');
                            window.advancedMortalityChart.render();
                            log('[Mortality Chart Debug] ✅ Chart forced update and render completed');
                        } catch (renderError) {
                            console.error('[Mortality Chart Debug] ❌ Error during chart render:', renderError);
                        }
                    }
                }, 100);

            } catch (error) {
                console.error('[Mortality Chart Debug] ❌ ERROR during chart creation:', error);
                console.error('[Mortality Chart Debug] Error stack:', error.stack);

                // Show no data state on error
                if (elements.container) {
                    elements.container.style.display = 'none';
                    log('[Mortality Chart Debug] Chart container hidden due to error');
                }
                if (elements.noData) {
                    elements.noData.classList.remove('d-none');
                    log('[Mortality Chart Debug] No data message shown due to error');

                    // Add error message to no data div
                    const errorMsg = elements.noData.querySelector('.text-center h5');
                    if (errorMsg) {
                        errorMsg.textContent = 'Chart Error: ' + error.message;
                    }
                }
            }

            log('[Mortality Chart Debug] ========== CHART WITH DATA INITIALIZATION END ==========');
        }

        // Check required DOM elements for mortality chart
        function checkMortalityChartElements() {
            const elements = {
                canvas: document.getElementById('advancedMortalityChart'),
                container: document.getElementById('mortalityChartContainer'),
                noData: document.getElementById('mortalityChartNoData'),
                title: document.getElementById('mortalityChartTitle'),
                selector: document.getElementById('mortalityChartType')
            };

            log('[Mortality Chart Debug] Required elements check:', {
                canvas: !!elements.canvas,
                container: !!elements.container,
                noData: !!elements.noData,
                title: !!elements.title,
                selector: !!elements.selector
            });

            return elements;
        }

        // Enhanced chart type selector handler with logging
        function handleMortalityChartTypeChange() {
            log('[Mortality Chart Debug] Setting up chart type selector handler');

            const selector = document.getElementById('mortalityChartType');
            if (!selector) {
                console.warn('[Mortality Chart Debug] Chart type selector not found');
                return;
            }

            log('[Mortality Chart Debug] Chart type selector found, adding event listener');
            selector.addEventListener('change', function(e) {
                const selectedType = e.target.value;
                log('[Mortality Chart Debug] Chart type changed to:', selectedType);

                if (selectedType === 'auto') {
                    log('[Mortality Chart Debug] Auto type selected, reinitializing chart');
                    initializeAdvancedMortalityChart();
                } else {
                    log('[Mortality Chart Debug] Manual type selected:', selectedType);
                    if (advancedMortalityChart && advancedMortalityChart.config) {
                        log('[Mortality Chart Debug] Updating existing chart type');
                        advancedMortalityChart.config.type = selectedType;
                        advancedMortalityChart.update();
                        log('[Mortality Chart Debug] ✅ Chart type updated successfully');
                    } else {
                        console.warn('[Mortality Chart Debug] No existing chart to update, initializing new one');
                        initializeAdvancedMortalityChart();
                    }
                }
            });

            log('[Mortality Chart Debug] ✅ Chart type selector handler registered');
        }

        // Enhanced mortality chart initialization on tab change
        function initializeMortalityChartOnTabChange() {
            log('[Mortality Chart Debug] Initializing mortality chart for tab change');

            // Small delay to ensure DOM is ready
            setTimeout(() => {
                log('[Mortality Chart Debug] Delayed initialization starting');
                initializeAdvancedMortalityChart();
                handleMortalityChartTypeChange();
                log('[Mortality Chart Debug] Delayed initialization completed');
            }, 200);
        }

        // Enhanced debug helper function
        function debugMortalityChartState() {
            log('[Mortality Chart Debug] ========== CHART STATE DEBUG ==========');

            const debugInfo = {
                chartJs: {
                    available: typeof Chart !== 'undefined',
                    version: typeof Chart !== 'undefined' ? Chart.version : 'N/A'
                },
                elements: {
                    canvas: !!document.getElementById('advancedMortalityChart'),
                    container: !!document.getElementById('mortalityChartContainer'),
                    noData: !!document.getElementById('mortalityChartNoData'),
                    title: !!document.getElementById('mortalityChartTitle'),
                    selector: !!document.getElementById('mortalityChartType')
                },
                chartInstance: {
                    exists: !!advancedMortalityChart,
                    id: advancedMortalityChart?.id,
                    type: advancedMortalityChart?.config?.type
                },
                tab: {
                    mortalityTabActive: document.querySelector('.nav-link[wire\\:click\\.prevent*="mortality"]')
                        ?.classList.contains('active')
                }
            };

            log('[Mortality Chart Debug] Debug info:', debugInfo);

            // Show debug info in UI
            const debugPanel = document.getElementById('mortalityChartDebug');
            const debugOutput = document.getElementById('debugOutput');

            if (debugPanel && debugOutput) {
                debugPanel.classList.remove('d-none');
                debugOutput.innerHTML = `
            <strong>Chart.js:</strong> ${debugInfo.chartJs.available ? '✅ Loaded (v' + debugInfo.chartJs.version + ')' : '❌ Not loaded'}<br>
            <strong>Canvas:</strong> ${debugInfo.elements.canvas ? '✅ Found' : '❌ Missing'}<br>
            <strong>Chart Instance:</strong> ${debugInfo.chartInstance.exists ? '✅ Created (' + debugInfo.chartInstance.type + ')' : '❌ Not created'}<br>
            <strong>Tab Active:</strong> ${debugInfo.tab.mortalityTabActive ? '✅ Mortality tab active' : '❌ Not on mortality tab'}<br>
            <button class="btn btn-sm btn-secondary mt-2" onclick="document.getElementById('mortalityChartDebug').classList.add('d-none')">Hide Debug</button>
        `;
            }

            // Try to manually test Chart.js
            if (typeof Chart !== 'undefined') {
                try {
                    const testCanvas = document.createElement('canvas');
                    testCanvas.width = 100;
                    testCanvas.height = 100;
                    const testCtx = testCanvas.getContext('2d');
                    const testChart = new Chart(testCtx, {
                        type: 'line',
                        data: {
                            labels: ['Test'],
                            datasets: [{
                                label: 'Test',
                                data: [1]
                            }]
                        }
                    });
                    testChart.destroy();
                    log('[Mortality Chart Debug] ✅ Chart.js test successful');
                } catch (error) {
                    console.error('[Mortality Chart Debug] ❌ Chart.js test failed:', error);
                }
            }

            log('[Mortality Chart Debug] ========================================');
        }

        // Force chart initialization with extensive logging
        function forceInitializeMortalityChart() {
            log('[Mortality Chart Debug] FORCE INITIALIZATION REQUESTED');

            // Wait for a moment to ensure DOM is ready
            setTimeout(() => {
                log('[Mortality Chart Debug] Starting forced initialization');

                // Ensure we're on mortality tab
                const mortalityTab = document.querySelector('.nav-link[wire\\:click\\.prevent*="mortality"]');
                if (mortalityTab && !mortalityTab.classList.contains('active')) {
                    log('[Mortality Chart Debug] Switching to mortality tab first');
                    mortalityTab.click();

                    // Wait for tab switch then initialize
                    setTimeout(() => {
                        initializeAdvancedMortalityChart();
                    }, 500);
                } else {
                    initializeAdvancedMortalityChart();
                }
            }, 100);
        }

        // Test Chart.js loading
        function testChartJsLoading() {
            log('[Mortality Chart Debug] Testing Chart.js loading...');

            if (typeof Chart === 'undefined') {
                console.warn('[Mortality Chart Debug] Chart.js not found, attempting to load...');

                // Try to load Chart.js if not available
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                script.onload = function() {
                    log('[Mortality Chart Debug] Chart.js loaded successfully');
                    setTimeout(() => {
                        initializeAdvancedMortalityChart();
                    }, 100);
                };
                script.onerror = function() {
                    console.error('[Mortality Chart Debug] Failed to load Chart.js');
                };
                document.head.appendChild(script);
            } else {
                log('[Mortality Chart Debug] Chart.js already available');
                initializeAdvancedMortalityChart();
            }
        }

        // Make debug functions globally available
        window.debugMortalityChart = debugMortalityChartState;
        window.forceInitializeMortalityChart = forceInitializeMortalityChart;
        window.testChartJsLoading = testChartJsLoading;
        
        // NEW: Manual refresh chart data function
        window.refreshMortalityChartData = function() {
            log('[Mortality Chart Debug] Manual refresh chart data requested');
            
            if (typeof Livewire !== 'undefined') {
                log('[Mortality Chart Debug] Calling Livewire refreshMortalityChartData method');
                
                // Use wire:click equivalent for programmatic call
                @this.call('refreshMortalityChartData').then(() => {
                    log('[Mortality Chart Debug] Livewire refresh method completed');
                    if (typeof toastr !== 'undefined') {
                        toastr.success('Chart data refreshed');
                    }
                }).catch(error => {
                    console.error('[Mortality Chart Debug] Livewire refresh method failed:', error);
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Failed to refresh chart data');
                    }
                });
            } else {
                console.error('[Mortality Chart Debug] Livewire not available');
                if (typeof toastr !== 'undefined') {
                    toastr.error('Livewire not available');
                }
            }
        };

        // Initial chart initialization on page load
        document.addEventListener('DOMContentLoaded', function() {
            log('[Analytics Debug] DOM Content Loaded - checking initial tab');

            // Check if mortality tab is initially active
            setTimeout(() => {
                const mortalityTab = document.querySelector(
                    '.nav-link[wire\\:click\\.prevent*="mortality"]');
                if (mortalityTab && mortalityTab.classList.contains('active')) {
                    log('[Analytics Debug] Mortality tab initially active, initializing chart');
                    testChartJsLoading(); // Use comprehensive test instead
                }
            }, 1000); // Increased delay to ensure all resources loaded
        });

        // Add window load event as backup
        window.addEventListener('load', function() {
            log('[Analytics Debug] Window fully loaded');
            setTimeout(() => {
                const mortalityTab = document.querySelector(
                    '.nav-link[wire\\:click\\.prevent*="mortality"]');
                if (mortalityTab && mortalityTab.classList.contains('active')) {
                    log('[Analytics Debug] Window load: Mortality tab active, initializing chart');
                    testChartJsLoading();
                }
            }, 500);
        });

        // Manual tab click handler for mortality
        document.addEventListener('click', function(e) {
            const tabLink = e.target.closest('.nav-link[wire\\:click\\.prevent*="mortality"]');
            if (tabLink) {
                log('[Analytics Debug] Manual mortality tab click detected');
                setTimeout(() => {
                    testChartJsLoading(); // Use comprehensive test
                }, 300);
            }
        });



        // Enhanced chart type selector handler (remove old method and use Livewire instead)
        function handleMortalityChartTypeChange() {
            log('[Mortality Chart Debug] Chart type handler setup - using Livewire model binding');
            // No need for manual event listeners since we're using wire:model.live
        }

        // Override the old chart type change handler to use Livewire
        function handleChartTypeChange(newType) {
            log('[Mortality Chart Debug] Chart type changed via JavaScript to:', newType);

            if (window.advancedMortalityChart && window.advancedMortalityChart.config) {
                log('[Mortality Chart Debug] Updating existing chart type from', window.advancedMortalityChart
                    .config.type, 'to', newType);

                // Only update if the type is different
                if (window.advancedMortalityChart.config.type !== newType) {
                    try {
                        // For Chart.js, we need to destroy and recreate for type changes
                        log('[Mortality Chart Debug] Recreating chart with new type');
                        initializeAdvancedMortalityChart();
                    } catch (error) {
                        console.error('[Mortality Chart Debug] Error updating chart type:', error);
                    }
                }
            } else {
                log('[Mortality Chart Debug] No existing chart, initializing new one');
                initializeAdvancedMortalityChart();
            }
        }

        // Global debug functions (enhanced)
        window.debugMortalityChart = function() {
            log('[Mortality Chart Debug] ========== ENHANCED CHART STATE DEBUG ==========');

            const debugInfo = {
                chartJs: {
                    available: typeof Chart !== 'undefined',
                    version: typeof Chart !== 'undefined' ? Chart.version : 'N/A'
                },
                elements: {
                    canvas: !!document.getElementById('advancedMortalityChart'),
                    container: !!document.getElementById('mortalityChartContainer'),
                    noData: !!document.getElementById('mortalityChartNoData'),
                    title: !!document.getElementById('mortalityChartTitle'),
                    chartTypeSelector: !!document.querySelector('[wire\\:model\\.live="chartType"]'),
                    viewTypeSelector: !!document.querySelector('[wire\\:model\\.live="viewType"]')
                },
                chartInstance: {
                    exists: !!window.advancedMortalityChart,
                    id: window.advancedMortalityChart?.id,
                    type: window.advancedMortalityChart?.config?.type
                },
                livewireState: {
                    components: Object.keys(Livewire.all()),
                    componentCount: Object.keys(Livewire.all()).length
                },
                tab: {
                    mortalityTabActive: document.querySelector('.nav-link[wire\\:click\\.prevent*="mortality"]')
                        ?.classList.contains('active')
                }
            };

            log('[Mortality Chart Debug] Enhanced debug info:', debugInfo);

            // Show debug info in UI
            const debugPanel = document.getElementById('mortalityChartDebug');
            const debugOutput = document.getElementById('debugOutput');

            if (debugPanel && debugOutput) {
                debugPanel.classList.remove('d-none');
                debugOutput.innerHTML = `
                <strong>Chart.js:</strong> ${debugInfo.chartJs.available ? '✅ Loaded (v' + debugInfo.chartJs.version + ')' : '❌ Not loaded'}<br>
                <strong>Canvas:</strong> ${debugInfo.elements.canvas ? '✅ Found' : '❌ Missing'}<br>
                <strong>Chart Instance:</strong> ${debugInfo.chartInstance.exists ? '✅ Created (' + debugInfo.chartInstance.type + ')' : '❌ Not created'}<br>
                <strong>Livewire:</strong> ${debugInfo.livewireState.componentCount} components<br>
                <strong>Chart Type Selector:</strong> ${debugInfo.elements.chartTypeSelector ? '✅ Found' : '❌ Missing'}<br>
                <strong>View Type Selector:</strong> ${debugInfo.elements.viewTypeSelector ? '✅ Found' : '❌ Missing'}<br>
                <strong>Tab Active:</strong> ${debugInfo.tab.mortalityTabActive ? '✅ Mortality tab active' : '❌ Not on mortality tab'}<br>
                <button class="btn btn-sm btn-secondary mt-2" onclick="document.getElementById('mortalityChartDebug').classList.add('d-none')">Hide Debug</button>
            `;
            }

            // Test Chart.js functionality
            if (typeof Chart !== 'undefined') {
                try {
                    const testCanvas = document.createElement('canvas');
                    testCanvas.width = 100;
                    testCanvas.height = 100;
                    const testCtx = testCanvas.getContext('2d');
                    const testChart = new Chart(testCtx, {
                        type: 'line',
                        data: {
                            labels: ['Test'],
                            datasets: [{
                                label: 'Test',
                                data: [1]
                            }]
                        }
                    });
                    testChart.destroy();
                    log('[Mortality Chart Debug] ✅ Chart.js functionality test successful');
                } catch (error) {
                    console.error('[Mortality Chart Debug] ❌ Chart.js functionality test failed:', error);
                }
            }

            return debugInfo;
        };

        window.forceInitializeMortalityChart = function() {
            log('[Mortality Chart Debug] FORCE INITIALIZATION REQUESTED');

            // Wait for a moment to ensure DOM is ready
            setTimeout(() => {
                log('[Mortality Chart Debug] Starting forced initialization');

                // Ensure we're on mortality tab
                const mortalityTab = document.querySelector('.nav-link[wire\\:click\\.prevent*="mortality"]');
                if (mortalityTab && !mortalityTab.classList.contains('active')) {
                    log('[Mortality Chart Debug] Switching to mortality tab first');
                    mortalityTab.click();

                    // Wait for tab switch then initialize
                    setTimeout(() => {
                        initializeAdvancedMortalityChart();
                    }, 500);
                } else {
                    initializeAdvancedMortalityChart();
                }
            }, 100);
        };

        // Update mortality statistics based on visible datasets
        window.updateMortalityStats = function(chart) {
            if (!chart) return;
            
            const visibleDatasets = chart.data.datasets.filter((dataset, index) => 
                chart.isDatasetVisible(index)
            );
            
            let totalDeaths = 0;
            let totalDays = 0;
            let maxDailyDeaths = 0;
            let activeSources = visibleDatasets.length;
            
            visibleDatasets.forEach(dataset => {
                dataset.data.forEach(value => {
                    totalDeaths += value;
                    if (value > 0) totalDays++;
                    if (value > maxDailyDeaths) maxDailyDeaths = value;
                });
            });
            
            const avgDeaths = totalDays > 0 ? (totalDeaths / totalDays).toFixed(1) : 0;
            
            // Update statistics cards if they exist
            const statsCards = document.querySelectorAll('.mortality-stat-card');
            if (statsCards.length >= 3) {
                // Total Deaths
                const totalCard = statsCards[0]?.querySelector('.mortality-stat-number');
                if (totalCard) totalCard.textContent = totalDeaths.toLocaleString();
                
                // Active Sources (Farms/Coops/Livestock)
                const activeCard = statsCards[1]?.querySelector('.mortality-stat-number');
                if (activeCard) activeCard.textContent = activeSources;
                
                // Avg Deaths/Day
                const avgCard = statsCards[2]?.querySelector('.mortality-stat-number');
                if (avgCard) avgCard.textContent = avgDeaths;
                
                // Max Daily Deaths (if 4th card exists)
                if (statsCards[3]) {
                    const maxCard = statsCards[3].querySelector('.mortality-stat-number');
                    if (maxCard) maxCard.textContent = maxDailyDeaths;
                }
            }
            
            log('[Mortality Stats] Updated statistics:', {
                totalDeaths,
                activeSources,
                avgDeaths,
                maxDailyDeaths
            });
        };

        // Check required DOM elements for mortality chart
        function checkMortalityChartElements() {
            const elements = {
                canvas: document.getElementById('advancedMortalityChart'),
                container: document.getElementById('mortalityChartContainer'),
                noData: document.getElementById('mortalityChartNoData'),
                title: document.getElementById('mortalityChartTitle'),
                selector: document.getElementById('mortalityChartType')
            };

            log('[Mortality Chart Debug] Required elements check:', {
                canvas: !!elements.canvas,
                container: !!elements.container,
                noData: !!elements.noData,
                title: !!elements.title,
                selector: !!elements.selector
            });

            return elements;
        }





        log('[Analytics Debug] Livewire initialized');
</script>
@endpush
</rewritten_file>