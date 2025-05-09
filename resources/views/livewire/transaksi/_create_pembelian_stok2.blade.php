<div id="stokFormCard" class="app-content  flex-column-fluid " tabindex="-1" style="display: none;" wire:ignore.self>
    <!--begin::Content container-->
    <div id="kt_app_content_container" class="app-container  container-fluid "
        data-select2-id="select2-data-kt_app_content_container">
        <!--begin::Layout-->
        <div class="d-flex flex-column flex-lg-row" data-select2-id="select2-data-152-7yz0">
            <!--begin::Content-->
            <div class="flex-lg-row-fluid mb-10 mb-lg-0 me-lg-7 me-xl-10">
                <!--begin::Card-->
                <div class="card">
                    <!--begin::Card body-->
                    <div class="card-body p-12">
                        @if($editMode == true)
                            <!--begin::Form-->
                            <form action="" id="kt_pembelian_stok_form">
                                <!--begin::Wrapper-->
                                <div class="d-flex flex-column align-items-start flex-xxl-row">
                                    <!--begin::Input group-->
                                    <div class="d-flex align-items-center flex-equal fw-row me-4 order-2"
                                        data-bs-toggle="tooltip" data-bs-trigger="hover"
                                        data-bs-original-title="Specify invoice date" data-kt-initialized="1">
                                        <!--begin::Date-->
                                        <div class="fs-6 fw-bold text-gray-700 text-nowrap">Tanggal:</div>
                                        <!--end::Date-->

                                        <!--begin::Input-->
                                        <div class="position-relative d-flex align-items-center w-150px">
                                            <!--begin::Datepicker-->
                                            <input class="form-control form-control-transparent fw-bold pe-5 flatpickr-input" placeholder="Pilih Tanggal" wire:model="tanggal" name="tanggal" id="tanggalPembelian" type="text" readonly="readonly"
                                            x-data 
                                            x-init="flatpickr($el, {
                                                enableTime: true,
                                                dateFormat: 'Y-m-d',
                                                defaultDate: '{{ $tanggal }}', // Set initial date from Livewire
                                            })">
                                            <!--end::Datepicker-->
                                            
                                            <!--begin::Icon-->
                                            <i class="ki-outline ki-down fs-4 position-absolute ms-4 end-0"></i>                        
                                            <!--end::Icon-->
                                        </div>
                                        <!--end::Input-->  
                                        @error('tanggal')
                                        <span class="text-danger">{{ $message }}</span> @enderror
                                        @error('tanggal')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror 
                                    </div>
                                    <!--end::Input group-->

                                    <!--begin::Input group-->
                                    <div class="d-flex flex-center flex-equal fw-row text-nowrap order-1 order-xxl-2 me-4"
                                        data-bs-toggle="tooltip" data-bs-trigger="hover"
                                        data-bs-original-title="Enter invoice number" data-kt-initialized="1">
                                        <span class="fs-2x fw-bold text-gray-800">Faktur #</span>
                                        <input type="text" wire:model="faktur" 
                                            class="form-control form-control-flush fw-bold text-muted fs-3 w-125px"
                                            value="2021001" readonly>
                                        @error('faktur')
                                        <span class="text-danger">{{ $message }}</span> @enderror
                                        @error('faktur')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror 
                                    </div>
                                    <!--end::Input group-->

                                    <!--begin::Input group-->
                                    <div class="d-flex align-items-center justify-content-end flex-equal order-3 fw-row" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-original-title="Specify invoice due date" data-kt-initialized="1">
                                        <!--begin::Date-->
                                        <div class="fs-6 fw-bold text-gray-700 text-nowrap">Supplier:</div>  
                                        <!--end::Date-->                    
                    
                                        <!--begin::Input-->
                                        <div class="position-relative d-flex align-items-center w-170px">
                                            <select id="selectedSupplier" name="selectedSupplier" class="form-control" wire:model="selectedSupplier" disabled>
                                                <option value="">Select a supplier</option>
                                                @foreach($suppliers as $supplier)
                                                    {{-- <option value="{{ $supplier->id }}">{{ $supplier->nama }}</option> --}}
                                                    <option value="{{ $supplier->id }}" @if($supplier->id == $selectedSupplier) selected @endif>{{ $supplier->nama }}</option>

                                                @endforeach
                                            </select>
                                            
                                            <!--begin::Icon-->
                                            <i class="ki-outline ki-down fs-4 position-absolute end-0 ms-4"></i>                        <!--end::Icon-->
                                            @error('selectedSupplier')
                                            <span class="text-danger">{{ $message }}</span> @enderror
                                            @error('selectedSupplier')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror 
                                        </div>
                                        <!--end::Input-->                
                                    </div>                
                                    <!--end::Input group-->

                                </div>
                                <!--end::Top-->

                                <!--begin::Separator-->
                                <div class="separator separator-dashed my-10"></div>
                                <!--end::Separator-->

                                <!--begin::Wrapper-->
                                <div class="mb-0">

                                    <!--begin::Table wrapper-->
                                    <div class="table-responsive mb-10">
                                        <!--begin::Table-->
                                        <table id="itemsTable" class="display" style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Jenis</th>
                                                    <th>Nama</th>
                                                    <th>Jumlah</th>
                                                    <th>Terpakai</th>
                                                    <th>Sisa</th>
                                                    <th>Harga</th>
                                                    <th>Sub Total</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>
                                    <!--end::Table-->

                                </div>
                                <!--end::Wrapper-->
                            </form>
                            <!--end::Form-->
                        
                        @else
                            <!--begin::Form--> 
                            <form action="" id="kt_pembelian_stok_form">
                                <!--begin::Wrapper-->
                                <div class="d-flex flex-column align-items-start flex-xxl-row">
                                    <!--begin::Input group-->
                                    <div class="d-flex align-items-center flex-equal fw-row me-4 order-2"
                                        data-bs-toggle="tooltip" data-bs-trigger="hover"
                                        data-bs-original-title="Specify invoice date" data-kt-initialized="1">
                                        <!--begin::Date-->
                                        <div class="fs-6 fw-bold text-gray-700 text-nowrap">Tanggal:</div>
                                        <!--end::Date-->

                                        <!--begin::Input-->
                                        <div class="position-relative d-flex align-items-center w-150px">
                                            <!--begin::Datepicker-->
                                            <input class="form-control form-control-transparent fw-bold pe-5 flatpickr-input" placeholder="Pilih Tanggal" wire:model="tanggal" name="tanggal" id="tanggalPembelian" type="text" readonly="readonly"
                                            x-data 
                                            x-init="flatpickr($el, {
                                                enableTime: true,
                                                dateFormat: 'Y-m-d',
                                                defaultDate: '{{ $tanggal }}', // Set initial date from Livewire
                                            })">
                                            <!--end::Datepicker-->
                                            
                                            <!--begin::Icon-->
                                            <i class="ki-outline ki-down fs-4 position-absolute ms-4 end-0"></i>                        
                                            <!--end::Icon-->
                                        </div>
                                        <!--end::Input-->  
                                        @error('tanggal')
                                        <span class="text-danger">{{ $message }}</span> @enderror
                                        @error('tanggal')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror 
                                    </div>
                                    <!--end::Input group-->

                                    <!--begin::Input group-->
                                    <div class="d-flex flex-center flex-equal fw-row text-nowrap order-1 order-xxl-2 me-4"
                                        data-bs-toggle="tooltip" data-bs-trigger="hover"
                                        data-bs-original-title="Enter invoice number" data-kt-initialized="1">
                                        <span class="fs-2x fw-bold text-gray-800">Faktur #</span>
                                        <input type="text" wire:model="faktur" 
                                            class="form-control form-control-flush fw-bold text-muted fs-3 w-125px"
                                            placeholder="{{ $fakturPlaceholder }}">
                                        @error('faktur')
                                        <span class="text-danger">{{ $message }}</span> @enderror
                                        @error('faktur')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror 
                                    </div>
                                    <!--end::Input group-->

                                    <!--begin::Input group-->
                                    <div class="d-flex align-items-center justify-content-end flex-equal order-3 fw-row" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-original-title="Specify invoice due date" data-kt-initialized="1">
                                        <!--begin::Date-->
                                        <div class="fs-6 fw-bold text-gray-700 text-nowrap">Supplier:</div>  
                                        <!--end::Date-->                    
                    
                                        <!--begin::Input-->
                                        <div class="position-relative d-flex align-items-center w-150px">
                                            <select id="selectedSupplier" name="selectedSupplier" class="form-control" wire:model="selectedSupplier">
                                                <option value="">Select a supplier</option>
                                                @foreach($suppliers as $supplier)
                                                    {{-- <option value="{{ $supplier->id }}">{{ $supplier->nama }}</option> --}}
                                                    <option value="{{ $supplier->id }}" @if($supplier->id == $selectedSupplier) selected @endif>{{ $supplier->nama }}</option>

                                                @endforeach
                                            </select>
                                            
                                            <!--begin::Icon-->
                                            <i class="ki-outline ki-down fs-4 position-absolute end-0 ms-4"></i>                        <!--end::Icon-->
                                            @error('selectedSupplier')
                                            <span class="text-danger">{{ $message }}</span> @enderror
                                            @error('selectedSupplier')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror 
                                        </div>
                                        <!--end::Input-->                
                                    </div>                
                                    <!--end::Input group-->

                                </div>
                                <!--end::Wrapper-->

                                <!--begin::Separator-->
                                <div class="separator separator-dashed my-10"></div>
                                <!--end::Separator-->

                                <!--begin::Wrapper-->
                                <div class="mb-0">
                                    <!--begin::Table wrapper-->
                                    <div class="table-responsive mb-10">
                                        <table class="table g-5 gs-0 mb-0 fw-bold text-gray-700" data-kt-element="items">
                                            <thead>
                                                <tr class="border-bottom fs-7 fw-bold text-gray-700 text-uppercase">
                                                    <th class="min-w-300px w-475px">Item</th>
                                                    <th class="min-w-100px w-100px">QTY</th>
                                                    <th class="min-w-150px w-150px">Price</th>
                                                    <th class="min-w-100px w-150px text-end">Total</th>
                                                    <th class="min-w-75px w-75px text-end">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($items as $index => $item)
                                                    <tr class="border-bottom border-bottom-dashed" data-kt-element="item">
                                                        <td>
                                                            <select wire:model="items.{{ $index }}.id" class="form-select">
                                                                <option value="">Select Item</option>
                                                                @foreach($allItems as $availableItem)
                                                                    <option value="{{ $availableItem->id }}">{{ $availableItem->itemCategory->name .' - '.$availableItem->name }}</option>
                                                                @endforeach
                                                            </select>
                                                            @error("items.$index.id")
                                                                <span class="text-danger">{{ $message }}</span>
                                                            @enderror
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-solid" wire:model="items.{{ $index }}.qty" min="0.01" step="0.01" data-kt-element="quantity">
                                                            @error("items.$index.qty")
                                                                <span class="text-danger">{{ $message }}</span>
                                                            @enderror
                                                        </td>
                                                        <td>
                                                            <input type="text" class="form-control form-control-solid text-end" wire:model="items.{{ $index }}.harga" data-kt-element="harga">
                                                            @error("items.$index.harga")
                                                                <span class="text-danger">{{ $message }}</span>
                                                            @enderror
                                                        </td>
                                                        <td class="text-end">
                                                            Rp<span data-kt-element="total">{{ number_format($this->calculateItemTotal($item), 2) }}</span>
                                                        </td>
                                                        <td class="text-end">
                                                            <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" wire:click="removeItem({{ $index }})">
                                                                <i class="ki-outline ki-trash fs-3"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                             <!--begin::Table foot-->
                                             <tfoot>
                                                <tr
                                                    class="border-top border-top-dashed align-top fs-6 fw-bold text-gray-700">
                                                    <th class="text-primary">
                                                        <button class="btn btn-link py-1" data-kt-element="add-item" wire:click="addItem">Add item</button>
                                                    </th>

                                                    <th colspan="2" class="border-bottom border-bottom-dashed ps-0">
                                                        <div class="d-flex flex-column align-items-start">
                                                            <div class="fs-5">Subtotal</div>
                                                        </div>
                                                    </th>

                                                    <th colspan="2" class="border-bottom border-bottom-dashed text-end">
                                                        Rp<span data-kt-element="sub-total">0.00</span>
                                                    </th>
                                                </tr>

                                                <tr class="align-top fw-bold text-gray-700">
                                                    <th></th>

                                                    <th colspan="2" class="fs-4 ps-0">Total</th>

                                                    <th colspan="2" class="text-end fs-4 text-nowrap">
                                                        Rp<span data-kt-element="grand-total">0.00</span>
                                                    </th>
                                                </tr>
                                            </tfoot>
                                            <!--end::Table foot-->
                                        </table>
                                        <!--begin::Item template-->
                                    <table class="table d-none" data-kt-element="item-template">
                                        <tbody>
                                            <tr class="border-bottom border-bottom-dashed" data-kt-element="item">
                                                <td class="pe-7">
                                                    <input type="text" class="form-control form-control-solid mb-2"
                                                        name="name[]" placeholder="Item name">
                                                </td>

                                                <td class="ps-0">
                                                    <input type="number" class="form-control form-control-solid" min="0.01" step="0.01"
                                                        name="quantity[]" placeholder="0.01" data-kt-element="quantity">
                                                </td>

                                                <td>
                                                    <input type="text" class="form-control form-control-solid text-end"
                                                        name="harga[]" placeholder="0.00" data-kt-element="harga">
                                                </td>

                                                <td class="pt-8 text-end">
                                                    Rp<span data-kt-element="total">0.00</span>
                                                </td>

                                                <td class="pt-5 text-end">
                                                    <button type="button"
                                                        class="btn btn-sm btn-icon btn-active-color-primary"
                                                        data-kt-element="remove-item">
                                                        <i class="ki-outline ki-trash fs-3"></i> </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>

                                    <table class="table d-none" data-kt-element="empty-template">
                                        <tbody>
                                            <tr data-kt-element="empty">
                                                <th colspan="5" class="text-muted text-center py-10">
                                                    No items
                                                </th>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <!--end::Item template-->
                                    </div>
                                    <!--end::Table wrapper-->

                                    <!--begin::Error Messages at Bottom-->
                                    <div class="mt-3">
                                        @if(session()->has('validation-errors'))
                                            <div class="alert alert-danger">
                                                <ul>
                                                    @foreach(session('validation-errors')['errors'] as $error)
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                    <!--end::Error Messages at Bottom-->
                                </div>
                                <!--end::Wrapper-->
                            </form>
                            <!--end::Form-->

                        @endif
                        
                    </div>
                    <!--end::Card body-->
                </div>
                <!--end::Card-->
            </div>
            <!--end::Content-->

            <!--begin::Sidebar-->
            <div class="flex-lg-auto min-w-lg-300px" data-select2-id="select2-data-151-2mkj">
                <!--begin::Card-->
                <div class="card" data-kt-sticky="true" data-kt-sticky-name="invoice"
                    data-kt-sticky-offset="{default: false, lg: '200px'}"
                    data-kt-sticky-width="{lg: '250px', lg: '300px'}" data-kt-sticky-left="auto"
                    data-kt-sticky-top="150px" data-kt-sticky-animation="false" data-kt-sticky-zindex="95" style="">

                    <!--begin::Card body-->
                    <div class="card-body p-10">
                        <!--begin::Input group-->
                        <div class="mb-10">
                            <!--begin::Label-->
                            <label class="form-label fw-bold fs-6 text-gray-700">DOC Batch</label>
                            <!--end::Label-->

                            <select id="selectedTernak" name="selectedTernak" class="form-control" wire:model="selectedTernak" @if ($editMode) disabled @endif>
                                <option value="">=== Pilih DOC Batch ===</option>
                                @foreach($ternaks as $ternak)
                                    <option value="{{ $ternak->id }}" @if($ternak->id == $selectedTernak) selected @endif>{{ $ternak->name }}</option>

                                @endforeach
                            </select>
                            @error('selectedTernak')
                            <span class="text-danger">{{ $message }}</span> @enderror
                            @error('selectedTernak')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror 
                        </div>
                        <!--end::Input group-->

                        <!--begin::Input group-->
                        <div class="mb-10">
                            <!--begin::Label-->
                            <label class="form-label fw-bold fs-6 text-gray-700">Ekspedisi</label>
                            <!--end::Label-->

                            <select id="selectedEkspedisi" name="selectedEkspedisi" class="form-control" wire:model="selectedEkspedisi" @if ($editMode) disabled @endif>
                                <option value="">=== Pilih Ekspedisi ===</option>
                                @if ($ekspedisis->isEmpty())
                                    <option value="" disabled>Belum ada data</option>
                                @else
                                    @foreach($ekspedisis as $ekspedisi)
                                        <option value="{{ $ekspedisi->id }}" @if($ekspedisi->id == $selectedEkspedisi) selected @endif>{{ $ekspedisi->nama }}</option>
                                    @endforeach
                                @endif
                            </select>
                            @error('selectedEkspedisi')
                            <span class="text-danger">{{ $message }}</span> @enderror
                            @error('selectedEkspedisi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror 
                        </div>
                        <!--end::Input group-->

                        <!--begin::Input group-->
                        <div class="mb-10">
                            <!--begin::Label-->
                            <label class="form-label fw-bold fs-6 text-gray-700">No. SJ</label>
                            <!--end::Label-->

                            <input type="text" id="noSjInput" class="form-control form-control-solid text-end" wire:model.defer="noSj" data-kt-element="noSj">
                            @error('noSj')
                            <span class="text-danger">{{ $message }}</span> @enderror
                            @error('noSj')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror 
                        </div>
                        <!--end::Input group-->

                        <!--begin::Input group-->
                        <div class="mb-10">
                            <!--begin::Label-->
                            <label class="form-label fw-bold fs-6 text-gray-700">Tarif Ekspedisi</label>
                            <!--end::Label-->

                            <input type="number" id="tarifEkspedisi" class="form-control form-control-solid text-end" wire:model.defer="tarifEkspedisi" data-kt-element="tarifEkspedisi" min="0" step="0.01" oninput="this.value = this.value.replace(/[^0-9.]/g, '')">
                            @error('tarifEkspedisi')
                            <span class="text-danger">{{ $message }}</span> @enderror
                            @error('tarifEkspedisi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror 
                        </div>
                        <!--end::Input group-->

                        <!--begin::Separator-->
                        <div class="separator separator-dashed mb-8"></div>
                        <!--end::Separator-->

                        <!--begin::Actions-->
                        <div class="mb-0">
                            {{-- <!--begin::Row-->
                            <div class="row mb-5">
                                <!--begin::Col-->
                                <div class="col">
                                    <a href="#" class="btn btn-light btn-active-light-primary w-100">Preview</a>
                                </div>
                                <!--end::Col-->

                                <!--begin::Col-->
                                <div class="col">
                                    <a href="#" class="btn btn-light btn-active-light-primary w-100">Download</a>
                                </div>
                                <!--end::Col-->
                            </div>
                            <!--end::Row--> --}}

                            <button type="button" class="btn btn-secondary" wire:click="close()">Close</button>
                            <button type="submit" href="#" class="btn btn-primary" wire:click="store()">Save
                            </button>
                        </div>
                        <!--end::Actions-->
                    </div>
                    <!--end::Card body-->
                </div>
                <!--end::Card-->
            </div>
            <!--end::Sidebar-->
        </div>
        <!--end::Layout-->
    </div>
    <!--end::Content container-->
</div>