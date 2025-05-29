<div>
    @if ($showForm)
    <div class="card" id="qaForm">
        <div class="card-header">
            <h3 class="card-title">QA Checklist Form</h3>
            <div class="card-toolbar">
                {{-- <button wire:click="exportToJson" class="btn btn-sm btn-light-primary me-2">
                    <i class="ki-duotone ki-file-down fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Export JSON
                </button>
                <button wire:click="exportToTxt" class="btn btn-sm btn-light-primary">
                    <i class="ki-duotone ki-file-down fs-2">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                    Export TXT
                </button> --}}
            </div>
        </div>
        <div class="card-body">
            @if (session()->has('message'))
            <div class="alert alert-success">
                {{ session('message') }}
            </div>
            @endif

            <form wire:submit.prevent="save">
                <div class="row mb-5">
                    <div class="col-md-6">
                        <label class="form-label required">Feature Name</label>
                        <input type="text" class="form-control" wire:model="feature_name"
                            placeholder="Enter feature name">
                        @error('feature_name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Feature Category</label>
                        <select class="form-select" wire:model.live="feature_category">
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                            <option value="{{ $category }}">{{ $category }}</option>
                            @endforeach
                        </select>
                        @error('feature_category') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-md-6">
                        <label class="form-label">Feature Subcategory</label>
                        <select class="form-select" wire:model.live="feature_subcategory" @if(!$feature_category)
                            disabled @endif>
                            <option value="">Select Subcategory</option>
                            @if($feature_category && !empty($subcategories))
                            @foreach($subcategories as $subcategory)
                            <option value="{{ $subcategory }}">{{ $subcategory }}</option>
                            @endforeach
                            @endif
                        </select>
                        @error('feature_subcategory') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label required">Test Type</label>
                        <select class="form-select" wire:model="test_type">
                            <option value="">Select Type</option>
                            <option value="CRUD">CRUD</option>
                            <option value="UI/UX">UI/UX</option>
                            <option value="Functionality">Functionality</option>
                            <option value="Performance">Performance</option>
                            <option value="Security">Security</option>
                            <option value="Data Validation">Data Validation</option>
                            <option value="Error Handling">Error Handling</option>
                            <option value="Integration">Integration</option>
                            <option value="Business Logic">Business Logic</option>
                        </select>
                        @error('test_type') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-md-12">
                        <label class="form-label required">Test Case</label>
                        <textarea class="form-control" wire:model="test_case" rows="3"
                            placeholder="Contoh: Verifikasi bahwa user dapat menambahkan data ternak baru dengan mengisi semua field yang diperlukan"></textarea>
                        @error('test_case') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-md-12">
                        <label class="form-label">URL</label>
                        <input type="text" class="form-control" wire:model="url"
                            placeholder="Enter URL or path (optional)">
                        @error('url') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-md-12">
                        <label class="form-label">Test Steps</label>
                        <textarea class="form-control" wire:model="test_steps" rows="3" placeholder="Contoh:
1. Login ke aplikasi sebagai admin
2. Klik menu 'Ternak'
3. Klik tombol 'Tambah Ternak'
4. Isi form dengan data:
   - Nama Ternak: Sapi 001
   - Jenis: Sapi Perah
   - Tanggal Lahir: 01/01/2023
   - Berat: 500 kg
5. Klik tombol 'Simpan'"></textarea>
                        @error('test_steps') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-md-12">
                        <label class="form-label">Expected Result</label>
                        <textarea class="form-control" wire:model="expected_result" rows="3" placeholder="Contoh:
1. Data ternak berhasil disimpan
2. Muncul notifikasi sukses
3. Data ternak baru muncul di daftar ternak
4. Semua field terisi sesuai input"></textarea>
                        @error('expected_result') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-md-3">
                        <label class="form-label required">Priority</label>
                        <select class="form-select" wire:model="priority">
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                            <option value="Critical">Critical</option>
                        </select>
                        @error('priority') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Status</label>
                        <select class="form-select" wire:model="status">
                            <option value="Not Tested">Not Tested</option>
                            <option value="Passed">Passed</option>
                            <option value="Failed">Failed</option>
                            <option value="Blocked">Blocked</option>
                        </select>
                        @error('status') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Environment</label>
                        <select class="form-select" wire:model="environment">
                            <option value="Development">Development</option>
                            <option value="Staging">Staging</option>
                            <option value="Production">Production</option>
                        </select>
                        @error('environment') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Test Date</label>
                        <input type="date" class="form-control" wire:model="test_date">
                        @error('test_date') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-md-4">
                        <label class="form-label required">Tester Name</label>
                        <input type="text" class="form-control" wire:model="tester_name"
                            placeholder="Enter tester name">
                        @error('tester_name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Browser</label>
                        <input type="text" class="form-control" wire:model="browser" placeholder="e.g., Chrome 120">
                        @error('browser') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Device</label>
                        <input type="text" class="form-control" wire:model="device"
                            placeholder="e.g., Desktop, iPhone 12">
                        @error('device') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-md-6">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" wire:model="notes" rows="3"
                            placeholder="Additional notes"></textarea>
                        @error('notes') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Error Details</label>
                        <textarea class="form-control" wire:model="error_details" rows="3"
                            placeholder="Error details if test failed"></textarea>
                        @error('error_details') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="text-end">
                    <button type="button" class="btn btn-light me-2" wire:click="cancelEdit">
                        Batal
                    </button>

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

    {{-- @else

    <div class="card mt-5">
        <div class="card-header">
            <h3 class="card-title">QA Checklist Results</h3>
            <div class="card-toolbar">
                <button type="button" class="btn btn-primary ms-2" id="show-qa-form"
                    onclick="Livewire.dispatch('showQaForm')">
                    <i class="fa fa-plus"></i> Add New QA
                </button>

                <div class="d-flex align-items-center position-relative my-1">
                    <input type="text" wire:model.live="search" class="form-control form-control-solid w-250px ps-13"
                        placeholder="Search..." />
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="qa-checklist-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Feature</th>
                            <th>Category</th>
                            <th>Subcategory</th>
                            <th>Type</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Tester</th>
                            <th>Date</th>
                            <th>URL</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                    <tbody>
                        @foreach($checklists as $checklist)
                        <tr>
                            <td>{{ $checklist->feature_name }}</td>
                            <td>{{ $checklist->feature_category }}</td>
                            <td>{{ $checklist->feature_subcategory }}</td>
                            <td>{{ $checklist->test_type }}</td>
                            <td>
                                <span class="badge bg-{{ 
                                        $checklist->priority === 'Critical' ? 'danger' : 
                                        ($checklist->priority === 'High' ? 'warning' : 
                                        ($checklist->priority === 'Medium' ? 'info' : 'success')) 
                                    }}">
                                    {{ $checklist->priority }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ 
                                        $checklist->status === 'Passed' ? 'success' : 
                                        ($checklist->status === 'Failed' ? 'danger' : 
                                        ($checklist->status === 'Blocked' ? 'dark' : 'warning')) 
                                    }}">
                                    {{ $checklist->status }}
                                </span>
                            </td>
                            <td>{{ $checklist->tester_name }}</td>
                            <td>{{ $checklist->test_date->format('Y-m-d') }}</td>
                            <td>
                                @if($checklist->url)
                                <a href="{{ $checklist->url }}" target="_blank" class="btn btn-sm btn-info">
                                    Go to URL
                                </a>
                                @else
                                N/A
                                @endif
                            </td>
                            <td>
                                <button wire:click="edit({{ $checklist->id }})" class="btn btn-sm btn-primary me-2">
                                    <i class="ki-duotone ki-pencil fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </button>
                                <button wire:click="delete({{ $checklist->id }})" class="btn btn-sm btn-danger"
                                    onclick="confirm('Are you sure?') || event.stopImmediatePropagation()">
                                    <i class="ki-duotone ki-trash fs-2">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                        <span class="path5"></span>
                                    </i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $checklists->links() }}
            </div>
        </div>
    </div> --}}
    @endif

</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        // Get browser info
        const browserInfo = navigator.userAgent;
        const browser = browserInfo.includes('Chrome') ? 'Chrome' :
                       browserInfo.includes('Firefox') ? 'Firefox' :
                       browserInfo.includes('Safari') ? 'Safari' :
                       browserInfo.includes('Edge') ? 'Edge' : 'Unknown';
        
        // Get device info
        const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
        const device = isMobile ? 'Mobile' : 'Desktop';
        
        // Set browser and device info
        @this.set('browser', browser);
        @this.set('device', device);
    });
</script>

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

<script>
    $(document).ready(function() {
        $('#qa-checklist-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route('administrator.qa.index') }}', // Ensure this route is defined
            columns: [
                { data: 'feature_name', name: 'feature_name' },
                { data: 'feature_category', name: 'feature_category' },
                { data: 'feature_subcategory', name: 'feature_subcategory' },
                { data: 'test_type', name: 'test_type' },
                { data: 'priority', name: 'priority' },
                { data: 'status', name: 'status' },
                { data: 'tester_name', name: 'tester_name' },
                { data: 'test_date', name: 'test_date' },
                { data: 'url', name: 'url' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ]
        });
    });
</script>
@endpush