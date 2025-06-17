<div>
    @if ($showForm)
    <form wire:submit.prevent="save" class="space-y-4">
        <div>
            <label class="block mb-1 font-medium">User</label>
            <select wire:model="user_id" class="form-control rounded" required>
                <option value="">Pilih User</option>
                @foreach($users as $user)
                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                @endforeach
            </select>
            @error('user_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
        </div>
        <div>
            <label class="block mb-1 font-medium">Company</label>
            <select wire:model="company_id" class="form-control rounded" required>
                <option value="">Pilih Company</option>
                @foreach($companies as $company)
                <option value="{{ $company->id }}">{{ $company->name }}</option>
                @endforeach
            </select>
            @error('company_id')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
        </div>
        <div>
            <label class="block mb-1 font-medium">Status</label>
            <select wire:model="status" class="form-control rounded" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            @error('status')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
        </div>
        <div>
            <label class="block mb-1 font-medium">Admin</label>
            <select wire:model="isAdmin" class="form-control rounded" required>
                <option value="0">No</option>
                <option value="1">Yes</option>
            </select>
            @error('isAdmin')<span class="text-red-500 text-xs">{{ $message }}</span>@enderror
        </div>
        <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="btn btn-light border px-5 py-2 rounded hover:bg-gray-100 transition"
                onclick="Livewire.dispatch('closeMapping')">Batal</button>
            <button type="submit"
                class="btn btn-primary px-5 py-2 rounded hover:bg-orange-600 transition">Simpan</button>
        </div>
        @if (session()->has('success'))
        <div class="mt-2 text-green-600">{{ session('success') }}</div>
        @endif
    </form>
    @endif
</div>