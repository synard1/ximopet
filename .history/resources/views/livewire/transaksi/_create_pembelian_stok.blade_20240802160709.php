<div id="kt_app_content" class="app-content  flex-column-fluid " data-select2-id="select2-data-kt_app_content">
    
           
    <!--begin::Content container-->
    <div id="kt_app_content_container" class="app-container  container-fluid " data-select2-id="select2-data-kt_app_content_container">
        <!--begin::Layout-->
<div class="d-flex flex-column flex-lg-row" data-select2-id="select2-data-152-7yz0">
<!--begin::Content-->
<div class="flex-lg-row-fluid mb-10 mb-lg-0 me-lg-7 me-xl-10">
    <!--begin::Card--> 
<div class="card">     
<!--begin::Card body-->
<div class="card-body p-12">
    <!--begin::Form-->
    <form action="" id="kt_invoice_form">
        <!--begin::Wrapper-->
        <div class="d-flex flex-column align-items-start flex-xxl-row">
            <!--begin::Input group-->
            <div class="d-flex align-items-center flex-equal fw-row me-4 order-2" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-original-title="Specify invoice date" data-kt-initialized="1">
                <!--begin::Date-->
                <div class="fs-6 fw-bold text-gray-700 text-nowrap">Date:</div>  
                <!--end::Date-->                    

                <!--begin::Input-->
                <div class="position-relative d-flex align-items-center w-150px">
                    <!--begin::Datepicker-->
                    <input class="form-control form-control-transparent fw-bold pe-5 flatpickr-input" placeholder="Select date" name="invoice_date" type="text" readonly="readonly">
                    <!--end::Datepicker-->
                    
                    <!--begin::Icon-->
                    <i class="ki-outline ki-down fs-4 position-absolute ms-4 end-0"></i>                        <!--end::Icon-->
                </div>
                <!--end::Input-->                
            </div>                
            <!--end::Input group--> 

            <!--begin::Input group-->
            <div class="d-flex flex-center flex-equal fw-row text-nowrap order-1 order-xxl-2 me-4" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-original-title="Enter invoice number" data-kt-initialized="1">
                <span class="fs-2x fw-bold text-gray-800">Invoice #</span> 
                <input type="text" class="form-control form-control-flush fw-bold text-muted fs-3 w-125px" value="2021001" placehoder="...">
            </div>                
            <!--end::Input group--> 

            <!--begin::Input group-->
            <div class="d-flex align-items-center justify-content-end flex-equal order-3 fw-row" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-original-title="Specify invoice due date" data-kt-initialized="1">
                <!--begin::Date-->
                <div class="fs-6 fw-bold text-gray-700 text-nowrap">Due Date:</div>  
                <!--end::Date-->                    

                <!--begin::Input-->
                <div class="position-relative d-flex align-items-center w-150px">
                    <!--begin::Datepicker-->
                    <input class="form-control form-control-transparent fw-bold pe-5 flatpickr-input" placeholder="Select date" name="invoice_due_date" type="text" readonly="readonly">
                    <!--end::Datepicker-->
                    
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
            <!--begin::Row-->
            <div class="row gx-10 mb-5">
                <!--begin::Col-->
                <div class="col-lg-6">
                    <label class="form-label fs-6 fw-bold text-gray-700 mb-3">Bill From</label>

                    <!--begin::Input group-->
                    <div class="mb-5">
                        <input type="text" class="form-control form-control-solid" placeholder="Name">
                    </div>
                    <!--end::Input group-->

                    <!--begin::Input group-->
                    <div class="mb-5">
                        <input type="text" class="form-control form-control-solid" placeholder="Email">
                    </div>
                    <!--end::Input group-->

                    <!--begin::Input group-->
                    <div class="mb-5">
                        <textarea name="notes" class="form-control form-control-solid" rows="3" placeholder="Who is this invoice from?"></textarea>
                    </div>
                    <!--end::Input group-->
                </div>                
                <!--end::Col--> 

                <!--begin::Col-->
                <div class="col-lg-6">
                    <label class="form-label fs-6 fw-bold text-gray-700 mb-3">Bill To</label>

                    <!--begin::Input group-->
                    <div class="mb-5">
                        <input type="text" class="form-control form-control-solid" placeholder="Name">
                    </div>
                    <!--end::Input group-->

                    <!--begin::Input group-->
                    <div class="mb-5">
                        <input type="text" class="form-control form-control-solid" placeholder="Email">
                    </div>
                    <!--end::Input group-->

                    <!--begin::Input group-->
                    <div class="mb-5">
                        <textarea name="notes" class="form-control form-control-solid" rows="3" placeholder="What is this invoice for?"></textarea>
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
                        <tr class="border-bottom border-bottom-dashed" data-kt-element="item">
                            <td class="pe-7">                                            
                                <input type="text" class="form-control form-control-solid mb-2" name="name[]" placeholder="Item name">

                                <input type="text" class="form-control form-control-solid" name="description[]" placeholder="Description">     
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

                                    <button class="btn btn-link py-1" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-original-title="Coming soon" data-kt-initialized="1">Add tax</button>

                                    <button class="btn btn-link py-1" data-bs-toggle="tooltip" data-bs-trigger="hover" data-bs-original-title="Coming soon" data-kt-initialized="1">Add discount</button>
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

                        <input type="text" class="form-control form-control-solid" name="description[]" placeholder="Description">     
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

            <!--begin::Notes-->            
            <div class="mb-0">
                <label class="form-label fs-6 fw-bold text-gray-700">Notes</label>

                <textarea name="notes" class="form-control form-control-solid" rows="3" placeholder="Thanks for your business"></textarea>
            </div>                        
            <!--end::Notes--> 
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
<div class="card" data-kt-sticky="true" data-kt-sticky-name="invoice" data-kt-sticky-offset="{default: false, lg: '200px'}" data-kt-sticky-width="{lg: '250px', lg: '300px'}" data-kt-sticky-left="auto" data-kt-sticky-top="150px" data-kt-sticky-animation="false" data-kt-sticky-zindex="95" style="">

<!--begin::Card body-->
<div class="card-body p-10">  
    <!--begin::Input group-->
    <div class="mb-10">
        <!--begin::Label-->
        <label class="form-label fw-bold fs-6 text-gray-700">Currency</label>
        <!--end::Label-->

        <!--begin::Select-->           
        <select name="currnecy" aria-label="Select a Timezone" data-control="select2" data-placeholder="Select currency" class="form-select form-select-solid select2-hidden-accessible" data-select2-id="select2-data-9-xr36" tabindex="-1" aria-hidden="true" data-kt-initialized="1">
            <option value="" data-select2-id="select2-data-11-3gme"></option>

                                <option data-kt-flag="flags/united-states.svg" value="USD" data-select2-id="select2-data-154-iunb">USD&nbsp;-&nbsp;USA dollar</option>
                                <option data-kt-flag="flags/united-kingdom.svg" value="GBP" data-select2-id="select2-data-155-d7sd">GBP&nbsp;-&nbsp;British pound</option>
                                <option data-kt-flag="flags/australia.svg" value="AUD" data-select2-id="select2-data-156-1ksv">AUD&nbsp;-&nbsp;Australian dollar</option>
                                <option data-kt-flag="flags/japan.svg" value="JPY" data-select2-id="select2-data-157-w2af">JPY&nbsp;-&nbsp;Japanese yen</option>
                                <option data-kt-flag="flags/sweden.svg" value="SEK" data-select2-id="select2-data-158-16qu">SEK&nbsp;-&nbsp;Swedish krona</option>
                                <option data-kt-flag="flags/canada.svg" value="CAD" data-select2-id="select2-data-159-smz5">CAD&nbsp;-&nbsp;Canadian dollar</option>
                                <option data-kt-flag="flags/switzerland.svg" value="CHF" data-select2-id="select2-data-160-h73q">CHF&nbsp;-&nbsp;Swiss franc</option>
                        </select><span class="select2 select2-container select2-container--bootstrap5 select2-container--below" dir="ltr" data-select2-id="select2-data-10-oqm4" style="width: 100%;"><span class="selection"><span class="select2-selection select2-selection--single form-select form-select-solid" role="combobox" aria-haspopup="true" aria-expanded="false" tabindex="0" aria-disabled="false" aria-labelledby="select2-currnecy-ah-container" aria-controls="select2-currnecy-ah-container"><span class="select2-selection__rendered" id="select2-currnecy-ah-container" role="textbox" aria-readonly="true" title="GBP&nbsp;-&nbsp;British pound">GBP&nbsp;-&nbsp;British pound</span><span class="select2-selection__arrow" role="presentation"><b role="presentation"></b></span></span></span><span class="dropdown-wrapper" aria-hidden="true"></span></span>            
        <!--end::Select-->
    </div>
    <!--end::Input group-->

    <!--begin::Separator-->
    <div class="separator separator-dashed mb-8"></div>
    <!--end::Separator--> 

    <!--begin::Input group-->
    <div class="mb-8">
        <!--begin::Option-->
        <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack mb-5">
            <span class="form-check-label ms-0 fw-bold fs-6 text-gray-700">
                Payment method
            </span>

            <input class="form-check-input" type="checkbox" checked="checked" value="">               
        </label>
        <!--end::Option-->

        <!--begin::Option-->
        <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack mb-5">
            <span class="form-check-label ms-0 fw-bold fs-6 text-gray-700">
                Late fees
            </span>

            <input class="form-check-input" type="checkbox" value="">               
        </label>
        <!--end::Option-->

        <!--begin::Option-->
        <label class="form-check form-switch form-switch-sm form-check-custom form-check-solid flex-stack">
            <span class="form-check-label ms-0 fw-bold fs-6 text-gray-700">
                Notes
            </span>

            <input class="form-check-input" type="checkbox" value="">               
        </label>
        <!--end::Option-->
    </div>
    <!--end::Input group-->

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
        
        <button type="submit" href="#" class="btn btn-primary w-100" id="kt_invoice_submit_button"><i class="ki-outline ki-triangle fs-3"></i>                Send Invoice
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
<!--end::Layout-->              </div>
    <!--end::Content container-->
</div>