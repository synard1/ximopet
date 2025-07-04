<?php

namespace App\DataTables;

use App\Models\User;
use Yajra\DataTables\Html\Column;
use Yajra\DataTables\EloquentDataTable;
use Yajra\DataTables\Services\DataTable;
use Yajra\DataTables\Html\Builder as HtmlBuilder;
use Illuminate\Database\Eloquent\Builder as QueryBuilder;
use App\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class UsersDataTable extends DataTable
{
    /**
     * Build the DataTable class.
     *
     * @param QueryBuilder $query Results from query() method.
     */
    public function dataTable(QueryBuilder $query): EloquentDataTable
    {
        return (new EloquentDataTable($query))
            ->rawColumns(['user', 'last_login_at'])
            ->editColumn('user', function (User $user) {
                // return $user->name;
                return view('pages.apps.user-management.users.columns._user', compact('user'));
            })
            ->editColumn('role', function (User $user) {
                return ucwords($user->roles->first()?->name);
            })
            ->editColumn('last_login_at', function (User $user) {
                return sprintf('<div class="badge badge-light fw-bold">%s</div>', $user->last_login_at ? $user->last_login_at->diffForHumans() : $user->updated_at->diffForHumans());
            })
            ->editColumn('created_at', function (User $user) {
                return $user->created_at->format('d M Y, h:i a');
            })
            ->addColumn('action', function (User $user) {
                return view('pages.apps.user-management.users.columns._actions', compact('user'));
            })
            ->setRowId('id');
    }


    /**
     * Get the query source of dataTable.
     */
    public function query(User $model): QueryBuilder
    {
        $query = $model->newQuery();

        // Include related role relationship for performance
        $query->with('roles');

        // If user is not SuperAdmin
        if (!Auth::user()->hasRole('SuperAdmin')) {
            $superAdminRoleId = Role::whereName('SuperAdmin')->value('id');

            // Get current user's company mapping
            $currentUserMapping = \App\Models\CompanyUser::getUserMapping();

            if ($currentUserMapping && $currentUserMapping->isAdmin) {
                // Company admin should see all users that belong to the same company –
                // either via company_users mapping OR already having company_id set directly on users table
                $companyId = $currentUserMapping->company_id;

                $countPivot = User::whereHas('companyUsers', function ($qq) use ($companyId) {
                    $qq->where('company_id', $companyId);
                })->count();
                Log::debug('[UsersDataTable] Pivot only count', ['company_id' => $companyId, 'count' => $countPivot]);

                $countCompanyId = User::where('company_id', $companyId)->count();
                Log::debug('[UsersDataTable] Direct company_id count', ['company_id' => $companyId, 'count' => $countCompanyId]);

                $query = $query->where(function ($q) use ($companyId) {
                    // Users linked through the pivot table (any active status allowed)
                    $q->whereHas('companyUsers', function ($sub) use ($companyId) {
                        $sub->where('company_id', $companyId);
                    })
                        // OR users whose company_id is already set (fallback for legacy data)
                        ->orWhere('company_id', $companyId);
                });

                // Debug log
                Log::debug('[UsersDataTable] CompanyAdmin Query', [
                    'user_id' => Auth::id(),
                    'company_id' => $companyId,
                    'sql' => $query->toSql(),
                    'bindings' => $query->getBindings(),
                    'result_count' => $query->count(),
                ]);

                // Temporary: log roles of users in company to identify exclusion reason
                $companyUsers = User::where(function ($q) use ($companyId) {
                    $q->whereHas('companyUsers', function ($sub) use ($companyId) {
                        $sub->where('company_id', $companyId);
                    })->orWhere('company_id', $companyId);
                })->with('roles')->get();
                $companyUsers->each(function ($u) {
                    Log::debug('[UsersDataTable] Company user roles', [
                        'user_id' => $u->id,
                        'roles' => $u->roles->pluck('name', 'id')->toArray(),
                    ]);
                });

                return $query;
            } else {
                // If user is not admin, only show themselves
                $selfQuery = $query->where('id', Auth::id())
                    ->whereDoesntHave('roles', function (QueryBuilder $q) use ($superAdminRoleId) {
                        $q->where('id', $superAdminRoleId);
                    });

                Log::debug('[UsersDataTable] Non-Admin Self Query', [
                    'user_id' => Auth::id(),
                    'sql' => $selfQuery->toSql(),
                    'bindings' => $selfQuery->getBindings(),
                    'result_count' => $selfQuery->count(),
                ]);

                return $selfQuery;
            }
        }

        Log::debug('[UsersDataTable] SuperAdmin Query', [
            'user_id' => Auth::id(),
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'result_count' => $query->count(),
        ]);

        return $query;
    }

    /**
     * Optional method if you want to use the html builder.
     */
    public function html(): HtmlBuilder
    {
        return $this->builder()
            ->setTableId('users-table')
            ->columns($this->getColumns())
            ->minifiedAjax()
            ->dom('rt' . "<'row'<'col-sm-12 col-md-5'l><'col-sm-12 col-md-7'p>>",)
            ->addTableClass('table align-middle table-row-dashed fs-6 gy-5 dataTable no-footer text-gray-600 fw-semibold')
            ->setTableHeadClass('text-start text-muted fw-bold fs-7 text-uppercase gs-0')
            ->orderBy(2)
            ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/apps/user-management/users/columns/_draw-scripts.js')) . "}");
    }

    /**
     * Get the dataTable columns definition.
     */
    public function getColumns(): array
    {
        return [
            Column::make('user')->addClass('d-flex align-items-center')->name('name'),
            Column::make('role')->searchable(false),
            Column::make('last_login_at')->title('Last Login'),
            Column::make('created_at')->title('Joined Date')->addClass('text-nowrap'),
            Column::computed('action')
                ->addClass('text-end text-nowrap')
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
        return 'Users_' . date('YmdHis');
    }
}
