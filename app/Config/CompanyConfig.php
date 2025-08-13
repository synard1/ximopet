<?php

namespace App\Config;

use App\Config\company\CompanyConfigService as Service;

class CompanyConfig
{
    public static function __callStatic($name, $arguments)
    {
        if (method_exists(Service::class, $name)) {
            return Service::$name(...$arguments);
        }

        throw new \BadMethodCallException("Method {$name} does not exist on CompanyConfigService");
    }
}
