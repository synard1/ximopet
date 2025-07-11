<?php

namespace App\DataTables;

use App\Models\CurrentTernak;
use App\Models\TernakDepletion;
use App\Models\KematianTernak;
// use App\Models\KelompokTernak as Ternak;
use App\Models\Livestock;
use App\Models\TernakAfkir;
use App\Models\Kandang;
use App\Models\LivestockDepletion;
use App\Models\TernakJual;
use App\Models\TransaksiJual;
use App\Config\LivestockDepletionConfig;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Carbon\Carbon;

class LivestockDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn()
            ->rawColumns(['name'])
            // ->editColumn('berat_beli', function (Livestock $livestock) {
            //     if ($livestock->berat_beli < 1000) {
            //         return $livestock->berat_beli . ' gram';
            //     } elseif ($livestock->berat_beli < 1000000) {
            //         return number_format($livestock->berat_beli / 1000, 2) . ' Kg';
            //     } else {
            //         return number_format($livestock->berat_beli / 1000000, 2) . ' Ton';
            //     }
            // })
            ->editColumn('quantity', function (Livestock $livestock) {
                return $livestock->currentLivestock->quantity;
            })
            ->editColumn('umur', function (Livestock $livestock) {
                // Calculate the age of the livestock using Carbon
                $tanggalMasuk = Carbon::parse($livestock->start_date);
                $HariIni = Carbon::now();
                $umur = $tanggalMasuk->diffInDays($HariIni) + 1;
                return number_format($umur, 2) . ' Hari';
            })
            ->editColumn('jumlah_mati', function (Livestock $livestock) {
                // Use config normalization for backward compatibility
                $mortalityTypes = [
                    LivestockDepletionConfig::LEGACY_TYPE_MATI,
                    LivestockDepletionConfig::TYPE_MORTALITY
                ];
                $deplesi = LivestockDepletion::where('livestock_id', $livestock->id)
                    ->whereIn('jenis', $mortalityTypes)
                    ->sum('jumlah');
                return $deplesi;
            })
            ->editColumn('jumlah_afkir', function (Livestock $livestock) {
                // Use config normalization for backward compatibility
                $cullingTypes = [
                    LivestockDepletionConfig::LEGACY_TYPE_AFKIR,
                    LivestockDepletionConfig::TYPE_CULLING
                ];
                $deplesi = LivestockDepletion::where('livestock_id', $livestock->id)
                    ->whereIn('jenis', $cullingTypes)
                    ->sum('jumlah');
                return $deplesi;
            })
            ->editColumn('status', function (Livestock $livestock) {
                return $livestock->getStatusLabel();
            })
            // ->editColumn('jumlah_terjual', function (Livestock $livestock) {
            //     $jumlah = TernakJual::where('kelompok_ternak_id',$livestock->id)->sum('quantity');
            //     return $jumlah  ?? '0';
            // })
            // ->editColumn('stok_akhir', function (Livestock $livestock) {
            //     $currentTernak = CurrentTernak::where('kelompok_ternak_id',$livestock->id)->first();
            //     // $deplesi = TernakDepletion::where('ternak_id', $livestock->id)->sum('jumlah_deplesi');
            //     // return $livestock->quantity  ?? '0';

            //     // $populasi_awal = $livestock->populasi_awal;
            //     // $populasi_mati = KematianTernak::where('kelompok_ternak_id', $livestock->id)->sum('quantity');
            //     // $populasi_afkir = TernakAfkir::where('kelompok_ternak_id', $livestock->id)->sum('jumlah');
            //     // $populasi_terjual = TernakJual::where('kelompok_ternak_id', $livestock->id)->sum('quantity');

            //     $jumlah = $currentTernak->quantity;
            //     // $jumlah = $populasi_awal - $populasi_mati - $populasi_afkir - $populasi_terjual;
            //     // $currentTernak->quantity = $jumlah; // Ensure the result is not negative
            //     // $currentTernak->save();

            //     if (config('xolution.ALLOW_NEGATIF_SELLING')){
            //         return $jumlah;
            //     }else{
            //         return max(0, $jumlah); // Ensure the result is not negative
            //     }

            // })
            ->editColumn('name', function (Livestock $livestock) {
                if (auth()->user()->can('read livestock records')) {
                    return '<a href="#" class="text-gray-800 text-hover-primary mb-1" data-kt-action="view_detail_livestock" data-kt-livestock-id="' . $livestock->id . '">' . $livestock->name . '</a>';
                } else {
                    return $livestock->name;
                }
            })

            ->editColumn('start_date', function (Livestock $livestock) {
                return $livestock->start_date->format('d M Y, h:i a');
            })
            ->editColumn('created_at', function (Livestock $livestock) {
                return $livestock->created_at->format('d M Y, h:i a');
            })
            // ->orderColumn('jumlah_mati', function ($query, $order) {
            //     $query->orderBy(
            //         KematianTernak::selectRaw('SUM(quantity)')
            //             ->whereColumn('kelompok_ternak_id', 'kelompok_ternak.id')
            //             ->whereNull('ternak_mati.deleted_at'),
            //         $order
            //     );
            // })
            ->addColumn('action', function (Livestock $livestock) {
                return view('pages.masterdata.livestock._actions', compact('livestock'));
            })
            ->setRowId('id');
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(Livestock $model): QueryBuilder
    {
        $query = $model->newQuery();

        if (auth()->user()->hasRole('Operator')) {
            $query->whereHas('farm.farmOperators', function ($query) {
                $query->where('user_id', auth()->id());
            });
        }

        if (auth()->user()->hasRole(['Administrator', 'Manager', 'Supervisor'])) {
            $query->where('company_id', auth()->user()->company_id);
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('livestocks-table')
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
                    [10, 25, 50, -1],
                    ['10 rows', '25 rows', '50 rows', 'Show all']
                ],
                'buttons'      => ['export', 'print', 'reload', 'colvis'],
            ])
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/masterdata/livestock/_draw-scripts.js')) . "}");
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
            Column::make('name'),
            Column::make('start_date'),
            Column::make('initial_quantity'),
            Column::computed('quantity')
                ->title('Current Quantity')
                ->orderable(true)
                ->orderDataType('custom-quantity'),
            Column::computed('umur'),
            Column::computed('jumlah_mati')
                ->title(trans('content.ternak', [], 'id') . ' Mati')
                ->orderable(true)
                ->orderDataType('custom-jumlah-mati'),
            Column::computed('jumlah_afkir')->title(trans('content.ternak', [], 'id') . ' Afkir'),
            // Column::computed('jumlah_terjual')->title(trans('content.ternak',[],'id').' Terjual'),
            // Column::computed('stok_akhir')->title('Sisa '.trans('content.ternak',[],'id')),
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
        return 'Livestocks_' . date('YmdHis');
    }
}
