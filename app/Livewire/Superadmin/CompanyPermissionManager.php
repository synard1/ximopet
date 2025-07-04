<?php

namespace App\Livewire\Superadmin;

use Livewire\Component;
use App\Models\Company;
// use Spatie\Permission\Models\Permission;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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
    public array $systemDefaultIds = [];
    public array $presets = [];
    public string $presetName = '';
    public string $selectedPresetId = '';
    public bool $isSuperAdmin = false;

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
        $this->loadPresets();

        $this->isSuperAdmin = Auth::user()?->hasRole('SuperAdmin');
    }

    public function loadCompany($companyId)
    {
        $this->company = Company::findOrFail($companyId);
        $this->selectedPermissions = $this->company->allowedPermissions()->pluck('id')->toArray();
        $this->originalSelected   = $this->selectedPermissions;

        // Determine system default permissions: assume Administrator role seeded by system
        $adminRole = Role::where('company_id', $this->company->id)
            ->where('name', 'Administrator')
            ->first();

        if ($adminRole) {
            $this->systemDefaultIds = $adminRole->permissions()->pluck('permissions.id')->toArray();
        } else {
            $this->systemDefaultIds = [];
        }

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

    public function loadPresets(): void
    {
        $this->presets = \App\Models\PermissionPreset::orderBy('name')->get()->toArray();
    }

    public function savePreset(): void
    {
        $name = trim($this->presetName);
        if ($name === '') {
            $this->dispatch('error', 'Preset name is required');
            return;
        }

        $preset = \App\Models\PermissionPreset::updateOrCreate(
            ['name' => $name],
            [
                'permission_ids' => $this->selectedPermissions,
                'created_by'    => Auth::id(),
            ]
        );

        $this->presetName = '';
        $this->loadPresets();
        $this->dispatch('success', 'Preset saved');
    }

    public function applyPreset($presetId): void
    {
        $preset = \App\Models\PermissionPreset::find($presetId);
        if (!$preset) return;

        Log::info('Applying permission preset', ['preset' => $presetId, 'count' => count($preset->permission_ids ?? [])]);

        $this->selectedPresetId = $presetId;
        $this->selectedPermissions = array_values($preset->permission_ids ?? []);
    }

    public function updatedSelectedPresetId($value)
    {
        if ($value === '' || $value === '0') return;
        $this->applyPreset($value);
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
        $this->dispatch('success', 'Permissions saved');
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
