{{-- resources/views/livewire/menu/partials/menu-diff-item.blade.php --}}
@props(['items', 'type', 'level' => 0])

@php
$statusColorClass = [
'added' => 'text-success',
'modified' => 'text-warning',
'deleted' => 'text-danger',
][$type] ?? '';

$statusBadgeClass = [
'added' => 'bg-success-subtle text-success',
'modified' => 'bg-warning-subtle text-warning',
'deleted' => 'bg-danger-subtle text-danger',
][$type] ?? '';

$statusIconClass = [
'added' => 'bi bi-plus-circle',
'modified' => 'bi bi-pencil-square',
'deleted' => 'bi bi-x-circle',
][$type] ?? '';
@endphp

{{-- Render list items for the given type (added, modified, or deleted) --}}
<ul class="mb-0 list-unstyled ps-{{ $level * 4 }}"> {{-- Adjust ps-* for indentation --}}
    @foreach($items as $item)
    @php
    // Data for the current item (from backup for added, from current for deleted, current for modified display)
    $itemData = $item['data'] ?? [];
    $backupData = $item['backup_data'] ?? []; // Only for modified
    @endphp

    <li class="d-flex align-items-center {{ $statusColorClass }}">
        <i class="{{ $statusIconClass }} me-1"></i>
        <span>
            <strong>{{ $itemData['name'] ?? 'N/A' }}</strong> ({{ $itemData['label'] ?? 'N/A' }})
        </span>
        <span class="badge {{ $statusBadgeClass }} ms-2">{{ $type }}</span>
    </li>

    {{-- Display specific changes for modified items --}}
    @if ($type === 'modified' && !empty($item['changes']))
    <ul class="list-unstyled ms-4 mt-1 mb-1 border-start ps-3">
        @foreach($item['changes'] as $field => $change)
        @php
        $currentValue = is_array($change['current']) ? implode(', ', $change['current']) : (string) $change['current'];
        $backupValue = is_array($change['backup']) ? implode(', ', $change['backup']) : (string) $change['backup'];
        @endphp
        <li class="text-muted small">
            <strong>{{ ucfirst($field) }}:</strong>
            @if ($currentValue !== '' || $backupValue !== '') {{-- Only show if either value is not empty --}}
            <span class="text-danger">{{ $currentValue === '' ? 'Empty' : $currentValue }}</span> →
            <span class="text-success">{{ $backupValue === '' ? 'Empty' : $backupValue }}</span>
            @else
            <span class="fst-italic">No change in value (both empty)</span>
            @endif
        </li>
        @endforeach
    </ul>
    @endif

    {{-- Display children recursively within this partial --}}
    @if (!empty($item['children']))
    <div class="ms-4 border-start ps-3">
        <div class="text-muted small mt-1 mb-1">Children ({{ count($item['children']) }})</div>
        {{-- Recursively call THIS partial for children of the SAME type --}}
        @include('livewire.menu.partials.menu-diff-item', ['items' => $item['children'], 'type' => $type, 'level' =>
        $level + 1])
    </div>
    @endif

    @endforeach
</ul>