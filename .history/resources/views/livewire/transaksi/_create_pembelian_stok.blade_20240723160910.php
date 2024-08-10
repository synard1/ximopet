<div class="card" id="stokFormCard" tabindex="-1" style="display: none;" wire:ignore.self>
            <div class="card-body">
                <form action="" id="kt_pembelian_stok_form">
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
                                <input class="form-control form-control-transparent fw-bold pe-5 flatpickr-input" placeholder="Pilih Tanggal" wire:model="tanggal" name="tanggal" type="text" readonly="readonly">
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
        
                        <!--begin::Input group-->
                        <div class="d-flex flex-center flex-equal fw-row text-nowrap order-1 order-xxl-2 me-4" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-original-title="Enter invoice number" data-kt-initialized="1">
                            <span class="fs-2x fw-bold text-gray-800">Invoice #</span> 
                            <input type="text" class="form-control form-control-flush fw-bold text-muted fs-3 w-125px" wire:model="faktur" name="faktur" value="000000">
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
                                <select id="supplierDropdown" class="form-control" wire:model="selectedSupplier ">
                                    <option value="">Select a supplier</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                                
                                <!--begin::Icon-->
                                <i class="ki-outline ki-down fs-4 position-absolute end-0 ms-4"></i>                        <!--end::Icon-->
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
                                    <tr class="border-bottom border-bottom-dashed" data-kt-element="item">
                                        <td class="pe-7">                                            
                                            <input type="text" class="form-control form-control-solid mb-2" name="name[]" placeholder="Item name">
                                        </td>
        
                                        <td class="ps-0">                                            
                                            <input type="text" class="form-control form-control-solid" min="1" name="quantity[]" placeholder="1" value="1" data-kt-element="quantity">
                                        </td>
        
                                        <td>   
                                            <input type="text" class="form-control form-control-solid text-end" name="price[]" placeholder="0.00" value="0.00" data-kt-element="price">
                                        </td>
                                        
                                        <td class="pt-8 text-end text-nowrap">
                                            $<span data-kt-element="total">0.00</span>
                                        </td>
        
                                        <td class="pt-5 text-end">
                                            <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" data-kt-element="remove-item">
                                                <i class="ki-outline ki-trash fs-3"></i>                                    </button>                             
                                        </td>
                                    </tr>          
                                </tbody>
                                <!--end::Table body-->
        
                                <!--begin::Table foot-->
                                <tfoot>
                                    <tr class="border-top border-top-dashed align-top fs-6 fw-bold text-gray-700">
                                        <th class="text-primary">
                                            <button class="btn btn-link py-1" data-kt-element="add-item">Add item</button>
                                        </th>  
        
                                        <th colspan="2" class="border-bottom border-bottom-dashed ps-0">
                                            <div class="d-flex flex-column align-items-start">
                                                <div class="fs-5">Subtotal</div> 
                                            </div>
                                        </th> 
        
                                        <th colspan="2" class="border-bottom border-bottom-dashed text-end">
                                            $<span data-kt-element="sub-total">0.00</span>
                                        </th> 
                                    </tr>    
                                    
                                    <tr class="align-top fw-bold text-gray-700">
                                        <th></th>
                                        
                                        <th colspan="2" class="fs-4 ps-0">Total</th> 
        
                                        <th colspan="2" class="text-end fs-4 text-nowrap">
                                            $<span data-kt-element="grand-total">0.00</span>
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
                                    <input type="text" class="form-control form-control-solid text-end" name="price[]" placeholder="0.00" data-kt-element="price">
                                </td>
                                
                                <td class="pt-8 text-end">
                                    $<span data-kt-element="total">0.00</span>
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
                {{-- <form>
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Jenis</label>
                        <!--end::Label-->
                        <!--begin::Select2-->
                        <select id="status" name="jenis" wire:model="jenis" class="js-select2 form-control">
                            <option value="">=== Pilih ===</option>
                            @foreach ($availableJenis as $jenisItem)
                            @if($jenisItem == $jenis) 'ada' @endif
                                <option value="{{ $jenisItem }}" @if($jenisItem == $jenis) selected @endif>{{ $jenisItem }}</option>
                            @endforeach
                        </select>
                        <!--end::Select2-->
                        @error('jenis')
                        <span class="text-danger">{{ $message }}</span> @enderror
                        @error('jenis')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <!--end::Input group-->

                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Kode Stok</label>
                        <!--end::Label-->
                        <!--begin::Input-->
                        <input type="text" wire:model="kode" id="kode"
                            class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Kode Stok" />
                        <!--end::Input-->
                        @error('kode')
                        <span class="text-danger">{{ $message }}</span> @enderror
                        @error('kode')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <!--end::Input group-->

                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Nama Stok</label>
                        <!--end::Label-->
                        <!--begin::Input-->
                        <input type="text" wire:model="nama" name="nama" id="nama" value="{{ $nama }}"
                            class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Nama Stok"/>
                        <!--end::Input-->
                        @error('nama')
                        <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <!--end::Input group-->

                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Satuan Besar</label>
                        <!--end::Label-->
                        <!--begin::Input-->
                        <input type="text" wire:model="satuan_besar" name="satuan_besar" id="satuan_besar" value="{{ $satuan_besar }}"
                            class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Satuan Terbesar"/>
                        <!--end::Input-->
                        @error('satuan_besar')
                        <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Satuan Kecil</label>
                        <!--end::Label-->
                        <!--begin::Input-->
                        <input type="text" wire:model="satuan_kecil" name="satuan_kecil" id="satuan_kecil" value="{{ $satuan_kecil }}"
                            class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Satuan Terkecil"/>
                        <!--end::Input-->
                        @error('satuan_kecil')
                        <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <!--end::Input group-->
                    <!--begin::Input group-->
                    <div class="fv-row mb-7">
                        <!--begin::Label-->
                        <label class="required fw-semibold fs-6 mb-2">Konversi</label>
                        <!--end::Label-->
                        <!--begin::Input-->
                        <input type="text" wire:model="konversi" name="konversi" id="konversi" value="{{ $konversi }}"
                            class="form-control form-control-solid mb-3 mb-lg-0" placeholder="Konversi Satuan Besar ke Kecil"/>
                        <!--end::Input-->
                        @error('konversi')
                        <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <!--end::Input group-->
                </form> --}}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="close()">Close</button>
                <button type="button" class="btn btn-primary" wire:click="store()">Save changes</button>
            </div>
</div>
