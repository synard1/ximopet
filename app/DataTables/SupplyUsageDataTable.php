<?php

namespace App\DataTables;

use App\Models\SupplyUsage;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Config\SupplyUsageBypassConfig;
use App\Helpers\SupplyUsageStatusHelper;

class SupplyUsageDataTable extends DataTable
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
            ->editColumn('farm_id', function (SupplyUsage $usage) {
                return $usage->farm->name;
            })
            ->editColumn('coop_id', function (SupplyUsage $usage) {
                return $usage->coop->name;
            })
            ->editColumn('converted_quantity', function (SupplyUsage $usage) {
                $total = $usage->details->sum(function ($detail) {
                    return $detail->converted_quantity;
                });

                return formatNumber($total, 0);
            })
            ->editColumn('usage_date', function (SupplyUsage $usage) {
                return $usage->usage_date->format('d M Y');
            })
            ->editColumn('status', function (SupplyUsage $usage) {
                $statuses = SupplyUsage::STATUS_LABELS;
                $currentStatus = $usage->status;

                // Check if status can be changed (disabled for certain statuses)
                $isDisabled = in_array($currentStatus, [
                    SupplyUsage::STATUS_CANCELLED,
                    SupplyUsage::STATUS_COMPLETED,
                    SupplyUsage::STATUS_REJECTED
                ]) ? 'disabled' : '';

                // Check if user has update permission or is Supervisor/Manager
                $user = \Illuminate\Support\Facades\Auth::user();
                $role = $user->getRoleNames()->first();
                $allowedOptions = SupplyUsageStatusHelper::getAllowedStatusOptions($role, $currentStatus);

                $html = '<div class="d-flex align-items-center">';
                $html .= '<select class="form-select form-select-sm status-select" data-kt-usage-id="' . $usage->id . '" data-kt-action="update_status" data-current="' . $currentStatus . '" data-original-status="' . $currentStatus . '" ' . $isDisabled . '>';

                foreach ($statuses as $value => $label) {
                    if (!in_array($value, $allowedOptions)) continue;
                    $selected = $value === $currentStatus ? 'selected' : '';
                    $optionDisabled = '';
                    $optionStyle = '';
                    $tooltip = '';
                    if ($value === SupplyUsage::STATUS_COMPLETED && empty($usage->livestock_id)) {
                        // Tidak disable, hanya tambahkan tooltip warning
                        $optionStyle = 'style="background-color: #fff3cd; color: #856404;"';
                        $tooltip = 'data-bs-toggle="tooltip" title="Warning: Livestock belum dipilih. Tidak bisa complete tanpa Livestock."';
                        // Log warning jika user mencoba memilih completed tanpa livestock_id
                        Log::warning('SupplyUsageDataTable: Attempt to select completed without livestock_id', [
                            'usage_id' => $usage->id,
                            'livestock_id' => $usage->livestock_id
                        ]);
                    }
                    $html .= "<option value='{$value}' {$selected} {$optionDisabled} {$optionStyle} {$tooltip}>{$label}</option>";
                }

                $html .= '</select>';
                // Tambahkan script untuk handle error dan reset status
                $html .= '<script>
                (function() {
                    const usageId = "' . $usage->id . '";
                    const selectElement = document.querySelector(\'[data-kt-usage-id="\' + usageId + \'"]\');
                    
                    if (selectElement && !selectElement.hasAttribute("data-listener-added")) {
                        selectElement.setAttribute("data-listener-added", "true");
                        
                        // Store original value on change
                        selectElement.addEventListener("change", function() {
                            this.setAttribute("data-pending-status", this.value);
                        });
                        
                        // Listen for Livewire events
                        document.addEventListener("livewire:init", function() {
                            Livewire.on("supply-usage-error", function(data) {
                                if (data.usageId === usageId) {
                                    const originalStatus = selectElement.getAttribute("data-original-status");
                                    selectElement.value = originalStatus;
                                    console.log("Status reset to:", originalStatus);
                                }
                            });
                            
                            Livewire.on("supply-usage-success", function(data) {
                                if (data.usageId === usageId) {
                                    const pendingStatus = selectElement.getAttribute("data-pending-status");
                                    if (pendingStatus) {
                                        selectElement.setAttribute("data-original-status", pendingStatus);
                                    }
                                }
                            });
                        });
                    }
                })();
                </script>';
                // Interaktif: jika completed dipilih tanpa livestock, tampilkan badge warning kuning
                // if (empty($usage->livestock_id)) {
                //     $html .= '<div class="mt-1"><span class="badge bg-warning text-dark">Warning: Pilih Livestock sebelum menyelesaikan usage!</span></div>';
                // }
                $html .= '</div>';

                return $html;
            })
            ->editColumn('created_at', function (SupplyUsage $usage) {
                return $usage->created_at->format('d M Y, h:i a');
            })
            ->addColumn('action', function (SupplyUsage $transaction) {
                return view('pages.masterdata.supply._usage_actions', compact('transaction'));
            })
            ->setRowId('id')
            ->rawColumns(['status', 'action']);
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(SupplyUsage $model): QueryBuilder
    {
        $query = $model->newQuery();

        if (Auth::user()->hasRole(['Administrator', 'Manager', 'Supervisor'])) {
            $query->where('company_id', Auth::user()->company_id);
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
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/masterdata/supply/_usage_draw-scripts.js')) . "}");
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('DT_RowIndex')->title('#')->searchable(false)->orderable(false),
            Column::make('usage_date')->title('Tanggal Penggunaan')->searchable(false),
            Column::make('farm_id')->title('Asal'),
            Column::make('coop_id')->title('Tujuan'),
            Column::computed('converted_quantity')->title('Jumlah Penggunaan'),
            Column::make('status')->title('Status')->searchable(false),
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
        return 'Supply_Usage_' . date('YmdHis');
    }
}
