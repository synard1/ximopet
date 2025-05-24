<div class="d-flex justify-content-end flex-shrink-0">
    @can('edit roles')
    <button type="button" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1"
        wire:click="$dispatch('editRole', { id: {{ $role->id }} })">
        {!! getIcon('pencil', 'fs-3') !!}
    </button>
    @endcan

    @can('delete roles')
    <button type="button" class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm me-1"
        wire:click="$dispatch('deleteRole', { id: {{ $role->id }} })">
        {!! getIcon('trash', 'fs-3') !!}
    </button>
    @endcan

    <a href="{{ route('user-management.roles.show', $role) }}"
        class="btn btn-icon btn-bg-light btn-active-color-primary btn-sm">
        {!! getIcon('eye', 'fs-3') !!}
    </a>
</div>