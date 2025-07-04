<?php

namespace App\Livewire\Superadmin;

use Livewire\Component;
use App\Models\Company;
// use Spatie\Permission\Models\Permission;
use App\Models\Permission;
use Illuminate\Support\Facades\Log;

class CompanyPermissionManager extends Component
{
    protected $listeners = ['showCompanyPermission' => 'showCompanyPermission', 'closePanel' => 'closePanel'];

    public ?Company $company = null;
    public array $selectedPermissions = [];
    public $groupedPermissions = [];
    public bool $showPanel = false;
    public $abilityList = [];
    public $search = '';
    public array $originalSelected = [];
    private array $undoBuffer = [];
    private int $undoExpiresAt = 0;

    /**
     * Livewire lifecycle hook that fires whenever the `search` property is
     * updated from the UI. Useful for debugging and ensuring the data flow
     * between the front-end input and back-end component works correctly.
     */
    public function updatedSearch($value)
    {
        Log::debug('CompanyPermissionManager search updated', [
            'value' => $value,
        ]);
    }

    public function showCompanyPermission($companyId)
    {
        // dd($companyId);
        $this->loadCompany($companyId);
    }

    public function mount()
    {
        // $this->loadPermissions();
    }

    public function loadCompany($companyId)
    {
        $this->company = Company::findOrFail($companyId);
        $this->selectedPermissions = $this->company->allowedPermissions()->pluck('id')->toArray();
        $this->originalSelected   = $this->selectedPermissions;
        $this->showPanel = true;

        $this->loadPermissions();
    }

    public function loadPermissions(): void
    {
        $all = Permission::orderBy('name')->get();

        $abilities = ['access', 'create', 'read', 'update', 'delete', 'import', 'export', 'print'];
        $this->abilityList = $abilities; // for view

        $grouped = [];
        foreach ($all as $perm) {
            [$ability, $module] = explode(' ', $perm->name, 2);
            if (!in_array($ability, $abilities)) {
                $abilities[] = $ability;
            }
            $grouped[$module][$ability] = $perm->id;
        }

        // ensure consistent ability order
        foreach ($grouped as $module => $abilitiesMap) {
            foreach ($abilities as $ability) {
                if (!isset($grouped[$module][$ability])) {
                    $grouped[$module][$ability] = null;
                }
            }
            ksort($grouped[$module]);
        }

        ksort($grouped);
        $this->groupedPermissions = $grouped;
    }

    public function save()
    {
        if (!$this->company) {
            session()->flash('error', 'No company selected');
            return;
        }

        $this->undoBuffer = $this->company->allowedPermissions()->pluck('id')->toArray();
        $this->undoExpiresAt = time() + 15; // 15 sec window
        $this->company->allowedPermissions()->sync($this->selectedPermissions);
        Log::info('Company permissions updated', ['company' => $this->company->id, 'count' => count($this->selectedPermissions)]);
        $this->dispatchBrowserEvent('permission-saved');
    }

    public function selectAll()
    {
        $this->selectedPermissions = Permission::pluck('id')->toArray();
    }

    public function deselectAll()
    {
        $this->selectedPermissions = [];
    }

    // Reload selected permissions from database (reset to saved state)
    public function resetSelections()
    {
        if ($this->company) {
            $this->selectedPermissions = $this->company->allowedPermissions()->pluck('id')->toArray();
        }

        $this->search = '';
    }

    public function closePanel()
    {
        $this->reset(['company', 'selectedPermissions', 'showPanel']);
    }

    public function getFilteredPermissionsProperty()
    {
        $search = trim($this->search);
        Log::info('Filtering permissions', ['search' => $search]);

        // Empty search – return everything
        if ($search === '') {
            return $this->groupedPermissions;
        }

        // Split the search string into individual keywords (by whitespace)
        $keywords = array_filter(preg_split('/\s+/', strtolower($search)));

        $filtered = [];

        foreach ($this->groupedPermissions as $module => $map) {
            $moduleLower = strtolower($module);

            // Check if ALL keywords are present as full words in the module name
            $matchesModule = true;
            foreach ($keywords as $kw) {
                if (!preg_match('/\b' . preg_quote($kw, '/') . '\b/', $moduleLower)) {
                    $matchesModule = false;
                    break;
                }
            }

            // If module name matches, include it and continue
            if ($matchesModule) {
                $filtered[$module] = $map;
                continue;
            }

            // Otherwise, look inside abilities for ANY keyword match
            $matchesAbility = false;
            foreach ($map as $ability => $permId) {
                if (!$permId) {
                    continue;
                }
                $abilityLower = strtolower($ability);
                foreach ($keywords as $kw) {
                    if (preg_match('/\b' . preg_quote($kw, '/') . '\b/', $abilityLower)) {
                        $matchesAbility = true;
                        break 2; // break both foreach loops
                    }
                }
            }

            if ($matchesAbility) {
                $filtered[$module] = $map;
            }
        }

        Log::info('Filtered modules count', ['count' => count($filtered)]);

        return $filtered;
    }

    public function toggleAbility($ability)
    {
        foreach ($this->groupedPermissions as $module => $map) {
            $permId = $map[$ability] ?? null;
            if (!$permId) continue;
            if (in_array($permId, $this->selectedPermissions)) {
                $this->selectedPermissions = array_values(array_diff($this->selectedPermissions, [$permId]));
            } else {
                $this->selectedPermissions[] = $permId;
            }
        }
    }

    public function undo()
    {
        if (!$this->company) return;
        if ($this->undoBuffer && time() < $this->undoExpiresAt) {
            $this->company->allowedPermissions()->sync($this->undoBuffer);
            $this->selectedPermissions = $this->undoBuffer;
            $this->undoBuffer = [];
            session()->flash('success', 'Undo successful');
        }
    }

    public function render()
    {
        return view('livewire.superadmin.company-permission-manager');
    }
}
