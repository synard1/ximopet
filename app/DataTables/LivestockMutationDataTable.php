<?php

namespace App\DataTables;

use App\Models\LivestockMutation;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class LivestockMutationDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addIndexColumn() // Add this line to include row numbers

            ->rawColumns(['jumlah'])

            ->editColumn('tanggal', function (LivestockMutation $ternak) {
                return $ternak->tanggal->format('d M Y');
            })
            // ->editColumn('jumlah', function (LivestockMutation $ternak) {
            //     $jumlah = $ternak->mutationItem->first()?->quantity ?? null;
            //     return $jumlah . ' Ekor';
            // })
            // ->editColumn('berat', function (LivestockMutation $ternak) {
            //     $weight = $ternak->mutationItem->first()?->weight ?? null;
            //     return $weight . ' Kg';
            // })
            ->editColumn('source_livestock_id', function (LivestockMutation $ternak) {
                return $ternak->sourceLivestock->name;
            })
            ->editColumn('destination_livestock_id', function (LivestockMutation $ternak) {
                // Jika destination_livestock_id ada, tampilkan nama ternak tujuan
                if ($ternak->destinationLivestock) {
                    return $ternak->destinationLivestock->name;
                }

                // Jika destination_livestock_id kosong, cek pada kolom data (JSON)
                $data = $ternak->data;
                if (is_string($data)) {
                    // Decode jika masih string
                    $data = json_decode($data, true);
                }

                // Cek apakah ada destination_info.coop di data
                if (is_array($data) && isset($data['destination_info']['coop'])) {
                    $coop = $data['destination_info']['coop'];
                    // Tampilkan nama kandang (coop)
                    if (isset($coop['name'])) {
                        // Sertakan info farm jika ada
                        $coopName = $coop['name'];
                        if (isset($coop['farm_name'])) {
                            $coopName .= ' (' . $coop['farm_name'] . ')';
                        }
                        return $coopName;
                    }
                }

                // Fallback jika tidak ada data
                return '-';
            })
            // ->editColumn('id', function (LivestockMutation $ternak) {
            //     return strtoupper(substr(strrchr($ternak->id, '-'), 4));
            // })
            // ->editColumn('ternak_id', function (LivestockMutation $data) {
            //     return $data->livestock->name;
            // })
            // ->editColumn('farm_id', function (KematianTernak $ternak) {
            //     return $ternak->farm->nama;
            // })
            // ->editColumn('coop_id', function (KematianTernak $ternak) {
            //     return $ternak->kandang->nama;
            // })
            ->editColumn('created_at', function (LivestockMutation $ternak) {
                return $ternak->created_at->format('d M Y, h:i a');
            })
            // ->filterColumn('kelompok_ternak_id', function($query, $keyword) {
            //     $query->whereHas('kelompokTernak', function($q) use ($keyword) {
            //         $q->where('name', 'like', "%{$keyword}%");
            //     });
            // })
            // ->filterColumn('farm_id', function($query, $keyword) {
            //     $query->whereHas('farm', function($q) use ($keyword) {
            //         $q->where('nama', 'like', "%{$keyword}%");
            //     });
            // })
            // ->filterColumn('coop_id', function($query, $keyword) {
            //     $query->whereHas('kandang', function($q) use ($keyword) {
            //         $q->where('nama', 'like', "%{$keyword}%");
            //     });
            // })
            ->addColumn('action', function (LivestockMutation $transaction) {

                return view('pages.transaction.livestock-mutation._actions', compact('transaction'));
            })
            ->setRowId('id');
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(LivestockMutation $model): QueryBuilder
    {
        // if (auth()->user()->hasRole('Operator')) {
        //     return $model->newQuery()->whereHas('ternak.farm.farmOperators', function ($query) {
        //         $query->where('user_id', auth()->id());
        //     });
        // }
        return $model->newQuery();
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('livestockMutation-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(1)
            ->parameters([
                'scrollX'      =>  true,
                'searching'    =>  true,
                'responsive'   =>  false,
                'lengthMenu' => [
                    [10, 25, 50, -1],
                    ['10 rows', '25 rows', '50 rows', 'Show all']
                ],
                // 'buttons'      => [
                //     [
                //         'text' => '<i class="fa fa-plus"></i> Add New',
                //         'className' => 'btn btn-primary',
                //         'attr' => [
                //             'data-kt-action' => 'new_kternak'
                //         ]
                //     ],
                //     // ['extend' => 'excel', 'className' => 'btn btn-success', 'text' => '<i class="fa fa-file-excel"></i> Excel'],
                //     ['extend' => 'print', 'className' => 'btn btn-info', 'text' => '<i class="fa fa-print"></i> Print'],
                //     ['extend' => 'colvis', 'className' => 'btn btn-warning', 'text' => '<i class="fa fa-columns"></i> Columns']
                // ],
                'language' => [
                    'search' => 'Search:',
                    'searchPlaceholder' => 'Enter search term...'
                ],
            ])
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/transaction/livestock-mutation/_draw-scripts.js')) . "}");
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
            Column::make('id')->visible(false),
            Column::make('tanggal')->title('Tanggal'),
            Column::make('source_livestock_id')->title('Asal'),
            Column::make('destination_livestock_id')->title('Tujuan'),
            Column::computed('jumlah')->title('Jumlah Ekor'),
            // Column::make('berat')->title('Berat (Kg)'),
            // Column::computed('farm_id')->title('Farm')->visible(false),
            // Column::computed('coop_id')->title('Kandang')->visible(false),
            // Column::make('total_berat'),
            // Column::make('penyebab')->visible(false),
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
        return 'KematianTernaks_' . date('YmdHis');
    }
}
