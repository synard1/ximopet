<?php

namespace App\Http\Controllers\Transaksi;

use App\Http\Controllers\Controller;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use App\DataTables\DocsDataTable;
use App\DataTables\DocsDataTable;

class TransaksiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function docIndex(DocsDataTable $dataTable)
    {
        return $dataTable->render('pages/transaksi.pembelian-doc.list');
    }

    public function stokIndex(DocsDataTable $dataTable)
    {
        return $dataTable->render('pages/transaksi.pembelian-doc.list');
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
    public function show(Transaksi $transaksi)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaksi $transaksi)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaksi $transaksi)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaksi $transaksi)
    {
        //
    }
}
