<?php

namespace App\Services;

use Illuminate\Support\Facades\Request;

class MenuService
{
    public function processMenu($menuConfig)
    {
        foreach ($menuConfig as $category => &$categoryData) {
            if (isset($categoryData['items'])) {
                foreach ($categoryData['items'] as &$item) {
                    // Process 'active' conditions
                    if (isset($item['active'])) {
                        if ($item['active'] === 'callback') {
                            // Process special case for dashboard
                            $item['active'] = Request::is('/') || Request::is('dashboard');
                        } elseif (is_array($item['active'])) {
                            // Process array of routes
                            $active = false;
                            foreach ($item['active'] as $route) {
                                if (Request::is($route)) {
                                    $active = true;
                                    break;
                                }
                            }
                            $item['active'] = $active;
                        } else {
                            // Process single route string
                            $item['active'] = Request::is($item['active']);
                        }
                    }

                    // Process translations for labels
                    if ($item['label'] === 'Data Ternak') {
                        $item['label'] = 'Data ' . trans('content.livestocks', [], 'id');
                    } elseif (
                        $item['label'] === 'Data Ternak Afkir' ||
                        $item['label'] === 'Data Ternak Jual' ||
                        $item['label'] === 'Data Ternak Mati'
                    ) {
                        // Extract which type
                        $type = str_replace('Data Ternak ', '', $item['label']);
                        $item['label'] = 'Data ' . trans('content.ternak', [], 'id') . ' ' . $type;
                    } elseif ($item['label'] === 'Penjualan Ternak') {
                        $item['label'] = 'Penjualan ' . trans('content.ternak', [], 'id');
                    }
                }
            }
        }

        return $menuConfig;
    }
}
