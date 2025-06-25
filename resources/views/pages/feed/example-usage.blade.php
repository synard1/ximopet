@extends('layouts.app')

@section('title', 'Manual Feed Usage - Example')

@section('content')
<div class="d-flex flex-column flex-column-fluid">
    <!--begin::Toolbar-->
    <div id="kt_app_toolbar" class="app-toolbar py-3 py-lg-6">
        <div id="kt_app_toolbar_container" class="app-container container-xxl d-flex flex-stack">
            <div class="page-title d-flex flex-column justify-content-center flex-wrap me-3">
                <h1 class="page-heading d-flex text-dark fw-bold fs-3 flex-column justify-content-center my-0">
                    Manual Feed Usage Example
                </h1>
                <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-0 pt-1">
                    <li class="breadcrumb-item text-muted">
                        <a href="#" class="text-muted text-hover-primary">Feed Management</a>
                    </li>
                    <li class="breadcrumb-item">
                        <span class="bullet bg-gray-400 w-5px h-2px"></span>
                    </li>
                    <li class="breadcrumb-item text-muted">Manual Feed Usage</li>
                </ul>
            </div>
        </div>
    </div>
    <!--end::Toolbar-->

    <!--begin::Content-->
    <div id="kt_app_content" class="app-content flex-column-fluid">
        <div id="kt_app_content_container" class="app-container container-xxl">

            <!-- Example Usage Section -->
            <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
                <!-- Livestock Selection Card -->
                <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-md-5 mb-xl-10">
                    <div class="card h-md-50 mb-5 mb-xl-10">
                        <div class="card-body d-flex flex-column flex-center">
                            <div class="mb-2">
                                <h1 class="fw-semibold text-gray-800 text-center lh-lg">
                                    <span class="fw-bolder">Livestock-First</span>
                                    <br>
                                    <span class="text-gray-400 fw-bold">Approach</span>
                                </h1>
                                <div class="py-10 text-center">
                                    <img src="{{ asset('media/illustrations/sketchy-1/2.png') }}"
                                        class="theme-light-show w-200px" alt="">
                                </div>
                            </div>
                            <div class="text-center mb-1">
                                <a href="#livestock-list" class="btn btn-sm btn-primary me-2" id="show-livestock-list">
                                    Select Livestock
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Usage Statistics -->
                <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-md-5 mb-xl-10">
                    <div class="card h-md-50 mb-5 mb-xl-10 bg-light">
                        <div class="card-body d-flex flex-column flex-center">
                            <div class="mb-2">
                                <div class="py-10 text-center">
                                    <i class="ki-duotone ki-chart-simple fs-5x text-primary">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                    </i>
                                </div>
                                <h1 class="fw-semibold text-gray-800 text-center lh-lg">
                                    <span class="fw-bolder">Usage</span>
                                    <br>
                                    <span class="text-gray-400 fw-bold">Statistics</span>
                                </h1>
                            </div>
                            <div class="text-center mb-1">
                                <div class="d-flex flex-center">
                                    <span class="badge badge-light-primary fs-base">
                                        <i class="ki-duotone ki-arrow-up fs-5 text-primary ms-n1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        Real-time tracking
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feed Stocks Info -->
                <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-md-5 mb-xl-10">
                    <div class="card h-md-50 mb-5 mb-xl-10 bg-light">
                        <div class="card-body d-flex flex-column flex-center">
                            <div class="mb-2">
                                <div class="py-10 text-center">
                                    <i class="ki-duotone ki-package fs-5x text-success">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                </div>
                                <h1 class="fw-semibold text-gray-800 text-center lh-lg">
                                    <span class="fw-bolder">Feed</span>
                                    <br>
                                    <span class="text-gray-400 fw-bold">Stocks</span>
                                </h1>
                            </div>
                            <div class="text-center mb-1">
                                <div class="d-flex flex-center">
                                    <span class="badge badge-light-success fs-base">
                                        <i class="ki-duotone ki-check fs-5 text-success ms-n1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        Auto-calculated
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cost Tracking -->
                <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-md-5 mb-xl-10">
                    <div class="card h-md-50 mb-5 mb-xl-10 bg-light">
                        <div class="card-body d-flex flex-column flex-center">
                            <div class="mb-2">
                                <div class="py-10 text-center">
                                    <i class="ki-duotone ki-dollar fs-5x text-warning">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                    </i>
                                </div>
                                <h1 class="fw-semibold text-gray-800 text-center lh-lg">
                                    <span class="fw-bolder">Cost</span>
                                    <br>
                                    <span class="text-gray-400 fw-bold">Tracking</span>
                                </h1>
                            </div>
                            <div class="text-center mb-1">
                                <div class="d-flex flex-center">
                                    <span class="badge badge-light-warning fs-base">
                                        <i class="ki-duotone ki-chart-line-up fs-5 text-warning ms-n1">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                        </i>
                                        Precise costing
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Livestock List -->
            <div class="card" id="livestock-list">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-5">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <input type="text" data-kt-livestock-table-filter="search"
                                class="form-control form-control-solid w-250px ps-13" placeholder="Search livestock...">
                        </div>
                    </div>
                    <div class="card-toolbar">
                        <div class="d-flex justify-content-end" data-kt-livestock-table-toolbar="base">
                            <button type="button" class="btn btn-light-primary me-3" data-kt-menu-trigger="click"
                                data-kt-menu-placement="bottom-end">
                                <i class="ki-duotone ki-filter fs-2">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                                Filter
                            </button>
                            <div class="menu menu-sub menu-sub-dropdown w-300px w-md-325px" data-kt-menu="true">
                                <div class="px-7 py-5">
                                    <div class="fs-5 text-dark fw-bold">Filter Options</div>
                                </div>
                                <div class="separator border-gray-200"></div>
                                <div class="px-7 py-5" data-kt-livestock-table-filter="form">
                                    <div class="mb-10">
                                        <label class="form-label fs-6 fw-semibold">Status:</label>
                                        <select class="form-select form-select-solid fw-bold" data-kt-select2="true"
                                            data-placeholder="Select option" data-allow-clear="true"
                                            data-kt-livestock-table-filter="status" data-hide-search="true">
                                            <option></option>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="reset"
                                            class="btn btn-light btn-active-light-primary fw-semibold me-2 px-6"
                                            data-kt-menu-dismiss="true"
                                            data-kt-livestock-table-filter="reset">Reset</button>
                                        <button type="submit" class="btn btn-primary fw-semibold px-6"
                                            data-kt-menu-dismiss="true"
                                            data-kt-livestock-table-filter="filter">Apply</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body py-4">
                    <div class="table-responsive">
                        <table class="table table-rounded table-striped border gy-7 gs-7" id="kt_livestock_table">
                            <thead>
                                <tr class="fw-semibold fs-6 text-gray-800 border-bottom-2 border-gray-200">
                                    <th class="min-w-125px">Livestock</th>
                                    <th class="min-w-125px">Type</th>
                                    <th class="min-w-125px">Farm</th>
                                    <th class="min-w-125px">Available Feeds</th>
                                    <th class="min-w-125px">Status</th>
                                    <th class="text-end min-w-100px">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Sample livestock data -->
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-45px me-5">
                                                <img src="{{ asset('media/avatars/livestock-1.jpg') }}" class="h-auto"
                                                    alt="">
                                            </div>
                                            <div class="d-flex justify-content-start flex-column">
                                                <a href="#" class="text-dark fw-bold text-hover-primary fs-6">Livestock
                                                    Batch A</a>
                                                <span class="text-muted fw-semibold text-muted d-block fs-7">Broiler
                                                    Chickens</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-primary">Broiler</span>
                                    </td>
                                    <td>
                                        <span class="text-dark fw-bold d-block fs-6">Farm Central</span>
                                        <span class="text-muted fw-semibold d-block fs-7">Coop 1</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-dark fw-bold fs-6">3 Feed Types</span>
                                            <span class="text-muted fw-semibold fs-7">8 Stock Batches</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-success">Active</span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-primary"
                                            onclick="showManualFeedUsageModal('livestock-1')">
                                            <i class="ki-duotone ki-nutrition fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                                <span class="path4"></span>
                                                <span class="path5"></span>
                                                <span class="path6"></span>
                                                <span class="path7"></span>
                                                <span class="path8"></span>
                                                <span class="path9"></span>
                                                <span class="path10"></span>
                                                <span class="path11"></span>
                                                <span class="path12"></span>
                                            </i>
                                            Feed Usage
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-45px me-5">
                                                <img src="{{ asset('media/avatars/livestock-2.jpg') }}" class="h-auto"
                                                    alt="">
                                            </div>
                                            <div class="d-flex justify-content-start flex-column">
                                                <a href="#" class="text-dark fw-bold text-hover-primary fs-6">Livestock
                                                    Batch B</a>
                                                <span class="text-muted fw-semibold text-muted d-block fs-7">Layer
                                                    Chickens</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-info">Layer</span>
                                    </td>
                                    <td>
                                        <span class="text-dark fw-bold d-block fs-6">Farm Central</span>
                                        <span class="text-muted fw-semibold d-block fs-7">Coop 2</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-dark fw-bold fs-6">2 Feed Types</span>
                                            <span class="text-muted fw-semibold fs-7">5 Stock Batches</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-success">Active</span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-primary"
                                            onclick="showManualFeedUsageModal('livestock-2')">
                                            <i class="ki-duotone ki-nutrition fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                                <span class="path4"></span>
                                                <span class="path5"></span>
                                                <span class="path6"></span>
                                                <span class="path7"></span>
                                                <span class="path8"></span>
                                                <span class="path9"></span>
                                                <span class="path10"></span>
                                                <span class="path11"></span>
                                                <span class="path12"></span>
                                            </i>
                                            Feed Usage
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="symbol symbol-45px me-5">
                                                <img src="{{ asset('media/avatars/livestock-3.jpg') }}" class="h-auto"
                                                    alt="">
                                            </div>
                                            <div class="d-flex justify-content-start flex-column">
                                                <a href="#" class="text-dark fw-bold text-hover-primary fs-6">Livestock
                                                    Batch C</a>
                                                <span class="text-muted fw-semibold text-muted d-block fs-7">Free
                                                    Range</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-warning">Free Range</span>
                                    </td>
                                    <td>
                                        <span class="text-dark fw-bold d-block fs-6">Farm East</span>
                                        <span class="text-muted fw-semibold d-block fs-7">Outdoor Area</span>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-dark fw-bold fs-6">4 Feed Types</span>
                                            <span class="text-muted fw-semibold fs-7">12 Stock Batches</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-light-success">Active</span>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-primary"
                                            onclick="showManualFeedUsageModal('livestock-3')">
                                            <i class="ki-duotone ki-nutrition fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                                <span class="path4"></span>
                                                <span class="path5"></span>
                                                <span class="path6"></span>
                                                <span class="path7"></span>
                                                <span class="path8"></span>
                                                <span class="path9"></span>
                                                <span class="path10"></span>
                                                <span class="path11"></span>
                                                <span class="path12"></span>
                                            </i>
                                            Feed Usage
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Usage History -->
            <div class="card mt-5">
                <div class="card-header border-0 pt-6">
                    <div class="card-title">
                        <div class="d-flex align-items-center position-relative my-1">
                            <i class="ki-duotone ki-calendar fs-1 position-absolute ms-6">
                                <span class="path1"></span>
                                <span class="path2"></span>
                            </i>
                            <h3 class="fw-bold ms-15 m-0">Recent Feed Usage History</h3>
                        </div>
                    </div>
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-light-primary">
                            <i class="ki-duotone ki-plus fs-2"></i>
                            View All
                        </button>
                    </div>
                </div>
                <div class="card-body py-4">
                    <div class="timeline-label">
                        <!-- Timeline item -->
                        <div class="timeline-item">
                            <div class="timeline-label fw-bold text-gray-800 fs-6">10:00</div>
                            <div class="timeline-badge">
                                <i class="ki-duotone ki-abstract-8 text-gray-600 fs-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                            <div class="fw-mormal timeline-content text-muted ps-3">
                                <span class="fw-bold text-gray-800">Livestock Batch A</span> used
                                <span class="fw-bold text-primary">25.5 kg</span> of Starter Feed
                                <span class="text-muted">- Cost: Rp 127,500</span>
                            </div>
                        </div>
                        <!-- Timeline item -->
                        <div class="timeline-item">
                            <div class="timeline-label fw-bold text-gray-800 fs-6">14:30</div>
                            <div class="timeline-badge">
                                <i class="ki-duotone ki-abstract-8 text-gray-600 fs-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                            <div class="fw-mormal timeline-content text-muted ps-3">
                                <span class="fw-bold text-gray-800">Livestock Batch B</span> used
                                <span class="fw-bold text-primary">18.2 kg</span> of Layer Feed
                                <span class="text-muted">- Cost: Rp 91,000</span>
                            </div>
                        </div>
                        <!-- Timeline item -->
                        <div class="timeline-item">
                            <div class="timeline-label fw-bold text-gray-800 fs-6">16:45</div>
                            <div class="timeline-badge">
                                <i class="ki-duotone ki-abstract-8 text-gray-600 fs-3">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                            <div class="fw-mormal timeline-content text-muted ps-3">
                                <span class="fw-bold text-gray-800">Livestock Batch C</span> used
                                <span class="fw-bold text-primary">32.1 kg</span> of Organic Feed
                                <span class="text-muted">- Cost: Rp 192,600</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--end::Content-->
</div>

<!-- Include Manual Feed Usage Component -->
@livewire('feed-usages.manual-feed-usage')
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Smooth scroll to livestock list
        document.getElementById('show-livestock-list').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('livestock-list').scrollIntoView({
                behavior: 'smooth'
            });
        });

        // Handle feed usage completion
        window.addEventListener('feed-usage-completed', function(event) {
            console.log('Feed usage completed:', event.detail);
            
            // Show success notification
            if (typeof toastr !== 'undefined') {
                toastr.success(`Feed usage completed successfully! Quantity: ${event.detail.total_quantity}, Cost: Rp ${event.detail.total_cost.toLocaleString()}`);
            }
            
            // Optionally refresh the page or update UI
            // You can add logic here to update livestock statistics, refresh tables, etc.
        });

        // Initialize DataTable if needed
        if (typeof KTDatatablesServerSide !== 'undefined') {
            // Initialize DataTable for livestock
        }

        // Search functionality
        const searchInput = document.querySelector('[data-kt-livestock-table-filter="search"]');
        if (searchInput) {
            searchInput.addEventListener('keyup', function(e) {
                // Implement search functionality
                console.log('Searching for:', e.target.value);
            });
        }
    });

    // Function to show manual feed usage modal (called from buttons)
    function showManualFeedUsageModal(livestockId, feedId = null) {
        console.log('Opening manual feed usage modal for livestock:', livestockId, 'feed:', feedId);
        
        // Dispatch Livewire event to show modal
        Livewire.dispatch('show-manual-feed-usage', {
            livestock_id: livestockId,
            feed_id: feedId
        });
    }
</script>
@endpush

@push('styles')
<style>
    .timeline-label {
        position: relative;
    }

    .timeline-item {
        display: flex;
        position: relative;
        margin-bottom: 3rem;
    }

    .timeline-item:not(:last-child):before {
        content: '';
        position: absolute;
        left: 2rem;
        top: 2rem;
        height: calc(100% + 1.5rem);
        border-left: 1px dashed var(--bs-gray-300);
    }

    .timeline-label {
        width: 6rem;
        text-align: right;
        margin-right: 1rem;
    }

    .timeline-badge {
        width: 4rem;
        height: 4rem;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        background: var(--bs-white);
        border: 3px solid var(--bs-gray-300);
        border-radius: 50%;
        z-index: 1;
    }

    .timeline-content {
        flex: 1;
        margin-left: 1rem;
        padding-top: 0.5rem;
    }

    .symbol img {
        object-fit: cover;
        border-radius: 50%;
    }
</style>
@endpush