<?php

namespace App\DataTables\Pembelian;

use App\Models\LivestockPurchase;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class LivestockDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */

    private function formatRupiah($amount)
    {
        // Convert the number to a string with two decimal places
        $formattedAmount = number_format($amount, 2, ',', '.');

        // Add the currency symbol and return the formatted number
        return "Rp " . $formattedAmount;
    }

    private function formatNumber($amount)
    {
        // Convert the number to a string with two decimal places
        $formattedAmount = number_format($amount, 2, ',', '.');

        // Add the currency symbol and return the formatted number
        return $formattedAmount;
    }

    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('created_at', function (LivestockPurchase $transaksi) {
                return $transaksi->created_at->format('d M Y, h:i a');
            })
            ->editColumn('tanggal', function (LivestockPurchase $transaksi) {
                return $transaksi->tanggal->format('d-m-Y');
            })
            ->editColumn('rekanan_id', function (LivestockPurchase $transaksi) {
                return $transaksi->rekanans->nama ?? '';
            })
            ->editColumn('quantity', function (LivestockPurchase $transaksi) {
                return $transaksi->livestockPurchaseItem->sum('jumlah') ?? '';
            })
            ->editColumn('harga', function (LivestockPurchase $transaksi) {
                $harga = $transaksi->livestockPurchaseItem->first()?->harga_per_ekor ?? null;
                // * $transaksi->livestockPurchaseItem->jumlah;
                return $harga ?? '';
            })
            ->editColumn('sub_total', function (LivestockPurchase $transaksi) {
                $harga = $transaksi->livestockPurchaseItem->first()?->harga_per_ekor ?? null;
                $jumlah = $transaksi->livestockPurchaseItem->sum('jumlah');
                $total = $harga * $jumlah;
                return $this->formatRupiah($total);
            })
            ->editColumn('farm_id', function (LivestockPurchase $transaksi) {
                return $transaksi->farms->nama ?? '';
            })
            ->editColumn('coop_id', function (LivestockPurchase $transaksi) {
                return $transaksi->coops->nama ?? '';
            })
            ->editColumn('kelompok_ternak_id', function (LivestockPurchase $transaksi) {
                return $transaksi->kelompokTernak->name ?? '';
            })
            // ->editColumn('harga', function (LivestockPurchase $transaksi) {
            //     return $this->formatRupiah($transaksi->harga);
            // })

            ->setRowId('id')
            ->rawColumns([''])
            ->filterColumn('rekanan_id', function ($query, $keyword) {
                $query->whereHas('rekanans', function ($q) use ($keyword) {
                    $q->where('nama', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('farm_id', function ($query, $keyword) {
                $query->whereHas('farms', function ($q) use ($keyword) {
                    $q->where('nama', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('coop_id', function ($query, $keyword) {
                $query->whereHas('coops', function ($q) use ($keyword) {
                    $q->where('nama', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('kelompok_ternak_id', function ($query, $keyword) {
                $query->whereHas('kelompokTernak', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            });
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(LivestockPurchase $model): QueryBuilder
    {
        $query = $model->newQuery();

        // return $model->newQuery();
        // $query = $model::with('transaksiDetails')
        //     ->where('jenis','DOC')
        //     ->whereHas('transaksiDetails', function ($query) {
        //         // $query->where('jenis_barang', 'DOC');
        //     })
        //     // ->where('user_id',auth()->user()->id)
        //     ->orderBy('tanggal', 'ASC')
        //     ->newQuery();

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('docs-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            // ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(1)
            ->parameters([
                'scrollX'      =>  true,
                'searching'       =>  true,
                // 'responsive'       =>  true,
                'lengthMenu' => [
                    [10, 25, 50, -1],
                    ['10 rows', '25 rows', '50 rows', 'Show all']
                ],
                'buttons'      => ['export', 'print', 'reload', 'colvis'],
            ])
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/pembelian/doc/_draw-scripts.js')) . "}");
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            // Add a special column to trigger the child row
            // Column::computed('details')
            //     ->title('') // You can set a title if you want
            //     ->addClass('details-control text-center') // Add necessary classes for styling
            //     ->orderable(false)
            //     ->searchable(false)
            //     ->width(60) // Adjust width as needed
            //     ->exportable(false)
            //     ->printable(false),
            Column::make('invoice_number')->searchable(true),
            Column::make('tanggal')->title('Tanggal Pembelian')->searchable(true),
            // Column::make('rekanan_id')->title('Nama Supplier')->searchable(true),
            // Column::make('payload.doc.nama')->title('Nama DOC')->searchable(true),
            Column::make('quantity')->title('Total Ekor')->searchable(false),
            Column::computed('harga')->searchable(true),
            Column::make('sub_total')->searchable(true),
            // Column::make('kelompok_ternak_id')->visible(true)->title('Kelompok Ternak'),
            // Column::make('farm_id')->visible(false)->title('Farm'),
            // Column::make('coop_id')->visible(false)->title('Kandang'),
            Column::make('created_at')->title('Created Date')
                ->visible(false)
                // ->addClass('text-nowrap')
                ->searchable(false)
                ->addClass('text-nowrap details-control'),
            // Column::computed('action')
            //     // ->addClass('text-end text-nowrap')
            //     ->exportable(false)
            //     ->printable(false)
            //     // ->width(60)
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
