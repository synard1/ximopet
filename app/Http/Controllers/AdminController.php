<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function qaIndex()
    {
        return view('pages.admin.qa.index');
        // addVendors(['datatables']);

        // return $dataTable->render('pages.livestock.mutation.index');
    }

    public function qaTodoIndex()
    {
        return view('pages.admin.qa.todoIndex');
        // addVendors(['datatables']);

        // return $dataTable->render('pages.livestock.mutation.index');
    }

    public function routeIndex()
    {
        return view('pages.admin.routes.index');
        // addVendors(['datatables']);

        // return $dataTable->render('pages.livestock.mutation.index');
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
    public function show(Admin $admin)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Admin $admin)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Admin $admin)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Admin $admin)
    {
        //
    }

    public function downloadAttachment($commentId, $attachmentIndex)
    {
        $comment = \App\Models\QaTodoComment::findOrFail($commentId);
        $attachment = $comment->attachments[$attachmentIndex] ?? null;

        if (!$attachment) {
            return back()->with('error', 'Attachment not found.');
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->download(
            $attachment['path'],
            $attachment['name']
        );
    }
}
