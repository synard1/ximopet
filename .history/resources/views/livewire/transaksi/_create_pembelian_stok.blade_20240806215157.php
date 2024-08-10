<div class="card" id="stokFormCard" tabindex="-1" style="display: none;" wire:ignore.self>
            <div class="card-body">
                <form id="kt_pembelian_stok_form">
                    <!--begin::Wrapper-->
                    <div class="d-flex flex-column align-items-start flex-xxl-row">
                        <!--begin::Input group-->
                        <div class="d-flex align-items-center flex-equal fw-row me-4 order-2" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-original-title="Specify invoice date" data-kt-initialized="1">
                            <!--begin::Date-->
                            <div class="fs-6 fw-bold text-gray-700 text-nowrap">Tanggal Pembelian:</div>  
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
                                <i class="ki-outline ki-down fs-4 position-absolute ms-4 end-0"></i>                        <!--end::Icon-->
                            </div>
                            <!--end::Input-->  
                            @error('tanggal')
                            <span class="text-danger">{{ $message }}</span> @enderror
                            @error('tanggal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror              
                        </div>                
                        <!--end::Input group--> 

                        <div class="d-flex flex-center flex-equal fw-row text-nowrap order-1 order-xxl-2 me-4" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-original-title="Enter invoice number" data-kt-initialized="1">
                            <span class="fs-2x fw-bold text-gray-800">Invoice #</span> 
                            <input type="text" class="form-control form-control-flush fw-bold text-muted fs-3 w-125px" wire:model="faktur" name="faktur" placehoder="2021001">
                            @error('faktur')
                            <span class="text-danger">{{ $message }}</span> @enderror
                            @error('faktur')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror 
                        </div>
        
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
                                                        <option value="{{ $availableItem->id }}" @if($availableItem->id == $selectedSupplier) selected @endif>{{ $supplier->nama }}</option>
                                                    @endforeach
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
                                    <tr class="border-top border-top-dashed align-top fs-6 fw-bold text-gray-700">
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
                        </div>  
                        <!--end::Table-->
        
                        <!--begin::Item template-->
                        <table class="table d-none" data-kt-element="item-template">
                            <tbody><tr class="border-bottom border-bottom-dashed" data-kt-element="item">
                                <td class="pe-7">                                            
                                    <input type="text" class="form-control form-control-solid mb-2" name="name[]" placeholder="Item name">  
                                </td>
        
                                <td class="ps-0">                                            
                                    <input type="text" class="form-control form-control-solid" min="1" name="quantity[]" placeholder="1" data-kt-element="quantity">
                                </td>
        
                                <td>   
                                    <input type="text" class="form-control form-control-solid text-end" name="harga[]" placeholder="0.00" data-kt-element="harga">
                                </td>
                                
                                <td class="pt-8 text-end">
                                    Rp<span data-kt-element="total">0.00</span>
                                </td>
        
                                <td class="pt-5 text-end">
                                    <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" data-kt-element="remove-item">
                                        <i class="ki-outline ki-trash fs-3"></i>                            </button>                             
                                </td>
                            </tr>
                        </tbody></table>
        
                        <table class="table d-none" data-kt-element="empty-template">
                            <tbody><tr data-kt-element="empty">
                                <th colspan="5" class="text-muted text-center py-10">
                                    No items
                                </th>
                            </tr>
                        </tbody></table>
                        <!--end::Item template-->
                    </div>   
                    <!--end::Wrapper-->          
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="close()">Close</button>
                <button type="button" class="btn btn-primary" wire:click="store()">Save changes</button>
            </div>
</div>
