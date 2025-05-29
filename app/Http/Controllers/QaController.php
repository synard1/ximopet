<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\QaChecklist;
use Yajra\DataTables\Facades\DataTables;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class QaController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $qaChecklists = QaChecklist::query()
                // ->with(['feature', 'category', 'subcategory', 'testType', 'priority', 'status', 'tester'])
                ->orderBy('created_at', 'desc');

            return DataTables::eloquent($qaChecklists)
                ->addColumn('feature_name', function (QaChecklist $qaChecklist) {
                    return $qaChecklist->feature_name ?? 'N/A';
                })
                ->addColumn('feature_category', function (QaChecklist $qaChecklist) {
                    return $qaChecklist->feature_category ?? 'N/A';
                })
                ->addColumn('feature_subcategory', function (QaChecklist $qaChecklist) {
                    return $qaChecklist->feature_subcategory ?? 'N/A';
                })
                ->addColumn('test_type', function (QaChecklist $qaChecklist) {
                    return $qaChecklist->test_type ?? 'N/A';
                })
                ->addColumn('priority', function (QaChecklist $qaChecklist) {
                    $priority = $qaChecklist->priority ?? 'N/A';
                    $badgeClass = $priority === 'Critical' ? 'danger' : ($priority === 'High' ? 'warning' : ($priority === 'Medium' ? 'info' : 'success'));
                    return '<span class="badge bg-' . $badgeClass . '">' . $priority . '</span>';
                })
                ->addColumn('status', function (QaChecklist $qaChecklist) {
                    return $qaChecklist->status ?? 'N/A';
                })
                ->addColumn('tester_name', function (QaChecklist $qaChecklist) {
                    return $qaChecklist->tester_name ?? 'N/A';
                })
                ->addColumn('test_date', function (QaChecklist $qaChecklist) {
                    return $qaChecklist->test_date->format('F j, Y');
                })
                ->addColumn('url', function (QaChecklist $qaChecklist) {
                    return '<a href="' . $qaChecklist->url . '" target="_blank">View</a>';
                })
                ->addColumn('actions', function (QaChecklist $qaChecklist) {
                    $editUrl = route('administrator.qa.edit', $qaChecklist);
                    $deleteUrl = route('administrator.qa.destroy', $qaChecklist);
                    $csrf = csrf_field();
                    $method = method_field('DELETE');

                    return "
                        <a href=\"{$editUrl}\" class=\"btn btn-sm btn-info\"><i class=\"fa fa-edit\"></i></a>
                        <button type=\"submit\" class=\"btn btn-sm btn-danger\"><i class=\"fa fa-trash\" data-kt-qa-id=\"{$qaChecklist->id}\" data-kt-action=\"delete_row\"></i></button>
                    ";
                })
                // ->addColumn('actions', function (QaChecklist $qaChecklist) {
                //     return view('pages.admin.qa._actions', compact('qaChecklist'));
                // })
                ->rawColumns(['actions', 'url', 'priority'])
                // ->drawCallback("function() {" . file_get_contents(resource_path('views/pages/admin/qa/_draw-scripts.js')) . "}")
                ->toJson();
        }

        return view('pages.admin.qa.index');
    }

    public function create()
    {
        $categories = QaChecklist::getFeatureCategories();
        $subcategories = [];
        $testers = User::whereHas('roles', function ($query) {
            $query->where('name', 'qa-tester');
        })->get();

        return view('pages.admin.qa.create', compact('categories', 'subcategories', 'testers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'feature_name' => 'required|string|max:255',
            'feature_category' => 'required|string|max:255',
            'test_case' => 'required|string',
            'test_type' => 'required|in:CRUD,UI/UX,Functionality,Performance,Security',
            'status' => 'required|in:Passed,Failed,Not Tested',
            'notes' => 'nullable|string',
            'tester_name' => 'required|string|max:255',
            'test_date' => 'required|date',
            'browser' => 'nullable|string',
            'device' => 'nullable|string',
            'error_details' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $qaChecklist = QaChecklist::create($validated);

            DB::commit();

            // Create backup after successful creation, outside transaction
            try {
                // $this->qaBackupService->createBackup('created');
            } catch (\Exception $e) {
                Log::error('Backup creation failed after QA checklist creation: ' . $e->getMessage());
                // Don't throw the error, just log it
            }

            return redirect()->route('administrator.qa.index')
                ->with('success', 'QA checklist created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create QA checklist: ' . $e->getMessage());
            return back()->with('error', 'Failed to create QA checklist. Please try again.');
        }
    }

    public function edit(QaChecklist $qaChecklist)
    {
        $features = Feature::all();
        $categories = Category::all();
        $subcategories = Subcategory::all();
        $testTypes = TestType::all();
        $priorities = Priority::all();
        $statuses = Status::all();
        $testers = User::whereHas('roles', function ($query) {
            $query->where('name', 'qa-tester');
        })->get();

        return view('pages.admin.qa.edit', compact('qaChecklist', 'features', 'categories', 'subcategories', 'testTypes', 'priorities', 'statuses', 'testers'));
    }

    public function destroy(QaChecklist $qaChecklist)
    {
        try {
            DB::beginTransaction();

            $qaChecklist->delete();

            DB::commit();

            // Create backup after successful deletion, outside transaction
            try {
                $this->qaBackupService->createBackup('deleted');
            } catch (\Exception $e) {
                Log::error('Backup creation failed after QA checklist deletion: ' . $e->getMessage());
                // Don't throw the error, just log it
            }

            return redirect()->route('administrator.qa.index')
                ->with('success', 'QA checklist deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete QA checklist: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete QA checklist. Please try again.');
        }
    }
}
