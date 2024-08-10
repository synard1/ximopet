<div>
    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    @if($isOpen)
        @include('livewire.user.add-user-modal')
    @endif
</div>
