<div>
    <div class="p-6 bg-white rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold mb-4">Livestock Data Integrity Check</h2>
        <p class="text-sm text-gray-600 mb-6">Mengecek dan memperbaiki integritas data livestock termasuk relasi dengan
            CurrentLivestock</p>

        <div class="mb-4 space-x-4">
            <button wire:click="previewInvalidData"
                class="btn btn-primary px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50"
                wire:loading.attr="disabled">
                <span wire:loading.remove>Preview Invalid Data</span>
                <span wire:loading>Running...</span>
            </button>

            <button wire:click="previewChanges"
                class="btn btn-info px-4 py-2 bg-cyan-500 text-white rounded hover:bg-cyan-600 disabled:opacity-50"
                wire:loading.attr="disabled">
                <span wire:loading.remove>Preview Changes</span>
                <span wire:loading>Running...</span>
            </button>

            <button wire:click="previewPurchaseItemBatchMismatches"
                class="btn btn-warning px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 disabled:opacity-50"
                wire:loading.attr="disabled">
                <span wire:loading.remove>Check Purchase Item & Batch Mismatches</span>
                <span wire:loading>Running...</span>
            </button>

            <button wire:click="checkPriceDataIntegrity"
                class="btn btn-secondary px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600 disabled:opacity-50"
                wire:loading.attr="disabled">
                <span wire:loading.remove>Check Price Data Integrity</span>
                <span wire:loading>Running...</span>
            </button>

            @if($missingCurrentLivestockCount > 0)
            <button wire:click="previewCurrentLivestockChanges"
                class="btn btn-info px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 disabled:opacity-50 mr-2"
                wire:loading.attr="disabled">
                <span wire:loading.remove>Preview CurrentLivestock Changes</span>
                <span wire:loading>Loading Preview...</span>
            </button>
            <button wire:click="fixMissingCurrentLivestock"
                class="btn btn-success px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 disabled:opacity-50"
                wire:loading.attr="disabled">
                <span wire:loading.remove>Fix {{ $missingCurrentLivestockCount }} Missing CurrentLivestock</span>
                <span wire:loading>Processing...</span>
            </button>
            @endif

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
                <span wire:loading.remove">Delete {{ ($invalidStocksCount ?? 0) + ($invalidBatchesCount ?? 0) }} Invalid
                    Records</span>
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
                        @if($change['type'] === 'quantity_mismatch')
                        <span class="text-orange-700">🔄 Quantity Mismatch</span>
                        @elseif($change['type'] === 'conversion_mismatch')
                        <span class="text-purple-700">🔄 Conversion Mismatch</span>
                        @elseif($change['type'] === 'mutation_quantity_mismatch')
                        <span class="text-pink-700">🔄 Mutation Quantity Mismatch</span>
                        @elseif($change['type'] === 'empty_source')
                        <span class="text-red-700">⚠️ Empty Source (Source type/ID kosong)</span>
                        @elseif($change['type'] === 'purchase_item_batch_mismatch')
                        <span class="text-yellow-700">⚠️ Purchase Item & Batch Mismatch</span>
                        @elseif($change['type'] === 'missing_current_livestock')
                        <span class="text-blue-700">🏠 Missing CurrentLivestock</span>
                        @elseif($change['type'] === 'orphaned_current_livestock')
                        <span class="text-red-700">🗑️ Orphaned CurrentLivestock</span>
                        @elseif($change['type'] === 'create_current_livestock')
                        <span class="text-green-700">➕ Create CurrentLivestock</span>
                        @elseif($change['type'] === 'remove_orphaned_current_livestock')
                        <span class="text-red-700">🗑️ Remove Orphaned CurrentLivestock</span>
                        @elseif($change['type'] === 'price_data_missing')
                        <span class="text-purple-700">💰 Missing Price Data</span>
                        @elseif($change['type'] === 'price_calculation_mismatch')
                        <span class="text-purple-700">💰 Price Calculation Mismatch</span>
                        @elseif($change['type'] === 'livestock_price_aggregation_issue')
                        <span class="text-purple-700">💰 Price Aggregation Issue</span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-600 mb-3">
                        @if(isset($change['message']))
                        {{ $change['message'] }}
                        @elseif(isset($change['description']))
                        {{ $change['description'] }}
                        @endif

                        @if(isset($change['livestock_name']) && $change['livestock_name'])
                        <br><strong>Livestock:</strong> {{ $change['livestock_name'] }}
                        @endif
                        @if(isset($change['farm_name']) && $change['farm_name'])
                        <strong>Farm:</strong> {{ $change['farm_name'] }}
                        @endif
                        @if(isset($change['coop_name']) && $change['coop_name'])
                        <strong>Coop:</strong> {{ $change['coop_name'] }}
                        @endif
                    </p>

                    @if($change['type'] === 'empty_source' && isset($change['details']))
                    <div class="mb-2">
                        <table class="text-xs border rounded bg-gray-50">
                            <tbody>
                                @if($change['details']['batch_number'])
                                <tr>
                                    <td class="font-semibold pr-2">Batch Number</td>
                                    <td>{{ $change['details']['batch_number'] }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td class="font-semibold pr-2">Livestock ID</td>
                                    <td>{{ $change['details']['livestock_id'] }}</td>
                                </tr>
                                @if($change['details']['livestock_name'])
                                <tr>
                                    <td class="font-semibold pr-2">Livestock Name</td>
                                    <td>{{ $change['details']['livestock_name'] }}</td>
                                </tr>
                                @endif
                                @if($change['details']['mutation_id'])
                                <tr>
                                    <td class="font-semibold pr-2">Mutation ID</td>
                                    <td>{{ $change['details']['mutation_id'] }}</td>
                                </tr>
                                @endif
                                @if($change['details']['livestock_purchase_item_id'])
                                <tr>
                                    <td class="font-semibold pr-2">Purchase Item ID</td>
                                    <td>{{ $change['details']['livestock_purchase_item_id'] }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td class="font-semibold pr-2">Created At</td>
                                    <td>{{ $change['details']['created_at'] }}</td>
                                </tr>
                                <tr>
                                    <td class="font-semibold pr-2">Updated At</td>
                                    <td>{{ $change['details']['updated_at'] }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4"
                        style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="p-3 bg-red-50 rounded" style="background: #fef2f2; border-radius: 0.5rem;">
                            <h4 class="font-semibold text-red-700 mb-2" style="color: #b91c1c;">Before</h4>
                            <div class="space-y-1">
                                @if(isset($change['before']))
                                @foreach($change['before'] as $key => $value)
                                <div class="text-sm">
                                    <span class="font-medium">{{ str_replace('_', ' ', ucfirst($key)) }}:</span>
                                    <span class="text-red-600" style="color: #dc2626;">
                                        @if(is_bool($value))
                                        {{ $value ? 'Yes' : 'No' }}
                                        @else
                                        {{ $value }}
                                        @endif
                                    </span>
                                </div>
                                @endforeach
                                @else
                                <div class="text-sm text-gray-500">No previous data available.</div>
                                @endif
                            </div>
                        </div>
                        <div class="p-3 bg-green-50 rounded" style="background: #f0fdf4; border-radius: 0.5rem;">
                            <h4 class="font-semibold text-green-700 mb-2" style="color: #15803d;">After</h4>
                            <div class="space-y-1">
                                @if(isset($change['after']))
                                @foreach($change['after'] as $key => $value)
                                <div class="text-sm">
                                    <span class="font-medium">{{ str_replace('_', ' ', ucfirst($key)) }}:</span>
                                    <span class="text-green-600" style="color: #16a34a;">
                                        @if(is_bool($value))
                                        {{ $value ? 'Yes' : 'No' }}
                                        @else
                                        {{ $value }}
                                        @endif
                                    </span>
                                </div>
                                @endforeach
                                @else
                                <div class="text-sm text-gray-500">No updated data available.</div>
                                @endif
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
            <p>Found {{ ($invalidStocksCount ?? 0) + ($invalidBatchesCount ?? 0) }} invalid livestock batch/stock
                records and {{ $missingCurrentLivestockCount ?? 0 }} missing CurrentLivestock records.
                Click the buttons above to fix these issues.</p>
        </div>
        @endif

        @if($deletedStocksCount > 0)
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            <p class="font-bold">Success:</p>
            <p>Successfully deleted {{ $deletedStocksCount }} invalid records and recalculated stock levels.</p>
        </div>
        @endif

        @php
        $hasQuantityMismatch = collect($logs)->contains(fn($log) => $log['type'] === 'quantity_mismatch');
        $hasConversionMismatch = collect($logs)->contains(fn($log) => $log['type'] === 'conversion_mismatch');
        $hasMutationQuantityMismatch = collect($logs)->contains(fn($log) => $log['type'] ===
        'mutation_quantity_mismatch');
        $hasPurchaseItemBatchMismatch = collect($logs)->contains(fn($log) => $log['type'] ===
        'purchase_item_batch_mismatch');
        $hasMissingCurrentLivestock = collect($logs)->contains(fn($log) => $log['type'] ===
        'missing_current_livestock');
        $hasOrphanedCurrentLivestock = collect($logs)->contains(fn($log) => $log['type'] ===
        'orphaned_current_livestock');
        $hasPriceDataIssues = collect($logs)->contains(fn($log) => in_array($log['type'], [
        'price_data_missing', 'price_calculation_mismatch', 'livestock_price_aggregation_issue'
        ]));
        @endphp

        @if($hasQuantityMismatch)
        <button wire:click="fixAllQuantityMismatch"
            class="btn btn-warning mb-4 px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600 text-sm">
            Perbaiki Semua Quantity Mismatch
        </button>
        @endif
        @if($hasConversionMismatch)
        <button wire:click="fixAllConversionMismatch"
            class="btn btn-warning mb-4 px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600 text-sm">
            Perbaiki Semua Conversion Mismatch
        </button>
        @endif
        @if($hasMutationQuantityMismatch)
        <button wire:click="fixAllMutationQuantityMismatch"
            class="btn btn-warning mb-4 px-4 py-2 bg-pink-500 text-white rounded hover:bg-pink-600 text-sm">
            Perbaiki Semua Mutation Quantity Mismatch
        </button>
        @endif
        @if($hasPurchaseItemBatchMismatch)
        <button wire:click="fixPurchaseItemBatchMismatches"
            class="btn btn-warning mb-4 px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 text-sm">
            Perbaiki Semua Purchase Item & Batch Mismatch
        </button>
        @endif
        @if($hasMissingCurrentLivestock || $hasOrphanedCurrentLivestock)
        <button wire:click="fixMissingCurrentLivestock"
            class="btn btn-success mb-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 text-sm">
            Perbaiki CurrentLivestock Records
        </button>
        @endif
        @if($hasPriceDataIssues)
        <button wire:click="fixPriceDataIntegrity"
            class="btn btn-success mb-4 px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600 text-sm">
            Perbaiki Price Data Integrity Issues
        </button>
        @endif

        @if(count($logs) > 0)
        <div class="mt-4">
            <h3 class="text-xl font-semibold mb-2">Check Results:</h3>
            <div class="space-y-4">
                @foreach($logs as $log)
                <div class="p-4 rounded-lg border
                            @if($log['type'] === 'invalid_stock') bg-yellow-50 border-yellow-200
                            @elseif($log['type'] === 'deleted_stock') bg-red-50 border-red-200
                            @elseif($log['type'] === 'recalculation') bg-green-50 border-green-200
                            @elseif($log['type'] === 'missing_stock') bg-blue-50 border-blue-200
                            @elseif($log['type'] === 'missing_current_livestock') bg-blue-50 border-blue-200
                            @elseif($log['type'] === 'orphaned_current_livestock') bg-red-50 border-red-200
                            @elseif($log['type'] === 'fix_missing_current_livestock') bg-green-50 border-green-200
                            @elseif($log['type'] === 'remove_orphaned_current_livestock') bg-orange-50 border-orange-200
                            @else bg-gray-50 border-gray-200
                            @endif">

                    <div class="font-semibold flex items-center gap-2">
                        @if($log['type'] === 'invalid_stock')
                        <span class="text-yellow-700">⚠️ Invalid Stock Found</span>
                        @php
                        $canRestore =
                        app()->make(\App\Livewire\DataIntegrity\LivestockDataIntegrity::class)->canRestore($log['data']['source_type'],
                        $log['data']['source_id']);
                        @endphp
                        @if($canRestore)
                        <button
                            wire:click="restoreRecord('{{ $log['data']['source_type'] }}', '{{ $log['data']['source_id'] }}')"
                            class="ml-2 px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 text-xs">
                            Restore Data
                        </button>
                        @endif
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
                        @elseif($log['type'] === 'missing_current_livestock')
                        <span class="text-blue-700">🏠 Missing CurrentLivestock</span>
                        @elseif($log['type'] === 'orphaned_current_livestock')
                        <span class="text-red-700">🗑️ Orphaned CurrentLivestock</span>
                        @elseif($log['type'] === 'fix_missing_current_livestock')
                        <span class="text-green-700">✅ CurrentLivestock Created</span>
                        @elseif($log['type'] === 'remove_orphaned_current_livestock')
                        <span class="text-orange-700">🗑️ Orphaned CurrentLivestock Removed</span>
                        @elseif($log['type'] === 'price_data_missing')
                        <span class="text-purple-700">💰 Missing Price Data</span>
                        @elseif($log['type'] === 'price_calculation_mismatch')
                        <span class="text-purple-700">💰 Price Calculation Mismatch</span>
                        @elseif($log['type'] === 'livestock_price_aggregation_issue')
                        <span class="text-purple-700">💰 Price Aggregation Issue</span>
                        @elseif($log['type'] === 'price_data_fixed')
                        <span class="text-green-700">✅ Price Data Fixed</span>
                        @elseif($log['type'] === 'price_mismatch_fixed')
                        <span class="text-green-700">✅ Price Mismatch Fixed</span>
                        @elseif($log['type'] === 'livestock_price_fixed')
                        <span class="text-green-700">✅ Livestock Price Fixed</span>
                        @elseif($log['type'] === 'invalid_batch' || $log['type'] === 'invalid_batch_source' ||
                        $log['type'] === 'cannot_fix_empty_source' || $log['type'] === 'fix_single_failed')
                        <button
                            wire:click="fixSingleInvalidRecord('{{ $log['data']['id'] ?? $log['data']['batch_id'] ?? '' }}')"
                            class="ml-2 px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600 text-xs">
                            Perbaiki Data Ini
                        </button>
                        @endif
                        @if($log['type'] === 'cannot_fix_empty_source' || $log['type'] === 'fix_single_failed')
                        <span class="text-xs text-red-600 ml-2">Tidak bisa diperbaiki otomatis, cek data sumber!</span>
                        @endif
                        @if(isset($log['data']['id']) && isset($log['data']['model_type']))
                        <button
                            wire:click="loadAuditTrail('{{ $log['data']['model_type'] }}', '{{ $log['data']['id'] }}')"
                            class="ml-2 px-3 py-1 bg-gray-500 text-white rounded hover:bg-gray-600 text-xs">
                            Lihat Riwayat
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
                                <pre class="text-xs">{{ json_encode($log['data'], JSON_PRETTY_PRINT) }}</pre>
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
            <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-3xl relative">
                <button wire:click="hideAuditTrail"
                    class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">✖</button>
                <h3 class="text-lg font-bold mb-4">Riwayat Perbaikan Data</h3>
                <div class="mb-2 text-xs text-gray-500">
                    <div>model_type: {{ $selectedAuditModelType }}</div>
                    <div>model_id: {{ $selectedAuditModelId }}</div>
                    <div>Audit count: {{ count($auditTrails) }}</div>
                </div>
                <div class="overflow-x-auto max-h-96">
                    <table class="min-w-full text-xs">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-2 py-1">Waktu</th>
                                <th class="px-2 py-1">User</th>
                                <th class="px-2 py-1">Aksi</th>
                                <th class="px-2 py-1">Ringkasan</th>
                                <th class="px-2 py-1">Rollback</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($auditTrails as $audit)
                            <tr class="border-b">
                                <td class="px-2 py-1">{{ $audit['created_at'] ?? '-' }}</td>
                                <td class="px-2 py-1">{{ $audit['user']['name'] ?? '-' }}</td>
                                <td class="px-2 py-1">{{ $audit['action'] ?? '-' }}</td>
                                <td class="px-2 py-1">
                                    <details>
                                        <summary class="cursor-pointer text-blue-600">Lihat Detail</summary>
                                        <div class="mt-1">
                                            <div class="font-semibold text-xs">Before:</div>
                                            <pre
                                                class="bg-red-50 p-2 rounded text-xs">{{ json_encode($audit['before_data'] ?? [], JSON_PRETTY_PRINT) }}</pre>
                                            <div class="font-semibold text-xs mt-2">After:</div>
                                            <pre
                                                class="bg-green-50 p-2 rounded text-xs">{{ json_encode($audit['after_data'] ?? [], JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </details>
                                </td>
                                <td class="px-2 py-1">
                                    <button wire:click="rollbackAudit('{{ $audit['id'] }}')"
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