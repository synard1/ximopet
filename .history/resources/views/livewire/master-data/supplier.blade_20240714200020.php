<div>
    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    <button wire:click="create()" class="btn btn-primary mb-3">Create New Contact</button>

    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Alamat</th>
                <th>Email</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach($suppliers as $supplier)
            <tr>
                <td>{{ $supplier->kode }}</td>
                <td>{{ $supplier->nama }}</td>
                <td>{{ $supplier->alamat }}</td>
                <td>{{ $supplier->email }}</td>
                <td>
                    <button wire:click="edit('{{ $supplier->id }}')" class="btn btn-sm btn-info">Edit</button>
                    <button wire:click="delete({{ $supplier->id }}'')" class="btn btn-sm btn-danger">Delete</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($isOpen)
        @include('livewire.master-data._create_supplier')
    @endif
</div>
