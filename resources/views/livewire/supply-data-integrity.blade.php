<div>
    <div class="p-6 bg-white rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold mb-4">Supply Data Integrity Check v2.0</h2>

        <!-- Category Selector -->
        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-lg font-semibold">Check Categories</h3>
                <button wire:click="toggleCategorySelector"
                    class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    {{ $showCategorySelector ? 'Hide' : 'Show' }} Categories
                </button>
            </div>

            @if($showCategorySelector)
            <div class="space-y-3">
                <div class="flex space-x-2 mb-3">
                    <button wire:click="selectAllCategories"
                        class="px-3 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600">
                        Select All
                    </button>
                    <button wire:click="deselectAllCategories"
                        class="px-3 py-1 bg-gray-500 text-white rounded text-sm hover:bg-gray-600">
                        Deselect All
                    </button>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach($availableCategories as $key => $label)
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" wire:model="selectedCategories" value="{{ $key }}"
                            class="rounded border-gray-300">
                        <span class="text-sm">{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
                <div class="text-xs text-gray-600 mt-2">
                    Selected: {{ count($selectedCategories) }} / {{ count($availableCategories) }} categories
                </div>
            </div>
            @else
            <div class="text-sm text-gray-600">
                Checking {{ count($selectedCategories) }} of {{ count($availableCategories) }} categories
                @if(count($selectedCategories) < count($availableCategories)) <span class="text-blue-600">(Click "Show
                    Categories" to modify)</span>
                    @endif
            </div>
            @endif
        </div>

        <!-- Action Buttons -->
        <div class="mb-4 flex flex-wrap gap-3">
            <button wire:click="previewInvalidData"
                class="btn btn-primary px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50"
                wire:loading.attr="disabled" @if(empty($selectedCategories)) disabled @endif>
                <span wire:loading.remove>Preview Invalid Data</span>
                <span wire:loading>Running...</span>
            </button>

            <button wire:click="previewChanges"
                class="btn btn-info px-4 py-2 bg-cyan-500 text-white rounded hover:bg-cyan-600 disabled:opacity-50"
                wire:loading.attr="disabled">
                <span wire:loading.remove>Preview Changes</span>
                <span wire:loading>Running...</span>
            </button>

            @if($showPreview)
            <button wire:click="applyChanges"
                class="btn btn-success px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 disabled:opacity-50"
                wire:loading.attr="disabled">
                <span wire:loading.remove>Apply All Changes</span>
                <span wire:loading>Processing...</span>
            </button>
            @endif

            @if($showConfirmation)
            <button wire:click="runIntegrityCheck"
                class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 disabled:opacity-50"
                wire:loading.attr="disabled">
                <span wire:loading.remove>Delete {{ $invalidStocksCount }} Invalid Records</span>
                <span wire:loading>Processing...</span>
            </button>
            @endif
        </div>

        @if($showPreview)
        <div class="mb-4">
            <h3 class="text-xl font-semibold mb-2">Preview Changes</h3>
            <div class="space-y-4">
                @foreach($previewData as $change)
                <div class="p-4 rounded-lg border bg-white shadow-sm">
                    <div class="font-semibold mb-2">
                        @if($change['type'] === 'current_supply_mismatch')
                        <span class="text-blue-700">🔄 CurrentSupply Mismatch</span>
                        @elseif($change['type'] === 'quantity_mismatch')
                        <span class="text-orange-700">🔄 Quantity Mismatch</span>
                        @elseif($change['type'] === 'conversion_mismatch')
                        <span class="text-purple-700">🔄 Conversion Mismatch</span>
                        @elseif($change['type'] === 'mutation_quantity_mismatch')
                        <span class="text-pink-700">🔄 Mutation Quantity Mismatch</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-600 mb-3">{{ $change['message'] }}</p>
                    <div class="grid grid-cols-2 gap-4"
                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="p-3 bg-red-50 rounded" style="background: #fef2f2; border-radius: 0.5rem;">
                            <h4 class="font-semibold text-red-700 mb-2" style="color: #b91c1c;">Before</h4>
                            <div class="space-y-1">
                                @foreach($change['before'] as $key => $value)
                                <div class="text-sm">
                                    <span class="font-medium">{{ str_replace('_', ' ', ucfirst($key)) }}:</span>
                                    <span class="text-red-600" style="color: #dc2626;">{{ $value }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="p-3 bg-green-50 rounded" style="background: #f0fdf4; border-radius: 0.5rem;">
                            <h4 class="font-semibold text-green-700 mb-2" style="color: #15803d;">After</h4>
                            <div class="space-y-1">
                                @foreach($change['after'] as $key => $value)
                                <div class="text-sm">
                                    <span class="font-medium">{{ str_replace('_', ' ', ucfirst($key)) }}:</span>
                                    <span class="text-green-600" style="color: #16a34a;">{{ $value }}</span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        @if($error)
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            <p class="font-bold">Error:</p>
            <p>{{ $error }}</p>
        </div>
        @endif

        @if($showConfirmation)
        <div class="mb-4 p-4 bg-yellow-100 border border-yellow-400 text-yellow-700 rounded">
            <p class="font-bold">Warning:</p>
            <p>Found {{ $invalidStocksCount }} invalid supply stock records. Click the red button above to delete these
                records and recalculate stock levels.</p>
        </div>
        @endif

        @if($deletedStocksCount > 0)
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            <p class="font-bold">Success:</p>
            <p>Successfully deleted {{ $deletedStocksCount }} invalid records and recalculated stock levels.</p>
        </div>
        @endif

        <!-- Quick Fix Buttons -->
        @php
        $hasCurrentSupplyMismatch = collect($logs)->contains(fn($log) => $log['type'] === 'current_supply_mismatch');
        $hasMissingCurrentSupply = collect($logs)->contains(fn($log) => $log['type'] === 'missing_current_supply');
        $hasQuantityMismatch = collect($logs)->contains(fn($log) => $log['type'] === 'quantity_mismatch');
        $hasConversionMismatch = collect($logs)->contains(fn($log) => $log['type'] === 'conversion_mismatch');
        $hasMutationQuantityMismatch = collect($logs)->contains(fn($log) => $log['type'] ===
        'mutation_quantity_mismatch');
        @endphp

        @if($hasCurrentSupplyMismatch || $hasMissingCurrentSupply || $hasQuantityMismatch || $hasConversionMismatch ||
        $hasMutationQuantityMismatch)
        <div class="mb-4 p-4 bg-blue-50 rounded-lg">
            <h3 class="text-lg font-semibold mb-3 text-blue-800">Quick Fix Actions</h3>
            <div class="flex flex-wrap gap-2">
                @if($hasCurrentSupplyMismatch)
                <button wire:click="fixAllCurrentSupplyMismatch"
                    class="btn btn-warning px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">
                    Fix All CurrentSupply Mismatch
                </button>
                @endif
                @if($hasMissingCurrentSupply)
                <button wire:click="createMissingCurrentSupplyRecords"
                    class="btn btn-warning px-3 py-2 bg-indigo-500 text-white rounded hover:bg-indigo-600 text-sm">
                    Create Missing CurrentSupply Records
                </button>
                @endif
                @if($hasQuantityMismatch)
                <button wire:click="fixAllQuantityMismatch"
                    class="btn btn-warning px-3 py-2 bg-orange-500 text-white rounded hover:bg-orange-600 text-sm">
                    Fix All Quantity Mismatch
                </button>
                @endif
                @if($hasConversionMismatch)
                <button wire:click="fixAllConversionMismatch"
                    class="btn btn-warning px-3 py-2 bg-purple-500 text-white rounded hover:bg-purple-600 text-sm">
                    Fix All Conversion Mismatch
                </button>
                @endif
                @if($hasMutationQuantityMismatch)
                <button wire:click="fixAllMutationQuantityMismatch"
                    class="btn btn-warning px-3 py-2 bg-pink-500 text-white rounded hover:bg-pink-600 text-sm">
                    Fix All Mutation Quantity Mismatch
                </button>
                @endif
            </div>
        </div>
        @endif

        @if(count($logs) > 0)
        <div class="mt-4">
            <h3 class="text-xl font-semibold mb-2">Check Results:</h3>
            <div class="space-y-4">
                @foreach($logs as $log)
                <div class="p-4 rounded-lg border
                            @if($log['type'] === 'invalid_stock') bg-yellow-50 border-yellow-200
                            @elseif($log['type'] === 'current_supply_mismatch') bg-blue-50 border-blue-200
                            @elseif($log['type'] === 'missing_current_supply') bg-indigo-50 border-indigo-200
                            @elseif($log['type'] === 'orphaned_current_supply') bg-purple-50 border-purple-200
                            @elseif($log['type'] === 'deleted_stock') bg-red-50 border-red-200
                            @elseif($log['type'] === 'recalculation') bg-green-50 border-green-200
                            @elseif($log['type'] === 'missing_stock') bg-blue-50 border-blue-200
                            @elseif($log['type'] === 'quantity_mismatch') bg-orange-50 border-orange-200
                            @elseif($log['type'] === 'conversion_mismatch') bg-purple-50 border-purple-200
                            @elseif($log['type'] === 'mutation_quantity_mismatch') bg-pink-50 border-pink-200
                            @elseif($log['type'] === 'status_integrity_issue') bg-red-50 border-red-200
                            @elseif($log['type'] === 'master_data_issue') bg-gray-50 border-gray-200
                            @elseif($log['type'] === 'mutation_item_invalid_stock') bg-yellow-50 border-yellow-200
                            @else bg-gray-50 border-gray-200
                            @endif">

                    <div class="font-semibold flex items-center gap-2 flex-wrap">
                        @if($log['type'] === 'invalid_stock')
                        <span class="text-yellow-700">⚠️ Invalid Stock Found</span>
                        @php
                        $canRestore =
                        app()->make(\App\Livewire\SupplyDataIntegrity::class)->canRestore($log['data']['source_type'],
                        $log['data']['source_id']);
                        @endphp
                        @if($canRestore)
                        <button
                            wire:click="restoreRecord('{{ $log['data']['source_type'] }}', '{{ $log['data']['source_id'] }}')"
                            class="ml-2 px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 text-xs">
                            Restore Data
                        </button>
                        @endif
                        @elseif($log['type'] === 'current_supply_mismatch')
                        <span class="text-blue-700">🔄 CurrentSupply Mismatch</span>
                        @elseif($log['type'] === 'missing_current_supply')
                        <span class="text-indigo-700">➕ Missing CurrentSupply</span>
                        @elseif($log['type'] === 'orphaned_current_supply')
                        <span class="text-purple-700">🏴‍☠️ Orphaned CurrentSupply</span>
                        @elseif($log['type'] === 'deleted_stock')
                        <span class="text-red-700">🗑️ Stock Deleted</span>
                        @elseif($log['type'] === 'recalculation')
                        <span class="text-green-700">📊 Stock Recalculated</span>
                        @elseif($log['type'] === 'missing_stock')
                        <span class="text-blue-700">🛑 Missing Stock</span>
                        <button
                            wire:click="restoreStock('{{ $log['data']['type'] ?? $log['data']['source_type'] ?? 'purchase' }}', '{{ $log['data']['id'] }}')"
                            class="btn btn-danger ml-2 px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-xs">
                            Restore Stock
                        </button>
                        @elseif($log['type'] === 'quantity_mismatch')
                        <span class="text-orange-700">🔄 Quantity Mismatch</span>
                        @elseif($log['type'] === 'conversion_mismatch')
                        <span class="text-purple-700">🔄 Conversion Mismatch</span>
                        @elseif($log['type'] === 'mutation_quantity_mismatch')
                        <span class="text-pink-700">🔄 Mutation Quantity Mismatch</span>
                        @elseif($log['type'] === 'status_integrity_issue')
                        <span class="text-red-700">⚡ Status Integrity Issue</span>
                        @elseif($log['type'] === 'master_data_issue')
                        <span class="text-gray-700">🔗 Master Data Issue</span>
                        @elseif($log['type'] === 'mutation_item_invalid_stock')
                        <span class="text-yellow-700">🧬 Mutation Item Invalid Stock</span>
                        @elseif($log['type'] === 'info')
                        <span class="text-blue-700">ℹ️ Info</span>
                        @endif

                        @if(isset($log['data']['id']) && isset($log['data']['model_type']))
                        <button
                            wire:click="loadAuditTrail('{{ $log['data']['model_type'] }}', '{{ $log['data']['id'] }}')"
                            class="ml-2 px-3 py-1 bg-gray-500 text-white rounded hover:bg-gray-600 text-xs">
                            View History
                        </button>
                        @endif
                    </div>

                    <p class="mt-1">{{ $log['message'] }}</p>

                    @if(isset($log['reasons']) && count($log['reasons']) > 0)
                    <div class="mt-2">
                        <p class="font-semibold text-sm text-red-600">Reasons for invalidity:</p>
                        <ul class="list-disc list-inside text-sm text-red-600 mt-1">
                            @foreach($log['reasons'] as $reason)
                            <li>{{ $reason }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    @if(isset($log['data']))
                    <div class="mt-2 text-sm">
                        <details>
                            <summary class="cursor-pointer text-gray-600 hover:text-gray-800">
                                View Details
                            </summary>
                            <div class="mt-2 p-2 bg-white rounded border">
                                <pre
                                    class="text-xs overflow-x-auto">{{ json_encode($log['data'], JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </details>
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @elseif(!$isRunning)
        <div class="text-gray-500 italic">
            No integrity issues found or check not run yet.
        </div>
        @endif

        @if($showAuditTrail)
        <div class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-4xl relative max-h-screen overflow-y-auto">
                <button wire:click="hideAuditTrail"
                    class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 text-xl">✖</button>
                <h3 class="text-lg font-bold mb-4">Data History & Audit Trail</h3>
                <div class="mb-2 text-xs text-gray-500">
                    <div>Model Type: {{ $selectedAuditModelType }}</div>
                    <div>Model ID: {{ $selectedAuditModelId }}</div>
                    <div>Audit Records: {{ count($auditTrails) }}</div>
                </div>
                <div class="overflow-x-auto max-h-96">
                    <table class="min-w-full text-xs">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-2 py-1 text-left">Time</th>
                                <th class="px-2 py-1 text-left">User</th>
                                <th class="px-2 py-1 text-left">Action</th>
                                <th class="px-2 py-1 text-left">Details</th>
                                <th class="px-2 py-1 text-left">Rollback</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($auditTrails as $audit)
                            <tr class="border-b">
                                <td class="px-2 py-1">{{ $audit->created_at->format('Y-m-d H:i:s') }}</td>
                                <td class="px-2 py-1">{{ $audit->user?->name ?? '-' }}</td>
                                <td class="px-2 py-1">
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                                        {{ $audit->action }}
                                    </span>
                                </td>
                                <td class="px-2 py-1">
                                    <details>
                                        <summary class="cursor-pointer text-blue-600 hover:text-blue-800">View Changes
                                        </summary>
                                        <div class="mt-1 space-y-2">
                                            <div class="text-xs">
                                                <div class="font-semibold text-red-700">Before:</div>
                                                <pre
                                                    class="bg-red-50 p-2 rounded text-xs overflow-x-auto">{{ json_encode($audit->before_data, JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                            <div class="text-xs">
                                                <div class="font-semibold text-green-700">After:</div>
                                                <pre
                                                    class="bg-green-50 p-2 rounded text-xs overflow-x-auto">{{ json_encode($audit->after_data, JSON_PRETTY_PRINT) }}</pre>
                                            </div>
                                        </div>
                                    </details>
                                </td>
                                <td class="px-2 py-1">
                                    <button wire:click="rollbackAudit('{{ $audit->id }}')"
                                        class="px-2 py-1 bg-yellow-500 text-white rounded hover:bg-yellow-600 text-xs">
                                        Rollback
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>