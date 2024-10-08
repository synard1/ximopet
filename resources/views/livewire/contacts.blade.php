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
            @foreach($contacts as $contact)
            <tr>
                <td>{{ $contact->kode }}</td>
                <td>{{ $contact->nama }}</td>
                <td>{{ $contact->alamat }}</td>
                <td>{{ $contact->email }}</td>
                <td>
                    <button wire:click="edit({{ $contact->id }})" class="btn btn-sm btn-info">Edit</button>
                    <button wire:click="delete({{ $contact->id }})" class="btn btn-sm btn-danger">Delete</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($isOpen)
        @include('livewire.create')
    @endif
</div>
