<?php

namespace App\Http\Controllers\Pages;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Yajra\DataTables\Facades\DataTables;
use App\DataTables\CompanyDataTable;
use App\DataTables\CompanyUserDataTable;
use App\Models\CompanyUser;

class CompanyController extends Controller
{
    public function index(CompanyDataTable $dataTable)
    {
        addVendors(['datatables']);
        $company = null;
        $isCompanyAdmin = CompanyUser::isCompanyAdmin();
        if ($isCompanyAdmin) {
            $mapping = CompanyUser::getUserMapping();
            $company = $mapping ? $mapping->company : null;
        }
        // Tambahkan path logo untuk preview jika ada
        if ($company && $company->logo) {
            if (str_starts_with($company->logo, 'data:image')) {
                $company->logo_url = $company->logo; // base64
            } else {
                $company->logo_url = \Illuminate\Support\Facades\Storage::url($company->logo);
            }
        } else if ($company) {
            $company->logo_url = null;
        }
        return $dataTable->render('pages.masterdata.company.list', compact('company', 'isCompanyAdmin'));
    }

    public function getData()
    {
        $query = Company::query();

        // If not SuperAdmin, only show user's company
        if (!auth()->user()->hasRole('SuperAdmin')) {
            $query->where('id', auth()->user()->company_id);
        }

        return DataTables::of($query)
            ->addColumn('logo', function ($company) {
                if ($company->logo) {
                    if (str_starts_with($company->logo, 'data:image')) {
                        return '<img src="' . $company->logo . '" alt="' . $company->name . '" class="h-10 w-10 rounded-full">';
                    } else {
                        return '<img src="' . Storage::url($company->logo) . '" alt="' . $company->name . '" class="h-10 w-10 rounded-full">';
                    }
                }
                return '<div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                    <span class="text-gray-500 text-sm">' . substr($company->name, 0, 2) . '</span>
                </div>';
            })
            ->addColumn('status', function ($company) {
                $statusClass = $company->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
                return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $statusClass . '">' .
                    ucfirst($company->status) . '</span>';
            })
            ->addColumn('actions', function ($company) {
                $isSuperAdmin = auth()->user()->hasRole('SuperAdmin');
                $isOwner = $company->id === auth()->user()->company_id;

                $actions = '';

                if ($isSuperAdmin || $isOwner) {
                    $actions .= '<button wire:click="edit(' . $company->id . ')" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>';
                }

                if ($isSuperAdmin) {
                    $actions .= '<button wire:click="delete(' . $company->id . ')" 
                        onclick="confirm(\'Are you sure you want to delete this company?\') || event.stopImmediatePropagation()"
                        class="text-red-600 hover:text-red-900">Delete</button>';
                }

                return $actions;
            })
            ->rawColumns(['logo', 'status', 'actions'])
            ->make(true);
    }

    public function mappingIndex(CompanyUserDataTable $dataTable)
    {
        addVendors(['datatables']);
        return $dataTable->render('pages.masterdata.company.mapping');
    }

    public function update(Request $request, Company $company)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20',
                'address' => 'required|string|max:500',
                'logo' => 'nullable|image|max:5120', // 5MB
            ]);

            // Handle logo upload and convert to base64
            if ($request->hasFile('logo')) {
                $file = $request->file('logo');
                $imageData = file_get_contents($file->getRealPath());
                $base64Image = 'data:image/' . $file->getClientOriginalExtension() . ';base64,' . base64_encode($imageData);
                $validated['logo'] = $base64Image;
            }

            $company->update($validated);

            // Set logo_url for response
            $company->logo_url = $company->logo;

            return response()->json([
                'success' => true,
                'message' => 'Company updated successfully',
                'data' => $company
            ]);
        } catch (\Exception $e) {
            \Log::error('Company update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update company: ' . $e->getMessage()
            ], 500);
        }
    }
}
