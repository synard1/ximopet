<div>
    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    @if($isOpen)
        @include('livewire.master-data._edit_customer')
    @endif
</div>
