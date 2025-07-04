<div class="card" id="companyPermissionCard" style="display: {{ $showPanel ? 'block' : 'none' }};">
    {{-- <div class="card" id="companyPermissionCard" style="display: block;"> --}}
        <div class="card-header">
            <h3 class="card-title">Manage Permissions - {{ $company?->name }}</h3>
            <div class="card-toolbar">
                <button wire:click="closePanel" class="btn btn-sm btn-secondary">Close</button>
                <button wire:click="save" class="btn btn-sm btn-primary ms-2">Save</button>
            </div>
        </div>
        <div class="card-body">
            @if($company)
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-5">
                        <h4>{{ $company->name }}</h4>
                        <p class="text-muted mb-0">Domain: {{ $company->domain }}</p>
                        <p class="text-muted">Package: {{ $company->package ?? '-' }}</p>
                        <div class="mt-4 d-grid gap-2">
                            <button wire:click="selectAll" class="btn btn-light-primary btn-sm">Select All</button>
                            <button wire:click="deselectAll" class="btn btn-light btn-sm">Clear</button>
                            <button wire:click="resetSelections" class="btn btn-light-secondary btn-sm">Reset</button>
                        </div>
                        @if (session()->has('success'))
                        <div class="alert alert-success mt-3">{{ session('success') }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-md-9" style="max-height:65vh; overflow:auto;">
                    <div class="mb-3 d-flex">
                        <input type="text" class="form-control form-control-sm me-2"
                            placeholder="Search module / ability" wire:model.live.debounce.400ms="search">
                        <button class="btn btn-outline-secondary btn-sm" wire:click="resetSelections">Reset</button>
                    </div>
                    @if($isSuperAdmin)
                    <div class="mb-3 d-flex align-items-center">
                        <input type="text" class="form-control form-control-sm me-2" placeholder="New preset name"
                            wire:model.defer="presetName">
                        <button class="btn btn-sm btn-light-primary me-3" wire:click="savePreset">Save as
                            Preset</button>

                        <select class="form-select form-select-sm w-auto me-2" wire:model="selectedPresetId"
                            wire:change="applyPreset($event.target.value)">
                            <option value="0">-- Apply Preset --</option>
                            @foreach($presets as $preset)
                            <option value="{{ $preset['id'] }}">{{ $preset['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <table class="table table-sm table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:25%">Module</th>
                                @foreach($abilityList as $ability)
                                <th class="text-center" style="width:80px">
                                    <input type="checkbox" wire:click="toggleAbility('{{ $ability }}')">
                                    <br>{{ ucfirst($ability) }}
                                </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php($filteredPermissions = $this->filteredPermissions)
                            @forelse($filteredPermissions as $module => $abilities)
                            <tr>
                                <td class="fw-bold">{{ ucwords($module) }}</td>
                                @foreach($abilityList as $ability)
                                @php($permId = $abilities[$ability])
                                @php($isChecked = $permId && in_array($permId, $selectedPermissions))
                                @php($isSystemDefault = $permId && in_array($permId, $systemDefaultIds))
                                @php($cellClass = '')
                                @if($permId)
                                @if($isChecked && $isSystemDefault)
                                @php($cellClass = 'bg-light-primary') {{-- inherited & still allowed --}}
                                @elseif($isChecked && !$isSystemDefault)
                                @php($cellClass = 'bg-light-success') {{-- newly added --}}
                                @elseif(!$isChecked && $isSystemDefault)
                                @php($cellClass = 'bg-light-warning') {{-- removed own default --}}
                                @endif
                                @endif
                                <td class="text-center {{ $cellClass }}">
                                    @if($permId)
                                    <input type="checkbox" class="form-check-input" value="{{ $permId }}"
                                        wire:model="selectedPermissions">
                                    @endif
                                </td>
                                @endforeach
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ count($abilityList)+1 }}" class="text-center text-muted">No permissions
                                    found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div x-data="{show:false}"
                        x-init="window.addEventListener('permission-saved', () => {show=true; setTimeout(()=>show=false,15000);})"
                        x-show="show" x-transition class="position-fixed bottom-0 end-0 m-4">
                        <div class="alert alert-success d-flex align-items-center shadow">
                            <span>Permissions saved</span>
                            <button class="btn btn-link ms-3" wire:click="undo">Undo</button>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="alert alert-info">Select a company to manage permissions.</div>
            @endif
        </div>
    </div>