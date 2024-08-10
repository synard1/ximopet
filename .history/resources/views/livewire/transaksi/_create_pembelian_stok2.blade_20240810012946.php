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
                                            dateFormat: 'Y-m-d H:i',
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
                                        value="2021001" placehoder="...">
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
                            <!--end::Top-->

                            <!--begin::Separator-->
                            <div class="separator separator-dashed my-10"></div>
                            <!--end::Separator-->

                            <!--begin::Wrapper-->
                            <div class="mb-0">
                                <!--begin::Row-->
                                <div class="row gx-10 mb-5">
                                    <!--begin::Col-->
                                    <div class="col-lg-6">
                                        <label class="form-label fs-6 fw-bold text-gray-700 mb-3">Bill From</label>

                                        <!--begin::Input group-->
                                        <div class="mb-5">
                                            <input type="text" class="form-control form-control-solid"
                                                placeholder="Name">
                                        </div>
                                        <!--end::Input group-->

                                        <!--begin::Input group-->
                                        <div class="mb-5">
                                            <input type="text" class="form-control form-control-solid"
                                                placeholder="Email">
                                        </div>
                                        <!--end::Input group-->

                                        <!--begin::Input group-->
                                        <div class="mb-5">
                                            <textarea name="notes" class="form-control form-control-solid" rows="3"
                                                placeholder="Who is this invoice from?"></textarea>
                                        </div>
                                        <!--end::Input group-->
                                    </div>
                                    <!--end::Col-->

                                    <!--begin::Col-->
                                    <div class="col-lg-6">
                                        <label class="form-label fs-6 fw-bold text-gray-700 mb-3">Bill To</label>

                                        <!--begin::Input group-->
                                        <div class="mb-5">
                                            <input type="text" class="form-control form-control-solid"
                                                placeholder="Name">
                                        </div>
                                        <!--end::Input group-->

                                        <!--begin::Input group-->
                                        <div class="mb-5">
                                            <input type="text" class="form-control form-control-solid"
                                                placeholder="Email">
                                        </div>
                                        <!--end::Input group-->

                                        <!--begin::Input group-->
                                        <div class="mb-5">
                                            <textarea name="notes" class="form-control form-control-solid" rows="3"
                                                placeholder="What is this invoice for?"></textarea>
                                        </div>
                                        <!--end::Input group-->
                                    </div>
                                    <!--end::Col-->
                                </div>
                                <!--end::Row-->

                                <!--begin::Table wrapper-->
                                <div class="table-responsive mb-10">
                                    <!--begin::Table-->
                                    <table class="table g-5 gs-0 mb-0 fw-bold text-gray-700" data-kt-element="items">
                                        <!--begin::Table head-->
                                        <thead>
                                            <tr class="border-bottom fs-7 fw-bold text-gray-700 text-uppercase">
                                                <th class="min-w-300px w-475px">Item</th>
                                                <th class="min-w-100px w-100px">QTY</th>
                                                <th class="min-w-150px w-150px">Price</th>
                                                <th class="min-w-100px w-150px text-end">Total</th>
                                                <th class="min-w-75px w-75px text-end">Action</th>
                                            </tr>
                                        </thead>
                                        <!--end::Table head-->

                                        <!--begin::Table body-->
                                        <tbody>
                                            @foreach($items as $index => $item)
                                                <tr class="border-bottom border-bottom-dashed" data-kt-element="item">
                                                    <td>
                                                        <select wire:model="items.{{ $index }}.name" class="form-select select2">
                                                            <option value="">Select Item</option>
                                                            @foreach($allItems as $availableItem)
                                                                <option value="{{ $availableItem->id }}">{{ $availableItem->nama }}</option>
                                                                {{-- <option value="{{ $availableItem->id }}" @if($availableItem->id == $item) selected @endif>{{ $availableItem->nama }}</option> --}}
                                                            @endforeach
                                                            {{-- {!! $items[$index]['qty'] !!} --}}
                                                        </select>
                                                        <input type="hidden" wire:model="items.{{ $index }}.item_id">
                                                    </td>
                                                    <td class="ps-0">
                                                        <input type="number" class="form-control form-control-solid" min="1" wire:model="items.{{ $index }}.qty" placeholder="1" data-kt-element="quantity">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-solid text-end" wire:model="items.{{ $index }}.harga" placeholder="0.00" data-kt-element="harga" >
                                                    </td>
                                                    <td class="pt-8 text-end text-nowrap">
                                                        Rp<span data-kt-element="total">{{ number_format($item['qty'] * $item['harga'], 2) }}</span>
                                                    </td>
                                                    <td class="pt-5 text-end">
                                                        <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" wire:click="removeItem({{ $index }})">
                                                            <i class="ki-outline ki-trash fs-3"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <!--end::Table body-->

                                        <!--begin::Table foot-->
                                        <tfoot>
                                            <tr
                                                class="border-top border-top-dashed align-top fs-6 fw-bold text-gray-700">
                                                <th class="text-primary">
                                                    <button class="btn btn-link py-1" data-kt-element="add-item">Add
                                                        item</button>
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
                                </div>
                                <!--end::Table-->

                                <!--begin::Item template-->
                                <table class="table d-none" data-kt-element="item-template">
                                    <tbody>
                                        <tr class="border-bottom border-bottom-dashed" data-kt-element="item">
                                            <td class="pe-7">
                                                <input type="text" class="form-control form-control-solid mb-2"
                                                    name="name[]" placeholder="Item name">

                                                <input type="text" class="form-control form-control-solid"
                                                    name="description[]" placeholder="Description">
                                            </td>

                                            <td class="ps-0">
                                                <input type="text" class="form-control form-control-solid" min="1"
                                                    name="quantity[]" placeholder="1" data-kt-element="quantity">
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

                                {{-- <!--begin::Notes-->
                                <div class="mb-0">
                                    <label class="form-label fs-6 fw-bold text-gray-700">Notes</label>

                                    <textarea name="notes" class="form-control form-control-solid" rows="3"
                                        placeholder="Thanks for your business"></textarea>
                                </div>
                                <!--end::Notes--> --}}
                            </div>
                            <!--end::Wrapper-->
                        </form>
                        <!--end::Form-->
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
                            <label class="form-label fw-bold fs-6 text-gray-700">Farm</label>
                            <!--end::Label-->

                            <select id="selectedFarm" name="selectedFarm" class="form-control" wire:model="selectedFarm">
                                <option value="">=== Pilih Farm ===</option>
                                @foreach($farms as $farm)
                                    <option value="{{ $farm->farm_id }}">{{ $farm->nama_farm }}</option>

                                @endforeach
                            </select>
                        </div>
                        <!--end::Input group-->
{{-- 
                        <!--begin::Separator-->
                        <div class="separator separator-dashed mb-8"></div>
                        <!--end::Separator-->

                        <!--begin::Input group-->
                        <div class="mb-8">
                            <!--begin::Option-->
                            <label
                                class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack mb-5">
                                <span class="form-check-label ms-0 fw-bold fs-6 text-gray-700">
                                    Payment method
                                </span>

                                <input class="form-check-input" type="checkbox" checked="checked" value="">
                            </label>
                            <!--end::Option-->

                            <!--begin::Option-->
                            <label
                                class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack mb-5">
                                <span class="form-check-label ms-0 fw-bold fs-6 text-gray-700">
                                    Late fees
                                </span>

                                <input class="form-check-input" type="checkbox" value="">
                            </label>
                            <!--end::Option-->

                            <!--begin::Option-->
                            <label
                                class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack">
                                <span class="form-check-label ms-0 fw-bold fs-6 text-gray-700">
                                    Notes
                                </span>

                                <input class="form-check-input" type="checkbox" value="">
                            </label>
                            <!--end::Option-->
                        </div>
                        <!--end::Input group--> --}}

                        <!--begin::Separator-->
                        <div class="separator separator-dashed mb-8"></div>
                        <!--end::Separator-->

                        <!--begin::Actions-->
                        <div class="mb-0">
                            <!--begin::Row-->
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
                            <!--end::Row-->

                            <button type="submit" href="#" class="btn btn-primary w-100"
                                id="kt_invoice_submit_button"><i class="ki-outline ki-triangle fs-3"></i> Send Invoice
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