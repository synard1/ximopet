<div>
    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    @if($isOpen)
        @include('livewire.user._create_supplier')
    @endif
</div>
