<?php

namespace App\DataTables;

use App\Models\SupplyStock;
use App\Models\SupplyMutation;
use App\Models\MutationItem;
use App\Models\Item;
use App\Models\StockHistory;
use App\Models\Unit;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;

class SupplyMutationDataTable extends DataTable
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
            ->editColumn('from_farm_id', function (SupplyMutation $stok) {
                return $stok->fromFarm->name;
            })
            ->editColumn('to_farm_id', function (SupplyMutation $stok) {
                return $stok->toFarm->name;
            })
            ->editColumn('quantity', function (SupplyMutation $transaction) {
                $total = 0;
                $unitName = '';
                if ($transaction->supplyMutationItems && $transaction->supplyMutationItems->count() > 0) {
                    $items = $transaction->supplyMutationItems;
                    $total = $items->sum('converted_quantity');
                    $unitIds = $items->pluck('converted_unit_id')->unique();
                    if ($unitIds->count() === 1) {
                        $unit = $items->first()->convertedUnit;
                        $unitName = $unit ? ' ' . $unit->name : '';
                    }
                }
                return formatNumber($total, 2) . $unitName;
            })
            ->editColumn('status', function (SupplyMutation $transaction) {
                // Determine allowed statuses based on config
                $bypassEnabled = config('supply_mutation.workflow.bypass_approval.enabled');
                // Only show these statuses if bypass is enabled
                $statuses = [
                    'draft' => 'Draft',
                    'in_process' => 'Proses',
                    'completed' => 'Selesai',
                    'cancelled' => 'Dibatalkan',
                ];
                $currentStatus = $transaction->status;

                // Cek permission user
                $user = request()->user();
                if (!$user || !$user->can('update supply mutation')) {
                    return $statuses[$currentStatus] ?? $currentStatus;
                }

                $isDisabled = in_array($currentStatus, ['cancelled', 'completed']) ? 'disabled' : '';

                $html = '<div class="d-flex align-items-center">';
                $html .= '<select class="form-select form-select-sm status-select" data-kt-transaction-id="' . $transaction->id . '" data-kt-action="update_status" data-current="' . $currentStatus . '" ' . $isDisabled . '>';

                foreach ($statuses as $value => $label) {
                    $selected = $value === $currentStatus ? 'selected' : '';
                    $optionDisabled = ($currentStatus === 'completed' && $value !== 'completed') ? 'disabled' : '';
                    $optionStyle = ($currentStatus === 'completed' && $value !== 'completed') ? 'style="background-color: #f5f5f5; color: #999;"' : '';
                    $html .= "<option value='{$value}' {$selected} {$optionDisabled} {$optionStyle}>{$label}</option>";
                }

                $html .= '</select>';
                $html .= '</div>';

                return $html;
            })
            ->editColumn('date', function (SupplyMutation $stok) {
                return $stok->date->format('d M Y, h:i a');
            })
            ->editColumn('created_at', function (SupplyMutation $stok) {
                return $stok->created_at->format('d M Y, h:i a');
            })
            ->addColumn('action', function (SupplyMutation $transaction) {
                return view('pages.masterdata.supply._mutation_actions', compact('transaction'));
            })
            ->setRowId('id')
            ->rawColumns(['status']);
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(SupplyMutation $model): QueryBuilder
    {
        // Eager load supplyMutation.supplyMutationItems.convertedUnit agar efisien
        $query = $model->where('deleted_at', null)->with(['supplyMutationItems.convertedUnit'])->newQuery();

        $user = request()->user();
        if ($user && $user->hasRole(['Administrator', 'Manager', 'Supervisor'])) {
            $query->where('company_id', $user->company_id);
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('supplyMutation-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('Bfrtip')
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
            // ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            // ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            // ->orderBy(1)
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/masterdata/supply/_mutation_draw-scripts.js')) . "}");
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
            Column::make('number_full')->title('Nomor')->searchable(true),
            Column::make('date')->title('Tanggal')->searchable(false),
            Column::make('from_farm_id')->title('Asal'),
            Column::make('to_farm_id')->title('Tujuan'),
            Column::computed('quantity')->title('Jumlah'),
            Column::make('status')->title('Status'),
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
