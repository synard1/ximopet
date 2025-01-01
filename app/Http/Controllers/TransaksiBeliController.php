<?php

namespace App\Http\Controllers;

use App\Models\TransaksiBeli;
use Illuminate\Http\Request;
use App\DataTables\Pembelian\DocsDataTable;
use App\DataTables\Pembelian\PakanDataTable;
use App\DataTables\Pembelian\OvkDataTable;

class TransaksiBeliController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function indexDoc(DocsDataTable $dataTable)
    {
        // addJavascriptFile('assets/js/custom/pages/transaksi/pembelian-stok.js');
        return $dataTable->render('pages/pembelian/doc._doc');
    }
    public function indexPakan(PakanDataTable $dataTable)
    {
        // addJavascriptFile('assets/js/custom/pages/transaksi/pembelian-stok.js');
        return $dataTable->render('pages/pembelian._pakan');
    }

    public function indexOvk(OvkDataTable $dataTable)
    {
        // addJavascriptFile('assets/js/custom/pages/transaksi/pembelian-stok.js');
        return $dataTable->render('pages/pembelian._ovk');
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
    public function show(TransaksiBeli $transaksiBeli)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransaksiBeli $transaksiBeli)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TransaksiBeli $transaksiBeli)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransaksiBeli $transaksiBeli)
    {
        //
    }
}
