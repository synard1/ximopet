<x-default-layout>

    @section('title')
    Dashboard
    @endsection

    @section('breadcrumbs')
    {{ Breadcrumbs::render('dashboard') }}
    @endsection

    <div class="row gx-5 gx-xl-10">
        <!--begin::Col-->
        <div class="col-xl-9 mb-10">

            <!--begin::Lists Widget 19-->
            <div class="card card-flush h-xl-100">
                <!--begin::Heading-->
                <div class="card-header rounded bgi-no-repeat bgi-size-cover bgi-position-y-top bgi-position-x-center align-items-start h-250px"
                    style="background-image:url('/assets/media/svg/shapes/top-green.png" data-bs-theme="light">
                    <!--begin::Title-->
                    <h3 class="card-title align-items-start flex-column text-white pt-15">
                        <span class="fw-bold fs-2x mb-3">Apps Dashboard</span>

                        <div class="fs-4 text-white">
                            <span class="opacity-75">Anda dapat melihat data stok, transaksi dan lainnya disini</span>
                        </div>
                    </h3>
                    <!--end::Title-->
                </div>
                <!--end::Heading-->

                <!--begin::Body-->
                <div class="card-body mt-n20">
                    <!--begin::Stats-->
                    <div class="mt-n20 position-relative">
                        <!--begin::Row-->
                        <div class="row g-3 g-lg-6">
                            <!--begin::Col-->
                            <div class="col-6">
                                <!--begin::Items-->
                                <div class="bg-gray-100 bg-opacity-70 rounded-2 px-6 py-5">
                                    <!--begin::Symbol-->
                                    <div class="symbol symbol-30px me-5 mb-8">
                                        <span class="symbol-label">
                                            <i class="ki-outline ki-user fs-1 text-primary"></i>
                                        </span>
                                    </div>
                                    <!--end::Symbol-->

                                    <!--begin::Stats-->
                                    <div class="m-0">
                                        <!--begin::Number-->
                                        <span class="text-gray-700 fw-bolder d-block fs-2qx lh-1 ls-n1 mb-1"> {{
                                            $user->count(); }} </span>
                                        <!--end::Number-->

                                        <!--begin::Desc-->
                                        <span class="text-gray-500 fw-semibold fs-6">Users</span>
                                        <!--end::Desc-->
                                    </div>
                                    <!--end::Stats-->
                                </div>
                                <!--end::Items-->
                            </div>
                            <!--end::Col-->
                            <!--begin::Col-->
                            <div class="col-6">
                                <!--begin::Items-->
                                <div class="bg-gray-100 bg-opacity-70 rounded-2 px-6 py-5">
                                    <!--begin::Symbol-->
                                    <div class="symbol symbol-30px me-5 mb-8">
                                        <span class="symbol-label">
                                            <i class="ki-outline ki-bank fs-1 text-primary"></i>
                                        </span>
                                    </div>
                                    <!--end::Symbol-->

                                    <!--begin::Stats-->
                                    <div class="m-0">
                                        <!--begin::Number-->
                                        <span class="text-gray-700 fw-bolder d-block fs-2qx lh-1 ls-n1 mb-1"> {{
                                            $farm->count(); }} </span>
                                        <!--end::Number-->

                                        <!--begin::Desc-->
                                        <span class="text-gray-500 fw-semibold fs-6">Farm</span>
                                        <!--end::Desc-->
                                    </div>
                                    <!--end::Stats-->
                                </div>
                                <!--end::Items-->
                            </div>
                            <!--end::Col-->
                            <!--begin::Col-->
                            <div class="col-6">
                                <!--begin::Items-->
                                <div class="bg-gray-100 bg-opacity-70 rounded-2 px-6 py-5">
                                    <!--begin::Symbol-->
                                    <div class="symbol symbol-30px me-5 mb-8">
                                        <span class="symbol-label">
                                            <i class="ki-outline ki-award fs-1 text-primary"></i>
                                        </span>
                                    </div>
                                    <!--end::Symbol-->

                                    <!--begin::Stats-->
                                    <div class="m-0">
                                        <!--begin::Number-->
                                        <span class="text-gray-700 fw-bolder d-block fs-2qx lh-1 ls-n1 mb-1">{{
                                            $kandang->count(); }}</span>
                                        <!--end::Number-->

                                        <!--begin::Desc-->
                                        <span class="text-gray-500 fw-semibold fs-6">Kandang</span>
                                        <!--end::Desc-->
                                    </div>
                                    <!--end::Stats-->
                                </div>
                                <!--end::Items-->
                            </div>
                            <!--end::Col-->
                            <!--begin::Col-->
                            <div class="col-6">
                                <!--begin::Items-->
                                <div class="bg-gray-100 bg-opacity-70 rounded-2 px-6 py-5">
                                    <!--begin::Symbol-->
                                    <div class="symbol symbol-30px me-5 mb-8">
                                        <span class="symbol-label">
                                            <i class="ki-outline ki-parcel fs-1 text-primary"></i>
                                        </span>
                                    </div>
                                    <!--end::Symbol-->

                                    <!--begin::Stats-->
                                    <div class="m-0">
                                        <!--begin::Number-->
                                        <span class="text-gray-700 fw-bolder d-block fs-2qx lh-1 ls-n1 mb-1">{{ $stock
                                            }}</span>
                                        <!--end::Number-->

                                        <!--begin::Desc-->
                                        <span class="text-gray-500 fw-semibold fs-6">Stok</span>
                                        <!--end::Desc-->
                                    </div>
                                    <!--end::Stats-->
                                </div>
                                <!--end::Items-->
                            </div>
                            <!--end::Col-->

                        </div>
                        <!--end::Row-->
                    </div>
                    <!--end::Stats-->
                </div>
                <!--end::Body-->
            </div>
            <!--end::Lists Widget 19-->
        </div>
        <!--end::Col-->

        <div class="col-xl-3 mb-10">
            <div class="card card-flush h-100">
                <!--begin::Header-->
                <div class="card-header pt-5">
                    <!--begin::Title-->
                    <h3 class="card-title text-gray-800">Stok Barang</h3>
                    <!--end::Title-->
                </div>
                <!--end::Header-->

                <!--begin::Body-->
                <div class="card-body pt-5">
                    @foreach($stockByType as $stock)
                    <!--begin::Item-->
                    <div class="d-flex flex-stack">
                        <!--begin::Section-->
                        <div class="text-gray-700 fw-semibold fs-6 me-2">{{ $stock->jenis_barang }}</div>
                        <!--end::Section-->

                        <!--begin::Statistics-->
                        <div class="d-flex align-items-center">
                            <!--begin::Number-->
                            <span class="text-gray-900 fw-bolder fs-6">{{ number_format($stock->total_sisa) }}</span>
                            <!--end::Number-->
                        </div>
                        <!--end::Statistics-->
                    </div>
                    <!--end::Item-->

                    @if(!$loop->last)
                    <!--begin::Separator-->
                    <div class="separator separator-dashed my-3"></div>
                    <!--end::Separator-->
                    @endif
                    @endforeach
                </div>
                <!--end::Body-->
            </div>
        </div>

    </div>

    <div class="row gy-5 g-xl-12" data-select2-id="select2-data-166-ezuu">
        <!--begin::Col-->
        <div class="col-xl-12 mb-5 mb-xl-10" data-select2-id="select2-data-165-4jm3">

            <!--begin::Table Widget 4-->
            <div class="card card-flush h-xl-100">
                <!--begin::Card header-->
                <div class="card-header pt-7">
                    <!--begin::Title-->
                    <h3 class="card-title align-items-start flex-column">
                        <span class="card-label fw-bold text-gray-800">Transaksi Terakhir</span>
                        <span class="text-gray-500 mt-1 fw-semibold fs-6">5 transaksi terbaru</span>
                    </h3>
                    <!--end::Title-->
                </div>
                <!--end::Card header-->

                <!--begin::Card body-->
                <div class="card-body pt-2">
                    <!--begin::Table-->
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-3">
                            <!--begin::Table head-->
                            <thead>
                                <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase gs-0">
                                    <th>ID Transaksi</th>
                                    <th>Tanggal</th>
                                    <th>Farm</th>
                                    <th>Jenis Barang</th>
                                    <th>Nama Barang</th>
                                    <th>Jumlah</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <!--end::Table head-->

                            <!--begin::Table body-->
                            <tbody class="fw-semibold text-gray-600">
                                @foreach($lastTransactions as $transaction)
                                <tr>
                                    <td>
                                        <a href="#" class="text-gray-800 text-hover-primary">{{ strtoupper(substr(strrchr($transaction->id, '-'), 1)) }}</a>
                                    </td>
                                    <td>{{ $transaction->created_at->format('d M Y, H:i') }}</td>
                                    <td>{{ $transaction->farm_name ?? 'N/A' }}</td>
                                    <td>{{ $transaction->jenis_barang }}</td>
                                    <td>{{ $transaction->item_name }}</td>
                                    <td>{{ number_format($transaction->qty, 0, ',', '.') }}</td>
                                    <td>
                                        <span class="badge badge-light-{{ $transaction->status_color }} py-3 px-4 fs-7">{{ $transaction->status }}</span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-icon btn-light btn-active-light-primary toggle h-25px w-25px" data-bs-toggle="tooltip" data-bs-placement="top" title="Lihat Detail">
                                            <i class="ki-outline ki-arrow-right fs-2"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <!--end::Table body-->
                        </table>
                    </div>
                    <!--end::Table-->
                </div>
                <!--end::Card body-->
            </div>
            <!--end::Table Widget 4-->
        </div>
        <!--end::Col-->
    </div>

    {{--
    <!--begin::Row-->
    <div class="row g-10 g-xl-20 mb-5 mb-xl-20">
        <!--begin::Col-->
        <div class="col-xxl-12">
            @include('partials/widgets/engage/_widget-10')
        </div>
        <!--end::Col-->
    </div>
    <!--end::Row--> --}}
</x-default-layout>