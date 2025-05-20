<?php

namespace App\Console\Commands;

use App\Models\RoutePermission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class SyncRoutes extends Command
{
    protected $signature = 'routes:sync';
    protected $description = 'Sync application routes to the database';

    public function handle()
    {
        $this->info('Starting route sync...');
        $routes = Route::getRoutes();
        $count = 0;

        foreach ($routes as $route) {
            $methods = $route->methods();
            $uri = $route->uri();
            $name = $route->getName();
            $middleware = $route->middleware();

            foreach ($methods as $method) {
                if (in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
                    RoutePermission::updateOrCreate(
                        [
                            'route_path' => $uri,
                            'method' => $method,
                        ],
                        [
                            'route_name' => $name ?? '',
                            'middleware' => $middleware,
                            'is_active' => true,
                        ]
                    );
                    $count++;
                }
            }
        }

        $this->info("Successfully synced {$count} routes to the database.");
    }
}
