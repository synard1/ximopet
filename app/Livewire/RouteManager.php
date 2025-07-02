<?php

namespace App\Livewire;

use App\Models\RoutePermission;
use Illuminate\Support\Facades\Route;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\App;

class RouteManager extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public $route_name;
    public $route_path;
    public $method;
    public $middleware = [];
    public $permission_name;
    public $is_active = true;
    public $description;
    public $editingId;
    public $search = '';
    public $selectedRoutes = [];
    public $selectAll = false;

    protected $rules = [
        'route_name' => 'nullable|string|max:255',
        'route_path' => 'required|string|max:255',
        'method' => 'required|string|in:GET,POST,PUT,PATCH,DELETE',
        'middleware' => 'nullable|array',
        'permission_name' => 'nullable|string|max:255',
        'is_active' => 'boolean',
        'description' => 'nullable|string'
    ];

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function mount()
    {
        $this->syncRoutes();
    }

    public function syncRoutes()
    {
        $routes = collect(Route::getRoutes())->map(function ($route) {
            return [
                'route_name' => $route->getName(),
                'route_path' => $route->uri(),
                'method' => $route->methods()[0],
                'middleware' => $route->middleware(),
            ];
        });

        foreach ($routes as $route) {
            RoutePermission::firstOrCreate(
                [
                    'route_path' => $route['route_path'],
                    'method' => $route['method']
                ],
                [
                    'route_name' => $route['route_name'],
                    'middleware' => $route['middleware'],
                    'is_active' => true
                ]
            );
        }
    }

    public function render()
    {
        $routes = RoutePermission::when($this->search, function ($query) {
            $query->where(function ($q) {
                $q->where('route_name', 'like', '%' . $this->search . '%')
                    ->orWhere('route_path', 'like', '%' . $this->search . '%')
                    ->orWhere('permission_name', 'like', '%' . $this->search . '%');
            });
        })->paginate(10);

        // Get middleware aliases from Kernel
        $kernel = App::make(\Illuminate\Contracts\Http\Kernel::class);
        $middlewareAliases = collect($kernel->getRouteMiddleware())->keys()->toArray();

        // Get unique middleware from existing routes in the database
        $dbMiddleware = RoutePermission::distinct()->pluck('middleware')->flatten()->unique()->filter()->toArray();

        // Combine and sort middleware options
        $allMiddlewareOptions = array_unique(array_merge($middlewareAliases, $dbMiddleware));
        sort($allMiddlewareOptions);

        return view('livewire.route-manager', [
            'routes' => $routes,
            'methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
            'middlewareOptions' => $allMiddlewareOptions
        ]);
    }

    public function save()
    {
        $this->validate();

        $data = [
            'route_name' => $this->route_name,
            'route_path' => $this->route_path,
            'method' => $this->method,
            'middleware' => $this->middleware,
            'permission_name' => $this->permission_name,
            'is_active' => $this->is_active,
            'description' => $this->description
        ];

        if ($this->editingId) {
            RoutePermission::find($this->editingId)->update($data);
        } else {
            RoutePermission::create($data);
        }

        $this->reset();
        session()->flash('message', 'Route permission saved successfully.');
    }

    public function edit($id)
    {
        $route = RoutePermission::find($id);
        $this->editingId = $id;
        $this->route_name = $route->route_name;
        $this->route_path = $route->route_path;
        $this->method = $route->method;
        $this->middleware = $route->middleware;
        $this->permission_name = $route->permission_name;
        $this->is_active = $route->is_active;
        $this->description = $route->description;
    }

    public function delete($id)
    {
        RoutePermission::find($id)->delete();
        session()->flash('message', 'Route permission deleted successfully.');
    }

    public function toggleActive($id)
    {
        $route = RoutePermission::find($id);
        $route->update(['is_active' => !$route->is_active]);
    }

    public function bulkToggleActive()
    {
        if (empty($this->selectedRoutes)) {
            session()->flash('error', 'Please select at least one route.');
            return;
        }

        RoutePermission::whereIn('id', $this->selectedRoutes)
            ->update(['is_active' => !RoutePermission::whereIn('id', $this->selectedRoutes)->first()->is_active]);

        $this->selectedRoutes = [];
        session()->flash('message', 'Routes updated successfully.');
    }

    public function bulkDelete()
    {
        if (empty($this->selectedRoutes)) {
            session()->flash('error', 'Please select at least one route.');
            return;
        }

        RoutePermission::whereIn('id', $this->selectedRoutes)->delete();
        $this->selectedRoutes = [];
        session()->flash('message', 'Routes deleted successfully.');
    }
}
