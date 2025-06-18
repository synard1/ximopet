<?php

namespace App\DataTables;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Support\Facades\Storage;

class CompanyDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('logo', function ($company) {
                if ($company->logo && str_starts_with($company->logo, 'data:image')) {
                    return '<div class="position-relative">
                        <img src="' . $company->logo . '" 
                            alt="' . $company->name . '" 
                            style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; cursor: pointer;"
                            onclick="showImagePreview(this.src)"
                            onmouseover="showImagePreview(this.src)"
                            onmouseout="hideImagePreview()">
                        <div id="imagePreview" class="position-fixed d-none" 
                            style="z-index: 9999; background: rgba(0,0,0,0.8); padding: 10px; border-radius: 5px;">
                            <img src="" style="max-width: 300px; max-height: 300px;">
                        </div>
                    </div>
                    <script>
                        function showImagePreview(src) {
                            const preview = document.getElementById("imagePreview");
                            preview.querySelector("img").src = src;
                            preview.classList.remove("d-none");
                            preview.style.top = event.clientY + "px";
                            preview.style.left = event.clientX + "px";
                        }
                        function hideImagePreview() {
                            document.getElementById("imagePreview").classList.add("d-none");
                        }
                    </script>';
                }
                return '<div style="width: 40px; height: 40px; border-radius: 50%; background-color: #e5e7eb; display: flex; align-items: center; justify-content: center;">
                    <span style="color: #6b7280; font-size: 12px;">' . substr($company->name, 0, 2) . '</span>
                </div>';
            })
            ->addColumn('status', function ($company) {
                $statusClass = $company->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $statusClass . '">' .
                    ucfirst($company->status) . '</span>';
            })
            ->addColumn('action', function (Company $company) {
                return view('pages.masterdata.company._actions', compact('company'));
            })
            // ->addColumn('actions', function ($company) {
            //     $isSuperAdmin = auth()->user()->hasRole('SuperAdmin');
            //     $isOwner = $company->id === auth()->user()->company_id;

            //     $actions = '';

            //     if ($isSuperAdmin || $isOwner) {
            //         $actions .= '<button wire:click="edit(' . $company->id . ')" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>';
            //     }

            //     if ($isSuperAdmin) {
            //         $actions .= '<button wire:click="delete(' . $company->id . ')" 
            //             onclick="confirm(\'Are you sure you want to delete this company?\') || event.stopImmediatePropagation()"
            //             class="text-red-600 hover:text-red-900">Delete</button>';
            //     }

            //     return $actions;
            // })
            ->rawColumns(['logo', 'status', 'action'])
            ->setRowId('id');
    }

    /**
     * Get the query source of dataTable.
     */
    public function query(Company $model): QueryBuilder
    {
        $query = $model->newQuery();

        // If not SuperAdmin, only show user's company
        if (!auth()->user()->hasRole('SuperAdmin')) {
            $query->where('id', auth()->user()->company_id);
        }

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('company-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(1)
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/masterdata/company/_draw-scripts.js')) . "}");

        // ->drawCallback("function() {
        //     KTMenu.createInstances();
        // }");
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('logo')->title('Logo'),
            Column::make('name')->title('Name'),
            Column::make('email')->title('Email'),
            // Column::make('domain')->title('Domain'),
            Column::make('status')->title('Status'),
            Column::computed('action')
                ->exportable(false)
                ->printable(false)
                ->width(60)
                ->addClass('text-center')
                ->title('Actions'),
        ];
    }

    /**
     * Get the filename for export.
     */
    protected function filename(): string
    {
        return 'Company_' . date('YmdHis');
    }
}
