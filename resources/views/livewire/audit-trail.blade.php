<div>
    <div class="p-6 bg-white rounded-lg shadow-lg">
        <h2 class="text-2xl font-bold mb-6 text-gray-800">Audit Trail Data Perubahan</h2>

        @if(session('success'))
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('success') }}
        </div>
        @endif

        <form wire:submit.prevent class="mb-6 flex flex-wrap gap-3 items-center">
            <select wire:model.live="modelType" class="border rounded px-3 py-2 focus:ring focus:ring-blue-200">
                <option value="">Semua Model</option>
                <option value="App\\Models\\SupplyStock">SupplyStock</option>
                <option value="App\\Models\\SupplyPurchase">SupplyPurchase</option>
                <option value="App\\Models\\Feed">Feed</option>
                <option value="App\\Models\\Livestock">Livestock</option>
            </select>
            <input type="text" wire:model.live="user" placeholder="User"
                class="border rounded px-3 py-2 focus:ring focus:ring-blue-200" />
            <input type="date" wire:model.live="date" class="border rounded px-3 py-2 focus:ring focus:ring-blue-200" />
            <input type="text" wire:model.live="search" placeholder="Cari ID/Data"
                class="border rounded px-3 py-2 focus:ring focus:ring-blue-200" />
        </form>

        <div class="overflow-x-auto rounded-lg border">
            <table class="min-w-full text-xs">
                <thead>
                    <tr class="bg-gray-50 text-gray-700">
                        <th class="px-3 py-2 border-b">Waktu</th>
                        <th class="px-3 py-2 border-b">User</th>
                        <th class="px-3 py-2 border-b">Model</th>
                        <th class="px-3 py-2 border-b">Model ID</th>
                        <th class="px-3 py-2 border-b">Aksi</th>
                        <th class="px-3 py-2 border-b">Ringkasan</th>
                        <th class="px-3 py-2 border-b">Detail</th>
                        <th class="px-3 py-2 border-b">Rollback</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditTrails as $audit)
                    <tr class="border-b hover:bg-blue-50 transition-all">
                        <td class="px-3 py-2 border-b whitespace-nowrap">{{ $audit->created_at }}</td>
                        <td class="px-3 py-2 border-b whitespace-nowrap">{{ $audit->user?->name ?? '-' }}</td>
                        <td class="px-3 py-2 border-b font-semibold text-blue-700">{{ class_basename($audit->model_type)
                            }}</td>
                        <td class="px-3 py-2 border-b text-xs text-gray-600">{{ $audit->model_id }}</td>
                        <td class="px-3 py-2 border-b">
                            <span class="inline-block px-2 py-1 rounded text-xs font-semibold
                                @if(str_contains($audit->action, 'fix')) bg-yellow-100 text-yellow-800
                                @elseif(str_contains($audit->action, 'rollback')) bg-red-100 text-red-700
                                @else bg-gray-100 text-gray-700 @endif">
                                {{ $audit->action }}
                            </span>
                        </td>
                        <td class="px-3 py-2 border-b text-xs text-gray-500">{{
                            Str::limit(json_encode($audit->after_data), 40) }}</td>
                        <td class="px-3 py-2 border-b">
                            <button wire:click="showDetail('{{ $audit->id }}')"
                                class="px-3 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition">Detail</button>
                        </td>
                        <td class="px-3 py-2 border-b">
                            <button wire:click="confirmRollback('{{ $audit->id }}')"
                                class="px-3 py-1 bg-yellow-400 text-white rounded hover:bg-yellow-500 transition disabled:opacity-50"
                                @if(str_contains($audit->action, 'rollback')) disabled @endif>Rollback</button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-6 text-gray-400">Tidak ada data audit trail.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $auditTrails->links() }}</div>

        @if($showDetailId)
        @php $audit = $auditTrails->where('id', $showDetailId)->first(); @endphp
        <div class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-2xl p-8 w-full max-w-2xl relative">
                <button wire:click="hideDetail"
                    class="absolute top-2 right-2 text-gray-500 hover:text-gray-700 text-xl">✖</button>
                <h3 class="text-lg font-bold mb-6 text-gray-800">Detail Perubahan</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="p-4 bg-red-50 rounded border">
                        <h4 class="font-semibold text-red-700 mb-2">Before</h4>
                        <pre
                            class="text-xs whitespace-pre-wrap">{{ json_encode($audit->before_data, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                    <div class="p-4 bg-green-50 rounded border">
                        <h4 class="font-semibold text-green-700 mb-2">After</h4>
                        <pre
                            class="text-xs whitespace-pre-wrap">{{ json_encode($audit->after_data, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($confirmRollbackId)
        <div class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-2xl p-8 w-full max-w-md relative">
                <h3 class="text-lg font-bold mb-4 text-gray-800">Konfirmasi Rollback</h3>
                <p class="mb-4">Yakin ingin rollback data ini ke versi sebelumnya?</p>
                <div class="flex gap-3">
                    <button wire:click="rollback('{{ $confirmRollbackId }}')"
                        class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition">Ya,
                        Rollback</button>
                    <button wire:click="cancelRollback"
                        class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400 transition">Batal</button>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>