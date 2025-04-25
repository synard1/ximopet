<?php

namespace App\Http\Controllers\MasterData;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ExpeditionController extends Controller
{
    public function index()
    {
        return view('pages.expedition.index');
    }
}
