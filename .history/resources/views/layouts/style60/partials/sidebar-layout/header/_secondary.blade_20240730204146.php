<div class="app-header-secondary app-header-mobile-drawer" data-kt-drawer="true"
    data-kt-drawer-name="app-header-secondary" data-kt-drawer-activate="{default: true, lg: false}"
    data-kt-drawer-overlay="true" data-kt-drawer-width="250px" data-kt-drawer-direction="start"
    data-kt-drawer-toggle="#kt_header_secondary_mobile_toggle" data-kt-swapper="true"
    data-kt-swapper-mode="{default: 'append', lg: 'append'}"
    data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header'}">
    <!--begin::Header secondary wrapper-->
    <div class="d-flex flex-column flex-grow-1 overflow-hidden">
        <div
            class="app-header-secondary-menu-main d-flex flex-grow-lg-1 align-items-end pt-3 pt-lg-2 px-3 px-lg-0 w-auto overflow-auto flex-nowrap">
            <div class="app-container container-fluid">
                <!--begin::Main menu-->
                <div class="menu menu-rounded menu-nowrap flex-shrink-0 menu-row menu-active-bg menu-title-gray-700 menu-state-gray-900 menu-icon-gray-500 menu-arrow-gray-500 menu-state-icon-primary menu-state-bullet-primary fw-semibold fs-base align-items-stretch"
                    id="#kt_app_header_secondary_menu" data-kt-menu="true">
                    @if(request()->is('admin*'))
                    <!--begin:Menu item-->
                    <!--begin:Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu link-->
                        <a class="menu-link" href="/">
                            <span class="menu-title">Account</span>
                        </a>
                        <!--end:Menu link-->
                    </div>
                    <!--end:Menu item-->
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu content-->
                        <div class="menu-content">
                            <div class="menu-separator"></div>
                        </div>
                        <!--end:Menu content-->
                    </div>
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start"
                        class="menu-item here">
                        <!--begin:Menu link-->
                        <span class="menu-link">
                            <span class="menu-title">Administrator</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <!--end:Menu link-->
                        <!--begin:Menu sub-->
                        <div class="menu-sub menu-sub-dropdown px-lg-2 py-lg-4 w-150px w-lg-175px" style="">
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link active" href="index.html">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-chart-simple fs-3"></i>
                                    </span>
                                    <span class="menu-title">Dashboard</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link" href="account/settings.html">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-share fs-3"></i>
                                    </span>
                                    <span class="menu-title">Settings</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link" href="account/security.html">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-shield fs-3"></i>
                                    </span>
                                    <span class="menu-title">Security</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link" href="account/activity.html">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-scan-barcode fs-3"></i>
                                    </span>
                                    <span class="menu-title">Activity</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link" href="account/billing.html">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-dollar fs-3"></i>
                                    </span>
                                    <span class="menu-title">Billing</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link" href="apps/statements.html">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-two-credit-cart fs-3"></i>
                                    </span>
                                    <span class="menu-title">Statements</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <!--end:Menu item-->
                    @else
                    <!--begin:Menu item-->
                    <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start"
                        class="menu-item here">
                        <!--begin:Menu link-->
                        <span class="menu-link">
                            <span class="menu-title">Account</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <!--end:Menu link-->
                        <!--begin:Menu sub-->
                        <div class="menu-sub menu-sub-dropdown px-lg-2 py-lg-4 w-150px w-lg-175px" style="">
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link active" href="index.html">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-chart-simple fs-3"></i>
                                    </span>
                                    <span class="menu-title">Dashboard</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link" href="account/settings.html">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-share fs-3"></i>
                                    </span>
                                    <span class="menu-title">Settings</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link" href="account/security.html">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-shield fs-3"></i>
                                    </span>
                                    <span class="menu-title">Security</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link" href="account/activity.html">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-scan-barcode fs-3"></i>
                                    </span>
                                    <span class="menu-title">Activity</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link" href="account/billing.html">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-dollar fs-3"></i>
                                    </span>
                                    <span class="menu-title">Billing</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link" href="apps/statements.html">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-two-credit-cart fs-3"></i>
                                    </span>
                                    <span class="menu-title">Statements</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu content-->
                        <div class="menu-content">
                            <div class="menu-separator"></div>
                        </div>
                        <!--end:Menu content-->
                    </div>
                    <!--end:Menu item-->
                    @if(auth()->user()->hasRole(['SuperAdmin','Administrator']))
                        <!--begin:Menu item-->
                        <div class="menu-item">
                            <!--begin:Menu link-->
                            <a class="menu-link" href="/administrator">
                                <span class="menu-title">Administrator</span>
                            </a>
                            <!--end:Menu link-->
                        </div>
                        <!--end:Menu item-->
                    @endif

                    @endif

                    <!--begin:Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu content-->
                        <div class="menu-content">
                            <div class="menu-separator"></div>
                        </div>
                        <!--end:Menu content-->
                    </div>
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu link-->
                        <a class="menu-link" href="#">
                            <span class="menu-title">Reports</span>
                        </a>
                        <!--end:Menu link-->
                    </div>
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu content-->
                        <div class="menu-content">
                            <div class="menu-separator"></div>
                        </div>
                        <!--end:Menu content-->
                    </div>
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div class="menu-item flex-grow-1"></div>
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu content-->
                        <div class="menu-content">
                            <div class="menu-separator d-block d-lg-none"></div>
                        </div>
                        <!--end:Menu content-->
                    </div>
                    <!--end:Menu item-->
                </div>
                <!--end::Menu-->
            </div>
        </div>

        <div class="app-header-secondary-menu-sub d-flex align-items-stretch flex-grow-1">
            <div
                class="app-container d-flex flex-column flex-lg-row align-items-stretch justify-content-lg-between container-fluid">
                <!--begin::Main menu-->
                <div class="menu menu-rounded menu-column menu-lg-row menu-active-bg menu-title-gray-700 menu-state-gray-900 menu-icon-gray-500 menu-arrow-gray-500 menu-state-icon-primary menu-state-bullet-primary fw-semibold fs-base align-items-stretch my-2 my-lg-0 px-2 px-lg-0"
                    id="#kt_app_header_tertiary_menu" data-kt-menu="true">
                    @if(request()->is('admin*'))
                        <!--begin:Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu link-->
                        <a class="menu-link active" href="/administrator">
                            <span class="menu-icon">
                                <i class="ki-outline ki-element-9 fs-4"></i>
                            </span>
                            <span class="menu-title">Dashboard</span>
                        </a>
                        <!--end:Menu link-->
                    </div>
                    <!--end:Menu item-->
                    <!--begin:Menu item-->
                    <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start"
                        class="menu-item menu-lg-down-accordion me-0 me-lg-2">
                        <!--begin:Menu link-->
                        <span class="menu-link">
                            <span class="menu-icon">
                                <i class="ki-outline ki-grid fs-4"></i>
                            </span>
                            <span class="menu-title">Menu</span>
                            <span class="menu-arrow"></span>
                        </span>
                        <!--end:Menu link-->
                        <!--begin:Menu sub-->
                        <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-200px"
                            style="">
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link" href="/master-data/suppliers">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-menu fs-3"></i>
                                    </span>
                                    <span class="menu-title">Supplier</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link" href="/master-data/customers">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-basket-ok fs-3"></i>
                                    </span>
                                    <span class="menu-title">Customers</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link" href="/master-data/farms">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-home fs-3"></i>
                                    </span>
                                    <span class="menu-title">Farms</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link" href="/master-data/kandangs">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-home-1 fs-3"></i>
                                    </span>
                                    <span class="menu-title">Kandang</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link" href="/master-data/stoks">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-rocket fs-3"></i>
                                    </span>
                                    <span class="menu-title">Stoks</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                            <!--begin:Menu item-->
                            <div class="menu-item">
                                <!--begin:Menu link-->
                                <a class="menu-link" href="/transaksi/docs">
                                    <span class="menu-icon">
                                        <i class="ki-outline ki-calendar-8 fs-3"></i>
                                    </span>
                                    <span class="menu-title">DOC</span>
                                </a>
                                <!--end:Menu link-->
                            </div>
                            <!--end:Menu item-->
                        </div>
                        <!--end:Menu sub-->
                    </div>
                    <!--end:Menu item-->

                    @else
                        <!--begin:Menu item-->
                    <div class="menu-item">
                        <!--begin:Menu link-->
                        <a class="menu-link active" href="/">
                            <span class="menu-icon">
                                <i class="ki-outline ki-element-9 fs-4"></i>
                            </span>
                            <span class="menu-title">Dashboard</span>
                        </a>
                        <!--end:Menu link-->
                    </div>
                    <!--end:Menu item-->
                    @if(auth()->user()->hasRole(['Administrator']))
                        <!--begin:Menu item-->
                        <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start"
                            class="menu-item menu-lg-down-accordion me-0 me-lg-2">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-grid fs-4"></i>
                                </span>
                                <span class="menu-title">Menu</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->
                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-200px"
                                style="">
                                <!--begin:Menu item-->
                                <div class="menu-item">
                                    <!--begin:Menu link-->
                                    <a class="menu-link" href="/master-data/users">
                                        <span class="menu-icon">
                                            <i class="ki-outline ki-menu fs-3"></i>
                                        </span>
                                        <span class="menu-title">Users</span>
                                    </a>
                                    <!--end:Menu link-->
                                </div>
                                <!--end:Menu item-->
                            </div>
                            <!--end:Menu sub-->
                        </div>
                        <!--end:Menu item-->
                        @if(auth()->user()->hasRole(['Administrator']))
                        <!--begin:Menu item-->
                        <div data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-placement="bottom-start"
                            class="menu-item menu-lg-down-accordion me-0 me-lg-2">
                            <!--begin:Menu link-->
                            <span class="menu-link">
                                <span class="menu-icon">
                                    <i class="ki-outline ki-grid fs-4"></i>
                                </span>
                                <span class="menu-title">Menu</span>
                                <span class="menu-arrow"></span>
                            </span>
                            <!--end:Menu link-->
                            <!--begin:Menu sub-->
                            <div class="menu-sub menu-sub-lg-down-accordion menu-sub-lg-dropdown px-lg-2 py-lg-4 w-lg-200px"
                                style="">
                                <!--begin:Menu item-->
                                <div class="menu-item">
                                    <!--begin:Menu link-->
                                    <a class="menu-link" href="/master-data/suppliers">
                                        <span class="menu-icon">
                                            <i class="ki-outline ki-menu fs-3"></i>
                                        </span>
                                        <span class="menu-title">Supplier</span>
                                    </a>
                                    <!--end:Menu link-->
                                </div>
                                <!--end:Menu item-->
                                <!--begin:Menu item-->
                                <div class="menu-item">
                                    <!--begin:Menu link-->
                                    <a class="menu-link" href="/master-data/customers">
                                        <span class="menu-icon">
                                            <i class="ki-outline ki-basket-ok fs-3"></i>
                                        </span>
                                        <span class="menu-title">Customers</span>
                                    </a>
                                    <!--end:Menu link-->
                                </div>
                                <!--end:Menu item-->
                                <!--begin:Menu item-->
                                <div class="menu-item">
                                    <!--begin:Menu link-->
                                    <a class="menu-link" href="/master-data/farms">
                                        <span class="menu-icon">
                                            <i class="ki-outline ki-home fs-3"></i>
                                        </span>
                                        <span class="menu-title">Farms</span>
                                    </a>
                                    <!--end:Menu link-->
                                </div>
                                <!--end:Menu item-->
                                <!--begin:Menu item-->
                                <div class="menu-item">
                                    <!--begin:Menu link-->
                                    <a class="menu-link" href="/master-data/kandangs">
                                        <span class="menu-icon">
                                            <i class="ki-outline ki-home-1 fs-3"></i>
                                        </span>
                                        <span class="menu-title">Kandang</span>
                                    </a>
                                    <!--end:Menu link-->
                                </div>
                                <!--end:Menu item-->
                                <!--begin:Menu item-->
                                <div class="menu-item">
                                    <!--begin:Menu link-->
                                    <a class="menu-link" href="/master-data/stoks">
                                        <span class="menu-icon">
                                            <i class="ki-outline ki-rocket fs-3"></i>
                                        </span>
                                        <span class="menu-title">Stoks</span>
                                    </a>
                                    <!--end:Menu link-->
                                </div>
                                <!--end:Menu item-->
                                <!--begin:Menu item-->
                                <div class="menu-item">
                                    <!--begin:Menu link-->
                                    <a class="menu-link" href="/transaksi/docs">
                                        <span class="menu-icon">
                                            <i class="ki-outline ki-calendar-8 fs-3"></i>
                                        </span>
                                        <span class="menu-title">DOC</span>
                                    </a>
                                    <!--end:Menu link-->
                                </div>
                                <!--end:Menu item-->
                            </div>
                            <!--end:Menu sub-->
                        </div>
                        <!--end:Menu item-->
                    @endif
                    @endif
                    
                </div>
                <!--end::Menu-->
            </div>
        </div>
    </div>
    <!--end::Header secondary wrapper-->
</div>