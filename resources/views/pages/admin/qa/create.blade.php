<x-default-layout>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Create New QA Checklist</h3>
        </div>
        <div class="card-body">
            <form action="{{ route('administrator.qa.store') }}" method="POST">
                @csrf
                <div class="row mb-5">
                    <div class="col-md-6">
                        <label class="form-label required">Feature Name</label>
                        <input type="text" class="form-control" name="feature_name" placeholder="Enter feature name"
                            value="{{ old('feature_name') }}">
                        @error('feature_name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Feature Category</label>
                        <select class="form-select" name="feature_category">
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                            <option value="{{ $category }}" {{ old('feature_category')==$category ? 'selected' : '' }}>
                                {{ $category }}</option>
                            @endforeach
                        </select>
                        @error('feature_category') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-md-6">
                        <label class="form-label">Feature Subcategory</label>
                        <select class="form-select" name="feature_subcategory" @disabled(!old('feature_category'))
                            @disabled(!old('feature_category'))>
                            <option value="">Select Subcategory</option>
                            @if(old('feature_category') && !empty($subcategories))
                            @foreach($subcategories as $subcategory)
                            <option value="{{ $subcategory }}" {{ old('feature_subcategory')==$subcategory ? 'selected'
                                : '' }}>{{ $subcategory }}</option>
                            @endforeach
                            @endif
                        </select>
                        @error('feature_subcategory') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Test Type</label>
                        <select class="form-select" name="test_type">
                            <option value="">Select Type</option>
                            <option value="CRUD" {{ old('test_type')=='CRUD' ? 'selected' : '' }}>CRUD</option>
                            <option value="UI/UX" {{ old('test_type')=='UI/UX' ? 'selected' : '' }}>UI/UX</option>
                            <option value="Functionality" {{ old('test_type')=='Functionality' ? 'selected' : '' }}>
                                Functionality</option>
                            <option value="Performance" {{ old('test_type')=='Performance' ? 'selected' : '' }}>
                                Performance</option>
                            <option value="Security" {{ old('test_type')=='Security' ? 'selected' : '' }}>Security
                            </option>
                            <option value="Data Validation" {{ old('test_type')=='Data Validation' ? 'selected' : '' }}>
                                Data Validation</option>
                            <option value="Error Handling" {{ old('test_type')=='Error Handling' ? 'selected' : '' }}>
                                Error Handling</option>
                            <option value="Integration" {{ old('test_type')=='Integration' ? 'selected' : '' }}>
                                Integration</option>
                            <option value="Business Logic" {{ old('test_type')=='Business Logic' ? 'selected' : '' }}>
                                Business Logic</option>
                        </select>
                        @error('test_type') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-md-12">
                        <label class="form-label required">Test Case</label>
                        <textarea class="form-control" name="test_case" rows="3"
                            placeholder="Enter test case">{{ old('test_case') }}</textarea>
                        @error('test_case') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-md-12">
                        <label class="form-label">Test Steps</label>
                        <textarea class="form-control" name="test_steps" rows="3"
                            placeholder="Enter test steps">{{ old('test_steps') }}</textarea>
                        @error('test_steps') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-md-12">
                        <label class="form-label">Expected Result</label>
                        <textarea class="form-control" name="expected_result" rows="3"
                            placeholder="Enter expected result">{{ old('expected_result') }}</textarea>
                        @error('expected_result') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-md-3">
                        <label class="form-label required">Priority</label>
                        <select class="form-select" name="priority">
                            <option value="Low" {{ old('priority')=='Low' ? 'selected' : '' }}>Low</option>
                            <option value="Medium" {{ old('priority')=='Medium' ? 'selected' : '' }}>Medium</option>
                            <option value="High" {{ old('priority')=='High' ? 'selected' : '' }}>High</option>
                            <option value="Critical" {{ old('priority')=='Critical' ? 'selected' : '' }}>Critical
                            </option>
                        </select>
                        @error('priority') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Status</label>
                        <select class="form-select" name="status">
                            <option value="Not Tested" {{ old('status')=='Not Tested' ? 'selected' : '' }}>Not Tested
                            </option>
                            <option value="Passed" {{ old('status')=='Passed' ? 'selected' : '' }}>Passed</option>
                            <option value="Failed" {{ old('status')=='Failed' ? 'selected' : '' }}>Failed</option>
                            <option value="Blocked" {{ old('status')=='Blocked' ? 'selected' : '' }}>Blocked</option>
                        </select>
                        @error('status') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Environment</label>
                        <select class="form-select" name="environment">
                            <option value="Development" {{ old('environment')=='Development' ? 'selected' : '' }}>
                                Development</option>
                            <option value="Staging" {{ old('environment')=='Staging' ? 'selected' : '' }}>Staging
                            </option>
                            <option value="Production" {{ old('environment')=='Production' ? 'selected' : '' }}>
                                Production</option>
                        </select>
                        @error('environment') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Test Date</label>
                        <input type="date" class="form-control" name="test_date" value="{{ old('test_date') }}">
                        @error('test_date') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-md-4">
                        <label class="form-label required">Tester Name</label>
                        <input type="text" class="form-control" name="tester_name" placeholder="Enter tester name"
                            value="{{ old('tester_name') }}">
                        @error('tester_name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Browser</label>
                        <input type="text" class="form-control" name="browser" placeholder="e.g., Chrome 120"
                            value="{{ old('browser') }}">
                        @error('browser') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Device</label>
                        <input type="text" class="form-control" name="device" placeholder="e.g., Desktop, iPhone 12"
                            value="{{ old('device') }}">
                        @error('device') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-md-6">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3"
                            placeholder="Additional notes">{{ old('notes') }}</textarea>
                        @error('notes') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Error Details</label>
                        <textarea class="form-control" name="error_details" rows="3"
                            placeholder="Error details if test failed">{{ old('error_details') }}</textarea>
                        @error('error_details') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="ki-duotone ki-check fs-2">
                            <span class="path1"></span>
                            <span class="path2"></span>
                        </i>
                        Save Checklist
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-default-layout>