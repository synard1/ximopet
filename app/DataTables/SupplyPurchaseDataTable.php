<?php

namespace App\DataTables;

// use App\Models\FeedPurchaseBeli as FeedPurchase;
use App\Models\SupplyPurchase;
use App\Models\SupplyPurchaseBatch;
use App\Models\Item;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class SupplyPurchaseDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */

    private function formatRupiah($amount) {
        // Convert the number to a string with two decimal places
        $formattedAmount = number_format($amount, 2, ',', '.');
    
        // Add the currency symbol and return the formatted number
        return "Rp " . $formattedAmount;
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn() // Add this line to include row numbers
            ->editColumn('date', function (SupplyPurchaseBatch $transaction) {
                return $transaction->date->format('d-m-Y');
            })
            ->editColumn('supplier_id', function (SupplyPurchaseBatch $transaction) {
                return $transaction->supplier->name;
            })
            ->editColumn('farm_id', function (SupplyPurchaseBatch $transaction) {
                $firstPurchase = $transaction->supplyPurchases->first();
                return $firstPurchase?->farm?->name ?? '-';
                // return $transaction->supplyPurchases->livestok ?? '';
            })
            ->editColumn('kandang_id', function (SupplyPurchaseBatch $transaction) {
                $firstPurchase = $transaction->supplyPurchases->first();
                return $firstPurchase?->livestok?->kandang?->nama ?? '-';
            })
            ->editColumn('total', function (SupplyPurchaseBatch $transaction) {
                $total = $transaction->supplyPurchases->sum(function ($purchase) {
                    return $purchase->quantity * $purchase->price_per_kg;
                });
            
                return $this->formatRupiah($total);
            })
            ->addColumn('action', function (SupplyPurchaseBatch $transaction) {
                return view('pages.transaction.supply-purchases._actions', compact('transaction'));
            })
                    
            ->setRowId('id')
            ->rawColumns(['action']);
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(SupplyPurchaseBatch $model): QueryBuilder
    {
        $query = $model->newQuery();


        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('supplyPurchasing-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            // ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            // ->orderBy(0, 'desc')  // This will order by the first visible column (tanggal) in descending order
            ->parameters([
                'scrollX'      =>  true,
                'searching'       =>  true,
                // 'responsive'       =>  true,
                'lengthMenu' => [
                        [ 10, 25, 50, -1 ],
                        [ '10 rows', '25 rows', '50 rows', 'Show all' ]
                ],
                'buttons'      => ['export', 'print', 'reload','colvis'],
            ])
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/transaction/supply-purchases/_draw-scripts.js')) . "}");
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::computed('DT_RowIndex', 'No.')
            ->title('No.')
            ->addClass('text-center')
            ->width(50),
            Column::make('date')->title('Tanggal Pembelian')->searchable(true),
            // Column::make('no_sj')->title('No. SJ')->searchable(false),
            Column::make('invoice_number')->title('Invoice')->searchable(true),
            Column::make('supplier_id')->title('Supplier')->searchable(true),
            Column::computed('farm_id')->title('Farm')->searchable(true),
            // Column::make('rekanan_id')->title('Nama Supplier')->searchable(true),
            // Column::make('payload.doc.nama')->title('Nama DOC')->searchable(true),
            // Column::make('qty')->searchable(true),
            // Column::make('harga')->searchable(true),
            Column::make('total')->searchable(false),
            // Column::make('periode')->searchable(true),
            Column::make('created_at')->title('Created Date')->addClass('text-nowrap')->searchable(false)->visible(false),
            Column::computed('action')
                // ->addClass('text-end text-nowrap')
                ->exportable(false)
                ->printable(false)
                ->addClass('text-center')
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Docs_' . date('YmdHis');
    }
}
