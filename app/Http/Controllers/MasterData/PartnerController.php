<?php

namespace App\Http\Controllers\MasterData;

use Illuminate\Http\Request;
use App\DataTables\ExpeditionDataTable;
use App\Http\Controllers\Controller;

class PartnerController extends Controller
{
    public function expeditionIndex(ExpeditionDataTable $dataTable)
    {

        addVendors(['datatables']);

        return $dataTable->render('pages.masterdata.expedition.list');
    }
}
