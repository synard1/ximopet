<?php

namespace App\DataTables;

use App\Models\CompanyUser;
use App\Models\User;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class CompanyUserDataTable extends DataTable
{
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->addColumn('user', function ($mapping) {
                return $mapping->user ? $mapping->user->name . ' (' . $mapping->user->email . ')' : '-';
            })
            ->addColumn('company', function ($mapping) {
                return $mapping->company ? $mapping->company->name : '-';
            })
            ->addColumn('isAdmin', function ($mapping) {
                return $mapping->isAdmin ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>';
            })
            ->addColumn('status', function ($mapping) {
                $statusClass = $mapping->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $statusClass . '">' . ucfirst($mapping->status) . '</span>';
            })
            ->addColumn('actions', function ($mapping) {
                return '<button class="btn btn-sm btn-primary me-2" onclick="editMapping(' . $mapping->id . ')">Edit</button>' .
                    '<button class="btn btn-sm btn-danger" onclick="deleteMapping(' . $mapping->id . ')">Delete</button>';
            })
            ->rawColumns(['isAdmin', 'status', 'actions']);
    }

    public function query(CompanyUser $model): QueryBuilder
    {
        return $model->newQuery()->with(['user', 'company']);
    }

    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('companyuser-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(1);
    }

    public function getColumns(): array
    {
        return [
            Column::make('user')->title('User'),
            Column::make('company')->title('Company'),
            Column::make('isAdmin')->title('Admin'),
            Column::make('status')->title('Status'),
            Column::computed('actions')
                ->exportable(false)
                ->printable(false)
                ->width(80)
                ->addClass('text-center')
                ->title('Actions'),
        ];
    }

    protected function filename(): string
    {
        return 'CompanyUser_' . date('YmdHis');
    }
}
