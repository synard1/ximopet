<div class="card" id="companyFormCard" style="display: none;">
    <div class="card-header">
        <h3 class="card-title">{{ $isEditing ? 'Edit Company' : 'Create New Company' }}</h3>
    </div>
    <div class="card-body">
        <form wire:submit.prevent="save">
            <div class="row mb-5">
                <div class="col-md-6">
                    <label class="form-label required">Company Name</label>
                    <input type="text" wire:model="name" class="form-control" placeholder="Enter company name" />
                    @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label required">Email</label>
                    <input type="email" wire:model="email" class="form-control" placeholder="Enter email" />
                    @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-6">
                    <label class="form-label required">Phone</label>
                    <input type="text" wire:model="phone" class="form-control" placeholder="Enter phone number" />
                    @error('phone') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label required">Domain</label>
                    <input type="text" wire:model="domain" class="form-control" placeholder="Enter domain" />
                    @error('domain') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-6">
                    <label class="form-label required">Database</label>
                    <input type="text" wire:model="database" class="form-control" placeholder="Enter database name" />
                    @error('database') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label required">Package</label>
                    <input type="text" wire:model="package" class="form-control" placeholder="Enter package" />
                    @error('package') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-6">
                    <label class="form-label required">Status</label>
                    <select wire:model="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    @error('status') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Logo</label>
                    <input type="file" wire:model="logo" class="form-control" accept="image/*" />
                    @error('logo') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-12">
                    <label class="form-label required">Address</label>
                    <textarea wire:model="address" class="form-control" rows="3" placeholder="Enter address"></textarea>
                    @error('address') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-12">
                    <label class="form-label">Keterangan</label>
                    <textarea wire:model="keterangan" class="form-control" rows="3"
                        placeholder="Enter additional information"></textarea>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        {{ $isEditing ? 'Update Company' : 'Create Company' }}
                    </button>
                    <button type="button" wire:click="$dispatch('closeForm')" class="btn btn-secondary">
                        Cancel
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>