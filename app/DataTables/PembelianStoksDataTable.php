<?php

namespace App\DataTables;

use App\Models\Transaksi;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class PembelianStoksDataTable extends DataTable
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
            ->editColumn('created_at', function (Transaksi $transaksi) {
                return $transaksi->created_at->format('d M Y, h:i a');
            })
            ->editColumn('tanggal', function (Transaksi $transaksi) {
                return $transaksi->tanggal->format('d-m-Y');
            })
            ->editColumn('farm_id', function (Transaksi $transaksi) {
                return $transaksi->farms->nama;
            })
            ->editColumn('rekanan_id', function (Transaksi $transaksi) {
                return $transaksi->rekanans->nama;
            })
            // ->editColumn('payload.doc.nama', function (Transaksi $transaksi) {
            //     return $transaksi->payload['doc']['kode'] .' - '.$transaksi->payload['doc']['nama'];
            // })
            ->editColumn('harga', function (Transaksi $transaksi) {
                return $this->formatRupiah($transaksi->harga);
            })
            ->editColumn('sub_total', function (Transaksi $transaksi) {
                return $this->formatRupiah($transaksi->sub_total);
            })
            // ->editColumn('created_at', function (Kandang $kandang) {
            //     return $kandang->created_at->format('d M Y, h:i a');
            // })
            ->addColumn('action', function (Transaksi $transaksi) {
                return view('pages/transaksi.pembelian-stok._actions', compact('transaksi'));
            })
            // ->filterColumn('farm', function($query, $keyword) {
            //     $query->whereHas('farms', function($query) use ($keyword) { // Assuming you have a 'farm' relationship on your model
            //         $query->where('nama', 'like', "%{$keyword}%");
            //     });
            // })
            ->setRowId('id')
            ->rawColumns(['']);
            // ->make(true);
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(Transaksi $model): QueryBuilder
    {
        $query = $model->newQuery();

        // return $model->newQuery();
        $query = $model::where('jenis','Pembelian')
            ->where('jenis_barang','!=','DOC')
            ->orderBy('tanggal', 'DESC')
            ->newQuery();

        return $query;

    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('pembelianStoks-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            // ->dom('Bfrtip')
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            // ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(1)
            ->parameters([
                'scrollX'      =>  true,
                'searching'      =>  false,
            ])
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/transaksi/pembelian-stok/_draw-scripts.js')) . "}");
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('faktur')->searchable(true),
            Column::make('farm_id')->title('Farm')->searchable(true),
            Column::make('tanggal')->title('Tanggal Pembelian')->searchable(true),
            Column::make('rekanan_id')->title('Nama Supplier')->searchable(true),
            // Column::make('payload.doc.nama')->title('Nama DOC')->searchable(true),
            Column::make('qty')->searchable(true),
            Column::make('harga')->searchable(true),
            Column::make('sub_total')->searchable(true),
            // Column::make('periode')->searchable(true),
            Column::make('created_at')->title('Created Date')->addClass('text-nowrap')->searchable(false),
            Column::computed('action')
                // ->addClass('text-end text-nowrap')
                ->exportable(false)
                ->printable(false)
                // ->width(60)
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