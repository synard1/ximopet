<div>

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
                    <button wire:click="edit({{ $contact->id }})" class="btn btn-sm btn-info">Edit</button>
                    <button wire:click="delete({{ $contact->id }})" class="btn btn-sm btn-danger">Delete</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
@if($isOpen)
    @include('livewire.master-data._create_supplier')
@endif
</div>

