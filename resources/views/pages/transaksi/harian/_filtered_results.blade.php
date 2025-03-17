<div id="filteredDataContainer">
    @foreach($groupedData as $date => $transactions)
        @php
            $formattedDate = \Carbon\Carbon::parse($date)->format('d F Y');
            $totalQuantity = $transactions->sum('quantity');
            $totalBerat = $transactions->sum('total_berat');
            $totalHarga = $transactions->sum(fn($t) => $t->quantity * $t->harga);
        @endphp

        <div class="date-group" data-date="{{ $formattedDate }}">
            <h3>{{ $formattedDate }}</h3>
            <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                <thead>
                    <tr class="fw-bold text-muted">
                        <th>Date</th>
                        <th>Kandang</th>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Total Berat</th>
                        <th>Harga</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $transaction)
                        <tr>
                            <td>{{ $transaction->transaksiHarian->tanggal->format('Y-m-d') }}</td>
                            <td>{{ $transaction->transaksiHarian->kandang->nama }}</td>
                            <td>{{ $transaction->item->name }}</td>
                            <td>{{ $transaction->item->category->name }}</td>
                            <td>{{ $transaction->quantity }}</td>
                            <td>{{ $transaction->total_berat }}</td>
                            <td>{{ number_format($transaction->harga, 2) }}</td>
                        </tr>
                    @endforeach
                    <!-- Total Row -->
                    <tr class="fw-bold text-dark">
                        <td colspan="4" class="text-end">Total:</td>
                        <td>{{ $totalQuantity }}</td>
                        <td>{{ $totalBerat }}</td>
                        <td>{{ number_format($totalHarga, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach
</div>
