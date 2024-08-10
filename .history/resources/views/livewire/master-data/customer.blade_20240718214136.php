<div>
    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    {{-- <button wire:click="create()" class="btn btn-primary mb-3">Create New Contact</button>

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
            @foreach($customers as $customer)
            <tr>
                <td>{{ $customer->kode }}</td>
                <td>{{ $customer->nama }}</td>
                <td>{{ $customer->alamat }}</td>
                <td>{{ $customer->email }}</td>
                <td>
                    <button wire:click="edit('{{ $customer->id }}')" class="btn btn-sm btn-info">Edit</button>
                    <button wire:click="delete('{{ $customer->id }}')" class="btn btn-sm btn-danger">Delete</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table> --}}

    @if($isOpen)
        @include('livewire.master-data._edit_')
    @endif
</div>
