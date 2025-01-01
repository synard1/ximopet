<div id="filteredDataContainer">
    @foreach($groupedData as $date => $transactions)
        <div class="date-group" data-date="{{ \Carbon\Carbon::parse($date)->format('d F Y') }}">
            <h3>{{ \Carbon\Carbon::parse($date)->format('d F Y') }}</h3>
            <table class="table table-row-dashed table-row-gray-300 align-middle gs-0 gy-4">
                <thead>
                    <tr class="fw-bold text-muted">
                        <th>Date</th>
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
                            <td>{{ $transaction->item->name }}</td>
                            <td>{{ $transaction->item->category->name }}</td>
                            <td>{{ $transaction->quantity }}</td>
                            <td>{{ $transaction->total_berat }}</td>
                            <td>{{ number_format($transaction->harga, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</div>