<x-default-layout>

    @section('title', 'Laporan Pembelian Supply/OVK - ' . $summary['period'])

    <div class="container-fluid">
        <!-- Report Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-4"
                        style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                        <h2 class="mb-2">📊 LAPORAN PEMBELIAN SUPPLY/OVK</h2>
                        <h4 class="mb-0">{{ $summary['period'] }}</h4>
                        <p class="mb-0 mt-2">
                            <i class="fas fa-calendar-alt"></i> Generated: {{ now()->format('d M Y, H:i') }} WIB
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total Batches</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($summary['total_batches']) }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-boxes fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Total Purchases</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($summary['total_purchases']) }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Total Quantity</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    {{ number_format($summary['total_quantity'], 2) }} Unit
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-pills fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-md-6 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                    Total Value</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    Rp {{ number_format($summary['total_value'], 0, ',', '.') }}
                                </div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Batch Details -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">📦 Detail Batch Pembelian Supply/OVK</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Invoice</th>
                                        <th>Supplier</th>
                                        <th>Status</th>
                                        <th>Total Items</th>
                                        <th>Total Nilai</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($batches as $index => $batch)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ \Carbon\Carbon::parse($batch->date)->format('d-M-Y') }}</td>
                                        <td>{{ $batch->invoice_number ?? 'N/A' }}</td>
                                        <td>{{ $batch->supplier->name ?? 'N/A' }}</td>
                                        <td>
                                            <span
                                                class="badge badge-{{ $batch->status == 'completed' ? 'success' : 'secondary' }}">
                                                {{ ucfirst($batch->status ?? 'draft') }}
                                            </span>
                                        </td>
                                        <td>{{ $batch->supplyPurchases->count() }}</td>
                                        <td class="text-right">
                                            Rp {{ number_format($batch->supplyPurchases->sum(function($purchase) {
                                            return $purchase->quantity * $purchase->price_per_unit;
                                            }) + ($batch->expedition_fee ?? 0), 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Tidak ada data pembelian supply/OVK</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    @push('styles')
    <style>
        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }

        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }

        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }

        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }
    </style>
    @endpush
</x-default-layout>