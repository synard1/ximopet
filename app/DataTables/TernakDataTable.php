<?php

namespace App\DataTables;

use App\Models\CurrentTernak;
use App\Models\KematianTernak;
use App\Models\KelompokTernak as Ternak;
use App\Models\TernakAfkir;
use App\Models\Kandang;
use App\Models\TernakJual;
use App\Models\TransaksiJual;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Carbon\Carbon;

class TernakDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->rawColumns(['name'])
            // ->editColumn('berat_beli', function (Ternak $ternak) {
            //     if ($ternak->berat_beli < 1000) {
            //         return $ternak->berat_beli . ' gram';
            //     } elseif ($ternak->berat_beli < 1000000) {
            //         return number_format($ternak->berat_beli / 1000, 2) . ' Kg';
            //     } else {
            //         return number_format($ternak->berat_beli / 1000000, 2) . ' Ton';
            //     }
            // })
            ->editColumn('umur', function (Ternak $ternak) {
                // Calculate the age of the livestock using Carbon
                $tanggalMasuk = Carbon::parse($ternak->start_date);
                $HariIni = Carbon::now();
                $umur = $tanggalMasuk->diffInDays($HariIni) + 1;
                return $umur. ' Hari';
            })
            ->editColumn('jumlah_mati', function (Ternak $ternak) {
                $jumlah = KematianTernak::where('kelompok_ternak_id',$ternak->id)->sum('quantity');
                return $jumlah;
            })
            ->editColumn('jumlah_afkir', function (Ternak $ternak) {
                $jumlah = TernakAfkir::where('kelompok_ternak_id',$ternak->id)->sum('jumlah');
                return $jumlah;
            })
            ->editColumn('jumlah_terjual', function (Ternak $ternak) {
                $jumlah = TernakJual::where('kelompok_ternak_id',$ternak->id)->sum('quantity');
                return $jumlah  ?? '0';
            })
            ->editColumn('stok_akhir', function (Ternak $ternak) {
                $currentTernak = CurrentTernak::where('kelompok_ternak_id',$ternak->id)->first();
                // return $ternak->quantity  ?? '0';

                $populasi_awal = $ternak->populasi_awal;
                $populasi_mati = KematianTernak::where('kelompok_ternak_id', $ternak->id)->sum('quantity');
                $populasi_afkir = TernakAfkir::where('kelompok_ternak_id', $ternak->id)->sum('jumlah');
                $populasi_terjual = TernakJual::where('kelompok_ternak_id', $ternak->id)->sum('quantity');

                $jumlah = $populasi_awal - $populasi_mati - $populasi_afkir - $populasi_terjual;
                $currentTernak->quantity = $jumlah; // Ensure the result is not negative
                $currentTernak->save();

                if (config('xolution.ALLOW_NEGATIF_SELLING')){
                    return $jumlah;
                }else{
                    return max(0, $jumlah); // Ensure the result is not negative
                }

            })
            ->editColumn('name', function (Ternak $ternak) {
                return '<a href="#" class="text-gray-800 text-hover-primary mb-1" data-kt-action="view_detail_ternak" data-kt-ternak-id="' . $ternak->id . '">' . $ternak->name . '</a>';
            })
            // ->editColumn('name', function (Ternak $ternak) {
            //     return '<a href="#" class="text-gray-800 text-hover-primary mb-1" data-kt-action="view_detail_ternak" data-kt-ternak-id="' . $ternak->id . '">' . $ternak->name . '</a>';
            // })
            // ->editColumn('name', function (Ternak $ternak) {
            //     return '<a href="#" class="text-gray-800 text-hover-primary mb-1" data-kt-action="view_detail_ternak" data-kt-transaksi-id="' . $ternak->id . '">' . $ternak->name . '</a>';
            // })
            ->editColumn('start_date', function (Ternak $ternak) {
                return $ternak->start_date->format('d M Y, h:i a');
            })
            ->editColumn('created_at', function (Ternak $ternak) {
                return $ternak->created_at->format('d M Y, h:i a');
            })
            ->addColumn('action', function (Ternak $ternak) {
                return view('pages/masterdata.ternak._actions', compact('ternak'));
            })
            ->setRowId('id');
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(Ternak $model): QueryBuilder
    {
        if (auth()->user()->hasRole('Operator')) {
            return $model->newQuery()->whereHas('transaksiBeli.farms.farmOperators', function ($query) {
                $query->where('user_id', auth()->id());
            });
        }
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('ternaks-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(1)
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
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/masterdata/ternak/_draw-scripts.js')) . "}");
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('name'),
            Column::make('start_date'),
            Column::make('populasi_awal'),
            Column::computed('umur'),
            Column::computed('jumlah_mati')->title('Ternak Mati'),
            Column::computed('jumlah_afkir')->title('Ternak Afkir'),
            Column::computed('jumlah_terjual')->title('Ternak Terjual'),
            Column::computed('stok_akhir')->title('Sisa Ternak'),
            Column::make('status'),
            Column::make('created_at')->title('Created Date')->addClass('text-nowrap')->searchable(false)->visible(false),
            Column::computed('action')
                // ->addClass('text-end text-nowrap')
                ->exportable(false)
                ->printable(false)
                ->width(60)
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Ternaks_' . date('YmdHis');
    }
}
