<?php

namespace App\DataTables;

use App\Models\Item;
use App\Models\StockHistory;
use App\Models\TransaksiJual;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Carbon\Carbon;

class PenjualansDataTable extends DataTable
{
    private function formatRupiah($amount) {
        // Convert the number to a string with two decimal places
        $formattedAmount = number_format($amount, 0, ',', '.');
    
        // Add the currency symbol and return the formatted number
        return "Rp " . $formattedAmount;
    }

    private function formatNumber($amount) {
        // Convert the number to a string with two decimal places
        $formattedAmount = number_format($amount, 0, ',', '.');
    
        // Add the currency symbol and return the formatted number
        return $formattedAmount;
    }
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->editColumn('created_at', function (TransaksiJual $transaksi) {
                return $transaksi->created_at->format('d M Y, h:i a');
            })
            ->editColumn('tanggal_beli', function (TransaksiJual $transaksi) {
                return $transaksi->kelompokTernak->start_date->format('d-M-y');
            })
            ->editColumn('tanggal', function (TransaksiJual $transaksi) {
                return $transaksi->tanggal->format('d-M-y');
            })
            ->editColumn('rekanan_id', function (TransaksiJual $transaksi) {
                // Add a check to prevent division by zero
                return $transaksi->detail->rekanan_id
                    ? $transaksi->detail->rekanan->nama
                    : 'N/A';
            })
            ->editColumn('farm_id', function (TransaksiJual $transaksi) {
                return $transaksi->kelompokTernak->farm->nama;
            })
            ->editColumn('kandang_id', function (TransaksiJual $transaksi) {
                return $transaksi->kelompokTernak->kandang->nama;
            })
            ->editColumn('kelompok_ternak_id', function (TransaksiJual $transaksi) {
                return $transaksi->kelompokTernak->name;
            })
            ->editColumn('berat', function (TransaksiJual $transaksi) {
                return $transaksi->detail->berat;
            })
            ->editColumn('abw', function (TransaksiJual $transaksi) {
                // Add a check to prevent division by zero
                return $transaksi->detail->berat > 0 
                    ? number_format($transaksi->detail->berat / $transaksi->jumlah, 2) 
                    : 'N/A';
            })
            ->editColumn('harga', function (TransaksiJual $transaksi) {
                return $this->formatNumber($transaksi->detail->harga_jual);
            })
            ->editColumn('total', function (TransaksiJual $transaksi) {
                return $this->formatNumber($transaksi->detail->berat * $transaksi->detail->harga_jual);
            })
            ->editColumn('umur', function (TransaksiJual $transaksi) {
                // return $transaksi->details->umur;
                $tanggalMasuk = Carbon::parse($transaksi->kelompokTernak->start_date);
                $tanggalJual = Carbon::parse($transaksi->tanggal);
                $umur = $tanggalMasuk->diffInDays($tanggalJual) - 1;

                $transaksi->detail->update([
                    'umur' => $umur,
                    'updated_at' => Carbon::now()
                ]);
                return $umur;
            })
            ->editColumn('upxj', function (TransaksiJual $transaksi) {
                return $this->formatNumber($transaksi->detail->umur * $transaksi->jumlah);
            })
            ->addColumn('action', function (TransaksiJual $transaksi) {
                if (auth()->user()->hasRole('Operator')) {

                    return view('pages/transaksi.penjualan._actions', compact('transaksi'));
                };
            })
            ->setRowId('id')
            ->rawColumns(['kode']);
            // ->make(true);
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(TransaksiJual $model): QueryBuilder
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
            ->setTableId('penjualans-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            ->parameters([
                'scrollX'      =>  true,
                'scrollY'      => '400px', // Set a fixed height for vertical scrolling
                'scrollCollapse' => true,
                'searching'       =>  false,
                // 'responsive'       =>  true,
                'lengthMenu' => [
                        [ 10, 25, 50, -1 ],
                        [ '10 rows', '25 rows', '50 rows', 'Show all' ]
                ],
                'buttons'      => [
                    [
                        'extend' => 'print',
                        'text' => 'Print',
                        'exportOptions' => [
                            'columns' => ':visible'
                        ],
                        'customize' => 'function (win) {
                            $(win.document.body).find("table").addClass("display").css("font-size", "9px");
                            $(win.document.body).find("tr:nth-child(odd) td").each(function(index){
                                $(this).css("background-color", "#D0D0D0");
                            });
                            $(win.document.body).find("h1").css("text-align", "center");
                        }'
                    ],
                    'export', 
                    'reload',
                    'colvis'
                ],
                'fixedHeader'  => true,

            ])
            // ->dom('Bfrtip')
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            // ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(1)
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/transaksi/penjualan/_draw-scripts.js')) . "}");
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('tanggal_beli')
                ->title('Tanggal Masuk DOC')
                ->visible(false)
                ->searchable(true),
            Column::make('tanggal')
                ->title('Tanggal Penjualan')
                ->searchable(true),
            Column::make('faktur')
                ->title('No Faktur')
                ->searchable(true),
            Column::computed('rekanan_id')
                ->title('Nama Pelanggan')
                ->visible(false)
                ->searchable(true),
            Column::computed('farm_id')->title('Farm')->visible(false),
            Column::computed('kandang_id')->title('Kandang'),
            Column::make('kelompok_ternak_id')->title('Batch DOC'),
            Column::computed('jumlah')
                ->title('Jumlah (Ekor)')
                ->visible(true),
            Column::computed('berat')
                ->title('Berat (Kg)')
                ->visible(false),
            Column::computed('abw')
                ->title('ABW (Kg)')
                ->visible(false),
            Column::computed('harga')
                ->title('Harga /Kg (Rp)')
                ->visible(true),
            Column::computed('total')
                ->title('Total (Rp)')
                ->visible(true),
            Column::computed('umur')
                ->title('Umur Panen')
                ->visible(true),
            Column::computed('upxj')
                ->title('Umur Panen x Jumlah Ayam')
                ->visible(true),
            Column::make('created_at')->title('Created Date')->addClass('text-nowrap')
                ->searchable(false)
                ->visible(false),
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
        return 'Stoks_' . date('YmdHis');
    }
}
