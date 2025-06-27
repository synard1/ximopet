<?php

namespace App\TenantFinder;

use Spatie\Multitenancy\Models\Tenant;
use Spatie\Multitenancy\TenantFinder\TenantFinder;
use Illuminate\Http\Request;

class DomainTenantFinder extends TenantFinder
{
    public function findForRequest(Request $request): ?Tenant
    {
        $host = $request->getHost();

        // Extract subdomain if exists
        if (strpos($host, '.') !== false) {
            $subdomain = explode('.', $host)[0];
            return Tenant::where('domain', $subdomain)->first();
        }

        // For local development, use the first tenant
        if (in_array($host, ['localhost', '127.0.0.1', 'demo51.test'])) {
            return Tenant::first();
        }

        return Tenant::where('domain', $host)->first();
    }
}
