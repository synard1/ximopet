<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\DataTables\FeedPurchaseDataTable;
use App\DataTables\SupplyPurchaseDataTable;
use App\DataTables\SalesDataTable;

class TransactionController extends Controller
{
    public function feedIndex(FeedPurchaseDataTable $dataTable)
    {
        addVendors(['datatables']);
        return $dataTable->render('pages.transaction.feed-purchases.index');
    }

    public function supplyIndex(SupplyPurchaseDataTable $dataTable)
    {
        addVendors(['datatables']);
        return $dataTable->render('pages.transaction.supply-purchases.index');
    }

    public function salesIndex(SalesDataTable $dataTable)
    {
        addVendors(['datatables']);
        return $dataTable->render('pages.transaction.sales.index');
    }
}
