<?php

namespace App\DataTables;

use App\Models\LoginLog;
use Yajra\DataTables\Html\Button;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\Services\DataTable;

class LoginLogsDataTable extends DataTable
{
    public function dataTable($query)
    {
        return datatables()
            ->eloquent($query)
            ->addColumn('user', function ($log) {
                return $log->user ? $log->user->name : 'N/A';
            })
            ->addColumn('created_at', function ($log) {
                return $log->created_at->format('Y-m-d H:i:s');
            });
    }

    public function query(LoginLog $model)
    {
        return $model->newQuery()->with('user')->latest();
    }

    public function html()
    {
        return $this->builder()
            ->setTableId('login-logs-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->orderBy(0, 'desc');
    }

    protected function getColumns()
    {
        return [
            Column::make('user'),
            Column::make('ip_address'),
            Column::make('login_status'),
            Column::make('login_type'),
            Column::make('created_at')->title('Login Time'),
        ];
    }
}
