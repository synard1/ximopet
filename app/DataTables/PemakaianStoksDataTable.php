<?php

namespace App\DataTables;

use App\Models\StokHistory;
use App\Models\TransaksiBeli as Transaksi;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Carbon\Carbon;
use Illuminate\Http\Request;


class PemakaianStoksDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */

    private function formatRupiah($amount)
    {
        // Convert the number to a string with two decimal places
        // $formattedAmount = number_format($amount, 2, ',', '.');

        // Add the currency symbol and return the formatted number
        return "Rp " . $amount;
    }

    public function dataTable(QueryBuilder $query, Request $request): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('created_at', function (Transaksi $transaksi) {
                return $transaksi->created_at->format('d M Y, h:i a');
            })
            // ->editColumn('tanggal', function (StokMutasi $stokMutasi) {
            //     // $tanggal = Carbon::parse($stokMutasi->tanggal);
            //     // $tanggal->format('d-m-Y');
            //     // return $tanggal;
            //         return $stokMutasi->TransaksiDetail->tanggal->format('Y-m-d');

            // })
            // ->filterColumn('tanggal_pembelian', function($query, $keyword) {
            //     $query->whereHas('TransaksiDetail', function($query) use ($keyword) { // Assuming you have a 'farm' relationship on your model
            //         $query->where('tanggal', 'like', "%{$keyword}%");
            //     });
            // })
            // ->filter(function ($query) use ($request) {
            //     if ($request->has('search') && $request->get('search')['value'] != '') {
            //         $searchTerm = $request->get('search')['value'];

            //         $query->where(function ($q) use ($searchTerm) {
            //             $q->whereHas('TransaksiDetail', function ($subquery) use ($searchTerm) {
            //                 $subquery->where('tanggal', 'like', "%$searchTerm%");
            //                 // You can add more 'orWhere' conditions on TransaksiDetail columns here
            //             });
            //             // ->orWhereHas('farms', function ($subquery) use ($searchTerm) {
            //             //     $subquery->where('nama', 'like', "%$searchTerm%");
            //             //     // Add more 'orWhere' conditions on 'farms' columns if needed
            //             // })
            //             // ->orWhereHas('coops', function ($subquery) use ($searchTerm) {
            //             //     $subquery->where('nama', 'like', "%$searchTerm%");
            //             //     // Add more 'orWhere' conditions on 'farms' columns if needed
            //             // });
            //             // Add more 'orWhereHas' conditions for other relationships if needed
            //         });
            //     }
            // })
            ->editColumn('tanggal', function (Transaksi $transaksi) {
                // $tanggal = Carbon::parse($stokMutasi->tanggal);
                // $tanggal->format('d-m-Y');
                // return $tanggal;
                return $transaksi->tanggal->format('d-m-Y');
            })
            ->editColumn('farm_id', function (Transaksi $transaksi) {
                return $transaksi->farms->nama ?? 'N/A';
            })
            ->editColumn('coop_id', function (Transaksi $transaksi) {
                return $transaksi->coops->nama ?? 'N/A';
            })
            ->editColumn('id', function (Transaksi $transaksi) {
                // return $stokMutasi->id;
                $parts = explode('-', $transaksi->id);
                return end($parts); // Get the last part after splitting by '-'

            })
            ->editColumn('qty', function (Transaksi $transaksi) {
                return $transaksi->stokHistory()->sum('stok_keluar');
                // return $transaksi->transaksiDetail->reduce(function ($carry, $detail) {
                //     return $carry + ($detail->stokHistory->qty ?? 0);
                // }, 0);
            })
            ->editColumn('stok_awal', function (Transaksi $transaksi) {
                return $transaksi->stokHistory()->sum('stok_awal');
                // return $transaksi->transaksiDetail->reduce(function ($carry, $detail) {
                //     return $carry + ($detail->stokHistory->stok_awal ?? 0);
                // }, 0);
            })
            ->editColumn('stok_akhir', function (Transaksi $transaksi) {
                return $transaksi->stokHistory()->sum('stok_akhir');
                // return $transaksi->transaksiDetail->reduce(function ($carry, $detail) {
                //     return $carry + ($detail->stokHistory->stok_akhir ?? 0);
                // }, 0);
            })
            // ->editColumn('payload.doc.nama', function (StokMutasi $stokMutasi) {
            //     return $stokMutasi->payload['doc']['kode'] .' - '.$stokMutasi->payload['doc']['nama'];
            // })
            // ->editColumn('harga', function (StokMutasi $stokMutasi) {
            //     return $this->formatRupiah($stokMutasi->harga);
            // })
            // ->editColumn('sub_total', function (StokMutasi $stokMutasi) {
            //     return $this->formatRupiah($stokMutasi->sub_total);
            // })
            // ->editColumn('created_at', function (Kandang $kandang) {
            //     return $kandang->created_at->format('d M Y, h:i a');
            // })
            ->addColumn('action', function (Transaksi $transaksi) {
                return view('pages/transaksi.pemakaian-stok._actions', compact('transaksi'));
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
        $query = $model::with(['farms', 'rekanans', 'items', 'transaksiDetail', 'stokHistory'])
            ->where('jenis', 'Pemakaian')
            // ->orderBy('tanggal', 'DESC')
            ->newQuery();

        return $query;
    }

    // public function query(StokMutasi $model): QueryBuilder
    // {
    //     $query = $model->newQuery();

    //     // return $model->newQuery();
    //     $query = $model::with(['farms','rekanans','items','transaksiDetail','transaksi'])
    //         ->where('histori_stok.jenis','Pemakaian')
    //         ->groupBy('transaksi_details.transaksi_id')
    //         ->join('transaksi_details', 'histori_stok.transaksi_detail_id', '=', 'transaksi_details.id')
    //         ->select('histori_stok.jenis','transaksi_details.tanggal')
    //         ->newQuery();

    //     return $query;

    // }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('pemakaianStoks-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            // ->setTableId('pemakaianStoks-table')
            // ->columns($this->getColumns())
            // ->minifiedAjax()
            // ->dom('Bfrtip')
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            // ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            // // ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer')
            // ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(0)
            // ->parameters([
            //     // 'scrollX'      =>  true,
            //     // 'searching'     => false,
            //     'lengthMenu' => [
            //             [ 10, 25, 50, -1 ],
            //             [ '10 rows', '25 rows', '50 rows', 'Show all' ]
            //     ],
            //     'buttons'      => ['export', 'print', 'reload','colvis'],
            // ])
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/transaksi/pemakaian-stok/_draw-scripts.js')) . "}");
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('id')->searchable(false),
            Column::make('farm_id')->title('Farm'),
            Column::make('coop_id')->title('Kandang'),
            // Column::computed('tanggal_pembelian')->title('Tanggal Pembelian')->searchable(true),
            Column::make('tanggal')->title('Tanggal Pemakaian'),
            // Column::make('rekanan_id')->title('Nama Supplier')->searchable(true),
            // Column::make('item_id')->title('Nama Item')->searchable(true),
            // Column::make('qty')->title('Terpakai'),
            // Column::make('harga')->searchable(true),
            Column::make('stok_awal')->title('Stok Awal'),
            Column::make('qty')->title('Stok Terpakai'),
            Column::make('stok_akhir')->title('Stok Akhir'),
            // Column::make('sisa')->searchable(true),
            // Column::make('sub_total')->searchable(true),
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
