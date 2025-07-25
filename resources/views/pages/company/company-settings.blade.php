<!-- Supply Usage Bypass Configuration -->
<div class="card mb-5 mb-xl-10">
    <div class="card-header cursor-pointer">
        <div class="card-title m-0">
            <h3 class="fw-bold m-0">Supply Usage Bypass Configuration</h3>
        </div>
        <div class="card-toolbar">
            <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="collapse"
                data-bs-target="#supplyUsageBypassConfig">
                <i class="ki-duotone ki-down fs-2"></i>
            </button>
        </div>
    </div>
    <div id="supplyUsageBypassConfig" class="collapse show">
        <div class="card-body">
            <div class="row g-5 g-xl-10 mb-5 mb-xl-10">
                <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-md-5 mb-xl-10">
                    <div class="card card-flush h-md-50 mb-5 mb-xl-10">
                        <div class="card-header pt-5">
                            <div class="card-title d-flex flex-column">
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-dark me-2 lh-1 ls-n2">Direct Transitions</span>
                                </div>
                                <span class="text-gray-400 pt-1 fw-semibold fs-6">Allow direct status changes</span>
                            </div>
                        </div>
                        <div class="card-body pt-2 pb-4 d-flex align-items-center">
                            <div class="d-flex flex-column content-justify-center flex-grow-1">
                                <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                                    <input class="form-check-input" type="checkbox" value="1"
                                        id="allow_direct_to_in_process"
                                        wire:model.live="config.allow_direct_to_in_process" />
                                    <label class="form-check-label" for="allow_direct_to_in_process">
                                        Draft → In Process
                                    </label>
                                </div>
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1"
                                        id="allow_direct_to_completed"
                                        wire:model.live="config.allow_direct_to_completed" />
                                    <label class="form-check-label" for="allow_direct_to_completed">
                                        Draft → Completed
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-md-5 mb-xl-10">
                    <div class="card card-flush h-md-50 mb-5 mb-xl-10">
                        <div class="card-header pt-5">
                            <div class="card-title d-flex flex-column">
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-dark me-2 lh-1 ls-n2">Role Bypass</span>
                                </div>
                                <span class="text-gray-400 pt-1 fw-semibold fs-6">Skip approval for roles</span>
                            </div>
                        </div>
                        <div class="card-body pt-2 pb-4 d-flex align-items-center">
                            <div class="d-flex flex-column content-justify-center flex-grow-1">
                                <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                                    <input class="form-check-input" type="checkbox" value="1"
                                        id="skip_approval_for_operator"
                                        wire:model.live="config.skip_approval_for_operator" />
                                    <label class="form-check-label" for="skip_approval_for_operator">
                                        Operator
                                    </label>
                                </div>
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1"
                                        id="skip_approval_for_supervisor"
                                        wire:model.live="config.skip_approval_for_supervisor" />
                                    <label class="form-check-label" for="skip_approval_for_supervisor">
                                        Supervisor
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-md-5 mb-xl-10">
                    <div class="card card-flush h-md-50 mb-5 mb-xl-10">
                        <div class="card-header pt-5">
                            <div class="card-title d-flex flex-column">
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-dark me-2 lh-1 ls-n2">Workflow Settings</span>
                                </div>
                                <span class="text-gray-400 pt-1 fw-semibold fs-6">Additional workflow options</span>
                            </div>
                        </div>
                        <div class="card-body pt-2 pb-4 d-flex align-items-center">
                            <div class="d-flex flex-column content-justify-center flex-grow-1">
                                <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                                    <input class="form-check-input" type="checkbox" value="1"
                                        id="require_notes_for_status_change"
                                        wire:model.live="config.require_notes_for_status_change" />
                                    <label class="form-check-label" for="require_notes_for_status_change">
                                        Require Notes
                                    </label>
                                </div>
                                <div class="form-check form-switch form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="1" id="enable_audit_trail"
                                        wire:model.live="config.enable_audit_trail" />
                                    <label class="form-check-label" for="enable_audit_trail">
                                        Audit Trail
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-6 col-xl-6 col-xxl-3 mb-md-5 mb-xl-10">
                    <div class="card card-flush h-md-50 mb-5 mb-xl-10">
                        <div class="card-header pt-5">
                            <div class="card-title d-flex flex-column">
                                <div class="d-flex align-items-center">
                                    <span class="fs-2hx fw-bold text-dark me-2 lh-1 ls-n2">Actions</span>
                                </div>
                                <span class="text-gray-400 pt-1 fw-semibold fs-6">Configuration management</span>
                            </div>
                        </div>
                        <div class="card-body pt-2 pb-4 d-flex align-items-center">
                            <div class="d-flex flex-column content-justify-center flex-grow-1">
                                <button type="button" class="btn btn-primary mb-3"
                                    wire:click="saveSupplyUsageBypassConfig">
                                    <i class="ki-duotone ki-check fs-2"></i>
                                    Save Configuration
                                </button>
                                <button type="button" class="btn btn-light-warning"
                                    wire:click="resetSupplyUsageBypassConfig">
                                    <i class="ki-duotone ki-refresh fs-2"></i>
                                    Reset to Default
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Role-based Status Changes -->
            <div class="card card-flush mb-5 mb-xl-10">
                <div class="card-header">
                    <h3 class="card-title">Role-based Status Changes</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <h5>Operator</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>From Status</th>
                                            <th>To Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Draft</td>
                                            <td>Pending, Cancelled</td>
                                        </tr>
                                        <tr>
                                            <td>Pending</td>
                                            <td>In Process, Cancelled</td>
                                        </tr>
                                        <tr>
                                            <td>In Process</td>
                                            <td>Completed, Partially Used, Cancelled</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h5>Supervisor</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>From Status</th>
                                            <th>To Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Draft</td>
                                            <td>Pending, In Process, Cancelled</td>
                                        </tr>
                                        <tr>
                                            <td>Pending</td>
                                            <td>In Process, Under Review, Cancelled</td>
                                        </tr>
                                        <tr>
                                            <td>In Process</td>
                                            <td>Completed, Partially Used, Cancelled</td>
                                        </tr>
                                        <tr>
                                            <td>Under Review</td>
                                            <td>In Process, Rejected, Cancelled</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <h5>Manager</h5>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>From Status</th>
                                            <th>To Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Draft</td>
                                            <td>Pending, In Process, Completed, Cancelled</td>
                                        </tr>
                                        <tr>
                                            <td>Pending</td>
                                            <td>In Process, Under Review, Completed, Cancelled</td>
                                        </tr>
                                        <tr>
                                            <td>In Process</td>
                                            <td>Completed, Partially Used, Cancelled</td>
                                        </tr>
                                        <tr>
                                            <td>Under Review</td>
                                            <td>In Process, Rejected, Completed, Cancelled</td>
                                        </tr>
                                        <tr>
                                            <td>Rejected</td>
                                            <td>Draft, Cancelled</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>