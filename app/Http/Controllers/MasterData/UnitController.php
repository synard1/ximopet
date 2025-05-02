<?php

namespace App\Http\Controllers\MasterData;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\DataTables\UnitsDataTable;

class UnitController extends Controller
{
    public function index(UnitsDataTable $dataTable)
    {
        addVendors(['datatables']);

        return $dataTable->render('pages/masterdata.unit.index');
    }
}
