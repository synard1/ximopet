<?php

namespace App\Http\Controllers;

use App\Models\AppConfig;
use Illuminate\Http\Request;
use App\Services\ConfigService;


class AppConfigController extends Controller
{
    protected $configService;

    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(AppConfig $appConfig)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AppConfig $appConfig)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AppConfig $appConfig)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AppConfig $appConfig)
    {
        //
    }
}
